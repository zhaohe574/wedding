<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员补交平台抽成服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\PayEnum;
use app\common\enum\user\UserTerminalEnum;
use app\common\enum\YesNoEnum;
use app\common\logic\BaseLogic;
use app\common\model\financial\StaffSettlement;
use app\common\model\financial\FinancialFlow;
use app\common\model\financial\StaffSettlementRepay;
use app\common\model\pay\PayWay;
use app\common\model\staff\Staff;
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
                ->whereIn('dp.pay_way', [PayEnum::WECHAT_PAY])
                ->field('dp.id,dp.name,dp.pay_way,dp.icon,dp.sort,dp.remark,pw.is_default')
                ->order('pw.is_default desc,dp.sort desc,id asc')
                ->select()
                ->toArray();

            foreach ($payWays as &$item) {
                $item['extra'] = '微信支付';
            }
            unset($item);

            if (empty($payWays)) {
                throw new \RuntimeException('当前终端暂未开启微信小程序支付');
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
                'pay_deadline_time' => (int)$repay->expire_time,
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
        if (!in_array($payWay, [PayEnum::WECHAT_PAY], true)) {
            self::setError('补交平台抽成仅支持微信小程序支付');
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
            $repay = StaffSettlementRepay::where('settlement_id', (int)$settlement->id)
                ->where('pay_status', StaffSettlementRepay::PAY_STATUS_PENDING)
                ->where('closed_time', 0)->lock(true)->order('id', 'desc')->find();
            if ($repay && MoneyService::yuanToFen($repay->amount) !== MoneyService::yuanToFen($leftAmount)) {
                throw new \RuntimeException('已有待确认的补交流水，请查询并关闭后重试');
            }
            $repay = $repay ?: StaffSettlementRepay::createRepay([
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
            $repay->pay_sn = $paySn;
            $repay->save();
            Db::commit();
            $inTransaction = false;

            $payload = [
                'id' => (int)$settlement->id,
                'sn' => $paySn,
                'pay_sn' => $paySn,
                'payment_sn' => $paySn,
                'user_id' => (int)$order['user_id'],
                'order_amount' => $leftAmount,
                'pay_subject' => '服务人员补交平台抽成',
                'redirect_url' => $redirectUrl ?: '/packages/pages/staff_settlement/staff_settlement',
            ];

            $payService = new WeChatPayService($terminal, (int)$order['user_id']);
            $result = $payService->pay(self::PAY_FROM, $payload);

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

            $amountError = self::validateCallbackAmount($repay, $callbackData);
            if ($amountError !== '' || trim($transactionId) === '') {
                throw new \RuntimeException($amountError ?: '支付回调缺少第三方交易号');
            }
            if ((int)$repay->pay_status === StaffSettlementRepay::PAY_STATUS_PAID
                && (string)$repay->transaction_id !== $transactionId) {
                throw new \RuntimeException('重复支付回调交易号不一致');
            }
            if ((int)$repay->pay_status === StaffSettlementRepay::PAY_STATUS_PAID) {
                Db::commit();
                return [true, '已处理', ['settlement_id' => (int)$repay->settlement_id, 'repay_id' => (int)$repay->id]];
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
            if ((int)$settlement->status === StaffSettlement::STATUS_CANCELLED
                || (int)$repay->closed_time > 0
                || MoneyService::yuanToFen($repayAmount) > MoneyService::yuanToFen($leftAmount)) {
                $repay->markPaid($transactionId, $callbackData);
                $repay->is_compensation = 1;
                $repay->refund_sn = 'R' . $repay->repay_sn;
                $repay->refund_status = 1;
                $repay->fail_reason = '结算已关闭或金额已结清，实收进入原路补偿退款';
                $repay->save();
                StaffSettlement::recordDueCollectionFlow($settlement, $repay);
                Db::commit();
                return [true, '实收已登记，补偿退款处理中', ['settlement_id' => (int)$settlement->id, 'repay_id' => (int)$repay->id]];
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
            if ($repay && (int)$repay->pay_way === PayEnum::WECHAT_PAY) {
                WeChatPayService::reconcilePayment($repay, 'pay_sn');
                $repay = StaffSettlementRepay::find((int)$repay->id);
                $settlement = self::getUserSettlement($userId, $settlementId);
            }

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

    public static function processCompensationRefunds(): void
    {
        $rows = StaffSettlementRepay::where('is_compensation', 1)->whereIn('refund_status', [1, 2])
            ->where('refund_query_time', '<=', time() - 60)->order('refund_query_time')->limit(100)->select();
        foreach ($rows as $row) {
            $claimed = StaffSettlementRepay::where('id', (int)$row->id)
                ->where('refund_query_time', '<=', time() - 60)->update(['refund_query_time' => time()]);
            if (!$claimed) continue;
            try {
                $service = new WeChatPayService(UserTerminalEnum::WECHAT_MMP);
                $result = (int)$row->refund_status === 1
                    ? $service->refund(['transaction_id' => $row->transaction_id, 'refund_sn' => $row->refund_sn,
                        'refund_amount' => (float)$row->amount, 'total_amount' => (float)$row->amount])
                    : $service->queryRefund((string)$row->refund_sn);
                $result['refund_status'] = $result['status'] ?? '';
                self::handleWechatRefundCallback($result);
            } catch (\Throwable $e) {
                Log::error('抽成补偿退款结果待确认：' . $e->getMessage());
            }
        }
    }

    public static function handleWechatRefundCallback(array $data): bool
    {
        return Db::transaction(static function () use ($data): bool {
            $sn = (string)($data['out_refund_no'] ?? '');
            if ($sn === '') return false;
            $repay = StaffSettlementRepay::where('refund_sn', $sn)->where('is_compensation', 1)->lock(true)->find();
            if (!$repay || WeChatPayService::validateRefundResult($data, (string)$repay->pay_sn,
                (string)$repay->transaction_id, $repay->amount, $repay->amount) !== '') return false;
            if ((int)$repay->refund_status === 3) return true;
            $status = strtoupper((string)($data['refund_status'] ?? $data['status'] ?? ''));
            if (!in_array($status, ['SUCCESS', 'PROCESSING', 'ABNORMAL', 'CLOSED'], true)) return false;
            if ($status === 'CLOSED') {
                $repay->refund_sn = 'R' . $repay->repay_sn . '-' . bin2hex(random_bytes(3));
                $repay->refund_status = 1;
            } else {
                $repay->refund_status = $status === 'SUCCESS' ? 3 : ($status === 'PROCESSING' ? 2 : 4);
            }
            $repay->refund_transaction_id = (string)($data['refund_id'] ?? '');
            $repay->save();
            if ($status === 'SUCCESS') {
                FinancialFlow::createUniqueFlow([
                    'flow_type' => FinancialFlow::FLOW_TYPE_REFUND, 'biz_type' => FinancialFlow::BIZ_TYPE_PLATFORM_FEE_REFUND,
                    'biz_id' => (int)$repay->id, 'biz_sn' => $sn, 'order_id' => (int)$repay->order_id,
                    'staff_id' => (int)$repay->staff_id, 'amount' => (float)$repay->amount,
                    'direction' => FinancialFlow::DIRECTION_OUT, 'pay_way' => FinancialFlow::PAY_WAY_WECHAT,
                    'transaction_id' => (string)$repay->refund_transaction_id, 'remark' => '抽成补交异常实收原路退款',
                ]);
            }
            return true;
        });
    }

    public static function formatRepay(StaffSettlementRepay $repay): array
    {
        return [
            'id' => (int)$repay->id,
            'repay_sn' => (string)$repay->repay_sn,
            'settlement_id' => (int)$repay->settlement_id,
            'amount' => round((float)$repay->amount, 2),
            'is_compensation' => (int)$repay->is_compensation,
            'refund_status' => (int)$repay->refund_status,
            'refund_sn' => (string)$repay->refund_sn,
            'fail_reason' => (string)$repay->fail_reason,
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
        if ((int)$repay->pay_way !== PayEnum::WECHAT_PAY) {
            return '不支持的抽成补交渠道';
        }
        $userId = (int)Staff::where('id', (int)$repay->staff_id)->value('user_id');
        return WeChatPayService::validatePaymentResult($callbackData, (string)$repay->pay_sn,
            $repay->amount, $userId, self::PAY_FROM);
    }
}
