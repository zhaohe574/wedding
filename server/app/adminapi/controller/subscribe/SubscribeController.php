<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订阅消息管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\subscribe;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\subscribe\SubscribeLogic;
use app\adminapi\lists\subscribe\TemplateLists;
use app\adminapi\lists\subscribe\SceneLists;
use app\adminapi\lists\subscribe\MessageLogLists;
use app\adminapi\validate\subscribe\SubscribeValidate;

/**
 * 订阅消息管理控制器
 * Class SubscribeController
 * @package app\adminapi\controller\subscribe
 */
class SubscribeController extends BaseAdminController
{
    // ==================== 模板管理 ====================

    /**
     * @notes 获取模板列表
     * @return \think\response\Json
     */
    public function templateList()
    {
        return $this->dataLists(new TemplateLists());
    }

    /**
     * @notes 获取模板详情
     * @return \think\response\Json
     */
    public function templateDetail()
    {
        $params = (new SubscribeValidate())->goCheck('templateDetail');
        $result = SubscribeLogic::getTemplateDetail($params['id']);
        return $this->data($result);
    }

    /**
     * @notes 添加模板
     * @return \think\response\Json
     */
    public function addTemplate()
    {
        $params = (new SubscribeValidate())->goCheck('addTemplate');
        $result = SubscribeLogic::addTemplate($params);
        if ($result) {
            return $this->success('添加成功');
        }
        return $this->fail(SubscribeLogic::getError());
    }

    /**
     * @notes 编辑模板
     * @return \think\response\Json
     */
    public function editTemplate()
    {
        $params = (new SubscribeValidate())->goCheck('editTemplate');
        $result = SubscribeLogic::editTemplate($params);
        if ($result) {
            return $this->success('编辑成功');
        }
        return $this->fail(SubscribeLogic::getError());
    }

    /**
     * @notes 删除模板
     * @return \think\response\Json
     */
    public function deleteTemplate()
    {
        $params = (new SubscribeValidate())->goCheck('templateDetail');
        $result = SubscribeLogic::deleteTemplate($params['id']);
        if ($result) {
            return $this->success('删除成功');
        }
        return $this->fail(SubscribeLogic::getError());
    }

    /**
     * @notes 切换模板状态
     * @return \think\response\Json
     */
    public function toggleTemplateStatus()
    {
        $params = (new SubscribeValidate())->goCheck('templateDetail');
        $result = SubscribeLogic::toggleTemplateStatus($params['id']);
        if ($result) {
            return $this->success('操作成功');
        }
        return $this->fail(SubscribeLogic::getError());
    }

    /**
     * @notes 获取场景列表
     * @return \think\response\Json
     */
    public function getSceneOptions()
    {
        $result = SubscribeLogic::getSceneOptions();
        return $this->data($result);
    }

    // ==================== 场景配置 ====================

    /**
     * @notes 获取场景配置列表
     * @return \think\response\Json
     */
    public function sceneList()
    {
        return $this->dataLists(new SceneLists());
    }

    /**
     * @notes 获取场景详情
     * @return \think\response\Json
     */
    public function sceneDetail()
    {
        $params = (new SubscribeValidate())->goCheck('sceneDetail');
        $result = SubscribeLogic::getSceneDetail($params['id']);
        return $this->data($result);
    }

    /**
     * @notes 编辑场景配置
     * @return \think\response\Json
     */
    public function editScene()
    {
        $params = (new SubscribeValidate())->goCheck('editScene');
        $result = SubscribeLogic::editScene($params);
        if ($result) {
            return $this->success('编辑成功');
        }
        return $this->fail(SubscribeLogic::getError());
    }

    /**
     * @notes 切换场景状态
     * @return \think\response\Json
     */
    public function toggleSceneStatus()
    {
        $params = (new SubscribeValidate())->goCheck('sceneDetail');
        $result = SubscribeLogic::toggleSceneStatus($params['id']);
        if ($result) {
            return $this->success('操作成功');
        }
        return $this->fail(SubscribeLogic::getError());
    }

    /**
     * @notes 绑定模板到场景
     * @return \think\response\Json
     */
    public function bindTemplate()
    {
        $params = (new SubscribeValidate())->goCheck('bindTemplate');
        $result = SubscribeLogic::bindTemplate($params['scene_id'], $params['template_id']);
        if ($result) {
            return $this->success('绑定成功');
        }
        return $this->fail(SubscribeLogic::getError());
    }

    // ==================== 发送记录 ====================

    /**
     * @notes 获取发送记录列表
     * @return \think\response\Json
     */
    public function logList()
    {
        return $this->dataLists(new MessageLogLists());
    }

    /**
     * @notes 获取发送记录详情
     * @return \think\response\Json
     */
    public function logDetail()
    {
        $params = (new SubscribeValidate())->goCheck('logDetail');
        $result = SubscribeLogic::getLogDetail($params['id']);
        return $this->data($result);
    }

    /**
     * @notes 重试发送
     * @return \think\response\Json
     */
    public function retryLog()
    {
        $params = (new SubscribeValidate())->goCheck('logDetail');
        $result = SubscribeLogic::retryLog($params['id']);
        if ($result) {
            return $this->success('重试成功');
        }
        return $this->fail(SubscribeLogic::getError());
    }

    // ==================== 统计数据 ====================

    /**
     * @notes 获取发送统计
     * @return \think\response\Json
     */
    public function getStatistics()
    {
        $params = $this->request->get();
        $result = SubscribeLogic::getStatistics($params);
        return $this->data($result);
    }

    /**
     * @notes 获取发送趋势
     * @return \think\response\Json
     */
    public function getTrend()
    {
        $days = $this->request->get('days', 7);
        $result = SubscribeLogic::getTrend((int)$days);
        return $this->data($result);
    }

    /**
     * @notes 获取场景统计
     * @return \think\response\Json
     */
    public function getSceneStatistics()
    {
        $params = $this->request->get();
        $result = SubscribeLogic::getSceneStatistics($params);
        return $this->data($result);
    }

    // ==================== 测试发送 ====================

    /**
     * @notes 测试发送消息
     * @return \think\response\Json
     */
    public function testSend()
    {
        $params = (new SubscribeValidate())->goCheck('testSend');
        $result = SubscribeLogic::testSend($params);
        if ($result['success']) {
            return $this->success('发送成功', $result);
        }
        return $this->fail($result['msg']);
    }
}
