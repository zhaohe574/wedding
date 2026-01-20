<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 成本管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\financial;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\financial\CostLogic;
use app\adminapi\lists\financial\CostRecordLists;
use app\adminapi\validate\financial\CostValidate;

/**
 * 成本管理控制器
 * Class CostController
 * @package app\adminapi\controller\financial
 */
class CostController extends BaseAdminController
{
    /**
     * @notes 成本列表
     */
    public function lists()
    {
        return $this->dataLists(new CostRecordLists());
    }

    /**
     * @notes 成本详情
     */
    public function detail()
    {
        $params = (new CostValidate())->goCheck('detail');
        $result = CostLogic::detail($params['id']);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 添加成本
     */
    public function add()
    {
        $params = (new CostValidate())->goCheck('add');
        $result = CostLogic::add($params);
        if ($result === false) {
            return $this->fail(CostLogic::getError());
        }
        return $this->success('添加成功');
    }

    /**
     * @notes 编辑成本
     */
    public function edit()
    {
        $params = (new CostValidate())->goCheck('edit');
        $result = CostLogic::edit($params);
        if ($result === false) {
            return $this->fail(CostLogic::getError());
        }
        return $this->success('编辑成功');
    }

    /**
     * @notes 删除成本
     */
    public function delete()
    {
        $params = (new CostValidate())->goCheck('detail');
        $result = CostLogic::delete($params['id']);
        if ($result === false) {
            return $this->fail(CostLogic::getError());
        }
        return $this->success('删除成功');
    }

    /**
     * @notes 确认成本
     */
    public function confirm()
    {
        $params = (new CostValidate())->goCheck('detail');
        $result = CostLogic::confirm($params['id'], $this->adminId);
        if ($result === false) {
            return $this->fail(CostLogic::getError());
        }
        return $this->success('确认成功');
    }

    /**
     * @notes 批量确认
     */
    public function batchConfirm()
    {
        $params = (new CostValidate())->goCheck('batch');
        $result = CostLogic::batchConfirm($params['ids'], $this->adminId);
        if ($result === false) {
            return $this->fail(CostLogic::getError());
        }
        return $this->success('确认成功', $result);
    }

    /**
     * @notes 成本统计
     */
    public function statistics()
    {
        $params = $this->request->get();
        $result = CostLogic::statistics($params);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 成本类型选项
     */
    public function typeOptions()
    {
        $result = CostLogic::typeOptions();
        return $this->success('获取成功', $result);
    }
}
