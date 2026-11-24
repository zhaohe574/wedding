<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - API 写接口幂等中间件
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\http\middleware;

use app\common\enum\ErrorCodeEnum;
use app\common\service\JsonService;
use app\common\service\RequestContextService;
use Closure;
use think\facade\Cache;
use think\facade\Config;
use think\Response;

/**
 * 消费客户端 `Idempotency-Key` 请求头，保护写接口免受弱网重试/并发连点双写影响。
 *
 * 约定：
 * 1. 仅作用于写方法（POST / PUT / PATCH / DELETE）且携带非空 `Idempotency-Key` 请求头的请求；
 * 2. 无该头或 GET 等读方法直接透传，保证兼容旧端与 PC；
 * 3. 按 `actor(userId/ip) + method + route + Idempotency-Key` 隔离作用域，防止跨用户/跨路由串数据；
 * 4. 使用独立文件互斥锁包裹「检查 + processing 占位」临界区，在进入业务逻辑前立即释放文件锁；
 * 5. 仅当 HTTP 200 且业务 code === 1 || code === 2 时缓存结果；异常或业务失败释放占位锁以便重试。
 */
class IdempotencyMiddleware
{
    private const WRITE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle($request, Closure $next)
    {
        $method = strtoupper((string)$request->method());
        if (!in_array($method, self::WRITE_METHODS, true)) {
            return $next($request);
        }

        $rawKey = trim((string)$request->header('idempotency-key', ''));
        if ($rawKey === '') {
            return $next($request);
        }

        if (preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $rawKey) !== 1) {
            return JsonService::fail('幂等键格式错误', [], 0, 1, ErrorCodeEnum::VALIDATE_FAILED);
        }

        $userId = (int)($request->userId ?? 0);
        $actor = $userId > 0 ? 'u:' . $userId : 'ip:' . (string)$request->ip();
        $route = strtolower(trim((string)$request->pathinfo(), '/'));
        if ($route === '') {
            $route = strtolower((string)$request->url(false));
        }

        $scopeHash = hash('sha256', implode('|', [$actor, $method, $route, $rawKey]));
        $cacheKey = 'api_idempotency:' . $scopeHash;
        $payloadHash = $this->computePayloadHash($request);

        $precheckResponse = $this->acquireOrReplay($request, $scopeHash, $cacheKey, $payloadHash);
        if ($precheckResponse !== null) {
            return $precheckResponse;
        }

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $this->deleteRecord($cacheKey);
            throw $e;
        }

        if ($response instanceof Response) {
            $httpStatus = $response->getCode();
            $payload = $this->extractJsonPayload($response);
            if ($httpStatus === 200 && is_array($payload) && in_array((int)($payload['code'] ?? -999), [1, 2], true)) {
                $this->writeRecord($cacheKey, [
                    'status' => 'done',
                    'payload_hash' => $payloadHash,
                    'http_status' => $httpStatus,
                    'payload' => $payload,
                    'created_at' => time(),
                ], $this->getTtl());
                $response->header(['X-Idempotency-Replayed' => '0']);
                return $response;
            }
        }

        $this->deleteRecord($cacheKey);
        return $response;
    }

    /**
     * @notes 在文件锁临界区内检查缓存状态或写入 processing 占位
     */
    private function acquireOrReplay($request, string $scopeHash, string $cacheKey, string $payloadHash): ?Response
    {
        $lockHandle = $this->openLock($scopeHash);
        try {
            $existing = $this->readRecord($cacheKey);
            if (is_array($existing)) {
                $status = (string)($existing['status'] ?? '');
                if ($status === 'processing') {
                    return JsonService::fail('请勿重复提交', [], 0, 1, ErrorCodeEnum::IDEMPOTENT_REPLAY)
                        ->header(['X-Idempotency-Replayed' => '0']);
                }

                if ($status === 'done') {
                    $savedHash = (string)($existing['payload_hash'] ?? '');
                    if (!hash_equals($savedHash, $payloadHash)) {
                        return JsonService::fail('幂等键与请求内容不匹配', [], 0, 1, ErrorCodeEnum::IDEMPOTENT_REPLAY)
                            ->header(['X-Idempotency-Replayed' => '0']);
                    }

                    $requestId = RequestContextService::ensureRequestId($request);
                    $replayStatus = (int)($existing['http_status'] ?? 200);
                    $replayPayload = is_array($existing['payload'] ?? null) ? $existing['payload'] : [];

                    return json(
                        $replayPayload,
                        $replayStatus,
                        [
                            'Content-Type' => 'application/json; charset=utf-8',
                            RequestContextService::HEADER_REQUEST_ID => $requestId,
                            'X-Idempotency-Replayed' => '1',
                        ],
                        ['json_encode_param' => JSON_UNESCAPED_SLASHES]
                    );
                }
            }

            $this->writeRecord($cacheKey, [
                'status' => 'processing',
                'payload_hash' => $payloadHash,
                'created_at' => time(),
            ], $this->getLockTtl());

            return null;
        } finally {
            $this->closeLock($lockHandle);
        }
    }

    /**
     * @notes 打开并加锁 scope 对应的互斥文件
     * @return resource|null
     */
    private function openLock(string $scopeHash)
    {
        $lockDir = rtrim($this->getLockDir(), '/\\') . DIRECTORY_SEPARATOR . substr($scopeHash, 0, 2);
        if (!is_dir($lockDir) && !@mkdir($lockDir, 0777, true) && !is_dir($lockDir)) {
            return null;
        }

        $lockPath = $lockDir . DIRECTORY_SEPARATOR . $scopeHash . '.lock';
        $handle = @fopen($lockPath, 'c+');
        if ($handle === false) {
            return null;
        }

        @flock($handle, LOCK_EX);
        return $handle;
    }

    /**
     * @param resource|null $handle
     */
    private function closeLock($handle): void
    {
        if (!is_resource($handle)) {
            return;
        }
        @flock($handle, LOCK_UN);
        @fclose($handle);
    }

    /**
     * @notes 计算请求体指纹，防止同一 Idempotency-Key 携带不同载荷误重放
     */
    private function computePayloadHash($request): string
    {
        $rawContent = trim((string)$request->getContent());
        if ($rawContent !== '') {
            return hash('sha256', $rawContent);
        }

        $post = $request->post();
        if (!is_array($post)) {
            $post = [];
        }
        $this->ksortRecursive($post);
        $encoded = json_encode($post, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return hash('sha256', $encoded === false ? '' : $encoded);
    }

    private function ksortRecursive(array &$data): void
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
        unset($value);
        ksort($data);
    }

    private function extractJsonPayload(Response $response): ?array
    {
        $data = $response->getData();
        if (is_array($data)) {
            return $data;
        }

        $content = trim($response->getContent());
        if ($content === '') {
            return null;
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : null;
    }

    protected function readRecord(string $cacheKey): ?array
    {
        $value = Cache::get($cacheKey);
        return is_array($value) ? $value : null;
    }

    protected function writeRecord(string $cacheKey, array $record, int $ttl): void
    {
        Cache::set($cacheKey, $record, $ttl);
    }

    protected function deleteRecord(string $cacheKey): void
    {
        Cache::delete($cacheKey);
    }

    protected function getLockDir(): string
    {
        return rtrim((string)runtime_path('idempotency'), '/\\');
    }

    protected function getTtl(): int
    {
        return max(1, (int)Config::get('project.idempotency.ttl', 600));
    }

    protected function getLockTtl(): int
    {
        return max(1, (int)Config::get('project.idempotency.lock_ttl', 30));
    }
}
