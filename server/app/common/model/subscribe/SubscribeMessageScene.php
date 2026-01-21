<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订阅消息场景配置模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\subscribe;

use app\common\model\BaseModel;

/**
 * 订阅消息场景配置模型
 * Class SubscribeMessageScene
 * @package app\common\model\subscribe
 */
class SubscribeMessageScene extends BaseModel
{
    protected $name = 'subscribe_message_scene';

    // 状态
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    /**
     * @notes 数据映射JSON自动转换
     * @param $value
     * @return array
     */
    public function getDataMappingAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @notes 数据映射存储自动转JSON
     * @param $value
     * @return string
     */
    public function setDataMappingAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * @notes 根据场景标识获取配置
     * @param string $scene
     * @return SubscribeMessageScene|null
     */
    public static function getByScene(string $scene): ?SubscribeMessageScene
    {
        return self::where('scene', $scene)
            ->where('status', self::STATUS_ENABLED)
            ->find();
    }

    /**
     * @notes 根据触发事件获取场景配置
     * @param string $triggerEvent
     * @return SubscribeMessageScene|null
     */
    public static function getByTriggerEvent(string $triggerEvent): ?SubscribeMessageScene
    {
        return self::where('trigger_event', $triggerEvent)
            ->where('status', self::STATUS_ENABLED)
            ->find();
    }

    /**
     * @notes 获取所有启用的场景配置
     * @return array
     */
    public static function getEnabledScenes(): array
    {
        return self::where('status', self::STATUS_ENABLED)
            ->order('sort', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 更新场景配置
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function updateScene(int $id, array $data): bool
    {
        $data['update_time'] = time();
        return self::where('id', $id)->update($data) > 0;
    }

    /**
     * @notes 切换状态
     * @param int $id
     * @return bool
     */
    public static function toggleStatus(int $id): bool
    {
        $scene = self::find($id);
        if (!$scene) {
            return false;
        }
        $newStatus = $scene->status == self::STATUS_ENABLED ? self::STATUS_DISABLED : self::STATUS_ENABLED;
        return self::where('id', $id)->update([
            'status' => $newStatus,
            'update_time' => time(),
        ]) > 0;
    }

    /**
     * @notes 绑定模板到场景
     * @param int $sceneId
     * @param string $templateId
     * @return bool
     */
    public static function bindTemplate(int $sceneId, string $templateId): bool
    {
        return self::where('id', $sceneId)->update([
            'template_id' => $templateId,
            'update_time' => time(),
        ]) > 0;
    }
}
