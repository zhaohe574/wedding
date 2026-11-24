<?php
declare(strict_types=1);

/**
 * 批次4：菜单清单重构（manifest 2026-11-23.1）后的角色授权迁移（只增不减）。
 *
 * 清单重构新增了一批 Hub / 拆分菜单与「我的工作」三个分组目录，它们默认没有任何
 * 角色授权 —— 已按旧页面授权过的角色，侧边栏会突然少一整块入口。这里按 menu_key
 * 幂等补授：角色此前授权过映射中任意一个旧来源菜单，就补授对应新菜单，
 * 并沿 pid 链补齐全部祖先目录（否则 linear_to_tree 会把整棵子树丢掉）。
 *
 * ⚠️ 必须先执行 `php think admin_menu:sync --apply` 把清单落库（新菜单有了 id），
 *    再运行本脚本，否则目标 key 在库中不存在会被跳过。
 *
 * 默认 dry-run，加 --apply 落库（事务内插入 + 清 AdminAuthCache）。
 *
 * 用法：
 *   php server/database/migrations/20261123_admin_menu_refactor_grants.php
 *   php server/database/migrations/20261123_admin_menu_refactor_grants.php --apply
 */
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
$app->initialize();

set_exception_handler(static function (Throwable $e): void {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
});

$db = think\facade\Db::connect();
$prefix = (string)config('database.connections.mysql.prefix', 'la_');
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) {
    throw new RuntimeException('数据库前缀不合法');
}
$apply = in_array('--apply', $argv, true);
$menuTable = $prefix . 'system_menu';
$roleMenuTable = $prefix . 'system_role_menu';

// 新菜单 => 旧来源菜单（角色授权过任意一个来源，就补授目标及其全部祖先）
$mappings = [
    'page.order.trade_rules' => ['page.setting.feature_switch'],
    'page.review.content_security' => ['page.setting.feature_switch', 'page.review.sensitive'],
    'page.staff.governance' => ['page.staff.certificate', 'page.staff.tag_review', 'page.service.tag', 'page.setting.feature_switch'],
    'page.setting.system.ops_hub' => ['page.setting.system.environment', 'page.setting.system.cache', 'page.setting.feature_switch'],
    'page.aftersale.hub' => ['page.aftersale.ticket', 'page.aftersale.complaint', 'page.aftersale.callback'],
    'page.dynamic.hub' => ['page.dynamic.lists', 'page.dynamic.comment.review'],
    'page.review.hub' => ['page.review.lists', 'page.review.tag'],
    'page.questionnaire.hub' => ['page.questionnaire.couple_questionnaire', 'page.questionnaire.couple_questionnaire_task', 'page.questionnaire.couple_question_bank'],
    'page.article.hub' => ['page.article.lists', 'page.article.column'],
    'page.setting.pay.hub' => ['page.setting.pay.method', 'page.setting.pay.config'],
    'page.channel.weapp_hub' => ['page.channel.weapp', 'page.setting.search'],
    'page.channel.wx_oa.hub' => [
        'page.channel.wx_oa.config',
        'page.channel.wx_oa.menu',
        'page.channel.wx_oa.reply.follow_reply',
        'page.channel.wx_oa.reply.keyword_reply',
        'page.channel.wx_oa.reply.default_reply',
    ],
    'page.message.hub' => [
        'page.notification.lists',
        'page.message.oa_notification',
        'page.message.mnp_notification',
        'page.message.notice',
        'page.message.short_letter',
    ],
    'page.setting.website.hub' => [
        'page.setting.website.information',
        'page.setting.order_confirm_letter',
        'page.setting.website.filing',
        'page.setting.website.protocol',
        'page.setting.website.statistics',
    ],
    'page.setting.user.hub' => ['page.setting.user.login_register', 'page.setting.user.setup'],
    'nav.staff-center.fulfillment' => [
        'page.staff_center.calendar',
        'page.staff_center.order',
        'page.staff_center.rule',
        'page.staff_center.couple_questionnaire',
        'page.staff_center.schedule_confirm_letter',
        'page.staff_center.aftersale',
    ],
    'nav.staff-center.profile-group' => [
        'page.staff_center.profile',
        'page.staff_center.showcase',
        'page.staff_center.work',
        'page.staff_center.certificate',
        'page.staff_center.package',
        'page.staff_center.dynamic',
    ],
    'nav.staff-center.team-group' => ['page.staff_center.team', 'page.staff_center.team_review'],
];

// 库内索引：menu_key => id；id => pid
$idByKey = [];
$pidById = [];
foreach ($db->table($menuTable)->whereNotNull('menu_key')->field('id,pid,menu_key')->select()->toArray() as $row) {
    $idByKey[(string)$row['menu_key']] = (int)$row['id'];
    $pidById[(int)$row['id']] = (int)$row['pid'];
}

// 角色：超管账号不走 role_menu 授权（root=1 直接放全量菜单），其余角色逐个幂等补授
$roles = $db->table($prefix . 'system_role')
    ->whereNull('delete_time')
    ->field('id,name')
    ->select()->toArray();
$roles = array_values(array_filter($roles, static fn($r) => (string)$r['name'] !== '超级管理员'));
if (!$roles) {
    echo "库中无角色，无需处理。\n";
    exit(0);
}

$plans = [];
foreach ($roles as $role) {
    $roleId = (int)$role['id'];
    $existing = array_flip(array_map('intval', $db->table($roleMenuTable)->where('role_id', $roleId)->column('menu_id')));

    $grant = [];
    foreach ($mappings as $targetKey => $sourceKeys) {
        $hit = null;
        foreach ($sourceKeys as $sourceKey) {
            if (isset($idByKey[$sourceKey], $existing[$idByKey[$sourceKey]])) {
                $hit = $sourceKey;
                break;
            }
        }
        if ($hit === null) {
            continue;
        }
        if (!isset($idByKey[$targetKey])) {
            echo "跳过（库中无此 key）：{$targetKey}（角色「{$role['name']}」已授权来源 {$hit}）\n";
            continue;
        }
        // 目标自身 + 沿 pid 链向上的全部祖先目录
        $cursor = $idByKey[$targetKey];
        while ($cursor > 0) {
            $grant[$cursor] = $targetKey;
            $cursor = $pidById[$cursor] ?? 0;
        }
    }

    if ($grant) {
        $plans[$roleId] = ['name' => (string)$role['name'], 'grant' => $grant, 'existing' => $existing];
    }
}

// 汇总待补授权并输出
$pendingIds = [];
foreach ($plans as $roleId => &$plan) {
    $plan['pending'] = array_values(array_filter(array_keys($plan['grant']), static fn($id) => !isset($plan['existing'][$id])));
    $pendingIds = array_merge($pendingIds, $plan['pending']);
}
unset($plan);

$names = [];
if ($pendingIds) {
    $names = $db->table($menuTable)->whereIn('id', array_values(array_unique($pendingIds)))->column('name', 'id');
}

$total = 0;
foreach ($plans as $roleId => $plan) {
    echo "角色：{$plan['name']}（id={$roleId}） 待补授权 " . count($plan['pending']) . " 条（含祖先）\n";
    foreach ($plan['pending'] as $id) {
        echo "  + {$id} " . ($names[$id] ?? '-') . "（来源 {$plan['grant'][$id]}）\n";
    }
    $total += count($plan['pending']);
}

if (!$total) {
    echo "无需处理。\n";
    exit(0);
}
if (!$apply) {
    echo "\n[DRY-RUN] 未落库。加 --apply 执行。\n";
    exit(0);
}

$db->startTrans();
try {
    foreach ($plans as $roleId => $plan) {
        foreach ($plan['pending'] as $id) {
            $db->table($roleMenuTable)->insert(['role_id' => $roleId, 'menu_id' => $id]);
        }
    }
    $db->commit();
    if (class_exists(\app\common\cache\AdminAuthCache::class)) {
        (new \app\common\cache\AdminAuthCache())->deleteTag();
    }
} catch (Throwable $e) {
    $db->rollback();
    throw $e;
}

echo "已补授权 {$total} 条。\n";
