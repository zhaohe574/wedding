<?php

declare(strict_types=1);

namespace app\adminapi\controller\notification;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\notification\MnpLogLists;
use app\adminapi\lists\notification\MnpTemplateLists;
use app\adminapi\logic\notification\MnpNotificationLogic;

/**
 * 小程序订阅通知管理控制器
 */
class MnpNotificationController extends BaseAdminController
{
    public function templateList()
    {
        return $this->dataLists(new MnpTemplateLists());
    }

    public function templateDetail()
    {
        return $this->data(MnpNotificationLogic::detail((int) $this->request->get('id', 0)));
    }

    public function editTemplate()
    {
        if (MnpNotificationLogic::editTemplate($this->request->post())) {
            return $this->success('小程序订阅通知模板保存成功');
        }
        return $this->fail(MnpNotificationLogic::getError());
    }

    public function logList()
    {
        return $this->dataLists(new MnpLogLists());
    }

    public function testSend()
    {
        $result = MnpNotificationLogic::testSend($this->request->post());
        if (($result['success'] ?? false) === true) {
            return $this->success($result['msg'] ?? '小程序测试通知已提交', $result);
        }
        return $this->fail((string) ($result['msg'] ?? '小程序测试通知发送失败'));
    }
}
