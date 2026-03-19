<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端订单变更业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\order\Order;
use app\common\model\order\OrderChange;
use app\common\model\order\OrderPause;
use app\common\model\order\OrderChangeLog;
use app\common\service\OrderNotificationService;

/**
 * 小程序端订单变更业务逻辑
 * Class OrderChangeLogic
 * @package app\api\logic
 */
class OrderChangeLogic extends BaseLogic
{
    /**
     * @notes 获取用户变更申请列表
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function getUserChanges(int $userId, array $params): array
    {
        $page = intval($params['page'] ?? 1);
        $pageSize = intval($params['page_size'] ?? 15);
        $changeType = $params['change_type'] ?? '';
        $changeStatus = $params['change_status'] ?? '';

        $query = OrderChange::where('user_id', $userId);

        if ($changeType !== '') {
            $query->where('change_type', $changeType);
        }
        if ($changeStatus !== '') {
            $query->where('change_status', $changeStatus);
        }

        $total = (clone $query)->count();
        $lists = $query->with([
            'order' => function ($q) {
                $q->field('id, order_sn, service_date, order_status');
            },
            'addonItems',
        ])
            ->order('id', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['change_type_desc'] = (new OrderChange(['change_type' => $item['change_type']]))->change_type_desc;
            $item['change_status_desc'] = (new OrderChange(['change_status' => $item['change_status']]))->change_status_desc;
            $item['addon_action_desc'] = (new OrderChange(['addon_action' => $item['addon_action'] ?? 0]))->addon_action_desc;
        }

        return [
            'lists' => $lists,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'last_page' => ceil($total / $pageSize),
        ];
    }

    /**
     * @notes 获取变更详情
     * @param int $changeId
     * @param int $userId
     * @return array|null
     */
    public static function getChangeDetail(int $changeId, int $userId): ?array
    {
        $change = OrderChange::with([
            'order' => function ($q) {
                $q->field('id, order_sn, user_id, service_date, order_status, pay_amount');
            },
            'orderItem',
            'addonItems',
            'oldStaff' => function ($q) {
                $q->field('id, name, avatar');
            },
            'newStaff' => function ($q) {
                $q->field('id, name, avatar');
            },
            'addStaff' => function ($q) {
                $q->field('id, name, avatar');
            },
        ])->where('id', $changeId)->where('user_id', $userId)->find();

        if (!$change) {
            return null;
        }

        $data = $change->toArray();
        $data['change_type_desc'] = $change->change_type_desc;
        $data['change_status_desc'] = $change->change_status_desc;
        $data['addon_action_desc'] = $change->addon_action_desc;

        return $data;
    }

    /**
     * @notes 检查订单是否可变更
     * @param int $orderId
     * @param int $userId
     * @return array
     */
    public static function checkCanChange(int $orderId, int $userId): array
    {
        $order = Order::find($orderId);
        if (!$order || $order->user_id != $userId) {
            return ['can_change' => false, 'message' => '订单不存在'];
        }

        [$canChange, $message] = OrderChange::checkCanChange($orderId);
        return [
            'can_change' => $canChange,
            'message' => $message,
            'order' => [
                'id' => $order->id,
                'order_sn' => $order->order_sn,
                'service_date' => $order->service_date,
                'order_status' => $order->order_status,
                'is_paused' => $order->is_paused,
            ],
        ];
    }

    /**
     * @notes 申请改期
     * @param int $userId
     * @param int $orderId
     * @param string $newDate
     * @param string $reason
     * @param array $attachImages
     * @return array
     */
    public static function applyDateChange(
        int $userId,
        int $orderId,
        string $newDate,
        string $reason = '',
        array $attachImages = []
    ): array {
        [$success, $message, $change] = OrderChange::applyDateChange(
            $userId, $orderId, $newDate, 0, $reason, $attachImages
        );

        if ($success && $change) {
            OrderNotificationService::notifyUserOnDateChangeApplied((int) $change->id);
            OrderNotificationService::notifyStaffOnDateChangeApplied((int) $change->id);
        }

        return [
            'success' => $success,
            'message' => $message,
            'change_id' => $change ? $change->id : 0,
        ];
    }

    /**
     * @notes 申请换人
     * @param int $userId
     * @param int $orderId
     * @param int $orderItemId
     * @param int $newStaffId
     * @param string $reason
     * @param array $attachImages
     * @return array
     */
    public static function applyStaffChange(
        int $userId,
        int $orderId,
        int $orderItemId,
        int $newStaffId,
        string $reason = '',
        array $attachImages = []
    ): array {
        return [
            'success' => false,
            'message' => self::getDeprecatedMessage(),
            'change_id' => 0,
            'price_diff' => 0,
        ];
    }

    /**
     * @notes 申请加项
     * @param int $userId
     * @param int $orderId
     * @param int $staffId
     * @param int $packageId
     * @param string $serviceDate
     * @param string $reason
     * @return array
     */
    public static function applyAddItem(
        int $userId,
        int $orderId,
        int $staffId,
        int $packageId,
        string $serviceDate,
        string $reason = ''
    ): array {
        [$success, $message, $change] = OrderChange::applyAddItem(
            $userId, $orderId, $staffId, $packageId, $serviceDate, 0, $reason
        );

        return [
            'success' => $success,
            'message' => $message,
            'change_id' => $change ? $change->id : 0,
            'add_price' => $change ? $change->add_price : 0,
        ];
    }

    /**
     * @notes 申请附加服务变更
     * @param int $userId
     * @param int $orderId
     * @param int $orderItemId
     * @param int $addonAction
     * @param array $addonIds
     * @param string $reason
     * @param array $attachImages
     * @return array
     */
    public static function applyAddonChange(
        int $userId,
        int $orderId,
        int $orderItemId,
        int $addonAction,
        array $addonIds,
        string $reason = '',
        array $attachImages = []
    ): array {
        [$success, $message, $change] = OrderChange::applyAddonChange(
            $userId,
            $orderId,
            $orderItemId,
            $addonAction,
            $addonIds,
            $reason,
            $attachImages
        );

        if ($success && $change) {
            OrderNotificationService::notifyUserOnAddonChangeApplied((int) $change->id);
            OrderNotificationService::notifyStaffOnAddonChangeApplied((int) $change->id);
        }

        return [
            'success' => $success,
            'message' => $message,
            'change_id' => $change ? $change->id : 0,
            'price_diff' => $change ? $change->price_diff : 0,
        ];
    }

    /**
     * @notes 取消变更申请
     * @param int $changeId
     * @param int $userId
     * @return array
     */
    public static function cancelChange(int $changeId, int $userId): array
    {
        $change = OrderChange::find($changeId);
        [$success, $message] = OrderChange::cancelChange($changeId, $userId);
        if ($success) {
            $changeType = (int)($change->change_type ?? 0);
            if ($changeType === OrderChange::TYPE_DATE) {
                OrderNotificationService::notifyUserOnDateChangeCancelled($changeId);
                OrderNotificationService::notifyStaffOnDateChangeCancelled($changeId);
            }
            if ($changeType === OrderChange::TYPE_ADDON) {
                OrderNotificationService::notifyUserOnAddonChangeCancelled($changeId);
                OrderNotificationService::notifyStaffOnAddonChangeCancelled($changeId);
            }
        }
        return ['success' => $success, 'message' => $message];
    }

    /**
     * @notes 申请暂停
     * @param int $userId
     * @param int $orderId
     * @param int $pauseType
     * @param string $reason
     * @param string $startDate
     * @param string $endDate
     * @param array $proofImages
     * @return array
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
        [$success, $message, $pause] = OrderPause::applyPause(
            $userId, $orderId, $pauseType, $reason, $startDate, $endDate, $proofImages
        );

        if ($success && $pause) {
            OrderNotificationService::notifyUserOnPauseApplied((int) $pause->id);
            OrderNotificationService::notifyStaffOnPauseApplied((int) $pause->id);
        }

        return [
            'success' => $success,
            'message' => $message,
            'pause_id' => $pause ? $pause->id : 0,
        ];
    }

    /**
     * @notes 取消暂停申请
     * @param int $pauseId
     * @param int $userId
     * @return array
     */
    public static function cancelPause(int $pauseId, int $userId): array
    {
        [$success, $message] = OrderPause::cancelPause($pauseId, $userId);
        if ($success) {
            OrderNotificationService::notifyUserOnPauseCancelled($pauseId);
            OrderNotificationService::notifyStaffOnPauseCancelled($pauseId);
        }
        return ['success' => $success, 'message' => $message];
    }

    /**
     * @notes 获取暂停详情
     * @param int $pauseId
     * @param int $userId
     * @return array|null
     */
    public static function getPauseDetail(int $pauseId, int $userId): ?array
    {
        $pause = OrderPause::with([
            'order' => function ($q) {
                $q->field('id, order_sn, service_date, order_status, pay_amount');
            },
        ])->where('id', $pauseId)->where('user_id', $userId)->find();

        if (!$pause) {
            return null;
        }

        $data = $pause->toArray();
        $data['pause_status_desc'] = $pause->pause_status_desc;
        $data['pause_type_desc'] = $pause->pause_type_desc;

        // 计算剩余天数
        if ($pause->pause_status == OrderPause::STATUS_PAUSED) {
            $remainDays = (strtotime($pause->pause_end_date) - time()) / 86400;
            $data['remain_days'] = max(0, ceil($remainDays));
        }

        return $data;
    }

    /**
     * @notes 获取用户暂停列表
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function getUserPauses(int $userId, array $params): array
    {
        $page = intval($params['page'] ?? 1);
        $pageSize = intval($params['page_size'] ?? 15);

        $query = OrderPause::where('user_id', $userId);

        if (!empty($params['pause_status'])) {
            $query->where('pause_status', $params['pause_status']);
        }

        $total = (clone $query)->count();
        $lists = $query->with([
            'order' => function ($q) {
                $q->field('id, order_sn, service_date, order_status');
            }
        ])
            ->order('id', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['pause_status_desc'] = (new OrderPause(['pause_status' => $item['pause_status']]))->pause_status_desc;
            $item['pause_type_desc'] = (new OrderPause(['pause_type' => $item['pause_type']]))->pause_type_desc;
        }

        return [
            'lists' => $lists,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'last_page' => ceil($total / $pageSize),
        ];
    }

    /**
     * @notes 获取变更类型选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return OrderChange::getTypeOptions();
    }

    /**
     * @notes 获取暂停类型选项
     * @return array
     */
    public static function getPauseTypeOptions(): array
    {
        return OrderPause::getTypeOptions();
    }

    /**
     * @notes 获取时间段选项
     * @return array
     */
    public static function getTimeSlotOptions(): array
    {
        return [
            ['value' => 0, 'label' => '全天'],
        ];
    }

    /**
     * @notes 已下线功能提示
     * @return string
     */
    protected static function getDeprecatedMessage(): string
    {
        return '功能已下线，请取消订单后重新下单';
    }
}
