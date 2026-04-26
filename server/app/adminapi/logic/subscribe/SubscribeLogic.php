<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订阅消息管理逻辑层
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\subscribe;

use app\common\logic\BaseLogic;
use app\common\model\subscribe\SubscribeMessageTemplate;
use app\common\model\subscribe\SubscribeMessageScene;
use app\common\model\subscribe\SubscribeMessageLog;
use app\common\model\subscribe\UserSubscribe;
use app\common\service\SubscribeMessageService;

/**
 * 订阅消息管理逻辑层
 * Class SubscribeLogic
 * @package app\adminapi\logic\subscribe
 */
class SubscribeLogic extends BaseLogic
{
    // ==================== 模板管理 ====================

    /**
     * @notes 获取模板详情
     * @param int $id
     * @return array
     */
    public static function getTemplateDetail(int $id): array
    {
        $template = SubscribeMessageTemplate::find($id);
        if (!$template) {
            return [];
        }
        
        $data = $template->toArray();
        $data['scene_desc'] = SubscribeMessageTemplate::getSceneDesc($data['scene'] ?? '');
        $data['status_desc'] = $template->status_desc;
        $data['config_status'] = SubscribeMessageTemplate::getConfigStatus($data['template_id'] ?? '');
        $data['config_status_desc'] = SubscribeMessageTemplate::getConfigStatusDesc($data['template_id'] ?? '');
        
        // 获取订阅统计
        if (!empty($data['template_id'])) {
            $data['subscribe_stats'] = UserSubscribe::getTemplateStats($data['template_id']);
        }
        
        return $data;
    }

    /**
     * @notes 添加模板
     * @param array $params
     * @return bool
     */
    public static function addTemplate(array $params): bool
    {
        try {
            if (!SubscribeMessageTemplate::isTemplateScene((string) ($params['scene'] ?? ''))) {
                self::setError('当前场景不支持独立模板');
                return false;
            }

            // 检查模板ID是否重复
            if (SubscribeMessageTemplate::where('template_id', $params['template_id'])->find()) {
                self::setError('模板ID已存在');
                return false;
            }

            SubscribeMessageTemplate::addTemplate([
                'template_id' => $params['template_id'],
                'name' => $params['name'],
                'title' => $params['title'] ?? '',
                'scene' => $params['scene'],
                'content' => $params['content'] ?? [],
                'example' => $params['example'] ?? '',
                'keywords' => $params['keywords'] ?? '',
                'category_id' => $params['category_id'] ?? '',
                'status' => $params['status'] ?? 1,
                'sort' => $params['sort'] ?? 0,
                'remark' => $params['remark'] ?? '',
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑模板
     * @param array $params
     * @return bool
     */
    public static function editTemplate(array $params): bool
    {
        try {
            $templateId = (int) ($params['id'] ?? 0);
            $template = SubscribeMessageTemplate::find($templateId);
            if (!$template) {
                self::setError('模板不存在');
                return false;
            }

            if (!SubscribeMessageTemplate::isTemplateScene((string) ($params['scene'] ?? ''))) {
                self::setError('当前场景不支持独立模板');
                return false;
            }

            // 检查模板ID是否被其他记录使用
            $exists = SubscribeMessageTemplate::where('template_id', $params['template_id'])
                ->where('id', '<>', $templateId)
                ->find();
            if ($exists) {
                self::setError('模板ID已被使用');
                return false;
            }

            $updateData = [
                'template_id' => $params['template_id'],
                'name' => $params['name'],
                'title' => $params['title'] ?? '',
                'scene' => $params['scene'],
                'example' => $params['example'] ?? '',
                'keywords' => $params['keywords'] ?? '',
                'category_id' => $params['category_id'] ?? '',
                'status' => (int) ($params['status'] ?? 1),
                'sort' => (int) ($params['sort'] ?? 0),
                'remark' => $params['remark'] ?? '',
            ];

            if (array_key_exists('content', $params)) {
                $updateData['content'] = is_array($params['content'])
                    ? json_encode($params['content'], JSON_UNESCAPED_UNICODE)
                    : $params['content'];
            }

            SubscribeMessageTemplate::updateTemplate($templateId, $updateData);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除模板
     * @param int $id
     * @return bool
     */
    public static function deleteTemplate(int $id): bool
    {
        try {
            $template = SubscribeMessageTemplate::find($id);
            if (!$template) {
                self::setError('模板不存在');
                return false;
            }

            // 检查是否有场景关联
            $sceneCount = SubscribeMessageScene::where('template_id', $template->template_id)->count();
            if ($sceneCount > 0) {
                self::setError('模板已关联场景，请先解除关联');
                return false;
            }

            $template->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 切换模板状态
     * @param int $id
     * @return bool
     */
    public static function toggleTemplateStatus(int $id): bool
    {
        try {
            return SubscribeMessageTemplate::toggleStatus($id);
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取场景选项
     * @return array
     */
    public static function getSceneOptions(): array
    {
        return SubscribeMessageTemplate::getSceneList();
    }

    // ==================== 场景配置 ====================

    /**
     * @notes 获取场景详情
     * @param int $id
     * @return array
     */
    public static function getSceneDetail(int $id): array
    {
        $scene = SubscribeMessageScene::find($id);
        if (!$scene) {
            return [];
        }

        $data = $scene->toArray();
        $template = null;

        // 获取关联模板信息
        if (!empty($data['template_id'])) {
            $template = SubscribeMessageTemplate::where('template_id', $data['template_id'])->find();
            $data['template_name'] = $template ? $template->name : '模板已删除';
        }

        $data['available_params'] = SubscribeMessageTemplate::getSceneAvailableParams((string) ($data['scene'] ?? ''));
        $data['config_status'] = SubscribeMessageTemplate::getConfigStatus($data['template_id'] ?? '');
        $data['config_status_desc'] = SubscribeMessageTemplate::getConfigStatusDesc($data['template_id'] ?? '');

        return $data;
    }

    /**
     * @notes 编辑场景配置
     * @param array $params
     * @return bool
     */
    public static function editScene(array $params): bool
    {
        try {
            $sceneId = (int) ($params['id'] ?? 0);
            $scene = SubscribeMessageScene::find($sceneId);
            if (!$scene) {
                self::setError('场景配置不存在');
                return false;
            }

            $dataMapping = self::normalizeDataMapping($params['data_mapping'] ?? []);
            if ($dataMapping === null) {
                self::setError('数据映射必须是合法JSON对象');
                return false;
            }

            $updateData = [
                'name' => $params['name'] ?? $scene->name,
                'description' => $params['description'] ?? '',
                'template_id' => $params['template_id'] ?? '',
                'trigger_event' => $params['trigger_event'] ?? '',
                'data_mapping' => json_encode($dataMapping, JSON_UNESCAPED_UNICODE),
                'page_path' => $params['page_path'] ?? '',
                'is_auto' => (int) ($params['is_auto'] ?? 1),
                'delay_seconds' => (int) ($params['delay_seconds'] ?? 0),
                'status' => (int) ($params['status'] ?? 1),
                'sort' => (int) ($params['sort'] ?? 0),
            ];

            if ((int) $updateData['status'] === SubscribeMessageScene::STATUS_ENABLED
                && !self::validateSceneConfiguration((string) $scene->scene, $updateData, $dataMapping)
            ) {
                return false;
            }

            SubscribeMessageScene::updateScene($sceneId, $updateData);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 切换场景状态
     * @param int $id
     * @return bool
     */
    public static function toggleSceneStatus(int $id): bool
    {
        try {
            $scene = SubscribeMessageScene::find($id);
            if (!$scene) {
                self::setError('场景配置不存在');
                return false;
            }

            if ((int) $scene->status !== SubscribeMessageScene::STATUS_ENABLED) {
                $data = $scene->toArray();
                $mapping = self::normalizeDataMapping($data['data_mapping'] ?? []);
                if ($mapping === null || !self::validateSceneConfiguration((string) $scene->scene, $data, $mapping)) {
                    return false;
                }
            }

            return SubscribeMessageScene::toggleStatus($id);
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 绑定模板到场景
     * @param int $sceneId
     * @param string $templateId
     * @return bool
     */
    public static function bindTemplate(int $sceneId, string $templateId): bool
    {
        try {
            $scene = SubscribeMessageScene::find($sceneId);
            if (!$scene) {
                self::setError('场景不存在');
                return false;
            }

            // 检查模板是否存在
            $template = SubscribeMessageTemplate::where('template_id', $templateId)->find();
            if (!$template) {
                self::setError('模板不存在');
                return false;
            }

            if (!SubscribeMessageTemplate::canTemplateBindToScene((string) $template->scene, (string) $scene->scene)) {
                self::setError('模板场景与订阅场景不一致，不能绑定');
                return false;
            }

            if ((int) $scene->status === SubscribeMessageScene::STATUS_ENABLED) {
                $data = $scene->toArray();
                $data['template_id'] = $templateId;
                $mapping = self::normalizeDataMapping($data['data_mapping'] ?? []);
                if ($mapping === null || !self::validateSceneConfiguration((string) $scene->scene, $data, $mapping)) {
                    return false;
                }
            }

            return SubscribeMessageScene::bindTemplate($sceneId, $templateId);
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 归一化数据映射
     * @param mixed $mapping
     * @return array|null
     */
    protected static function normalizeDataMapping(mixed $mapping): ?array
    {
        if (is_array($mapping)) {
            return $mapping;
        }

        $mapping = trim((string) $mapping);
        if ($mapping === '') {
            return [];
        }

        $decoded = json_decode($mapping, true);
        return is_array($decoded) && self::isAssocArray($decoded) ? $decoded : null;
    }

    /**
     * @notes 判断是否为JSON对象结构
     * @param array $value
     * @return bool
     */
    protected static function isAssocArray(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) !== range(0, count($value) - 1);
    }

    /**
     * @notes 校验启用场景配置
     * @param string $sceneKey
     * @param array $data
     * @param array $dataMapping
     * @return bool
     */
    protected static function validateSceneConfiguration(string $sceneKey, array $data, array $dataMapping): bool
    {
        $templateId = trim((string) ($data['template_id'] ?? ''));
        if (!SubscribeMessageTemplate::isUsableTemplateId($templateId)) {
            self::setError('启用场景必须绑定真实微信模板ID');
            return false;
        }

        $template = SubscribeMessageTemplate::where('template_id', $templateId)->find();
        if (!$template || (int) $template->status !== SubscribeMessageTemplate::STATUS_ENABLED) {
            self::setError('启用场景绑定的模板不存在或未启用');
            return false;
        }

        if (!SubscribeMessageTemplate::canTemplateBindToScene((string) $template->scene, $sceneKey)) {
            self::setError('模板场景与订阅场景不一致，不能启用');
            return false;
        }

        $templateContent = $template->content;
        if (empty($templateContent)) {
            self::setError('启用场景的模板内容不能为空');
            return false;
        }

        $availableParams = array_column(SubscribeMessageTemplate::getSceneAvailableParams($sceneKey), 'key');
        foreach (array_keys($templateContent) as $keyword) {
            if (empty($dataMapping[$keyword])) {
                self::setError('数据映射缺少模板字段：' . $keyword);
                return false;
            }

            if (!in_array($dataMapping[$keyword], $availableParams, true)) {
                self::setError('数据映射字段不可用：' . $keyword . ' => ' . $dataMapping[$keyword]);
                return false;
            }
        }

        $pagePath = trim((string) ($data['page_path'] ?? ''));
        if ($pagePath === '' || str_contains($pagePath, '..') || preg_match('#^(?:https?:)?//#i', $pagePath)) {
            self::setError('小程序页面路径无效');
            return false;
        }

        if (!preg_match('#^[A-Za-z0-9_\\-/\\?=&.%]+$#', $pagePath)) {
            self::setError('小程序页面路径格式非法');
            return false;
        }

        return true;
    }

    // ==================== 发送记录 ====================

    /**
     * @notes 获取发送记录详情
     * @param int $id
     * @return array
     */
    public static function getLogDetail(int $id): array
    {
        $log = SubscribeMessageLog::find($id);
        if (!$log) {
            return [];
        }

        $data = $log->toArray();
        $data['send_status_desc'] = $log->send_status_desc;
        $data['scene_desc'] = SubscribeMessageTemplate::getSceneDesc($data['scene'] ?? '');
        $data['create_time'] = !empty($data['create_time']) ? date('Y-m-d H:i:s', (int) $data['create_time']) : '';
        $data['send_time'] = !empty($data['send_time']) ? date('Y-m-d H:i:s', (int) $data['send_time']) : '';
        $data['planned_send_time'] = !empty($data['planned_send_time']) ? date('Y-m-d H:i:s', (int) $data['planned_send_time']) : '';

        return $data;
    }

    /**
     * @notes 重试发送
     * @param int $id
     * @return bool
     */
    public static function retryLog(int $id): bool
    {
        try {
            $log = SubscribeMessageLog::find($id);
            if (!$log) {
                self::setError('记录不存在');
                return false;
            }

            if ($log->send_status != SubscribeMessageLog::SEND_STATUS_FAILED) {
                self::setError('只能重试发送失败的记录');
                return false;
            }

            SubscribeMessageLog::markForRetry($id);
            $result = SubscribeMessageService::dispatchLog($id, true);
            if (!$result['success']) {
                self::setError($result['msg'] ?? '重试失败');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    // ==================== 统计数据 ====================

    /**
     * @notes 获取发送统计
     * @param array $params
     * @return array
     */
    public static function getStatistics(array $params = []): array
    {
        $startDate = $params['start_date'] ?? '';
        $endDate = $params['end_date'] ?? '';

        $stats = SubscribeMessageLog::getSendStatistics($startDate, $endDate);

        // 额外统计
        $stats['template_count'] = SubscribeMessageTemplate::where('status', 1)
            ->whereIn('scene', SubscribeMessageTemplate::getTemplateSceneValues())
            ->count();
        $stats['scene_count'] = SubscribeMessageScene::where('status', 1)
            ->whereIn('scene', SubscribeMessageTemplate::getActiveSceneValues())
            ->count();
        $stats['user_subscribe_count'] = UserSubscribe::whereIn('status', [1, 2])->count();

        return $stats;
    }

    /**
     * @notes 获取发送趋势
     * @param int $days
     * @return array
     */
    public static function getTrend(int $days = 7): array
    {
        return SubscribeMessageLog::getSendTrend($days);
    }

    /**
     * @notes 获取场景统计
     * @param array $params
     * @return array
     */
    public static function getSceneStatistics(array $params = []): array
    {
        $startDate = $params['start_date'] ?? '';
        $endDate = $params['end_date'] ?? '';

        $stats = SubscribeMessageLog::getSceneStatistics($startDate, $endDate);

        // 补充场景名称
        foreach ($stats as &$item) {
            $item['scene_name'] = SubscribeMessageTemplate::getSceneDesc($item['scene']);
        }

        return $stats;
    }

    // ==================== 测试发送 ====================

    /**
     * @notes 测试发送
     * @param array $params
     * @return array
     */
    public static function testSend(array $params): array
    {
        return SubscribeMessageService::send(
            (int)$params['user_id'],
            $params['scene'],
            $params['data'] ?? [],
            'test',
            0,
            $params['page'] ?? '',
            [SubscribeMessageService::OPTION_FORCE_DISPATCH => true]
        );
    }
}
