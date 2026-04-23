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

    public const PLACEHOLDER_TEMPLATE_PREFIX = 'TEMPLATE_ID_';

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
    const SCENE_WAITLIST_RELEASE = 'waitlist_release'; // 候补释放
    const SCENE_WAITLIST_EXPIRED = 'waitlist_expired'; // 候补失效

    /**
     * 当前仍保留的客户订阅场景。
     */
    private const ACTIVE_SCENE_OPTIONS = [
        ['value' => self::SCENE_ORDER_CONFIRM, 'label' => '订单确认通知'],
        ['value' => self::SCENE_SCHEDULE_REMIND, 'label' => '服务提醒通知'],
        ['value' => self::SCENE_REFUND_RESULT, 'label' => '退款结果通知'],
        ['value' => self::SCENE_TICKET_UPDATE, 'label' => '工单进度通知'],
        ['value' => self::SCENE_WAITLIST_RELEASE, 'label' => '候补释放通知'],
        ['value' => self::SCENE_WAITLIST_EXPIRED, 'label' => '候补失效通知'],
    ];

    /**
     * 可独立维护模板的场景。
     * 候补失效复用候补释放模板，因此不单独开放模板维护。
     */
    private const TEMPLATE_SCENE_OPTIONS = [
        ['value' => self::SCENE_ORDER_CONFIRM, 'label' => '订单确认通知'],
        ['value' => self::SCENE_SCHEDULE_REMIND, 'label' => '服务提醒通知'],
        ['value' => self::SCENE_REFUND_RESULT, 'label' => '退款结果通知'],
        ['value' => self::SCENE_TICKET_UPDATE, 'label' => '工单进度通知'],
        ['value' => self::SCENE_WAITLIST_RELEASE, 'label' => '候补状态通知'],
    ];

    /**
     * 允许共用模板的场景组。
     */
    private const SHARED_TEMPLATE_SCENE_GROUPS = [
        [self::SCENE_WAITLIST_RELEASE, self::SCENE_WAITLIST_EXPIRED],
    ];

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
            self::SCENE_WAITLIST_RELEASE => '候补释放通知',
            self::SCENE_WAITLIST_EXPIRED => '候补失效通知',
        ];
        return $map[$scene] ?? '未知场景';
    }

    /**
     * @notes 获取所有场景列表
     * @return array
     */
    public static function getSceneList(): array
    {
        return self::ACTIVE_SCENE_OPTIONS;
    }

    /**
     * @notes 获取活跃场景值列表
     * @return array
     */
    public static function getActiveSceneValues(): array
    {
        return array_column(self::ACTIVE_SCENE_OPTIONS, 'value');
    }

    /**
     * @notes 判断是否为活跃订阅场景
     * @param string $scene
     * @return bool
     */
    public static function isActiveScene(string $scene): bool
    {
        return in_array($scene, self::getActiveSceneValues(), true);
    }

    /**
     * @notes 获取可独立维护模板的场景列表
     * @return array
     */
    public static function getTemplateSceneList(): array
    {
        return self::TEMPLATE_SCENE_OPTIONS;
    }

    /**
     * @notes 获取可独立维护模板的场景值列表
     * @return array
     */
    public static function getTemplateSceneValues(): array
    {
        return array_column(self::TEMPLATE_SCENE_OPTIONS, 'value');
    }

    /**
     * @notes 判断是否允许独立维护模板
     * @param string $scene
     * @return bool
     */
    public static function isTemplateScene(string $scene): bool
    {
        return in_array($scene, self::getTemplateSceneValues(), true);
    }

    /**
     * @notes 判断模板是否可绑定到目标场景
     * @param string $templateScene
     * @param string $targetScene
     * @return bool
     */
    public static function canTemplateBindToScene(string $templateScene, string $targetScene): bool
    {
        if ($templateScene === $targetScene) {
            return true;
        }

        foreach (self::SHARED_TEMPLATE_SCENE_GROUPS as $sceneGroup) {
            if (in_array($templateScene, $sceneGroup, true) && in_array($targetScene, $sceneGroup, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @notes 获取场景可用参数
     * @param string $scene
     * @return array
     */
    public static function getSceneAvailableParams(string $scene): array
    {
        $map = [
            self::SCENE_ORDER_CONFIRM => [
                ['key' => 'order_sn', 'label' => '订单编号', 'desc' => '系统生成的订单编号'],
                ['key' => 'status_text', 'label' => '确认状态', 'desc' => '订单确认状态文案'],
                ['key' => 'pay_amount', 'label' => '订单金额', 'desc' => '订单支付金额'],
                ['key' => 'service_date', 'label' => '服务日期', 'desc' => '预约服务日期'],
                ['key' => 'service_name', 'label' => '服务名称', 'desc' => '订单中的服务项目名称'],
            ],
            self::SCENE_SCHEDULE_REMIND => [
                ['key' => 'service_name', 'label' => '服务内容', 'desc' => '待提醒的服务项目名称'],
                ['key' => 'service_date', 'label' => '服务时间', 'desc' => '预约服务日期时间'],
                ['key' => 'address', 'label' => '服务地点', 'desc' => '服务执行地点'],
                ['key' => 'staff_name', 'label' => '服务人员', 'desc' => '当前安排的服务人员'],
            ],
            self::SCENE_REFUND_RESULT => [
                ['key' => 'order_sn', 'label' => '订单编号', 'desc' => '退款关联的订单编号'],
                ['key' => 'refund_amount', 'label' => '退款金额', 'desc' => '本次退款金额'],
                ['key' => 'status_text', 'label' => '退款状态', 'desc' => '退款审核结果文案'],
                ['key' => 'reason', 'label' => '退款原因', 'desc' => '退款或驳回原因'],
            ],
            self::SCENE_TICKET_UPDATE => [
                ['key' => 'ticket_sn', 'label' => '工单编号', 'desc' => '售后工单编号'],
                ['key' => 'status_text', 'label' => '工单状态', 'desc' => '当前工单状态文案'],
                ['key' => 'handle_note', 'label' => '处理说明', 'desc' => '本次处理备注'],
                ['key' => 'update_time', 'label' => '更新时间', 'desc' => '工单最近更新时间'],
            ],
            self::SCENE_WAITLIST_RELEASE => [
                ['key' => 'staff_name', 'label' => '服务人员', 'desc' => '释放档期对应的服务人员'],
                ['key' => 'schedule_date', 'label' => '档期日期', 'desc' => '释放的预约日期'],
                ['key' => 'package_name', 'label' => '套餐名称', 'desc' => '候补关联套餐名称'],
                ['key' => 'status_text', 'label' => '状态说明', 'desc' => '候补状态说明文案'],
            ],
            self::SCENE_WAITLIST_EXPIRED => [
                ['key' => 'staff_name', 'label' => '服务人员', 'desc' => '候补对应的服务人员'],
                ['key' => 'schedule_date', 'label' => '档期日期', 'desc' => '失效的预约日期'],
                ['key' => 'package_name', 'label' => '套餐名称', 'desc' => '候补关联套餐名称'],
                ['key' => 'status_text', 'label' => '状态说明', 'desc' => '候补状态说明文案'],
            ],
        ];

        return $map[$scene] ?? [];
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
     * @notes 是否为占位模板ID
     * @param string|null $templateId
     * @return bool
     */
    public static function isPlaceholderTemplateId(?string $templateId): bool
    {
        $templateId = trim((string) $templateId);
        return $templateId !== '' && str_starts_with($templateId, self::PLACEHOLDER_TEMPLATE_PREFIX);
    }

    /**
     * @notes 是否已配置真实模板ID
     * @param string|null $templateId
     * @return bool
     */
    public static function isUsableTemplateId(?string $templateId): bool
    {
        $templateId = trim((string) $templateId);
        return $templateId !== '' && !self::isPlaceholderTemplateId($templateId);
    }

    /**
     * @notes 获取模板配置状态
     * @param string|null $templateId
     * @return string
     */
    public static function getConfigStatus(?string $templateId): string
    {
        $templateId = trim((string) $templateId);
        if ($templateId === '') {
            return 'unbound';
        }

        return self::isPlaceholderTemplateId($templateId) ? 'placeholder' : 'configured';
    }

    /**
     * @notes 获取模板配置状态文案
     * @param string|null $templateId
     * @return string
     */
    public static function getConfigStatusDesc(?string $templateId): string
    {
        return match (self::getConfigStatus($templateId)) {
            'configured' => '已配置真实模板',
            'placeholder' => '待填写真实模板ID',
            default => '未绑定模板',
        };
    }

    /**
     * @notes 获取启用的模板列表
     * @return array
     */
    public static function getEnabledTemplates(): array
    {
        return self::where('status', self::STATUS_ENABLED)
            ->whereIn('scene', self::getTemplateSceneValues())
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
