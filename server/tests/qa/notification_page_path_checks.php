<?php
declare(strict_types=1);

/**
 * 通知模板 / 站内信 / 支付回跳 page_path 对齐回归检查（零数据库依赖）。
 *
 * 执行：php server/tests/qa/notification_page_path_checks.php
 */

$serverRoot = dirname(__DIR__, 2);
$repoRoot = dirname($serverRoot);
require $serverRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use app\common\model\wechat\OaNotificationTemplate;
use app\common\service\MiniappPagePathService;
use app\common\service\StationNotificationService;
use app\common\service\WechatNotificationService;

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

// 1. 解析 newuniapp/src/pages.json（支持 JSONC 注释与尾逗号），收集全部已注册页面
$stripJsonc = static function (string $input): string {
    $out = '';
    $len = strlen($input);
    $inString = false;
    for ($i = 0; $i < $len; $i++) {
        $ch = $input[$i];
        $next = $i + 1 < $len ? $input[$i + 1] : '';
        if ($inString) {
            $out .= $ch;
            if ($ch === '\\' && $i + 1 < $len) {
                $out .= $next;
                $i++;
            } elseif ($ch === '"') {
                $inString = false;
            }
            continue;
        }
        if ($ch === '"') {
            $inString = true;
            $out .= $ch;
        } elseif ($ch === '/' && $next === '/') {
            while ($i < $len && $input[$i] !== "\n") {
                $i++;
            }
            if ($i < $len) {
                $out .= "\n";
            }
        } elseif ($ch === '/' && $next === '*') {
            $i += 2;
            while ($i + 1 < $len && !($input[$i] === '*' && $input[$i + 1] === '/')) {
                $i++;
            }
            $i++;
        } else {
            $out .= $ch;
        }
    }
    return (string) preg_replace('/,\s*([}\]])/', '$1', $out);
};

$pagesJsonPath = $repoRoot . '/newuniapp/src/pages.json';
$pagesRaw = file_get_contents($pagesJsonPath);
$assertTrue($pagesRaw !== false, '读取 newuniapp/src/pages.json 成功');
$pagesConfig = json_decode($stripJsonc((string) $pagesRaw), true);
$assertTrue(is_array($pagesConfig), '解析 newuniapp/src/pages.json JSON 成功');

$registeredPages = [];
foreach (($pagesConfig['pages'] ?? []) as $page) {
    if (!empty($page['path'])) {
        $registeredPages[(string) $page['path']] = true;
    }
}
foreach (($pagesConfig['subPackages'] ?? []) as $pkg) {
    $rootPrefix = trim((string) ($pkg['root'] ?? ''), '/');
    foreach (($pkg['pages'] ?? []) as $page) {
        if (!empty($page['path'])) {
            $full = ($rootPrefix !== '' ? $rootPrefix . '/' : '') . ltrim((string) $page['path'], '/');
            $registeredPages[$full] = true;
        }
    }
}
$assertTrue(count($registeredPages) > 0, 'newuniapp/src/pages.json 页面数大于 0');

$assertValidNewuniappTarget = static function (string $path, string $label) use (
    &$registeredPages,
    $repoRoot,
    $assertTrue
): void {
    $assertTrue($path !== '', "{$label}: 路径非空");
    $assertTrue(!str_starts_with($path, '/'), "{$label}: 不应包含前导斜杠 ({$path})");
    $assertTrue(!str_contains($path, 'packages/pages/'), "{$label}: 不应残留旧包路径 ({$path})");
    $assertTrue(!str_starts_with($path, 'pages/notification/'), "{$label}: 不应残留旧通知页 ({$path})");
    $assertTrue(substr_count($path, '?') <= 1, "{$label}: query 不应出现双问号 ({$path})");

    $route = explode('?', $path, 2)[0];
    $assertTrue(isset($registeredPages[$route]), "{$label}: 路由已在 pages.json 注册 ({$route})");
    $assertTrue(
        is_file($repoRoot . '/newuniapp/src/' . $route . '.vue'),
        "{$label}: Vue 文件存在于 newuniapp/src ({$route}.vue)"
    );
};

// 2. 校验 LEGACY_TO_CURRENT 全量 13 条映射
$mapping = MiniappPagePathService::LEGACY_TO_CURRENT;
$assertSame(count($mapping), 13, 'LEGACY_TO_CURRENT 映射数量为 13');
foreach ($mapping as $legacy => $current) {
    $assertValidNewuniappTarget($current, "LEGACY_TO_CURRENT[{$legacy}]");
}

// 3. 校验 normalize / appendId / sanitizeWechatPagePath 边界场景
$assertSame(MiniappPagePathService::normalize(''), '', 'normalize 空串返回空串');
$assertSame(MiniappPagePathService::normalize('   '), '', 'normalize 纯空白返回空串');
$assertSame(
    MiniappPagePathService::normalize('/packages/pages/order_detail/order_detail'),
    'packages/pkg-order/pages/detail/detail',
    'normalize 去除前导斜杠并转换旧路径'
);
$assertSame(
    MiniappPagePathService::normalize('packages/pages/activity_registration/detail', true),
    '/packages/pkg-user/pages/activity/activity',
    'normalize($leadingSlash=true) 保留单前导斜杠'
);
$assertSame(
    MiniappPagePathService::normalize('packages/pages/order_change/pause_detail'),
    'packages/pkg-service/pages/change/detail?type=pause',
    'normalize pause_detail 附带 type=pause'
);
$assertSame(
    MiniappPagePathService::normalize('/packages/pages/order_change/pause_detail?id=42'),
    'packages/pkg-service/pages/change/detail?type=pause&id=42',
    'normalize pause_detail 已有 query 时合并为 &id='
);
$assertSame(
    MiniappPagePathService::normalize('packages/pages/order_change/pause_detail?type=pause&id=42'),
    'packages/pkg-service/pages/change/detail?type=pause&id=42',
    'normalize pause_detail 去重已有 type=pause 参数'
);
$assertSame(
    MiniappPagePathService::appendId('packages/pages/order_change/pause_detail', 88),
    'packages/pkg-service/pages/change/detail?type=pause&id=88',
    'appendId pause_detail 正确使用 &id='
);
$assertSame(
    MiniappPagePathService::appendId('packages/pkg-service/pages/change/detail?type=pause&id=15', 88),
    'packages/pkg-service/pages/change/detail?type=pause&id=15',
    'appendId 已有 id= 时不覆盖不重复追加'
);
$assertSame(
    MiniappPagePathService::appendId('packages/pkg-service/pages/change/detail?pid=15', 88),
    'packages/pkg-service/pages/change/detail?pid=15&id=88',
    'appendId 对 pid= 不误判为 id='
);
$assertSame(
    MiniappPagePathService::appendId('packages/pages/order_detail/order_detail', 0),
    'packages/pkg-order/pages/detail/detail',
    'appendId businessId<=0 不追加 id='
);
$assertSame(
    MiniappPagePathService::normalize('packages/pkg-order/pages/detail/detail?id=9'),
    'packages/pkg-order/pages/detail/detail?id=9',
    'normalize 对新路径透传保留'
);
$assertSame(
    MiniappPagePathService::normalize('https://example.com/pay/callback?order=1', true),
    'https://example.com/pay/callback?order=1',
    'normalize 透传 http(s) URL'
);
$assertSame(
    MiniappPagePathService::sanitizeWechatPagePath('/packages/pages/order_change/pause_detail?id=7'),
    'packages/pkg-service/pages/change/detail?type=pause&id=7',
    'sanitizeWechatPagePath 输出无斜杠规范路径'
);
$assertSame(
    MiniappPagePathService::sanitizeWechatPagePath('packages/pkg-order/pages/detail/detail?id=1 2'),
    '',
    'sanitizeWechatPagePath 拦截含空格的非法路径'
);

// 4. 校验 StationNotificationService::target() 全部目标
$stationRef = new ReflectionClass(StationNotificationService::class);
$targetTypes = [];
foreach ($stationRef->getConstants() as $name => $val) {
    if (str_starts_with((string) $name, 'TARGET_')) {
        $targetTypes[$name] = (string) $val;
    }
}
$assertSame(count($targetTypes), 16, 'StationNotificationService TARGET_* 常量数量为 16');
$clientRoutedOnlyTargets = [
    'TARGET_REVIEW_LIST' => true,
    'TARGET_REVIEW_DETAIL' => true,
    'TARGET_STAFF_DYNAMIC' => true,
    'TARGET_STAFF_WORK' => true,
    'TARGET_STAFF_CERTIFICATE' => true,
    'TARGET_STAFF_DETAIL' => true,
];
$mappedCount = 0;
foreach ($targetTypes as $constName => $type) {
    $target = StationNotificationService::target($type);
    $page = (string) ($target['page'] ?? '');
    if (isset($clientRoutedOnlyTargets[$constName])) {
        $assertSame($page, '', "StationNotificationService::target({$constName}) 仅走前端 target_type 路由，page 保持空串");
        continue;
    }
    $mappedCount++;
    $assertValidNewuniappTarget($page, "StationNotificationService::target({$constName})");
    $withId = MiniappPagePathService::appendId($page, 123);
    $assertTrue(str_contains($withId, 'id=123'), "{$constName} appendId 应包含 id=123 ({$withId})");
    $assertTrue(substr_count($withId, '?') <= 1, "{$constName} appendId 不应出现双问号 ({$withId})");
}
$assertSame($mappedCount, 10, 'StationNotificationService 服务端映射 page 的目标数量为 10');
$pauseTarget = StationNotificationService::target(StationNotificationService::TARGET_PAUSE);
$assertSame(
    $pauseTarget['page'],
    'packages/pkg-service/pages/change/detail?type=pause',
    'TARGET_PAUSE target path'
);
$assertSame(
    MiniappPagePathService::appendId($pauseTarget['page'], 123),
    'packages/pkg-service/pages/change/detail?type=pause&id=123',
    'TARGET_PAUSE appendId(&id=)'
);

// 5. Reflection 校验 WechatNotificationService::buildPagePath()
$buildPagePath = new ReflectionMethod(WechatNotificationService::class, 'buildPagePath');
$buildPagePath->setAccessible(true);

$assertSame(
    $buildPagePath->invoke(null, '', 'order_update', 101, 'staff'),
    'packages/pkg-staff/pages/order-detail/order-detail?id=101',
    'buildPagePath 员工默认跳转'
);
$assertSame(
    $buildPagePath->invoke(null, '', OaNotificationTemplate::SCENE_TICKET_UPDATE, 102, 'user'),
    'packages/pkg-service/pages/aftersale/ticket-detail?id=102',
    'buildPagePath 工单默认跳转'
);
$assertSame(
    $buildPagePath->invoke(null, '', OaNotificationTemplate::SCENE_WAITLIST_RELEASE, 103, 'user'),
    'packages/pkg-order/pages/waitlist/waitlist?id=103',
    'buildPagePath 候补释放默认跳转'
);
$assertSame(
    $buildPagePath->invoke(null, '', OaNotificationTemplate::SCENE_WAITLIST_EXPIRED, 104, 'user'),
    'packages/pkg-order/pages/waitlist/waitlist?id=104',
    'buildPagePath 候补过期默认跳转'
);
$assertSame(
    $buildPagePath->invoke(null, '', OaNotificationTemplate::SCENE_ORDER_UPDATE, 105, 'user'),
    'packages/pkg-order/pages/detail/detail?id=105',
    'buildPagePath 订单默认跳转'
);
$assertSame(
    $buildPagePath->invoke(null, '/packages/pages/order_change/pause_detail', '', 201, 'user'),
    'packages/pkg-service/pages/change/detail?type=pause&id=201',
    'buildPagePath 历史 pause_detail 运行时兜底'
);
$assertSame(
    $buildPagePath->invoke(null, 'packages/pages/activity_registration/detail', '', 202, 'user'),
    'packages/pkg-user/pages/activity/activity?id=202',
    'buildPagePath 历史 activity_registration 运行时兜底'
);

// 6. 解析 like.sql 中的服务号 / 小程序订阅模板 INSERT 语句
$parseSqlTuple = static function (string $tuple): array {
    $fields = [];
    $len = strlen($tuple);
    $i = 0;
    while ($i < $len) {
        while ($i < $len && ctype_space($tuple[$i])) {
            $i++;
        }
        if ($i >= $len) {
            break;
        }
        if ($tuple[$i] === "'") {
            $i++;
            $buf = '';
            while ($i < $len) {
                $ch = $tuple[$i];
                if ($ch === '\\' && $i + 1 < $len) {
                    $buf .= $tuple[$i + 1];
                    $i += 2;
                    continue;
                }
                if ($ch === "'") {
                    if ($i + 1 < $len && $tuple[$i + 1] === "'") {
                        $buf .= "'";
                        $i += 2;
                        continue;
                    }
                    $i++;
                    break;
                }
                $buf .= $ch;
                $i++;
            }
            $fields[] = $buf;
            while ($i < $len && $tuple[$i] !== ',') {
                $i++;
            }
            if ($i < $len && $tuple[$i] === ',') {
                $i++;
            }
        } else {
            $start = $i;
            while ($i < $len && $tuple[$i] !== ',') {
                $i++;
            }
            $fields[] = trim(substr($tuple, $start, $i - $start));
            if ($i < $len && $tuple[$i] === ',') {
                $i++;
            }
        }
    }
    return $fields;
};

$sqlPath = $serverRoot . '/public/install/db/like.sql';
$sqlLines = file($sqlPath, FILE_IGNORE_NEW_LINES);
$assertTrue(is_array($sqlLines), '读取 server/public/install/db/like.sql 成功');

$oaCount = 0;
$mnpCount = 0;
foreach ($sqlLines as $lineNo => $line) {
    if (preg_match('/^INSERT INTO `(la_wechat_(?:oa|mnp)_notification_template)` \(([^)]+)\) VALUES \((.+)\);$/', $line, $m)) {
        $table = $m[1];
        $cols = array_map(static fn(string $c): string => trim($c, " `\t"), explode(',', $m[2]));
        $pageColIdx = array_search('page_path', $cols, true);
        $sceneColIdx = array_search('scene', $cols, true);
        $assertTrue($pageColIdx !== false, "{$table} line " . ($lineNo + 1) . ' 含 page_path 列');

        $vals = $parseSqlTuple($m[3]);
        $assertTrue(count($vals) === count($cols), "{$table} line " . ($lineNo + 1) . ' 列数与值数一致');

        $scene = (string) ($vals[$sceneColIdx !== false ? $sceneColIdx : 1] ?? '');
        $pagePath = (string) ($vals[$pageColIdx] ?? '');
        $assertValidNewuniappTarget($pagePath, "like.sql {$table}[{$scene}]");

        if ($table === 'la_wechat_oa_notification_template') {
            $oaCount++;
        } else {
            $mnpCount++;
        }
    }
}

$assertSame($oaCount, 15, 'like.sql la_wechat_oa_notification_template INSERT 数量为 15');
$assertSame($mnpCount, 4, 'like.sql la_wechat_mnp_notification_template INSERT 数量为 4');

// 7. 校验 like.sql 中 la_order 含 start_service_time 列
$sqlFull = (string) file_get_contents($sqlPath);
$assertTrue(
    (bool) preg_match('/CREATE TABLE `la_order`[\s\S]*?`start_service_time`/m', $sqlFull),
    'like.sql la_order DDL 包含 start_service_time 列'
);

// 8. 校验作品与证书审核通知方法存在且为 public static
$interactRef = new ReflectionClass(\app\common\service\InteractNotificationService::class);
foreach (['notifyStaffWorkAuditResult', 'notifyStaffCertificateAuditResult'] as $methodName) {
    $assertTrue($interactRef->hasMethod($methodName), "InteractNotificationService 存在方法 {$methodName}");
    $method = $interactRef->getMethod($methodName);
    $assertTrue($method->isPublic() && $method->isStatic(), "InteractNotificationService::{$methodName} 为 public static");
}

// 9. 校验 P1-2C 结算页路由、Repay 修复及工作台候补接口
$assertSame(
    MiniappPagePathService::LEGACY_TO_CURRENT['packages/pages/staff_settlement/staff_settlement'] ?? '',
    'packages/pkg-staff/pages/settlement-list/settlement-list',
    'LEGACY_TO_CURRENT[staff_settlement] 指向 settlement-list'
);
$settlementTarget = StationNotificationService::target(StationNotificationService::TARGET_STAFF_SETTLEMENT);
$assertSame(
    $settlementTarget['page'],
    'packages/pkg-staff/pages/settlement-list/settlement-list',
    'TARGET_STAFF_SETTLEMENT 指向 settlement-list'
);

$repayServiceSrc = (string) file_get_contents($serverRoot . '/app/common/service/StaffSettlementRepayService.php');
$assertTrue(
    !str_contains($repayServiceSrc, '$repay->expire_time'),
    'StaffSettlementRepayService::getPayOrderInfo 已移除未定义变量 $repay->expire_time'
);
$assertTrue(
    str_contains($repayServiceSrc, '/packages/pkg-staff/pages/settlement-list/settlement-list'),
    'StaffSettlementRepayService::pay 默认回跳指向 settlement-list'
);

$staffCenterCtrlRef = new ReflectionClass(\app\api\controller\StaffCenterController::class);
$staffCenterLogicRef = new ReflectionClass(\app\api\logic\StaffCenterLogic::class);
foreach (['waitlistLists', 'waitlistNotify'] as $action) {
    $assertTrue($staffCenterCtrlRef->hasMethod($action), "StaffCenterController 存在方法 {$action}");
    $assertTrue($staffCenterLogicRef->hasMethod($action), "StaffCenterLogic 存在方法 {$action}");
}

echo "[OK] notification_page_path_checks passed ({$assertions} assertions)\n";
