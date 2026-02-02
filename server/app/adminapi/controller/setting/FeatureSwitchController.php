<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 功能开关设置
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\setting;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\setting\FeatureSwitchLogic;
use app\adminapi\validate\setting\FeatureSwitchValidate;

/**
 * 功能开关设置控制器
 * Class FeatureSwitchController
 * @package app\adminapi\controller\setting
 */
class FeatureSwitchController extends BaseAdminController
{
    /**
     * @notes 获取功能开关配置
     */
    public function getConfig()
    {
        $result = FeatureSwitchLogic::getConfig();
        return $this->data($result);
    }

    /**
     * @notes 设置功能开关配置
     */
    public function setConfig()
    {
        $params = (new FeatureSwitchValidate())->post()->goCheck('setConfig');
        FeatureSwitchLogic::setConfig($params);
        return $this->success('设置成功', [], 1, 1);
    }
}
