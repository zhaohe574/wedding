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
        $query = SubscribeMessageTemplate::where('status', SubscribeMessageTemplate::STATUS_ENABLED)
            ->whereIn('scene', SubscribeMessageTemplate::getTemplateSceneValues());
        
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
            $templateId = (string) ($params['template_id'] ?? '');
            $scene = (string) ($params['scene'] ?? '');
            if (!self::isRecordableTemplate($templateId, $scene)) {
                return false;
            }

            $accepted = ($params['result'] ?? '') === 'accept';
            UserSubscribe::recordSubscribe(
                (int)$params['user_id'],
                $templateId,
                $scene,
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
     * @return array
     */
    public static function batchRecordSubscribe(array $params): array
    {
        $subscribeResults = $params['results'] ?? [];
        $scene = $params['scene'] ?? '';
        $count = 0;
        $failed = 0;
        $errors = [];

        foreach ($subscribeResults as $templateId => $result) {
            $templateId = (string) $templateId;
            $result = (string) $result;
            if (!in_array($result, ['accept', 'reject'], true)) {
                $failed++;
                $errors[] = $templateId . ': 订阅结果值无效';
                continue;
            }

            if (!self::isRecordableTemplate($templateId, (string) $scene)) {
                $failed++;
                $errors[] = $templateId . ': ' . self::getError();
                continue;
            }

            UserSubscribe::recordSubscribe((int) $params['user_id'], $templateId, (string) $scene, $result === 'accept');
            $count++;
        }

        return [
            'count' => $count,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * @notes 校验客户端上报的模板是否来自已启用场景
     * @param string $templateId
     * @param string $scene
     * @return bool
     */
    protected static function isRecordableTemplate(string $templateId, string $scene = ''): bool
    {
        if (!SubscribeMessageTemplate::isUsableTemplateId($templateId)) {
            self::setError('模板ID未配置或无效');
            return false;
        }

        $template = SubscribeMessageTemplate::where('template_id', $templateId)
            ->where('status', SubscribeMessageTemplate::STATUS_ENABLED)
            ->find();
        if (!$template) {
            self::setError('模板不存在或未启用');
            return false;
        }

        $allowedScenes = self::getRecordSceneScope($scene);
        if ($scene !== '' && empty($allowedScenes)) {
            self::setError('订阅场景无效');
            return false;
        }

        $sceneQuery = SubscribeMessageScene::where('template_id', $templateId)
            ->where('status', SubscribeMessageScene::STATUS_ENABLED);
        if (!empty($allowedScenes)) {
            $sceneQuery->whereIn('scene', $allowedScenes);
        }
        $sceneConfigs = $sceneQuery->select();
        if (count($sceneConfigs) === 0) {
            self::setError('模板未绑定启用场景');
            return false;
        }

        foreach ($sceneConfigs as $sceneConfig) {
            if (SubscribeMessageTemplate::canTemplateBindToScene((string) $template->scene, (string) $sceneConfig->scene)) {
                return true;
            }
        }

        self::setError('模板场景与订阅场景不一致');
        return false;
    }

    /**
     * @notes 获取前端上报场景的允许范围
     * @param string $scene
     * @return array
     */
    protected static function getRecordSceneScope(string $scene): array
    {
        if ($scene === '') {
            return [];
        }

        if (SubscribeMessageTemplate::isActiveScene($scene)) {
            return [$scene];
        }

        $groups = [
            'order' => [
                SubscribeMessageTemplate::SCENE_ORDER_CONFIRM,
                SubscribeMessageTemplate::SCENE_SCHEDULE_REMIND,
            ],
            'aftersale' => [
                SubscribeMessageTemplate::SCENE_REFUND_RESULT,
                SubscribeMessageTemplate::SCENE_TICKET_UPDATE,
            ],
            'waitlist' => [
                SubscribeMessageTemplate::SCENE_WAITLIST_RELEASE,
                SubscribeMessageTemplate::SCENE_WAITLIST_EXPIRED,
            ],
        ];

        return $groups[$scene] ?? [];
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
            $item['reserved_count'] = (int) ($item['reserved_count'] ?? 0);
            $item['can_send'] = $item['status'] == UserSubscribe::STATUS_PERMANENT
                || (int) $item['accept_count'] > $item['reserved_count'];
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
        $templateIds = array_values(array_filter(
            array_unique(array_column($scenes, 'template_id')),
            static fn ($templateId) => SubscribeMessageTemplate::isUsableTemplateId((string) $templateId)
        ));
        $templates = [];
        if (!empty($templateIds)) {
            $templates = SubscribeMessageTemplate::whereIn('template_id', $templateIds)
                ->where('status', 1)
                ->column('name, template_id, scene', 'template_id');
        }

        $result = [];
        foreach ($scenes as $scene) {
            if (!SubscribeMessageTemplate::isActiveScene((string) ($scene['scene'] ?? ''))) {
                continue;
            }

            $configStatus = SubscribeMessageTemplate::getConfigStatus($scene['template_id'] ?? '');
            if (
                $configStatus !== 'configured'
                || empty($scene['template_id'])
                || !isset($templates[$scene['template_id']])
            ) {
                continue;
            }

            $result[] = [
                'scene' => $scene['scene'],
                'name' => $scene['name'],
                'description' => $scene['description'],
                'template_id' => $scene['template_id'],
                'template_name' => $templates[$scene['template_id']]['name'] ?? '',
                'config_status' => $configStatus,
                'config_status_desc' => SubscribeMessageTemplate::getConfigStatusDesc($scene['template_id']),
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
        if (!SubscribeMessageTemplate::isActiveScene($scene)) {
            return [
                'need_subscribe' => false,
                'template_id' => '',
                'config_status' => 'offline',
                'config_status_desc' => '场景已下线',
                'reason' => '场景已下线',
            ];
        }

        // 获取场景配置
        $sceneConfig = SubscribeMessageScene::getByScene($scene);
        $templateId = $sceneConfig ? (string) $sceneConfig->template_id : '';
        if (!$sceneConfig || !SubscribeMessageTemplate::isUsableTemplateId($templateId)) {
            return [
                'need_subscribe' => false,
                'template_id' => '',
                'config_status' => SubscribeMessageTemplate::getConfigStatus($templateId),
                'config_status_desc' => SubscribeMessageTemplate::getConfigStatusDesc($templateId),
                'reason' => '场景未配置有效模板',
            ];
        }

        // 检查模板是否启用
        $template = SubscribeMessageTemplate::where('template_id', $templateId)
            ->where('status', 1)
            ->find();
        if (!$template) {
            return [
                'need_subscribe' => false,
                'template_id' => '',
                'config_status' => SubscribeMessageTemplate::getConfigStatus($templateId),
                'config_status_desc' => SubscribeMessageTemplate::getConfigStatusDesc($templateId),
                'reason' => '模板未启用',
            ];
        }

        // 检查用户订阅状态
        $hasSubscription = UserSubscribe::hasSubscription($userId, $templateId);

        return [
            'need_subscribe' => !$hasSubscription,
            'template_id' => $templateId,
            'template_name' => $template->name,
            'scene_name' => $sceneConfig->name,
            'has_subscription' => $hasSubscription,
            'config_status' => SubscribeMessageTemplate::getConfigStatus($templateId),
            'config_status_desc' => SubscribeMessageTemplate::getConfigStatusDesc($templateId),
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
            SubscribeMessageLog::SEND_STATUS_SENDING => '发送中',
        ];
        return $map[$status] ?? '未知';
    }
}
