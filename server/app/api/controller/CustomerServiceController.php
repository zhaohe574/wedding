<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 用户咨询入口控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\CustomerServiceLogic;
use app\api\validate\CustomerServiceValidate;

/**
 * 用户咨询入口
 */
class CustomerServiceController extends BaseApiController
{
    public array $notNeedLogin = ['startConsult'];

    /**
     * @notes 启动咨询并返回当前应联系的顾问/兜底客服
     * @return \think\response\Json
     */
    public function startConsult()
    {
        $params = (new CustomerServiceValidate())->post()->goCheck('startConsult');
        $result = CustomerServiceLogic::startConsult($this->userId, $params);
        if ($result === false) {
            return $this->fail(CustomerServiceLogic::getError() ?: '咨询入口初始化失败');
        }

        return $this->data($result);
    }
}
