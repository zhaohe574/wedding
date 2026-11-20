<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 活动票种模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\dynamic;

use app\common\model\BaseModel;

/**
 * 活动票种模型
 */
class ActivityTicket extends BaseModel
{
    protected $name = 'activity_ticket';

    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    public function getRemainingCountAttr($value, $data): int
    {
        // 常规库存：库存必须是 >=1 的正数，剩余 = max(库存 - 已售, 0)
        $stock = max((int)($data['stock'] ?? 0), 0);
        $sold = (int)($data['sold_count'] ?? 0);
        return max($stock - $sold, 0);
    }

    public function getPriceLabelAttr($value, $data): string
    {
        $price = round((float)($data['price'] ?? 0), 2);
        return $price <= 0 ? '免费' : '¥' . number_format($price, 2, '.', '');
    }
}
