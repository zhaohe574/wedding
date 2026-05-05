<?php
declare(strict_types=1);

namespace app\adminapi\controller\setting;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\setting\OrderConfirmLetterLogic;
use app\adminapi\validate\setting\OrderConfirmLetterValidate;

class OrderConfirmLetterController extends BaseAdminController
{
    public function getConfig()
    {
        return $this->data(OrderConfirmLetterLogic::getConfig());
    }

    public function setConfig()
    {
        $params = (new OrderConfirmLetterValidate())->post()->goCheck('setConfig');
        OrderConfirmLetterLogic::setConfig($params);
        return $this->success('保存成功，影响后续新生成确认函', [], 1, 1);
    }

    public function fontLists()
    {
        return $this->data(OrderConfirmLetterLogic::fontLists());
    }

    public function uploadFont()
    {
        try {
            $result = OrderConfirmLetterLogic::uploadFont();
            return $this->success('上传成功', $result, 1, 1);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function setFont()
    {
        $params = (new OrderConfirmLetterValidate())->post()->goCheck('setFont');
        $result = OrderConfirmLetterLogic::setFont($params);
        return $this->success('设置成功', $result, 1, 1);
    }

    public function deleteFont()
    {
        $params = (new OrderConfirmLetterValidate())->post()->goCheck('deleteFont');
        OrderConfirmLetterLogic::deleteFont($params);
        return $this->success('删除成功', [], 1, 1);
    }

    public function checkFont()
    {
        return $this->data(OrderConfirmLetterLogic::checkFont());
    }
}
