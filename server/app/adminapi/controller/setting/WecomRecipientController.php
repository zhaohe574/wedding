<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 企微接收人维护控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\setting;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\setting\WecomRecipientLogic;
use app\adminapi\validate\setting\WecomRecipientValidate;

class WecomRecipientController extends BaseAdminController
{
    public function lists()
    {
        $params = $this->request->get();
        return $this->data(WecomRecipientLogic::lists($params));
    }

    public function updateAdvisor()
    {
        $params = (new WecomRecipientValidate())->goCheck('updateAdvisor');
        $result = WecomRecipientLogic::updateAdvisor($params);
        if ($result) {
            return $this->success('保存成功');
        }

        return $this->fail(WecomRecipientLogic::getError());
    }
}
