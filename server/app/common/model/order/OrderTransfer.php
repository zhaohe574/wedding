<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单转让模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;
use app\common\model\user\User;
use think\model\concern\SoftDelete;
use think\facade\Db;

/**
 * 订单转让模型
 * 支持订单在用户之间转让
 * Class OrderTransfer
 * @package app\common\model\order
 */
class OrderTransfer extends BaseModel
{
    use SoftDelete;

    protected $name = 'order_transfer';
    protected $deleteTime = 'delete_time';

    // 转让状态
    const STATUS_PENDING = 0;       // 待审核
    const STATUS_WAITING = 1;       // 待接收（审核通过，等待接收方确认）
    const STATUS_ACCEPTED = 2;      // 接收确认
    const STATUS_COMPLETED = 3;     // 转让完成
    const STATUS_REJECTED = 4;      // 已拒绝
    const STATUS_CANCELLED = 5;     // 已取消

    // 验证码有效期（秒）
    const CODE_EXPIRE_TIME = 600; // 10分钟

    // 最大发送验证码次数
    const MAX_CODE_SEND_COUNT = 5;

    /**
     * @notes 关联订单
     * @return \think\model\relation\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * @notes 关联转让方用户
     * @return \think\model\relation\BelongsTo
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id', 'id');
    }

    /**
     * @notes 关联接收方用户
     * @return \think\model\relation\BelongsTo
     */
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id', 'id');
    }

    /**
     * @notes 转让状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getTransferStatusDescAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_WAITING => '待接收',
            self::STATUS_ACCEPTED => '接收确认',
            self::STATUS_COMPLETED => '转让完成',
            self::STATUS_REJECTED => '已拒绝',
            self::STATUS_CANCELLED => '已取消',
        ];
        return $map[$data['transfer_status']] ?? '未知';
    }

    /**
     * @notes 生成转让单号
     * @return string
     */
    public static function generateTransferSn(): string
    {
        return 'TRF' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @notes 生成接收验证码
     * @return string
     */
    public static function generateAcceptCode(): string
    {
        return str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @notes 检查订单是否可转让
     * @param int $orderId
     * @param int $userId
     * @return array [bool $canTransfer, string $message]
     */
    public static function checkCanTransfer(int $orderId, int $userId): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            return [false, '订单不存在'];
        }

        // 检查所有者
        if ($order->user_id != $userId) {
            return [false, '无权操作此订单'];
        }

        // 只有已支付或服务中的订单可以转让
        if (!in_array($order->order_status, [Order::STATUS_PAID, Order::STATUS_IN_SERVICE])) {
            return [false, '当前订单状态不支持转让'];
        }

        // 检查是否已有转让记录
        if ($order->is_transferred) {
            return [false, '该订单已被转让过'];
        }

        // 检查是否有进行中的转让
        $pendingTransfer = self::where('order_id', $orderId)
            ->whereIn('transfer_status', [self::STATUS_PENDING, self::STATUS_WAITING, self::STATUS_ACCEPTED])
            ->find();
        if ($pendingTransfer) {
            return [false, '存在进行中的转让申请'];
        }

        // 检查是否暂停中
        if ($order->is_paused) {
            return [false, '订单已暂停，请先恢复订单'];
        }

        return [true, '可以转让'];
    }

    /**
     * @notes 申请转让
     * @param int $userId 转让方用户ID
     * @param int $orderId 订单ID
     * @param string $toUserName 接收方姓名
     * @param string $toUserMobile 接收方手机号
     * @param string $reason 转让原因
     * @param float $transferFee 转让手续费
     * @return array [bool $success, string $message, OrderTransfer|null $transfer]
     */
    public static function applyTransfer(
        int $userId,
        int $orderId,
        string $toUserName,
        string $toUserMobile,
        string $reason = '',
        float $transferFee = 0
    ): array {
        // 检查是否可转让
        [$canTransfer, $message] = self::checkCanTransfer($orderId, $userId);
        if (!$canTransfer) {
            return [false, $message, null];
        }

        // 验证接收方手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $toUserMobile)) {
            return [false, '接收方手机号格式不正确', null];
        }

        $order = Order::find($orderId);
        $fromUser = User::find($userId);

        // 检查接收方是否为自己
        if ($fromUser->mobile == $toUserMobile) {
            return [false, '不能转让给自己', null];
        }

        Db::startTrans();
        try {
            // 检查接收方是否为已注册用户
            $toUser = User::where('mobile', $toUserMobile)->find();

            // 创建转让记录
            $transfer = self::create([
                'transfer_sn' => self::generateTransferSn(),
                'order_id' => $orderId,
                'order_sn' => $order->order_sn,
                'from_user_id' => $userId,
                'from_user_name' => $fromUser->nickname ?? $fromUser->mobile,
                'from_user_mobile' => $fromUser->mobile ?? '',
                'to_user_id' => $toUser ? $toUser->id : 0,
                'to_user_name' => $toUserName,
                'to_user_mobile' => $toUserMobile,
                'to_user_verified' => 0,
                'transfer_status' => self::STATUS_PENDING,
                'transfer_reason' => $reason,
                'transfer_fee' => $transferFee,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 记录日志
            OrderChangeLog::addLog(
                $orderId,
                OrderChangeLog::RELATED_TYPE_TRANSFER,
                $transfer->id,
                OrderChangeLog::OPERATOR_USER,
                $userId,
                'apply',
                0,
                self::STATUS_PENDING,
                "申请转让给：{$toUserName}（{$toUserMobile}）"
            );

            Db::commit();
            return [true, '转让申请已提交，请等待审核', $transfer];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '申请失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 审核转让申请
     * @param int $transferId
     * @param int $adminId
     * @param bool $approved
     * @param string $remark
     * @param string $rejectReason
     * @return array [bool $success, string $message]
     */
    public static function auditTransfer(
        int $transferId,
        int $adminId,
        bool $approved,
        string $remark = '',
        string $rejectReason = ''
    ): array {
        $transfer = self::find($transferId);
        if (!$transfer) {
            return [false, '转让记录不存在'];
        }

        if ($transfer->transfer_status != self::STATUS_PENDING) {
            return [false, '该转让申请已处理'];
        }

        Db::startTrans();
        try {
            $beforeStatus = $transfer->transfer_status;

            if ($approved) {
                $transfer->transfer_status = self::STATUS_WAITING;
                $transfer->audit_remark = $remark;
                $logContent = '审核通过，等待接收方确认';

                // 发送验证码给接收方
                self::sendAcceptCode($transferId);
            } else {
                $transfer->transfer_status = self::STATUS_REJECTED;
                $transfer->reject_reason = $rejectReason;
                $transfer->audit_remark = $remark;
                $logContent = '审核拒绝：' . $rejectReason;
            }

            $transfer->audit_admin_id = $adminId;
            $transfer->audit_time = time();
            $transfer->update_time = time();
            $transfer->save();

            // 记录日志
            OrderChangeLog::addLog(
                $transfer->order_id,
                OrderChangeLog::RELATED_TYPE_TRANSFER,
                $transferId,
                OrderChangeLog::OPERATOR_ADMIN,
                $adminId,
                'audit',
                $beforeStatus,
                $transfer->transfer_status,
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
     * @notes 发送接收验证码
     * @param int $transferId
     * @return array [bool $success, string $message]
     */
    public static function sendAcceptCode(int $transferId): array
    {
        $transfer = self::find($transferId);
        if (!$transfer) {
            return [false, '转让记录不存在'];
        }

        if ($transfer->transfer_status != self::STATUS_WAITING) {
            return [false, '当前状态不可发送验证码'];
        }

        // 检查发送次数
        if ($transfer->accept_code_send_count >= self::MAX_CODE_SEND_COUNT) {
            return [false, '验证码发送次数已达上限'];
        }

        // 检查发送间隔（60秒）
        if ($transfer->accept_code_send_time > 0 && time() - $transfer->accept_code_send_time < 60) {
            return [false, '请60秒后再发送'];
        }

        // 生成验证码
        $code = self::generateAcceptCode();

        // 更新记录
        $transfer->accept_code = $code;
        $transfer->accept_code_expire = time() + self::CODE_EXPIRE_TIME;
        $transfer->accept_code_send_time = time();
        $transfer->accept_code_send_count = $transfer->accept_code_send_count + 1;
        $transfer->update_time = time();
        $transfer->save();

        // TODO: 发送短信验证码给接收方
        // SmsService::send($transfer->to_user_mobile, 'transfer_accept', ['code' => $code]);

        return [true, '验证码已发送'];
    }

    /**
     * @notes 接收方确认接收
     * @param int $transferId
     * @param string $mobile 接收方手机号
     * @param string $code 验证码
     * @return array [bool $success, string $message]
     */
    public static function acceptTransfer(int $transferId, string $mobile, string $code): array
    {
        $transfer = self::find($transferId);
        if (!$transfer) {
            return [false, '转让记录不存在'];
        }

        if ($transfer->transfer_status != self::STATUS_WAITING) {
            return [false, '当前状态不可接收'];
        }

        // 验证手机号
        if ($transfer->to_user_mobile != $mobile) {
            return [false, '手机号不匹配'];
        }

        // 验证码检查
        if ($transfer->accept_code != $code) {
            return [false, '验证码错误'];
        }

        if (time() > $transfer->accept_code_expire) {
            return [false, '验证码已过期'];
        }

        Db::startTrans();
        try {
            $beforeStatus = $transfer->transfer_status;

            // 更新转让状态
            $transfer->transfer_status = self::STATUS_ACCEPTED;
            $transfer->to_user_verified = 1;
            $transfer->accept_time = time();
            $transfer->update_time = time();
            $transfer->save();

            // 记录日志
            OrderChangeLog::addLog(
                $transfer->order_id,
                OrderChangeLog::RELATED_TYPE_TRANSFER,
                $transferId,
                OrderChangeLog::OPERATOR_USER,
                $transfer->to_user_id ?: 0,
                'accept',
                $beforeStatus,
                self::STATUS_ACCEPTED,
                '接收方确认接收'
            );

            Db::commit();

            // 自动完成转让
            return self::completeTransfer($transferId, 0);
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '接收失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 完成转让
     * @param int $transferId
     * @param int $adminId 管理员ID（手动完成时传入）
     * @return array [bool $success, string $message]
     */
    public static function completeTransfer(int $transferId, int $adminId = 0): array
    {
        $transfer = self::find($transferId);
        if (!$transfer) {
            return [false, '转让记录不存在'];
        }

        if ($transfer->transfer_status != self::STATUS_ACCEPTED) {
            return [false, '当前状态不可完成转让'];
        }

        Db::startTrans();
        try {
            $order = Order::find($transfer->order_id);
            $beforeStatus = $transfer->transfer_status;

            // 检查/创建接收方用户
            $toUserId = $transfer->to_user_id;
            if (!$toUserId) {
                // 接收方未注册，需要创建账号
                $newUser = User::create([
                    'sn' => User::generateUserSn(),
                    'nickname' => $transfer->to_user_name,
                    'mobile' => $transfer->to_user_mobile,
                    'is_new_user' => 1,
                    'register_source' => 4, // 转让创建
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
                $toUserId = $newUser->id;
                $transfer->to_user_id = $toUserId;
            }

            // 更新订单所有者
            $order->original_user_id = $order->user_id;
            $order->user_id = $toUserId;
            $order->is_transferred = 1;
            $order->transfer_id = $transferId;
            // 更新联系人信息为接收方
            $order->contact_name = $transfer->to_user_name;
            $order->contact_mobile = $transfer->to_user_mobile;
            $order->update_time = time();
            $order->save();

            // 更新转让状态
            $transfer->transfer_status = self::STATUS_COMPLETED;
            $transfer->complete_time = time();
            $transfer->complete_admin_id = $adminId;
            $transfer->update_time = time();
            $transfer->save();

            // 记录订单日志
            OrderLog::addLog(
                $order->id,
                $adminId > 0 ? 2 : 3, // 2=管理员, 3=系统
                $adminId ?: 0,
                'transfer',
                $order->order_status,
                $order->order_status,
                "订单已转让给：{$transfer->to_user_name}（{$transfer->to_user_mobile}）"
            );

            // 记录变更日志
            OrderChangeLog::addLog(
                $transfer->order_id,
                OrderChangeLog::RELATED_TYPE_TRANSFER,
                $transferId,
                $adminId > 0 ? OrderChangeLog::OPERATOR_ADMIN : OrderChangeLog::OPERATOR_SYSTEM,
                $adminId ?: 0,
                'complete',
                $beforeStatus,
                self::STATUS_COMPLETED,
                '转让完成'
            );

            Db::commit();
            return [true, '转让完成'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '转让失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 取消转让申请
     * @param int $transferId
     * @param int $userId
     * @return array [bool $success, string $message]
     */
    public static function cancelTransfer(int $transferId, int $userId): array
    {
        $transfer = self::find($transferId);
        if (!$transfer) {
            return [false, '转让记录不存在'];
        }

        if ($transfer->from_user_id != $userId) {
            return [false, '无权操作此转让申请'];
        }

        if (!in_array($transfer->transfer_status, [self::STATUS_PENDING, self::STATUS_WAITING])) {
            return [false, '当前状态不可取消'];
        }

        Db::startTrans();
        try {
            $beforeStatus = $transfer->transfer_status;
            $transfer->transfer_status = self::STATUS_CANCELLED;
            $transfer->update_time = time();
            $transfer->save();

            // 记录日志
            OrderChangeLog::addLog(
                $transfer->order_id,
                OrderChangeLog::RELATED_TYPE_TRANSFER,
                $transferId,
                OrderChangeLog::OPERATOR_USER,
                $userId,
                'cancel',
                $beforeStatus,
                self::STATUS_CANCELLED,
                '用户取消转让申请'
            );

            Db::commit();
            return [true, '已取消'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '取消失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 获取转让状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            ['value' => self::STATUS_PENDING, 'label' => '待审核'],
            ['value' => self::STATUS_WAITING, 'label' => '待接收'],
            ['value' => self::STATUS_ACCEPTED, 'label' => '接收确认'],
            ['value' => self::STATUS_COMPLETED, 'label' => '转让完成'],
            ['value' => self::STATUS_REJECTED, 'label' => '已拒绝'],
            ['value' => self::STATUS_CANCELLED, 'label' => '已取消'],
        ];
    }
}
