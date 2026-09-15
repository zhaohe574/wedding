<?php

declare(strict_types=1);

namespace app\adminapi\controller\notification;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\notification\OaFollowerLists;
use app\adminapi\lists\notification\OaLogLists;
use app\adminapi\lists\notification\OaTemplateLists;
use app\adminapi\logic\notification\OaNotificationLogic;

/**
 * 公众号通知管理控制器。
 */
class OaNotificationController extends BaseAdminController
{
    public function config()
    {
        return $this->data(OaNotificationLogic::config());
    }

    public function saveConfig()
    {
        if (OaNotificationLogic::saveConfig($this->request->post())) {
            return $this->success('公众号通知开关保存成功');
        }
        return $this->fail(OaNotificationLogic::getError());
    }

    public function templateList()
    {
        return $this->dataLists(new OaTemplateLists());
    }

    public function templateDetail()
    {
        return $this->data(OaNotificationLogic::detail((int) $this->request->get('id', 0)));
    }

    public function editTemplate()
    {
        if (OaNotificationLogic::editTemplate($this->request->post())) {
            return $this->success('公众号模板保存成功');
        }
        return $this->fail(OaNotificationLogic::getError());
    }

    public function logList()
    {
        return $this->dataLists(new OaLogLists());
    }

    public function eventList()
    {
        $page = max(1, (int)$this->request->get('page_no', 1));
        $size = min(100, max(1, (int)$this->request->get('page_size', 20)));
        $query = \think\facade\Db::name('notification_event');
        $count = (clone $query)->count();
        $rows = $query->order('id desc')->page($page, $size)->select()->toArray();
        foreach ($rows as &$row) {
            $payload = json_decode($row['payload'], true) ?: [];
            $row['event'] = $payload['event'] ?? '';
            $row['title'] = $payload['title'] ?? '';
            $row['business_type'] = $payload['business_type'] ?? '';
            $row['business_id'] = $payload['business_id'] ?? 0;
            unset($row['payload']);
        }
        return $this->data(['lists' => $rows, 'count' => $count, 'page_no' => $page, 'page_size' => $size]);
    }

    public function followerList()
    {
        return $this->dataLists(new OaFollowerLists());
    }

    public function retry()
    {
        $result = \app\common\service\WechatNotificationService::retryLog((int)$this->request->post('id', 0));
        return $result ? $this->success('已加入重试队列') : $this->fail('通知不可重试，请检查关注、绑定、模板及业务权限');
    }

    public function eventRetry()
    {
        $result = \app\common\service\BusinessNotificationService::retry((int)$this->request->post('id', 0));
        return $result ? $this->success('业务事件已加入重试队列') : $this->fail('事件不可重试或业务权限已变化');
    }

    public function testSend()
    {
        $result = OaNotificationLogic::testSend($this->request->post());
        if (($result['success'] ?? false) === true) {
            return $this->success('公众号测试通知已提交', $result);
        }
        return $this->fail((string) ($result['msg'] ?? '公众号测试通知发送失败'));
    }
}
