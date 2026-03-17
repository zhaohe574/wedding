<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单变更附加服务明细模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;

/**
 * 订单变更附加服务明细模型
 * Class OrderChangeAddon
 * @package app\common\model\order
 */
class OrderChangeAddon extends BaseModel
{
    protected $name = 'order_change_addon';

    /**
     * @notes 关联变更单
     * @return \think\model\relation\BelongsTo
     */
    public function change()
    {
        return $this->belongsTo(OrderChange::class, 'change_id', 'id');
    }

    /**
     * @notes 关联订单附加服务快照
     * @return \think\model\relation\BelongsTo
     */
    public function orderItemAddon()
    {
        return $this->belongsTo(OrderItemAddon::class, 'order_item_addon_id', 'id');
    }
}
