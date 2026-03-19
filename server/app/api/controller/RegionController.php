<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\RegionLogic;

/**
 * 小程序地区控制器
 */
class RegionController extends BaseApiController
{
    public array $notNeedLogin = ['serviceTree'];

    /**
     * @notes 服务地区树
     * @return \think\response\Json
     */
    public function serviceTree()
    {
        return $this->data(RegionLogic::serviceTree());
    }
}
