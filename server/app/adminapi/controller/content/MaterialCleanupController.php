<?php
// +----------------------------------------------------------------------
// | 素材清理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\content;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\MaterialCleanupLogic;
use app\adminapi\validate\MaterialCleanupValidate;
use think\response\Json;

class MaterialCleanupController extends BaseAdminController
{
    public function summary(): Json
    {
        $params = (new MaterialCleanupValidate())->get()->goCheck('summary');
        return $this->data(MaterialCleanupLogic::summary($params));
    }

    public function lists(): Json
    {
        $params = (new MaterialCleanupValidate())->get()->goCheck('lists');
        return $this->data(MaterialCleanupLogic::lists($params));
    }

    public function delete(): Json
    {
        $params = (new MaterialCleanupValidate())->post()->goCheck('delete');
        return $this->success('清理完成', MaterialCleanupLogic::delete($params, $this->adminId), 1, 1);
    }
}
