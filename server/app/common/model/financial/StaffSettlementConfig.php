<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 结算配置模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\financial;

use app\common\model\BaseModel;
use app\common\model\staff\Staff;
use app\common\model\service\ServiceCategory;
use think\model\concern\SoftDelete;

/**
 * 服务人员结算配置模型
 * Class StaffSettlementConfig
 * @package app\common\model\financial
 */
class StaffSettlementConfig extends BaseModel
{
    use SoftDelete;

    protected $name = 'staff_settlement_config';
    protected $deleteTime = 'delete_time';

    // 结算周期
    const CYCLE_MONTHLY = 1;    // 月结
    const CYCLE_WEEKLY = 2;     // 周结
    const CYCLE_SINGLE = 3;     // 单笔结

    // 状态
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_ENABLED = 1;   // 启用

    /**
     * @notes 结算周期描述
     */
    public static function getCycleDesc($value = true)
    {
        $data = [
            self::CYCLE_MONTHLY => '月结',
            self::CYCLE_WEEKLY => '周结',
            self::CYCLE_SINGLE => '单笔结',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 状态描述
     */
    public static function getStatusDesc($value = true)
    {
        $data = [
            self::STATUS_DISABLED => '禁用',
            self::STATUS_ENABLED => '启用',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 关联服务人员
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id')
            ->field('id, name, avatar');
    }

    /**
     * @notes 关联服务分类
     */
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id', 'id')
            ->field('id, name');
    }

    /**
     * @notes 获取结算配置
     * @param int $staffId 服务人员ID
     * @param int $categoryId 服务分类ID
     * @return self|null
     */
    public static function getConfig(int $staffId, int $categoryId = 0): ?self
    {
        // 优先级：人员+分类 > 人员 > 分类 > 默认
        
        // 1. 人员+分类的配置
        if ($staffId > 0 && $categoryId > 0) {
            $config = self::where('staff_id', $staffId)
                ->where('category_id', $categoryId)
                ->where('status', self::STATUS_ENABLED)
                ->find();
            if ($config) {
                return $config;
            }
        }
        
        // 2. 人员的配置
        if ($staffId > 0) {
            $config = self::where('staff_id', $staffId)
                ->where('category_id', 0)
                ->where('status', self::STATUS_ENABLED)
                ->find();
            if ($config) {
                return $config;
            }
        }
        
        // 3. 分类的配置
        if ($categoryId > 0) {
            $config = self::where('staff_id', 0)
                ->where('category_id', $categoryId)
                ->where('status', self::STATUS_ENABLED)
                ->find();
            if ($config) {
                return $config;
            }
        }
        
        // 4. 默认配置
        return self::where('is_default', 1)
            ->where('status', self::STATUS_ENABLED)
            ->find();
    }

    /**
     * @notes 计算结算金额
     */
    public static function calculateSettlement(float $orderAmount, int $staffId, int $categoryId = 0): array
    {
        $config = self::getConfig($staffId, $categoryId);
        
        if (!$config) {
            // 没有配置，使用默认70%
            $rate = 70;
            $minAmount = 0;
        } else {
            $rate = $config->settlement_rate;
            $minAmount = $config->min_amount;
        }
        
        $settlementAmount = round($orderAmount * $rate / 100, 2);
        $platformAmount = $orderAmount - $settlementAmount;
        
        // 检查最低结算金额
        if ($settlementAmount < $minAmount) {
            $settlementAmount = $minAmount;
            $platformAmount = $orderAmount - $settlementAmount;
        }
        
        return [
            'order_amount' => $orderAmount,
            'settlement_rate' => $rate,
            'settlement_amount' => $settlementAmount,
            'platform_amount' => $platformAmount,
            'config_id' => $config ? $config->id : 0,
        ];
    }

    /**
     * @notes 创建配置
     */
    public static function createConfig(array $data): self
    {
        $config = new self();
        $config->staff_id = $data['staff_id'] ?? 0;
        $config->category_id = $data['category_id'] ?? 0;
        $config->settlement_rate = $data['settlement_rate'];
        $config->min_amount = $data['min_amount'] ?? 0;
        $config->settle_cycle = $data['settle_cycle'] ?? self::CYCLE_MONTHLY;
        $config->settle_delay_days = $data['settle_delay_days'] ?? 7;
        $config->is_default = $data['is_default'] ?? 0;
        $config->status = self::STATUS_ENABLED;
        $config->remark = $data['remark'] ?? '';
        $config->save();
        return $config;
    }

    /**
     * @notes 获取默认配置
     */
    public static function getDefaultConfig(): ?self
    {
        return self::where('is_default', 1)
            ->where('status', self::STATUS_ENABLED)
            ->find();
    }

    /**
     * @notes 设为默认配置
     */
    public function setAsDefault(): bool
    {
        // 取消其他默认配置
        self::where('is_default', 1)
            ->where('id', '<>', $this->id)
            ->update(['is_default' => 0]);
        
        $this->is_default = 1;
        return $this->save();
    }
}
