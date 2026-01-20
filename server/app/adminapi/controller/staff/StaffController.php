<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\staff;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\staff\StaffLists;
use app\adminapi\logic\staff\StaffLogic;
use app\adminapi\validate\staff\StaffValidate;

/**
 * 工作人员管理控制器
 * Class StaffController
 * @package app\adminapi\controller\staff
 */
class StaffController extends BaseAdminController
{
    /**
     * @notes 工作人员列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new StaffLists());
    }

    /**
     * @notes 工作人员详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new StaffValidate())->goCheck('detail');
        $result = StaffLogic::detail($params['id']);
        return $this->data($result);
    }

    /**
     * @notes 添加工作人员
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new StaffValidate())->post()->goCheck('add');
        $result = StaffLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }

    /**
     * @notes 编辑工作人员
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new StaffValidate())->post()->goCheck('edit');
        $result = StaffLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }

    /**
     * @notes 删除工作人员
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new StaffValidate())->post()->goCheck('delete');
        StaffLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }

    /**
     * @notes 修改工作人员状态
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        $params = (new StaffValidate())->post()->goCheck('status');
        $result = StaffLogic::changeStatus($params);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }

    /**
     * @notes 获取所有工作人员(用于下拉选择)
     * @return \think\response\Json
     */
    public function all()
    {
        $params = $this->request->get();
        $result = StaffLogic::getAll($params);
        return $this->data($result);
    }

    /**
     * @notes 工作人员统计数据
     * @return \think\response\Json
     */
    public function statistics()
    {
        $result = StaffLogic::statistics();
        return $this->data($result);
    }
}
