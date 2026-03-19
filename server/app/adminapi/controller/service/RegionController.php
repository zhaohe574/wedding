<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\service;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\service\RegionLists;
use app\adminapi\logic\service\RegionLogic;
use app\adminapi\validate\service\RegionValidate;

/**
 * 服务地区控制器
 */
class RegionController extends BaseAdminController
{
    /**
     * @notes 地区列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new RegionLists());
    }

    /**
     * @notes 全部城市选项
     * @return \think\response\Json
     */
    public function cityOptions()
    {
        return $this->data(RegionLogic::cityOptions());
    }

    /**
     * @notes 启用城市选项
     * @return \think\response\Json
     */
    public function enabledCityOptions()
    {
        return $this->data(RegionLogic::enabledCityOptions());
    }

    /**
     * @notes 区县选项
     * @return \think\response\Json
     */
    public function districtOptions()
    {
        $params = (new RegionValidate())->goCheck('districtOptions');
        return $this->data(RegionLogic::districtOptions((string)$params['city_code']));
    }

    /**
     * @notes 新增地区
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new RegionValidate())->post()->goCheck('add');
        if (RegionLogic::add($params)) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(RegionLogic::getError());
    }

    /**
     * @notes 编辑地区
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new RegionValidate())->post()->goCheck('edit');
        if (RegionLogic::edit($params)) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(RegionLogic::getError());
    }

    /**
     * @notes 删除地区
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new RegionValidate())->post()->goCheck('delete');
        if (RegionLogic::delete($params)) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(RegionLogic::getError());
    }

    /**
     * @notes 修改状态
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        $params = (new RegionValidate())->post()->goCheck('status');
        if (RegionLogic::changeStatus($params)) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(RegionLogic::getError());
    }
}
