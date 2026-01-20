<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单项模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;
use app\common\model\staff\Staff;
use app\common\model\service\ServicePackage;

/**
 * 订单项模型
 * Class OrderItem
 * @package app\common\model\order
 */
class OrderItem extends BaseModel
{
    protected $name = 'order_item';

    // 项状态
    const STATUS_PENDING = 0;       // 待服务
    const STATUS_IN_SERVICE = 1;    // 服务中
    const STATUS_COMPLETED = 2;     // 已完成
    const STATUS_CANCELLED = 3;     // 已取消

    /**
     * @notes 关联订单
     * @return \think\model\relation\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

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
        return $this->belongsTo(ServicePackage::class, 'package_id', 'id');
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getItemStatusDescAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待服务',
            self::STATUS_IN_SERVICE => '服务中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_CANCELLED => '已取消',
        ];
        return $map[$data['item_status']] ?? '未知';
    }

    /**
     * @notes 时间段描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getTimeSlotDescAttr($value, $data): string
    {
        $map = [
            0 => '全天',
            1 => '上午',
            2 => '下午',
            3 => '晚上',
        ];
        return $map[$data['time_slot']] ?? '未知';
    }
}
