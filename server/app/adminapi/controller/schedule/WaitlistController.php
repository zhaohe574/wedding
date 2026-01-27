<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 候补管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\schedule;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\schedule\WaitlistLists;
use app\adminapi\logic\schedule\WaitlistLogic;
use app\adminapi\validate\schedule\WaitlistValidate;

/**
 * 候补管理控制器
 * Class WaitlistController
 * @package app\adminapi\controller\schedule
 */
class WaitlistController extends BaseAdminController
{
    /**
     * @notes 候补列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new WaitlistLists());
    }

    /**
     * @notes 候补详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new WaitlistValidate())->goCheck('detail');
        $result = WaitlistLogic::detail($params['id']);
        return $this->data($result);
    }

    /**
     * @notes 批量通知
     * @return \think\response\Json
     */
    public function batchNotify()
    {
        $params = (new WaitlistValidate())->post()->goCheck('batchNotify');
        $result = WaitlistLogic::batchNotify($params);
        if ($result !== false) {
            return $this->success("成功通知 {$result} 条记录");
        }
        return $this->fail(WaitlistLogic::getError());
    }

    /**
     * @notes 通知单个候补
     * @return \think\response\Json
     */
    public function notify()
    {
        $params = (new WaitlistValidate())->post()->goCheck('notify');
        $result = WaitlistLogic::notify($params);
        if (true === $result) {
            return $this->success('通知成功');
        }
        return $this->fail(WaitlistLogic::getError());
    }

    /**
     * @notes 转正预约
     * @return \think\response\Json
     */
    public function convert()
    {
        $params = (new WaitlistValidate())->post()->goCheck('convert');
        $result = WaitlistLogic::convert($params);
        if (true === $result) {
            return $this->success('转正成功');
        }
        return $this->fail(WaitlistLogic::getError());
    }

    /**
     * @notes 标记失效
     * @return \think\response\Json
     */
    public function invalidate()
    {
        $params = (new WaitlistValidate())->post()->goCheck('invalidate');
        $result = WaitlistLogic::invalidate($params);
        if (true === $result) {
            return $this->success('操作成功');
        }
        return $this->fail(WaitlistLogic::getError());
    }

    /**
     * @notes 候补统计
     * @return \think\response\Json
     */
    public function statistics()
    {
        $result = WaitlistLogic::statistics();
        return $this->data($result);
    }
}
