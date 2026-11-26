<?php
declare(strict_types=1);

/**
 * 档期确认函审计修复单元测试。
 *
 * 覆盖：双轨配置归一化、暂停订单资格校验、错误码文案、模板变量清洗、字体渲染缓存。
 * 直接执行：
 *   php server/tests/unit/StaffScheduleConfirmLetterAuditTest.php
 */

$serverRoot = dirname(__DIR__, 2);
require $serverRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$app = new \think\App($serverRoot);
$app->initialize();

use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\service\OrderConfirmLetterFontService;
use app\common\service\StaffScheduleConfirmLetterService;

$assertions = 0;

$assertTrue = static function (bool $condition, string $label) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException('[FAIL] ' . $label);
    }
};

$assertSame = static function ($actual, $expected, string $label) use (&$assertions): void {
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

try {
    // ----------------------------------------------------------------
    // 1. normalizeConfigPayload：design_config 图层文案回写顶层，并保留 existing 开关/主题
    // ----------------------------------------------------------------
    $normalize = new ReflectionMethod(StaffScheduleConfirmLetterService::class, 'normalizeConfigPayload');
    $normalize->setAccessible(true);

    $textLayer = static function (string $id, string $field, string $text, int $z): array {
        return [
            'id' => $id,
            'type' => 'text',
            'x' => 160,
            'y' => 300,
            'w' => 720,
            'h' => 120,
            'z' => $z,
            'visible' => 1,
            'locked' => 0,
            'opacity' => 1,
            'rotate' => 0,
            'text' => $text,
            'fontSize' => 42,
            'fontWeight' => '400',
            'lineHeight' => 1.35,
            'align' => 'center',
            'color' => '#FFF7E6',
            'editable' => 1,
            'field' => $field,
        ];
    };

    $designConfig = [
        'canvas' => ['width' => 1080, 'height' => 1920],
        'background' => ['type' => 'color', 'color' => '#2B2118', 'image' => '', 'fit' => 'cover', 'opacity' => 1],
        'layers' => [
            $textLayer('title', 'title', '新标题', 2),
            $textLayer('subtitle', 'subtitle', 'NEW SUBTITLE', 3),
            $textLayer('content', 'content_template', '{service_date_label}，{customer_alias}档期已确认', 5),
            $textLayer('footer', 'footer_note', '新页脚文案', 10),
        ],
    ];
    $existing = [
        'title' => '旧标题',
        'subtitle' => '旧副标题',
        'content_template' => '旧正文',
        'footer_note' => '旧页脚',
        'background_type' => 'color',
        'background_image' => '',
        'background_color' => '#191713',
        'text_theme' => 'dark',
        'show_customer_alias' => 0,
        'show_service_name' => 1,
        'show_city' => 0,
    ];

    $result = $normalize->invoke(null, 9527, ['design_config' => $designConfig], $existing);
    $assertSame(true, is_array($result), 'normalizeConfigPayload 返回数组');
    $assertSame('新标题', $result['title'], 'title 从 design_config 图层同步');
    $assertSame('NEW SUBTITLE', $result['subtitle'], 'subtitle 从 design_config 图层同步');
    $assertSame('{service_date_label}，{customer_alias}档期已确认', $result['content_template'], 'content_template 从图层同步');
    $assertSame('新页脚文案', $result['footer_note'], 'footer_note 从图层同步');
    $assertSame(0, $result['show_customer_alias'], '未传 show_customer_alias 时保留 existing=0');
    $assertSame(0, $result['show_city'], '未传 show_city 时保留 existing=0');
    $assertSame(1, $result['show_service_name'], '未传 show_service_name 时保留 existing=1');
    $assertSame('dark', $result['text_theme'], '未传 text_theme 时保留 existing=dark');
    $assertSame('#2B2118', $result['background_color'], '背景色优先取 design_config');

    $partialDesign = $designConfig;
    $partialDesign['layers'] = [$textLayer('title', 'title', '只改标题', 2)];
    $partialResult = $normalize->invoke(null, 9527, ['design_config' => $partialDesign], $existing);
    $assertSame('只改标题', $partialResult['title'], '保留图层同步标题');
    $assertSame('旧副标题', $partialResult['subtitle'], '图层缺失时回落 existing 副标题');
    $assertSame('旧页脚', $partialResult['footer_note'], '图层缺失时回落 existing 页脚');

    // ----------------------------------------------------------------
    // 2. checkGenerateQualification：暂停订单即使服务项已确认也必须拒绝
    // ----------------------------------------------------------------
    $qualify = new ReflectionMethod(StaffScheduleConfirmLetterService::class, 'checkGenerateQualification');
    $qualify->setAccessible(true);

    $pausedOrder = new Order();
    $pausedOrder->id = 10086;
    $pausedOrder->order_status = Order::STATUS_PAUSED;
    $confirmedItem = new OrderItem();
    $confirmedItem->confirm_status = 1;

    $pausedRejected = false;
    try {
        $qualify->invoke(null, $pausedOrder, $confirmedItem);
    } catch (Throwable $e) {
        $pausedRejected = str_contains($e->getMessage(), StaffScheduleConfirmLetterService::ERROR_ORDER_STATUS);
    }
    $assertSame(true, $pausedRejected, 'STATUS_PAUSED 订单抛出 ERROR_ORDER_STATUS');

    // ----------------------------------------------------------------
    // 3. normalizeErrorMessage：核心错误码均有可读中文
    // ----------------------------------------------------------------
    $errorCodes = [
        StaffScheduleConfirmLetterService::ERROR_STALE,
        StaffScheduleConfirmLetterService::ERROR_NOT_BOUND,
        StaffScheduleConfirmLetterService::ERROR_ORDER_STATUS,
        StaffScheduleConfirmLetterService::ERROR_NOT_LOCKED,
        StaffScheduleConfirmLetterService::ERROR_SERVICE_DATE,
        StaffScheduleConfirmLetterService::ERROR_CONFIG_NOT_FOUND,
        StaffScheduleConfirmLetterService::ERROR_CONFIG_DISABLED,
        StaffScheduleConfirmLetterService::ERROR_CONFIG_LAST_ACTIVE,
        StaffScheduleConfirmLetterService::ERROR_ASSET_RUNTIME,
        StaffScheduleConfirmLetterService::ERROR_ASSET_RENDER,
        StaffScheduleConfirmLetterService::ERROR_ASSET_DIRECTORY,
        StaffScheduleConfirmLetterService::ERROR_ASSET_UPLOAD,
        StaffScheduleConfirmLetterService::ERROR_ASSET_FONT_FILE_MISSING,
        StaffScheduleConfirmLetterService::ERROR_ASSET_FONT_FILE_UNREADABLE,
        StaffScheduleConfirmLetterService::ERROR_ASSET_FONT_RENDER,
        StaffScheduleConfirmLetterService::ERROR_QRCODE_MISSING,
    ];
    foreach ($errorCodes as $code) {
        $message = StaffScheduleConfirmLetterService::normalizeErrorMessage($code);
        $assertTrue($message !== '' && $message !== $code, '错误码有可读文案：' . $code);
    }
    $qrcodeMessage = StaffScheduleConfirmLetterService::normalizeErrorMessage(
        StaffScheduleConfirmLetterService::ERROR_QRCODE_MISSING
    );
    $assertTrue(str_contains($qrcodeMessage, '品牌与站点'), '二维码错误指向「品牌与站点」');
    $assertTrue(str_contains($qrcodeMessage, '海报资源'), '二维码错误指向「海报资源」');

    // ----------------------------------------------------------------
    // 4. sanitizeTemplateText：仅保留白名单变量
    // ----------------------------------------------------------------
    $sanitize = new ReflectionMethod(StaffScheduleConfirmLetterService::class, 'sanitizeTemplateText');
    $sanitize->setAccessible(true);

    $allowedVariables = ['service_date_label', 'customer_alias', 'service_name', 'city_label', 'staff_name'];
    $rawText = '';
    foreach ($allowedVariables as $variable) {
        $rawText .= '{' . $variable . '}';
    }
    $rawText .= '{confirm_date}{city}';
    $sanitized = (string)$sanitize->invoke(null, $rawText);
    foreach ($allowedVariables as $variable) {
        $assertTrue(str_contains($sanitized, '{' . $variable . '}'), '合法变量保留：' . $variable);
    }
    $assertTrue(!str_contains($sanitized, '{confirm_date}'), '非法变量 {confirm_date} 被清除');
    $assertTrue(!str_contains($sanitized, '{city}'), '非法变量 {city} 被清除');

    // ----------------------------------------------------------------
    // 5. assertActiveFontsRenderable：二次调用命中缓存且 clearRenderCache 可安全执行
    // ----------------------------------------------------------------
    $fontDirectory = $serverRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'common'
        . DIRECTORY_SEPARATOR . 'resource' . DIRECTORY_SEPARATOR . 'fonts';
    $sansPath = $fontDirectory . DIRECTORY_SEPARATOR . OrderConfirmLetterFontService::DEFAULT_SANS_FILE;
    $serifPath = $fontDirectory . DIRECTORY_SEPARATOR . OrderConfirmLetterFontService::DEFAULT_SERIF_FILE;
    $fontOptions = [
        'sans_file' => OrderConfirmLetterFontService::DEFAULT_SANS_FILE,
        'serif_file' => OrderConfirmLetterFontService::DEFAULT_SERIF_FILE,
        'sans_path' => $sansPath,
        'serif_path' => $serifPath,
    ];

    if (!extension_loaded('imagick') || !class_exists(\Imagick::class)) {
        echo 'SKIP: Imagick 未启用，跳过字体渲染缓存断言' . PHP_EOL;
    } elseif (!is_file($sansPath) || !is_file($serifPath)) {
        echo 'SKIP: 内置字体文件缺失，跳过字体渲染缓存断言' . PHP_EOL;
    } else {
        $renderCache = new ReflectionProperty(OrderConfirmLetterFontService::class, 'renderCheckCache');
        $renderCache->setAccessible(true);
        $fontHash = (string)(OrderConfirmLetterFontService::getActiveFontSignature($fontOptions)['hash'] ?? '');
        $assertTrue($fontHash !== '', '字体签名 hash 非空');

        OrderConfirmLetterFontService::assertActiveFontsRenderable($fontOptions);
        $cacheAfterFirst = $renderCache->getValue();
        $assertSame(true, !empty($cacheAfterFirst[$fontHash]), '首次检测后写入静态缓存');

        OrderConfirmLetterFontService::assertActiveFontsRenderable($fontOptions);
        $assertSame(true, true, '二次检测命中缓存不报错');

        OrderConfirmLetterFontService::clearRenderCache($fontOptions);
        $assertSame([], $renderCache->getValue(), 'clearRenderCache 清空进程内缓存');
    }
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

echo 'StaffScheduleConfirmLetterAuditTest: ' . $assertions . ' assertions passed' . PHP_EOL;
exit(0);
