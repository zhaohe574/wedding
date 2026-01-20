<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员作品管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\staff;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\staff\StaffWorkLists;
use app\adminapi\logic\staff\StaffWorkLogic;
use app\adminapi\validate\staff\StaffWorkValidate;

/**
 * 工作人员作品管理控制器
 * Class StaffWorkController
 * @package app\adminapi\controller\staff
 */
class StaffWorkController extends BaseAdminController
{
    /**
     * @notes 作品列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new StaffWorkLists());
    }

    /**
     * @notes 作品详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new StaffWorkValidate())->goCheck('detail');
        $result = StaffWorkLogic::detail($params['id']);
        return $this->data($result);
    }

    /**
     * @notes 添加作品
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new StaffWorkValidate())->post()->goCheck('add');
        $result = StaffWorkLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(StaffWorkLogic::getError());
    }

    /**
     * @notes 编辑作品
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new StaffWorkValidate())->post()->goCheck('edit');
        $result = StaffWorkLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(StaffWorkLogic::getError());
    }

    /**
     * @notes 删除作品
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new StaffWorkValidate())->post()->goCheck('delete');
        StaffWorkLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }

    /**
     * @notes 修改作品状态
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        $params = (new StaffWorkValidate())->post()->goCheck('status');
        $result = StaffWorkLogic::changeStatus($params);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(StaffWorkLogic::getError());
    }

    /**
     * @notes 设为封面
     * @return \think\response\Json
     */
    public function setCover()
    {
        $params = (new StaffWorkValidate())->post()->goCheck('detail');
        $result = StaffWorkLogic::setCover($params['id']);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(StaffWorkLogic::getError());
    }
}
