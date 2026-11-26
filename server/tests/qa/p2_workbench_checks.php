<?php
declare(strict_types=1);

/**
 * P2-C 服务人员工作台 P2 迭代静态与契约回归检查（零数据库依赖）。
 *
 * 覆盖：服务日作战面板 / 档期海报与电子名片 / 文案模板库 / 权限隔离门禁。
 * 执行：php server/tests/qa/p2_workbench_checks.php
 */

$serverRoot = dirname(__DIR__, 2);
$repoRoot = dirname($serverRoot);
require $serverRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$app = new \think\App($serverRoot);
$app->initialize();

use app\api\controller\StaffCenterController;
use app\api\logic\StaffCenterLogic;
use app\api\validate\StaffCenterValidate;
use app\common\model\staff\StaffCopyTemplate;

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
// 1. StaffCopyTemplate 模型场景与 12 条内置系统话术校验
// ----------------------------------------------------------------
$expectedScenes = ['inquiry', 'confirm', 'pre_wedding', 'service_day', 'after_service', 'waitlist'];
$assertSame(array_keys(StaffCopyTemplate::SCENES), $expectedScenes, 'StaffCopyTemplate 定义 6 个场景常量');

$sceneOptions = StaffCopyTemplate::getSceneOptions();
$assertSame(count($sceneOptions), 6, 'getSceneOptions 返回 6 个场景');

$presets = StaffCopyTemplate::getDefaultSystemTemplates();
$assertSame(count($presets), 12, '内置系统预设模板共 12 条');

$seenPresetKeys = [];
foreach ($presets as $idx => $preset) {
    $key = (string)($preset['preset_key'] ?? '');
    $scene = (string)($preset['scene'] ?? '');
    $title = trim((string)($preset['title'] ?? ''));
    $content = trim((string)($preset['content'] ?? ''));
    $assertTrue($key !== '' && !isset($seenPresetKeys[$key]), "预设模板 #{$idx} preset_key 唯一且非空");
    $seenPresetKeys[$key] = true;
    $assertTrue(in_array($scene, $expectedScenes, true), "预设模板 {$key} scene 属于合法枚举");
    $assertTrue($title !== '' && $content !== '', "预设模板 {$key} 标题与正文非空");
}

// ----------------------------------------------------------------
// 2. Controller / Logic / Validate 方法与场景对齐校验
// ----------------------------------------------------------------
$ctrlRef = new ReflectionClass(StaffCenterController::class);
$ctrlMethods = [
    'serviceDayPanel',
    'businessCard',
    'copyTemplateLists',
    'copyTemplateSave',
    'copyTemplateDelete',
    'copyTemplateUse',
    'scheduleConfirmLetterConfig',
    'scheduleConfirmLetterPreview',
    'scheduleConfirmLetterSaveConfig',
    'orderConfirmLetterGenerate',
    'orderConfirmLetterHistory',
    'orderConfirmLetterDetail',
];
foreach ($ctrlMethods as $method) {
    $assertTrue($ctrlRef->hasMethod($method), "StaffCenterController 包含 {$method}()");
    $assertTrue($ctrlRef->getMethod($method)->isPublic(), "StaffCenterController::{$method}() 为 public");
}

$logicRef = new ReflectionClass(StaffCenterLogic::class);
foreach ([
    'serviceDayPanel',
    'businessCard',
    'copyTemplateLists',
    'copyTemplateSave',
    'copyTemplateDelete',
    'copyTemplateUse',
    'scheduleConfirmLetterConfig',
] as $method) {
    $assertTrue($logicRef->hasMethod($method), "StaffCenterLogic 包含 {$method}()");
}

$logicSrc = (string)file_get_contents($serverRoot . '/app/api/logic/StaffCenterLogic.php');
$assertTrue(str_contains($logicSrc, 'color_presets'), 'StaffCenterLogic::scheduleConfirmLetterConfig 返回 color_presets');
$assertTrue(str_contains($logicSrc, '#191713'), 'StaffCenterLogic 包含玄墨黑主题色预设');

$validateRef = new ReflectionClass(StaffCenterValidate::class);
foreach ([
    'sceneServiceDayPanel',
    'sceneCopyTemplateLists',
    'sceneCopyTemplateSave',
    'sceneCopyTemplateAction',
] as $sceneMethod) {
    $assertTrue($validateRef->hasMethod($sceneMethod), "StaffCenterValidate 注册验证场景方法 {$sceneMethod}()");
}

// ----------------------------------------------------------------
// 3. DDL 与迁移脚本校验
// ----------------------------------------------------------------
$likeSql = (string)file_get_contents($serverRoot . '/public/install/db/like.sql');
$assertTrue(str_contains($likeSql, 'la_staff_copy_template'), 'like.sql 包含 la_staff_copy_template 建表语句');

$migrationPath = $serverRoot . '/database/migrations/20261120_staff_copy_template.php';
$assertTrue(is_file($migrationPath), '存在增量迁移脚本 20261120_staff_copy_template.php');
$migrationSrc = (string)file_get_contents($migrationPath);
$assertTrue(str_contains($migrationSrc, 'la_staff_copy_template'), '迁移脚本目标为 la_staff_copy_template');

// ----------------------------------------------------------------
// 4. newuniapp 页面、组件、路由与工作台守卫隔离校验
// ----------------------------------------------------------------
$pagesJsonPath = $repoRoot . '/newuniapp/src/pages.json';
$pagesJson = (string)file_get_contents($pagesJsonPath);
foreach ([
    'pages/service-day/service-day',
    'pages/card-poster/card-poster',
    'pages/copy-templates/copy-templates',
] as $routePath) {
    $assertTrue(str_contains($pagesJson, $routePath), "pages.json 已注册 {$routePath}");
}

$linkTs = (string)file_get_contents($repoRoot . '/newuniapp/src/utils/link.ts');
foreach ([
    '/packages/pkg-staff/pages/service-day/service-day',
    '/packages/pkg-staff/pages/card-poster/card-poster',
    '/packages/pkg-staff/pages/copy-templates/copy-templates',
    'staff_schedule_confirm_letter',
] as $token) {
    $assertTrue(str_contains($linkTs, $token), "link.ts 已登记 {$token}");
}

$staffComponentFiles = [
    'newuniapp/src/packages/pkg-staff/components/business-card-canvas/business-card-canvas.vue',
    'newuniapp/src/packages/pkg-staff/components/schedule-poster-editor/schedule-poster-editor.vue',
    'newuniapp/src/packages/pkg-staff/components/copy-template-sheet/copy-template-sheet.vue',
];
foreach ($staffComponentFiles as $rel) {
    $assertTrue(is_file($repoRoot . '/' . $rel), "工作台子组件存在: {$rel}");
}

$staffPageFiles = [
    'newuniapp/src/packages/pkg-staff/pages/service-day/service-day.vue',
    'newuniapp/src/packages/pkg-staff/pages/card-poster/card-poster.vue',
    'newuniapp/src/packages/pkg-staff/pages/copy-templates/copy-templates.vue',
];
foreach ($staffPageFiles as $rel) {
    $fullPath = $repoRoot . '/' . $rel;
    $assertTrue(is_file($fullPath), "工作台页面存在: {$rel}");
    $src = (string)file_get_contents($fullPath);
    $assertTrue(str_contains($src, 'useStaffGuard'), "{$rel} 引入并调用 useStaffGuard");
    $assertTrue(str_contains($src, '<staff-guard'), "{$rel} 挂载 <staff-guard />");
}

// 权限隔离检查：客户端页面目录严禁引用档期确认函/海报生成与推送端点
$customerDirs = [
    $repoRoot . '/newuniapp/src/pages',
    $repoRoot . '/newuniapp/src/packages/pkg-order',
    $repoRoot . '/newuniapp/src/packages/pkg-catalog',
    $repoRoot . '/newuniapp/src/packages/pkg-service',
    $repoRoot . '/newuniapp/src/packages/pkg-user',
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
        $assertTrue(
            !str_contains($src, 'orderConfirmLetter') && !str_contains($src, 'scheduleConfirmLetter'),
            "客户端页面不得引用确认函/海报能力: {$rel}"
        );
    }
}

// ----------------------------------------------------------------
// 5. 平台统一话术模板（后台 + 工作台对接）静态与契约校验
// ----------------------------------------------------------------
$variableOptions = StaffCopyTemplate::getVariableOptions();
$assertSame(count($variableOptions), 7, 'getVariableOptions 返回 7 个动态变量项');
foreach ([
    '{customer_name}',
    '{service_date}',
    '{service_address}',
    '{package_name}',
    '{staff_name}',
    '{category_name}',
    '{price_text}',
] as $variableKey) {
    $found = false;
    foreach ($variableOptions as $option) {
        if (($option['key'] ?? null) === $variableKey) {
            $found = true;
            break;
        }
    }
    $assertTrue($found, "动态变量选项包含 {$variableKey}");
}

$copyTemplateCtrlMethods = [
    'lists',
    'detail',
    'add',
    'edit',
    'delete',
    'changeStatus',
    'sceneOptions',
    'importDefaults',
];
foreach ([
    'app\\adminapi\\controller\\staff\\StaffCopyTemplateController',
    'app\\adminapi\\controller\\ops\\StaffCopyTemplateController',
] as $copyTemplateCtrlClass) {
    $ctrlRef = new ReflectionClass($copyTemplateCtrlClass);
    foreach ($copyTemplateCtrlMethods as $method) {
        $assertTrue($ctrlRef->hasMethod($method), "{$copyTemplateCtrlClass} 包含 {$method}()");
        $assertTrue($ctrlRef->getMethod($method)->isPublic(), "{$copyTemplateCtrlClass}::{$method}() 为 public");
    }
}

$adminCopyTemplatePage = $repoRoot . '/admin/src/views/staff/copy_template/index.vue';
$adminCopyTemplateApi = $repoRoot . '/admin/src/api/staff-copy-template.ts';
$assertTrue(is_file($adminCopyTemplatePage), '后台统一话术页面存在: admin/src/views/staff/copy_template/index.vue');
$assertTrue(is_file($adminCopyTemplateApi), '后台统一话术 API 存在: admin/src/api/staff-copy-template.ts');

$governanceHubSrc = (string)file_get_contents($repoRoot . '/admin/src/views/staff/governance/index.vue');
$assertTrue(str_contains($governanceHubSrc, 'copy_template'), 'governance Hub 挂载 copy_template 页面组件');
$assertTrue(str_contains($governanceHubSrc, 'ops.staffCopyTemplate/lists'), 'governance Hub 登记 ops.staffCopyTemplate/lists 权限');

echo sprintf("[OK] p2_workbench_checks passed (%d assertions)\n", $assertions);
