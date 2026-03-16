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
     * @notes 获取服务人员数据范围（my* 接口必须）
     * @return int
     */
    protected function getRequiredStaffScopeId(): int
    {
        return StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
    }

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

    /**
     * @notes 我的候补列表
     * @return \think\response\Json
     */
    public function myWaitlist()
    {
        if ($this->getRequiredStaffScopeId() <= 0) {
            return $this->failRequiredStaffScope();
        }
        return $this->dataLists(new WaitlistLists());
    }

    /**
     * @notes 我的候补详情
     * @return \think\response\Json
     */
    public function myWaitlistDetail()
    {
        $staffScopeId = $this->getRequiredStaffScopeId();
        if ($staffScopeId <= 0) {
            return $this->failRequiredStaffScope();
        }
        $params = (new WaitlistValidate())->goCheck('detail');
        $staffId = (int)Waitlist::where('id', $params['id'])->value('staff_id');
        if ($staffId !== $staffScopeId) {
            return $this->fail('无权限查看');
        }
        $result = WaitlistLogic::detail((int)$params['id']);
        return $this->data($result);
    }

    /**
     * @notes 我的候补批量通知
     * @return \think\response\Json
     */
    public function myWaitlistBatchNotify()
    {
        $staffScopeId = $this->getRequiredStaffScopeId();
        if ($staffScopeId <= 0) {
            return $this->failRequiredStaffScope();
        }
        $params = (new WaitlistValidate())->post()->goCheck('batchNotify');
        $ids = $params['ids'] ?? [];
        $count = $ids ? Waitlist::whereIn('id', $ids)->where('staff_id', $staffScopeId)->count() : 0;
        if ($count !== count($ids)) {
            return $this->fail('无权限操作');
        }
        $result = WaitlistLogic::batchNotify($params);
        if ($result !== false) {
            return $this->success("成功通知 {$result} 条记录", [], 1, 1);
        }
        return $this->fail(WaitlistLogic::getError());
    }

    /**
     * @notes 我的候补通知
     * @return \think\response\Json
     */
    public function myWaitlistNotify()
    {
        $staffScopeId = $this->getRequiredStaffScopeId();
        if ($staffScopeId <= 0) {
            return $this->failRequiredStaffScope();
        }
        $params = (new WaitlistValidate())->post()->goCheck('notify');
        $staffId = (int)Waitlist::where('id', $params['id'])->value('staff_id');
        if ($staffId !== $staffScopeId) {
            return $this->fail('无权限操作');
        }
        $result = WaitlistLogic::notify($params);
        if (true === $result) {
            return $this->success('通知成功', [], 1, 1);
        }
        return $this->fail(WaitlistLogic::getError());
    }

    /**
     * @notes 我的候补转正
     * @return \think\response\Json
     */
    public function myWaitlistConvert()
    {
        $staffScopeId = $this->getRequiredStaffScopeId();
        if ($staffScopeId <= 0) {
            return $this->failRequiredStaffScope();
        }
        $params = (new WaitlistValidate())->post()->goCheck('convert');
        $staffId = (int)Waitlist::where('id', $params['id'])->value('staff_id');
        if ($staffId !== $staffScopeId) {
            return $this->fail('无权限操作');
        }
        $result = WaitlistLogic::convert($params);
        if (true === $result) {
            return $this->success('转正成功', [], 1, 1);
        }
        return $this->fail(WaitlistLogic::getError());
    }

    /**
     * @notes 我的候补失效
     * @return \think\response\Json
     */
    public function myWaitlistInvalidate()
    {
        $staffScopeId = $this->getRequiredStaffScopeId();
        if ($staffScopeId <= 0) {
            return $this->failRequiredStaffScope();
        }
        $params = (new WaitlistValidate())->post()->goCheck('invalidate');
        $staffId = (int)Waitlist::where('id', $params['id'])->value('staff_id');
        if ($staffId !== $staffScopeId) {
            return $this->fail('无权限操作');
        }
        $result = WaitlistLogic::invalidate($params);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(WaitlistLogic::getError());
    }

    /**
     * @notes 我的候补统计
     * @return \think\response\Json
     */
    public function myWaitlistStatistics()
    {
        $staffScopeId = $this->getRequiredStaffScopeId();
        if ($staffScopeId <= 0) {
            return $this->failRequiredStaffScope();
        }
        $result = WaitlistLogic::statistics(['staff_id' => $staffScopeId]);
        return $this->data($result);
    }
}
