<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 后台模块下线守卫
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\concern;

use app\common\service\JsonService;

trait OfflineModuleGuard
{
    /**
     * @notes 终止已下线模块的后台访问
     */
    protected function abortOfflineModule(string $moduleName): void
    {
        JsonService::throw($moduleName . '已在精简版后台下线');
    }
}
