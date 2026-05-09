<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 结算配置模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\financial;

use app\common\model\BaseModel;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffTeam;
use app\common\model\service\ServiceCategory;
use app\common\service\StaffTeamService;
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

    // 适用范围
    const SCOPE_DEFAULT = 1;    // 全员默认
    const SCOPE_TEAM = 2;       // 队伍
    const SCOPE_STAFF = 3;      // 人员

    // 结算模式
    const MODE_RATE = 1;        // 比例抽成
    const MODE_MONTHLY = 2;     // 包月金额

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
     * @notes 适用范围描述
     */
    public static function getScopeDesc($value = true)
    {
        $data = [
            self::SCOPE_DEFAULT => '全员默认',
            self::SCOPE_TEAM => '队伍',
            self::SCOPE_STAFF => '人员',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 结算模式描述
     */
    public static function getModeDesc($value = true)
    {
        $data = [
            self::MODE_RATE => '比例抽成',
            self::MODE_MONTHLY => '包月金额',
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
     * @notes 关联服务队伍
     */
    public function team()
    {
        return $this->belongsTo(StaffTeam::class, 'team_id', 'id')
            ->field('id, name, leader_staff_id, status');
    }

    /**
     * @notes 获取结算配置
     * @param int $staffId 服务人员ID
     * @param int $categoryId 服务分类ID
     * @return self|null
     */
    public static function getConfig(int $staffId, int $categoryId = 0): ?self
    {
        // 固定优先级：人员指定配置 > 队伍配置 > 全员默认配置
        if ($staffId > 0) {
            $config = self::where('scope_type', self::SCOPE_STAFF)
                ->where('staff_id', $staffId)
                ->where('status', self::STATUS_ENABLED)
                ->order('id', 'desc')
                ->find();
            if ($config) {
                return $config;
            }

            // 兼容未填 scope_type 的旧人员配置
            $config = self::where('staff_id', $staffId)
                ->where('scope_type', 0)
                ->where('status', self::STATUS_ENABLED)
                ->order('id', 'desc')
                ->find();
            if ($config) {
                return $config;
            }

            $team = StaffTeamService::getActiveTeamByStaffId($staffId);
            if (!empty($team['team_id'])) {
                $config = self::where('scope_type', self::SCOPE_TEAM)
                    ->where('team_id', (int)$team['team_id'])
                    ->where('status', self::STATUS_ENABLED)
                    ->order('id', 'desc')
                    ->find();
                if ($config) {
                    return $config;
                }
            }
        }

        $defaultConfig = self::where('scope_type', self::SCOPE_DEFAULT)
            ->where('is_default', 1)
            ->where('status', self::STATUS_ENABLED)
            ->order('id', 'desc')
            ->find();
        if ($defaultConfig) {
            return $defaultConfig;
        }

        return self::where('is_default', 1)
                ->where('status', self::STATUS_ENABLED)
                ->order('id', 'desc')
                ->find();
    }

    /**
     * @notes 计算结算金额
     */
    public static function calculateSettlement(float $orderAmount, int $staffId, int $categoryId = 0, string $serviceDate = ''): array
    {
        $config = self::getConfig($staffId, $categoryId);

        $orderAmount = round(max($orderAmount, 0), 2);
        $team = StaffTeamService::getActiveTeamByStaffId($staffId);
        $teamId = (int)($team['team_id'] ?? 0);
        $leaderStaffId = (int)($team['leader_staff_id'] ?? 0);

        $rule = self::normalizeRule($config);
        $settlementMode = (int)$rule['settlement_mode'];
        $companyRate = (float)$rule['company_rate'];
        $leaderRate = (float)$rule['leader_rate'];
        $monthlyFee = (float)$rule['monthly_fee'];

        $companyAmount = 0.0;
        $leaderAmount = 0.0;
        $monthlyFeeDeductAmount = 0.0;

        if ($settlementMode === self::MODE_MONTHLY) {
            $monthlyFeeDeductAmount = StaffCompanyFeeUsage::consumeMonthlyFee(
                $staffId,
                (int)$rule['config_id'],
                $serviceDate ?: date('Y-m-d'),
                $monthlyFee,
                $orderAmount
            );
            $companyAmount = $monthlyFeeDeductAmount;
        } else {
            $companyAmount = round($orderAmount * $companyRate / 100, 2);
            if ($leaderStaffId > 0 && $leaderStaffId !== $staffId && $leaderRate > 0) {
                $leaderAmount = round($orderAmount * $leaderRate / 100, 2);
            }
        }

        $settlementAmount = round(max($orderAmount - $companyAmount - $leaderAmount, 0), 2);
        $platformAmount = $companyAmount;

        if ($settlementMode === self::MODE_RATE && $settlementAmount < (float)$rule['min_amount']) {
            $settlementAmount = round((float)$rule['min_amount'], 2);
            $companyAmount = round(max($orderAmount - $settlementAmount - $leaderAmount, 0), 2);
            $platformAmount = $companyAmount;
        }
        
        return [
            'order_amount' => $orderAmount,
            'settlement_rate' => $settlementMode === self::MODE_RATE ? $rule['settlement_rate'] : 0,
            'settlement_amount' => $settlementAmount,
            'platform_amount' => $platformAmount,
            'config_id' => (int)$rule['config_id'],
            'scope_type' => (int)$rule['scope_type'],
            'rule_source' => (string)$rule['rule_source'],
            'settlement_mode' => $settlementMode,
            'settlement_mode_text' => self::getModeDesc($settlementMode),
            'team_id' => $teamId,
            'leader_staff_id' => $leaderStaffId,
            'company_rate' => $companyRate,
            'company_amount' => $companyAmount,
            'leader_rate' => $settlementMode === self::MODE_RATE ? $leaderRate : 0,
            'leader_amount' => $leaderAmount,
            'monthly_fee_amount' => $settlementMode === self::MODE_MONTHLY ? $monthlyFee : 0,
            'monthly_fee_deduct_amount' => $monthlyFeeDeductAmount,
        ];
    }

    /**
     * @notes 创建配置
     */
    public static function createConfig(array $data): self
    {
        $data = self::normalizeConfigPayload($data);
        $config = new self();
        $config->scope_type = $data['scope_type'];
        $config->staff_id = $data['staff_id'] ?? 0;
        $config->team_id = $data['team_id'] ?? 0;
        $config->category_id = $data['category_id'] ?? 0;
        $config->settlement_rate = $data['settlement_rate'];
        $config->settlement_mode = $data['settlement_mode'];
        $config->company_rate = $data['company_rate'];
        $config->leader_rate = $data['leader_rate'];
        $config->monthly_fee = $data['monthly_fee'];
        $config->min_amount = $data['min_amount'] ?? 0;
        $config->settle_cycle = $data['settle_cycle'] ?? self::CYCLE_MONTHLY;
        $config->settle_delay_days = $data['settle_delay_days'] ?? 7;
        $config->is_default = 0;
        $config->status = self::STATUS_ENABLED;
        $config->remark = $data['remark'] ?? '';
        $config->save();

        if ((int)$data['is_default'] === 1) {
            $config->setAsDefault();
        }

        return $config;
    }

    /**
     * @notes 保存配置字段
     */
    public function saveConfig(array $data): bool
    {
        $wasDefault = (int)($this->is_default ?? 0) === 1;
        $data = self::normalizeConfigPayload($data);
        if ($wasDefault && (int)$data['scope_type'] !== self::SCOPE_DEFAULT) {
            throw new \RuntimeException('默认配置必须为全员默认范围');
        }
        if ($wasDefault) {
            $data['is_default'] = 1;
        }

        $this->scope_type = $data['scope_type'];
        $this->staff_id = $data['staff_id'];
        $this->team_id = $data['team_id'];
        $this->category_id = $data['category_id'];
        $this->settlement_rate = $data['settlement_rate'];
        $this->settlement_mode = $data['settlement_mode'];
        $this->company_rate = $data['company_rate'];
        $this->leader_rate = $data['leader_rate'];
        $this->monthly_fee = $data['monthly_fee'];
        $this->min_amount = $data['min_amount'];
        $this->settle_cycle = $data['settle_cycle'];
        $this->settle_delay_days = $data['settle_delay_days'];
        $this->status = $data['status'];
        $this->remark = $data['remark'];

        if ((int)$data['is_default'] === 1) {
            return $this->setAsDefault();
        }

        $this->is_default = 0;

        return $this->save();
    }

    /**
     * @notes 归一化配置入参，保证比例与包月互斥
     */
    public static function normalizeConfigPayload(array $data): array
    {
        $scopeType = (int)($data['scope_type'] ?? self::SCOPE_DEFAULT);
        if (!in_array($scopeType, [self::SCOPE_DEFAULT, self::SCOPE_TEAM, self::SCOPE_STAFF], true)) {
            $scopeType = self::SCOPE_DEFAULT;
        }

        $settlementMode = (int)($data['settlement_mode'] ?? self::MODE_RATE);
        if (!in_array($settlementMode, [self::MODE_RATE, self::MODE_MONTHLY], true)) {
            $settlementMode = self::MODE_RATE;
        }

        $staffId = $scopeType === self::SCOPE_STAFF ? (int)($data['staff_id'] ?? 0) : 0;
        $teamId = $scopeType === self::SCOPE_TEAM ? (int)($data['team_id'] ?? 0) : 0;
        $isDefault = $scopeType === self::SCOPE_DEFAULT && (int)($data['is_default'] ?? 0) === 1 ? 1 : 0;

        $companyRate = round(max(min((float)($data['company_rate'] ?? 0), 100), 0), 2);
        $leaderRate = round(max(min((float)($data['leader_rate'] ?? 0), 100), 0), 2);
        $settlementRate = array_key_exists('settlement_rate', $data)
            ? round(max(min((float)$data['settlement_rate'], 100), 0), 2)
            : round(max(100 - $companyRate - $leaderRate, 0), 2);
        $monthlyFee = round(max((float)($data['monthly_fee'] ?? 0), 0), 2);

        if ($settlementMode === self::MODE_RATE) {
            if (!array_key_exists('company_rate', $data) && array_key_exists('settlement_rate', $data)) {
                $companyRate = round(max(100 - $settlementRate - $leaderRate, 0), 2);
            }
            $monthlyFee = 0.0;
        } else {
            $settlementRate = 0.0;
            $companyRate = 0.0;
            $leaderRate = 0.0;
        }

        return [
            'scope_type' => $scopeType,
            'staff_id' => $staffId,
            'team_id' => $teamId,
            'category_id' => 0,
            'settlement_mode' => $settlementMode,
            'settlement_rate' => $settlementRate,
            'company_rate' => $companyRate,
            'leader_rate' => $leaderRate,
            'monthly_fee' => $monthlyFee,
            'min_amount' => round(max((float)($data['min_amount'] ?? 0), 0), 2),
            'settle_cycle' => (int)($data['settle_cycle'] ?? self::CYCLE_MONTHLY),
            'settle_delay_days' => (int)($data['settle_delay_days'] ?? 7),
            'is_default' => $isDefault,
            'status' => (int)($data['status'] ?? self::STATUS_ENABLED),
            'remark' => (string)($data['remark'] ?? ''),
        ];
    }

    /**
     * @notes 归一化结算规则快照
     */
    protected static function normalizeRule(?self $config): array
    {
        if (!$config) {
            return [
                'config_id' => 0,
                'scope_type' => self::SCOPE_DEFAULT,
                'rule_source' => 'default',
                'settlement_mode' => self::MODE_RATE,
                'settlement_rate' => 70.0,
                'company_rate' => 30.0,
                'leader_rate' => 0.0,
                'monthly_fee' => 0.0,
                'min_amount' => 0.0,
            ];
        }

        $scopeType = (int)($config->scope_type ?? 0);
        if (!in_array($scopeType, [self::SCOPE_DEFAULT, self::SCOPE_TEAM, self::SCOPE_STAFF], true)) {
            $scopeType = (int)$config->staff_id > 0 ? self::SCOPE_STAFF : self::SCOPE_DEFAULT;
        }

        $settlementMode = (int)($config->settlement_mode ?? self::MODE_RATE);
        if (!in_array($settlementMode, [self::MODE_RATE, self::MODE_MONTHLY], true)) {
            $settlementMode = self::MODE_RATE;
        }

        $settlementRate = round(max(min((float)($config->settlement_rate ?? 70), 100), 0), 2);
        $companyRate = round(max(min((float)($config->company_rate ?? 0), 100), 0), 2);
        $leaderRate = round(max(min((float)($config->leader_rate ?? 0), 100), 0), 2);
        if ($settlementMode === self::MODE_RATE && $companyRate <= 0 && $settlementRate > 0) {
            $companyRate = round(max(100 - $settlementRate - $leaderRate, 0), 2);
        }

        if ($settlementMode === self::MODE_MONTHLY) {
            $settlementRate = 0.0;
            $companyRate = 0.0;
            $leaderRate = 0.0;
        }

        return [
            'config_id' => (int)$config->id,
            'scope_type' => $scopeType,
            'rule_source' => [
                self::SCOPE_STAFF => 'staff',
                self::SCOPE_TEAM => 'team',
                self::SCOPE_DEFAULT => 'default',
            ][$scopeType] ?? 'default',
            'settlement_mode' => $settlementMode,
            'settlement_rate' => $settlementRate,
            'company_rate' => $companyRate,
            'leader_rate' => $leaderRate,
            'monthly_fee' => round(max((float)($config->monthly_fee ?? 0), 0), 2),
            'min_amount' => round(max((float)($config->min_amount ?? 0), 0), 2),
        ];
    }

    /**
     * @notes 获取默认配置
     */
    public static function getDefaultConfig(): ?self
    {
        return self::where('is_default', 1)
            ->where('scope_type', self::SCOPE_DEFAULT)
            ->where('status', self::STATUS_ENABLED)
            ->find();
    }

    /**
     * @notes 设为默认配置
     */
    public function setAsDefault(): bool
    {
        if ((int)$this->scope_type !== self::SCOPE_DEFAULT) {
            throw new \RuntimeException('默认配置必须为全员默认范围');
        }

        // 取消其他默认配置
        self::where('is_default', 1)
            ->where('id', '<>', $this->id)
            ->update(['is_default' => 0]);
        
        $this->is_default = 1;
        return $this->save();
    }
}
