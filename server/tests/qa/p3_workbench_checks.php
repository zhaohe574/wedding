<?php
declare(strict_types=1);

/**
 * P3 服务人员工作台 P3 迭代静态与契约回归检查（零数据库依赖）。
 *
 * 覆盖：新人档案 / 服务手记 / 跟进提醒 / 婚礼 Checklist / 团队成员管理 / 权限隔离门禁。
 * 执行：php server/tests/qa/p3_workbench_checks.php
 */

$serverRoot = dirname(__DIR__, 2);
$repoRoot = dirname($serverRoot);
require $serverRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$app = new \think\App($serverRoot);
$app->initialize();

use app\api\controller\StaffCenterController;
use app\api\logic\StaffCenterLogic;
use app\api\validate\StaffCenterValidate;
use app\common\model\staff\StaffFollowupReminder;
use app\common\model\staff\StaffServiceNote;
use app\common\model\staff\StaffWeddingChecklist;
use app\common\service\StaffCoupleWorkbenchService;
use app\common\service\StaffTeamWorkbenchService;

$assertions = 0;
$assertSame = static function (mixed $actual, mixed $expected, string $label) use (&$assertions): void {
    $assertions++;
    if ($actual !== $expected) {
        throw new RuntimeException(sprintf(
            "[FAIL] %s\n  expected: %s\n  actual:   %s",
            $label,
            var_export($expected, true),
            var_export($actual, true)
        ));
    }
};
$assertTrue = static function (bool $cond, string $label) use (&$assertions): void {
    $assertions++;
    if (!$cond) {
        throw new RuntimeException("[FAIL] {$label}");
    }
};

// ----------------------------------------------------------------
// 1. P3 三大模型常量、枚举选项与 8 项标准婚礼 Checklist 预设校验
// ----------------------------------------------------------------
$expectedNoteCategories = ['communication', 'preference', 'rehearsal', 'review'];
$assertSame(array_keys(StaffServiceNote::CATEGORIES), $expectedNoteCategories, 'StaffServiceNote 定义 4 个手记分类');
$assertSame(count(StaffServiceNote::getCategoryOptions()), 4, 'StaffServiceNote::getCategoryOptions 返回 4 项');

$expectedRemindTypes = ['pre_comm', 'questionnaire', 'rehearsal', 'balance', 'aftercare', 'other'];
$assertSame(array_keys(StaffFollowupReminder::REMIND_TYPES), $expectedRemindTypes, 'StaffFollowupReminder 定义 6 种提醒类型');
$assertSame(count(StaffFollowupReminder::getRemindTypeOptions()), 6, 'getRemindTypeOptions 返回 6 项');
$assertSame(StaffFollowupReminder::getStatusDesc(StaffFollowupReminder::STATUS_PENDING), '待跟进', 'STATUS_PENDING 状态文案一致');
$assertSame(StaffFollowupReminder::getStatusDesc(StaffFollowupReminder::STATUS_DONE), '已完成', 'STATUS_DONE 状态文案一致');

$expectedStages = ['pre_wedding', 'rehearsal', 'wedding_day', 'after_service'];
$assertSame(array_keys(StaffWeddingChecklist::STAGES), $expectedStages, 'StaffWeddingChecklist 定义 4 个筹备阶段');
$assertSame(count(StaffWeddingChecklist::getStageOptions()), 4, 'getStageOptions 返回 4 个阶段');

$presets = StaffWeddingChecklist::getDefaultChecklistTemplates();
$assertSame(count($presets), 8, '默认婚礼 Checklist 预设共 8 条');
foreach ($presets as $idx => $preset) {
    $stage = (string)($preset['stage'] ?? '');
    $title = trim((string)($preset['title'] ?? ''));
    $remark = trim((string)($preset['remark'] ?? ''));
    $assertTrue(in_array($stage, $expectedStages, true), "Checklist 预设 #{$idx} stage 属于合法阶段");
    $assertTrue($title !== '' && $remark !== '', "Checklist 预设 #{$idx} 标题与备注非空");
}

// ----------------------------------------------------------------
// 2. Service / Controller / Logic / Validate 方法与场景对齐校验
// ----------------------------------------------------------------
$coupleSvcRef = new ReflectionClass(StaffCoupleWorkbenchService::class);
foreach ([
    'coupleLists',
    'coupleDetail',
    'coupleSendQuestionnaire',
    'serviceNoteSave',
    'serviceNoteTogglePin',
    'serviceNoteDelete',
    'followupLists',
    'followupSave',
    'followupAction',
    'followupDelete',
    'checklistInitPresets',
    'checklistSave',
    'checklistToggle',
    'checklistDelete',
    'countPendingFollowups',
    'countActiveCouples',
] as $method) {
    $assertTrue($coupleSvcRef->hasMethod($method), "StaffCoupleWorkbenchService 包含 {$method}()");
}

$teamSvcRef = new ReflectionClass(StaffTeamWorkbenchService::class);
foreach ([
    'teamOverview',
    'teamUpdateInfo',
    'teamMemberCandidates',
    'teamMemberAdd',
    'teamMemberToggleStatus',
    'teamMemberRemove',
    'countMyTeamMembers',
] as $method) {
    $assertTrue($teamSvcRef->hasMethod($method), "StaffTeamWorkbenchService 包含 {$method}()");
}

$ctrlRef = new ReflectionClass(StaffCenterController::class);
$logicRef = new ReflectionClass(StaffCenterLogic::class);
$p3Actions = [
    'coupleLists',
    'coupleDetail',
    'coupleSendQuestionnaire',
    'serviceNoteSave',
    'serviceNoteTogglePin',
    'serviceNoteDelete',
    'followupLists',
    'followupSave',
    'followupAction',
    'followupDelete',
    'checklistInitPresets',
    'checklistSave',
    'checklistToggle',
    'checklistDelete',
    'teamOverview',
    'teamUpdateInfo',
    'teamMemberCandidates',
    'teamMemberAdd',
    'teamMemberToggleStatus',
    'teamMemberRemove',
];
foreach ($p3Actions as $method) {
    $assertTrue($ctrlRef->hasMethod($method), "StaffCenterController 包含 {$method}()");
    $assertTrue($ctrlRef->getMethod($method)->isPublic(), "StaffCenterController::{$method}() 为 public");
    $assertTrue($logicRef->hasMethod($method), "StaffCenterLogic 包含 {$method}()");
}

$logicSrc = (string)file_get_contents($serverRoot . '/app/api/logic/StaffCenterLogic.php');
foreach (['pending_followup_count', 'couple_count', 'team_member_count', 'is_team_leader'] as $dashKey) {
    $assertTrue(str_contains($logicSrc, $dashKey), "StaffCenterLogic::dashboard 包含 {$dashKey}");
}

$validateRef = new ReflectionClass(StaffCenterValidate::class);
foreach ([
    'sceneCoupleLists',
    'sceneCoupleDetail',
    'sceneCoupleSendQuestionnaire',
    'sceneServiceNoteSave',
    'sceneServiceNoteAction',
    'sceneFollowupLists',
    'sceneFollowupSave',
    'sceneFollowupAction',
    'sceneFollowupDelete',
    'sceneChecklistInitPresets',
    'sceneChecklistSave',
    'sceneChecklistItemAction',
    'sceneTeamUpdateInfo',
    'sceneTeamMemberCandidates',
    'sceneTeamMemberAdd',
    'sceneTeamMemberToggleStatus',
    'sceneTeamMemberRemove',
] as $sceneMethod) {
    $assertTrue($validateRef->hasMethod($sceneMethod), "StaffCenterValidate 注册验证场景方法 {$sceneMethod}()");
}

// ----------------------------------------------------------------
// 3. DDL 与增量迁移校验
// ----------------------------------------------------------------
$likeSql = (string)file_get_contents($serverRoot . '/public/install/db/like.sql');
$migrationPath = $serverRoot . '/database/migrations/20261120_staff_workbench_p3_tables.php';
$assertTrue(is_file($migrationPath), '存在增量迁移脚本 20261120_staff_workbench_p3_tables.php');
$migrationSrc = (string)file_get_contents($migrationPath);

foreach ([
    'la_staff_service_note',
    'la_staff_followup_reminder',
    'la_staff_wedding_checklist',
] as $tableName) {
    $assertTrue(str_contains($likeSql, $tableName), "like.sql 包含 {$tableName} 建表语句");
    $assertTrue(str_contains($migrationSrc, $tableName), "迁移脚本包含 {$tableName} 建表语句");
}

// ----------------------------------------------------------------
// 4. newuniapp 页面、组件、路由与工作台守卫隔离校验
// ----------------------------------------------------------------
$pagesJson = (string)file_get_contents($repoRoot . '/newuniapp/src/pages.json');
foreach ([
    'pages/couple-list/couple-list',
    'pages/couple-detail/couple-detail',
    'pages/followups/followups',
    'pages/team-members/team-members',
] as $routePath) {
    $assertTrue(str_contains($pagesJson, $routePath), "pages.json 已注册 {$routePath}");
}

$linkTs = (string)file_get_contents($repoRoot . '/newuniapp/src/utils/link.ts');
foreach ([
    '/packages/pkg-staff/pages/couple-list/couple-list',
    '/packages/pkg-staff/pages/couple-detail/couple-detail',
    '/packages/pkg-staff/pages/followups/followups',
    '/packages/pkg-staff/pages/team-members/team-members',
] as $routePath) {
    $assertTrue(str_contains($linkTs, $routePath), "link.ts 已登记 {$routePath}");
}

$p3Components = [
    'newuniapp/src/packages/pkg-staff/components/service-note-sheet/service-note-sheet.vue',
    'newuniapp/src/packages/pkg-staff/components/followup-sheet/followup-sheet.vue',
    'newuniapp/src/packages/pkg-staff/components/team-member-add-sheet/team-member-add-sheet.vue',
];
foreach ($p3Components as $rel) {
    $assertTrue(is_file($repoRoot . '/' . $rel), "P3 工作台子组件存在: {$rel}");
}

$p3Pages = [
    'newuniapp/src/packages/pkg-staff/pages/couple-list/couple-list.vue',
    'newuniapp/src/packages/pkg-staff/pages/couple-detail/couple-detail.vue',
    'newuniapp/src/packages/pkg-staff/pages/followups/followups.vue',
    'newuniapp/src/packages/pkg-staff/pages/team-members/team-members.vue',
];
foreach ($p3Pages as $rel) {
    $fullPath = $repoRoot . '/' . $rel;
    $assertTrue(is_file($fullPath), "P3 工作台页面存在: {$rel}");
    $src = (string)file_get_contents($fullPath);
    $assertTrue(str_contains($src, 'useStaffGuard'), "{$rel} 引入并调用 useStaffGuard");
    $assertTrue(str_contains($src, '<staff-guard'), "{$rel} 挂载 <staff-guard />");
}

// 权限隔离检查：客户侧页面严禁引用工作台 P3 专属接口
$customerDirs = [
    $repoRoot . '/newuniapp/src/pages',
    $repoRoot . '/newuniapp/src/packages/pkg-order',
    $repoRoot . '/newuniapp/src/packages/pkg-catalog',
    $repoRoot . '/newuniapp/src/packages/pkg-service',
    $repoRoot . '/newuniapp/src/packages/pkg-user',
];
$forbiddenTokens = [
    'coupleLists',
    'coupleDetail',
    'serviceNoteSave',
    'followupLists',
    'followupSave',
    'teamOverview',
    'teamMemberAdd',
];
foreach ($customerDirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'vue') {
            continue;
        }
        $src = (string)file_get_contents($file->getPathname());
        $rel = str_replace($repoRoot . DIRECTORY_SEPARATOR, '', $file->getPathname());
        foreach ($forbiddenTokens as $token) {
            $assertTrue(!str_contains($src, $token), "客户端页面不得引用工作台 P3 能力 {$token}: {$rel}");
        }
    }
}

echo sprintf("[OK] p3_workbench_checks passed (%d assertions)\n", $assertions);
