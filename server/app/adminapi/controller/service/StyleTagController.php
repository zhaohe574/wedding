<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 风格标签管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\service;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\service\StyleTagLists;
use app\adminapi\logic\service\StyleTagLogic;
use app\adminapi\validate\service\StyleTagValidate;
use app\common\model\service\StyleTag;

/**
 * 风格标签管理控制器
 * Class StyleTagController
 * @package app\adminapi\controller\service
 */
class StyleTagController extends BaseAdminController
{
    /**
     * @notes 标签列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new StyleTagLists());
    }

    /**
     * @notes 标签详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new StyleTagValidate())->goCheck('detail');
        $result = StyleTagLogic::detail($params['id']);
        return $this->data($result);
    }

    /**
     * @notes 添加标签
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new StyleTagValidate())->post()->goCheck('add');
        $result = StyleTagLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(StyleTagLogic::getError());
    }

    /**
     * @notes 编辑标签
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new StyleTagValidate())->post()->goCheck('edit');
        $result = StyleTagLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(StyleTagLogic::getError());
    }

    /**
     * @notes 删除标签
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new StyleTagValidate())->post()->goCheck('delete');
        $result = StyleTagLogic::delete($params);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(StyleTagLogic::getError());
    }

    /**
     * @notes 修改标签状态
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        $params = (new StyleTagValidate())->post()->goCheck('status');
        $result = StyleTagLogic::changeStatus($params);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(StyleTagLogic::getError());
    }

    /**
     * @notes 获取所有标签(用于下拉选择)
     * @return \think\response\Json
     */
    public function all()
    {
        $params = $this->request->get();
        $result = StyleTagLogic::getAll($params);
        return $this->data($result);
    }

    /**
     * @notes 获取标签类型选项
     * @return \think\response\Json
     */
    public function typeOptions()
    {
        $result = StyleTag::getTypeOptions();
        return $this->data($result);
    }
}
