<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务分类管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\service;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\service\CategoryLists;
use app\adminapi\logic\service\CategoryLogic;
use app\adminapi\validate\service\CategoryValidate;

/**
 * 服务分类管理控制器
 * Class CategoryController
 * @package app\adminapi\controller\service
 */
class CategoryController extends BaseAdminController
{
    /**
     * @notes 分类列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new CategoryLists());
    }

    /**
     * @notes 分类树形结构
     * @return \think\response\Json
     */
    public function tree()
    {
        $result = CategoryLogic::tree();
        return $this->data($result);
    }

    /**
     * @notes 分类详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new CategoryValidate())->goCheck('detail');
        $result = CategoryLogic::detail($params['id']);
        return $this->data($result);
    }

    /**
     * @notes 添加分类
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new CategoryValidate())->post()->goCheck('add');
        $result = CategoryLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(CategoryLogic::getError());
    }

    /**
     * @notes 编辑分类
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new CategoryValidate())->post()->goCheck('edit');
        $result = CategoryLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(CategoryLogic::getError());
    }

    /**
     * @notes 删除分类
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new CategoryValidate())->post()->goCheck('delete');
        $result = CategoryLogic::delete($params);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(CategoryLogic::getError());
    }

    /**
     * @notes 修改分类状态
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        $params = (new CategoryValidate())->post()->goCheck('status');
        $result = CategoryLogic::changeStatus($params);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(CategoryLogic::getError());
    }

    /**
     * @notes 获取所有分类(用于下拉选择)
     * @return \think\response\Json
     */
    public function all()
    {
        $result = CategoryLogic::getAll();
        return $this->data($result);
    }
}
