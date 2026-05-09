<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员证书管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\staff;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\staff\StaffCertificateLists;
use app\adminapi\logic\staff\StaffCertificateLogic;
use app\adminapi\validate\staff\StaffCertificateValidate;
use app\common\model\staff\StaffCertificate;
use app\common\service\StaffService;

/**
 * 工作人员证书管理控制器
 * Class StaffCertificateController
 * @package app\adminapi\controller\staff
 */
class StaffCertificateController extends BaseAdminController
{
    /**
     * @notes 证书列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new StaffCertificateLists());
    }

    /**
     * @notes 证书详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new StaffCertificateValidate())->goCheck('detail');
        $certificateId = (int) $params['id'];
        if (StaffService::isStaffRole($this->adminInfo)) {
            $staffId = (int) StaffCertificate::where('id', $certificateId)->value('staff_id');
            if (!StaffService::canAccessStaff($this->adminId, $this->adminInfo, $staffId)) {
                return $this->fail('无权限查看');
            }
        }
        $result = StaffCertificateLogic::detail($certificateId);
        return $this->data($result);
    }

    /**
     * @notes 添加证书
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new StaffCertificateValidate())->post()->goCheck('add');
        if (StaffService::isStaffRole($this->adminInfo) && !StaffService::canAccessStaff($this->adminId, $this->adminInfo, (int)$params['staff_id'])) {
            return $this->fail('无权限操作');
        }
        $result = StaffCertificateLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(StaffCertificateLogic::getError());
    }

    /**
     * @notes 编辑证书
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new StaffCertificateValidate())->post()->goCheck('edit');
        if (StaffService::isStaffRole($this->adminInfo)) {
            $staffId = (int) StaffCertificate::where('id', $params['id'])->value('staff_id');
            if (!StaffService::canAccessStaff($this->adminId, $this->adminInfo, $staffId)) {
                return $this->fail('无权限操作');
            }
        }
        $result = StaffCertificateLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(StaffCertificateLogic::getError());
    }

    /**
     * @notes 删除证书
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new StaffCertificateValidate())->post()->goCheck('delete');
        if (StaffService::isStaffRole($this->adminInfo)) {
            $staffId = (int) StaffCertificate::where('id', $params['id'])->value('staff_id');
            if (!StaffService::canAccessStaff($this->adminId, $this->adminInfo, $staffId)) {
                return $this->fail('无权限操作');
            }
        }
        StaffCertificateLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }

    /**
     * @notes 审核证书
     * @return \think\response\Json
     */
    public function audit()
    {
        $params = (new StaffCertificateValidate())->post()->goCheck('audit');
        if (StaffService::isStaffRole($this->adminInfo)) {
            $staffId = (int) StaffCertificate::where('id', $params['id'])->value('staff_id');
            if (!StaffService::canAccessStaff($this->adminId, $this->adminInfo, $staffId)) {
                return $this->fail('无权限操作');
            }
        }
        $result = StaffCertificateLogic::audit($params);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(StaffCertificateLogic::getError());
    }
}
