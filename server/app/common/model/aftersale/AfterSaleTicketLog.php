<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 售后工单日志模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\aftersale;

use app\common\model\BaseModel;

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
        return self::where('ticket_id', $ticketId)
            ->order('create_time', 'asc')
            ->select()
            ->toArray();
    }
}
