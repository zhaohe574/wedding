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
use app\common\model\schedule\Waitlist;
use app\common\service\StaffService;

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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = (int) Waitlist::where('id', $params['id'])->value('staff_id');
            if ($staffId !== $staffScopeId) {
                return $this->fail('无权限查看');
            }
        }
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $ids = $params['ids'] ?? [];
            $count = $ids ? Waitlist::whereIn('id', $ids)->where('staff_id', $staffScopeId)->count() : 0;
            if ($count !== count($ids)) {
                return $this->fail('无权限操作');
            }
        }
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = (int) Waitlist::where('id', $params['id'])->value('staff_id');
            if ($staffId !== $staffScopeId) {
                return $this->fail('无权限操作');
            }
        }
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = (int) Waitlist::where('id', $params['id'])->value('staff_id');
            if ($staffId !== $staffScopeId) {
                return $this->fail('无权限操作');
            }
        }
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = (int) Waitlist::where('id', $params['id'])->value('staff_id');
            if ($staffId !== $staffScopeId) {
                return $this->fail('无权限操作');
            }
        }
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
        $params = $this->request->get();
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $params['staff_id'] = $staffScopeId;
        }
        $result = WaitlistLogic::statistics($params);
        return $this->data($result);
    }
}
