<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 投诉模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\aftersale;

use app\common\model\BaseModel;
use app\common\model\order\Order;
use app\common\model\staff\Staff;
use app\common\model\user\User;
use app\common\service\FileService;
use think\facade\Db;

/**
 * 投诉模型
 * Class Complaint
 * @package app\common\model\aftersale
 */
class Complaint extends BaseModel
{
    protected $name = 'complaint';

    // 投诉类型
    const TYPE_SERVICE = 1;     // 服务态度
    const TYPE_ABILITY = 2;     // 履约偏差
    const TYPE_PUNCTUAL = 3;    // 沟通问题
    const TYPE_VIOLATION = 4;   // 执行落差
    const TYPE_OTHER = 5;       // 其他

    // 投诉等级
    const LEVEL_NORMAL = 1;     // 一般
    const LEVEL_SERIOUS = 2;    // 严重
    const LEVEL_URGENT = 3;     // 紧急

    // 投诉状态
    const STATUS_PENDING = 0;       // 待处理
    const STATUS_PROCESSING = 1;    // 处理中
    const STATUS_HANDLED = 2;       // 已处理
    const STATUS_APPEALED = 3;      // 已申诉
    const STATUS_CLOSED = 4;        // 已关闭

    // 处理动作
    const ACTION_NONE = 0;      // 无
    const ACTION_WARNING = 1;   // 警告
    const ACTION_DEDUCT = 2;    // 扣款
    const ACTION_DISABLE = 3;   // 禁用
    const ACTION_OTHER = 4;     // 其他

    /**
     * @notes 关联订单
     * @return \think\model\relation\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * @notes 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->field('id,nickname,avatar,mobile');
    }

    /**
     * @notes 关联被投诉人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id')
            ->field('id,name,avatar,mobile');
    }

    /**
     * @notes 投诉类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getTypeDescAttr($value, $data): string
    {
        $map = [
            self::TYPE_SERVICE => '服务态度',
            self::TYPE_ABILITY => '履约偏差',
            self::TYPE_PUNCTUAL => '沟通问题',
            self::TYPE_VIOLATION => '执行落差',
            self::TYPE_OTHER => '其他',
        ];
        return $map[$data['type']] ?? '未知';
    }

    /**
     * @notes 投诉等级描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getLevelDescAttr($value, $data): string
    {
        $map = [
            self::LEVEL_NORMAL => '一般',
            self::LEVEL_SERIOUS => '严重',
            self::LEVEL_URGENT => '紧急',
        ];
        return $map[$data['level']] ?? '未知';
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusDescAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待处理',
            self::STATUS_PROCESSING => '处理中',
            self::STATUS_HANDLED => '已处理',
            self::STATUS_APPEALED => '已申诉',
            self::STATUS_CLOSED => '已关闭',
        ];
        return $map[$data['status']] ?? '未知';
    }

    /**
     * @notes 图片获取器
     * @param $value
     * @return array
     */
    public function getImagesAttr($value): array
    {
        return self::restoreMediaList($value);
    }

    /**
     * @notes 图片设置器
     * @param $value
     * @return string
     */
    public function setImagesAttr($value): string
    {
        return json_encode(self::sanitizeMediaList($value, 9), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @notes 视频获取器
     * @param $value
     * @return array
     */
    public function getVideosAttr($value): array
    {
        return self::restoreMediaList($value);
    }

    /**
     * @notes 视频设置器
     * @param $value
     * @return string
     */
    public function setVideosAttr($value): string
    {
        return json_encode(self::sanitizeMediaList($value, 3), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @notes 生成投诉编号
     * @return string
     */
    public static function generateComplaintSn(): string
    {
        return 'CP' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @notes 提交投诉
     * @param array $data
     * @return array [bool $success, string $message, Complaint|null $complaint]
     */
    public static function submitComplaint(array $data): array
    {
        $userId = (int)($data['user_id'] ?? 0);
        $order = self::resolveOwnedOrder((int)($data['order_id'] ?? 0), $userId);
        if (!$order) {
            return [false, '关联订单不存在', null];
        }

        if (!self::canCreateComplaintForOrder($order)) {
            return [false, '当前订单状态不支持发起投诉', null];
        }

        if (self::hasUnsafeMediaItem($data['images'] ?? [])) {
            return [false, '请先上传有效的投诉图片', null];
        }
        $images = self::sanitizeMediaList($data['images'] ?? [], 9);

        if (self::hasUnsafeMediaItem($data['videos'] ?? [])) {
            return [false, '请先上传有效的视频凭证', null];
        }
        $videos = self::sanitizeMediaList($data['videos'] ?? [], 3);

        $staffId = self::resolveComplaintStaffId($order, (int)($data['staff_id'] ?? 0));
        if ($staffId < 0) {
            return [false, '投诉服务人员与订单不匹配', null];
        }

        Db::startTrans();
        try {
            // 计算截止时间（根据等级）
            $deadlineHours = [
                self::LEVEL_NORMAL => 72,
                self::LEVEL_SERIOUS => 48,
                self::LEVEL_URGENT => 24,
            ];
            $level = $data['level'] ?? self::LEVEL_NORMAL;
            $deadline = time() + ($deadlineHours[$level] ?? 72) * 3600;

            $complaint = self::create([
                'complaint_sn' => self::generateComplaintSn(),
                'order_id' => (int)$order->id,
                'user_id' => $userId,
                'staff_id' => $staffId,
                'type' => $data['type'] ?? self::TYPE_OTHER,
                'level' => $level,
                'title' => self::trimText((string)($data['title'] ?? ''), 200),
                'content' => self::trimText((string)($data['content'] ?? ''), 5000),
                'images' => $images,
                'videos' => $videos,
                'expect_result' => self::trimText((string)($data['expect_result'] ?? ''), 500),
                'contact_name' => self::trimText((string)($data['contact_name'] ?? ''), 50),
                'contact_mobile' => trim((string)($data['contact_mobile'] ?? '')),
                'status' => self::STATUS_PENDING,
                'deadline' => $deadline,
                'source' => 1,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            Db::commit();
            return [true, '投诉提交成功', $complaint];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '提交失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 处理投诉
     * @param int $complaintId
     * @param int $adminId
     * @param array $handleData
     * @return array [bool $success, string $message]
     */
    public static function handleComplaint(int $complaintId, int $adminId, array $handleData): array
    {
        Db::startTrans();
        try {
            $complaint = self::lock(true)->find($complaintId);
            if (!$complaint) {
                Db::rollback();
                return [false, '投诉记录不存在'];
            }

            if (!in_array($complaint->status, [self::STATUS_PENDING, self::STATUS_PROCESSING])) {
                Db::rollback();
                return [false, '当前状态不可处理'];
            }

            $complaint->handle_admin_id = $adminId;
            $complaint->handle_result = self::trimText((string)($handleData['result'] ?? ''), 5000);
            $complaint->handle_action = (int)($handleData['action'] ?? self::ACTION_NONE);
            $complaint->handle_amount = round(max(0, (float)($handleData['amount'] ?? 0)), 2);
            $complaint->handle_time = time();
            $complaint->status = self::STATUS_HANDLED;
            $complaint->update_time = time();
            $complaint->save();

            // 如果有处理动作需要执行
            if ($handleData['action'] == self::ACTION_DISABLE && $complaint->staff_id > 0) {
                // TODO: 禁用服务人员
            }

            Db::commit();
            return [true, '处理成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '处理失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 用户评价满意度
     * @param int $complaintId
     * @param int $userId
     * @param int $satisfaction
     * @return array [bool $success, string $message]
     */
    public static function rateSatisfaction(int $complaintId, int $userId, int $satisfaction): array
    {
        try {
            $complaint = self::find($complaintId);
            if (!$complaint) {
                return [false, '投诉记录不存在'];
            }

            if ($complaint->user_id != $userId) {
                return [false, '无权操作'];
            }

            if ($complaint->status != self::STATUS_HANDLED) {
                return [false, '当前状态不可评价'];
            }

            $complaint->satisfaction = $satisfaction;
            $complaint->update_time = time();
            $complaint->save();

            return [true, '评价成功'];
        } catch (\Exception $e) {
            return [false, '评价失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 获取统计数据
     * @return array
     */
    public static function getStatistics(): array
    {
        $today = strtotime(date('Y-m-d'));
        $todayEnd = $today + 86400;

        return [
            'total' => self::count(),
            'pending' => self::where('status', self::STATUS_PENDING)->count(),
            'handled' => self::where('status', self::STATUS_HANDLED)->count(),
            'today_new' => self::where('create_time', '>=', $today)->where('create_time', '<', $todayEnd)->count(),
            'overtime' => self::where('is_overtime', 1)->whereNotIn('status', [self::STATUS_HANDLED, self::STATUS_CLOSED])->count(),
        ];
    }

    /**
     * @notes 解析用户订单
     */
    protected static function resolveOwnedOrder(int $orderId, int $userId): ?Order
    {
        if ($orderId <= 0 || $userId <= 0) {
            return null;
        }

        return Order::with(['items'])
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->find();
    }

    /**
     * @notes 当前订单是否允许发起投诉
     */
    protected static function canCreateComplaintForOrder(Order $order): bool
    {
        return in_array((int)$order->order_status, [
            Order::STATUS_PAID,
            Order::STATUS_IN_SERVICE,
            Order::STATUS_COMPLETED,
            Order::STATUS_REVIEWED,
            Order::STATUS_REFUNDED,
        ], true);
    }

    /**
     * @notes 解析投诉关联服务人员
     */
    protected static function resolveComplaintStaffId(Order $order, int $staffId = 0): int
    {
        $staffIds = [];
        foreach ($order->items ?? [] as $item) {
            $itemStaffId = (int)($item->staff_id ?? 0);
            if ($itemStaffId > 0) {
                $staffIds[] = $itemStaffId;
            }
        }
        $staffIds = array_values(array_unique($staffIds));

        if ($staffId > 0) {
            return in_array($staffId, $staffIds, true) ? $staffId : -1;
        }

        return $staffIds[0] ?? 0;
    }

    /**
     * @notes 还原附件列表
     */
    protected static function restoreMediaList($value): array
    {
        $lists = json_decode((string)$value, true);
        if (!is_array($lists)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($item) {
            $path = self::sanitizeMediaPath((string)$item);
            return $path !== '' ? FileService::getFileUrl($path) : '';
        }, $lists)));
    }

    /**
     * @notes 清洗附件列表
     */
    protected static function sanitizeMediaList($value, int $maxCount): array
    {
        if (!is_array($value)) {
            return [];
        }

        $lists = [];
        foreach ($value as $item) {
            $path = self::sanitizeMediaPath((string)$item);
            if ($path === '') {
                continue;
            }

            $lists[] = $path;
            if (count($lists) >= $maxCount) {
                break;
            }
        }

        return array_values(array_unique($lists));
    }

    /**
     * @notes 是否包含不安全附件
     */
    protected static function hasUnsafeMediaItem($value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (trim((string)$item) !== '' && self::sanitizeMediaPath((string)$item) === '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @notes 清洗单个附件路径
     */
    protected static function sanitizeMediaPath(string $value): string
    {
        $value = trim(str_replace('\\', '/', $value));
        if ($value === '') {
            return '';
        }

        if (preg_match('/^(?:wxfile|file|blob|data|javascript):/i', $value)) {
            return '';
        }

        if (preg_match('#^https?://#i', $value)) {
            $value = FileService::setFileUrl($value);
        }

        $value = ltrim($value, '/');
        if ($value === '' || preg_match('#^https?://#i', $value) || str_contains($value, '..')) {
            return '';
        }

        return preg_match('#^[A-Za-z0-9/_\.-]+$#', $value) ? $value : '';
    }

    /**
     * @notes 截断文本
     */
    protected static function trimText(string $value, int $maxLength): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return mb_strlen($value, 'UTF-8') > $maxLength
            ? mb_substr($value, 0, $maxLength, 'UTF-8')
            : $value;
    }
}
