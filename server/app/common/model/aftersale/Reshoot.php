<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 补拍/重拍申请模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\aftersale;

use app\common\model\BaseModel;
use app\common\model\order\Order;
use app\common\model\staff\Staff;
use app\common\model\user\User;
use think\facade\Db;

/**
 * 补拍/重拍申请模型
 * Class Reshoot
 * @package app\common\model\aftersale
 */
class Reshoot extends BaseModel
{
    protected $name = 'reshoot';

    // 申请类型
    const TYPE_RESHOOT = 1;     // 补拍
    const TYPE_RETAKE = 2;      // 重拍

    // 原因类型
    const REASON_UNSATISFIED = 1;   // 效果不满意
    const REASON_WEATHER = 2;       // 天气原因
    const REASON_EQUIPMENT = 3;     // 设备故障
    const REASON_STAFF = 4;         // 人员原因
    const REASON_OTHER = 5;         // 其他

    // 申请状态
    const STATUS_PENDING = 0;       // 待审核
    const STATUS_APPROVED = 1;      // 审核通过
    const STATUS_REJECTED = 2;      // 审核拒绝
    const STATUS_SCHEDULED = 3;     // 已安排
    const STATUS_COMPLETED = 4;     // 已完成
    const STATUS_CANCELLED = 5;     // 已取消

    // 费用状态
    const FEE_UNPAID = 0;   // 待支付
    const FEE_PAID = 1;     // 已支付

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
     * @notes 关联原服务人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id')
            ->field('id,name,avatar,mobile');
    }

    /**
     * @notes 关联新服务人员
     * @return \think\model\relation\BelongsTo
     */
    public function newStaff()
    {
        return $this->belongsTo(Staff::class, 'new_staff_id', 'id')
            ->field('id,name,avatar,mobile');
    }

    /**
     * @notes 申请类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getTypeDescAttr($value, $data): string
    {
        $map = [
            self::TYPE_RESHOOT => '补拍',
            self::TYPE_RETAKE => '重拍',
        ];
        return $map[$data['type']] ?? '未知';
    }

    /**
     * @notes 原因类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getReasonTypeDescAttr($value, $data): string
    {
        $map = [
            self::REASON_UNSATISFIED => '效果不满意',
            self::REASON_WEATHER => '天气原因',
            self::REASON_EQUIPMENT => '设备故障',
            self::REASON_STAFF => '人员原因',
            self::REASON_OTHER => '其他',
        ];
        return $map[$data['reason_type']] ?? '未知';
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
            self::STATUS_PENDING => '待审核',
            self::STATUS_APPROVED => '审核通过',
            self::STATUS_REJECTED => '审核拒绝',
            self::STATUS_SCHEDULED => '已安排',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_CANCELLED => '已取消',
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
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @notes 图片设置器
     * @param $value
     * @return string
     */
    public function setImagesAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * @notes 生成申请编号
     * @return string
     */
    public static function generateReshootSn(): string
    {
        return 'RS' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @notes 提交申请
     * @param array $data
     * @return array [bool $success, string $message, Reshoot|null $reshoot]
     */
    public static function applyReshoot(array $data): array
    {
        Db::startTrans();
        try {
            // 检查订单
            $order = Order::find($data['order_id']);
            if (!$order) {
                return [false, '订单不存在', null];
            }

            if ($order->user_id != $data['user_id']) {
                return [false, '无权操作此订单', null];
            }

            // 检查是否有未处理的申请
            $existsReshoot = self::where('order_id', $data['order_id'])
                ->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_SCHEDULED])
                ->find();
            
            if ($existsReshoot) {
                return [false, '存在未处理的补拍/重拍申请', null];
            }

            $reshoot = self::create([
                'reshoot_sn' => self::generateReshootSn(),
                'order_id' => $data['order_id'],
                'order_item_id' => $data['order_item_id'] ?? 0,
                'user_id' => $data['user_id'],
                'staff_id' => $data['staff_id'] ?? 0,
                'type' => $data['type'] ?? self::TYPE_RESHOOT,
                'reason_type' => $data['reason_type'] ?? self::REASON_OTHER,
                'reason' => $data['reason'] ?? '',
                'images' => $data['images'] ?? [],
                'expect_date' => $data['expect_date'] ?? null,
                'expect_time_slot' => $data['expect_time_slot'] ?? '',
                'status' => self::STATUS_PENDING,
                'is_free' => 1,  // 默认免费
                'create_time' => time(),
                'update_time' => time(),
            ]);

            Db::commit();
            return [true, '申请提交成功', $reshoot];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '提交失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 审核申请
     * @param int $reshootId
     * @param int $adminId
     * @param bool $approved
     * @param array $auditData
     * @return array [bool $success, string $message]
     */
    public static function auditReshoot(int $reshootId, int $adminId, bool $approved, array $auditData = []): array
    {
        Db::startTrans();
        try {
            $reshoot = self::find($reshootId);
            if (!$reshoot) {
                return [false, '申请记录不存在'];
            }

            if ($reshoot->status != self::STATUS_PENDING) {
                return [false, '当前状态不可审核'];
            }

            $reshoot->audit_admin_id = $adminId;
            $reshoot->audit_time = time();
            $reshoot->audit_remark = $auditData['remark'] ?? '';
            $reshoot->update_time = time();

            if ($approved) {
                $reshoot->status = self::STATUS_APPROVED;
                $reshoot->is_free = $auditData['is_free'] ?? 1;
                $reshoot->fee = $auditData['fee'] ?? 0;
            } else {
                $reshoot->status = self::STATUS_REJECTED;
            }

            $reshoot->save();

            Db::commit();
            return [true, $approved ? '审核通过' : '已拒绝'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '审核失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 安排档期
     * @param int $reshootId
     * @param int $adminId
     * @param array $scheduleData
     * @return array [bool $success, string $message]
     */
    public static function scheduleReshoot(int $reshootId, int $adminId, array $scheduleData): array
    {
        Db::startTrans();
        try {
            $reshoot = self::find($reshootId);
            if (!$reshoot) {
                return [false, '申请记录不存在'];
            }

            if ($reshoot->status != self::STATUS_APPROVED) {
                return [false, '当前状态不可安排'];
            }

            // 如果需要付费且未支付
            if (!$reshoot->is_free && $reshoot->fee_status != self::FEE_PAID) {
                return [false, '请先完成费用支付'];
            }

            $reshoot->schedule_id = $scheduleData['schedule_id'] ?? 0;
            $reshoot->schedule_date = $scheduleData['schedule_date'];
            $reshoot->new_staff_id = $scheduleData['new_staff_id'] ?? $reshoot->staff_id;
            $reshoot->status = self::STATUS_SCHEDULED;
            $reshoot->update_time = time();
            $reshoot->save();

            Db::commit();
            return [true, '安排成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '安排失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 完成补拍
     * @param int $reshootId
     * @param int $adminId
     * @param string $remark
     * @return array [bool $success, string $message]
     */
    public static function completeReshoot(int $reshootId, int $adminId, string $remark = ''): array
    {
        Db::startTrans();
        try {
            $reshoot = self::find($reshootId);
            if (!$reshoot) {
                return [false, '申请记录不存在'];
            }

            if ($reshoot->status != self::STATUS_SCHEDULED) {
                return [false, '当前状态不可完成'];
            }

            $reshoot->status = self::STATUS_COMPLETED;
            $reshoot->complete_time = time();
            $reshoot->complete_remark = $remark;
            $reshoot->update_time = time();
            $reshoot->save();

            Db::commit();
            return [true, '完成成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '完成失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 取消申请
     * @param int $reshootId
     * @param int $userId
     * @return array [bool $success, string $message]
     */
    public static function cancelReshoot(int $reshootId, int $userId): array
    {
        try {
            $reshoot = self::find($reshootId);
            if (!$reshoot) {
                return [false, '申请记录不存在'];
            }

            if ($reshoot->user_id != $userId) {
                return [false, '无权操作'];
            }

            if (!in_array($reshoot->status, [self::STATUS_PENDING, self::STATUS_APPROVED])) {
                return [false, '当前状态不可取消'];
            }

            $reshoot->status = self::STATUS_CANCELLED;
            $reshoot->update_time = time();
            $reshoot->save();

            return [true, '取消成功'];
        } catch (\Exception $e) {
            return [false, '取消失败：' . $e->getMessage()];
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
            'approved' => self::where('status', self::STATUS_APPROVED)->count(),
            'scheduled' => self::where('status', self::STATUS_SCHEDULED)->count(),
            'completed' => self::where('status', self::STATUS_COMPLETED)->count(),
            'today_new' => self::where('create_time', '>=', $today)->where('create_time', '<', $todayEnd)->count(),
        ];
    }
}
