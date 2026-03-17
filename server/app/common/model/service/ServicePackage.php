<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

namespace app\common\model\service;

use app\common\model\BaseModel;
use app\common\model\staff\Staff;
use think\model\concern\SoftDelete;

/**
 * 服务套餐模型
 * Class ServicePackage
 * @package app\common\model\service
 */
class ServicePackage extends BaseModel
{
    use SoftDelete;

    protected $name = 'service_package';
    protected $deleteTime = 'delete_time';

    /**
     * @notes 获取所属分类
     * @return \think\model\relation\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id', 'id');
    }

    /**
     * @notes 关联工作人员（人员专属套餐）
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    /**
     * @notes 套餐内容获取器(JSON转数组)
     * @param $value
     * @return array
     */
    public function getContentAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @notes 套餐内容设置器(数组转JSON)
     * @param $value
     * @return false|string
     */
    public function setContentAttr($value)
    {
        return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
    }

    /**
     * @notes 统一返回全天预约配置
     * @param int $packageId
     * @param int $staffId
     * @return array|null
     */
    public static function resolveBookingConfig(int $packageId, int $staffId = 0): ?array
    {
        $package = self::find($packageId);
        if (!$package) {
            return null;
        }

        if ((int)$package->staff_id <= 0) {
            return null;
        }

        if ($staffId > 0 && (int)$package->staff_id !== $staffId) {
            return null;
        }

        return [];
    }

    /**
     * @notes 新口径始终按全天处理，旧调用透传时也直接放行
     * @param int $packageId
     * @param int $staffId
     * @param array $timeSlots
     * @return array [bool, string, array|null]
     */
    public static function validateTimeSlots(int $packageId, int $staffId, array $timeSlots): array
    {
        $config = self::resolveBookingConfig($packageId, $staffId);
        if ($config === null) {
            return [false, '套餐不存在', null];
        }

        return [true, '', $config];
    }

    /**
     * @notes 归一化时间段数组
     * @param mixed $timeSlots
     * @return array
     */
    protected static function normalizeTimeSlots($timeSlots): array
    {
        if (!is_array($timeSlots)) {
            return [];
        }

        $normalized = array_map('intval', $timeSlots);
        return array_values(array_unique($normalized));
    }

    /**
     * @notes 分场次价格已下线，固定返回 null
     * @param string $startTime 开始时间 HH:mm
     * @param string $endTime 结束时间 HH:mm
     * @return float|null 匹配的时段价格，未找到返回null
     */
    public function getSlotPrice(string $startTime, string $endTime): ?float
    {
        return null;
    }

    /**
     * @notes 场次价格已下线，固定返回 null
     * @param int $timeSlot
     * @return float|null
     */
    public function getSlotPriceByTimeSlot(int $timeSlot): ?float
    {
        return null;
    }

    /**
     * @notes 获取指定场次的价格（未设置则返回默认价）
     * @param int $timeSlot
     * @return float
     */
    public function calculatePriceByTimeSlot(int $timeSlot = 0): float
    {
        return round((float)$this->price, 2);
    }

    /**
     * @notes 计算最终价格（考虑时段）
     * @param string $startTime 开始时间（可选）
     * @param string $endTime 结束时间（可选）
     * @return float
     */
    public function calculatePrice(string $startTime = '', string $endTime = ''): float
    {
        return round((float)$this->price, 2);
    }

    /**
     * @notes 检查是否为人员专属套餐
     * @return bool
     */
    public function isStaffOnly(): bool
    {
        return (int)$this->staff_id > 0;
    }

    /**
     * @notes 检查是否为全局套餐
     * @return bool
     */
    public function isGlobal(): bool
    {
        return (int)$this->staff_id <= 0;
    }

    /**
     * @notes 分类名称获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getCategoryNameAttr($value, $data)
    {
        $category = ServiceCategory::find($data['category_id']);
        return $category ? $category->name : '';
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getIsShowDescAttr($value, $data)
    {
        return $data['is_show'] ? '上架' : '下架';
    }

    /**
     * @notes 推荐描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getIsRecommendDescAttr($value, $data)
    {
        return $data['is_recommend'] ? '是' : '否';
    }

    /**
     * @notes 服务时长格式化获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getDurationDescAttr($value, $data)
    {
        return $data['duration'] . '小时';
    }

    /**
     * @notes 获取所属工作人员名称
     * @param $value
     * @param $data
     * @return string
     */
    public function getStaffNameAttr($value, $data)
    {
        if (empty($data['staff_id']) || (int)$data['staff_id'] <= 0) {
            return '';
        }
        try {
            $staff = Staff::find($data['staff_id']);
            return $staff ? $staff->name : '';
        } catch (\Exception $e) {
            return '';
        }
    }
}
