<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 活动报名服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\PayEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\enum\user\UserTerminalEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\dynamic\ActivityPayment;
use app\common\model\dynamic\ActivityRefund;
use app\common\model\dynamic\ActivityRegistration;
use app\common\model\dynamic\ActivityTicket;
use app\common\model\dynamic\Dynamic;
use app\common\model\financial\FinancialFlow;
use app\common\model\user\User;
use app\common\service\pay\AliPayService;
use app\common\service\pay\WeChatPayService;
use app\common\service\MoneyService;
use think\facade\Db;
use think\facade\Log;

/**
 * 活动报名服务
 */
class ActivityRegistrationService
{
    const PAY_FROM = 'activity_registration';
    const DEFAULT_PAY_EXPIRE_MINUTES = 30;
    protected static string $error = '';

    public static function getError(): string
    {
        return self::$error;
    }

    /**
     * @notes 批量释放已超时的活动报名待支付记录
     */
    public static function expirePendingRegistrations(int $limit = 200): array
    {
        $limit = min(max($limit, 1), 1000);
        $now = time();

        $paymentRegistrationIds = ActivityPayment::where('pay_status', ActivityPayment::STATUS_PENDING)
            ->where('expire_time', '>', 0)
            ->where('expire_time', '<=', $now)
            ->limit($limit)
            ->column('registration_id');

        $remainingLimit = max($limit - count($paymentRegistrationIds), 0);
        $fallbackRegistrationIds = [];
        if ($remainingLimit > 0) {
            $fallbackDeadline = $now - self::DEFAULT_PAY_EXPIRE_MINUTES * 60;
            $fallbackQuery = ActivityRegistration::where('registration_status', ActivityRegistration::STATUS_PENDING_PAY)
                ->where('pay_status', ActivityRegistration::PAY_STATUS_UNPAID)
                ->where('create_time', '<=', $fallbackDeadline);
            $excludedRegistrationIds = array_filter(array_map('intval', $paymentRegistrationIds));
            if (!empty($excludedRegistrationIds)) {
                $fallbackQuery->whereNotIn('id', $excludedRegistrationIds);
            }
            $fallbackRegistrationIds = $fallbackQuery->limit($remainingLimit)->column('id');
        }

        $registrationIds = array_values(array_unique(array_filter(array_map('intval', array_merge(
            $paymentRegistrationIds,
            $fallbackRegistrationIds
        )))));

        $expired = 0;
        $failed = 0;
        foreach ($registrationIds as $registrationId) {
            if (self::syncPendingRegistrationExpired($registrationId)) {
                $expired++;
            } else {
                $failed++;
            }
        }

        return [
            'scanned' => count($registrationIds),
            'expired' => $expired,
            'failed' => $failed,
        ];
    }

    protected static function setError(string $error): void
    {
        self::$error = $error;
    }

    /**
     * @notes 获取活动报名展示摘要
     */
    public static function buildActivitySummary(array $dynamic, int $userId = 0): array
    {
        $activityId = (int)($dynamic['id'] ?? 0);
        if ($activityId <= 0 || (int)($dynamic['dynamic_type'] ?? 0) !== Dynamic::TYPE_ACTIVITY) {
            return [
                'activity_enabled' => 0,
                'activity_status_text' => '',
                'tickets' => [],
            ];
        }

        $userRegistration = $userId > 0 ? self::getUserRegistration($activityId, $userId) : null;
        if ($userRegistration && (int)$userRegistration->registration_status === ActivityRegistration::STATUS_PENDING_PAY) {
            self::syncPendingRegistrationExpired((int)$userRegistration->id);
            $userRegistration = self::getUserRegistration($activityId, $userId);
            $dynamic = Dynamic::where('id', $activityId)->findOrEmpty()->getData();
        }

        $tickets = self::getTicketOptions($activityId, true);
        $minPrice = null;
        $hasFree = false;
        $fallbackMinPrice = null;
        $fallbackHasFree = false;
        $ticketRemaining = 0;
        $saleableTicketRemaining = 0;
        foreach ($tickets as $ticket) {
            $price = round((float)($ticket['price'] ?? 0), 2);
            $remaining = (int)($ticket['remaining_count'] ?? 0);
            if ($price <= 0) {
                $fallbackHasFree = true;
            }
            if ($fallbackMinPrice === null || $price < $fallbackMinPrice) {
                $fallbackMinPrice = $price;
            }
            if ((int)($ticket['can_buy'] ?? 0) === 1 && $remaining > 0) {
                if ($minPrice === null || $price < $minPrice) {
                    $minPrice = $price;
                }
                if ($price <= 0) {
                    $hasFree = true;
                }
                $saleableTicketRemaining += $remaining;
            }
            $ticketRemaining += $remaining;
        }
        if ($minPrice === null) {
            $minPrice = $fallbackMinPrice;
            $hasFree = $fallbackHasFree;
        }

        $totalQuota = (int)($dynamic['activity_total_quota'] ?? 0);
        $registeredCount = (int)($dynamic['activity_registered_count'] ?? 0);
        $effectiveTicketRemaining = $saleableTicketRemaining;
        $quotaRemaining = $totalQuota > 0 ? max($totalQuota - $registeredCount, 0) : $effectiveTicketRemaining;
        $totalRemaining = $totalQuota > 0 ? min($quotaRemaining, $effectiveTicketRemaining) : $effectiveTicketRemaining;
        [$canRegister, $reason] = self::checkActivityAvailable($dynamic, $tickets);

        return [
            'activity_enabled' => 1,
            'activity_start_time' => (int)($dynamic['activity_start_time'] ?? 0),
            'activity_signup_deadline' => (int)($dynamic['activity_signup_deadline'] ?? 0),
            'activity_signup_enabled' => (int)($dynamic['activity_signup_enabled'] ?? 0),
            'activity_total_quota' => $totalQuota,
            'activity_registered_count' => $registeredCount,
            'activity_remaining_count' => max($totalRemaining, 0),
            'activity_price_label' => self::buildPriceLabel($hasFree, $minPrice),
            'activity_can_register' => $canRegister ? 1 : 0,
            'activity_disabled_reason' => $reason,
            'activity_registration_status' => $userRegistration
                ? (int)$userRegistration->registration_status
                : -1,
            'activity_registration_status_text' => $userRegistration
                ? ActivityRegistration::getStatusText((int)$userRegistration->registration_status)
                : '未报名',
            'activity_registration_id' => $userRegistration ? (int)$userRegistration->id : 0,
            'activity_has_registered' => $userRegistration ? 1 : 0,
            'tickets' => $tickets,
        ];
    }

    /**
     * @notes 保存活动票种
     */
    public static function saveTickets(int $dynamicId, array $tickets): void
    {
        $now = time();
        $existingTickets = ActivityTicket::where('dynamic_id', $dynamicId)->select();
        $existingIds = [];
        foreach ($existingTickets as $existingTicket) {
            $existingIds[] = (int)$existingTicket->id;
        }

        $submittedIds = [];
        $sort = 1;
        foreach ($tickets as $ticket) {
            $name = trim((string)($ticket['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $price = round((float)($ticket['price'] ?? 0), 2);
            $stock = max(0, (int)($ticket['stock'] ?? 0));
            $saleStartTime = max(0, (int)($ticket['sale_start_time'] ?? 0));
            $saleEndTime = max(0, (int)($ticket['sale_end_time'] ?? 0));
            $ticketId = (int)($ticket['id'] ?? 0);
            $status = (int)($ticket['status'] ?? ActivityTicket::STATUS_ENABLED) === ActivityTicket::STATUS_DISABLED
                ? ActivityTicket::STATUS_DISABLED
                : ActivityTicket::STATUS_ENABLED;
            $ticketData = [
                'dynamic_id' => $dynamicId,
                'name' => mb_substr($name, 0, 80),
                'price' => $price,
                'stock' => $stock,
                'sale_start_time' => $saleStartTime,
                'sale_end_time' => $saleEndTime,
                'status' => $status,
                'sort' => (int)($ticket['sort'] ?? $sort),
                'update_time' => $now,
            ];

            if ($ticketId > 0 && in_array($ticketId, $existingIds, true)) {
                /** @var ActivityTicket|null $ticketModel */
                $ticketModel = ActivityTicket::where('dynamic_id', $dynamicId)
                    ->where('id', $ticketId)
                    ->find();
                if ($ticketModel) {
                    if ($stock < (int)$ticketModel->sold_count) {
                        throw new \InvalidArgumentException('票种库存不能小于已占用数量');
                    }
                    $registrationCount = ActivityRegistration::where('ticket_id', $ticketId)->count();
                    if ($registrationCount > 0) {
                        $oldName = (string)$ticketModel->name;
                        $oldPrice = round((float)$ticketModel->price, 2);
                        if ($oldName !== mb_substr($name, 0, 80) || abs($oldPrice - $price) >= 0.01) {
                            throw new \InvalidArgumentException('已有报名的票种不能修改名称或价格，请新增票种并停用旧票种');
                        }
                    }
                    $ticketModel->save($ticketData);
                    $submittedIds[] = $ticketId;
                    $sort++;
                    continue;
                }
            }

            $ticketData['sold_count'] = 0;
            $ticketData['create_time'] = $now;
            ActivityTicket::create($ticketData);
            $sort++;
        }

        $removedIds = array_values(array_diff($existingIds, $submittedIds));
        foreach ($removedIds as $removedId) {
            $registrationCount = ActivityRegistration::where('ticket_id', $removedId)->count();
            if ($registrationCount > 0) {
                ActivityTicket::where('id', $removedId)->update([
                    'status' => ActivityTicket::STATUS_DISABLED,
                    'update_time' => $now,
                ]);
                continue;
            }
            ActivityTicket::where('id', $removedId)->delete();
        }
    }

    /**
     * @notes 获取票种列表
     */
    public static function getTicketOptions(int $dynamicId, bool $enabledOnly = false): array
    {
        $query = ActivityTicket::where('dynamic_id', $dynamicId);
        if ($enabledOnly) {
            $query->where('status', ActivityTicket::STATUS_ENABLED);
        }
        $tickets = $query->order(['sort' => 'asc', 'id' => 'asc'])->select()->toArray();
        foreach ($tickets as &$ticket) {
            $ticket['remaining_count'] = max((int)$ticket['stock'] - (int)$ticket['sold_count'], 0);
            $ticket['price_label'] = round((float)$ticket['price'], 2) <= 0
                ? '免费'
                : '¥' . number_format((float)$ticket['price'], 2, '.', '');
            [$canBuy, $buyReason] = self::checkTicketBuyable($ticket);
            $ticket['can_buy'] = $canBuy ? 1 : 0;
            $ticket['buy_disabled_reason'] = $buyReason;
            $ticket['sale_time_text'] = self::buildTicketSaleTimeText($ticket);
        }
        unset($ticket);
        return $tickets;
    }

    /**
     * @notes 提交报名
     */
    public static function register(int $dynamicId, int $ticketId, int $userId, array $params): array
    {
        Db::startTrans();
        try {
            /** @var Dynamic|null $dynamic */
            $dynamic = Dynamic::where('id', $dynamicId)->lock(true)->find();
            if (!$dynamic || (int)$dynamic->dynamic_type !== Dynamic::TYPE_ACTIVITY) {
                Db::rollback();
                return [false, '活动不存在', []];
            }

            $dynamicData = $dynamic->getData();
            $existing = self::getUserRegistration($dynamicId, $userId, true);
            if ($existing) {
                if (self::isPendingRegistrationExpired($existing)) {
                    ActivityPayment::where('registration_id', (int)$existing->id)
                        ->where('pay_status', ActivityPayment::STATUS_PENDING)
                        ->update([
                            'pay_status' => ActivityPayment::STATUS_FAILED,
                            'update_time' => time(),
                        ]);
                    $existing->pay_status = ActivityRegistration::PAY_STATUS_FAILED;
                    self::releaseRegistrationQuota($existing, false);
                    $dynamicData['activity_registered_count'] = max((int)($dynamicData['activity_registered_count'] ?? 0) - 1, 0);
                    $dynamic->activity_registered_count = $dynamicData['activity_registered_count'];
                } else {
                    Db::rollback();
                    return [false, '你已提交过该活动报名', ['registration_id' => (int)$existing->id]];
                }
            }

            $ticketsSnapshot = self::getTicketOptions($dynamicId, true);
            [$available, $reason] = self::checkActivityAvailable($dynamicData, $ticketsSnapshot);
            if (!$available) {
                Db::rollback();
                return [false, $reason, []];
            }

            /** @var ActivityTicket|null $ticket */
            $ticket = ActivityTicket::where('id', $ticketId)
                ->where('dynamic_id', $dynamicId)
                ->where('status', ActivityTicket::STATUS_ENABLED)
                ->lock(true)
                ->find();
            if (!$ticket) {
                Db::rollback();
                return [false, '票种不存在或已停用', []];
            }

            if ((int)$ticket->sold_count >= (int)$ticket->stock) {
                Db::rollback();
                return [false, '该票种已售罄', []];
            }
            [$ticketCanBuy, $ticketReason] = self::checkTicketBuyable($ticket->getData());
            if (!$ticketCanBuy) {
                Db::rollback();
                return [false, $ticketReason ?: '该票种暂不可购买', []];
            }

            $totalQuota = (int)($dynamicData['activity_total_quota'] ?? 0);
            if ($totalQuota > 0 && (int)($dynamicData['activity_registered_count'] ?? 0) >= $totalQuota) {
                Db::rollback();
                return [false, '活动名额已满', []];
            }

            $price = round((float)$ticket->price, 2);
            $now = time();
            $registration = ActivityRegistration::create([
                'dynamic_id' => $dynamicId,
                'ticket_id' => $ticketId,
                'user_id' => $userId,
                'contact_name' => mb_substr(trim((string)($params['contact_name'] ?? '')), 0, 50),
                'contact_mobile' => mb_substr(trim((string)($params['contact_mobile'] ?? '')), 0, 30),
                'remark' => mb_substr(trim((string)($params['remark'] ?? '')), 0, 255),
                'quantity' => 1,
                'ticket_name' => (string)$ticket->name,
                'ticket_price' => $price,
                'pay_amount' => $price,
                'registration_status' => $price <= 0
                    ? ActivityRegistration::STATUS_REGISTERED
                    : ActivityRegistration::STATUS_PENDING_PAY,
                'pay_status' => $price <= 0
                    ? ActivityRegistration::PAY_STATUS_PAID
                    : ActivityRegistration::PAY_STATUS_UNPAID,
                'cancel_status' => ActivityRegistration::CANCEL_STATUS_NONE,
                'create_time' => $now,
                'update_time' => $now,
            ]);

            // 待支付报名也占用库存，避免超卖；取消或退款完成后释放。
            $ticket->sold_count = (int)$ticket->sold_count + 1;
            $ticket->update_time = $now;
            $ticket->save();

            $dynamic->activity_registered_count = (int)($dynamicData['activity_registered_count'] ?? 0) + 1;
            $dynamic->update_time = $now;
            $dynamic->save();

            Db::commit();
            return [true, $price <= 0 ? '报名成功' : '报名已提交，请完成支付', [
                'registration_id' => (int)$registration->id,
                'need_pay' => $price > 0 ? 1 : 0,
            ]];
        } catch (\Throwable $e) {
            Db::rollback();
            return [false, '报名失败：' . $e->getMessage(), []];
        }
    }

    /**
     * @notes 获取报名支付方式数据
     */
    public static function getPayWay(int $registrationId, int $userId, int $terminal): array|false
    {
        self::setError('');
        $registration = self::findUserRegistration($registrationId, $userId);
        if (!$registration) {
            self::setError('报名记录不存在');
            return false;
        }
        if (self::syncPendingRegistrationExpired($registrationId)) {
            self::setError('报名支付已超时，请重新报名');
            return false;
        }
        if ((int)$registration->registration_status !== ActivityRegistration::STATUS_PENDING_PAY) {
            self::setError('报名记录不可支付');
            return false;
        }

        $payWays = \app\common\model\pay\PayWay::alias('pw')
            ->join('dev_pay_config dp', 'pw.pay_config_id = dp.id')
            ->where(['pw.scene' => $terminal, 'pw.status' => 1])
            ->field('dp.id,dp.name,dp.pay_way,dp.icon,dp.sort,dp.remark,pw.is_default')
            ->order('pw.is_default desc,dp.sort desc,id asc')
            ->select()
            ->toArray();

        $userMoney = User::where(['id' => $userId])->value('user_money');
        foreach ($payWays as &$item) {
            if ((int)$item['pay_way'] === PayEnum::WECHAT_PAY) {
                $item['extra'] = '微信快捷支付';
            } elseif ((int)$item['pay_way'] === PayEnum::ALI_PAY) {
                $item['extra'] = '支付宝快捷支付';
            } elseif ((int)$item['pay_way'] === PayEnum::BALANCE_PAY) {
                $item['extra'] = '可用余额：' . number_format((float)$userMoney, 2, '.', '');
            } else {
                $item['extra'] = (string)($item['remark'] ?? '');
            }
        }
        unset($item);

        if (empty($payWays)) {
            self::setError('当前终端暂未开启可用支付方式');
            return false;
        }

        $payDeadlineTime = self::getPendingPayDeadline($registration);

        return [
            'lists' => $payWays,
            'order_amount' => round((float)$registration->pay_amount, 2),
            'pay_deadline_time' => $payDeadlineTime,
            'pay_remain_seconds' => max($payDeadlineTime - time(), 0),
            'total_amount' => round((float)$registration->pay_amount, 2),
            'pay_amount' => round((float)$registration->pay_amount, 2),
            'paid_amount' => 0,
            'unpaid_amount' => round((float)$registration->pay_amount, 2),
            'need_pay_amount' => round((float)$registration->pay_amount, 2),
            'need_pay_label' => '支付报名费',
            'pay_subject' => '活动报名：' . (string)$registration->ticket_name,
            'payment_mode' => 'activity',
            'payment_channel' => 1,
        ];
    }

    /**
     * @notes 发起活动报名支付
     */
    public static function prepay(int $registrationId, int $userId, int $payWay, int $terminal, string $redirectUrl = ''): array|false
    {
        self::setError('');
        Db::startTrans();
        try {
            $registration = ActivityRegistration::where('id', $registrationId)
                ->where('user_id', $userId)
                ->lock(true)
                ->find();
            if (!$registration) {
                Db::rollback();
                return false;
            }
            if (self::isPendingRegistrationExpired($registration)) {
                ActivityPayment::where('registration_id', $registrationId)
                    ->where('pay_status', ActivityPayment::STATUS_PENDING)
                    ->update([
                        'pay_status' => ActivityPayment::STATUS_FAILED,
                        'update_time' => time(),
                    ]);
                $registration->pay_status = ActivityRegistration::PAY_STATUS_FAILED;
                self::releaseRegistrationQuota($registration, false);
                Db::commit();
                return false;
            }
            if ((int)$registration->registration_status !== ActivityRegistration::STATUS_PENDING_PAY) {
                Db::rollback();
                return false;
            }
            if ((float)$registration->pay_amount <= 0) {
                Db::rollback();
                return false;
            }

            ActivityPayment::where('registration_id', $registrationId)
                ->where('pay_status', ActivityPayment::STATUS_PENDING)
                ->update([
                    'pay_status' => ActivityPayment::STATUS_FAILED,
                    'update_time' => time(),
                ]);

            $payment = ActivityPayment::create([
                'payment_sn' => ActivityPayment::generatePaymentSn(),
                'registration_id' => $registrationId,
                'dynamic_id' => (int)$registration->dynamic_id,
                'ticket_id' => (int)$registration->ticket_id,
                'user_id' => $userId,
                'pay_way' => $payWay,
                'pay_terminal' => $terminal,
                'pay_amount' => round((float)$registration->pay_amount, 2),
                'pay_status' => ActivityPayment::STATUS_PENDING,
                'expire_time' => time() + self::DEFAULT_PAY_EXPIRE_MINUTES * 60,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            if ($payWay === PayEnum::BALANCE_PAY) {
                $result = self::payByBalance($registration, $payment);
                Db::commit();
                return $result;
            }

            Db::commit();

            $order = [
                'id' => (int)$registration->id,
                'sn' => (string)$payment->payment_sn,
                'pay_sn' => (string)$payment->payment_sn,
                'payment_sn' => (string)$payment->payment_sn,
                'user_id' => $userId,
                'order_amount' => round((float)$payment->pay_amount, 2),
                'pay_subject' => '活动报名：' . (string)$registration->ticket_name,
                'redirect_url' => $redirectUrl ?: '/packages/pages/activity_registration/detail',
                'pay_deadline_time' => (int)$payment->expire_time,
            ];

            $service = null;
            if ($payWay === PayEnum::WECHAT_PAY) {
                $service = new WeChatPayService($terminal, $userId);
                $result = $service->pay(self::PAY_FROM, $order);
            } elseif ($payWay === PayEnum::ALI_PAY) {
                $service = new AliPayService($terminal);
                $result = $service->pay(self::PAY_FROM, $order);
            } else {
                self::setError('支付方式参数错误');
                return false;
            }

            if ($result === false) {
                self::setError($service && method_exists($service, 'getError') ? (string)$service->getError() : '发起支付失败');
                return false;
            }

            $result['payment_sn'] = (string)$payment->payment_sn;
            $result['registration_id'] = (int)$registration->id;
            return $result;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            Log::write('活动报名预支付失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * @notes 支付成功回调
     */
    public static function paySuccess(string $paymentSn, string $transactionId, array $callbackData = []): array
    {
        Db::startTrans();
        try {
            /** @var ActivityPayment|null $payment */
            $payment = ActivityPayment::where('payment_sn', $paymentSn)->lock(true)->find();
            if (!$payment) {
                Db::rollback();
                return [false, '支付记录不存在', []];
            }

            if ((int)$payment->pay_status === ActivityPayment::STATUS_PAID) {
                Db::commit();
                return [true, '已处理', ['registration_id' => (int)$payment->registration_id]];
            }

            if ((int)$payment->pay_status !== ActivityPayment::STATUS_PENDING) {
                Db::rollback();
                return [false, '支付记录状态不允许处理回调', []];
            }

            $error = self::validatePaidCallback($payment, $callbackData, $transactionId);
            if ($error !== '') {
                Db::rollback();
                return [false, $error, []];
            }

            /** @var ActivityRegistration|null $registration */
            $registration = ActivityRegistration::where('id', (int)$payment->registration_id)->lock(true)->find();
            if (!$registration) {
                Db::rollback();
                return [false, '报名记录不存在', []];
            }

            $payment->pay_status = ActivityPayment::STATUS_PAID;
            $payment->transaction_id = trim($transactionId) !== '' ? $transactionId : null;
            $payment->pay_time = time();
            $payment->callback_time = time();
            $payment->callback_data = json_encode($callbackData, JSON_UNESCAPED_UNICODE);
            $payment->update_time = time();
            $payment->save();

            $registration->payment_sn = (string)$payment->payment_sn;
            $registration->pay_status = ActivityRegistration::PAY_STATUS_PAID;
            $registration->pay_time = time();
            $registration->update_time = time();

            if ((int)$registration->cancel_status === ActivityRegistration::CANCEL_STATUS_PENDING
                || (int)$registration->registration_status === ActivityRegistration::STATUS_CANCEL_APPLY
            ) {
                ActivityRefund::where('registration_id', (int)$registration->id)
                    ->where('refund_status', ActivityRefund::STATUS_PENDING)
                    ->where('payment_id', 0)
                    ->update([
                        'payment_id' => (int)$payment->id,
                        'refund_amount' => round((float)$payment->pay_amount, 2),
                        'update_time' => time(),
                    ]);
            } elseif ((int)$registration->registration_status === ActivityRegistration::STATUS_PENDING_PAY) {
                $registration->registration_status = ActivityRegistration::STATUS_REGISTERED;
            }
            $registration->save();

            self::recordPaymentFlow($payment);

            Db::commit();
            return [true, '支付成功', ['registration_id' => (int)$registration->id]];
        } catch (\Throwable $e) {
            Db::rollback();
            return [false, $e->getMessage(), []];
        }
    }

    protected static function payByBalance(ActivityRegistration $registration, ActivityPayment $payment): array
    {
        $payAmount = round((float)$payment->pay_amount, 2);
        $user = User::lock(true)->find((int)$registration->user_id);
        if (!$user) {
            throw new \RuntimeException('用户不存在');
        }
        if (round((float)$user->user_money, 2) < $payAmount) {
            throw new \RuntimeException('余额不足');
        }

        $now = time();
        $transactionId = self::buildBalanceTransactionId((string)$payment->payment_sn);

        $user->user_money = round((float)$user->user_money - $payAmount, 2);
        $user->save();

        AccountLogLogic::add(
            (int)$registration->user_id,
            AccountLogEnum::UM_DEC_ACTIVITY_REGISTRATION,
            AccountLogEnum::DEC,
            $payAmount,
            (string)$payment->payment_sn,
            '活动报名余额支付',
            [
                'registration_id' => (int)$registration->id,
                'dynamic_id' => (int)$registration->dynamic_id,
                'ticket_id' => (int)$registration->ticket_id,
            ]
        );

        $payment->pay_status = ActivityPayment::STATUS_PAID;
        $payment->transaction_id = $transactionId;
        $payment->pay_time = $now;
        $payment->callback_time = $now;
        $payment->callback_data = json_encode(['pay_way' => 'balance'], JSON_UNESCAPED_UNICODE);
        $payment->update_time = $now;
        $payment->save();

        $registration->payment_sn = (string)$payment->payment_sn;
        $registration->pay_status = ActivityRegistration::PAY_STATUS_PAID;
        $registration->pay_time = $now;
        $registration->registration_status = ActivityRegistration::STATUS_REGISTERED;
        $registration->update_time = $now;
        $registration->save();

        self::recordPaymentFlow($payment);

        return [
            'pay_way' => PayEnum::BALANCE_PAY,
            'config' => [],
            'payment_sn' => (string)$payment->payment_sn,
            'registration_id' => (int)$registration->id,
        ];
    }

    protected static function buildBalanceTransactionId(string $paymentSn): string
    {
        return 'BALANCE_' . $paymentSn;
    }

    /**
     * @notes 获取支付状态
     */
    public static function getPayStatus(int $registrationId, int $userId, string $paymentSn = ''): array|false
    {
        $registration = self::findUserRegistration($registrationId, $userId);
        if (!$registration) {
            return false;
        }
        if (self::syncPendingRegistrationExpired($registrationId)) {
            $registration = self::findUserRegistration($registrationId, $userId);
            if (!$registration) {
                return false;
            }
        }
        $query = ActivityPayment::where('registration_id', $registrationId);
        if ($paymentSn !== '') {
            $query->where('payment_sn', $paymentSn);
        }
        $payment = $query->order('id', 'desc')->find();
        $payDeadlineTime = $payment ? (int)($payment->expire_time ?? 0) : 0;
        $payRemainSeconds = $payDeadlineTime > 0 ? max($payDeadlineTime - time(), 0) : 0;

        return [
            'pay_status' => (int)$registration->pay_status,
            'order' => [
                'id' => (int)$registration->id,
                'order_status' => 0,
                'pay_deadline_time' => $payDeadlineTime,
                'pay_remain_seconds' => $payRemainSeconds,
            ],
            'registration' => self::formatRegistration($registration),
            'payment' => $payment ? self::formatPayment($payment) : null,
        ];
    }

    /**
     * @notes 用户提交取消申请
     */
    public static function applyCancel(int $registrationId, int $userId, string $reason): array
    {
        Db::startTrans();
        try {
            $registration = ActivityRegistration::where('id', $registrationId)
                ->where('user_id', $userId)
                ->lock(true)
                ->find();
            if (!$registration) {
                Db::rollback();
                return [false, '报名记录不存在'];
            }
            if (self::isPendingRegistrationExpired($registration)) {
                ActivityPayment::where('registration_id', $registrationId)
                    ->where('pay_status', ActivityPayment::STATUS_PENDING)
                    ->update([
                        'pay_status' => ActivityPayment::STATUS_FAILED,
                        'update_time' => time(),
                    ]);
                $registration->pay_status = ActivityRegistration::PAY_STATUS_FAILED;
                self::releaseRegistrationQuota($registration, false);
                Db::commit();
                return [false, '报名支付已超时，请重新报名'];
            }
            if (!in_array((int)$registration->registration_status, [
                ActivityRegistration::STATUS_PENDING_PAY,
                ActivityRegistration::STATUS_REGISTERED,
                ActivityRegistration::STATUS_REFUND_FAILED,
            ], true)) {
                Db::rollback();
                return [false, '当前状态不可申请取消'];
            }
            if ((int)$registration->cancel_status === ActivityRegistration::CANCEL_STATUS_PENDING) {
                Db::rollback();
                return [false, '取消申请已提交，请等待审核'];
            }

            $registration->cancel_status = ActivityRegistration::CANCEL_STATUS_PENDING;
            $registration->cancel_reason = mb_substr(trim($reason), 0, 255);
            $registration->cancel_apply_time = time();
            $registration->registration_status = ActivityRegistration::STATUS_CANCEL_APPLY;
            $registration->update_time = time();
            $registration->save();

            $payment = ActivityPayment::where('registration_id', $registrationId)
                ->where('pay_status', ActivityPayment::STATUS_PAID)
                ->order('id', 'desc')
                ->find();

            $existsRefund = ActivityRefund::where('registration_id', $registrationId)
                ->whereIn('refund_status', [
                    ActivityRefund::STATUS_PENDING,
                    ActivityRefund::STATUS_APPROVED,
                    ActivityRefund::STATUS_PROCESSING,
                ])
                ->lock(true)
                ->find();
            if (!$existsRefund) {
                ActivityRefund::create([
                    'refund_sn' => ActivityRefund::generateRefundSn(),
                    'registration_id' => $registrationId,
                    'payment_id' => $payment ? (int)$payment->id : 0,
                    'dynamic_id' => (int)$registration->dynamic_id,
                    'ticket_id' => (int)$registration->ticket_id,
                    'user_id' => $userId,
                    'refund_amount' => $payment ? round((float)$payment->pay_amount, 2) : 0,
                    'actual_refund_amount' => 0,
                    'refund_status' => ActivityRefund::STATUS_PENDING,
                    'refund_reason' => mb_substr(trim($reason), 0, 255),
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            }

            Db::commit();
            return [true, '取消申请已提交'];
        } catch (\Throwable $e) {
            Db::rollback();
            return [false, '提交失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 后台审核取消/退款申请
     */
    public static function auditCancel(int $refundId, int $adminId, bool $approved, string $remark = ''): array
    {
        Db::startTrans();
        try {
            /** @var ActivityRefund|null $refund */
            $refund = ActivityRefund::where('id', $refundId)->lock(true)->find();
            if (!$refund) {
                Db::rollback();
                return [false, '退款申请不存在'];
            }
            if ((int)$refund->refund_status !== ActivityRefund::STATUS_PENDING) {
                Db::rollback();
                return [false, '当前状态不可审核'];
            }

            /** @var ActivityRegistration|null $registration */
            $registration = ActivityRegistration::where('id', (int)$refund->registration_id)->lock(true)->find();
            if (!$registration) {
                Db::rollback();
                return [false, '报名记录不存在'];
            }

            $refund->audit_admin_id = $adminId;
            $refund->audit_time = time();
            $refund->audit_remark = mb_substr(trim($remark), 0, 255);
            $refund->update_time = time();

            if (!$approved) {
                $refund->refund_status = ActivityRefund::STATUS_REJECTED;
                $refund->save();

                $registration->cancel_status = ActivityRegistration::CANCEL_STATUS_REJECTED;
                $registration->cancel_reject_reason = mb_substr(trim($remark), 0, 255);
                $registration->registration_status = (int)$registration->pay_status === ActivityRegistration::PAY_STATUS_PAID
                    ? ActivityRegistration::STATUS_REGISTERED
                    : ActivityRegistration::STATUS_PENDING_PAY;
                $registration->update_time = time();
                $registration->save();

                Db::commit();
                return [true, '已拒绝'];
            }

            $payment = ActivityPayment::where('id', (int)$refund->payment_id)->lock(true)->find();
            if (!$payment || (float)$refund->refund_amount <= 0) {
                $refund->refund_status = ActivityRefund::STATUS_COMPLETED;
                $refund->actual_refund_amount = 0;
                $refund->refund_time = time();
                $refund->save();

                self::releaseRegistrationQuota($registration);
                Db::commit();
                return [true, '已取消报名'];
            }

            $refund->refund_status = ActivityRefund::STATUS_APPROVED;
            $refund->save();
            $registration->registration_status = ActivityRegistration::STATUS_REFUNDING;
            $registration->update_time = time();
            $registration->save();

            [$success, $message] = self::executeRefund($refund, $payment);
            Db::commit();
            return [$success, $message];
        } catch (\Throwable $e) {
            Db::rollback();
            return [false, '审核失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 报名列表
     */
    public static function registrationList(array $params): array
    {
        $pageSize = max(1, (int)($params['page_size'] ?? $params['limit'] ?? 15));
        $query = ActivityRegistration::with(['dynamic', 'ticket', 'user']);
        self::applyRegistrationFilters($query, $params);
        $list = $query->order('id', 'desc')->paginate($pageSize)->toArray();
        $list['data'] = self::formatRegistrationList($list['data'] ?? []);
        return $list;
    }

    /**
     * @notes 取消/退款申请列表
     */
    public static function refundList(array $params): array
    {
        $pageSize = max(1, (int)($params['page_size'] ?? $params['limit'] ?? 15));
        $query = ActivityRefund::with([
            'registration' => function ($query) {
                $query->with(['dynamic', 'ticket', 'user']);
            },
            'payment'
        ]);
        if (isset($params['refund_status']) && $params['refund_status'] !== '') {
            $query->where('refund_status', (int)$params['refund_status']);
        }
        if (!empty($params['dynamic_id'])) {
            $query->where('dynamic_id', (int)$params['dynamic_id']);
        }
        $list = $query->order('id', 'desc')->paginate($pageSize)->toArray();
        $registrations = [];
        foreach ($list['data'] as $index => $item) {
            if (!empty($item['registration'])) {
                $registrations[$index] = $item['registration'];
            }
        }
        $formattedRegistrations = self::formatRegistrationList(array_values($registrations));
        $formattedRegistrationIndex = 0;
        foreach ($list['data'] as &$item) {
            $item['refund_status_desc'] = ActivityRefund::getStatusText((int)$item['refund_status']);
            if (!empty($item['registration'])) {
                $item['registration'] = $formattedRegistrations[$formattedRegistrationIndex] ?? self::formatRegistrationArray($item['registration']);
                $formattedRegistrationIndex++;
            }
        }
        unset($item);
        return $list;
    }

    /**
     * @notes 我的报名列表
     */
    public static function myRegistrations(int $userId, array $params): array
    {
        $pageSize = max(1, (int)($params['page_size'] ?? 10));
        $query = ActivityRegistration::with(['dynamic', 'ticket'])
            ->where('user_id', $userId);
        self::applyMyRegistrationStatusGroupFilter($query, (string)($params['status_group'] ?? 'all'));
        $list = $query->order('id', 'desc')
            ->paginate($pageSize)
            ->toArray();
        foreach ($list['data'] as &$item) {
            if ((int)($item['registration_status'] ?? -1) === ActivityRegistration::STATUS_PENDING_PAY
                && self::syncPendingRegistrationExpired((int)$item['id'])
            ) {
                $fresh = ActivityRegistration::with(['dynamic', 'ticket'])->where('id', (int)$item['id'])->find();
                if ($fresh) {
                    $item = $fresh->toArray();
                }
            }
        }
        unset($item);
        $list['data'] = self::formatRegistrationList($list['data'] ?? []);
        return $list;
    }

    protected static function applyMyRegistrationStatusGroupFilter($query, string $statusGroup): void
    {
        switch ($statusGroup) {
            case 'pending_pay':
                $query->where('registration_status', ActivityRegistration::STATUS_PENDING_PAY);
                break;
            case 'registered':
                $query->where('registration_status', ActivityRegistration::STATUS_REGISTERED);
                break;
            case 'cancel_refund':
                $query->where(function ($q) {
                    $q->whereIn('registration_status', [
                        ActivityRegistration::STATUS_CANCEL_APPLY,
                        ActivityRegistration::STATUS_CANCELLED,
                        ActivityRegistration::STATUS_REFUNDING,
                        ActivityRegistration::STATUS_REFUND_FAILED,
                    ])->whereOr('cancel_status', '>', ActivityRegistration::CANCEL_STATUS_NONE);
                });
                break;
            default:
                break;
        }
    }

    public static function registrationDetail(int $registrationId, int $userId): ?array
    {
        $registration = ActivityRegistration::with(['dynamic', 'ticket'])
            ->where('id', $registrationId)
            ->where('user_id', $userId)
            ->find();
        if ($registration && (int)$registration->registration_status === ActivityRegistration::STATUS_PENDING_PAY) {
            self::syncPendingRegistrationExpired($registrationId);
            $registration = ActivityRegistration::with(['dynamic', 'ticket'])
                ->where('id', $registrationId)
                ->where('user_id', $userId)
                ->find();
        }
        return $registration ? self::formatRegistrationArray($registration->toArray()) : null;
    }

    protected static function executeRefund(ActivityRefund $refund, ActivityPayment $payment): array
    {
        try {
            if ((int)$payment->pay_way === ActivityPayment::WAY_BALANCE) {
                return self::refundToBalance($refund, $payment);
            }

            if ((int)$payment->pay_way === ActivityPayment::WAY_WECHAT) {
                if (trim((string)$payment->transaction_id) === '') {
                    return self::markRefundFailed($refund, '缺少微信交易号，无法自动退款');
                }
                $refundTerminal = (int)($payment->pay_terminal ?? 0) > 0
                    ? (int)$payment->pay_terminal
                    : UserTerminalEnum::WECHAT_MMP;
                $result = (array)(new WeChatPayService($refundTerminal))->refund([
                    'transaction_id' => (string)$payment->transaction_id,
                    'refund_sn' => (string)$refund->refund_sn,
                    'refund_amount' => (float)$refund->refund_amount,
                    'total_amount' => (float)$payment->pay_amount,
                ]);
                $status = strtoupper((string)($result['status'] ?? ''));
                if ($status === 'SUCCESS') {
                    return self::completeRefund($refund, $payment, (string)($result['refund_id'] ?? $refund->refund_sn), json_encode($result, JSON_UNESCAPED_UNICODE));
                }
                $refund->refund_status = ActivityRefund::STATUS_PROCESSING;
                $refund->refund_msg = $status !== '' ? '微信退款处理中：' . $status : '微信退款处理中';
                $refund->update_time = time();
                $refund->save();
                return [true, '退款处理中'];
            }

            if ((int)$payment->pay_way === ActivityPayment::WAY_ALIPAY) {
                $result = (array)(new AliPayService())->refund((string)$payment->payment_sn, (float)$refund->refund_amount, (string)$refund->refund_sn);
                $fundChanged = strtoupper((string)($result['fundChange'] ?? $result['fund_change'] ?? ''));
                if (($result['code'] ?? '') === '10000' && ($result['msg'] ?? '') === 'Success' && $fundChanged === 'Y') {
                    return self::completeRefund($refund, $payment, (string)($result['tradeNo'] ?? ''), json_encode($result, JSON_UNESCAPED_UNICODE));
                }
                if (($result['code'] ?? '') === '10000' && ($result['msg'] ?? '') === 'Success') {
                    return self::markRefundFailed($refund, '支付宝未实际退资：' . json_encode($result, JSON_UNESCAPED_UNICODE));
                }
                return self::markRefundFailed($refund, (string)($result['subMsg'] ?? $result['msg'] ?? '支付宝退款失败'));
            }

            $refund->refund_status = ActivityRefund::STATUS_PROCESSING;
            $refund->refund_msg = '待线下确认退款';
            $refund->update_time = time();
            $refund->save();
            return [true, '待线下确认退款'];
        } catch (\Throwable $e) {
            return self::markRefundFailed($refund, $e->getMessage());
        }
    }

    protected static function refundToBalance(ActivityRefund $refund, ActivityPayment $payment): array
    {
        if ((int)$refund->refund_status === ActivityRefund::STATUS_COMPLETED
            || (int)$payment->pay_status === ActivityPayment::STATUS_REFUNDED
        ) {
            return [true, '退款已完成'];
        }

        $user = User::lock(true)->find((int)$refund->user_id);
        if (!$user) {
            return self::markRefundFailed($refund, '退款用户不存在');
        }

        $refundAmount = round((float)$refund->refund_amount, 2);
        $user->user_money = round((float)$user->user_money + $refundAmount, 2);
        $user->save();

        AccountLogLogic::add(
            (int)$refund->user_id,
            AccountLogEnum::UM_INC_ACTIVITY_REGISTRATION_REFUND,
            AccountLogEnum::INC,
            $refundAmount,
            (string)$refund->refund_sn,
            '活动报名退款退回余额',
            [
                'registration_id' => (int)$refund->registration_id,
                'dynamic_id' => (int)$refund->dynamic_id,
                'ticket_id' => (int)$refund->ticket_id,
                'payment_id' => (int)$payment->id,
            ]
        );

        return self::completeRefund($refund, $payment, self::buildBalanceTransactionId((string)$refund->refund_sn), '余额退款完成');
    }

    protected static function completeRefund(ActivityRefund $refund, ActivityPayment $payment, string $thirdRefundNo = '', string $message = ''): array
    {
        if ((int)$refund->refund_status === ActivityRefund::STATUS_COMPLETED) {
            return [true, '退款已完成'];
        }
        if (!in_array((int)$refund->refund_status, [
            ActivityRefund::STATUS_APPROVED,
            ActivityRefund::STATUS_PROCESSING,
        ], true)) {
            return [false, '当前退款状态不可完成'];
        }

        $registration = ActivityRegistration::where('id', (int)$refund->registration_id)->lock(true)->find();
        if (!$registration) {
            return [false, '报名记录不存在'];
        }

        $refund->refund_status = ActivityRefund::STATUS_COMPLETED;
        $refund->actual_refund_amount = round((float)$refund->refund_amount, 2);
        $refund->third_refund_no = $thirdRefundNo;
        $refund->refund_msg = mb_substr($message, 0, 1000);
        $refund->refund_time = time();
        $refund->update_time = time();
        $refund->save();

        $payment->refund_amount = round((float)$refund->refund_amount, 2);
        $payment->refund_time = time();
        $payment->pay_status = ActivityPayment::STATUS_REFUNDED;
        $payment->update_time = time();
        $payment->save();

        $registration->pay_status = ActivityRegistration::PAY_STATUS_REFUNDED;
        self::releaseRegistrationQuota($registration);
        self::recordRefundFlow($refund, $payment, $thirdRefundNo);

        return [true, '退款完成'];
    }

    protected static function markRefundFailed(ActivityRefund $refund, string $message): array
    {
        $refund->refund_status = ActivityRefund::STATUS_FAILED;
        $refund->refund_msg = mb_substr($message, 0, 1000);
        $refund->update_time = time();
        $refund->save();

        ActivityRegistration::where('id', (int)$refund->registration_id)->update([
            'registration_status' => ActivityRegistration::STATUS_REFUND_FAILED,
            'update_time' => time(),
        ]);
        return [false, $message];
    }

    /**
     * @notes 微信退款回调处理
     */
    public static function handleWechatRefundCallback(array $message): bool
    {
        $refundStatus = strtoupper((string)($message['refund_status'] ?? ''));
        $outRefundNo = trim((string)($message['out_refund_no'] ?? ''));
        if ($outRefundNo === '') {
            return false;
        }

        Db::startTrans();
        try {
            /** @var ActivityRefund|null $refund */
            $refund = ActivityRefund::where('refund_sn', $outRefundNo)->lock(true)->find();
            if (!$refund) {
                Db::rollback();
                return false;
            }
            if ((int)$refund->refund_status === ActivityRefund::STATUS_COMPLETED) {
                Db::commit();
                return true;
            }
            $payment = ActivityPayment::where('id', (int)$refund->payment_id)->lock(true)->find();
            if (!$payment) {
                Db::rollback();
                return false;
            }

            $messageJson = json_encode($message, JSON_UNESCAPED_UNICODE) ?: '';
            if ($refundStatus === 'SUCCESS') {
                [$success, ] = self::completeRefund(
                    $refund,
                    $payment,
                    (string)($message['refund_id'] ?? $outRefundNo),
                    $messageJson
                );
                $success ? Db::commit() : Db::rollback();
                return $success;
            }

            if (in_array($refundStatus, ['ABNORMAL', 'CLOSED'], true)) {
                self::markRefundFailed($refund, '微信退款失败：' . $refundStatus . ($messageJson ? '；' . $messageJson : ''));
                Db::commit();
                return true;
            }

            Db::rollback();
            return false;
        } catch (\Throwable $e) {
            Db::rollback();
            Log::write('活动报名微信退款回调失败：' . $e->getMessage());
            return false;
        }
    }

    protected static function releaseRegistrationQuota(ActivityRegistration $registration, bool $markCancelApproved = true): void
    {
        if ((int)$registration->registration_status === ActivityRegistration::STATUS_CANCELLED) {
            return;
        }

        ActivityTicket::where('id', (int)$registration->ticket_id)
            ->where('sold_count', '>', 0)
            ->dec('sold_count')
            ->update(['update_time' => time()]);

        Dynamic::where('id', (int)$registration->dynamic_id)
            ->where('activity_registered_count', '>', 0)
            ->dec('activity_registered_count')
            ->update(['update_time' => time()]);

        $registration->registration_status = ActivityRegistration::STATUS_CANCELLED;
        if ($markCancelApproved) {
            $registration->cancel_status = ActivityRegistration::CANCEL_STATUS_APPROVED;
            $registration->cancel_audit_time = time();
        }
        $registration->update_time = time();
        $registration->save();
    }

    protected static function getUserRegistration(int $dynamicId, int $userId, bool $lock = false): ?ActivityRegistration
    {
        $query = ActivityRegistration::where('dynamic_id', $dynamicId)
            ->where('user_id', $userId)
            ->whereIn('registration_status', ActivityRegistration::getActiveStatuses());
        if ($lock) {
            $query->lock(true);
        }
        return $query->find();
    }

    protected static function findUserRegistration(int $registrationId, int $userId): ?ActivityRegistration
    {
        return ActivityRegistration::where('id', $registrationId)->where('user_id', $userId)->find();
    }

    protected static function syncPendingRegistrationExpired(int $registrationId): bool
    {
        Db::startTrans();
        try {
            /** @var ActivityRegistration|null $registration */
            $registration = ActivityRegistration::where('id', $registrationId)->lock(true)->find();
            if (!$registration || !self::isPendingRegistrationExpired($registration)) {
                Db::commit();
                return false;
            }

            ActivityPayment::where('registration_id', $registrationId)
                ->where('pay_status', ActivityPayment::STATUS_PENDING)
                ->update([
                    'pay_status' => ActivityPayment::STATUS_FAILED,
                    'update_time' => time(),
                ]);
            $registration->pay_status = ActivityRegistration::PAY_STATUS_FAILED;
            self::releaseRegistrationQuota($registration, false);

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            Log::write('活动报名待支付过期释放失败：' . $e->getMessage());
            return false;
        }
    }

    protected static function isPendingRegistrationExpired(ActivityRegistration $registration): bool
    {
        if ((int)$registration->registration_status !== ActivityRegistration::STATUS_PENDING_PAY) {
            return false;
        }

        $deadline = self::getPendingPayDeadline($registration);
        return $deadline > 0 && $deadline <= time();
    }

    protected static function getPendingPayDeadline(ActivityRegistration $registration): int
    {
        $deadline = (int)ActivityPayment::where('registration_id', (int)$registration->id)
            ->where('pay_status', ActivityPayment::STATUS_PENDING)
            ->order('id', 'desc')
            ->value('expire_time');
        if ($deadline <= 0) {
            $deadline = self::getModelTimestamp($registration, 'create_time') + self::DEFAULT_PAY_EXPIRE_MINUTES * 60;
        }
        return max($deadline, 0);
    }

    protected static function getModelTimestamp($model, string $field): int
    {
        $value = $model->getData($field);
        return self::parseTimestampValue($value);
    }

    protected static function parseTimestampValue($value): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        $timestamp = strtotime((string)$value);
        return $timestamp === false ? 0 : $timestamp;
    }

    protected static function checkActivityAvailable(array $dynamic, array $tickets): array
    {
        if ((int)($dynamic['status'] ?? Dynamic::STATUS_OFFLINE) !== Dynamic::STATUS_PUBLISHED) {
            return [false, '活动未发布'];
        }
        if ((int)($dynamic['activity_signup_enabled'] ?? 0) !== 1) {
            return [false, '报名未开启'];
        }
        $now = time();
        $deadline = (int)($dynamic['activity_signup_deadline'] ?? 0);
        if ($deadline > 0 && $deadline <= $now) {
            return [false, '报名已截止'];
        }
        $startTime = (int)($dynamic['activity_start_time'] ?? 0);
        if ($startTime > 0 && $startTime <= $now) {
            return [false, '活动已开始'];
        }
        if (empty($tickets)) {
            return [false, '暂无可报名票种'];
        }
        $totalQuota = (int)($dynamic['activity_total_quota'] ?? 0);
        if ($totalQuota > 0 && (int)($dynamic['activity_registered_count'] ?? 0) >= $totalQuota) {
            return [false, '活动名额已满'];
        }
        $hasTicketStock = false;
        foreach ($tickets as $ticket) {
            if ((int)($ticket['remaining_count'] ?? 0) > 0 && (int)($ticket['can_buy'] ?? 0) === 1) {
                $hasTicketStock = true;
                break;
            }
        }
        if ($hasTicketStock) {
            return [true, ''];
        }
        foreach ($tickets as $ticket) {
            if ((int)($ticket['remaining_count'] ?? 0) > 0) {
                return [false, (string)($ticket['buy_disabled_reason'] ?? '暂无当前可购买票种')];
            }
        }
        return [false, '票种已售罄'];
    }

    protected static function checkTicketBuyable(array $ticket): array
    {
        if ((int)($ticket['status'] ?? ActivityTicket::STATUS_DISABLED) !== ActivityTicket::STATUS_ENABLED) {
            return [false, '票种已停用'];
        }
        if ((int)($ticket['sold_count'] ?? 0) >= (int)($ticket['stock'] ?? 0)) {
            return [false, '该票种已售罄'];
        }
        $now = time();
        $saleStartTime = (int)($ticket['sale_start_time'] ?? 0);
        if ($saleStartTime > 0 && $saleStartTime > $now) {
            return [false, '该票种未开售'];
        }
        $saleEndTime = (int)($ticket['sale_end_time'] ?? 0);
        if ($saleEndTime > 0 && $saleEndTime <= $now) {
            return [false, '该票种已停售'];
        }
        return [true, ''];
    }

    protected static function buildTicketSaleTimeText(array $ticket): string
    {
        $saleStartTime = (int)($ticket['sale_start_time'] ?? 0);
        $saleEndTime = (int)($ticket['sale_end_time'] ?? 0);
        if ($saleStartTime <= 0 && $saleEndTime <= 0) {
            return '跟随活动报名时间';
        }

        $startText = $saleStartTime > 0 ? date('Y-m-d H:i', $saleStartTime) : '不限开始';
        $endText = $saleEndTime > 0 ? date('Y-m-d H:i', $saleEndTime) : '不限结束';
        return $startText . ' 至 ' . $endText;
    }

    protected static function buildPriceLabel(bool $hasFree, ?float $minPrice): string
    {
        if ($minPrice === null) {
            return '';
        }
        if ($hasFree || $minPrice <= 0) {
            return '免费';
        }
        return '¥' . number_format($minPrice, 2, '.', '') . '起';
    }

    protected static function validatePaidCallback(ActivityPayment $payment, array $callbackData, string $transactionId): string
    {
        if (in_array((int)$payment->pay_way, [ActivityPayment::WAY_WECHAT, ActivityPayment::WAY_ALIPAY], true) && trim($transactionId) === '') {
            return '支付回调缺少第三方交易号';
        }
        $attach = trim((string)($callbackData['attach'] ?? $callbackData['passback_params'] ?? ''));
        if ($attach !== self::PAY_FROM) {
            return '支付回调来源不是活动报名';
        }
        $outTradeNo = trim((string)($callbackData['out_trade_no'] ?? ''));
        if ($outTradeNo !== '' && $outTradeNo !== (string)$payment->payment_sn) {
            return '支付单号不匹配';
        }
        if (isset($callbackData['amount']) && is_array($callbackData['amount']) && array_key_exists('total', $callbackData['amount'])) {
            try {
                $expectedFen = MoneyService::yuanToFen($payment->pay_amount);
            } catch (\Throwable $e) {
                return '本地支付金额格式错误';
            }
            if ((int)$callbackData['amount']['total'] !== $expectedFen) {
                return '支付回调金额不一致';
            }
        }
        if (isset($callbackData['total_amount'])) {
            $actualAmount = round((float)$callbackData['total_amount'], 2);
            $expectedAmount = round((float)$payment->pay_amount, 2);
            if (abs($actualAmount - $expectedAmount) >= 0.01) {
                return '支付回调金额不一致';
            }
        }
        return '';
    }

    protected static function recordPaymentFlow(ActivityPayment $payment): void
    {
        FinancialFlow::safeCreateUniqueFlow([
            'flow_type' => FinancialFlow::FLOW_TYPE_INCOME,
            'biz_type' => FinancialFlow::BIZ_TYPE_OTHER,
            'biz_id' => (int)$payment->id,
            'biz_sn' => (string)$payment->payment_sn,
            'order_id' => 0,
            'user_id' => (int)$payment->user_id,
            'amount' => round((float)$payment->pay_amount, 2),
            'direction' => FinancialFlow::DIRECTION_IN,
            'pay_way' => self::mapPaymentPayWayToFlowPayWay((int)$payment->pay_way),
            'transaction_id' => (string)($payment->transaction_id ?? ''),
            'remark' => '活动报名支付入账',
            'operator_type' => 0,
            'operator_id' => 0,
            'create_time' => (int)$payment->pay_time ?: time(),
        ]);
    }

    protected static function recordRefundFlow(ActivityRefund $refund, ActivityPayment $payment, string $thirdRefundNo = ''): void
    {
        FinancialFlow::safeCreateUniqueFlow([
            'flow_type' => FinancialFlow::FLOW_TYPE_REFUND,
            'biz_type' => FinancialFlow::BIZ_TYPE_OTHER,
            'biz_id' => (int)$refund->id,
            'biz_sn' => (string)$refund->refund_sn,
            'order_id' => 0,
            'user_id' => (int)$refund->user_id,
            'amount' => round((float)$refund->actual_refund_amount, 2),
            'direction' => FinancialFlow::DIRECTION_OUT,
            'pay_way' => self::mapPaymentPayWayToFlowPayWay((int)$payment->pay_way),
            'transaction_id' => $thirdRefundNo,
            'remark' => '活动报名退款出账',
            'operator_type' => 0,
            'operator_id' => 0,
            'create_time' => (int)$refund->refund_time ?: time(),
        ]);
    }

    protected static function formatPayment(ActivityPayment $payment): array
    {
        return [
            'id' => (int)$payment->id,
            'payment_sn' => (string)$payment->payment_sn,
            'pay_amount' => round((float)$payment->pay_amount, 2),
            'pay_way' => (int)$payment->pay_way,
            'pay_way_desc' => ActivityPayment::getPayWayText((int)$payment->pay_way),
            'pay_status' => (int)$payment->pay_status,
            'pay_status_desc' => ActivityPayment::getStatusText((int)$payment->pay_status),
            'pay_time' => (int)($payment->pay_time ?? 0),
        ];
    }

    protected static function formatRegistration(ActivityRegistration $registration): array
    {
        return self::formatRegistrationArray($registration->toArray());
    }

    protected static function formatRegistrationList(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $registrationIds = array_values(array_unique(array_filter(array_map(static function ($item) {
            return (int)($item['id'] ?? 0);
        }, $items))));

        $latestRefunds = self::getLatestRefundsByRegistrationIds($registrationIds);

        return array_map(static function ($item) use ($latestRefunds) {
            $registrationId = (int)($item['id'] ?? 0);
            return self::formatRegistrationArray(
                $item,
                $latestRefunds[$registrationId] ?? null,
                true
            );
        }, $items);
    }

    protected static function getLatestRefundsByRegistrationIds(array $registrationIds): array
    {
        if (empty($registrationIds)) {
            return [];
        }

        $refunds = ActivityRefund::whereIn('registration_id', $registrationIds)
            ->order('id', 'desc')
            ->select();

        $latestRefunds = [];
        foreach ($refunds as $refund) {
            $registrationId = (int)$refund->registration_id;
            if ($registrationId > 0 && !isset($latestRefunds[$registrationId])) {
                $latestRefunds[$registrationId] = $refund;
            }
        }

        return $latestRefunds;
    }

    protected static function formatLatestRefund(?ActivityRefund $refund): ?array
    {
        if (!$refund) {
            return null;
        }

        return [
            'id' => (int)$refund->id,
            'refund_sn' => (string)$refund->refund_sn,
            'refund_amount' => round((float)$refund->refund_amount, 2),
            'actual_refund_amount' => round((float)$refund->actual_refund_amount, 2),
            'refund_status' => (int)$refund->refund_status,
            'refund_status_desc' => ActivityRefund::getStatusText((int)$refund->refund_status),
            'refund_reason' => (string)$refund->refund_reason,
            'audit_remark' => (string)$refund->audit_remark,
            'refund_msg' => (string)$refund->refund_msg,
            'refund_time' => self::getModelTimestamp($refund, 'refund_time'),
            'create_time' => self::getModelTimestamp($refund, 'create_time'),
        ];
    }

    protected static function formatRegistrationArray(
        array $item,
        ?ActivityRefund $latestRefund = null,
        bool $latestRefundLoaded = false
    ): array
    {
        foreach (['create_time', 'update_time', 'pay_time', 'cancel_apply_time', 'cancel_audit_time'] as $timeField) {
            $item[$timeField] = self::parseTimestampValue($item[$timeField] ?? 0);
        }
        $item['registration_status_desc'] = ActivityRegistration::getStatusText((int)($item['registration_status'] ?? 0));
        $item['pay_status_desc'] = ActivityRegistration::getPayStatusText((int)($item['pay_status'] ?? 0));
        $item['cancel_status_desc'] = ActivityRegistration::getCancelStatusText((int)($item['cancel_status'] ?? 0));
        $item['ticket_price_label'] = round((float)($item['ticket_price'] ?? 0), 2) <= 0
            ? '免费'
            : '¥' . number_format((float)$item['ticket_price'], 2, '.', '');
        $refund = $latestRefundLoaded
            ? $latestRefund
            : ActivityRefund::where('registration_id', (int)($item['id'] ?? 0))
                ->order('id', 'desc')
                ->find();
        $item['latest_refund'] = self::formatLatestRefund($refund);
        return $item;
    }

    protected static function mapPaymentPayWayToFlowPayWay(int $payWay): int
    {
        return [
            ActivityPayment::WAY_WECHAT => FinancialFlow::PAY_WAY_WECHAT,
            ActivityPayment::WAY_ALIPAY => FinancialFlow::PAY_WAY_ALIPAY,
            ActivityPayment::WAY_BALANCE => FinancialFlow::PAY_WAY_BALANCE,
            ActivityPayment::WAY_OFFLINE => FinancialFlow::PAY_WAY_OFFLINE,
        ][$payWay] ?? FinancialFlow::PAY_WAY_SYSTEM;
    }

    protected static function applyRegistrationFilters($query, array $params): void
    {
        if (!empty($params['dynamic_id'])) {
            $query->where('dynamic_id', (int)$params['dynamic_id']);
        }
        if (!empty($params['ticket_id'])) {
            $query->where('ticket_id', (int)$params['ticket_id']);
        }
        if (isset($params['registration_status']) && $params['registration_status'] !== '') {
            $query->where('registration_status', (int)$params['registration_status']);
        }
        if (isset($params['pay_status']) && $params['pay_status'] !== '') {
            $query->where('pay_status', (int)$params['pay_status']);
        }
        if (isset($params['cancel_status']) && $params['cancel_status'] !== '') {
            $query->where('cancel_status', (int)$params['cancel_status']);
        }
        if (!empty($params['keyword'])) {
            $keyword = trim((string)$params['keyword']);
            $query->where(function ($q) use ($keyword) {
                $q->whereLike('contact_name', '%' . $keyword . '%')
                    ->whereOr('contact_mobile', 'like', '%' . $keyword . '%');
            });
        }
    }
}
