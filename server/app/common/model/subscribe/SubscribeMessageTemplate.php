<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订阅消息模板模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\subscribe;

use app\common\model\BaseModel;

/**
 * 订阅消息模板模型
 * Class SubscribeMessageTemplate
 * @package app\common\model\subscribe
 */
class SubscribeMessageTemplate extends BaseModel
{
    protected $name = 'subscribe_message_template';

    // 状态
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_ENABLED = 1;   // 启用

    // 场景类型
    const SCENE_ORDER_CREATE = 'order_create';       // 订单创建
    const SCENE_ORDER_PAID = 'order_paid';           // 支付成功
    const SCENE_ORDER_CONFIRM = 'order_confirm';     // 订单确认
    const SCENE_ORDER_COMPLETE = 'order_complete';   // 服务完成
    const SCENE_SCHEDULE_REMIND = 'schedule_remind'; // 档期提醒
    const SCENE_REFUND_RESULT = 'refund_result';     // 退款结果
    const SCENE_CALLBACK_REMIND = 'callback_remind'; // 回访提醒
    const SCENE_TICKET_UPDATE = 'ticket_update';     // 工单更新
    const SCENE_CHANGE_RESULT = 'change_result';     // 变更审核
    const SCENE_SCHEDULE_CHANGE = 'schedule_change'; // 档期变更

    /**
     * @notes 内容JSON自动转换
     * @param $value
     * @return array
     */
    public function getContentAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @notes 内容存储自动转JSON
     * @param $value
     * @return string
     */
    public function setContentAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusDescAttr($value, $data): string
    {
        return $data['status'] == self::STATUS_ENABLED ? '启用' : '禁用';
    }

    /**
     * @notes 获取场景描述
     * @param string|int $scene
     * @return string
     */
    public static function getSceneDesc(string|int $scene): string
    {
        $map = [
            self::SCENE_ORDER_CREATE => '订单创建通知',
            self::SCENE_ORDER_PAID => '支付成功通知',
            self::SCENE_ORDER_CONFIRM => '订单确认通知',
            self::SCENE_ORDER_COMPLETE => '服务完成通知',
            self::SCENE_SCHEDULE_REMIND => '档期提醒通知',
            self::SCENE_REFUND_RESULT => '退款结果通知',
            self::SCENE_CALLBACK_REMIND => '回访提醒通知',
            self::SCENE_TICKET_UPDATE => '工单进度通知',
            self::SCENE_CHANGE_RESULT => '变更审核通知',
            self::SCENE_SCHEDULE_CHANGE => '档期变更通知',
        ];
        return $map[$scene] ?? '未知场景';
    }

    /**
     * @notes 获取所有场景列表
     * @return array
     */
    public static function getSceneList(): array
    {
        return [
            ['value' => self::SCENE_ORDER_CREATE, 'label' => '订单创建通知'],
            ['value' => self::SCENE_ORDER_PAID, 'label' => '支付成功通知'],
            ['value' => self::SCENE_ORDER_CONFIRM, 'label' => '订单确认通知'],
            ['value' => self::SCENE_ORDER_COMPLETE, 'label' => '服务完成通知'],
            ['value' => self::SCENE_SCHEDULE_REMIND, 'label' => '档期提醒通知'],
            ['value' => self::SCENE_REFUND_RESULT, 'label' => '退款结果通知'],
            ['value' => self::SCENE_CALLBACK_REMIND, 'label' => '回访提醒通知'],
            ['value' => self::SCENE_TICKET_UPDATE, 'label' => '工单进度通知'],
            ['value' => self::SCENE_CHANGE_RESULT, 'label' => '变更审核通知'],
            ['value' => self::SCENE_SCHEDULE_CHANGE, 'label' => '档期变更通知'],
        ];
    }

    /**
     * @notes 根据场景获取模板
     * @param string $scene
     * @return SubscribeMessageTemplate|null
     */
    public static function getByScene(string $scene): ?SubscribeMessageTemplate
    {
        return self::where('scene', $scene)
            ->where('status', self::STATUS_ENABLED)
            ->find();
    }

    /**
     * @notes 获取启用的模板列表
     * @return array
     */
    public static function getEnabledTemplates(): array
    {
        return self::where('status', self::STATUS_ENABLED)
            ->order('sort', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 更新模板配置
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function updateTemplate(int $id, array $data): bool
    {
        $data['update_time'] = time();
        return self::where('id', $id)->update($data) > 0;
    }

    /**
     * @notes 添加新模板
     * @param array $data
     * @return SubscribeMessageTemplate
     */
    public static function addTemplate(array $data): SubscribeMessageTemplate
    {
        $data['create_time'] = time();
        $data['update_time'] = time();
        return self::create($data);
    }

    /**
     * @notes 切换状态
     * @param int $id
     * @return bool
     */
    public static function toggleStatus(int $id): bool
    {
        $template = self::find($id);
        if (!$template) {
            return false;
        }
        $newStatus = $template->status == self::STATUS_ENABLED ? self::STATUS_DISABLED : self::STATUS_ENABLED;
        return self::where('id', $id)->update([
            'status' => $newStatus,
            'update_time' => time(),
        ]) > 0;
    }
}
