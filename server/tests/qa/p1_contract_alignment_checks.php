<?php
declare(strict_types=1);

/**
 * P1-3 跨端契约对齐与接口幂等 / Token 策略回归检查（零数据库依赖）。
 *
 * 执行：php server/tests/qa/p1_contract_alignment_checks.php
 */

$serverRoot = dirname(__DIR__, 2);
$repoRoot = dirname($serverRoot);
require $serverRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$app = new \think\App($serverRoot);
$app->initialize();

use app\api\http\middleware\IdempotencyMiddleware;
use app\common\enum\ErrorCodeEnum;
use app\common\exception\ScheduleOccupancyException;
use app\common\exception\UserBookingConflictException;
use app\common\http\middleware\LikeAdminAllowMiddleware;
use app\common\service\JsonService;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Request;
use think\Response;

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
// 1. ErrorCodeEnum 与 shared/contracts/error-codes.ts 一致性校验
// ----------------------------------------------------------------
$errorCodesTsPath = $repoRoot . '/shared/contracts/error-codes.ts';
$errorCodesTs = file_get_contents($errorCodesTsPath);
$assertTrue($errorCodesTs !== false, '读取 shared/contracts/error-codes.ts 成功');

$startPos = strpos((string)$errorCodesTs, 'defineErrorCodes({');
$endPos = strpos((string)$errorCodesTs, '} as const)');
$assertTrue($startPos !== false && $endPos !== false && $endPos > $startPos, '解析 defineErrorCodes 对象区间');
$errorCodesBody = substr((string)$errorCodesTs, $startPos, $endPos - $startPos);

preg_match_all('/^\s*([A-Z][A-Z0-9_]*)\s*:/m', $errorCodesBody, $tsMatches);
$tsKeys = $tsMatches[1] ?? [];
$phpKeys = ErrorCodeEnum::all();

$assertSame(count($tsKeys), 26, 'TS ERROR_CODES 共 26 个枚举键');
$assertSame(array_values(array_diff($tsKeys, $phpKeys)), [], 'TS ERROR_CODES 无遗漏同步到 ErrorCodeEnum');
$assertSame(array_values(array_diff($phpKeys, $tsKeys)), [], 'ErrorCodeEnum 无 TS 未定义的多余错误码');
$assertTrue(ErrorCodeEnum::isValid(ErrorCodeEnum::TOKEN_EXPIRED), 'ErrorCodeEnum::isValid 对有效码返回 true');
$assertTrue(!ErrorCodeEnum::isValid('NOT_A_CODE'), 'ErrorCodeEnum::isValid 对无效码返回 false');
$assertTrue(!ErrorCodeEnum::isValid(''), 'ErrorCodeEnum::isValid 对空串返回 false');

// ----------------------------------------------------------------
// 2. JsonService 结构化 error_code 输出契约
// ----------------------------------------------------------------
$successRes = JsonService::success('ok', ['id' => 1]);
$successData = $successRes->getData();
$assertSame((int)$successData['code'], 1, 'JsonService::success code 为 1');
$assertTrue(!array_key_exists('error_code', $successData), 'JsonService::success 不输出 error_code');

$dataRes = JsonService::data(['items' => []]);
$dataPayload = $dataRes->getData();
$assertSame((int)$dataPayload['code'], 1, 'JsonService::data code 为 1');
$assertTrue(!array_key_exists('error_code', $dataPayload), 'JsonService::data 不输出 error_code');

$defaultFailPayload = JsonService::fail('普通失败')->getData();
$assertSame((int)$defaultFailPayload['code'], 0, 'JsonService::fail code 为 0');
$assertSame($defaultFailPayload['msg'], '普通失败', 'JsonService::fail 保留中文 msg');
$assertSame($defaultFailPayload['error_code'], ErrorCodeEnum::UNKNOWN, 'JsonService::fail 默认 error_code 为 UNKNOWN');
$assertTrue(!empty($defaultFailPayload['request_id']), 'JsonService::fail 包含 request_id');

$expiredFailPayload = JsonService::fail('登录超时', [], -1, 0)->getData();
$assertSame((int)$expiredFailPayload['code'], -1, 'JsonService::fail(-1) 保留 code=-1');
$assertSame($expiredFailPayload['error_code'], ErrorCodeEnum::TOKEN_EXPIRED, 'code=-1 自动推导 TOKEN_EXPIRED');

$orderMissingPayload = JsonService::fail('订单不存在', [], 0, 1, ErrorCodeEnum::ORDER_NOT_FOUND)->getData();
$assertSame($orderMissingPayload['error_code'], ErrorCodeEnum::ORDER_NOT_FOUND, '显式传入 ORDER_NOT_FOUND 生效');

$forbiddenRes = JsonService::forbidden('账号高风险', [], ErrorCodeEnum::RISK_BLOCKED);
$assertSame($forbiddenRes->getCode(), 403, 'JsonService::forbidden 返回 HTTP 403');
$assertSame($forbiddenRes->getData()['error_code'], ErrorCodeEnum::RISK_BLOCKED, 'JsonService::forbidden 输出 RISK_BLOCKED');

$caughtThrow = null;
try {
    JsonService::throw('参数校验失败', [], 0, 1, ErrorCodeEnum::VALIDATE_FAILED);
} catch (HttpResponseException $e) {
    $caughtThrow = $e->getResponse()->getData();
}
$assertTrue(is_array($caughtThrow), 'JsonService::throw 抛出 HttpResponseException');
$assertSame($caughtThrow['error_code'], ErrorCodeEnum::VALIDATE_FAILED, 'JsonService::throw 保留 VALIDATE_FAILED');

// ----------------------------------------------------------------
// 3. ExceptionHandle resolveErrorCode 异常映射契约
// ----------------------------------------------------------------
$handler = new class($app) extends \app\ExceptionHandle {
    public function exposeResolveErrorCode(Throwable $e, int $statusCode): string
    {
        return $this->resolveErrorCode($e, $statusCode);
    }

    public function exposeRenderApiJson(Request $request, Throwable $e): Response
    {
        return $this->renderApiJson($request, $e);
    }
};

$assertSame(
    $handler->exposeResolveErrorCode(new ValidateException('字段错误'), 500),
    ErrorCodeEnum::VALIDATE_FAILED,
    'ValidateException 映射 VALIDATE_FAILED'
);
$assertSame(
    $handler->exposeResolveErrorCode(ScheduleOccupancyException::conflict('主持', '2026-12-20', 'ORD001', '改期'), 500),
    ErrorCodeEnum::SCHEDULE_LOCKED,
    'ScheduleOccupancyException 映射 SCHEDULE_LOCKED'
);
$assertSame(
    $handler->exposeResolveErrorCode(UserBookingConflictException::submitting(), 500),
    ErrorCodeEnum::SCHEDULE_LOCKED,
    'UserBookingConflictException 映射 SCHEDULE_LOCKED'
);
$assertSame(
    $handler->exposeResolveErrorCode(new HttpException(403, 'forbidden'), 403),
    ErrorCodeEnum::RISK_BLOCKED,
    'HttpException(403) 映射 RISK_BLOCKED'
);
$assertSame(
    $handler->exposeResolveErrorCode(new HttpException(401, 'unauthorized'), 401),
    ErrorCodeEnum::TOKEN_EXPIRED,
    'HttpException(401) 映射 TOKEN_EXPIRED'
);
$assertSame(
    $handler->exposeResolveErrorCode(new RuntimeException('boom'), 500),
    ErrorCodeEnum::UNKNOWN,
    '普通异常映射 UNKNOWN'
);

$apiReq = (new Request())->setMethod('GET')->setPathinfo('api/order/create');
$apiErrPayload = $handler->exposeRenderApiJson($apiReq, new ValidateException('缺少参数'))->getData();
$assertSame($apiErrPayload['error_code'], ErrorCodeEnum::VALIDATE_FAILED, 'renderApiJson 响应体包含 error_code');
$assertSame($apiErrPayload['msg'], '缺少参数', 'renderApiJson 保留异常文案');

// ----------------------------------------------------------------
// 4. IdempotencyMiddleware 幂等与并发锁行为（内存存储 + 临时锁目录）
// ----------------------------------------------------------------
$tempLockDir = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'wm_idemp_qa_' . uniqid('', true);
$removeDir = static function (string $dir) use (&$removeDir): void {
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            $removeDir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
};

$makeMiddleware = static function () use ($app, $tempLockDir): IdempotencyMiddleware {
    return new class($tempLockDir) extends IdempotencyMiddleware {
        public array $memoryStore = [];
        private string $dir;

        public function __construct(string $dir)
        {
            $this->dir = $dir;
        }

        protected function readRecord(string $cacheKey): ?array
        {
            return $this->memoryStore[$cacheKey] ?? null;
        }

        protected function writeRecord(string $cacheKey, array $record, int $ttl): void
        {
            $this->memoryStore[$cacheKey] = $record;
        }

        protected function deleteRecord(string $cacheKey): void
        {
            unset($this->memoryStore[$cacheKey]);
        }

        protected function getLockDir(): string
        {
            return $this->dir;
        }

        protected function getTtl(): int
        {
            return 600;
        }

        protected function getLockTtl(): int
        {
            return 30;
        }
    };
};

$makeRequest = static function (string $method, string $path, array $headers = [], array $post = [], int $userId = 1001, string $rawInput = ''): Request {
    $req = (new Request())
        ->setMethod($method)
        ->setPathinfo($path)
        ->withHeader($headers)
        ->withPost($post);
    if ($rawInput !== '') {
        $req->withInput($rawInput);
    }
    $req->userId = $userId;
    return $req;
};

try {
    $mw = $makeMiddleware();

    // Case 1: GET 请求直接透传（不读写幂等缓存）
    $calls = 0;
    $res1 = $mw->handle($makeRequest('GET', 'api/order/lists', ['Idempotency-Key' => 'key-12345678']), static function () use (&$calls) {
        $calls++;
        return JsonService::success('get-ok');
    });
    $assertSame($calls, 1, 'Case1: GET 请求直接放行执行');
    $assertSame($res1->getHeader('X-Idempotency-Replayed'), null, 'Case1: GET 请求不加 X-Idempotency-Replayed 头');
    $assertSame($mw->memoryStore, [], 'Case1: GET 请求不写幂等缓存');

    // Case 2: POST 无 Idempotency-Key 直接透传（兼容旧端）
    $calls = 0;
    $res2 = $mw->handle($makeRequest('POST', 'api/order/create', [], ['staff_id' => 1]), static function () use (&$calls) {
        $calls++;
        return JsonService::success('no-key-ok');
    });
    $assertSame($calls, 1, 'Case2: 无 Idempotency-Key 的 POST 请求正常放行');
    $assertSame($res2->getHeader('X-Idempotency-Replayed'), null, 'Case2: 无 Idempotency-Key 不写重放头');

    // Case 3: 非法 Idempotency-Key 直接拦截 VALIDATE_FAILED
    $calls = 0;
    $res3 = $mw->handle($makeRequest('POST', 'api/order/create', ['Idempotency-Key' => 'bad key!'], ['staff_id' => 1]), static function () use (&$calls) {
        $calls++;
        return JsonService::success('should-not-run');
    });
    $assertSame($calls, 0, 'Case3: 非法 Idempotency-Key 不执行业务 handler');
    $assertSame($res3->getData()['error_code'], ErrorCodeEnum::VALIDATE_FAILED, 'Case3: 非法 Idempotency-Key 返回 VALIDATE_FAILED');

    // Case 4 & Case 5: 首次写请求执行并缓存 + 相同重放不重复执行且返回 X-Idempotency-Replayed: 1
    $calls = 0;
    $reqPostA = $makeRequest('POST', 'api/order/create', ['Idempotency-Key' => 'm3k2ab-x8f92k1p'], ['staff_id' => 7, 'date' => '2026-12-20'], 1001);
    $res4 = $mw->handle($reqPostA, static function () use (&$calls) {
        $calls++;
        return JsonService::success('订单创建成功', ['order_id' => 88]);
    });
    $assertSame($calls, 1, 'Case4: 首次幂等请求执行一次 handler');
    $assertSame($res4->getHeader('X-Idempotency-Replayed'), '0', 'Case4: 首次响应 X-Idempotency-Replayed=0');

    $reqPostReplay = $makeRequest('POST', 'api/order/create', ['Idempotency-Key' => 'm3k2ab-x8f92k1p'], ['date' => '2026-12-20', 'staff_id' => 7], 1001);
    $res5 = $mw->handle($reqPostReplay, static function () use (&$calls) {
        $calls++;
        return JsonService::success('不应再次执行', ['order_id' => 99]);
    });
    $assertSame($calls, 1, 'Case5: 相同幂等重放不再次调用业务 handler');
    $assertSame($res5->getHeader('X-Idempotency-Replayed'), '1', 'Case5: 重放响应 X-Idempotency-Replayed=1');
    $assertSame((int)$res5->getData()['data']['order_id'], 88, 'Case5: 重放原封不动返回首次 order_id=88');

    // Case 6: 同一 Key 携带不同载荷拒绝（IDEMPOTENT_REPLAY）
    $reqMismatch = $makeRequest('POST', 'api/order/create', ['Idempotency-Key' => 'm3k2ab-x8f92k1p'], ['staff_id' => 9, 'date' => '2026-12-20'], 1001);
    $res6 = $mw->handle($reqMismatch, static function () use (&$calls) {
        $calls++;
        return JsonService::success('不应执行');
    });
    $assertSame($calls, 1, 'Case6: 载荷不一致不执行 handler');
    $assertSame($res6->getData()['error_code'], ErrorCodeEnum::IDEMPOTENT_REPLAY, 'Case6: 同 key 异 payload 返回 IDEMPOTENT_REPLAY');

    // Case 7: 不同 userId 使用相同 key 互不影响
    $reqOtherUser = $makeRequest('POST', 'api/order/create', ['Idempotency-Key' => 'm3k2ab-x8f92k1p'], ['staff_id' => 7, 'date' => '2026-12-20'], 2002);
    $res7 = $mw->handle($reqOtherUser, static function () use (&$calls) {
        $calls++;
        return JsonService::success('另一用户创建成功', ['order_id' => 99]);
    });
    $assertSame($calls, 2, 'Case7: 跨 userId 作用域隔离正常放行');
    $assertSame((int)$res7->getData()['data']['order_id'], 99, 'Case7: 另一用户得到独立响应');

    // Case 8: 业务返回 code=0 失败时释放占位锁，重试可重新执行
    $callsRetryFail = 0;
    $res8a = $mw->handle($makeRequest('POST', 'api/order/cancel', ['Idempotency-Key' => 'retry-fail-0001'], ['id' => 5], 1001), static function () use (&$callsRetryFail) {
        $callsRetryFail++;
        return JsonService::fail('暂不可取消', [], 0, 1, ErrorCodeEnum::ORDER_STATUS_INVALID);
    });
    $assertSame($res8a->getData()['error_code'], ErrorCodeEnum::ORDER_STATUS_INVALID, 'Case8a: 业务失败透传原 error_code');
    $res8b = $mw->handle($makeRequest('POST', 'api/order/cancel', ['Idempotency-Key' => 'retry-fail-0001'], ['id' => 5], 1001), static function () use (&$callsRetryFail) {
        $callsRetryFail++;
        return JsonService::success('取消成功');
    });
    $assertSame($callsRetryFail, 2, 'Case8b: 业务失败释放占位锁后允许重试执行');
    $assertSame((int)$res8b->getData()['code'], 1, 'Case8b: 重试成功返回 code=1');

    // Case 9: 业务抛异常时释放占位锁，重试可重新执行
    $callsRetryEx = 0;
    $threw = false;
    try {
        $mw->handle($makeRequest('POST', 'api/order/confirm', ['Idempotency-Key' => 'retry-excp-0001'], ['id' => 6], 1001), static function () use (&$callsRetryEx) {
            $callsRetryEx++;
            throw new RuntimeException('模拟瞬时异常');
        });
    } catch (RuntimeException $e) {
        $threw = true;
    }
    $assertTrue($threw, 'Case9a: 中间件原样上抛业务异常');
    $res9b = $mw->handle($makeRequest('POST', 'api/order/confirm', ['Idempotency-Key' => 'retry-excp-0001'], ['id' => 6], 1001), static function () use (&$callsRetryEx) {
        $callsRetryEx++;
        return JsonService::success('确认成功');
    });
    $assertSame($callsRetryEx, 2, 'Case9b: 异常释放占位锁后允许重试执行');
    $assertSame((int)$res9b->getData()['code'], 1, 'Case9b: 异常后重试成功');

    // Case 10: 并发 processing 状态占位命中返回 IDEMPOTENT_REPLAY
    $scopeHash10 = hash('sha256', implode('|', ['u:1001', 'POST', 'api/pay/prepay', 'concurrent-key-01']));
    $mw->memoryStore['api_idempotency:' . $scopeHash10] = [
        'status' => 'processing',
        'payload_hash' => 'any',
        'created_at' => time(),
    ];
    $callsConcurrent = 0;
    $res10 = $mw->handle($makeRequest('POST', 'api/pay/prepay', ['Idempotency-Key' => 'concurrent-key-01'], ['order_id' => 10], 1001), static function () use (&$callsConcurrent) {
        $callsConcurrent++;
        return JsonService::success('不应进入并发重入');
    });
    $assertSame($callsConcurrent, 0, 'Case10: processing 占位阻止并发双写');
    $assertSame($res10->getData()['error_code'], ErrorCodeEnum::IDEMPOTENT_REPLAY, 'Case10: 并发占位返回 IDEMPOTENT_REPLAY');
} finally {
    $removeDir($tempLockDir);
}

// ----------------------------------------------------------------
// 5. CORS 头放行、路由中间件顺序、Token 策略与空指针防御校验
// ----------------------------------------------------------------
$allowMw = new LikeAdminAllowMiddleware();
$optionsReq = (new Request())->setMethod('OPTIONS')->setPathinfo('api/order/create');
$optionsRes = $allowMw->handle($optionsReq, static fn() => response());
$allowHeaders = (string)$optionsRes->getHeader('Access-Control-Allow-Headers');
$exposeHeaders = (string)$optionsRes->getHeader('Access-Control-Expose-Headers');
$assertTrue(str_contains($allowHeaders, 'Idempotency-Key'), 'LikeAdminAllowMiddleware 放行 Idempotency-Key');
$assertTrue(str_contains($exposeHeaders, 'X-Idempotency-Replayed'), 'LikeAdminAllowMiddleware 暴露 X-Idempotency-Replayed');

$apiRouteCfg = require $serverRoot . '/app/api/config/route.php';
$middlewareList = $apiRouteCfg['middleware'] ?? [];
$riskIdx = array_search(\app\api\http\middleware\UserRiskControlMiddleware::class, $middlewareList, true);
$idempIdx = array_search(IdempotencyMiddleware::class, $middlewareList, true);
$assertTrue($riskIdx !== false && $idempIdx !== false && $idempIdx > $riskIdx, 'IdempotencyMiddleware 注册在 UserRiskControlMiddleware 之后');

$projectCfg = require $serverRoot . '/config/project.php';
$assertSame((int)$projectCfg['user_token']['expire_duration'], 3600 * 24 * 7, 'user_token 默认过期时长为 7 天');
$assertSame((int)$projectCfg['user_token']['be_expire_duration'], 3600 * 24 * 2, 'user_token 默认临期自动续期阈值为 2 天');
$assertSame((int)$projectCfg['admin_token']['expire_duration'], 3600 * 8, 'admin_token 保持 8 小时');
$assertSame((int)$projectCfg['idempotency']['ttl'], 600, 'idempotency ttl 默认 600 秒');

$tokenServiceSrc = (string)file_get_contents($serverRoot . '/app/api/service/UserTokenService.php');
$tokenCacheSrc = (string)file_get_contents($serverRoot . '/app/common/cache/UserTokenCache.php');
$assertTrue(!str_contains($tokenServiceSrc, '$userSession->isEmpty()'), 'UserTokenService 已移除 null 风险的 ->isEmpty()');
$assertTrue(str_contains($tokenCacheSrc, 'if (empty($user))'), 'UserTokenCache 已补齐空用户守卫');

echo "P1-3 契约与幂等/Token 回归检查全部通过：共 {$assertions} 条断言。\n";
