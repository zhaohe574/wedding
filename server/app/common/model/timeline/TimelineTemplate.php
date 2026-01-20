<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 时间轴模板模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\timeline;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 时间轴模板模型
 * Class TimelineTemplate
 * @package app\common\model\timeline
 */
class TimelineTemplate extends BaseModel
{
    use SoftDelete;

    protected $name = 'timeline_template';
    protected $deleteTime = 'delete_time';

    // 服务类型
    const SERVICE_TYPE_ALL = 0;         // 通用
    const SERVICE_TYPE_PHOTO = 1;       // 摄影
    const SERVICE_TYPE_MAKEUP = 2;      // 化妆
    const SERVICE_TYPE_FULL = 3;        // 全套服务

    /**
     * @notes 服务类型选项
     * @return array
     */
    public static function getServiceTypeOptions(): array
    {
        return [
            self::SERVICE_TYPE_ALL => '通用',
            self::SERVICE_TYPE_PHOTO => '摄影',
            self::SERVICE_TYPE_MAKEUP => '化妆',
            self::SERVICE_TYPE_FULL => '全套服务',
        ];
    }

    /**
     * @notes 服务类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getServiceTypeDescAttr($value, $data): string
    {
        $options = self::getServiceTypeOptions();
        return $options[$data['service_type']] ?? '未知';
    }

    /**
     * @notes 任务列表获取器(JSON转数组)
     * @param $value
     * @return array
     */
    public function getTasksAttr($value): array
    {
        if (empty($value)) {
            return [];
        }
        return is_array($value) ? $value : json_decode($value, true) ?: [];
    }

    /**
     * @notes 任务列表修改器(数组转JSON)
     * @param $value
     * @return string
     */
    public function setTasksAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
    }

    /**
     * @notes 获取启用的模板列表
     * @param int $serviceType
     * @return array
     */
    public static function getEnabledTemplates(int $serviceType = 0): array
    {
        $query = self::where('is_enabled', 1);
        
        if ($serviceType > 0) {
            $query->whereIn('service_type', [0, $serviceType]);
        }
        
        return $query->order('is_default desc, sort desc, id asc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取默认模板
     * @param int $serviceType
     * @return array|null
     */
    public static function getDefaultTemplate(int $serviceType = 0): ?array
    {
        $query = self::where('is_enabled', 1)
            ->where('is_default', 1);
        
        if ($serviceType > 0) {
            $query->whereIn('service_type', [0, $serviceType]);
        }
        
        $template = $query->order('service_type desc')->find();
        
        return $template ? $template->toArray() : null;
    }

    /**
     * @notes 增加使用次数
     * @param int $templateId
     * @return bool
     */
    public static function incrementUseCount(int $templateId): bool
    {
        return self::where('id', $templateId)->inc('use_count')->update();
    }
}
