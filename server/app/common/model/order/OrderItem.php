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

    // 追加属性到数组
    protected $append = ['item_status_desc', 'item_type_desc'];

    // 项状态
    const STATUS_PENDING = 0;       // 待服务
    const STATUS_IN_SERVICE = 1;    // 服务中
    const STATUS_COMPLETED = 2;     // 已完成
    const STATUS_CANCELLED = 3;     // 已取消

    // 订单项类型
    const TYPE_SERVICE = 1;         // 主服务
    const TYPE_CUSTOM_OPTION = 2;   // 自定义附加项
    const TYPE_RELATED_STAFF = 3;   // 关联服务人员

    /**
     * @notes 生成套餐说明快照
     * @param int $packageId
     * @param string $packageDescription
     * @return string
     */
    public static function resolvePackageDescription(int $packageId = 0, string $packageDescription = ''): string
    {
        $description = trim($packageDescription);
        if ($description === '' && $packageId > 0) {
            $package = ServicePackage::field('description')->find($packageId);
            $description = trim((string)($package->description ?? ''));
        }

        if ($description === '') {
            return '';
        }

        return function_exists('mb_substr')
            ? mb_substr($description, 0, 500)
            : substr($description, 0, 500);
    }

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
     * @notes 关联生效中的附加服务快照
     * @return \think\model\relation\HasMany
     */
    public function addons()
    {
        return $this->hasMany(OrderItemAddon::class, 'order_item_id', 'id')
            ->where('status', OrderItemAddon::STATUS_ACTIVE)
            ->order('id', 'asc');
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getItemStatusDescAttr($value, $data): string
    {
        if (!isset($data['item_status'])) {
            return '未知';
        }

        $map = [
            self::STATUS_PENDING => '待服务',
            self::STATUS_IN_SERVICE => '服务中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_CANCELLED => '已取消',
        ];
        return $map[$data['item_status']] ?? '未知';
    }

    /**
     * @notes 订单项类型描述
     * @param $value
     * @param $data
     * @return string
     */
    public function getItemTypeDescAttr($value, $data): string
    {
        $map = [
            self::TYPE_SERVICE => '主服务',
            self::TYPE_CUSTOM_OPTION => '预约附加项',
            self::TYPE_RELATED_STAFF => '关联服务人员',
        ];

        return $map[(int)($data['item_type'] ?? self::TYPE_SERVICE)] ?? '主服务';
    }

    /**
     * @notes item_meta 获取器
     * @param mixed $value
     * @return array
     */
    public function getItemMetaAttr($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return $value ? (json_decode((string)$value, true) ?: []) : [];
    }

    /**
     * @notes item_meta 设置器
     * @param mixed $value
     * @return string
     */
    public function setItemMetaAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
    }

    /**
     * @notes 是否为主服务项
     * @param array $item
     * @return bool
     */
    public static function isServiceItem(array $item): bool
    {
        return (int)($item['item_type'] ?? self::TYPE_SERVICE) === self::TYPE_SERVICE;
    }
}
