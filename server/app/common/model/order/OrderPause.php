<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单暂停模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;
use app\common\model\user\User;
use think\model\concern\SoftDelete;
use think\facade\Db;

/**
 * 订单暂停模型
 * 支持特殊情况订单暂停和恢复
 * Class OrderPause
 * @package app\common\model\order
 */
class OrderPause extends BaseModel
{
    use SoftDelete;

    protected $name = 'order_pause';
    protected $deleteTime = 'delete_time';

    // 暂停状态
    const STATUS_PENDING = 0;       // 待审核
    const STATUS_PAUSED = 1;        // 暂停中
    const STATUS_RESUMED = 2;       // 已恢复
    const STATUS_REJECTED = 3;      // 已拒绝
    const STATUS_CANCELLED = 4;     // 已取消

    // 暂停类型
    const TYPE_EPIDEMIC = 1;        // 疫情
    const TYPE_EMERGENCY = 2;       // 突发事件
    const TYPE_PERSONAL = 3;        // 个人原因
    const TYPE_OTHER = 4;           // 其他

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
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @notes 暂停状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPauseStatusDescAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_PAUSED => '暂停中',
            self::STATUS_RESUMED => '已恢复',
            self::STATUS_REJECTED => '已拒绝',
            self::STATUS_CANCELLED => '已取消',
        ];
        return $map[$data['pause_status']] ?? '未知';
    }

    /**
     * @notes 暂停类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPauseTypeDescAttr($value, $data): string
    {
        $map = [
            self::TYPE_EPIDEMIC => '疫情',
            self::TYPE_EMERGENCY => '突发事件',
            self::TYPE_PERSONAL => '个人原因',
            self::TYPE_OTHER => '其他',
        ];
        return $map[$data['pause_type']] ?? '未知';
    }

    /**
     * @notes 证明材料图片获取器
     * @param $value
     * @return array
     */
    public function getProofImagesAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @notes 证明材料图片设置器
     * @param $value
     * @return string
     */
    public function setProofImagesAttr($value): string
    {
        return is_array($value) ? json_encode($value) : $value;
    }

    /**
     * @notes 生成暂停单号
     * @return string
     */
    public static function generatePauseSn(): string
    {
        return 'PSE' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @notes 检查订单是否可暂停
     * @param int $orderId
     * @param int $userId
     * @return array [bool $canPause, string $message]
     */
    public static function checkCanPause(int $orderId, int $userId): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            return [false, '订单不存在'];
        }

        // 检查所有者
        if ($order->user_id != $userId) {
            return [false, '无权操作此订单'];
        }

        // 只有已支付或服务中的订单可以暂停
        if (!in_array($order->order_status, [Order::STATUS_PAID, Order::STATUS_IN_SERVICE])) {
            return [false, '当前订单状态不支持暂停'];
        }

        // 检查是否已暂停
        if ($order->is_paused) {
            return [false, '订单已处于暂停状态'];
        }

        // 检查是否有进行中的暂停申请
        $pendingPause = self::where('order_id', $orderId)
            ->whereIn('pause_status', [self::STATUS_PENDING])
            ->find();
        if ($pendingPause) {
            return [false, '存在待审核的暂停申请'];
        }

        return [true, '可以暂停'];
    }

    /**
     * @notes 申请暂停
     * @param int $userId
     * @param int $orderId
     * @param int $pauseType 暂停类型
     * @param string $reason 暂停原因
     * @param string $startDate 暂停开始日期
     * @param string $endDate 暂停结束日期（预计）
     * @param array $proofImages 证明材料图片
     * @return array [bool $success, string $message, OrderPause|null $pause]
     */
    public static function applyPause(
        int $userId,
        int $orderId,
        int $pauseType,
        string $reason,
        string $startDate,
        string $endDate,
        array $proofImages = []
    ): array {
        // 检查是否可暂停
        [$canPause, $message] = self::checkCanPause($orderId, $userId);
        if (!$canPause) {
            return [false, $message, null];
        }

        // 验证暂停类型
        if (!in_array($pauseType, [self::TYPE_EPIDEMIC, self::TYPE_EMERGENCY, self::TYPE_PERSONAL, self::TYPE_OTHER])) {
            return [false, '无效的暂停类型', null];
        }

        // 验证日期
        if (strtotime($startDate) > strtotime($endDate)) {
            return [false, '结束日期不能早于开始日期', null];
        }

        $order = Order::find($orderId);

        Db::startTrans();
        try {
            // 计算暂停天数
            $pauseDays = (strtotime($endDate) - strtotime($startDate)) / 86400 + 1;

            // 创建暂停记录
            $pause = self::create([
                'pause_sn' => self::generatePauseSn(),
                'order_id' => $orderId,
                'order_sn' => $order->order_sn,
                'user_id' => $userId,
                'pause_status' => self::STATUS_PENDING,
                'pause_type' => $pauseType,
                'pause_reason' => $reason,
                'pause_start_date' => $startDate,
                'pause_end_date' => $endDate,
                'pause_days' => $pauseDays,
                'original_service_date' => $order->service_date,
                'proof_images' => $proofImages,
                'remind_before_days' => 3,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 记录日志
            $typeDesc = self::getTypeDesc($pauseType);
            OrderChangeLog::addLog(
                $orderId,
                OrderChangeLog::RELATED_TYPE_PAUSE,
                $pause->id,
                OrderChangeLog::OPERATOR_USER,
                $userId,
                'apply',
                0,
                self::STATUS_PENDING,
                "申请暂停（{$typeDesc}）：{$startDate} 至 {$endDate}"
            );

            Db::commit();
            return [true, '暂停申请已提交，请等待审核', $pause];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '申请失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 审核暂停申请
     * @param int $pauseId
     * @param int $adminId
     * @param bool $approved
     * @param string $remark
     * @param string $rejectReason
     * @return array [bool $success, string $message]
     */
    public static function auditPause(
        int $pauseId,
        int $adminId,
        bool $approved,
        string $remark = '',
        string $rejectReason = ''
    ): array {
        $pause = self::find($pauseId);
        if (!$pause) {
            return [false, '暂停记录不存在'];
        }

        if ($pause->pause_status != self::STATUS_PENDING) {
            return [false, '该暂停申请已处理'];
        }

        Db::startTrans();
        try {
            $order = Order::find($pause->order_id);
            $beforeStatus = $pause->pause_status;

            if ($approved) {
                $pause->pause_status = self::STATUS_PAUSED;
                $pause->audit_remark = $remark;
                $logContent = '审核通过，订单已暂停';

                // 更新订单暂停状态
                $order->is_paused = 1;
                $order->pause_id = $pauseId;
                $order->update_time = time();
                $order->save();

                // 记录订单日志
                OrderLog::addLog(
                    $order->id,
                    2, // 管理员
                    $adminId,
                    'pause',
                    $order->order_status,
                    $order->order_status,
                    '订单已暂停'
                );
            } else {
                $pause->pause_status = self::STATUS_REJECTED;
                $pause->reject_reason = $rejectReason;
                $pause->audit_remark = $remark;
                $logContent = '审核拒绝：' . $rejectReason;
            }

            $pause->audit_admin_id = $adminId;
            $pause->audit_time = time();
            $pause->update_time = time();
            $pause->save();

            // 记录变更日志
            OrderChangeLog::addLog(
                $pause->order_id,
                OrderChangeLog::RELATED_TYPE_PAUSE,
                $pauseId,
                OrderChangeLog::OPERATOR_ADMIN,
                $adminId,
                'audit',
                $beforeStatus,
                $pause->pause_status,
                $logContent
            );

            Db::commit();
            return [true, $approved ? '审核通过' : '已拒绝'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '审核失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 恢复订单
     * @param int $pauseId
     * @param int $adminId
     * @param string $newServiceDate 新服务日期（可选）
     * @param string $remark
     * @return array [bool $success, string $message]
     */
    public static function resumeOrder(
        int $pauseId,
        int $adminId,
        string $newServiceDate = '',
        string $remark = ''
    ): array {
        $pause = self::find($pauseId);
        if (!$pause) {
            return [false, '暂停记录不存在'];
        }

        if ($pause->pause_status != self::STATUS_PAUSED) {
            return [false, '该订单未处于暂停状态'];
        }

        Db::startTrans();
        try {
            $order = Order::find($pause->order_id);
            $beforeStatus = $pause->pause_status;

            // 计算实际暂停天数
            $actualDays = (time() - strtotime($pause->pause_start_date)) / 86400;

            // 更新暂停记录
            $pause->pause_status = self::STATUS_RESUMED;
            $pause->resume_time = time();
            $pause->resume_admin_id = $adminId;
            $pause->resume_remark = $remark;
            $pause->actual_pause_days = max(0, ceil($actualDays));
            if ($newServiceDate) {
                $pause->new_service_date = $newServiceDate;
            }
            $pause->update_time = time();
            $pause->save();

            // 更新订单状态
            $order->is_paused = 0;
            if ($newServiceDate) {
                $order->service_date = $newServiceDate;
            }
            $order->update_time = time();
            $order->save();

            // 如果有新服务日期，更新订单项
            if ($newServiceDate) {
                OrderItem::where('order_id', $order->id)->update([
                    'service_date' => $newServiceDate,
                    'update_time' => time(),
                ]);
            }

            // 记录订单日志
            OrderLog::addLog(
                $order->id,
                2, // 管理员
                $adminId,
                'resume',
                $order->order_status,
                $order->order_status,
                '订单已恢复' . ($newServiceDate ? "，新服务日期：{$newServiceDate}" : '')
            );

            // 记录变更日志
            OrderChangeLog::addLog(
                $pause->order_id,
                OrderChangeLog::RELATED_TYPE_PAUSE,
                $pauseId,
                OrderChangeLog::OPERATOR_ADMIN,
                $adminId,
                'resume',
                $beforeStatus,
                self::STATUS_RESUMED,
                '订单恢复' . ($newServiceDate ? "，新服务日期：{$newServiceDate}" : '')
            );

            Db::commit();
            return [true, '订单已恢复'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '恢复失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 取消暂停申请
     * @param int $pauseId
     * @param int $userId
     * @return array [bool $success, string $message]
     */
    public static function cancelPause(int $pauseId, int $userId): array
    {
        $pause = self::find($pauseId);
        if (!$pause) {
            return [false, '暂停记录不存在'];
        }

        if ($pause->user_id != $userId) {
            return [false, '无权操作此暂停申请'];
        }

        if ($pause->pause_status != self::STATUS_PENDING) {
            return [false, '只能取消待审核的申请'];
        }

        Db::startTrans();
        try {
            $beforeStatus = $pause->pause_status;
            $pause->pause_status = self::STATUS_CANCELLED;
            $pause->update_time = time();
            $pause->save();

            // 记录日志
            OrderChangeLog::addLog(
                $pause->order_id,
                OrderChangeLog::RELATED_TYPE_PAUSE,
                $pauseId,
                OrderChangeLog::OPERATOR_USER,
                $userId,
                'cancel',
                $beforeStatus,
                self::STATUS_CANCELLED,
                '用户取消暂停申请'
            );

            Db::commit();
            return [true, '已取消'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '取消失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 获取即将到期的暂停订单（定时任务用）
     * @param int $days 提前天数
     * @return array
     */
    public static function getExpiringPauses(int $days = 3): array
    {
        $targetDate = date('Y-m-d', strtotime("+{$days} days"));

        return self::where('pause_status', self::STATUS_PAUSED)
            ->where('pause_end_date', '<=', $targetDate)
            ->where('reminded', 0)
            ->select()
            ->toArray();
    }

    /**
     * @notes 标记已提醒
     * @param int $pauseId
     * @return bool
     */
    public static function markReminded(int $pauseId): bool
    {
        return self::where('id', $pauseId)->update([
            'reminded' => 1,
            'remind_time' => time(),
            'update_time' => time(),
        ]) > 0;
    }

    /**
     * @notes 获取暂停类型描述
     * @param int $type
     * @return string
     */
    public static function getTypeDesc(int $type): string
    {
        $map = [
            self::TYPE_EPIDEMIC => '疫情',
            self::TYPE_EMERGENCY => '突发事件',
            self::TYPE_PERSONAL => '个人原因',
            self::TYPE_OTHER => '其他',
        ];
        return $map[$type] ?? '未知';
    }

    /**
     * @notes 获取暂停类型选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return [
            ['value' => self::TYPE_EPIDEMIC, 'label' => '疫情'],
            ['value' => self::TYPE_EMERGENCY, 'label' => '突发事件'],
            ['value' => self::TYPE_PERSONAL, 'label' => '个人原因'],
            ['value' => self::TYPE_OTHER, 'label' => '其他'],
        ];
    }

    /**
     * @notes 获取暂停状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            ['value' => self::STATUS_PENDING, 'label' => '待审核'],
            ['value' => self::STATUS_PAUSED, 'label' => '暂停中'],
            ['value' => self::STATUS_RESUMED, 'label' => '已恢复'],
            ['value' => self::STATUS_REJECTED, 'label' => '已拒绝'],
            ['value' => self::STATUS_CANCELLED, 'label' => '已取消'],
        ];
    }
}
