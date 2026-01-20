<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

namespace app\common\model\staff;

use app\common\model\BaseModel;

/**
 * 工作人员套餐关联模型
 * Class StaffPackage
 * @package app\common\model\staff
 */
class StaffPackage extends BaseModel
{
    protected $name = 'staff_package';

    /**
     * @notes 关联工作人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    /**
     * @notes 关联套餐
     * @return \think\model\relation\BelongsTo
     */
    public function package()
    {
        return $this->belongsTo(\app\common\model\service\ServicePackage::class, 'package_id', 'id');
    }

    /**
     * @notes 批量设置套餐
     * @param int $staffId
     * @param array $packages 格式: [['package_id' => 1, 'price' => 100.00, 'is_default' => 0], ...]
     * @return void
     */
    public static function setPackages(int $staffId, array $packages): void
    {
        // 删除原有套餐
        self::where('staff_id', $staffId)->delete();

        // 添加新套餐
        if (!empty($packages)) {
            $data = [];
            $time = time();
            foreach ($packages as $pkg) {
                $data[] = [
                    'staff_id' => $staffId,
                    'package_id' => $pkg['package_id'],
                    'price' => $pkg['price'] ?? 0,
                    'is_default' => $pkg['is_default'] ?? 0,
                    'create_time' => $time,
                ];
            }
            (new self())->saveAll($data);
        }
    }

    /**
     * @notes 获取工作人员的套餐列表
     * @param int $staffId
     * @return array
     */
    public static function getPackages(int $staffId): array
    {
        return self::where('staff_id', $staffId)
            ->with(['package'])
            ->select()
            ->toArray();
    }
}
