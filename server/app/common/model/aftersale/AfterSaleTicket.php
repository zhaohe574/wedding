<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 售后工单模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\aftersale;

use app\common\model\BaseModel;
use app\common\model\order\Order;
use app\common\model\user\User;
use think\facade\Db;

/**
 * 售后工单模型
 * Class AfterSaleTicket
 * @package app\common\model\aftersale
 */
class AfterSaleTicket extends BaseModel
{
    protected $name = 'after_sale_ticket';

    // 工单类型
    const TYPE_COMPLAINT = 1;   // 投诉
    const TYPE_CONSULT = 2;     // 咨询
    const TYPE_AFTER_SALE = 3;  // 售后
    const TYPE_SUGGEST = 4;     // 建议
    const TYPE_OTHER = 5;       // 其他

    // 优先级
    const PRIORITY_LOW = 1;     // 低
    const PRIORITY_MEDIUM = 2;  // 中
    const PRIORITY_HIGH = 3;    // 高
    const PRIORITY_URGENT = 4;  // 紧急

    // 工单状态
    const STATUS_PENDING = 0;       // 待分配
    const STATUS_PROCESSING = 1;    // 处理中
    const STATUS_CONFIRMING = 2;    // 待确认
    const STATUS_COMPLETED = 3;     // 已完成
    const STATUS_CLOSED = 4;        // 已关闭
    const STATUS_CANCELLED = 5;     // 已取消

    // 来源
    const SOURCE_MINIAPP = 1;   // 小程序
    const SOURCE_ADMIN = 2;     // 后台
    const SOURCE_PHONE = 3;     // 电话

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
     * @notes 关联处理人
     * @return \think\model\relation\BelongsTo
     */
    public function assignAdmin()
    {
        return $this->belongsTo('app\common\model\auth\Admin', 'assign_admin_id', 'id')
            ->field('id,name,avatar');
    }

    /**
     * @notes 工单类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getTypeDescAttr($value, $data): string
    {
        $map = [
            self::TYPE_COMPLAINT => '投诉',
            self::TYPE_CONSULT => '咨询',
            self::TYPE_AFTER_SALE => '售后',
            self::TYPE_SUGGEST => '建议',
            self::TYPE_OTHER => '其他',
        ];
        return $map[$data['type']] ?? '未知';
    }

    /**
     * @notes 优先级描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPriorityDescAttr($value, $data): string
    {
        $map = [
            self::PRIORITY_LOW => '低',
            self::PRIORITY_MEDIUM => '中',
            self::PRIORITY_HIGH => '高',
            self::PRIORITY_URGENT => '紧急',
        ];
        return $map[$data['priority']] ?? '未知';
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
            self::STATUS_PENDING => '待分配',
            self::STATUS_PROCESSING => '处理中',
            self::STATUS_CONFIRMING => '待确认',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_CLOSED => '已关闭',
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
     * @notes 生成工单编号
     * @return string
     */
    public static function generateTicketSn(): string
    {
        return 'TK' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @notes 创建工单
     * @param array $data
     * @return array [bool $success, string $message, AfterSaleTicket|null $ticket]
     */
    public static function createTicket(array $data): array
    {
        Db::startTrans();
        try {
            // 计算截止时间（根据优先级）
            $deadlineHours = [
                self::PRIORITY_LOW => 72,
                self::PRIORITY_MEDIUM => 48,
                self::PRIORITY_HIGH => 24,
                self::PRIORITY_URGENT => 4,
            ];
            $priority = $data['priority'] ?? self::PRIORITY_MEDIUM;
            $deadline = time() + ($deadlineHours[$priority] ?? 48) * 3600;

            $ticket = self::create([
                'ticket_sn' => self::generateTicketSn(),
                'order_id' => $data['order_id'] ?? 0,
                'user_id' => $data['user_id'],
                'type' => $data['type'] ?? self::TYPE_AFTER_SALE,
                'priority' => $priority,
                'title' => $data['title'],
                'content' => $data['content'] ?? '',
                'images' => $data['images'] ?? [],
                'contact_name' => $data['contact_name'] ?? '',
                'contact_phone' => $data['contact_phone'] ?? '',
                'status' => self::STATUS_PENDING,
                'deadline' => $deadline,
                'source' => $data['source'] ?? self::SOURCE_MINIAPP,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 记录日志
            AfterSaleTicketLog::addLog($ticket->id, 1, $data['user_id'], 'create', 0, self::STATUS_PENDING, '创建工单');

            Db::commit();
            return [true, '工单创建成功', $ticket];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '创建失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 分配工单
     * @param int $ticketId
     * @param int $adminId
     * @param int $operatorId
     * @return array [bool $success, string $message]
     */
    public static function assignTicket(int $ticketId, int $adminId, int $operatorId): array
    {
        Db::startTrans();
        try {
            $ticket = self::find($ticketId);
            if (!$ticket) {
                return [false, '工单不存在'];
            }

            if ($ticket->status != self::STATUS_PENDING) {
                return [false, '当前状态不可分配'];
            }

            $oldStatus = $ticket->status;
            $ticket->assign_admin_id = $adminId;
            $ticket->assign_time = time();
            $ticket->status = self::STATUS_PROCESSING;
            $ticket->update_time = time();
            $ticket->save();

            // 记录日志
            AfterSaleTicketLog::addLog($ticketId, 2, $operatorId, 'assign', $oldStatus, self::STATUS_PROCESSING, '分配工单给管理员ID:' . $adminId);

            Db::commit();
            return [true, '分配成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '分配失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 处理工单
     * @param int $ticketId
     * @param int $adminId
     * @param string $result
     * @return array [bool $success, string $message]
     */
    public static function handleTicket(int $ticketId, int $adminId, string $result): array
    {
        Db::startTrans();
        try {
            $ticket = self::find($ticketId);
            if (!$ticket) {
                return [false, '工单不存在'];
            }

            if ($ticket->status != self::STATUS_PROCESSING) {
                return [false, '当前状态不可处理'];
            }

            $oldStatus = $ticket->status;
            $ticket->handle_result = $result;
            $ticket->handle_time = time();
            $ticket->status = self::STATUS_CONFIRMING;
            $ticket->update_time = time();
            $ticket->save();

            // 记录日志
            AfterSaleTicketLog::addLog($ticketId, 2, $adminId, 'handle', $oldStatus, self::STATUS_CONFIRMING, '处理工单：' . $result);

            Db::commit();
            return [true, '处理成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '处理失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 用户确认完成
     * @param int $ticketId
     * @param int $userId
     * @param int $satisfaction
     * @param string $remark
     * @return array [bool $success, string $message]
     */
    public static function confirmComplete(int $ticketId, int $userId, int $satisfaction = 5, string $remark = ''): array
    {
        Db::startTrans();
        try {
            $ticket = self::find($ticketId);
            if (!$ticket) {
                return [false, '工单不存在'];
            }

            if ($ticket->user_id != $userId) {
                return [false, '无权操作此工单'];
            }

            if ($ticket->status != self::STATUS_CONFIRMING) {
                return [false, '当前状态不可确认'];
            }

            $oldStatus = $ticket->status;
            $ticket->status = self::STATUS_COMPLETED;
            $ticket->satisfaction = $satisfaction;
            $ticket->satisfaction_remark = $remark;
            $ticket->update_time = time();
            $ticket->save();

            // 记录日志
            AfterSaleTicketLog::addLog($ticketId, 1, $userId, 'confirm', $oldStatus, self::STATUS_COMPLETED, '用户确认完成，满意度：' . $satisfaction . '星');

            Db::commit();
            return [true, '确认成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '确认失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 关闭工单
     * @param int $ticketId
     * @param int $adminId
     * @param string $reason
     * @return array [bool $success, string $message]
     */
    public static function closeTicket(int $ticketId, int $adminId, string $reason): array
    {
        Db::startTrans();
        try {
            $ticket = self::find($ticketId);
            if (!$ticket) {
                return [false, '工单不存在'];
            }

            if ($ticket->status == self::STATUS_COMPLETED || $ticket->status == self::STATUS_CLOSED) {
                return [false, '当前状态不可关闭'];
            }

            $oldStatus = $ticket->status;
            $ticket->status = self::STATUS_CLOSED;
            $ticket->close_reason = $reason;
            $ticket->close_time = time();
            $ticket->update_time = time();
            $ticket->save();

            // 记录日志
            AfterSaleTicketLog::addLog($ticketId, 2, $adminId, 'close', $oldStatus, self::STATUS_CLOSED, '关闭工单：' . $reason);

            Db::commit();
            return [true, '关闭成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '关闭失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 升级工单
     * @param int $ticketId
     * @param int $operatorId
     * @return array [bool $success, string $message]
     */
    public static function escalateTicket(int $ticketId, int $operatorId): array
    {
        Db::startTrans();
        try {
            $ticket = self::find($ticketId);
            if (!$ticket) {
                return [false, '工单不存在'];
            }

            // 升级优先级
            $newPriority = min($ticket->priority + 1, self::PRIORITY_URGENT);
            $ticket->priority = $newPriority;
            $ticket->escalate_level = $ticket->escalate_level + 1;
            $ticket->escalate_time = time();
            $ticket->update_time = time();
            $ticket->save();

            // 记录日志
            AfterSaleTicketLog::addLog($ticketId, 3, $operatorId, 'escalate', $ticket->status, $ticket->status, '工单升级，当前优先级：' . $newPriority);

            Db::commit();
            return [true, '升级成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '升级失败：' . $e->getMessage()];
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
            'processing' => self::where('status', self::STATUS_PROCESSING)->count(),
            'completed' => self::where('status', self::STATUS_COMPLETED)->count(),
            'today_new' => self::where('create_time', '>=', $today)->where('create_time', '<', $todayEnd)->count(),
            'overtime' => self::where('is_overtime', 1)->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CLOSED, self::STATUS_CANCELLED])->count(),
        ];
    }
}
