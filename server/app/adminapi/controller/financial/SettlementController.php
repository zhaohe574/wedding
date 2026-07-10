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
        $params = (new SettlementValidate())->get()->goCheck('detail');
        $result = SettlementLogic::detail((int)$params['id']);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 执行结算
     */
    public function settle()
    {
        $params = (new SettlementValidate())->post()->goCheck('settle');
        $result = SettlementLogic::settle((int)$params['id']);
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('操作成功');
    }

    /**
     * @notes 批量结算
     */
    public function batchSettle()
    {
        $params = (new SettlementValidate())->post()->goCheck('batchSettle');
        $ids = array_map('intval', $params['ids']);
        $result = SettlementLogic::batchSettle($ids);
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('操作成功', $result);
    }

    /**
     * @notes 手动生成结算记录
     */
    public function generate()
    {
        $params = (new SettlementValidate())->post()->goCheck('generate');
        $result = SettlementLogic::generate($params);
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('生成成功', $result);
    }

    /**
     * @notes 重试转账
     */
    public function retryTransfer()
    {
        $params = (new SettlementValidate())->post()->goCheck('detail');
        $result = SettlementLogic::retryTransfer((int)$params['id']);
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('重试成功');
    }

    /**
     * @notes 补入线下平台抽成收款
     */
    public function collectDue()
    {
        $params = (new SettlementValidate())->post()->goCheck('collectDue');
        $result = SettlementLogic::collectDue($params, $this->adminId);
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('补入成功');
    }

    /**
     * @notes 同步转账状态
     */
    public function syncTransfer()
    {
        $params = (new SettlementValidate())->post()->goCheck('syncTransfer');
        $result = SettlementLogic::syncTransfer((int)($params['id'] ?? 0));
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('同步成功', $result);
    }

    /**
     * @notes 转账明细
     */
    public function transferDetail()
    {
        $params = (new SettlementValidate())->get()->goCheck('detail');
        $result = SettlementLogic::transferDetail((int)$params['id']);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 转账配置
     */
    public function transferConfig()
    {
        $result = SettlementLogic::transferConfig();
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 保存转账配置
     */
    public function saveTransferConfig()
    {
        $params = (new SettlementValidate())->post()->goCheck('saveTransferConfig');
        $result = SettlementLogic::saveTransferConfig($params);
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('保存成功', $result);
    }

    /**
     * @notes 取消结算
     */
    public function cancel()
    {
        $params = (new SettlementValidate())->post()->goCheck('detail');
        $result = SettlementLogic::cancel((int)$params['id']);
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
        $params = (new SettlementValidate())->post()->goCheck('createBatch');
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
        $params = (new SettlementValidate())->post()->goCheck('auditBatch');
        $params['batch_id'] = (int)$params['batch_id'];
        $params['status'] = (int)$params['status'];
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
        $params = (new SettlementValidate())->post()->goCheck('detail');
        $params['batch_id'] = (int)$params['id'];
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
        $params = (new SettlementValidate())->post()->goCheck('detail');
        $params['batch_id'] = (int)$params['id'];
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
        $params = (new SettlementValidate())->post()->goCheck('addConfig');
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
        $params = (new SettlementValidate())->post()->goCheck('editConfig');
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
        $params = (new SettlementValidate())->post()->goCheck('detail');
        $result = SettlementLogic::deleteConfig((int)$params['id']);
        if ($result === false) {
            return $this->fail(SettlementLogic::getError());
        }
        return $this->success('删除成功');
    }
}
