<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端订单变更业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\order\Order;
use app\common\model\order\OrderChange;
use app\common\model\order\OrderTransfer;
use app\common\model\order\OrderPause;
use app\common\model\order\OrderChangeLog;

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
            }
        ])
            ->order('id', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['change_type_desc'] = (new OrderChange(['change_type' => $item['change_type']]))->change_type_desc;
            $item['change_status_desc'] = (new OrderChange(['change_status' => $item['change_status']]))->change_status_desc;
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
            'oldStaff' => function ($q) {
                $q->field('id, name, avatar, price');
            },
            'newStaff' => function ($q) {
                $q->field('id, name, avatar, price');
            },
            'addStaff' => function ($q) {
                $q->field('id, name, avatar, price');
            },
        ])->where('id', $changeId)->where('user_id', $userId)->find();

        if (!$change) {
            return null;
        }

        $data = $change->toArray();
        $data['change_type_desc'] = $change->change_type_desc;
        $data['change_status_desc'] = $change->change_status_desc;

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
     * @param int $newTimeSlot
     * @param string $reason
     * @param array $attachImages
     * @return array
     */
    public static function applyDateChange(
        int $userId,
        int $orderId,
        string $newDate,
        int $newTimeSlot,
        string $reason = '',
        array $attachImages = []
    ): array {
        [$success, $message, $change] = OrderChange::applyDateChange(
            $userId, $orderId, $newDate, $newTimeSlot, $reason, $attachImages
        );

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
        [$success, $message, $change] = OrderChange::applyStaffChange(
            $userId, $orderId, $orderItemId, $newStaffId, $reason, $attachImages
        );

        return [
            'success' => $success,
            'message' => $message,
            'change_id' => $change ? $change->id : 0,
            'price_diff' => $change ? $change->price_diff : 0,
        ];
    }

    /**
     * @notes 申请加项
     * @param int $userId
     * @param int $orderId
     * @param int $staffId
     * @param int $packageId
     * @param string $serviceDate
     * @param int $timeSlot
     * @param string $reason
     * @return array
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
        [$success, $message, $change] = OrderChange::applyAddItem(
            $userId, $orderId, $staffId, $packageId, $serviceDate, $timeSlot, $reason
        );

        return [
            'success' => $success,
            'message' => $message,
            'change_id' => $change ? $change->id : 0,
            'add_price' => $change ? $change->add_price : 0,
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
        [$success, $message] = OrderChange::cancelChange($changeId, $userId);
        return ['success' => $success, 'message' => $message];
    }

    /**
     * @notes 申请转让
     * @param int $userId
     * @param int $orderId
     * @param string $toUserName
     * @param string $toUserMobile
     * @param string $reason
     * @return array
     */
    public static function applyTransfer(
        int $userId,
        int $orderId,
        string $toUserName,
        string $toUserMobile,
        string $reason = ''
    ): array {
        [$success, $message, $transfer] = OrderTransfer::applyTransfer(
            $userId, $orderId, $toUserName, $toUserMobile, $reason
        );

        return [
            'success' => $success,
            'message' => $message,
            'transfer_id' => $transfer ? $transfer->id : 0,
        ];
    }

    /**
     * @notes 取消转让申请
     * @param int $transferId
     * @param int $userId
     * @return array
     */
    public static function cancelTransfer(int $transferId, int $userId): array
    {
        [$success, $message] = OrderTransfer::cancelTransfer($transferId, $userId);
        return ['success' => $success, 'message' => $message];
    }

    /**
     * @notes 接收转让
     * @param int $transferId
     * @param string $mobile
     * @param string $code
     * @return array
     */
    public static function acceptTransfer(int $transferId, string $mobile, string $code): array
    {
        [$success, $message] = OrderTransfer::acceptTransfer($transferId, $mobile, $code);
        return ['success' => $success, 'message' => $message];
    }

    /**
     * @notes 获取转让详情
     * @param int $transferId
     * @param int $userId
     * @return array|null
     */
    public static function getTransferDetail(int $transferId, int $userId): ?array
    {
        $transfer = OrderTransfer::with([
            'order' => function ($q) {
                $q->field('id, order_sn, service_date, order_status, pay_amount');
            },
        ])->where('id', $transferId)
            ->where(function ($query) use ($userId) {
                $query->where('from_user_id', $userId)->whereOr('to_user_id', $userId);
            })
            ->find();

        if (!$transfer) {
            return null;
        }

        $data = $transfer->toArray();
        $data['transfer_status_desc'] = $transfer->transfer_status_desc;
        $data['is_from_user'] = $transfer->from_user_id == $userId;
        // 隐藏验证码
        unset($data['accept_code']);

        return $data;
    }

    /**
     * @notes 获取用户转让列表
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function getUserTransfers(int $userId, array $params): array
    {
        $page = intval($params['page'] ?? 1);
        $pageSize = intval($params['page_size'] ?? 15);
        $type = $params['type'] ?? 'all'; // all, from, to

        $query = OrderTransfer::where(function ($q) use ($userId, $type) {
            if ($type == 'from') {
                $q->where('from_user_id', $userId);
            } elseif ($type == 'to') {
                $q->where('to_user_id', $userId);
            } else {
                $q->where('from_user_id', $userId)->whereOr('to_user_id', $userId);
            }
        });

        if (!empty($params['transfer_status'])) {
            $query->where('transfer_status', $params['transfer_status']);
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
            $item['transfer_status_desc'] = (new OrderTransfer(['transfer_status' => $item['transfer_status']]))->transfer_status_desc;
            $item['is_from_user'] = $item['from_user_id'] == $userId;
            unset($item['accept_code']);
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
            ['value' => 1, 'label' => '早礼'],
            ['value' => 2, 'label' => '午宴'],
            ['value' => 3, 'label' => '晚宴'],
        ];
    }
}
