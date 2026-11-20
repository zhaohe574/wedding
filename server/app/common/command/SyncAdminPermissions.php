<?php

declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

/** 将新增操作权限登记到已有数据库，不改变角色授权。 */
class SyncAdminPermissions extends Command
{
    /**
     * 权限迁移文件匹配规则：database/migrations 下按文件名顺序执行
     * - 20260914_admin_permissions.sql
     * - 20261105_admin_permission_gaps.sql
     */
    protected const MIGRATION_PATTERN = '/(?:admin_permissions|admin_permission_gaps)\.sql$/';

    protected function configure()
    {
        $this->setName('sync_admin_permissions')->setDescription('补登记后台操作权限，不自动授予角色');
    }

    protected function execute(Input $input, Output $output)
    {
        $connection = (string)config('database.default', 'mysql');
        $prefix = (string)config('database.connections.' . $connection . '.prefix', 'la_');
        if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) {
            throw new \RuntimeException('数据库表前缀不合法');
        }

        $files = $this->resolveMigrationFiles();
        if (empty($files)) {
            $output->writeln('未找到权限迁移文件');
            return 1;
        }

        foreach ($files as $file) {
            $sql = (string)file_get_contents($file);
            $sql = (string)preg_replace('/^--.*$/m', '', $sql);
            $statements = $this->splitStatements(str_replace('`la_', '`' . $prefix, $sql));

            Db::transaction(static function () use ($statements) {
                foreach ($statements as $statement) {
                    Db::execute($statement);
                }
            });

            $output->writeln('已处理：' . basename($file) . '（' . count($statements) . ' 条语句）');
        }

        (new \app\common\cache\AdminAuthCache())->deleteTag();
        $output->writeln('权限登记完成，请按岗位需要在角色管理中分配新增操作。');
        return 0;
    }

    /**
     * @notes 按文件名顺序返回权限迁移文件
     * @return array<string>
     */
    protected function resolveMigrationFiles(): array
    {
        $dir = dirname(__DIR__, 3) . '/database/migrations';
        $files = glob($dir . '/*.sql') ?: [];
        $files = array_values(array_filter($files, static function (string $file): bool {
            return (bool)preg_match(self::MIGRATION_PATTERN, $file);
        }));
        sort($files, SORT_STRING);

        return $files;
    }

    /**
     * @notes 拆分 SQL 语句
     *
     * 迁移文件里存在 `(SELECT ... ) AS existing` 这类子查询，直接按分号 explode 足够，
     * 但仍需过滤空语句与纯注释行。
     *
     * @param string $sql
     * @return array<string>
     */
    protected function splitStatements(string $sql): array
    {
        $statements = [];
        foreach (explode(';', $sql) as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }
            $statements[] = $statement;
        }

        return $statements;
    }
}
