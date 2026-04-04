<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 消息通知管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\notification;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\notification\NotificationLogic;
use app\adminapi\lists\notification\NotificationLists;
use app\adminapi\validate\notification\NotificationValidate;

/**
 * 消息通知管理控制器
 * Class NotificationController
 * @package app\adminapi\controller\notification
 */
class NotificationController extends BaseAdminController
{
    /**
     * @notes 通知列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new NotificationLists());
    }

    /**
     * @notes 通知详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new NotificationValidate())->goCheck('detail');
        $result = NotificationLogic::detail($params['id']);
        return $this->success('', $result);
    }

    /**
     * @notes 发送通知（单个用户）
     * @return \think\response\Json
     */
    public function send()
    {
        $params = (new NotificationValidate())->post()->goCheck('send');
        $result = NotificationLogic::send($params);
        if ($result === true) {
            return $this->success('发送成功');
        }
        return $this->fail(NotificationLogic::getError());
    }

    /**
     * @notes 批量发送通知
     * @return \think\response\Json
     */
    public function batchSend()
    {
        $params = (new NotificationValidate())->post()->goCheck('batchSend');
        $result = NotificationLogic::batchSend($params);
        if ($result === true) {
            return $this->success('批量发送成功');
        }
        return $this->fail(NotificationLogic::getError());
    }

    /**
     * @notes 全员通知
     * @return \think\response\Json
     */
    public function sendToAll()
    {
        $params = (new NotificationValidate())->post()->goCheck('sendToAll');
        $result = NotificationLogic::sendToAll($params);
        if ($result === true) {
            return $this->success('发送成功');
        }
        return $this->fail(NotificationLogic::getError());
    }

    /**
     * @notes 删除通知
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new NotificationValidate())->post()->goCheck('detail');
        $result = NotificationLogic::delete($params['id']);
        if ($result === true) {
            return $this->success('删除成功');
        }
        return $this->fail(NotificationLogic::getError());
    }

    /**
     * @notes 批量删除通知
     * @return \think\response\Json
     */
    public function batchDelete()
    {
        $params = (new NotificationValidate())->post()->goCheck('batchDelete');
        $result = NotificationLogic::batchDelete($params['ids']);
        if ($result === true) {
            return $this->success('批量删除成功');
        }
        return $this->fail(NotificationLogic::getError());
    }

    /**
     * @notes 通知统计
     * @return \think\response\Json
     */
    public function statistics()
    {
        $params = $this->request->get();
        $result = NotificationLogic::statistics($params);
        return $this->success('', $result);
    }

    /**
     * @notes 获取通知类型选项
     * @return \think\response\Json
     */
    public function typeOptions()
    {
        $result = NotificationLogic::typeOptions();
        return $this->success('', $result);
    }

    /**
     * @notes 通知模板列表
     * @return \think\response\Json
     */
    public function templates()
    {
        $result = NotificationLogic::templates();
        return $this->success('', $result);
    }

    /**
     * @notes 发送趋势统计
     * @return \think\response\Json
     */
    public function sendTrend()
    {
        $params = $this->request->get();
        $result = NotificationLogic::sendTrend($params);
        return $this->success('', $result);
    }
}
