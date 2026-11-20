<?php

declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

/**
 * 菜单清单一致性校验（CI 用）。
 *
 * 五项检查，任何一项失败都会让线上「菜单点了白屏 / 菜单凭空消失 / 权限 403」：
 *   1. 清单自身：key 唯一、component 不重复、type 合法
 *   2. component 在 admin/src/views 下**恰好命中一个** .vue
 *      —— 前端 loadRouteView 用的是 includes() 模糊匹配，写错不报错只白屏
 *   3. 清单与库一致（--strict 时差异即失败）
 *   4. policy 里每个 key 都能解析到库内菜单 id（魔法数字替换成 key 后的保命符）
 *   5. 授权孤儿为 0（子已授权、父未授权会让整棵子树消失）
 *
 * 用法：
 *   php think admin_menu:check            # 只报问题，不因差异失败
 *   php think admin_menu:check --strict   # 任一项失败即 exit 1
 */
class AdminMenuCheck extends Command
{
    protected string $viewsDir = '';
    protected array $manifest = [];

    protected function configure()
    {
        $this->setName('admin_menu:check')
            ->addOption('strict', null, Option::VALUE_NONE, '任一问题即返回非 0')
            ->setDescription('校验菜单清单：component 命中唯一性 / 清单与库一致性 / policy 可解析 / 授权孤儿');
    }

    protected function execute(Input $input, Output $output)
    {
        $strict = (bool)$input->getOption('strict');
        $connection = (string)config('database.default', 'mysql');
        $prefix = (string)config('database.connections.' . $connection . '.prefix', 'la_');
        $menuTable = $prefix . 'system_menu';
        $roleMenuTable = $prefix . 'system_role_menu';

        $manifestPath = root_path() . 'database/menu/menu_manifest.php';
        if (!is_file($manifestPath)) {
            $output->error('找不到 menu_manifest.php');
            return 1;
        }
        $this->manifest = require $manifestPath;
        $this->viewsDir = dirname(root_path(), 1) . DIRECTORY_SEPARATOR . 'admin/src/views';

        $problems = [];

        // ---------- 1. 清单自身 ----------
        $flat = [];
        $walk = function (array $nodes, ?string $parent) use (&$walk, &$flat, &$problems) {
            foreach ($nodes as $node) {
                $key = (string)($node['key'] ?? '');
                if ($key === '') {
                    $problems[] = '清单节点缺少 key：' . json_encode($node, JSON_UNESCAPED_UNICODE);
                    continue;
                }
                if (isset($flat[$key])) {
                    $problems[] = "key 重复：{$key}";
                }
                $type = (string)($node['type'] ?? '');
                if (!in_array($type, ['M', 'C'], true)) {
                    $problems[] = "{$key} 的 type 非法（只允许 M / C）：{$type}";
                }
                if ($type === 'C' && empty($node['component'])) {
                    $problems[] = "{$key} 是 C 但缺少 component";
                }
                if ($type === 'M' && empty($node['path'])) {
                    $problems[] = "{$key} 是 M 但缺少 path";
                }
                $flat[$key] = ['node' => $node, 'parent' => $parent];
                $walk((array)($node['children'] ?? []), $key);
            }
        };
        $walk((array)($this->manifest['menus'] ?? []), null);

        // ---------- 2. component 唯一命中 ----------
        $components = [];
        foreach ($flat as $key => $entry) {
            if (($entry['node']['type'] ?? '') !== 'C') {
                continue;
            }
            $component = (string)$entry['node']['component'];
            if (isset($components[$component])) {
                $problems[] = "component 在清单内重复：{$component}（{$components[$component]} 与 {$key}）";
            }
            $components[$component] = $key;

            $hits = $this->matchViews($component);
            if (count($hits) === 0) {
                $problems[] = "component 找不到对应文件：{$component}（{$key}）";
            } elseif (count($hits) > 1) {
                $problems[] = "component 模糊匹配到多个文件：{$component} → " . implode(', ', $hits) . "（{$key}）";
            }
        }

        // ---------- 3. 清单与库一致 ----------
        $dbRows = [];
        foreach (Db::table($menuTable)->whereNotNull('menu_key')->field('menu_key,type,name,is_disable')->select() as $r) {
            $dbRows[(string)$r['menu_key']] = $r;
        }
        $missingInDb = array_diff(array_keys($flat), array_keys($dbRows));
        $missingInManifest = array_diff(array_keys($dbRows), array_keys($flat));
        foreach ($missingInDb as $key) {
            $problems[] = "清单有、库中无（需 sync --apply）：{$key}";
        }
        foreach ($missingInManifest as $key) {
            $problems[] = "库中有 key、清单中无（删清单前请先退役）：{$key}";
        }

        // ---------- 4. policy 可解析 ----------
        $policy = (array)($this->manifest['policy'] ?? []);
        foreach ($policy as $bucket => $keys) {
            foreach ((array)$keys as $key) {
                if (!isset($flat[$key])) {
                    $problems[] = "policy.{$bucket} 引用了清单里不存在的 key：{$key}";
                    continue;
                }
                if (!isset($dbRows[$key])) {
                    $problems[] = "policy.{$bucket} 的 key 在库中不存在：{$key}";
                }
            }
        }

        // ---------- 5. 授权孤儿 ----------
        $orphans = Db::query(
            "SELECT rm.role_id, rm.menu_id, m.pid
             FROM `{$roleMenuTable}` rm
             JOIN `{$menuTable}` m ON m.id = rm.menu_id
             WHERE m.pid > 0 AND NOT EXISTS (
                 SELECT 1 FROM `{$roleMenuTable}` p
                 WHERE p.role_id = rm.role_id AND p.menu_id = m.pid
             )"
        );
        foreach ($orphans as $o) {
            $problems[] = "授权孤儿：role={$o['role_id']} 授权了 menu={$o['menu_id']} 但未授权其父 {$o['pid']}（该子树会整棵消失）";
        }

        // ---------- 输出 ----------
        $output->writeln('清单节点：' . count($flat) . '　库内已托管：' . count($dbRows) . '　policy：' . count((array)($policy['public'] ?? [])) . ' 项公共');
        if (!$problems) {
            $output->writeln('<info>菜单校验通过</info>');
            return 0;
        }

        foreach ($problems as $p) {
            $output->writeln('  - ' . $p);
        }
        $output->writeln('');
        $output->writeln('<comment>共 ' . count($problems) . ' 个问题</comment>');

        return $strict ? 1 : 0;
    }

    /**
     * 复刻前端 loadRouteView 的 includes() 语义，检查命中数。
     * 前端写错 component 只会 console.error 然后渲染空 RouterView，页面白屏不报错。
     */
    protected function matchViews(string $component): array
    {
        $needle = '/' . $component . '.vue';
        $hits = [];
        if (!is_dir($this->viewsDir)) {
            return $hits;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->viewsDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'vue') {
                continue;
            }
            $path = str_replace('\\', '/', $file->getPathname());
            if (strpos($path, $needle) !== false) {
                $hits[] = str_replace(str_replace('\\', '/', $this->viewsDir) . '/', '', $path);
            }
        }
        return $hits;
    }
}
