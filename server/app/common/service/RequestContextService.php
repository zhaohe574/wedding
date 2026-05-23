<?php
// +----------------------------------------------------------------------
// | 请求上下文服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use think\Request;

/**
 * 为接口响应、操作日志和业务日志提供统一的 request_id / 用户上下文。
 *
 * 该服务不依赖数据库迁移；旧库可直接上线。若后续需要把 request_id
 * 独立落表，请参考 docs/ops/database-migration.md 中的可选迁移规范。
 */
class RequestContextService
{
    public const HEADER_REQUEST_ID = 'X-Request-Id';

    /**
     * 确保当前请求存在 request_id。优先信任网关/前端传入的 X-Request-Id，
     * 但会清洗字符并截断，避免日志污染。
     */
    public static function ensureRequestId(?Request $request = null): string
    {
        $request = $request ?: request();
        $existing = (string)($request->requestId ?? '');
        if ($existing !== '') {
            return $existing;
        }

        $incoming = (string)$request->header(self::HEADER_REQUEST_ID, '');
        $requestId = self::normalizeRequestId($incoming);
        if ($requestId === '') {
            $requestId = self::generateRequestId();
        }

        $request->requestId = $requestId;
        return $requestId;
    }

    /**
     * 获取当前请求的结构化日志上下文。
     *
     * @return array{request_id:string,admin_id:int,user_id:int,order_id:int,method:string,path:string,ip:string}
     */
    public static function current(?Request $request = null, array $extra = []): array
    {
        $request = $request ?: request();
        $params = $request->param();

        $context = [
            'request_id' => self::ensureRequestId($request),
            'admin_id' => (int)($request->adminId ?? ($request->adminInfo['admin_id'] ?? 0)),
            'user_id' => (int)($request->userId ?? ($request->userInfo['user_id'] ?? 0)),
            'order_id' => (int)($extra['order_id'] ?? $params['order_id'] ?? $params['id'] ?? 0),
            'method' => strtoupper((string)$request->method()),
            'path' => (string)$request->url(false),
            'ip' => (string)$request->ip(),
        ];

        foreach ($extra as $key => $value) {
            if (!array_key_exists((string)$key, $context)) {
                $context[(string)$key] = $value;
            }
        }

        return $context;
    }

    /**
     * 生成便于排查的短 request_id：req_yyyymmddHHMMSS_随机串。
     */
    public static function generateRequestId(): string
    {
        try {
            $random = bin2hex(random_bytes(8));
        } catch (\Throwable $e) {
            $random = substr(md5(uniqid('', true)), 0, 16);
        }

        return 'req_' . date('YmdHis') . '_' . $random;
    }

    private static function normalizeRequestId(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[^A-Za-z0-9_.:-]/', '', $value) ?: '';
        return substr($value, 0, 64);
    }
}
