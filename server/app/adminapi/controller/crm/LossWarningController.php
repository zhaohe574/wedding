<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 流失预警控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\crm;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\crm\LossWarningLists;
use app\adminapi\logic\crm\LossWarningLogic;
use app\adminapi\validate\crm\LossWarningValidate;

/**
 * 流失预警控制器
 */
class LossWarningController extends BaseAdminController
{
    /**
     * @notes 流失预警列表
     */
    public function lists()
    {
        return $this->dataLists(new LossWarningLists());
    }

    /**
     * @notes 流失预警详情
     */
    public function detail()
    {
        $params = (new LossWarningValidate())->goCheck('detail');
        $detail = LossWarningLogic::detail((int)$params['id'], $this->getCrmAdvisorScopeId(), $this->adminInfo);
        if ($detail === false) {
            return $this->fail(LossWarningLogic::getError());
        }
        return $this->data($detail);
    }

    /**
     * @notes 处理预警
     */
    public function handle()
    {
        $params = (new LossWarningValidate())->post()->goCheck('handle');
        $result = LossWarningLogic::handle($params, $this->getCrmAdvisorScopeId(), $this->adminInfo);
        if ($result === true) {
            return $this->success('处理成功', [], 1, 1);
        }
        return $this->fail(LossWarningLogic::getError());
    }

    /**
     * @notes 忽略预警
     */
    public function ignore()
    {
        $params = (new LossWarningValidate())->post()->goCheck('ignore');
        $result = LossWarningLogic::ignore($params, $this->getCrmAdvisorScopeId(), $this->adminInfo);
        if ($result === true) {
            return $this->success('忽略成功', [], 1, 1);
        }
        return $this->fail(LossWarningLogic::getError());
    }

    /**
     * @notes 手动生成预警
     */
    public function generate()
    {
        $result = LossWarningLogic::generate($this->getCrmAdvisorScopeId());
        if ($result === false) {
            return $this->fail(LossWarningLogic::getError());
        }
        return $this->success('生成成功', $result, 1, 1);
    }

    /**
     * @notes 推送待处理预警
     */
    public function push()
    {
        $params = $this->request->post();
        $result = LossWarningLogic::push($params, $this->getCrmAdvisorScopeId(), $this->adminInfo);
        if ($result === false) {
            return $this->fail(LossWarningLogic::getError());
        }
        return $this->success('推送完成', $result, 1, 1);
    }

    /**
     * @notes 预警统计
     */
    public function stats()
    {
        return $this->data(LossWarningLogic::stats($this->getCrmAdvisorScopeId()));
    }

    /**
     * @notes 预警选项
     */
    public function options()
    {
        return $this->data(LossWarningLogic::options());
    }
}
