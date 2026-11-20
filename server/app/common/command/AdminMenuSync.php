<?php

declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

/**
 * 后台菜单同步（B0 地基）。
 *
 * 单一事实源 = server/database/menu/menu_manifest.php。
 * 本命令只做四件事，且**永不物理删除**菜单：
 *   1. --dry-run（默认）  比对清单与库，输出三分报告
 *   2. --apply            快照 → 前序 upsert → 退役标记 → 祖先闭包补授权
 *   3. --rollback=BATCH   按批次还原字段、删除本批新增（新增行若已被授权则拒绝）
 *   4. --adopt=ID:KEY     把某条未托管行认领到指定 key
 *   另有 --export=PATH     导出库内菜单与角色授权快照（只读，用于对账）
 *
 * 硬规则：menu_key IS NULL 的行是「未托管」（运营手工菜单 / A 型权限 / 历史残留），
 * 本命令只读它们、绝不改写 —— 这是不误伤线上数据的前提。
 */
class AdminMenuSync extends Command
{
    /** 参与比对的字段（manifest 字段名 => 数据库列名）。pid 单独处理。 */
    protected const FIELDS = [
        'type' => 'type',
        'name' => 'name',
        'icon' => 'icon',
        'sort' => 'sort',
        'perms' => 'perms',
        'path' => 'paths',
        'component' => 'component',
        'selected' => 'selected',
        'params' => 'params',
        'is_cache' => 'is_cache',
        'is_show' => 'is_show',
        'is_disable' => 'is_disable'
    ];

    protected string $menuTable = 'la_system_menu';
    protected string $roleMenuTable = 'la_system_role_menu';
    protected string $menuSnapshot = 'la_system_menu_snapshot';
    protected string $roleMenuSnapshot = 'la_system_role_menu_snapshot';
    protected array $manifest = [];

    protected function configure()
    {
        $this->setName('admin_menu:sync')
            ->addOption('apply', null, Option::VALUE_NONE, '落库（默认只打印差异）')
            ->addOption('dry-run', null, Option::VALUE_NONE, '只打印差异（默认行为）')
            ->addOption('batch', null, Option::VALUE_REQUIRED, '批次标识，如 20261108B2')
            ->addOption('rollback', null, Option::VALUE_REQUIRED, '按批次回滚')
            ->addOption('adopt', null, Option::VALUE_REQUIRED, '认领未托管行，格式 id:key')
            ->addOption('export', null, Option::VALUE_REQUIRED, '导出快照到文件（只读）')
            ->setDescription('按 menu_manifest.php 同步后台菜单（幂等 / 可回滚 / 不删数据）');
    }

    protected function execute(Input $input, Output $output)
    {
        $connection = (string)config('database.default', 'mysql');
        $prefix = (string)config('database.connections.' . $connection . '.prefix', 'la_');
        if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) {
            throw new \RuntimeException('数据库表前缀不合法');
        }
        $this->menuTable = $prefix . 'system_menu';
        $this->roleMenuTable = $prefix . 'system_role_menu';
        $this->menuSnapshot = $prefix . 'system_menu_snapshot';
        $this->roleMenuSnapshot = $prefix . 'system_role_menu_snapshot';

        $manifestPath = root_path() . 'database/menu/menu_manifest.php';
        if (!is_file($manifestPath)) {
            $output->error('找不到 ' . $manifestPath);
            return 1;
        }
        $manifest = require $manifestPath;
        if (!is_array($manifest['menus'] ?? null)) {
            $output->error('menu_manifest.php 结构不合法（缺少 menus）');
            return 1;
        }
        $this->manifest = $manifest;

        if ($export = $input->getOption('export')) {
            return $this->doExport((string)$export, $output);
        }
        if ($adopt = $input->getOption('adopt')) {
            return $this->doAdopt((string)$adopt, $output);
        }
        if ($rollback = $input->getOption('rollback')) {
            return $this->doRollback((string)$rollback, $output);
        }

        $apply = (bool)$input->getOption('apply');
        $batch = (string)($input->getOption('batch') ?: date('YmdHis'));

        return $apply ? $this->doApply($batch, $output) : $this->doDiff($output);
    }

    // ------------------------------------------------------------------ 清单

    /**
     * 把嵌套清单压平成 [key => entry]，顺序保持前序（父在前、子在后），
     * 落库时天然满足「父先子后」。
     */
    protected function flatten(): array
    {
        $out = [];
        $walk = function (array $nodes, ?string $parentKey) use (&$walk, &$out) {
            foreach ($nodes as $node) {
                $key = (string)($node['key'] ?? '');
                if ($key === '') {
                    throw new \RuntimeException('清单节点缺少 key：' . json_encode($node, JSON_UNESCAPED_UNICODE));
                }
                if (isset($out[$key])) {
                    throw new \RuntimeException('清单 key 重复：' . $key);
                }
                $out[$key] = ['node' => $node, 'parent' => $parentKey];
                $walk((array)($node['children'] ?? []), $key);
            }
        };
        $walk($this->manifest['menus'], null);
        return $out;
    }

    /** manifest 节点 → 目标列值；$pid 为 null 表示「不参与 pid 比对」 */
    protected function targetRow(array $node, ?int $pid): array
    {
        $row = [
            'type' => (string)($node['type'] ?? 'C'),
            'name' => (string)($node['name'] ?? ''),
            'icon' => (string)($node['icon'] ?? ''),
            'sort' => (int)($node['sort'] ?? 0),
            'perms' => (string)($node['perms'] ?? ''),
            'paths' => (string)($node['path'] ?? ''),
            'component' => (string)($node['component'] ?? ''),
            'selected' => (string)($node['selected'] ?? ''),
            'params' => (string)($node['params'] ?? ''),
            'is_cache' => (int)($node['is_cache'] ?? 0),
            'is_show' => (int)($node['is_show'] ?? 1),
            'is_disable' => !empty($node['retired']) ? 1 : 0
        ];
        if ($pid !== null) {
            $row = ['pid' => $pid] + $row;
        }
        return $row;
    }

    // ------------------------------------------------------------------ diff

    protected function doDiff(Output $output): int
    {
        $plan = $this->buildPlan();
        $output->writeln('<info>清单版本</info> ' . ($this->manifest['version'] ?? '-'));
        $output->writeln(
            '清单节点：' . count($plan['flat'])
            . '　库内已托管：' . $plan['managedCount']
            . '　未托管：' . $plan['unmanagedCount']
        );

        $output->writeln('');
        $output->writeln('== 需新增 ' . count($plan['insert']) . ' 条 ==');
        foreach (array_slice($plan['insert'], 0, 50) as $item) {
            $output->writeln('  + ' . $item['key'] . '  ' . $item['node']['name']
                . '  (' . ($item['node']['component'] ?? '-') . ')');
        }

        $output->writeln('');
        $output->writeln('== 需更新 ' . count($plan['update']) . ' 条 ==');
        foreach (array_slice($plan['update'], 0, 80) as $item) {
            $output->writeln((!empty($item['adopt']) ? '  ⊙ ' : '  ~ ') . $item['key']
                . '  ' . ($item['node']['name'] ?? '') . '  [' . implode(', ', array_keys($item['diff'])) . ']');
            foreach ($item['diff'] as $field => $pair) {
                $output->writeln('      ' . $field . ': ' . $this->show($pair[0]) . ' → ' . $this->show($pair[1]));
            }
        }

        $output->writeln('');
        $output->writeln('== 未托管（不会改动）' . $plan['unmanagedCount'] . ' 条 ==');
        foreach (array_slice($plan['unmanaged'], 0, 20) as $row) {
            $output->writeln('  · id=' . $row['id'] . ' ' . $row['type'] . ' ' . $row['name']
                . ' | paths=' . $row['paths']);
        }

        $output->writeln('');
        $output->writeln('== 授权孤儿（子已授权、父未授权，会导致菜单整棵消失）' . count($plan['orphans']) . ' 条 ==');
        foreach (array_slice($plan['orphans'], 0, 20) as $row) {
            $output->writeln('  ! role=' . $row['role_id'] . ' menu=' . $row['menu_id'] . ' 缺父 ' . $row['pid']);
        }

        $total = count($plan['insert']) + count($plan['update']);
        $output->writeln('');
        $output->writeln($total
            ? "<comment>共 {$total} 处差异，加 --apply 落库</comment>"
            : '<info>无差异</info>');
        return 0;
    }

    protected function show($v): string
    {
        if ($v === null || $v === '') {
            return "''";
        }
        return is_int($v) ? (string)$v : "'" . $v . "'";
    }

    // ------------------------------------------------------------------ plan

    protected function buildPlan(): array
    {
        $flat = $this->flatten();
        $rows = Db::table($this->menuTable)->select()->toArray();

        $byKey = [];
        $unmanagedById = [];
        foreach ($rows as $row) {
            $key = (string)($row['menu_key'] ?? '');
            if ($key === '') {
                $unmanagedById[(int)$row['id']] = $row;
            } else {
                $byKey[$key] = $row;
            }
        }

        // 升级库兜底认领：清单节点的 legacy_ids 是「按 component / paths 精确匹配出来的库内 id」，
        // 只有同时满足「未托管 + component（或 paths）一致」才认领。
        // 不允许按 id 盲目认领 —— 本库 440 段 id 曾整体偏移，盲认会把按钮当页面。
        $adoptByKey = [];
        foreach ($flat as $key => $entry) {
            if (isset($byKey[$key])) {
                continue;
            }
            foreach ((array)($entry['node']['legacy_ids'] ?? []) as $id) {
                $id = (int)$id;
                $row = $unmanagedById[$id] ?? null;
                if (!$row) {
                    continue;
                }
                if ($this->looksSame($entry['node'], $row)) {
                    $adoptByKey[$key] = $id;
                }
                break;
            }
        }

        $insert = [];
        $update = [];
        foreach ($flat as $key => $entry) {
            $node = $entry['node'];
            $parentId = $entry['parent'] === null ? 0 : (int)($byKey[$entry['parent']]['id'] ?? 0);
            // 父节点本身是新增时，dry-run 阶段拿不到 id，pid 不参与比对（apply 时再算）
            $pidKnown = $entry['parent'] === null || isset($byKey[$entry['parent']]);

            if (isset($byKey[$key])) {
                $target = $this->targetRow($node, $pidKnown ? $parentId : null);
                $diff = [];
                foreach ($target as $col => $new) {
                    $old = $byKey[$key][$col] ?? null;
                    if ((string)$old !== (string)$new) {
                        $field = array_search($col, self::FIELDS, true) ?: $col;
                        $diff[$field] = [$old, $new];
                    }
                }
                if ($diff) {
                    $update[] = [
                        'key' => $key,
                        'node' => $node,
                        'id' => (int)$byKey[$key]['id'],
                        'diff' => $diff
                    ];
                }
                continue;
            }

            if (isset($adoptByKey[$key])) {
                $id = $adoptByKey[$key];
                $target = $this->targetRow($node, $pidKnown ? $parentId : null);
                $diff = ['menu_key' => [null, $key]];
                foreach ($target as $col => $new) {
                    $old = $unmanagedById[$id][$col] ?? null;
                    if ((string)$old !== (string)$new) {
                        $field = array_search($col, self::FIELDS, true) ?: $col;
                        $diff[$field] = [$old, $new];
                    }
                }
                $update[] = ['key' => $key, 'node' => $node, 'id' => $id, 'diff' => $diff, 'adopt' => true];
                continue;
            }

            $insert[] = ['key' => $key, 'node' => $node, 'parent' => $entry['parent']];
        }

        return [
            'flat' => $flat,
            'insert' => $insert,
            'update' => $update,
            'unmanaged' => array_values($unmanagedById),
            'unmanagedCount' => count($unmanagedById),
            'managedCount' => count($byKey),
            'orphans' => $this->findOrphans()
        ];
    }

    /**
     * 认领前的最后一道校验：库内行必须与清单节点「长得一样」。
     * C 比 component，M 比 paths —— 只靠 id 会把按钮误当页面（库内 id 存在整体偏移）。
     */
    protected function looksSame(array $node, array $row): bool
    {
        if ((string)($row['type'] ?? '') !== (string)($node['type'] ?? '')) {
            return false;
        }
        if (($node['type'] ?? '') === 'C') {
            return (string)($row['component'] ?? '') === (string)($node['component'] ?? '');
        }
        return (string)($row['paths'] ?? '') === (string)($node['path'] ?? '');
    }

    /** 子已授权但父未授权 —— linear_to_tree 会把整棵子树丢掉 */    protected function findOrphans(): array
    {
        $sql = "SELECT rm.role_id, rm.menu_id, m.pid
                FROM `{$this->roleMenuTable}` rm
                JOIN `{$this->menuTable}` m ON m.id = rm.menu_id
                WHERE m.pid > 0 AND NOT EXISTS (
                    SELECT 1 FROM `{$this->roleMenuTable}` p
                    WHERE p.role_id = rm.role_id AND p.menu_id = m.pid
                )";
        return Db::query($sql);
    }

    // ------------------------------------------------------------------ apply

    protected function doApply(string $batch, Output $output): int
    {
        $flat = $this->flatten();
        $created = 0;
        $changed = 0;
        $adopted = 0;

        Db::startTrans();
        try {
            $keyId = [];
            foreach (Db::table($this->menuTable)->whereNotNull('menu_key')->field('id,menu_key')->select() as $r) {
                $keyId[(string)$r['menu_key']] = (int)$r['id'];
            }
            // 兜底认领：按清单 legacy_ids + component/paths 双重校验挂 key
            $unmanagedRows = [];
            foreach (Db::table($this->menuTable)->whereNull('menu_key')
                ->field('id,type,component,paths')->select() as $r) {
                $unmanagedRows[(int)$r['id']] = $r;
            }
            foreach ($flat as $key => $entry) {
                if (isset($keyId[$key])) {
                    continue;
                }
                foreach ((array)($entry['node']['legacy_ids'] ?? []) as $id) {
                    $id = (int)$id;
                    $row = $unmanagedRows[$id] ?? null;
                    if ($row && $this->looksSame($entry['node'], $row)) {
                        Db::execute("UPDATE `{$this->menuTable}` SET `menu_key` = ? WHERE `id` = ?", [$key, $id]);
                        $keyId[$key] = $id;
                        $adopted++;
                    }
                    break;
                }
            }

            foreach ($flat as $key => $entry) {
                $node = $entry['node'];
                $pid = $entry['parent'] === null ? 0 : (int)($keyId[$entry['parent']] ?? 0);
                $target = $this->targetRow($node, $pid);

                if (isset($keyId[$key])) {
                    $current = Db::table($this->menuTable)->where('id', $keyId[$key])->find();
                    $diff = [];
                    foreach ($target as $col => $new) {
                        if ((string)($current[$col] ?? '') !== (string)$new) {
                            $diff[$col] = $new;
                        }
                    }
                    if ($diff) {
                        $this->snapshotMenu($batch, (int)$keyId[$key], 'update', $current);
                        Db::table($this->menuTable)->where('id', $keyId[$key])->update($diff);
                        $changed++;
                    }
                    continue;
                }

                $now = time();
                $row = $target + ['menu_key' => $key, 'create_time' => $now, 'update_time' => $now];
                $newId = (int)Db::table($this->menuTable)->insertGetId($row);
                $keyId[$key] = $newId;
                $this->snapshotMenu($batch, $newId, 'insert', $row);
                $created++;
            }

            // 祖先闭包：给缺父的授权补上父级（只增不删）
            $granted = 0;
            foreach ($this->findOrphans() as $orphan) {
                $roleId = (int)$orphan['role_id'];
                $cursor = (int)$orphan['pid'];
                while ($cursor > 0) {
                    $exists = Db::table($this->roleMenuTable)
                        ->where('role_id', $roleId)->where('menu_id', $cursor)->count();
                    if (!$exists) {
                        Db::table($this->roleMenuTable)->insert(['role_id' => $roleId, 'menu_id' => $cursor]);
                        Db::table($this->roleMenuSnapshot)->insert([
                            'batch_id' => $batch,
                            'role_id' => $roleId,
                            'menu_id' => $cursor,
                            'action' => 'grant',
                            'create_time' => time()
                        ]);
                        $granted++;
                    }
                    $cursor = (int)Db::table($this->menuTable)->where('id', $cursor)->value('pid');
                }
            }

            Db::commit();

            if (class_exists(\app\common\cache\AdminAuthCache::class)) {
                (new \app\common\cache\AdminAuthCache())->deleteTag();
            }
        } catch (\Throwable $e) {
            Db::rollback();
            $output->error('落库失败已回滚：' . $e->getMessage());
            return 1;
        }

        $output->writeln("<info>批次 {$batch} 完成</info>");
        $output->writeln("认领 {$adopted} / 新增 {$created} / 变更 {$changed} / 补授权 {$granted}");
        $output->writeln('回滚：php think admin_menu:sync --rollback=' . $batch);
        return 0;
    }

    protected function snapshotMenu(string $batch, int $menuId, string $action, array $payload): void
    {
        Db::table($this->menuSnapshot)->insert([
            'batch_id' => $batch,
            'menu_id' => $menuId,
            'action' => $action,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'create_time' => time()
        ]);
    }

    // ------------------------------------------------------------------ rollback

    protected function doRollback(string $batch, Output $output): int
    {
        $rows = Db::table($this->menuSnapshot)->where('batch_id', $batch)->order('id', 'desc')->select()->toArray();
        if (!$rows) {
            $output->error('找不到批次 ' . $batch . ' 的快照');
            return 1;
        }

        Db::startTrans();
        try {
            $restored = 0;
            $deleted = 0;
            foreach ($rows as $snap) {
                $payload = json_decode((string)$snap['payload'], true) ?: [];
                $menuId = (int)$snap['menu_id'];
                if ($snap['action'] === 'insert') {
                    $refs = Db::table($this->roleMenuTable)->where('menu_id', $menuId)->count();
                    if ($refs > 0) {
                        throw new \RuntimeException("菜单 {$menuId} 已被角色授权（{$refs} 条），请先撤销授权再回滚");
                    }
                    Db::table($this->menuTable)->where('id', $menuId)->delete();
                    $deleted++;
                    continue;
                }
                $fields = [];
                foreach (array_keys(self::FIELDS) as $mf) {
                    $col = self::FIELDS[$mf];
                    if (array_key_exists($col, $payload)) {
                        $fields[$col] = $payload[$col];
                    }
                }
                if (array_key_exists('pid', $payload)) {
                    $fields['pid'] = $payload['pid'];
                }
                if ($fields) {
                    Db::table($this->menuTable)->where('id', $menuId)->update($fields);
                    $restored++;
                }
            }

            $revoked = 0;
            foreach (Db::table($this->roleMenuSnapshot)->where('batch_id', $batch)->select() as $g) {
                $revoked += (int)Db::table($this->roleMenuTable)
                    ->where('role_id', (int)$g['role_id'])->where('menu_id', (int)$g['menu_id'])->delete();
            }

            Db::commit();

            if (class_exists(\app\common\cache\AdminAuthCache::class)) {
                (new \app\common\cache\AdminAuthCache())->deleteTag();
            }

            $output->writeln("<info>批次 {$batch} 已回滚</info>");
            $output->writeln("还原 {$restored} / 删除新增 {$deleted} / 撤销补授权 {$revoked}");
            return 0;
        } catch (\Throwable $e) {
            Db::rollback();
            $output->error('回滚失败已回滚事务：' . $e->getMessage());
            return 1;
        }
    }

    // ------------------------------------------------------------------ adopt / export

    protected function doAdopt(string $spec, Output $output): int
    {
        if (!preg_match('/^(\d+):([A-Za-z0-9_.\-]+)$/', $spec, $m)) {
            $output->error('格式应为 id:key，例如 612:ops.tools.poster');
            return 1;
        }
        $id = (int)$m[1];
        $key = (string)$m[2];
        $row = Db::table($this->menuTable)->where('id', $id)->find();
        if (!$row) {
            $output->error('菜单不存在：' . $id);
            return 1;
        }
        if (!empty($row['menu_key'])) {
            $output->error('该行已被托管为 ' . $row['menu_key'] . '，如需换 key 请先改清单');
            return 1;
        }
        if (Db::table($this->menuTable)->where('menu_key', $key)->count()) {
            $output->error('key 已被占用：' . $key);
            return 1;
        }
        Db::execute("UPDATE `{$this->menuTable}` SET `menu_key` = ? WHERE `id` = ?", [$key, $id]);
        $output->writeln("<info>已认领</info> id={$id} → {$key}");
        $output->writeln('提示：该 key 若未写入 manifest，下次 apply 不会主动改它；建议同步补进清单。');
        return 0;
    }

    protected function doExport(string $path, Output $output): int
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $data = [
            'exported_at' => date('c'),
            'menu_count' => Db::table($this->menuTable)->count(),
            'role_menu_count' => Db::table($this->roleMenuTable)->count(),
            'menus' => Db::table($this->menuTable)->order('id', 'asc')->select()->toArray(),
            'role_menu' => Db::table($this->roleMenuTable)->order('role_id', 'asc')->select()->toArray()
        ];
        file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $output->writeln("<info>已导出</info> {$path}");
        $output->writeln('菜单 ' . $data['menu_count'] . ' 条 / 授权 ' . $data['role_menu_count'] . ' 条');
        return 0;
    }
}
