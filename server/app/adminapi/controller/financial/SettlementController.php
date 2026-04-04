<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 结算管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\financial;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\financial\SettlementLogic;
use app\adminapi\lists\financial\StaffSettlementLists;
use app\adminapi\lists\financial\SettlementBatchLists;
use app\adminapi\validate\financial\SettlementValidate;

/**
 * 结算管理控制器
 * Class SettlementController
 * @package app\adminapi\controller\financial
 */
class SettlementController extends BaseAdminController
{
    /**
     * @notes 结算记录列表
     */
    public function lists()
    {
        return $this->dataLists(new StaffSettlementLists());
    }

    /**
     * @notes 结算详情
     */
    public function detail()
    {
        $params = (new SettlementValidate())->goCheck('detail');
        $result = SettlementLogic::detail($params['id']);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 执行结算
     */
    public function settle()
    {
        $params = (new SettlementValidate())->goCheck('settle');
        $result = SettlementLogic::settle($params['id']);
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('结算成功');
    }

    /**
     * @notes 批量结算
     */
    public function batchSettle()
    {
        $params = (new SettlementValidate())->goCheck('batchSettle');
        $result = SettlementLogic::batchSettle($params['ids']);
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('结算成功', $result);
    }

    /**
     * @notes 取消结算
     */
    public function cancel()
    {
        $params = (new SettlementValidate())->goCheck('detail');
        $result = SettlementLogic::cancel($params['id']);
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('取消成功');
    }

    /**
     * @notes 结算统计
     */
    public function statistics()
    {
        $params = $this->request->get();
        $result = SettlementLogic::statistics($params);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 人员结算汇总
     */
    public function staffSummary()
    {
        $params = $this->request->get();
        $result = SettlementLogic::staffSummary($params);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 批次列表
     */
    public function batchLists()
    {
        return $this->dataLists(new SettlementBatchLists());
    }

    /**
     * @notes 创建结算批次
     */
    public function createBatch()
    {
        $params = (new SettlementValidate())->goCheck('createBatch');
        $result = SettlementLogic::createBatch($params);
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('创建成功', $result);
    }

    /**
     * @notes 审核批次
     */
    public function auditBatch()
    {
        $params = (new SettlementValidate())->goCheck('auditBatch');
        $result = SettlementLogic::auditBatch($params, $this->adminId);
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('操作成功');
    }

    /**
     * @notes 执行批次
     */
    public function executeBatch()
    {
        $params = (new SettlementValidate())->goCheck('detail');
        $params['batch_id'] = $params['id'];
        $result = SettlementLogic::executeBatch($params, $this->adminId);
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('执行成功', $result);
    }

    /**
     * @notes 取消批次
     */
    public function cancelBatch()
    {
        $params = (new SettlementValidate())->goCheck('detail');
        $params['batch_id'] = $params['id'];
        $result = SettlementLogic::cancelBatch($params);
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('取消成功');
    }

    /**
     * @notes 结算配置列表
     */
    public function configLists()
    {
        $result = SettlementLogic::configLists();
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 添加结算配置
     */
    public function addConfig()
    {
        $params = (new SettlementValidate())->goCheck('addConfig');
        $result = SettlementLogic::addConfig($params);
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('添加成功');
    }

    /**
     * @notes 编辑结算配置
     */
    public function editConfig()
    {
        $params = (new SettlementValidate())->goCheck('editConfig');
        $result = SettlementLogic::editConfig($params);
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('编辑成功');
    }

    /**
     * @notes 删除结算配置
     */
    public function deleteConfig()
    {
        $params = (new SettlementValidate())->goCheck('detail');
        $result = SettlementLogic::deleteConfig($params['id']);
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('删除成功');
    }
}
