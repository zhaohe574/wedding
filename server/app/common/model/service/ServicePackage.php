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

    // 套餐类型常量
    const TYPE_GLOBAL = 1;      // 全局套餐
    const TYPE_STAFF_ONLY = 2;  // 人员专属套餐

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
     * @notes 时段价格获取器(JSON转数组)
     * @param $value
     * @return array
     */
    public function getSlotPricesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @notes 时段价格设置器(数组转JSON)
     * @param $value
     * @return string
     */
    public function setSlotPricesAttr($value)
    {
        return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
    }

    /**
     * @notes 获取指定时段的价格
     * @param string $startTime 开始时间 HH:mm
     * @param string $endTime 结束时间 HH:mm
     * @return float|null 匹配的时段价格，未找到返回null
     */
    public function getSlotPrice(string $startTime, string $endTime): ?float
    {
        $slotPrices = $this->slot_prices ?? [];
        foreach ($slotPrices as $slot) {
            if (isset($slot['start_time']) && isset($slot['end_time']) &&
                $slot['start_time'] === $startTime && $slot['end_time'] === $endTime) {
                return isset($slot['price']) ? (float)$slot['price'] : null;
            }
        }
        return null;
    }

    /**
     * @notes 计算最终价格（考虑时段）
     * @param string $startTime 开始时间（可选）
     * @param string $endTime 结束时间（可选）
     * @return float
     */
    public function calculatePrice(string $startTime = '', string $endTime = ''): float
    {
        // 如果指定了时段，优先使用时段价格
        if ($startTime && $endTime) {
            $slotPrice = $this->getSlotPrice($startTime, $endTime);
            if ($slotPrice !== null) {
                return $slotPrice;
            }
        }
        // 返回默认价格
        return (float)$this->price;
    }

    /**
     * @notes 检查是否为人员专属套餐
     * @return bool
     */
    public function isStaffOnly(): bool
    {
        return $this->package_type == self::TYPE_STAFF_ONLY && $this->staff_id > 0;
    }

    /**
     * @notes 检查是否为全局套餐
     * @return bool
     */
    public function isGlobal(): bool
    {
        return $this->package_type == self::TYPE_GLOBAL || $this->staff_id == 0;
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
     * @notes 套餐类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPackageTypeDescAttr($value, $data)
    {
        $typeMap = [
            self::TYPE_GLOBAL => '全局套餐',
            self::TYPE_STAFF_ONLY => '人员专属',
        ];
        return $typeMap[$data['package_type'] ?? self::TYPE_GLOBAL] ?? '未知';
    }

    /**
     * @notes 获取所属工作人员名称
     * @param $value
     * @param $data
     * @return string
     */
    public function getStaffNameAttr($value, $data)
    {
        if (empty($data['staff_id'])) {
            return '';
        }
        $staff = Staff::find($data['staff_id']);
        return $staff ? $staff->name : '';
    }
}
