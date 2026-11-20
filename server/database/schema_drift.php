<?php
declare(strict_types=1);

/**
 * 数据库结构漂移扫描。
 *
 * 背景：历史库往往没执行过全部 schema 变更，代码一跑就报
 *   Unknown column 'xxx' in 'where clause' / Table 'xxx' doesn't exist
 * 逐个 500 去追效率极低，所以这里直接拿「代码仓库里的 DDL」对比「真实库」。
 *
 * DDL 来源（按优先级合并）：
 *   public/install/db/like.sql      安装基线
 *   database/schema/*.sql           独立建表脚本
 *   database/migrations/*.sql       增量变更
 *
 * 用法：
 *   php database/schema_drift.php            # 只报告
 *   php database/schema_drift.php --sql      # 输出可直接执行的补齐 SQL
 *   php database/schema_drift.php --apply    # 直接补齐（仅新增表/列，不改不删）
 *
 * 退出码：0 无漂移；1 存在漂移（便于 CI 使用）
 */

require dirname(__DIR__) . '/vendor/autoload.php';
$app = new think\App(dirname(__DIR__) . DIRECTORY_SEPARATOR);
$app->initialize();

$apply = in_array('--apply', $argv, true);
$emitSql = in_array('--sql', $argv, true) || $apply;

$prefix = (string)config('database.connections.mysql.prefix', 'la_');
$db = think\facade\Db::connect();

// ---------------------------------------------------------------- 解析 DDL

/** @return array<string, array{cols: array<string,string>, create: string}> */
function parseDdl(array $files, string $prefix): array
{
    $tables = [];

    foreach ($files as $file) {
        $sql = (string)file_get_contents($file);

        // CREATE TABLE
        if (preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`([a-zA-Z0-9_]+)`\s*\(([\s\S]*?)\n\)\s*ENGINE/i', $sql, $m, PREG_SET_ORDER)) {
            foreach ($m as $one) {
                $name = $one[1];
                if (!isset($tables[$name])) $tables[$name] = ['cols' => [], 'create' => ''];
                $tables[$name]['create'] = $one[0] . ';';
                foreach (explode("\n", $one[2]) as $line) {
                    if (!preg_match('/^\s*`([a-zA-Z0-9_]+)`\s+(.+?),?\s*$/m', $line, $cm)) continue;
                    $colName = $cm[1];
                    // GENERATED 列不能直接 ADD，跳过
                    if (stripos($cm[2], 'GENERATED ALWAYS') !== false) continue;
                    $tables[$name]['cols'][$colName] = rtrim($cm[2], ',');
                }
            }
        }

        // ALTER TABLE ... ADD COLUMN
        if (preg_match_all('/ALTER\s+TABLE\s+`([a-zA-Z0-9_]+)`\s+([\s\S]*?);/i', $sql, $am, PREG_SET_ORDER)) {
            foreach ($am as $one) {
                $name = $one[1];
                if (!isset($tables[$name])) $tables[$name] = ['cols' => [], 'create' => ''];
                if (preg_match_all('/ADD\s+(?:COLUMN\s+)?`([a-zA-Z0-9_]+)`\s+([^,;]+)/i', $one[2], $cols, PREG_SET_ORDER)) {
                    foreach ($cols as $c) {
                        if (stripos($c[2], 'GENERATED ALWAYS') !== false) continue;
                        $tables[$name]['cols'][$c[1]] = trim($c[2]);
                    }
                }
            }
        }
    }

    // 只保留当前前缀的表
    $result = [];
    foreach ($tables as $name => $info) {
        if (strpos($name, $prefix) === 0) {
            $result[$name] = $info;
        }
    }
    return $result;
}

$files = [];
foreach ([__DIR__ . '/../public/install/db/like.sql'] as $f) {
    if (is_file($f)) $files[] = $f;
}
foreach (['schema', 'migrations'] as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (!is_dir($path)) continue;
    foreach (glob($path . '/*.sql') ?: [] as $f) $files[] = $f;
}

if (!$files) {
    fwrite(STDERR, "未找到任何 DDL 文件\n");
    exit(1);
}

$expected = parseDdl($files, $prefix);

// ---------------------------------------------------------------- 对比真实库

$actualTables = [];
foreach ($db->query('SHOW TABLES') as $row) {
    $actualTables[] = (string)array_values($row)[0];
}
$actualTables = array_flip($actualTables);

$missingTables = [];
$missingCols = [];

foreach ($expected as $table => $info) {
    if (!isset($actualTables[$table])) {
        $missingTables[$table] = $info['create'];
        continue;
    }
    $actualCols = array_flip(array_column($db->query("SHOW COLUMNS FROM `{$table}`"), 'Field'));
    foreach ($info['cols'] as $col => $definition) {
        if (!isset($actualCols[$col])) {
            $missingCols[$table][] = [$col, $definition];
        }
    }
}

// ---------------------------------------------------------------- 输出

echo "DDL 来源 " . count($files) . " 个文件 / 期望表 " . count($expected) . " 张 / 实际表 " . count($actualTables) . " 张\n";

if (!$missingTables && !$missingCols) {
    echo "结构一致，无漂移\n";
    exit(0);
}

if ($missingTables) {
    echo "\n缺失的表（" . count($missingTables) . "）：\n";
    foreach ($missingTables as $t => $_) echo "  - {$t}\n";
}

if ($missingCols) {
    echo "\n缺失的列（" . array_sum(array_map('count', $missingCols)) . "）：\n";
    foreach ($missingCols as $t => $cols) {
        foreach ($cols as [$c, $_]) echo "  - {$t}.{$c}\n";
    }
}

$statements = [];
foreach ($missingTables as $t => $create) {
    $statements[] = $create;
}
foreach ($missingCols as $t => $cols) {
    foreach ($cols as [$c, $def]) {
        $statements[] = "ALTER TABLE `{$t}` ADD COLUMN `{$c}` {$def}";
    }
}

if ($emitSql) {
    echo "\n---- 补齐 SQL ----\n";
    foreach ($statements as $s) echo $s . ";\n";
}

if (!$apply) {
    echo "\n（未执行。加 --apply 自动补齐，或加 --sql 只看语句）\n";
    exit(1);
}

echo "\n---- 执行 ----\n";
$failed = 0;
foreach ($statements as $s) {
    try {
        $db->execute($s);
        echo "  [ok] " . substr($s, 0, 80) . "...\n";
    } catch (\Throwable $e) {
        $failed++;
        echo "  [fail] " . substr($s, 0, 80) . " -> " . $e->getMessage() . "\n";
    }
}

echo $failed === 0 ? "\n全部补齐完成\n" : "\n有 {$failed} 条失败，需人工处理\n";
exit($failed === 0 ? 0 : 1);
