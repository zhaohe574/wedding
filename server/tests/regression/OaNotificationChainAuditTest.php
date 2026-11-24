<?php
// +----------------------------------------------------------------------
// | 服务号通知链路审计回归测试（Batch 1b）
// +----------------------------------------------------------------------
// | 说明：与 FinancialChainAuditRegressionTest 同风格，Reflection + 断言 + 源码扫描。
// | 不依赖外部微信接口，不做业务库写操作；buildPayload 仅以内存模型构建。
// | 可直接执行：php tests/regression/OaNotificationChainAuditTest.php
// +----------------------------------------------------------------------

declare(strict_types=1);

use app\common\service\OrderNotificationService;
use app\common\service\WechatNotificationService;
use app\common\model\wechat\OaNotificationTemplate;
use app\common\model\wechat\OaFollower;

require __DIR__ . '/../../vendor/autoload.php';

$app = new \think\App(dirname(__DIR__, 2));
$app->initialize();

final class OaNotificationChainAuditTest
{
    private int $assertions = 0;

    public function run(): void
    {
        $this->testBuildPayloadSafeForPauseBusiness();
        $this->testFormatValueConst3CategoryAndStatus();
        $this->testResolveFallbackValueScheduleDate();
        $this->testWechatControllerPostGuards();
        $this->testAdminPermissionDependencies();
        $this->testEncryDemoDataNeedCheck();
        $this->testResolveOrderNotificationDataKeys();
        $this->testWeChatOaServiceNoBareSsl();
        $this->testSendServiceReminderScene();

        echo 'OK - ' . $this->assertions . " assertions\n";
    }

    // 1) buildPayload 对 pause + businessId=999999 用最小 $data 能安全构建（不串单、不崩）
    private function testBuildPayloadSafeForPauseBusiness(): void
    {
        $ref = new ReflectionClass(WechatNotificationService::class);
        $buildPayload = $ref->getMethod('buildPayload');
        $buildPayload->setAccessible(true);

        $template = new OaNotificationTemplate([
            'template_id' => 'TPL-PAUSE-TEST',
            'audience' => OaNotificationTemplate::AUDIENCE_USER,
            'page_path' => 'packages/pkg-order/pages/detail/detail',
            'data_mapping' => ['thing1' => 'title', 'time2' => 'service_date'],
        ]);
        $follower = new OaFollower(['openid' => 'o-test-openid']);

        $payload = $buildPayload->invoke(
            null,
            $template,
            $follower,
            'order_update',
            ['title' => '暂停即将到期提醒', 'status_text' => '暂停即将到期'],
            999999,
            '',
            'pause'
        );

        $this->assertTrue(is_array($payload) && isset($payload['touser'], $payload['template_id'], $payload['data']),
            'pause 业务必须能安全构建 payload');
        $this->assertSame('o-test-openid', $payload['touser'], 'touser 必须来自传入 follower，不得串单');
        $this->assertSame('TPL-PAUSE-TEST', $payload['template_id'], 'template_id 必须来自传入模板');
        $this->assertSame('暂停即将到期提醒', (string)($payload['data']['thing1']['value'] ?? ''),
            'thing1 必须取传入 title，不得串入其他订单数据');
        $this->assertTrue((bool)preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', (string)($payload['data']['time2']['value'] ?? '')),
            '缺失 service_date 时必须走类型安全兜底，不得崩溃或输出空值');
    }

    // 2) formatValue const3：类目归一 + 审核状态枚举
    private function testFormatValueConst3CategoryAndStatus(): void
    {
        $formatValue = (new ReflectionClass(WechatNotificationService::class))->getMethod('formatValue');
        $formatValue->setAccessible(true);

        $this->assertSame('摄影', $formatValue->invoke(null, 'const3', '司仪', 'category_name'),
            '类目字段必须按服务类目归类，绝不能落到审核状态枚举');
        $this->assertSame('审核不通过', $formatValue->invoke(null, 'const3', '审核不通过', 'status_text'),
            '状态字段必须保留审核不通过枚举');
    }

    // 3) resolveFallbackValue：service_date 兜底别名 schedule_date
    private function testResolveFallbackValueScheduleDate(): void
    {
        $resolve = (new ReflectionClass(WechatNotificationService::class))->getMethod('resolveFallbackValue');
        $resolve->setAccessible(true);

        $this->assertSame('2026-12-01', $resolve->invoke(null, 'service_date', 'time3',
            ['schedule_date' => '2026-12-01'], 'waitlist_release'),
            'waitlist_release 场景 service_date 必须能从 schedule_date 兜底');
    }

    // 4) WechatController 四个写操作方法必须带 isPost() 校验
    private function testWechatControllerPostGuards(): void
    {
        $source = $this->readSource('app/api/controller/WechatController.php');
        foreach (['oaSubscribeEntry', 'oaGuideClaim', 'oaSubscribeConfirm', 'oaSubscribeUnbind'] as $method) {
            $body = $this->extractFunctionBody($source, $method);
            $this->assertContains('$this->request->isPost()', $body, $method . ' 必须包含 POST 请求校验');
        }
    }

    // 5) AdminPermissionService::dependencies 必须包含 config 权限
    private function testAdminPermissionDependencies(): void
    {
        $deps = \app\common\service\AdminPermissionService::dependencies();
        $this->assertInArray('notification.oaNotification/config',
            $deps['notification.oaNotification/templateList'] ?? [],
            'templateList 必须继承 notification.oaNotification/config 权限');
        $this->assertInArray('notification.oaNotification/templateDetail',
            $deps['notification.oaNotification/templateList'] ?? [],
            'templateList 必须继承 templateDetail 权限');
    }

    // 6) EncryDemoDataMiddleware 默认属性必须包含体验站公众号/小程序配置接口
    private function testEncryDemoDataNeedCheck(): void
    {
        $props = (new ReflectionClass(\app\adminapi\http\middleware\EncryDemoDataMiddleware::class))
            ->getDefaultProperties();
        $needCheck = $props['needCheck'] ?? [];
        $this->assertInArray('experience.channel.official_account_setting/getConfig', $needCheck,
            'needCheck 必须包含体验站公众号配置接口');
        $this->assertInArray('experience.channel.mnp_settings/getConfig', $needCheck,
            'needCheck 必须包含体验站小程序配置接口');
    }

    // 7) resolveOrderNotificationData 必须返回 order_time 与 user_name
    private function testResolveOrderNotificationDataKeys(): void
    {
        $resolved = OrderNotificationService::resolveOrderNotificationData([
            'id' => 9001,
            'order_sn' => '202611240001',
            'package_name' => '法式唯美婚礼套系',
            'contact_name' => '新人李先生',
            'total_amount' => 6888.00,
            'pay_amount' => 6888.00,
            'service_date' => '2026-12-20',
        ]);

        $this->assertInArray('order_time', array_keys($resolved), '返回必须包含 order_time');
        $this->assertInArray('user_name', array_keys($resolved), '返回必须包含 user_name');
        $this->assertInArray('category_name', array_keys($resolved), '返回必须包含 category_name');
        $this->assertTrue(trim((string)$resolved['order_time']) !== '', 'order_time 不得为空');
        $this->assertSame('新人李先生', $resolved['user_name'], 'user_name 优先取 contact_name');
        $this->assertTrue(trim((string)$resolved['category_name']) !== '', 'category_name 不得为空');
    }

    // 8) WeChatOaService 不得再包含关闭 SSL 校验的裸 curl
    private function testWeChatOaServiceNoBareSsl(): void
    {
        $source = $this->readSource('app/common/service/wechat/WeChatOaService.php');
        $this->assertNotContains('CURLOPT_SSL_VERIFYPEER', $source,
            'getAllPrivateTemplates 必须移除裸 curl 的 SSL 校验关闭写法');
        $this->assertNotContains('curl_init', $source, 'WeChatOaService 必须统一走 EasyWeChat 客户端');
        $this->assertContains('toArray(false)', $source, 'getAllPrivateTemplates 必须使用 EasyWeChat 客户端');
    }

    // 9) sendServiceReminder 必须携带 schedule_remind 场景
    private function testSendServiceReminderScene(): void
    {
        $source = $this->readSource('app/common/service/OrderNotificationService.php');
        $body = $this->extractFunctionBody($source, 'sendServiceReminder');
        $this->assertContains("'scene' => 'schedule_remind'", $body,
            'sendServiceReminder 用户侧站内通知必须携带 schedule_remind 场景');
    }

    private function readSource(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('无法读取文件：' . $path);
        }
        return $contents;
    }

    private function assertContains(string $needle, string $haystack, string $message): void
    {
        $this->assertions++;
        if (!str_contains($haystack, $needle)) {
            throw new RuntimeException($message . '，缺少片段：' . $needle);
        }
    }

    private function assertNotContains(string $needle, string $haystack, string $message): void
    {
        $this->assertions++;
        if (str_contains($haystack, $needle)) {
            throw new RuntimeException($message . '，不应包含片段：' . $needle);
        }
    }

    private function assertInArray(string|int $needle, array $haystack, string $message): void
    {
        $this->assertions++;
        if (!in_array($needle, $haystack, true)) {
            throw new RuntimeException($message . '，缺少元素：' . $needle);
        }
    }

    private function extractFunctionBody(string $source, string $functionName): string
    {
        $position = strpos($source, 'function ' . $functionName . '(');
        if ($position === false) {
            throw new RuntimeException('无法定位函数：' . $functionName);
        }

        $braceStart = strpos($source, '{', $position);
        if ($braceStart === false) {
            throw new RuntimeException('无法定位函数体：' . $functionName);
        }

        $depth = 0;
        $length = strlen($source);
        for ($i = $braceStart; $i < $length; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($source, $braceStart, $i - $braceStart + 1);
                }
            }
        }

        throw new RuntimeException('函数体未闭合：' . $functionName);
    }

    private function assertSame($expected, $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException($message . '，期望：' . var_export($expected, true) . '，实际：' . var_export($actual, true));
        }
    }

    private function assertTrue(bool $condition, string $message): void
    {
        $this->assertions++;
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }
}

try {
    (new OaNotificationChainAuditTest())->run();
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL - ' . $e->getMessage() . "\n");
    exit(1);
}
