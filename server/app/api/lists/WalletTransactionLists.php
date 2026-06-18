<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端钱包交易记录
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\lists;

use app\common\enum\PayEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\model\dynamic\ActivityPayment;
use app\common\model\dynamic\ActivityRefund;
use app\common\model\order\Order;
use app\common\model\order\Payment;
use app\common\model\order\Refund;
use app\common\model\recharge\RechargeOrder;
use app\common\model\user\UserAccountLog;
use think\facade\Db;

/**
 * 用户侧人民币交易记录聚合列表
 */
class WalletTransactionLists extends BaseApiDataLists
{
    private ?array $transactions = null;

    public function lists(): array
    {
        return array_slice($this->getTransactions(), $this->limitOffset, $this->limitLength);
    }

    public function count(): int
    {
        return count($this->getTransactions());
    }

    private function getTransactions(): array
    {
        if ($this->transactions !== null) {
            return $this->transactions;
        }

        $transactions = array_merge(
            $this->orderPaymentTransactions(),
            $this->orderRefundTransactions(),
            $this->rechargeTransactions(),
            $this->activityPaymentTransactions(),
            $this->activityRefundTransactions(),
            $this->accountAdjustmentTransactions()
        );

        $action = (int)($this->params['action'] ?? 0);
        if (in_array($action, [AccountLogEnum::INC, AccountLogEnum::DEC], true)) {
            $transactions = array_values(array_filter(
                $transactions,
                static fn (array $item): bool => (int)$item['direction'] === $action
            ));
        }

        usort($transactions, static function (array $left, array $right): int {
            $timeCompare = (int)$right['_sort_time'] <=> (int)$left['_sort_time'];
            if ($timeCompare !== 0) {
                return $timeCompare;
            }
            return strcmp((string)$right['id'], (string)$left['id']);
        });

        foreach ($transactions as &$item) {
            unset($item['_sort_time']);
        }

        $this->transactions = $transactions;
        return $this->transactions;
    }

    private function orderPaymentTransactions(): array
    {
        if (!$this->isTableReady(Payment::class)) {
            return [];
        }

        $rows = Payment::field('id,payment_sn,order_id,order_sn,user_id,pay_type,pay_way,pay_amount,pay_status,pay_time,create_time')
            ->where('user_id', $this->userId)
            ->whereIn('pay_status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->where('pay_amount', '>', 0)
            ->select()
            ->toArray();

        $items = [];
        foreach ($rows as $row) {
            $orderId = (int)($row['order_id'] ?? 0);
            $items[] = $this->makeTransaction([
                'id' => 'order_payment_' . (int)$row['id'],
                'title' => $this->getOrderPaymentTitle((int)($row['pay_type'] ?? 0)),
                'amount' => $row['pay_amount'] ?? 0,
                'direction' => AccountLogEnum::DEC,
                'pay_way_desc' => Payment::getPayWayText((int)($row['pay_way'] ?? 0)),
                'biz_sn' => (string)($row['payment_sn'] ?? $row['order_sn'] ?? ''),
                'timestamp' => $this->resolveTime($row['pay_time'] ?? 0, $row['create_time'] ?? 0),
                'target_type' => $orderId > 0 ? 'order' : 'none',
                'target_id' => $orderId,
                'target_url' => $orderId > 0 ? '/pages/order_detail/order_detail?id=' . $orderId : '',
            ]);
        }

        return $items;
    }

    private function orderRefundTransactions(): array
    {
        if (!$this->isTableReady(Refund::class)) {
            return [];
        }

        $rows = Refund::field('id,refund_sn,order_id,user_id,refund_amount,actual_refund_amount,refund_status,refund_time,update_time,create_time')
            ->where('user_id', $this->userId)
            ->where('refund_status', Refund::STATUS_COMPLETED)
            ->where('actual_refund_amount', '>', 0)
            ->select()
            ->toArray();

        $items = [];
        foreach ($rows as $row) {
            $orderId = (int)($row['order_id'] ?? 0);
            $items[] = $this->makeTransaction([
                'id' => 'order_refund_' . (int)$row['id'],
                'title' => '订单退款',
                'amount' => $row['actual_refund_amount'] ?: ($row['refund_amount'] ?? 0),
                'direction' => AccountLogEnum::INC,
                'pay_way_desc' => '退款',
                'biz_sn' => (string)($row['refund_sn'] ?? ''),
                'timestamp' => $this->resolveTime($row['refund_time'] ?? 0, $row['update_time'] ?? 0, $row['create_time'] ?? 0),
                'target_type' => $orderId > 0 ? 'order' : 'none',
                'target_id' => $orderId,
                'target_url' => $orderId > 0 ? '/pages/order_detail/order_detail?id=' . $orderId : '',
            ]);
        }

        return $items;
    }

    private function rechargeTransactions(): array
    {
        if (!$this->isTableReady(RechargeOrder::class)) {
            return [];
        }

        $rows = RechargeOrder::field('id,sn,user_id,pay_way,pay_status,pay_time,order_amount,create_time')
            ->where('user_id', $this->userId)
            ->where('pay_status', PayEnum::ISPAID)
            ->where('order_amount', '>', 0)
            ->select()
            ->toArray();

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->makeTransaction([
                'id' => 'recharge_' . (int)$row['id'],
                'title' => '充值到账',
                'amount' => $row['order_amount'] ?? 0,
                'direction' => AccountLogEnum::INC,
                'pay_way_desc' => PayEnum::getPayDesc((int)($row['pay_way'] ?? 0)) ?: '充值',
                'biz_sn' => (string)($row['sn'] ?? ''),
                'timestamp' => $this->resolveTime($row['pay_time'] ?? 0, $row['create_time'] ?? 0),
                'target_type' => 'none',
                'target_id' => 0,
                'target_url' => '',
            ]);
        }

        return $items;
    }

    private function activityPaymentTransactions(): array
    {
        if (!$this->isTableReady(ActivityPayment::class)) {
            return [];
        }

        $rows = ActivityPayment::field('id,payment_sn,registration_id,dynamic_id,user_id,pay_way,pay_amount,pay_status,pay_time,create_time')
            ->where('user_id', $this->userId)
            ->whereIn('pay_status', [ActivityPayment::STATUS_PAID, ActivityPayment::STATUS_REFUNDED])
            ->where('pay_amount', '>', 0)
            ->select()
            ->toArray();

        $items = [];
        foreach ($rows as $row) {
            $registrationId = (int)($row['registration_id'] ?? 0);
            $items[] = $this->makeTransaction([
                'id' => 'activity_payment_' . (int)$row['id'],
                'title' => (int)($row['pay_way'] ?? 0) === ActivityPayment::WAY_BALANCE ? '活动报名余额支付' : '活动报名支付',
                'amount' => $row['pay_amount'] ?? 0,
                'direction' => AccountLogEnum::DEC,
                'pay_way_desc' => ActivityPayment::getPayWayText((int)($row['pay_way'] ?? 0)),
                'biz_sn' => (string)($row['payment_sn'] ?? ''),
                'timestamp' => $this->resolveTime($row['pay_time'] ?? 0, $row['create_time'] ?? 0),
                'target_type' => $registrationId > 0 ? 'activity_registration' : 'none',
                'target_id' => $registrationId,
                'target_url' => $registrationId > 0 ? '/packages/pages/activity_registration/detail?id=' . $registrationId : '',
            ]);
        }

        return $items;
    }

    private function activityRefundTransactions(): array
    {
        if (!$this->isTableReady(ActivityRefund::class)) {
            return [];
        }

        $rows = ActivityRefund::field('id,refund_sn,registration_id,user_id,refund_amount,actual_refund_amount,refund_status,refund_time,update_time,create_time')
            ->where('user_id', $this->userId)
            ->where('refund_status', ActivityRefund::STATUS_COMPLETED)
            ->where('actual_refund_amount', '>', 0)
            ->select()
            ->toArray();

        $items = [];
        foreach ($rows as $row) {
            $registrationId = (int)($row['registration_id'] ?? 0);
            $items[] = $this->makeTransaction([
                'id' => 'activity_refund_' . (int)$row['id'],
                'title' => '活动报名退款',
                'amount' => $row['actual_refund_amount'] ?: ($row['refund_amount'] ?? 0),
                'direction' => AccountLogEnum::INC,
                'pay_way_desc' => '退款',
                'biz_sn' => (string)($row['refund_sn'] ?? ''),
                'timestamp' => $this->resolveTime($row['refund_time'] ?? 0, $row['update_time'] ?? 0, $row['create_time'] ?? 0),
                'target_type' => $registrationId > 0 ? 'activity_registration' : 'none',
                'target_id' => $registrationId,
                'target_url' => $registrationId > 0 ? '/packages/pages/activity_registration/detail?id=' . $registrationId : '',
            ]);
        }

        return $items;
    }

    private function accountAdjustmentTransactions(): array
    {
        if (!$this->isTableReady(UserAccountLog::class)) {
            return [];
        }

        $rows = UserAccountLog::field('id,sn,user_id,change_type,action,change_amount,left_amount,source_sn,remark,extra,create_time')
            ->where('user_id', $this->userId)
            ->whereIn('change_type', [
                AccountLogEnum::UM_INC_ADMIN,
                AccountLogEnum::UM_DEC_ADMIN,
                AccountLogEnum::UM_DEC_RECHARGE_REFUND,
            ])
            ->where('change_amount', '>', 0)
            ->select()
            ->toArray();

        $items = [];
        foreach ($rows as $row) {
            if ($this->isCoveredByBusinessTransaction($row)) {
                continue;
            }

            $extra = $this->decodeExtra($row['extra'] ?? '');
            [$targetType, $targetId, $targetUrl] = $this->resolveAccountLogTarget($extra, (string)($row['source_sn'] ?? ''));
            $direction = (int)($row['action'] ?? AccountLogEnum::INC) === AccountLogEnum::DEC
                ? AccountLogEnum::DEC
                : AccountLogEnum::INC;
            $title = trim((string)($row['remark'] ?? '')) ?: AccountLogEnum::getChangeTypeDesc((int)($row['change_type'] ?? 0));

            $items[] = $this->makeTransaction([
                'id' => 'account_log_' . (int)$row['id'],
                'title' => $title ?: '余额变动',
                'amount' => $row['change_amount'] ?? 0,
                'direction' => $direction,
                'pay_way_desc' => '余额',
                'biz_sn' => (string)($row['source_sn'] ?: $row['sn'] ?? ''),
                'timestamp' => $this->resolveTime($row['create_time'] ?? 0),
                'target_type' => $targetType,
                'target_id' => $targetId,
                'target_url' => $targetUrl,
            ]);
        }

        return $items;
    }

    private function makeTransaction(array $item): array
    {
        $amount = number_format(abs((float)($item['amount'] ?? 0)), 2, '.', '');
        $direction = (int)($item['direction'] ?? AccountLogEnum::INC) === AccountLogEnum::DEC
            ? AccountLogEnum::DEC
            : AccountLogEnum::INC;
        $timestamp = $this->resolveTime($item['timestamp'] ?? 0);
        $targetUrl = trim((string)($item['target_url'] ?? ''));

        return [
            'id' => (string)($item['id'] ?? ''),
            'title' => trim((string)($item['title'] ?? '交易记录')),
            'amount' => $amount,
            'amount_desc' => ($direction === AccountLogEnum::DEC ? '-' : '+') . $amount,
            'direction' => $direction,
            'pay_way_desc' => trim((string)($item['pay_way_desc'] ?? '')),
            'biz_sn' => trim((string)($item['biz_sn'] ?? '')),
            'create_time' => $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '',
            'target_type' => $targetUrl !== '' ? (string)($item['target_type'] ?? 'none') : 'none',
            'target_id' => $targetUrl !== '' ? (int)($item['target_id'] ?? 0) : 0,
            'target_url' => $targetUrl,
            '_sort_time' => $timestamp,
        ];
    }

    private function getOrderPaymentTitle(int $payType): string
    {
        $titles = [
            Payment::TYPE_DEPOSIT => '订单定金支付',
            Payment::TYPE_BALANCE => '订单尾款支付',
            Payment::TYPE_FULL => '订单全款支付',
        ];

        return $titles[$payType] ?? '订单支付';
    }

    private function isCoveredByBusinessTransaction(array $row): bool
    {
        $changeType = (int)($row['change_type'] ?? 0);
        $remark = (string)($row['remark'] ?? '');
        if ($changeType === AccountLogEnum::UM_DEC_ADMIN && strpos($remark, '订单余额支付') !== false) {
            return true;
        }
        return false;
    }

    private function resolveAccountLogTarget(array $extra, string $sourceSn): array
    {
        $orderId = (int)($extra['order_id'] ?? 0);
        if ($orderId <= 0 && $sourceSn !== '' && $this->isTableReady(Order::class)) {
            $orderId = (int)Order::where('user_id', $this->userId)
                ->where('order_sn', $sourceSn)
                ->value('id');
        }
        if ($orderId > 0) {
            return ['order', $orderId, '/pages/order_detail/order_detail?id=' . $orderId];
        }

        $registrationId = (int)($extra['registration_id'] ?? 0);
        if ($registrationId > 0) {
            return [
                'activity_registration',
                $registrationId,
                '/packages/pages/activity_registration/detail?id=' . $registrationId,
            ];
        }

        return ['none', 0, ''];
    }

    private function decodeExtra($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        $value = trim((string)$value);
        if ($value === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function resolveTime(...$values): int
    {
        foreach ($values as $value) {
            $time = (int)$value;
            if ($time > 0) {
                return $time;
            }
        }
        return 0;
    }

    private function isTableReady(string $modelClass): bool
    {
        static $ready = [];

        if (array_key_exists($modelClass, $ready)) {
            return $ready[$modelClass];
        }

        try {
            $model = new $modelClass();
            $table = addslashes((string)$model->getTable());
            $ready[$modelClass] = !empty(Db::query("SHOW TABLES LIKE '{$table}'"));
        } catch (\Throwable $e) {
            $ready[$modelClass] = false;
        }

        return $ready[$modelClass];
    }
}
