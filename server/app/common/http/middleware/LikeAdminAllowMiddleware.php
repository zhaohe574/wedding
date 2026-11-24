<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\common\http\middleware;

use app\common\service\JsonService;
use Closure;

/**
 * 自定义跨域中间件
 * Class LikeAdminAllowMiddleware
 * @package app\common\http\middleware
 */
class LikeAdminAllowMiddleware
{
    /**
     * 允许的请求头常量
     */
    private const ALLOWED_HEADERS = [
        'Authorization', 'Sec-Fetch-Mode', 'DNT', 'X-Mx-ReqToken', 'Keep-Alive', 'User-Agent',
        'If-Match', 'If-None-Match', 'If-Unmodified-Since', 'X-Requested-With', 'If-Modified-Since',
        'Cache-Control', 'Content-Type', 'Accept-Language', 'Origin', 'Accept-Encoding', 'Access-Token',
        'token', 'version', 'X-Request-Id', 'Idempotency-Key'
    ];

    /**
     * @notes 跨域处理
     * @param $request
     * @param \Closure $next
     * @param array|null $header
     * @return mixed|\think\Response
     * @author 令狐冲
     * @date 2021/7/26 11:51
     */
    public function handle($request, Closure $next, ?array $header = []): mixed
    {
        // 设置跨域头
        $corsHeaders = $this->buildCorsHeaders($request);

        // 如果是OPTIONS请求，直接返回响应
        if (strtoupper($request->method()) === 'OPTIONS') {
            return response('', 204)->header($corsHeaders);
        }

        // 安装检测
        $install = file_exists(root_path() . '/config/install.lock');
        if (!$install) {
            return JsonService::fail('程序未安装', [], -2)->header($corsHeaders);
        }

        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header($corsHeaders);
        }

        return $response;
    }

    /**
     * @notes 生成跨域响应头
     *
     * 认证走 token 请求头而不是 Cookie，因此：
     * - 配置了 [CORS] ALLOWED_ORIGINS 时按白名单回显 Origin，并允许凭据；
     * - 未配置时保持通配，但不能声明 Allow-Credentials（* 与凭据并存会被浏览器直接拒绝）。
     *
     * @param $request
     * @return array
     */
    private function buildCorsHeaders($request): array
    {
        $headers = [
            'Access-Control-Allow-Headers' => implode(', ', self::ALLOWED_HEADERS),
            'Access-Control-Allow-Methods' => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
            'Access-Control-Expose-Headers' => 'X-Request-Id, X-Idempotency-Replayed',
            'Access-Control-Max-Age'       => '1728000',
        ];

        $allowedOrigins = $this->allowedOrigins();
        $origin = trim((string)$request->header('origin', ''));

        if (!empty($allowedOrigins)) {
            if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
                $headers['Access-Control-Allow-Origin'] = $origin;
                $headers['Access-Control-Allow-Credentials'] = 'true';
                $headers['Vary'] = 'Origin';
            }

            return $headers;
        }

        $headers['Access-Control-Allow-Origin'] = '*';

        return $headers;
    }

    /**
     * @notes 读取跨域白名单配置（逗号分隔）
     * @return array
     */
    private function allowedOrigins(): array
    {
        $raw = (string)env('cors.allowed_origins', '');
        if ($raw === '') {
            return [];
        }

        $origins = [];
        foreach (explode(',', $raw) as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }
            $origins[] = rtrim($item, '/');
        }

        return $origins;
    }
}
