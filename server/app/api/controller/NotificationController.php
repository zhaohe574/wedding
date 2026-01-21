<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端消息通知控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\NotificationLogic;

/**
 * 小程序端消息通知控制器
 * Class NotificationController
 * @package app\api\controller
 */
class NotificationController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * @notes 消息列表
     * @return \think\response\Json
     */
    public function lists()
    {
        $params = $this->request->get();
        $params['user_id'] = $this->userId;
        $result = NotificationLogic::lists($params);
        return $this->success('', $result);
    }

    /**
     * @notes 消息详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $id = $this->request->get('id', 0);
        if (empty($id)) {
            return $this->fail('消息ID不能为空');
        }
        $result = NotificationLogic::detail((int)$id, $this->userId);
        if ($result === false) {
            return $this->fail(NotificationLogic::getError());
        }
        return $this->success('', $result);
    }

    /**
     * @notes 未读数量
     * @return \think\response\Json
     */
    public function unreadCount()
    {
        $result = NotificationLogic::unreadCount($this->userId);
        return $this->success('', $result);
    }

    /**
     * @notes 标记已读
     * @return \think\response\Json
     */
    public function markRead()
    {
        $id = $this->request->post('id', 0);
        if (empty($id)) {
            return $this->fail('消息ID不能为空');
        }
        $result = NotificationLogic::markRead((int)$id, $this->userId);
        if ($result === false) {
            return $this->fail(NotificationLogic::getError());
        }
        return $this->success('标记成功');
    }

    /**
     * @notes 全部标记已读
     * @return \think\response\Json
     */
    public function markAllRead()
    {
        $notifyType = $this->request->post('notify_type', 0);
        $result = NotificationLogic::markAllRead($this->userId, (int)$notifyType);
        return $this->success('标记成功', ['count' => $result]);
    }

    /**
     * @notes 删除消息
     * @return \think\response\Json
     */
    public function delete()
    {
        $id = $this->request->post('id', 0);
        if (empty($id)) {
            return $this->fail('消息ID不能为空');
        }
        $result = NotificationLogic::delete((int)$id, $this->userId);
        if ($result === false) {
            return $this->fail(NotificationLogic::getError());
        }
        return $this->success('删除成功');
    }

    /**
     * @notes 清空消息
     * @return \think\response\Json
     */
    public function clear()
    {
        $notifyType = $this->request->post('notify_type', 0);
        $result = NotificationLogic::clear($this->userId, (int)$notifyType);
        return $this->success('清空成功', ['count' => $result]);
    }
}
