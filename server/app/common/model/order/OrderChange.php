<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单变更模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;
use app\common\model\user\User;
use app\common\model\staff\Staff;
use app\common\model\schedule\Schedule;
use app\common\service\RedisLockService;
use think\model\concern\SoftDelete;
use think\facade\Db;

/**
 * 订单变更模型
 * 支持改期、换人、加项三种变更类型
 * Class OrderChange
 * @package app\common\model\order
 */
class OrderChange extends BaseModel
{
    use SoftDelete;

    protected $name = 'order_change';
    protected $deleteTime = 'delete_time';

    // 变更类型
    const TYPE_DATE = 1;        // 改期
    const TYPE_STAFF = 2;       // 换人
    const TYPE_ADD_ITEM = 3;    // 加项

    // 变更状态
    const STATUS_PENDING = 0;       // 待审核
    const STATUS_APPROVED = 1;      // 审核通过
    const STATUS_REJECTED = 2;      // 审核拒绝
    const STATUS_EXECUTED = 3;      // 已执行
    const STATUS_CANCELLED = 4;     // 已取消

    // 时间段
    const TIME_SLOT_ALL = 0;        // 全天
    const TIME_SLOT_MORNING = 1;    // 早礼
    const TIME_SLOT_AFTERNOON = 2;  // 午宴
    const TIME_SLOT_EVENING = 3;    // 晚宴

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
     * @notes 关联订单项
     * @return \think\model\relation\BelongsTo
     */
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'id');
    }

    /**
     * @notes 关联原工作人员
     * @return \think\model\relation\BelongsTo
     */
    public function oldStaff()
    {
        return $this->belongsTo(Staff::class, 'old_staff_id', 'id');
    }

    /**
     * @notes 关联新工作人员
     * @return \think\model\relation\BelongsTo
     */
    public function newStaff()
    {
        return $this->belongsTo(Staff::class, 'new_staff_id', 'id');
    }

    /**
     * @notes 关联新增工作人员
     * @return \think\model\relation\BelongsTo
     */
    public function addStaff()
    {
        return $this->belongsTo(Staff::class, 'add_staff_id', 'id');
    }

    /**
     * @notes 变更类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getChangeTypeDescAttr($value, $data): string
    {
        $map = [
            self::TYPE_DATE => '改期',
            self::TYPE_STAFF => '换人',
            self::TYPE_ADD_ITEM => '加项',
        ];
        return $map[$data['change_type']] ?? '未知';
    }

    /**
     * @notes 变更状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getChangeStatusDescAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_APPROVED => '审核通过',
            self::STATUS_REJECTED => '审核拒绝',
            self::STATUS_EXECUTED => '已执行',
            self::STATUS_CANCELLED => '已取消',
        ];
        return $map[$data['change_status']] ?? '未知';
    }

    /**
     * @notes 时间段描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getTimeSlotDescAttr($value, $data): string
    {
        $map = [
            self::TIME_SLOT_ALL => '全天',
            self::TIME_SLOT_MORNING => '早礼',
            self::TIME_SLOT_AFTERNOON => '午宴',
            self::TIME_SLOT_EVENING => '晚宴',
        ];
        return $map[$value] ?? '未知';
    }

    /**
     * @notes 附件图片获取器
     * @param $value
     * @return array
     */
    public function getAttachImagesAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @notes 附件图片设置器
     * @param $value
     * @return string
     */
    public function setAttachImagesAttr($value): string
    {
        return is_array($value) ? json_encode($value) : $value;
    }

    /**
     * @notes 生成变更单号
     * @return string
     */
    public static function generateChangeSn(): string
    {
        return 'CHG' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @notes 检查订单是否可变更
     * @param int $orderId
     * @return array [bool $canChange, string $message]
     */
    public static function checkCanChange(int $orderId): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            return [false, '订单不存在'];
        }

        // 只有已支付或服务中的订单可以变更
        if (!in_array($order->order_status, [Order::STATUS_PAID, Order::STATUS_IN_SERVICE])) {
            return [false, '当前订单状态不支持变更'];
        }

        // 检查是否有未处理的变更申请
        $pendingChange = self::where('order_id', $orderId)
            ->whereIn('change_status', [self::STATUS_PENDING, self::STATUS_APPROVED])
            ->find();
        if ($pendingChange) {
            return [false, '存在未处理的变更申请，请等待处理完成后再申请'];
        }

        // 检查是否暂停中
        if ($order->is_paused) {
            return [false, '订单已暂停，请先恢复订单'];
        }

        return [true, '可以变更'];
    }

    /**
     * @notes 申请改期
     * @param int $userId
     * @param int $orderId
     * @param string $newDate 新服务日期
     * @param int $newTimeSlot 新时间段
     * @param string $reason 申请原因
     * @param array $attachImages 附件图片
     * @return array [bool $success, string $message, OrderChange|null $change]
     */
    public static function applyDateChange(
        int $userId,
        int $orderId,
        string $newDate,
        int $newTimeSlot,
        string $reason = '',
        array $attachImages = []
    ): array {
        // 检查是否可变更
        [$canChange, $message] = self::checkCanChange($orderId);
        if (!$canChange) {
            return [false, $message, null];
        }

        $order = Order::with(['items'])->find($orderId);
        
        // 检查订单所有者
        if ($order->user_id != $userId) {
            return [false, '无权操作此订单', null];
        }

        // 验证新日期
        if (strtotime($newDate) <= strtotime(date('Y-m-d'))) {
            return [false, '新服务日期必须大于今天', null];
        }

        Db::startTrans();
        try {
            // 获取原服务日期（取第一个订单项的日期）
            $firstItem = $order->items[0] ?? null;
            if (!$firstItem) {
                return [false, '订单项不存在', null];
            }

            // 检查所有订单项的新档期是否可用
            foreach ($order->items as $item) {
                $available = Schedule::checkAvailable($item->staff_id, $newDate, $newTimeSlot);
                if (!$available) {
                    $staffName = $item->staff_name ?: "人员ID:{$item->staff_id}";
                    return [false, "{$staffName}在{$newDate}该时段档期不可用", null];
                }
            }

            // 创建变更记录
            $change = self::create([
                'change_sn' => self::generateChangeSn(),
                'order_id' => $orderId,
                'order_sn' => $order->order_sn,
                'user_id' => $userId,
                'change_type' => self::TYPE_DATE,
                'change_status' => self::STATUS_PENDING,
                'old_service_date' => $firstItem->service_date,
                'new_service_date' => $newDate,
                'old_time_slot' => $firstItem->time_slot,
                'new_time_slot' => $newTimeSlot,
                'apply_reason' => $reason,
                'attach_images' => $attachImages,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 记录变更日志
            OrderChangeLog::addLog(
                $orderId,
                OrderChangeLog::RELATED_TYPE_CHANGE,
                $change->id,
                OrderChangeLog::OPERATOR_USER,
                $userId,
                'apply',
                0,
                self::STATUS_PENDING,
                "申请改期：{$firstItem->service_date} → {$newDate}"
            );

            // 更新订单变更标记
            Order::where('id', $orderId)->update([
                'has_changed' => 1,
                'update_time' => time(),
            ]);

            Db::commit();
            return [true, '改期申请已提交，请等待审核', $change];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '申请失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 申请换人
     * @param int $userId
     * @param int $orderId
     * @param int $orderItemId 订单项ID
     * @param int $newStaffId 新工作人员ID
     * @param string $reason 申请原因
     * @param array $attachImages 附件图片
     * @return array [bool $success, string $message, OrderChange|null $change]
     */
    public static function applyStaffChange(
        int $userId,
        int $orderId,
        int $orderItemId,
        int $newStaffId,
        string $reason = '',
        array $attachImages = []
    ): array {
        // 检查是否可变更
        [$canChange, $message] = self::checkCanChange($orderId);
        if (!$canChange) {
            return [false, $message, null];
        }

        $order = Order::find($orderId);
        if ($order->user_id != $userId) {
            return [false, '无权操作此订单', null];
        }

        // 获取订单项
        $orderItem = OrderItem::find($orderItemId);
        if (!$orderItem || $orderItem->order_id != $orderId) {
            return [false, '订单项不存在', null];
        }

        // 检查新人员是否存在
        $newStaff = Staff::find($newStaffId);
        if (!$newStaff || $newStaff->status != 1) {
            return [false, '新工作人员不存在或已停用', null];
        }

        // 检查是否同一人
        if ($orderItem->staff_id == $newStaffId) {
            return [false, '新人员与原人员相同', null];
        }

        // 检查新人员档期是否可用
        $available = Schedule::checkAvailable($newStaffId, $orderItem->service_date, $orderItem->time_slot);
        if (!$available) {
            return [false, '新工作人员在该日期时段档期不可用', null];
        }

        Db::startTrans();
        try {
            // 获取原人员信息
            $oldStaff = Staff::find($orderItem->staff_id);
            $oldPrice = $orderItem->price;
            $newPrice = $newStaff->price;
            $priceDiff = $newPrice - $oldPrice; // 正数需补付，负数需退款

            // 临时锁定新人员档期（15分钟）
            $lockResult = Schedule::temporaryLock(
                $newStaffId,
                $orderItem->service_date,
                $orderItem->time_slot,
                $orderId,
                15 * 60 // 15分钟
            );
            if (!$lockResult['success']) {
                return [false, '锁定新人员档期失败：' . $lockResult['message'], null];
            }

            // 创建变更记录
            $change = self::create([
                'change_sn' => self::generateChangeSn(),
                'order_id' => $orderId,
                'order_sn' => $order->order_sn,
                'user_id' => $userId,
                'change_type' => self::TYPE_STAFF,
                'change_status' => self::STATUS_PENDING,
                'order_item_id' => $orderItemId,
                'old_staff_id' => $orderItem->staff_id,
                'new_staff_id' => $newStaffId,
                'old_staff_name' => $oldStaff->name ?? '',
                'new_staff_name' => $newStaff->name,
                'old_schedule_id' => $orderItem->schedule_id,
                'new_schedule_id' => $lockResult['schedule_id'] ?? 0,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'price_diff' => $priceDiff,
                'apply_reason' => $reason,
                'attach_images' => $attachImages,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 记录日志
            $diffDesc = $priceDiff > 0 ? "需补付{$priceDiff}元" : ($priceDiff < 0 ? "需退款" . abs($priceDiff) . "元" : "无差价");
            OrderChangeLog::addLog(
                $orderId,
                OrderChangeLog::RELATED_TYPE_CHANGE,
                $change->id,
                OrderChangeLog::OPERATOR_USER,
                $userId,
                'apply',
                0,
                self::STATUS_PENDING,
                "申请换人：{$oldStaff->name} → {$newStaff->name}，{$diffDesc}"
            );

            // 更新订单变更标记
            Order::where('id', $orderId)->update([
                'has_changed' => 1,
                'update_time' => time(),
            ]);

            Db::commit();
            return [true, '换人申请已提交，请等待审核', $change];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '申请失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 申请加项
     * @param int $userId
     * @param int $orderId
     * @param int $staffId 新增工作人员ID
     * @param int $packageId 新增套餐ID
     * @param string $serviceDate 服务日期
     * @param int $timeSlot 时间段
     * @param string $reason 申请原因
     * @return array [bool $success, string $message, OrderChange|null $change]
     */
    public static function applyAddItem(
        int $userId,
        int $orderId,
        int $staffId,
        int $packageId,
        string $serviceDate,
        int $timeSlot,
        string $reason = ''
    ): array {
        // 检查是否可变更
        [$canChange, $message] = self::checkCanChange($orderId);
        if (!$canChange) {
            return [false, $message, null];
        }

        $order = Order::find($orderId);
        if ($order->user_id != $userId) {
            return [false, '无权操作此订单', null];
        }

        // 检查人员
        $staff = Staff::find($staffId);
        if (!$staff || $staff->status != 1) {
            return [false, '工作人员不存在或已停用', null];
        }

        // 检查套餐
        $package = \app\common\model\service\ServicePackage::find($packageId);
        if (!$package) {
            return [false, '服务套餐不存在', null];
        }

        // 验证日期
        if (strtotime($serviceDate) <= strtotime(date('Y-m-d'))) {
            return [false, '服务日期必须大于今天', null];
        }

        // 检查档期
        $available = Schedule::checkAvailable($staffId, $serviceDate, $timeSlot);
        if (!$available) {
            return [false, '该人员在指定日期时段档期不可用', null];
        }

        Db::startTrans();
        try {
            // 获取价格
            $price = \app\common\model\staff\StaffPackage::getStaffPackagePrice($staffId, $packageId) ?: $staff->price;

            // 临时锁定档期
            $lockResult = Schedule::temporaryLock($staffId, $serviceDate, $timeSlot, $orderId, 15 * 60);
            if (!$lockResult['success']) {
                return [false, '锁定档期失败：' . $lockResult['message'], null];
            }

            // 创建变更记录
            $change = self::create([
                'change_sn' => self::generateChangeSn(),
                'order_id' => $orderId,
                'order_sn' => $order->order_sn,
                'user_id' => $userId,
                'change_type' => self::TYPE_ADD_ITEM,
                'change_status' => self::STATUS_PENDING,
                'add_staff_id' => $staffId,
                'add_package_id' => $packageId,
                'add_staff_name' => $staff->name,
                'add_package_name' => $package->name,
                'add_service_date' => $serviceDate,
                'add_time_slot' => $timeSlot,
                'add_price' => $price,
                'add_schedule_id' => $lockResult['schedule_id'] ?? 0,
                'apply_reason' => $reason,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 记录日志
            OrderChangeLog::addLog(
                $orderId,
                OrderChangeLog::RELATED_TYPE_CHANGE,
                $change->id,
                OrderChangeLog::OPERATOR_USER,
                $userId,
                'apply',
                0,
                self::STATUS_PENDING,
                "申请加项：新增{$staff->name}（{$package->name}），价格{$price}元"
            );

            // 更新订单变更标记
            Order::where('id', $orderId)->update([
                'has_changed' => 1,
                'update_time' => time(),
            ]);

            Db::commit();
            return [true, '加项申请已提交，请等待审核', $change];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '申请失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 审核变更申请
     * @param int $changeId
     * @param int $adminId
     * @param bool $approved 是否通过
     * @param string $remark 审核备注
     * @param string $rejectReason 拒绝原因
     * @return array [bool $success, string $message]
     */
    public static function auditChange(
        int $changeId,
        int $adminId,
        bool $approved,
        string $remark = '',
        string $rejectReason = ''
    ): array {
        $change = self::find($changeId);
        if (!$change) {
            return [false, '变更记录不存在'];
        }

        if ($change->change_status != self::STATUS_PENDING) {
            return [false, '该变更申请已处理'];
        }

        Db::startTrans();
        try {
            $beforeStatus = $change->change_status;

            if ($approved) {
                $change->change_status = self::STATUS_APPROVED;
                $change->audit_remark = $remark;
                $logContent = '审核通过' . ($remark ? "：{$remark}" : '');
            } else {
                $change->change_status = self::STATUS_REJECTED;
                $change->reject_reason = $rejectReason;
                $change->audit_remark = $remark;
                $logContent = '审核拒绝：' . $rejectReason;

                // 释放临时锁定的档期
                if ($change->change_type == self::TYPE_STAFF && $change->new_schedule_id > 0) {
                    Schedule::releaseLock($change->new_schedule_id);
                }
                if ($change->change_type == self::TYPE_ADD_ITEM && $change->add_schedule_id > 0) {
                    Schedule::releaseLock($change->add_schedule_id);
                }
            }

            $change->audit_admin_id = $adminId;
            $change->audit_time = time();
            $change->update_time = time();
            $change->save();

            // 记录日志
            OrderChangeLog::addLog(
                $change->order_id,
                OrderChangeLog::RELATED_TYPE_CHANGE,
                $changeId,
                OrderChangeLog::OPERATOR_ADMIN,
                $adminId,
                'audit',
                $beforeStatus,
                $change->change_status,
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
     * @notes 执行变更
     * @param int $changeId
     * @param int $adminId
     * @return array [bool $success, string $message]
     */
    public static function executeChange(int $changeId, int $adminId): array
    {
        $change = self::find($changeId);
        if (!$change) {
            return [false, '变更记录不存在'];
        }

        if ($change->change_status != self::STATUS_APPROVED) {
            return [false, '该变更申请未审核通过或已执行'];
        }

        Db::startTrans();
        try {
            $order = Order::find($change->order_id);
            $beforeStatus = $change->change_status;

            // 根据变更类型执行不同的操作
            switch ($change->change_type) {
                case self::TYPE_DATE:
                    // 改期：更新所有订单项的服务日期
                    $result = self::executeDateChange($change, $order);
                    break;
                case self::TYPE_STAFF:
                    // 换人：更新订单项的工作人员
                    $result = self::executeStaffChange($change);
                    break;
                case self::TYPE_ADD_ITEM:
                    // 加项：创建新的订单项
                    $result = self::executeAddItem($change, $order);
                    break;
                default:
                    return [false, '未知的变更类型'];
            }

            if (!$result['success']) {
                Db::rollback();
                return $result;
            }

            // 更新变更状态
            $change->change_status = self::STATUS_EXECUTED;
            $change->execute_time = time();
            $change->execute_admin_id = $adminId;
            $change->update_time = time();
            $change->save();

            // 更新订单变更次数
            Order::where('id', $change->order_id)->inc('change_count')->update([
                'update_time' => time(),
            ]);

            // 记录日志
            OrderChangeLog::addLog(
                $change->order_id,
                OrderChangeLog::RELATED_TYPE_CHANGE,
                $changeId,
                OrderChangeLog::OPERATOR_ADMIN,
                $adminId,
                'execute',
                $beforeStatus,
                self::STATUS_EXECUTED,
                '执行变更完成'
            );

            Db::commit();
            return [true, '变更执行成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '执行失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 执行改期变更
     */
    private static function executeDateChange(OrderChange $change, Order $order): array
    {
        $items = OrderItem::where('order_id', $order->id)->select();

        foreach ($items as $item) {
            // 释放原档期
            if ($item->schedule_id > 0) {
                Schedule::releaseLock($item->schedule_id);
            }

            // 锁定新档期
            $lockResult = Schedule::confirmBooking(
                $item->staff_id,
                $change->new_service_date,
                $change->new_time_slot,
                $order->id,
                $order->user_id
            );

            // 更新订单项
            $item->service_date = $change->new_service_date;
            $item->time_slot = $change->new_time_slot;
            $item->schedule_id = $lockResult['schedule_id'] ?? 0;
            $item->update_time = time();
            $item->save();
        }

        // 更新订单服务日期
        $order->service_date = $change->new_service_date;
        $order->service_time_slot = $change->new_time_slot;
        $order->update_time = time();
        $order->save();

        return ['success' => true];
    }

    /**
     * @notes 执行换人变更
     */
    private static function executeStaffChange(OrderChange $change): array
    {
        $orderItem = OrderItem::find($change->order_item_id);
        if (!$orderItem) {
            return ['success' => false, 'message' => '订单项不存在'];
        }

        // 释放原档期
        if ($orderItem->schedule_id > 0) {
            Schedule::releaseLock($orderItem->schedule_id);
        }

        // 确认新档期（从临时锁定转为正式预约）
        if ($change->new_schedule_id > 0) {
            Schedule::where('id', $change->new_schedule_id)->update([
                'status' => Schedule::STATUS_BOOKED,
                'update_time' => time(),
            ]);
        }

        // 更新订单项
        $orderItem->original_staff_id = $orderItem->staff_id;
        $orderItem->original_price = $orderItem->price;
        $orderItem->staff_id = $change->new_staff_id;
        $orderItem->staff_name = $change->new_staff_name;
        $orderItem->price = $change->new_price;
        $orderItem->subtotal = $change->new_price * ($orderItem->quantity ?: 1);
        $orderItem->schedule_id = $change->new_schedule_id;
        $orderItem->is_changed = 1;
        $orderItem->change_id = $change->id;
        $orderItem->update_time = time();
        $orderItem->save();

        // 更新订单总金额
        $order = Order::find($change->order_id);
        $newTotalAmount = OrderItem::where('order_id', $order->id)->sum('subtotal');
        $order->total_amount = $newTotalAmount;
        $order->pay_amount = $newTotalAmount - $order->discount_amount - $order->coupon_amount;
        $order->update_time = time();
        $order->save();

        return ['success' => true];
    }

    /**
     * @notes 执行加项变更
     */
    private static function executeAddItem(OrderChange $change, Order $order): array
    {
        // 确认档期
        if ($change->add_schedule_id > 0) {
            Schedule::where('id', $change->add_schedule_id)->update([
                'status' => Schedule::STATUS_BOOKED,
                'update_time' => time(),
            ]);
        }

        // 创建新订单项
        $newItem = OrderItem::create([
            'order_id' => $order->id,
            'staff_id' => $change->add_staff_id,
            'package_id' => $change->add_package_id,
            'schedule_id' => $change->add_schedule_id,
            'service_date' => $change->add_service_date,
            'time_slot' => $change->add_time_slot,
            'staff_name' => $change->add_staff_name,
            'package_name' => $change->add_package_name,
            'price' => $change->add_price,
            'quantity' => 1,
            'subtotal' => $change->add_price,
            'is_changed' => 1,
            'change_id' => $change->id,
            'create_time' => time(),
            'update_time' => time(),
        ]);

        // 更新变更记录
        $change->add_order_item_id = $newItem->id;
        $change->save();

        // 更新订单总金额
        $newTotalAmount = OrderItem::where('order_id', $order->id)->sum('subtotal');
        $order->total_amount = $newTotalAmount;
        $order->pay_amount = $newTotalAmount - $order->discount_amount - $order->coupon_amount;
        $order->update_time = time();
        $order->save();

        return ['success' => true];
    }

    /**
     * @notes 取消变更申请
     * @param int $changeId
     * @param int $userId
     * @return array [bool $success, string $message]
     */
    public static function cancelChange(int $changeId, int $userId): array
    {
        $change = self::find($changeId);
        if (!$change) {
            return [false, '变更记录不存在'];
        }

        if ($change->user_id != $userId) {
            return [false, '无权操作此变更申请'];
        }

        if ($change->change_status != self::STATUS_PENDING) {
            return [false, '只能取消待审核的申请'];
        }

        Db::startTrans();
        try {
            $beforeStatus = $change->change_status;
            $change->change_status = self::STATUS_CANCELLED;
            $change->update_time = time();
            $change->save();

            // 释放临时锁定的档期
            if ($change->change_type == self::TYPE_STAFF && $change->new_schedule_id > 0) {
                Schedule::releaseLock($change->new_schedule_id);
            }
            if ($change->change_type == self::TYPE_ADD_ITEM && $change->add_schedule_id > 0) {
                Schedule::releaseLock($change->add_schedule_id);
            }

            // 记录日志
            OrderChangeLog::addLog(
                $change->order_id,
                OrderChangeLog::RELATED_TYPE_CHANGE,
                $changeId,
                OrderChangeLog::OPERATOR_USER,
                $userId,
                'cancel',
                $beforeStatus,
                self::STATUS_CANCELLED,
                '用户取消变更申请'
            );

            Db::commit();
            return [true, '已取消'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '取消失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 获取变更类型选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return [
            ['value' => self::TYPE_DATE, 'label' => '改期'],
            ['value' => self::TYPE_STAFF, 'label' => '换人'],
            ['value' => self::TYPE_ADD_ITEM, 'label' => '加项'],
        ];
    }

    /**
     * @notes 获取变更状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            ['value' => self::STATUS_PENDING, 'label' => '待审核'],
            ['value' => self::STATUS_APPROVED, 'label' => '审核通过'],
            ['value' => self::STATUS_REJECTED, 'label' => '审核拒绝'],
            ['value' => self::STATUS_EXECUTED, 'label' => '已执行'],
            ['value' => self::STATUS_CANCELLED, 'label' => '已取消'],
        ];
    }
}
