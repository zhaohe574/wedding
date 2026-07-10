<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员补交平台抽成服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\PayEnum;
use app\common\enum\YesNoEnum;
use app\common\logic\BaseLogic;
use app\common\logic\PaymentLogic;
use app\common\model\financial\StaffSettlement;
use app\common\model\financial\StaffSettlementRepay;
use app\common\model\pay\PayWay;
use app\common\model\staff\Staff;
use app\common\service\pay\AliPayService;
use app\common\service\pay\WeChatPayService;
use think\facade\Db;
use think\facade\Log;

/**
 * 服务人员补交平台抽成服务
 */
class StaffSettlementRepayService extends BaseLogic
{
    public const PAY_FROM = 'staff_settlement_repay';
    public const DEFAULT_PAY_EXPIRE_MINUTES = 30;

    /**
     * @notes 获取补交支付方式
     */
    public static function getPayWay(int $userId, int $settlementId, int $terminal): array|false
    {
        try {
            $settlement = self::getUserSettlement($userId, $settlementId);
            $leftAmount = self::validatePayableSettlement($settlement);
            $payWays = PayWay::alias('pw')
                ->join('dev_pay_config dp', 'pw.pay_config_id = dp.id')
                ->where(['pw.scene' => $terminal, 'pw.status' => YesNoEnum::YES])
                ->whereIn('dp.pay_way', [PayEnum::WECHAT_PAY, PayEnum::ALI_PAY])
                ->field('dp.id,dp.name,dp.pay_way,dp.icon,dp.sort,dp.remark,pw.is_default')
                ->order('pw.is_default desc,dp.sort desc,id asc')
                ->select()
                ->toArray();

            foreach ($payWays as &$item) {
                if ((int)$item['pay_way'] === PayEnum::WECHAT_PAY) {
                    $item['extra'] = '微信快捷支付';
                } elseif ((int)$item['pay_way'] === PayEnum::ALI_PAY) {
                    $item['extra'] = '支付宝快捷支付';
                } else {
                    $item['extra'] = (string)($item['remark'] ?? '');
                }
            }
            unset($item);

            if (empty($payWays)) {
                throw new \RuntimeException('当前终端暂未开启微信或支付宝支付');
            }

            return [
                'lists' => array_values($payWays),
                'order_amount' => $leftAmount,
                'pay_deadline_time' => 0,
                'pay_remain_seconds' => 0,
                'total_amount' => round((float)$settlement->staff_due_platform_amount, 2),
                'pay_amount' => $leftAmount,
                'paid_amount' => round((float)$settlement->staff_due_collected_amount, 2),
                'unpaid_amount' => $leftAmount,
                'need_pay_amount' => $leftAmount,
                'need_pay_label' => '补交平台抽成',
                'pay_subject' => '服务人员补交平台抽成',
                'payment_mode' => self::PAY_FROM,
                'payment_channel' => 1,
            ];
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取补交预支付订单信息
     */
    public static function getPayOrderInfo(array $params): array|false
    {
        try {
            $settlementId = (int)($params['order_id'] ?? $params['settlement_id'] ?? 0);
            $userId = (int)($params['user_id'] ?? 0);
            $settlement = self::getUserSettlement($userId, $settlementId);
            $leftAmount = self::validatePayableSettlement($settlement);

            return [
                'id' => (int)$settlement->id,
                'settlement_id' => (int)$settlement->id,
                'sn' => (string)$settlement->settlement_sn,
                'user_id' => $userId,
                'order_amount' => $leftAmount,
                'pay_subject' => '服务人员补交平台抽成',
                'pay_deadline_time' => 0,
            ];
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 发起线上补交支付
     */
    public static function pay(int $payWay, array $order, int $terminal, string $redirectUrl = ''): array|false
    {
        if (!in_array($payWay, [PayEnum::WECHAT_PAY, PayEnum::ALI_PAY], true)) {
            self::setError('补交平台抽成仅支持微信或支付宝支付');
            return false;
        }

        $inTransaction = false;
        Db::startTrans();
        $inTransaction = true;
        try {
            $settlement = StaffSettlement::where('id', (int)($order['settlement_id'] ?? $order['id'] ?? 0))
                ->lock(true)
                ->find();
            if (!$settlement) {
                throw new \RuntimeException('结算记录不存在');
            }

            $leftAmount = self::validatePayableSettlement($settlement);
            $repay = StaffSettlementRepay::createRepay([
                'settlement_id' => (int)$settlement->id,
                'settlement_sn' => (string)$settlement->settlement_sn,
                'staff_id' => (int)$settlement->staff_id,
                'order_id' => (int)$settlement->order_id,
                'order_item_id' => (int)$settlement->order_item_id,
                'amount' => $leftAmount,
                'collect_way' => StaffSettlementRepay::collectWayFromPayWay($payWay),
                'pay_way' => $payWay,
                'pay_status' => StaffSettlementRepay::PAY_STATUS_PENDING,
            ]);

            $paySn = (string)$repay->repay_sn;
            if ($payWay === PayEnum::WECHAT_PAY) {
                $paySn = PaymentLogic::formatOrderSn((string)$repay->repay_sn, $terminal);
            }
            $repay->pay_sn = $paySn;
            $repay->save();
            Db::commit();
            $inTransaction = false;

            $payload = [
                'id' => (int)$settlement->id,
                'sn' => $payWay === PayEnum::ALI_PAY ? (string)$repay->repay_sn : $paySn,
                'pay_sn' => $paySn,
                'payment_sn' => $paySn,
                'user_id' => (int)$order['user_id'],
                'order_amount' => $leftAmount,
                'pay_subject' => '服务人员补交平台抽成',
                'redirect_url' => $redirectUrl ?: '/packages/pages/staff_settlement/staff_settlement',
            ];

            $payService = null;
            if ($payWay === PayEnum::WECHAT_PAY) {
                $payService = new WeChatPayService($terminal, (int)$order['user_id']);
                $result = $payService->pay(self::PAY_FROM, $payload);
            } else {
                $payService = new AliPayService($terminal);
                $result = $payService->pay(self::PAY_FROM, $payload);
            }

            if ($result === false) {
                self::setError($payService && method_exists($payService, 'getError') ? (string)$payService->getError() : '发起补交支付失败');
                return false;
            }

            $result['payment_sn'] = $paySn;
            $result['pay_sn'] = $paySn;
            $result['sn'] = (string)$repay->repay_sn;
            $result['settlement_id'] = (int)$settlement->id;
            $result['repay_id'] = (int)$repay->id;
            return $result;
        } catch (\Throwable $e) {
            if ($inTransaction) {
                Db::rollback();
            }
            self::setError($e->getMessage());
            Log::write('服务人员补交平台抽成预支付失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * @notes 线上支付成功回调
     */
    public static function paySuccess(string $paySn, string $transactionId, array $callbackData = []): array
    {
        Db::startTrans();
        try {
            $repay = self::findRepayByPaySn($paySn, true);
            if (!$repay) {
                Db::rollback();
                return [false, '补交记录不存在', []];
            }

            if ((int)$repay->pay_status === StaffSettlementRepay::PAY_STATUS_PAID) {
                Db::commit();
                return [true, '已处理', ['settlement_id' => (int)$repay->settlement_id, 'repay_id' => (int)$repay->id]];
            }

            if ((int)$repay->pay_status !== StaffSettlementRepay::PAY_STATUS_PENDING) {
                Db::rollback();
                return [false, '补交记录状态不允许处理回调', []];
            }

            $amountError = self::validateCallbackAmount($repay, $callbackData);
            if ($amountError !== '') {
                $repay->markFailed($amountError);
                Db::commit();
                return [false, $amountError, []];
            }

            if ($transactionId !== '' && StaffSettlementRepay::where('transaction_id', $transactionId)
                ->where('id', '<>', (int)$repay->id)
                ->where('pay_status', StaffSettlementRepay::PAY_STATUS_PAID)
                ->find()
            ) {
                Db::rollback();
                return [false, '第三方交易号已被其他补交记录处理', []];
            }

            $settlement = StaffSettlement::where('id', (int)$repay->settlement_id)->lock(true)->find();
            if (!$settlement) {
                Db::rollback();
                return [false, '结算记录不存在', []];
            }

            $leftAmount = $settlement->getDuePlatformLeftAmount();
            $repayAmount = round((float)$repay->amount, 2);
            if ($repayAmount <= 0 || $repayAmount - $leftAmount > 0.01) {
                Db::rollback();
                return [false, '补交金额超过当前待补金额', []];
            }

            $repay->markPaid($transactionId, $callbackData);
            $settlement->applyDueCollection($repayAmount, 0, '服务人员线上补交平台抽成');
            StaffSettlement::recordDueCollectionFlow($settlement, $repay);

            Db::commit();
            return [true, '补交成功', [
                'settlement_id' => (int)$settlement->id,
                'repay_id' => (int)$repay->id,
            ]];
        } catch (\Throwable $e) {
            Db::rollback();
            return [false, $e->getMessage(), []];
        }
    }

    /**
     * @notes 后台手动补入线下收款
     */
    public static function manualCollect(int $settlementId, float $amount, int $adminId, string $remark = ''): bool
    {
        Db::startTrans();
        try {
            $settlement = StaffSettlement::where('id', $settlementId)->lock(true)->find();
            if (!$settlement) {
                throw new \RuntimeException('结算记录不存在');
            }

            $amount = round($amount, 2);
            if ($amount <= 0) {
                throw new \RuntimeException('补入金额必须大于0');
            }

            $leftAmount = $settlement->getDuePlatformLeftAmount();
            if ($leftAmount <= 0) {
                throw new \RuntimeException('当前结算无待补平台金额');
            }
            if ($amount - $leftAmount > 0.01) {
                throw new \RuntimeException('补入金额不能超过剩余待补金额');
            }

            $repay = StaffSettlementRepay::createRepay([
                'settlement_id' => (int)$settlement->id,
                'settlement_sn' => (string)$settlement->settlement_sn,
                'staff_id' => (int)$settlement->staff_id,
                'order_id' => (int)$settlement->order_id,
                'order_item_id' => (int)$settlement->order_item_id,
                'amount' => $amount,
                'collect_way' => StaffSettlementRepay::COLLECT_WAY_OFFLINE,
                'pay_way' => 0,
                'pay_status' => StaffSettlementRepay::PAY_STATUS_PENDING,
                'admin_id' => $adminId,
                'remark' => $remark,
            ]);
            $transactionId = 'OFFLINE_DUE_' . (int)$repay->id;
            $repay->markPaid($transactionId, [
                'source' => 'admin_offline_collect',
                'admin_id' => $adminId,
                'remark' => $remark,
            ]);
            $settlement->applyDueCollection($amount, $adminId, $remark ?: '后台补入线下平台抽成');
            StaffSettlement::recordDueCollectionFlow($settlement, $repay);

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 查询补交支付状态
     */
    public static function getPayStatus(int $userId, int $settlementId, string $paySn = ''): array|false
    {
        try {
            $settlement = self::getUserSettlement($userId, $settlementId);
            $query = StaffSettlementRepay::where('settlement_id', $settlementId);
            if ($paySn !== '') {
                $query->where(function ($q) use ($paySn) {
                    $q->where('pay_sn', $paySn)->whereOr('repay_sn', $paySn);
                });
            }
            $repay = $query->order('id', 'desc')->find();

            return [
                'pay_status' => $repay && (int)$repay->pay_status === StaffSettlementRepay::PAY_STATUS_PAID ? PayEnum::ISPAID : PayEnum::UNPAID,
                'pay_way' => $repay ? (int)$repay->pay_way : 0,
                'order' => [
                    'id' => (int)$settlement->id,
                    'order_status' => 0,
                    'order_amount' => round((float)($repay->amount ?? $settlement->getDuePlatformLeftAmount()), 2),
                    'pay_deadline_time' => 0,
                    'pay_remain_seconds' => 0,
                ],
                'repay' => $repay ? self::formatRepay($repay) : null,
                'settlement' => [
                    'id' => (int)$settlement->id,
                    'staff_due_platform_amount' => round((float)$settlement->staff_due_platform_amount, 2),
                    'staff_due_collected_amount' => round((float)$settlement->staff_due_collected_amount, 2),
                    'staff_due_left_amount' => $settlement->getDuePlatformLeftAmount(),
                    'staff_due_collect_status' => (int)$settlement->staff_due_collect_status,
                    'staff_due_collect_status_text' => StaffSettlement::getDueCollectStatusDesc((int)$settlement->staff_due_collect_status),
                ],
            ];
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function formatRepay(StaffSettlementRepay $repay): array
    {
        return [
            'id' => (int)$repay->id,
            'repay_sn' => (string)$repay->repay_sn,
            'settlement_id' => (int)$repay->settlement_id,
            'amount' => round((float)$repay->amount, 2),
            'collect_way' => (int)$repay->collect_way,
            'collect_way_text' => StaffSettlementRepay::getCollectWayDesc((int)$repay->collect_way),
            'pay_way' => (int)$repay->pay_way,
            'pay_status' => (int)$repay->pay_status,
            'pay_status_text' => StaffSettlementRepay::getPayStatusDesc((int)$repay->pay_status),
            'pay_sn' => (string)$repay->pay_sn,
            'transaction_id' => (string)$repay->transaction_id,
            'admin_id' => (int)$repay->admin_id,
            'remark' => (string)$repay->remark,
            'pay_time' => (int)($repay->pay_time ?? 0),
            'create_time' => (int)($repay->create_time ?? 0),
        ];
    }

    protected static function getUserSettlement(int $userId, int $settlementId): StaffSettlement
    {
        if ($userId <= 0 || $settlementId <= 0) {
            throw new \RuntimeException('参数错误');
        }

        $staffId = (int)Staff::where('user_id', $userId)->value('id');
        if ($staffId <= 0) {
            throw new \RuntimeException('未绑定服务人员');
        }

        $settlement = StaffSettlement::where('id', $settlementId)
            ->where('staff_id', $staffId)
            ->find();
        if (!$settlement) {
            throw new \RuntimeException('结算记录不存在或无权限');
        }

        return $settlement;
    }

    protected static function validatePayableSettlement(StaffSettlement $settlement): float
    {
        $leftAmount = $settlement->getDuePlatformLeftAmount();
        if ($leftAmount <= 0) {
            throw new \RuntimeException('当前结算无待补平台金额');
        }
        if ((int)$settlement->status === StaffSettlement::STATUS_CANCELLED) {
            throw new \RuntimeException('已取消结算不可补交');
        }

        return $leftAmount;
    }

    protected static function findRepayByPaySn(string $paySn, bool $lock = false): ?StaffSettlementRepay
    {
        $paySn = trim($paySn);
        if ($paySn === '') {
            return null;
        }

        $query = StaffSettlementRepay::where(function ($q) use ($paySn) {
            $q->where('pay_sn', $paySn)->whereOr('repay_sn', $paySn);
        });
        if ($lock) {
            $query->lock(true);
        }

        return $query->find();
    }

    protected static function validateCallbackAmount(StaffSettlementRepay $repay, array $callbackData): string
    {
        $expectedFen = MoneyService::yuanToFen((float)$repay->amount);
        if (isset($callbackData['amount']) && is_array($callbackData['amount']) && isset($callbackData['amount']['total'])) {
            $actualFen = (int)$callbackData['amount']['total'];
            return $actualFen === $expectedFen ? '' : '微信回调金额与补交金额不一致';
        }

        if (isset($callbackData['total_amount'])) {
            $actualAmount = round((float)$callbackData['total_amount'], 2);
            return abs($actualAmount - round((float)$repay->amount, 2)) < 0.01 ? '' : '支付宝回调金额与补交金额不一致';
        }

        return '';
    }
}
