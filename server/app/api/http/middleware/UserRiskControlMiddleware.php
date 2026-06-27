<?php

declare(strict_types=1);

namespace app\api\http\middleware;

use app\common\service\JsonService;
use app\common\service\UserRiskControlService;

/**
 * 小程序端用户风险等级写操作拦截。
 */
class UserRiskControlMiddleware
{
    public function handle($request, \Closure $next)
    {
        $userId = (int)($request->userId ?? 0);
        if ($userId <= 0) {
            return $next($request);
        }

        if (!UserRiskControlService::isActionAllowed($userId, (string)$request->controller(), (string)$request->action())) {
            return JsonService::fail('当前账号安全等级较高，暂时无法使用该功能');
        }

        return $next($request);
    }
}
