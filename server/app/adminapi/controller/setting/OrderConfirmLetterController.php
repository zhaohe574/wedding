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
}
