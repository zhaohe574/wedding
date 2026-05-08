<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

namespace app\common\model\staff;

use app\common\model\BaseModel;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\review\Review;
use app\common\model\service\ServiceCategory;
use app\common\model\service\StyleTag;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

/**
 * 工作人员模型
 * Class Staff
 * @package app\common\model\staff
 */
class Staff extends BaseModel
{
    use SoftDelete;

    protected $name = 'staff';
    protected $deleteTime = 'delete_time';

    // 状态
    const STATUS_DISABLE = 0;   // 禁用
    const STATUS_ENABLE = 1;    // 启用

    // 审核状态
    const AUDIT_PENDING = 0;    // 待审核
    const AUDIT_PASS = 1;       // 已通过
    const AUDIT_REJECT = 2;     // 已拒绝

    // 追加字段
    protected $append = [
        'category_name',
        'status_desc',
        'audit_status_desc',
        'tag_names',
    ];

    /**
     * @notes 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\app\common\model\user\User::class, 'user_id', 'id');
    }

    /**
     * @notes 关联服务分类
     * @return \think\model\relation\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id', 'id');
    }

    /**
     * @notes 关联作品
     * @return \think\model\relation\HasMany
     */
    public function works()
    {
        return $this->hasMany(StaffWork::class, 'staff_id', 'id');
    }

    /**
     * @notes 关联证书
     * @return \think\model\relation\HasMany
     */
    public function certificates()
    {
        return $this->hasMany(StaffCertificate::class, 'staff_id', 'id');
    }

    /**
     * @notes 关联标签
     * @return \think\model\relation\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(
            StyleTag::class,
            StaffTag::class,
            'tag_id',
            'staff_id'
        );
    }

    /**
     * @notes 头像获取器
     * @param $value
     * @return string
     */
    public function getAvatarAttr($value)
    {
        return trim($value) ? FileService::getFileUrl($value) : FileService::getFileUrl((string)(config('project.default_image.user_avatar') ?? ''));
    }

    /**
     * @notes 头像设置器
     * @param $value
     * @return string
     */
    public function setAvatarAttr($value)
    {
        return trim($value) ? FileService::setFileUrl($value) : '';
    }

    /**
     * @notes 企业微信成员ID获取器
     * @param $value
     * @return string
     */
    public function getWecomUseridAttr($value): string
    {
        return trim((string) $value);
    }

    /**
     * @notes 获取脱敏后的手机号（显式方法）
     * @return string
     */
    public function getMaskedMobile(): string
    {
        $mobile = $this->getData('mobile');
        if (empty($mobile)) {
            return '';
        }
        return substr_replace($mobile, '****', 3, 4);
    }

    /**
     * @notes 获取完整手机号（用于订单确认后显示）
     * @return string
     */
    public function getMobileFullAttr()
    {
        return $this->getData('mobile_full') ?: $this->getData('mobile');
    }

    /**
     * @notes 获取原始手机号（不经过脱敏）
     * @return string
     */
    public function getRawMobile(): string
    {
        return $this->getData('mobile') ?: '';
    }

    /**
     * @notes 分类名称获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getCategoryNameAttr($value, $data)
    {
        $category = ServiceCategory::find($data['category_id'] ?? 0);
        return $category ? $category->name : '';
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusDescAttr($value, $data)
    {
        return ($data['status'] ?? 0) ? '启用' : '禁用';
    }

    /**
     * @notes 审核状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getAuditStatusDescAttr($value, $data)
    {
        $statusMap = [
            self::AUDIT_PENDING => '待审核',
            self::AUDIT_PASS => '已通过',
            self::AUDIT_REJECT => '已拒绝',
        ];
        return $statusMap[$data['audit_status'] ?? 0] ?? '未知';
    }

    /**
     * @notes 标签名称获取器
     * @param $value
     * @param $data
     * @return array
     */
    public function getTagNamesAttr($value, $data)
    {
        $tagIds = StaffTag::where('staff_id', $data['id'] ?? 0)->column('tag_id');
        if (empty($tagIds)) {
            return [];
        }
        return StyleTag::whereIn('id', $tagIds)->column('name');
    }

    /**
     * @notes 标签ID获取器
     * @param $value
     * @param $data
     * @return array
     */
    public function getTagIdsAttr($value, $data)
    {
        return StaffTag::where('staff_id', $data['id'] ?? 0)->column('tag_id');
    }

    /**
     * @notes 获取状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            ['value' => self::STATUS_DISABLE, 'label' => '禁用'],
            ['value' => self::STATUS_ENABLE, 'label' => '启用'],
        ];
    }

    /**
     * @notes 获取审核状态选项
     * @return array
     */
    public static function getAuditStatusOptions(): array
    {
        return [
            ['value' => self::AUDIT_PENDING, 'label' => '待审核'],
            ['value' => self::AUDIT_PASS, 'label' => '已通过'],
            ['value' => self::AUDIT_REJECT, 'label' => '已拒绝'],
        ];
    }

    /**
     * @notes 生成工号
     * @return string
     */
    public static function generateSn(): string
    {
        $prefix = 'S';
        $date = date('Ymd');
        $baseSn = $prefix . $date;

        // 按当日工号前缀取当前最大号，避免依赖 create_time 的日期函数（该字段为时间戳）
        $lastStaff = self::withTrashed()
            ->where('sn', 'like', $baseSn . '%')
            ->field('sn')
            ->order('sn', 'desc')
            ->find();

        $num = 1;
        if ($lastStaff && preg_match('/^' . preg_quote($baseSn, '/') . '(\d{4})$/', (string) $lastStaff->sn, $matches)) {
            $num = intval($matches[1]) + 1;
        }

        $sn = $baseSn . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
        while (self::withTrashed()->where('sn', $sn)->find()) {
            $num++;
            $sn = $baseSn . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
        }

        return $sn;
    }

    /**
     * @notes 更新评分
     * @param int $staffId
     * @return void
     */
    public static function updateRating(int $staffId): void
    {
        self::refreshServiceStats($staffId);
    }

    /**
     * @notes 刷新人员服务场次与评分统计
     * @param int $staffId
     * @return array
     */
    public static function refreshServiceStats(int $staffId): array
    {
        if ($staffId <= 0) {
            return [
                'order_count' => 0,
                'review_count' => 0,
                'rating' => 0.0,
            ];
        }

        $stats = self::calculateServiceStats($staffId);
        self::where('id', $staffId)->update([
            'order_count' => $stats['order_count'],
            'review_count' => $stats['review_count'],
            'rating' => $stats['rating'],
            'update_time' => time(),
        ]);

        return $stats;
    }

    /**
     * @notes 批量刷新人员服务场次与评分统计
     * @param array $staffIds
     * @return int
     */
    public static function refreshServiceStatsBatch(array $staffIds): int
    {
        $staffIds = self::normalizeStaffIds($staffIds);
        if (empty($staffIds)) {
            return 0;
        }

        $statsMap = self::calculateServiceStatsBatch($staffIds);
        foreach ($staffIds as $staffId) {
            $stats = $statsMap[$staffId] ?? self::defaultServiceStats();
            self::where('id', $staffId)->update([
                'order_count' => $stats['order_count'],
                'review_count' => $stats['review_count'],
                'rating' => $stats['rating'],
                'update_time' => time(),
            ]);
        }
        return count($staffIds);
    }

    /**
     * @notes 根据订单ID刷新关联人员服务统计
     * @param int $orderId
     * @return int
     */
    public static function refreshServiceStatsByOrder(int $orderId): int
    {
        if ($orderId <= 0) {
            return 0;
        }

        $staffIds = OrderItem::where('order_id', $orderId)
            ->where('staff_id', '>', 0)
            ->column('staff_id');

        return self::refreshServiceStatsBatch($staffIds);
    }

    /**
     * @notes 刷新全部未删除人员服务场次与评分统计
     * @return int
     */
    public static function refreshAllServiceStats(): int
    {
        $staffIds = self::whereNull('delete_time')->column('id');
        return self::refreshServiceStatsBatch($staffIds);
    }

    /**
     * @notes 计算人员服务场次与近一年评价评分
     * @param int $staffId
     * @return array
     */
    public static function calculateServiceStats(int $staffId): array
    {
        $statsMap = self::calculateServiceStatsBatch([$staffId]);
        return $statsMap[$staffId] ?? self::defaultServiceStats();
    }

    /**
     * @notes 批量计算人员服务场次与近一年评价评分
     * @param array $staffIds
     * @return array
     */
    public static function calculateServiceStatsBatch(array $staffIds): array
    {
        $staffIds = self::normalizeStaffIds($staffIds);
        if (empty($staffIds)) {
            return [];
        }

        $statsMap = [];
        foreach ($staffIds as $staffId) {
            $statsMap[$staffId] = self::defaultServiceStats();
        }

        $orderRows = OrderItem::alias('oi')
            ->join('order o', 'o.id = oi.order_id')
            ->whereIn('oi.staff_id', $staffIds)
            ->where('oi.staff_id', '>', 0)
            ->where('oi.item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->whereIn('o.order_status', [Order::STATUS_COMPLETED, Order::STATUS_REVIEWED])
            ->where('o.pay_status', Order::PAY_STATUS_PAID)
            ->where('o.balance_paid', 1)
            ->whereNull('o.delete_time')
            ->field('oi.staff_id, COUNT(DISTINCT oi.order_id) AS order_count')
            ->group('oi.staff_id')
            ->select()
            ->toArray();

        foreach ($orderRows as $row) {
            $staffId = (int)($row['staff_id'] ?? 0);
            if ($staffId <= 0 || !isset($statsMap[$staffId])) {
                continue;
            }
            $statsMap[$staffId]['order_count'] = (int)($row['order_count'] ?? 0);
        }

        $reviewStartDate = date('Y-m-d', strtotime('-1 year'));
        $reviewRows = Review::whereIn('staff_id', $staffIds)
            ->where('status', Review::STATUS_APPROVED)
            ->where('is_show', 1)
            ->where('service_date', '>=', $reviewStartDate)
            ->whereNull('delete_time')
            ->field('staff_id, COUNT(*) AS review_count, AVG(score) AS rating')
            ->group('staff_id')
            ->select()
            ->toArray();

        foreach ($reviewRows as $row) {
            $staffId = (int)($row['staff_id'] ?? 0);
            if ($staffId <= 0 || !isset($statsMap[$staffId])) {
                continue;
            }

            $reviewCount = (int)($row['review_count'] ?? 0);
            $statsMap[$staffId]['review_count'] = $reviewCount;
            $statsMap[$staffId]['rating'] = $reviewCount > 0
                ? round((float)($row['rating'] ?? 0), 1)
                : 0.0;
        }

        return $statsMap;
    }

    /**
     * @notes 给人员列表注入实时服务场次与评分
     * @param array $rows
     * @param string $staffIdField
     * @return array
     */
    public static function injectServiceStats(array &$rows, string $staffIdField = 'id'): array
    {
        if (empty($rows)) {
            return $rows;
        }

        $staffIds = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $staffId = (int)($row[$staffIdField] ?? 0);
            if ($staffId > 0) {
                $staffIds[] = $staffId;
            }
        }

        $statsMap = self::calculateServiceStatsBatch($staffIds);
        foreach ($rows as &$row) {
            if (!is_array($row)) {
                continue;
            }

            $staffId = (int)($row[$staffIdField] ?? 0);
            $stats = $statsMap[$staffId] ?? self::defaultServiceStats();
            $row['order_count'] = $stats['order_count'];
            $row['review_count'] = $stats['review_count'];
            $row['rating'] = $stats['rating'];
        }
        unset($row);

        return $rows;
    }

    /**
     * @notes 给单个人员数据注入实时服务场次与评分
     * @param array $row
     * @param string $staffIdField
     * @return array
     */
    public static function injectServiceStatsToRow(array $row, string $staffIdField = 'id'): array
    {
        $rows = [$row];
        self::injectServiceStats($rows, $staffIdField);
        return $rows[0] ?? $row;
    }

    /**
     * @notes 获取默认服务统计值
     * @return array
     */
    protected static function defaultServiceStats(): array
    {
        return [
            'order_count' => 0,
            'review_count' => 0,
            'rating' => 0.0,
        ];
    }

    /**
     * @notes 规范化人员ID列表
     * @param array $staffIds
     * @return array
     */
    protected static function normalizeStaffIds(array $staffIds): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $staffIds))));
    }

    /**
     * @notes 增加浏览数
     * @param int $staffId
     * @return void
     */
    public static function incrementViewCount(int $staffId): void
    {
        self::where('id', $staffId)->inc('view_count')->update();
    }
}
