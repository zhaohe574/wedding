<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\order;

use app\adminapi\lists\BaseAdminDataLists;
use app\adminapi\logic\order\OrderLogic;
use app\common\lists\ListsExcelInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\service\OrderRefundService;

/**
 * 订单列表
 * Class OrderLists
 * @package app\adminapi\lists\order
 */
class OrderLists extends BaseAdminDataLists implements ListsExcelInterface, ListsSearchInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['order_status', 'pay_status', 'pay_type', 'order_type', 'source'],
            '%like%' => ['order_sn', 'contact_name', 'contact_mobile'],
            'between_time' => ['create_time'],
            'between_date' => ['service_date'],
        ];
    }

    /**
     * @notes 额外搜索条件
     * @return array
     */
    public function queryWhere(): array
    {
        $where = [];
        $orderStatus = $this->params['order_status'] ?? '';

        // 未主动筛选状态时，默认排除已取消和用户已删除订单
        if ($orderStatus === '' || $orderStatus === null) {
            $where[] = ['order_status', '<>', Order::STATUS_CANCELLED];
            $where[] = ['order_status', '<>', Order::STATUS_USER_DELETED];
        }

        // 用户ID搜索
        if (!empty($this->params['user_id'])) {
            $where[] = ['user_id', '=', $this->params['user_id']];
        }

        // 用户昵称/手机号搜索
        if (!empty($this->params['user_keyword'])) {
            $userIds = \app\common\model\user\User::where('nickname|mobile', 'like', '%' . $this->params['user_keyword'] . '%')
                ->column('id');
            if (!empty($userIds)) {
                $where[] = ['user_id', 'in', $userIds];
            } else {
                $where[] = ['user_id', '=', 0]; // 无匹配用户
            }
        }

        // 金额范围
        if (!empty($this->params['min_amount'])) {
            $where[] = ['pay_amount', '>=', $this->params['min_amount']];
        }
        if (!empty($this->params['max_amount'])) {
            $where[] = ['pay_amount', '<=', $this->params['max_amount']];
        }

        if (($this->params['payment_mode'] ?? '') !== '') {
            if ($this->params['payment_mode'] === 'deposit') {
                $where[] = ['deposit_amount', '>', 0];
            }
            if ($this->params['payment_mode'] === 'full') {
                $where[] = ['deposit_amount', '=', 0];
            }
        }

        if (($this->params['deposit_paid'] ?? '') !== '') {
            $where[] = ['deposit_paid', '=', (int) $this->params['deposit_paid']];
        }

        if (($this->params['balance_paid'] ?? '') !== '') {
            $where[] = ['balance_paid', '=', (int) $this->params['balance_paid']];
        }

        return $where;
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $query = Order::with([
            'user' => function ($query) {
                $query->field('id, nickname, avatar, mobile');
            }
        ])
            ->where($this->searchWhere)
            ->where($this->queryWhere());

        $staffScopeId = $this->getStaffScopeId();
        if ($staffScopeId > 0) {
            $query->with([
                'items' => function ($itemQuery) use ($staffScopeId) {
                    $itemQuery->field('id, order_id, staff_id, staff_name, package_name, service_date, item_status, confirm_status, schedule_id, price, quantity, subtotal')
                        ->where('staff_id', $staffScopeId)
                        ->with(['addons' => function ($addonQuery) {
                            $addonQuery->field('id, order_item_id, addon_id, addon_name, price, quantity, subtotal');
                        }]);
                }
            ]);
            $query->whereIn('id', function ($subQuery) use ($staffScopeId) {
                $subQuery->name('order_item')
                    ->where('staff_id', $staffScopeId)
                    ->field('order_id');
            });
        }

        $lists = $query->order($this->sortOrder ?: ['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $pendingCounts = [];
        $orderItemTotals = [];
        if (!empty($lists)) {
            $orderIds = array_column($lists, 'id');
            $totalRows = OrderItem::whereIn('order_id', $orderIds)
                ->field('order_id, COUNT(*) as total_count')
                ->group('order_id')
                ->select()
                ->toArray();
            $orderItemTotals = array_column($totalRows, 'total_count', 'order_id');
            $pendingQuery = OrderItem::whereIn('order_id', $orderIds)
                ->where('confirm_status', 0)
                ->where('item_status', '<>', OrderItem::STATUS_CANCELLED);
            if ($staffScopeId > 0) {
                $pendingQuery->where('staff_id', $staffScopeId);
            }
            $pendingRows = $pendingQuery
                ->field('order_id, COUNT(*) as pending_confirm_count')
                ->group('order_id')
                ->select()
                ->toArray();
            $pendingCounts = array_column($pendingRows, 'pending_confirm_count', 'order_id');
        }

        foreach ($lists as &$item) {
            if ($staffScopeId > 0) {
                $item = $this->applyStaffVisibleOrderAmounts($item, $staffScopeId);
            }
            $item['order_status_desc'] = $this->getStatusDesc($item['order_status']);
            $item['pay_status_desc'] = $this->getPayStatusDesc($item['pay_status']);
            $payStatusDisplay = Order::buildPayStatusDisplayFromState($item);
            $item['pay_status_display_key'] = $payStatusDisplay['key'];
            $item['pay_status_display_desc'] = $payStatusDisplay['desc'];
            $item['pay_type_desc'] = $this->getPayTypeDesc($item['pay_type']);
            $item['payment_channel'] = Order::resolvePaymentChannel(
                $item['payment_channel'] ?? null,
                $item['pay_type'] ?? null,
                $item['pay_voucher'] ?? ''
            );
            $item['payment_channel_desc'] = Order::getPaymentChannelText((int)$item['payment_channel']);
            $item['source_desc'] = $this->getSourceDesc($item['source']);
            $item = array_merge($item, Order::buildPaymentSummaryFromState($item));
            $item['pending_confirm_count'] = (int)($pendingCounts[$item['id']] ?? 0);
            $item['has_pending_confirm'] = $item['pending_confirm_count'] > 0 ? 1 : 0;
            $item['can_staff_manage_payment'] = true;
            if ($staffScopeId > 0) {
                $visibleItemCount = count($item['items'] ?? []);
                $totalItemCount = (int)($orderItemTotals[$item['id']] ?? $visibleItemCount);
                $item['can_staff_manage_payment'] = $totalItemCount > 0 && $totalItemCount === $visibleItemCount;
            }
            $item = array_merge(
                $item,
                Order::buildPayTimeoutSummaryFromState(
                    (int)($item['order_status'] ?? Order::STATUS_PENDING_PAY),
                    (int)($item['pay_deadline_time'] ?? 0)
                ),
                Order::buildConfirmTimeoutSummaryFromState(
                    (int)($item['order_status'] ?? Order::STATUS_PENDING_CONFIRM),
                    (int)($item['confirm_deadline_time'] ?? 0)
                )
            );
            $item['refundable_amount'] = OrderRefundService::getRefundableAmount((int)$item['id']);
            if ($staffScopeId > 0) {
                $item['refundable_amount'] = min((float)$item['refundable_amount'], (float)($item['paid_amount'] ?? 0));
            }
            $item['can_admin_refund'] = $item['refundable_amount'] > 0
                && !empty($item['can_staff_manage_payment'])
                && !OrderRefundService::hasPendingRefund((int)$item['id']);

            if (
                (int)($item['payment_channel'] ?? Order::PAYMENT_CHANNEL_ONLINE) === Order::PAYMENT_CHANNEL_OFFLINE
                && !empty($item['pay_voucher'])
                && (int)($item['pay_voucher_status'] ?? -1) === Order::VOUCHER_STATUS_PENDING
            ) {
                $item['pay_deadline_time'] = 0;
                $item['pay_remain_seconds'] = 0;
                $item['pay_timeout_action_desc'] = '';
            }
        }

        return $lists;
    }

    /**
     * @notes 总数
     * @return int
     */
    public function count(): int
    {
        $query = Order::where($this->searchWhere)
            ->where($this->queryWhere());

        $staffScopeId = $this->getStaffScopeId();
        if ($staffScopeId > 0) {
            $query->whereIn('id', function ($subQuery) use ($staffScopeId) {
                $subQuery->name('order_item')
                    ->where('staff_id', $staffScopeId)
                    ->field('order_id');
            });
        }

        return $query->count();
    }

    /**
     * @notes 导出字段
     * @return array
     */
    public function setExcelFields(): array
    {
        return [
            'order_sn' => '订单编号',
            'user.nickname' => '用户昵称',
            'user.mobile' => '用户手机',
            'order_status_desc' => '订单状态',
            'pay_status_display_desc' => '支付状态',
            'pay_type_desc' => '支付方式',
            'payment_channel_desc' => '付款渠道',
            'payment_mode_desc' => '支付模式',
            'total_amount' => '订单总额',
            'discount_amount' => '优惠金额',
            'pay_amount' => '应付金额',
            'paid_amount' => '已付金额',
            'unpaid_amount' => '待付金额',
            'deposit_amount' => '定金金额',
            'balance_amount' => '尾款金额',
            'contact_name' => '联系人',
            'contact_mobile' => '联系电话',
            'service_date' => '服务日期',
            'create_time' => '创建时间',
        ];
    }

    /**
     * @notes 导出文件名
     * @return string
     */
    public function setFileName(): string
    {
        return '订单列表';
    }

    /**
     * @notes 获取订单状态描述
     * @param int $status
     * @return string
     */
    protected function getStatusDesc(int $status): string
    {
        return Order::getStatusText($status);
    }

    /**
     * @notes 获取支付状态描述
     * @param int $status
     * @return string
     */
    protected function getPayStatusDesc(int $status): string
    {
        $map = [
            Order::PAY_STATUS_UNPAID => '未支付',
            Order::PAY_STATUS_PAID => '已支付',
            Order::PAY_STATUS_PARTIAL_REFUND => '部分退款',
            Order::PAY_STATUS_FULL_REFUND => '全额退款',
        ];
        return $map[$status] ?? '未知';
    }

    /**
     * @notes 获取支付方式描述
     * @param int $type
     * @return string
     */
    protected function getPayTypeDesc(int $type): string
    {
        $map = [
            Order::PAY_WAY_NONE => '未支付',
            Order::PAY_WAY_WECHAT => '微信支付',
            Order::PAY_WAY_ALIPAY => '支付宝',
            Order::PAY_WAY_BALANCE => '余额支付',
            Order::PAY_WAY_OFFLINE => '线下支付',
            Order::PAY_WAY_COMBINATION => '组合支付',
        ];
        return $map[$type] ?? '未知';
    }

    /**
     * @notes 获取来源描述
     * @param int $source
     * @return string
     */
    protected function getSourceDesc(int $source): string
    {
        $map = [
            Order::SOURCE_MINIAPP => '小程序',
            Order::SOURCE_H5 => 'H5',
            Order::SOURCE_ADMIN => '后台',
        ];
        return $map[$source] ?? '未知';
    }

    /**
     * @notes 列表按服务人员视角重算金额
     */
    protected function applyStaffVisibleOrderAmounts(array $order, int $staffScopeId): array
    {
        $items = $order['items'] ?? [];
        $serviceAmount = 0.0;
        $addonAmount = 0.0;

        foreach ($items as $item) {
            if ((int)($item['staff_id'] ?? 0) !== $staffScopeId) {
                continue;
            }

            $subtotal = isset($item['subtotal']) ? (float)$item['subtotal'] : 0.0;
            if ($subtotal <= 0) {
                $price = (float)($item['price'] ?? 0);
                $quantity = max((int)($item['quantity'] ?? 1), 1);
                $subtotal = $price * $quantity;
            }

            $itemAddonAmount = 0.0;
            foreach (($item['addons'] ?? []) as $addon) {
                $itemAddonAmount += (float)($addon['subtotal'] ?? $addon['price'] ?? 0);
            }

            if ((int)($item['item_type'] ?? 1) === 1) {
                $serviceAmount += $subtotal;
            } else {
                $addonAmount += $subtotal;
            }
            $addonAmount += round($itemAddonAmount, 2);
        }

        $serviceAmount = round($serviceAmount, 2);
        $addonAmount = round($addonAmount, 2);
        $visibleTotal = round($serviceAmount + $addonAmount, 2);
        $originTotalAmount = (float)($order['total_amount'] ?? 0);
        $originPayAmount = (float)($order['pay_amount'] ?? 0);
        $originDepositAmount = (float)($order['deposit_amount'] ?? 0);
        $originBalanceAmount = (float)($order['balance_amount'] ?? 0);
        $discountTotal = (float)($order['discount_amount'] ?? 0);
        $paidTotal = (float)($order['paid_amount'] ?? 0);

        $staffDiscount = 0.0;
        if ($originTotalAmount > 0 && $visibleTotal > 0) {
            $staffDiscount = round($discountTotal * ($visibleTotal / $originTotalAmount), 2);
        }

        $staffPayAmount = round(max($visibleTotal - $staffDiscount, 0), 2);
        $staffPaidAmount = 0.0;
        if ($originPayAmount > 0 && $staffPayAmount > 0 && $paidTotal > 0) {
            $staffPaidAmount = round($paidTotal * ($staffPayAmount / $originPayAmount), 2);
            if ($staffPaidAmount > $staffPayAmount) {
                $staffPaidAmount = $staffPayAmount;
            }
        }

        $staffDepositAmount = 0.0;
        $staffBalanceAmount = 0.0;
        if ($originDepositAmount > 0 && $originPayAmount > 0 && $staffPayAmount > 0) {
            $staffDepositAmount = round($originDepositAmount * ($staffPayAmount / $originPayAmount), 2);
            if ($staffDepositAmount > $staffPayAmount) {
                $staffDepositAmount = $staffPayAmount;
            }
            $staffBalanceAmount = round(max($staffPayAmount - $staffDepositAmount, 0), 2);
        }

        $depositPaid = 0;
        $balancePaid = 0;
        if ($staffDepositAmount > 0) {
            if ((int)($order['deposit_paid'] ?? 0) === 1 && $staffPaidAmount >= $staffDepositAmount) {
                $depositPaid = 1;
            }
            if ((int)($order['balance_paid'] ?? 0) === 1 && $staffPaidAmount >= $staffPayAmount) {
                $balancePaid = 1;
            }
        }

        $order['service_amount'] = $serviceAmount;
        $order['addon_amount'] = $addonAmount;
        $order['total_amount'] = $visibleTotal;
        $order['discount_amount'] = $staffDiscount;
        $order['pay_amount'] = $staffPayAmount;
        $order['paid_amount'] = $staffPaidAmount;
        $order['unpaid_amount'] = round(max($staffPayAmount - $staffPaidAmount, 0), 2);
        $order['deposit_amount'] = $staffDepositAmount;
        $order['balance_amount'] = $staffBalanceAmount;
        $order['deposit_paid'] = $depositPaid;
        $order['balance_paid'] = $balancePaid;
        if ($staffPayAmount <= 0) {
            $order['pay_status'] = Order::PAY_STATUS_UNPAID;
        } elseif ($staffPaidAmount >= $staffPayAmount) {
            $order['pay_status'] = Order::PAY_STATUS_PAID;
        }

        return $order;
    }
}
