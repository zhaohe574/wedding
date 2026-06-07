<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 新人问卷平台管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\questionnaire;

use app\adminapi\controller\BaseAdminController;
use app\common\service\CoupleQuestionnaireService;

/**
 * 新人问卷平台管理控制器
 */
class CoupleQuestionnaireController extends BaseAdminController
{
    /**
     * @notes 服务人员问卷配置列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->data(CoupleQuestionnaireService::adminConfigList($this->request->get()));
    }

    /**
     * @notes 指定服务人员问卷配置详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $staffId = (int)$this->request->get('staff_id', 0);
        if ($staffId <= 0) {
            return $this->fail('请选择服务人员');
        }

        return $this->data(CoupleQuestionnaireService::getStaffConfig($staffId));
    }

    /**
     * @notes 管理员代服务人员保存问卷草稿
     * @return \think\response\Json
     */
    public function save()
    {
        $staffId = (int)$this->request->post('staff_id', 0);
        if ($staffId <= 0) {
            return $this->fail('请选择服务人员');
        }

        try {
            CoupleQuestionnaireService::saveStaffConfig($staffId, $this->request->post());
            return $this->success('保存成功', [], 1, 1);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 管理员代服务人员发布问卷新版
     * @return \think\response\Json
     */
    public function publish()
    {
        $staffId = (int)$this->request->post('staff_id', 0);
        if ($staffId <= 0) {
            return $this->fail('请选择服务人员');
        }

        try {
            $result = CoupleQuestionnaireService::publishStaffVersion($staffId, $this->request->post());
            return $this->success('发布成功', $result, 1, 1);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 全部新人问卷任务
     * @return \think\response\Json
     */
    public function tasks()
    {
        return $this->data(CoupleQuestionnaireService::adminTaskList($this->request->get()));
    }

    /**
     * @notes 新人问卷任务详情
     * @return \think\response\Json
     */
    public function taskDetail()
    {
        $id = (int)$this->request->get('id', 0);
        $result = CoupleQuestionnaireService::adminTaskDetail($id);
        if (empty($result)) {
            return $this->fail('问卷任务不存在');
        }

        return $this->data($result);
    }

    /**
     * @notes 管理员手动发送问卷
     * @return \think\response\Json
     */
    public function send()
    {
        $id = (int)$this->request->post('id', 0);
        $orderId = (int)$this->request->post('order_id', 0);
        try {
            if ($id > 0) {
                CoupleQuestionnaireService::adminSendTask($id);
                return $this->success('发送成功', ['id' => $id], 1, 1);
            }
            if ($orderId > 0) {
                $task = CoupleQuestionnaireService::adminSendTaskByOrder($orderId);
                return $this->success('发送成功', $task, 1, 1);
            }
            return $this->fail('请选择问卷任务或订单');
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }
}
