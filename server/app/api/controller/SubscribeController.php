<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端订阅消息控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\SubscribeLogic;
use app\api\validate\SubscribeValidate;

/**
 * 小程序端订阅消息控制器
 * Class SubscribeController
 * @package app\api\controller
 */
class SubscribeController extends BaseApiController
{
    public array $notNeedLogin = ['getTemplateList'];

    /**
     * @notes 获取可用模板列表
     * @return \think\response\Json
     */
    public function getTemplateList()
    {
        $scene = $this->request->get('scene', '');
        $result = SubscribeLogic::getTemplateList($scene);
        return $this->data($result);
    }

    /**
     * @notes 记录订阅结果
     * @return \think\response\Json
     */
    public function recordSubscribe()
    {
        $params = (new SubscribeValidate())->goCheck('recordSubscribe');
        $params['user_id'] = $this->userId;
        $result = SubscribeLogic::recordSubscribe($params);
        if ($result) {
            return $this->success('记录成功');
        }
        return $this->fail(SubscribeLogic::getError());
    }

    /**
     * @notes 批量记录订阅结果
     * @return \think\response\Json
     */
    public function batchRecordSubscribe()
    {
        $params = (new SubscribeValidate())->goCheck('batchRecord');
        $params['user_id'] = $this->userId;
        $result = SubscribeLogic::batchRecordSubscribe($params);
        return $this->data(['count' => $result]);
    }

    /**
     * @notes 获取我的订阅状态
     * @return \think\response\Json
     */
    public function getMySubscribeStatus()
    {
        $templateIds = $this->request->get('template_ids', '');
        if (is_string($templateIds)) {
            $templateIds = array_filter(explode(',', $templateIds));
        }
        $result = SubscribeLogic::getUserSubscribeStatus($this->userId, $templateIds);
        return $this->data($result);
    }

    /**
     * @notes 获取我的订阅列表
     * @return \think\response\Json
     */
    public function getMySubscriptions()
    {
        $result = SubscribeLogic::getUserSubscriptions($this->userId);
        return $this->data($result);
    }

    /**
     * @notes 获取我的消息记录
     * @return \think\response\Json
     */
    public function getMyMessageLogs()
    {
        $params = $this->request->get();
        $params['user_id'] = $this->userId;
        $result = SubscribeLogic::getUserMessageLogs($params);
        return $this->data($result);
    }

    /**
     * @notes 获取需要订阅的场景列表
     * @return \think\response\Json
     */
    public function getSceneList()
    {
        $result = SubscribeLogic::getEnabledScenes();
        return $this->data($result);
    }

    /**
     * @notes 检查场景订阅状态
     * @return \think\response\Json
     */
    public function checkSceneSubscribe()
    {
        $scene = $this->request->get('scene', '');
        if (empty($scene)) {
            return $this->fail('场景不能为空');
        }
        $result = SubscribeLogic::checkSceneSubscribe($this->userId, $scene);
        return $this->data($result);
    }
}
