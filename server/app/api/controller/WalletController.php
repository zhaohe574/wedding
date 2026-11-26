<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端我的钱包控制器（对账只读）
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\WalletLogic;

/**
 * 我的钱包控制器
 * Class WalletController
 * @package app\api\controller
 */
class WalletController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * @notes 对账流水列表
     * @return \think\response\Json
     */
    public function flows()
    {
        $params = $this->request->get();
        $result = WalletLogic::flows($this->userId, is_array($params) ? $params : []);
        return $this->data($result);
    }

    /**
     * @notes 对账流水详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $flowKey = trim((string)$this->request->get('flow_key', ''));
        $result = WalletLogic::detail($this->userId, $flowKey);
        if ($result === null) {
            return $this->fail(WalletLogic::getError() ?: '流水记录不存在');
        }
        return $this->data($result);
    }
}
