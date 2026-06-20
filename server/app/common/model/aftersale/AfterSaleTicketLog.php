<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 售后工单日志模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\aftersale;

use app\common\model\auth\Admin;
use app\common\model\BaseModel;
use app\common\model\user\User;

/**
 * 售后工单日志模型
 * Class AfterSaleTicketLog
 * @package app\common\model\aftersale
 */
class AfterSaleTicketLog extends BaseModel
{
    protected $name = 'after_sale_ticket_log';

    // 操作人类型
    const OPERATOR_USER = 1;    // 用户
    const OPERATOR_ADMIN = 2;   // 管理员
    const OPERATOR_SYSTEM = 3;  // 系统

    /**
     * @notes 关联工单
     * @return \think\model\relation\BelongsTo
     */
    public function ticket()
    {
        return $this->belongsTo(AfterSaleTicket::class, 'ticket_id', 'id');
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
     * @notes 添加日志
     * @param int $ticketId
     * @param int $operatorType
     * @param int $operatorId
     * @param string $action
     * @param int $oldStatus
     * @param int $newStatus
     * @param string $content
     * @param array $images
     * @return bool
     */
    public static function addLog(int $ticketId, int $operatorType, int $operatorId, string $action, int $oldStatus, int $newStatus, string $content, array $images = []): bool
    {
        try {
            self::create([
                'ticket_id' => $ticketId,
                'operator_type' => $operatorType,
                'operator_id' => $operatorId,
                'action' => $action,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'content' => $content,
                'images' => $images,
                'create_time' => time(),
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @notes 获取工单日志列表
     * @param int $ticketId
     * @return array
     */
    public static function getLogsByTicket(int $ticketId): array
    {
        $logs = self::where('ticket_id', $ticketId)
            ->order('create_time', 'asc')
            ->select()
            ->toArray();

        foreach ($logs as &$log) {
            $operatorName = self::resolveOperatorName(
                (int)($log['operator_type'] ?? 0),
                (int)($log['operator_id'] ?? 0)
            );
            $log['operator_name'] = $operatorName;
            $log['content_display'] = self::resolveDisplayContent((string)($log['content'] ?? ''), $log);
        }
        unset($log);

        return $logs;
    }

    /**
     * @notes 解析操作人名称
     * @param int $operatorType
     * @param int $operatorId
     * @return string
     */
    private static function resolveOperatorName(int $operatorType, int $operatorId): string
    {
        if ($operatorType === self::OPERATOR_USER && $operatorId > 0) {
            $user = User::field('id,nickname,mobile')->find($operatorId);
            return trim((string)($user->nickname ?? $user->mobile ?? ''));
        }

        if ($operatorType === self::OPERATOR_ADMIN && $operatorId > 0) {
            $admin = Admin::field('id,name,account')->find($operatorId);
            return trim((string)($admin->name ?? $admin->account ?? ''));
        }

        if ($operatorType === self::OPERATOR_SYSTEM) {
            return '系统';
        }

        return '';
    }

    /**
     * @notes 兼容旧日志内容，将管理员ID转换为可读名称
     * @param string $content
     * @param array $log
     * @return string
     */
    private static function resolveDisplayContent(string $content, array $log): string
    {
        if (preg_match('/分配工单给管理员ID[:：]\s*(\d+)/u', $content, $matches)) {
            $adminId = (int)($matches[1] ?? 0);
            $admin = $adminId > 0 ? Admin::field('id,name,account')->find($adminId) : null;
            $adminName = trim((string)($admin->name ?? $admin->account ?? ''));
            if ($adminName !== '') {
                return '分配工单给' . $adminName;
            }
        }

        return $content !== '' ? $content : self::resolveFallbackContent($log);
    }

    /**
     * @notes 日志内容为空时生成兜底文案
     * @param array $log
     * @return string
     */
    private static function resolveFallbackContent(array $log): string
    {
        $action = (string)($log['action'] ?? '');
        $map = [
            'create' => '创建工单',
            'assign' => '分配工单',
            'auto_assign' => '自动分配工单',
            'handle' => '处理工单',
            'confirm' => '用户确认完成',
            'reject_confirm' => '用户拒绝处理结果',
            'close' => '关闭工单',
            'cancel' => '取消工单',
            'escalate' => '工单升级',
        ];

        return $map[$action] ?? '已更新进度';
    }
}
