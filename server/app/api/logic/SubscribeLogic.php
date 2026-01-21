<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端订阅消息逻辑层
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\subscribe\SubscribeMessageTemplate;
use app\common\model\subscribe\SubscribeMessageScene;
use app\common\model\subscribe\SubscribeMessageLog;
use app\common\model\subscribe\UserSubscribe;

/**
 * 小程序端订阅消息逻辑层
 * Class SubscribeLogic
 * @package app\api\logic
 */
class SubscribeLogic extends BaseLogic
{
    /**
     * @notes 获取可用模板列表
     * @param string $scene
     * @return array
     */
    public static function getTemplateList(string $scene = ''): array
    {
        $query = SubscribeMessageTemplate::where('status', SubscribeMessageTemplate::STATUS_ENABLED);
        
        if (!empty($scene)) {
            $query->where('scene', $scene);
        }

        $templates = $query->field('template_id, name, scene')
            ->order('sort', 'desc')
            ->select()
            ->toArray();

        foreach ($templates as &$item) {
            $item['scene_desc'] = SubscribeMessageTemplate::getSceneDesc($item['scene']);
        }

        return $templates;
    }

    /**
     * @notes 记录订阅结果
     * @param array $params
     * @return bool
     */
    public static function recordSubscribe(array $params): bool
    {
        try {
            $accepted = ($params['result'] ?? '') === 'accept';
            UserSubscribe::recordSubscribe(
                (int)$params['user_id'],
                $params['template_id'],
                $params['scene'] ?? '',
                $accepted
            );
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 批量记录订阅结果
     * @param array $params
     * @return int
     */
    public static function batchRecordSubscribe(array $params): int
    {
        $subscribeResults = $params['results'] ?? [];
        $scene = $params['scene'] ?? '';
        
        return UserSubscribe::batchRecordSubscribe(
            (int)$params['user_id'],
            $subscribeResults,
            $scene
        );
    }

    /**
     * @notes 获取用户订阅状态
     * @param int $userId
     * @param array $templateIds
     * @return array
     */
    public static function getUserSubscribeStatus(int $userId, array $templateIds): array
    {
        if (empty($templateIds)) {
            // 获取所有启用模板的ID
            $templateIds = SubscribeMessageTemplate::where('status', 1)
                ->column('template_id');
        }

        return UserSubscribe::getUserSubscribeStatus($userId, $templateIds);
    }

    /**
     * @notes 获取用户所有订阅
     * @param int $userId
     * @return array
     */
    public static function getUserSubscriptions(int $userId): array
    {
        $subscriptions = UserSubscribe::getUserAllSubscriptions($userId);

        // 补充模板信息
        $templateIds = array_column($subscriptions, 'template_id');
        $templates = [];
        if (!empty($templateIds)) {
            $templates = SubscribeMessageTemplate::whereIn('template_id', $templateIds)
                ->column('name, scene', 'template_id');
        }

        foreach ($subscriptions as &$item) {
            $template = $templates[$item['template_id']] ?? null;
            $item['template_name'] = $template['name'] ?? '未知模板';
            $item['scene_desc'] = $template ? SubscribeMessageTemplate::getSceneDesc($template['scene']) : '';
            $item['status_desc'] = self::getStatusDesc($item['status']);
            $item['can_send'] = $item['status'] == UserSubscribe::STATUS_PERMANENT || $item['accept_count'] > 0;
        }

        return $subscriptions;
    }

    /**
     * @notes 获取用户消息记录
     * @param array $params
     * @return array
     */
    public static function getUserMessageLogs(array $params): array
    {
        $userId = (int)$params['user_id'];
        $pageSize = (int)($params['page_size'] ?? 20);

        $logs = SubscribeMessageLog::getUserLogs($userId, $pageSize);

        // 补充场景描述
        foreach ($logs['data'] as &$item) {
            $item['scene_desc'] = SubscribeMessageTemplate::getSceneDesc($item['scene']);
            $item['send_status_desc'] = self::getSendStatusDesc($item['send_status']);
        }

        return $logs;
    }

    /**
     * @notes 获取启用的场景列表
     * @return array
     */
    public static function getEnabledScenes(): array
    {
        $scenes = SubscribeMessageScene::getEnabledScenes();

        // 获取关联模板信息
        $templateIds = array_filter(array_column($scenes, 'template_id'));
        $templates = [];
        if (!empty($templateIds)) {
            $templates = SubscribeMessageTemplate::whereIn('template_id', $templateIds)
                ->where('status', 1)
                ->column('name, template_id', 'template_id');
        }

        $result = [];
        foreach ($scenes as $scene) {
            if (empty($scene['template_id']) || !isset($templates[$scene['template_id']])) {
                continue; // 跳过未绑定模板或模板已禁用的场景
            }
            
            $result[] = [
                'scene' => $scene['scene'],
                'name' => $scene['name'],
                'description' => $scene['description'],
                'template_id' => $scene['template_id'],
                'template_name' => $templates[$scene['template_id']]['name'] ?? '',
            ];
        }

        return $result;
    }

    /**
     * @notes 检查场景订阅状态
     * @param int $userId
     * @param string $scene
     * @return array
     */
    public static function checkSceneSubscribe(int $userId, string $scene): array
    {
        // 获取场景配置
        $sceneConfig = SubscribeMessageScene::getByScene($scene);
        if (!$sceneConfig || empty($sceneConfig->template_id)) {
            return [
                'need_subscribe' => false,
                'template_id' => '',
                'reason' => '场景未配置',
            ];
        }

        // 检查模板是否启用
        $template = SubscribeMessageTemplate::where('template_id', $sceneConfig->template_id)
            ->where('status', 1)
            ->find();
        if (!$template) {
            return [
                'need_subscribe' => false,
                'template_id' => '',
                'reason' => '模板未启用',
            ];
        }

        // 检查用户订阅状态
        $hasSubscription = UserSubscribe::hasSubscription($userId, $sceneConfig->template_id);

        return [
            'need_subscribe' => !$hasSubscription,
            'template_id' => $sceneConfig->template_id,
            'template_name' => $template->name,
            'scene_name' => $sceneConfig->name,
            'has_subscription' => $hasSubscription,
        ];
    }

    /**
     * @notes 获取状态描述
     * @param int $status
     * @return string
     */
    protected static function getStatusDesc(int $status): string
    {
        $map = [
            UserSubscribe::STATUS_REJECTED => '已拒绝',
            UserSubscribe::STATUS_ACCEPTED => '已订阅',
            UserSubscribe::STATUS_PERMANENT => '永久订阅',
        ];
        return $map[$status] ?? '未知';
    }

    /**
     * @notes 获取发送状态描述
     * @param int $status
     * @return string
     */
    protected static function getSendStatusDesc(int $status): string
    {
        $map = [
            SubscribeMessageLog::SEND_STATUS_PENDING => '待发送',
            SubscribeMessageLog::SEND_STATUS_SUCCESS => '已发送',
            SubscribeMessageLog::SEND_STATUS_FAILED => '发送失败',
        ];
        return $map[$status] ?? '未知';
    }
}
