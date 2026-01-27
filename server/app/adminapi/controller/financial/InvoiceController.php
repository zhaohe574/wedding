<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 发票管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\financial;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\financial\InvoiceLogic;
use app\adminapi\lists\financial\InvoiceLists;
use app\adminapi\validate\financial\InvoiceValidate;

/**
 * 发票管理控制器
 * Class InvoiceController
 * @package app\adminapi\controller\financial
 */
class InvoiceController extends BaseAdminController
{
    /**
     * @notes 发票列表
     */
    public function lists()
    {
        return $this->dataLists(new InvoiceLists());
    }

    /**
     * @notes 发票详情
     */
    public function detail()
    {
        $params = (new InvoiceValidate())->goCheck('detail');
        $result = InvoiceLogic::detail($params['id']);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 开票
     */
    public function issue()
    {
        $params = (new InvoiceValidate())->goCheck('issue');
        $result = InvoiceLogic::issue($params, $this->adminId);
        if ($result === false) {
            return $this->fail(InvoiceLogic::getError());
        }
        return $this->success('开票成功');
    }

    /**
     * @notes 开票失败
     */
    public function markFailed()
    {
        $params = (new InvoiceValidate())->goCheck('fail');
        $result = InvoiceLogic::fail($params);
        if ($result === false) {
            return $this->fail(InvoiceLogic::getError());
        }
        return $this->success('操作成功');
    }

    /**
     * @notes 作废发票
     */
    public function void()
    {
        $params = (new InvoiceValidate())->goCheck('void');
        $result = InvoiceLogic::void($params);
        if ($result === false) {
            return $this->fail(InvoiceLogic::getError());
        }
        return $this->success('作废成功');
    }

    /**
     * @notes 发票统计
     */
    public function statistics()
    {
        $params = $this->request->get();
        $result = InvoiceLogic::statistics($params);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 发票类型选项
     */
    public function typeOptions()
    {
        $result = InvoiceLogic::typeOptions();
        return $this->success('获取成功', $result);
    }
}
