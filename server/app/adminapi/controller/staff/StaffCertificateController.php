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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = (int) StaffCertificate::where('id', $params['id'])->value('staff_id');
            if ($staffId !== $staffScopeId) {
                return $this->fail('无权限查看');
            }
        }
        $result = StaffCertificateLogic::detail($params['id']);
        return $this->data($result);
    }

    /**
     * @notes 添加证书
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new StaffCertificateValidate())->post()->goCheck('add');
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0 && (int) $params['staff_id'] !== $staffScopeId) {
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = (int) StaffCertificate::where('id', $params['id'])->value('staff_id');
            if ($staffId !== $staffScopeId) {
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = (int) StaffCertificate::where('id', $params['id'])->value('staff_id');
            if ($staffId !== $staffScopeId) {
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            return $this->fail('无权限操作');
        }
        $result = StaffCertificateLogic::audit($params);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(StaffCertificateLogic::getError());
    }
}
