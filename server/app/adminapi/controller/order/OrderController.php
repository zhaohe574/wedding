<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\order;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\order\OrderLists;
use app\adminapi\lists\order\OrderLogLists;
use app\adminapi\logic\order\OrderLogic;
use app\adminapi\validate\order\OrderValidate;
use app\common\model\order\OrderChange;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\service\StaffScheduleConfirmLetterService;
use app\common\service\StaffService;

/**
 * 订单管理控制器
 * Class OrderController
 * @package app\adminapi\controller\order
 */
class OrderController extends BaseAdminController
{
    /**
     * @notes 获取服务人员数据范围（my* 接口必须）
     * @return int
     */
    protected function getRequiredStaffScopeId(): int
    {
        return StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
    }

    /**
     * @notes 订单列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new OrderLists());
    }

    /**
     * @notes 订单详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new OrderValidate())->goCheck('detail');
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
        $result = OrderLogic::detail((int)$params['id']);
        if ($result === null) {
            return $this->fail('订单不存在');
        }
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        $canManageWholeOrder = true;
        if ($staffScopeId > 0) {
            $result = $this->applyStaffVisibleOrderAmounts($result, $staffScopeId);
            $items = $result['items'] ?? [];
            $canManageWholeOrder = $this->canStaffManageWholeOrder($result, $staffScopeId);
            $result['can_staff_manage_payment'] = $canManageWholeOrder;
            foreach ($items as $index => $item) {
                if ((int)($item['staff_id'] ?? 0) === $staffScopeId) {
                    continue;
                }
                $items[$index]['package_name'] = '--';
                $items[$index]['package_description'] = '';
                $items[$index]['price'] = 0;
                $items[$index]['subtotal'] = 0;
                $items[$index]['addons'] = [];
                if (isset($items[$index]['item_meta']) && is_array($items[$index]['item_meta'])) {
                    unset($items[$index]['item_meta']['label']);
                }
            }
            $result['items'] = $items;
            $result = array_merge($result, Order::buildPaymentSummaryFromState($result));
            $result['refundable_amount'] = min(
                (float)($result['refundable_amount'] ?? 0),
                (float)($result['paid_amount'] ?? 0)
            );
            $result['can_admin_refund'] = $result['refundable_amount'] > 0
                && !empty($result['can_admin_refund'])
                && !empty($result['can_staff_manage_payment']);
        }
        $result = $this->appendDirectRescheduleFlag($result, $staffScopeId, $canManageWholeOrder);
        $result = $this->appendScheduleConfirmLetterContext($result, $staffScopeId);
        return $this->data($result);
    }

    /**
     * @notes 订单详情附加档期确认海报生成上下文
     */
    protected function appendScheduleConfirmLetterContext(array $order, int $staffScopeId = 0): array
    {
        $candidates = [];
        $seenStaffIds = [];
        foreach (($order['items'] ?? []) as $item) {
            $staffId = (int)($item['staff_id'] ?? 0);
            if ($staffId <= 0 || isset($seenStaffIds[$staffId])) {
                continue;
            }
            if ($staffScopeId > 0 && $staffId !== $staffScopeId) {
                continue;
            }
            if (!in_array((int)($item['item_type'] ?? OrderItem::TYPE_SERVICE), [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF], true)) {
                continue;
            }
            if ((int)($item['item_status'] ?? OrderItem::STATUS_PENDING) === OrderItem::STATUS_CANCELLED) {
                continue;
            }

            $versions = StaffScheduleConfirmLetterService::listConfigs($staffId, false);
            $defaultConfigId = 0;
            foreach ($versions as $version) {
                if ((int)($version['is_default'] ?? 0) === 1) {
                    $defaultConfigId = (int)($version['config_id'] ?? 0);
                    break;
                }
            }
            if ($defaultConfigId <= 0 && !empty($versions[0]['config_id'])) {
                $defaultConfigId = (int)$versions[0]['config_id'];
            }

            $itemMeta = is_array($item['item_meta'] ?? null) ? $item['item_meta'] : [];
            $staffName = trim((string)($item['staff_name'] ?? ($item['staff']['name'] ?? '')));
            $serviceName = trim((string)($item['package_name'] ?? ($itemMeta['label'] ?? '')));
            $candidates[] = [
                'staff_id' => $staffId,
                'staff_name' => $staffName !== '' ? $staffName : ('服务人员' . $staffId),
                'service_name' => $serviceName !== '' ? $serviceName : ((string)($item['item_type_desc'] ?? '服务项')),
                'service_date' => (string)($item['service_date'] ?? ($order['service_date'] ?? '')),
                'item_type_desc' => (string)($item['item_type_desc'] ?? '服务项'),
                'versions' => $versions,
                'default_config_id' => $defaultConfigId,
            ];
            $seenStaffIds[$staffId] = true;
        }

        $order['schedule_confirm_letter'] = [
            'candidates' => $candidates,
            'default_staff_id' => (int)($candidates[0]['staff_id'] ?? 0),
        ];

        return $order;
    }

    /**
     * @notes 订单详情按服务人员视角重算金额
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

    /**
     * @notes 创建订单（后台创建）
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new OrderValidate())->post()->goCheck('add');
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $items = $params['items'] ?? [];
            if (empty($items)) {
                return $this->fail('请选择服务人员');
            }
            foreach ($items as $item) {
                if ((int)($item['staff_id'] ?? 0) !== $staffScopeId) {
                    return $this->fail('无权限操作');
                }
            }
        }
        $params['admin_id'] = $this->adminId;
        $result = OrderLogic::add($params);
        if (true === $result) {
            return $this->success('创建成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 编辑订单信息
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new OrderValidate())->post()->goCheck('edit');
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
        $params['admin_id'] = $this->adminId;
        $result = OrderLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 取消订单
     * @return \think\response\Json
     */
    public function cancel()
    {
        $params = (new OrderValidate())->post()->goCheck('cancel');
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
        if ($response = $this->checkWholeOrderManageScope((int)$params['id'])) {
            return $response;
        }
        $result = OrderLogic::cancel((int)$params['id'], $this->adminId, $params['reason'] ?? '');
        if (true === $result) {
            return $this->success('取消成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 确认订单
     * @return \think\response\Json
     */
    public function confirm()
    {
        $params = (new OrderValidate())->post()->goCheck('detail');
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }

        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        $result = $staffScopeId > 0
            ? OrderLogic::confirmByStaff((int)$params['id'], $staffScopeId, $this->adminId)
            : OrderLogic::confirmByAdmin((int)$params['id'], $this->adminId);

        if (true === $result) {
            return $this->success('确认成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 开始服务
     * @return \think\response\Json
     */
    public function startService()
    {
        $params = (new OrderValidate())->post()->goCheck('detail');
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
        if ($response = $this->checkWholeOrderManageScope((int)$params['id'], '共享订单不支持当前整单履约操作')) {
            return $response;
        }
        $result = OrderLogic::startService((int)$params['id'], $this->adminId);
        if (true === $result) {
            return $this->success('操作成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 完成订单
     * @return \think\response\Json
     */
    public function complete()
    {
        $params = (new OrderValidate())->post()->goCheck('detail');
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
        if ($response = $this->checkWholeOrderManageScope((int)$params['id'], '共享订单不支持当前整单履约操作')) {
            return $response;
        }
        $result = OrderLogic::complete((int)$params['id'], $this->adminId);
        if (true === $result) {
            return $this->success('操作成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 后台直接改期
     * @return \think\response\Json
     */
    public function directReschedule()
    {
        $params = (new OrderValidate())->post()->goCheck('directReschedule');
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }

        $params['admin_id'] = $this->adminId;
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        $result = OrderLogic::directReschedule($params, $staffScopeId);
        if ($result !== false) {
            return $this->success('改期成功', $result, 1, 1);
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 确认线下支付
     * @return \think\response\Json
     */
    public function confirmOfflinePay()
    {
        $params = (new OrderValidate())->post()->goCheck('confirmPay');
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
        if ($response = $this->checkWholeOrderManageScope((int)$params['id'])) {
            return $response;
        }
        $params['admin_id'] = $this->adminId;
        $result = OrderLogic::confirmOfflinePay($params);
        if (true === $result) {
            return $this->success('确认成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 审核线下支付凭证
     * @return \think\response\Json
     */
    public function auditVoucher()
    {
        $params = (new OrderValidate())->post()->goCheck('auditVoucher');
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
        if ($response = $this->checkWholeOrderManageScope((int)$params['id'])) {
            return $response;
        }
        $params['admin_id'] = $this->adminId;
        $result = OrderLogic::auditPayVoucher($params);
        if (true === $result) {
            return $this->success('审核成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 添加备注
     * @return \think\response\Json
     */
    public function addRemark()
    {
        $params = (new OrderValidate())->post()->goCheck('remark');
        if ($this->checkOrderScope((int)$params['id']) !== null) {
            return $this->checkOrderScope((int)$params['id']);
        }
        $result = OrderLogic::addRemark((int)$params['id'], $this->adminId, $params['remark']);
        if (true === $result) {
            return $this->success('添加成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 订单操作日志
     * @return \think\response\Json
     */
    public function logs()
    {
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $orderId = (int) $this->request->get('order_id', 0);
            if ($orderId <= 0) {
                return $this->fail('无权限操作');
            }
            $exists = OrderItem::where('order_id', $orderId)
                ->where('staff_id', $staffScopeId)
                ->find();
            if (!$exists) {
                return $this->fail('无权限操作');
            }
        }
        return $this->dataLists(new OrderLogLists());
    }

    /**
     * @notes 订单统计
     * @return \think\response\Json
     */
    public function statistics()
    {
        $params = $this->request->get();
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $params['staff_id'] = $staffScopeId;
        }
        $result = OrderLogic::statistics($params);
        return $this->data($result);
    }

    /**
     * @notes 后台建单支付预估
     * @return \think\response\Json
     */
    public function estimatePayment()
    {
        $params = (new OrderValidate())->post()->goCheck('addEstimate');
        $result = OrderLogic::estimatePayment($params);
        return $this->data($result);
    }

    /**
     * @notes 获取线下建单主套餐
     * @return \think\response\Json
     */
    public function offlineMainPackages()
    {
        try {
            $params = (new OrderValidate())->goCheck('offlineMainPackages');
            $params['main_staff_id'] = $this->applyOfflineMainStaffScope((int) ($params['main_staff_id'] ?? 0));
            if ((int) $params['main_staff_id'] <= 0) {
                return $this->fail('无权限操作');
            }
            $result = OrderLogic::getOfflineMainPackages($params);
            return $this->data($result);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 获取线下建单协作角色候选人
     * @return \think\response\Json
     */
    public function offlineRoleCandidates()
    {
        try {
            $params = (new OrderValidate())->goCheck('offlineRoleCandidates');
            $params['main_staff_id'] = $this->applyOfflineMainStaffScope((int) ($params['main_staff_id'] ?? 0));
            if ((int) $params['main_staff_id'] <= 0) {
                return $this->fail('无权限操作');
            }
            $result = OrderLogic::getOfflineRoleCandidates($params);
            return $this->data($result);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 线下建单金额预估
     * @return \think\response\Json
     */
    public function estimateOffline()
    {
        try {
            $params = (new OrderValidate())->post()->goCheck('estimateOffline');
            $params['main_staff_id'] = $this->applyOfflineMainStaffScope((int) ($params['main_staff_id'] ?? 0));
            if ((int) $params['main_staff_id'] <= 0) {
                return $this->fail('无权限操作');
            }
            $result = OrderLogic::estimateOffline($params);
            return $this->data($result);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 新增线下订单
     * @return \think\response\Json
     */
    public function addOffline()
    {
        $params = (new OrderValidate())->post()->goCheck('addOffline');
        $params['main_staff_id'] = $this->applyOfflineMainStaffScope((int) ($params['main_staff_id'] ?? 0));
        if ((int) $params['main_staff_id'] <= 0) {
            return $this->fail('无权限操作');
        }
        $params['admin_id'] = $this->adminId;
        $result = OrderLogic::addOffline($params);
        if (true === $result) {
            return $this->success('创建成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 获取订单状态选项
     * @return \think\response\Json
     */
    public function statusOptions()
    {
        $result = OrderLogic::getStatusOptions();
        return $this->data($result);
    }

    /**
     * @notes 获取支付方式选项
     * @return \think\response\Json
     */
    public function payWayOptions()
    {
        $result = OrderLogic::getPayWayOptions();
        return $this->data($result);
    }

    /**
     * @notes 导出订单
     * @return \think\response\Json
     */
    public function export()
    {
        return $this->dataLists(new OrderLists());
    }

    /**
     * @notes 校验订单数据范围
     * @param int $orderId
     * @return \think\response\Json|null
     */
    protected function checkOrderScope(int $orderId)
    {
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId <= 0) {
            return null;
        }
        $exists = OrderItem::where('order_id', $orderId)
            ->where('staff_id', $staffScopeId)
            ->find();
        if (!$exists) {
            return $this->fail('无权限操作');
        }
        return null;
    }

    /**
     * @notes 校验 staff 是否可管理整单支付动作（必须整单都归属当前 staff）
     * @param int $orderId
     * @return \think\response\Json|null
     */
    protected function checkWholeOrderManageScope(int $orderId, string $message = '共享订单不支持当前支付/退款操作')
    {
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId <= 0) {
            return null;
        }
        if (!Order::isWholeOrderOwnedByStaff($orderId, $staffScopeId)) {
            return $this->fail($message);
        }
        return null;
    }

    /**
     * @notes 判断详情是否允许当前 staff 管理整单支付动作
     */
    protected function canStaffManageWholeOrder(array $order, int $staffScopeId): bool
    {
        $items = $order['items'] ?? [];
        if (empty($items)) {
            return false;
        }
        $activeCount = 0;
        foreach ($items as $item) {
            if ((int)($item['item_status'] ?? 0) === OrderItem::STATUS_CANCELLED) {
                continue;
            }
            $activeCount++;
            if ((int)($item['staff_id'] ?? 0) !== $staffScopeId) {
                return false;
            }
        }
        return $activeCount > 0;
    }

    /**
     * @notes 补充直接改期权限标记
     */
    protected function appendDirectRescheduleFlag(array $order, int $staffScopeId = 0, bool $canManageWholeOrder = true): array
    {
        $order['can_direct_reschedule'] = OrderChange::canDirectRescheduleByState(
            (int)($order['order_status'] ?? -1),
            !empty($order['is_paused']),
            OrderChange::hasPendingChange((int)($order['id'] ?? 0)),
            $staffScopeId,
            $canManageWholeOrder
        ) ? 1 : 0;

        return $order;
    }

    /**
     * @notes 应用线下建单主服务人员数据范围
     * @param int $mainStaffId
     * @return int
     */
    protected function applyOfflineMainStaffScope(int $mainStaffId): int
    {
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId <= 0) {
            return $mainStaffId;
        }

        if ($mainStaffId > 0 && $mainStaffId !== $staffScopeId) {
            return 0;
        }

        return $staffScopeId;
    }

    /**
     * @notes 我的订单列表
     * @return \think\response\Json
     */
    public function myOrders()
    {
        if ($this->getRequiredStaffScopeId() <= 0) {
            return $this->failRequiredStaffScope();
        }
        return $this->dataLists(new OrderLists());
    }

    /**
     * @notes 我的订单详情（整单 + 他人项脱敏）
     * @return \think\response\Json
     */
    public function myOrderDetail()
    {
        $staffScopeId = $this->getRequiredStaffScopeId();
        if ($staffScopeId <= 0) {
            return $this->failRequiredStaffScope();
        }

        $params = (new OrderValidate())->goCheck('detail');
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }

        $result = OrderLogic::detail((int)$params['id']);
        if ($result === null) {
            return $this->fail('订单不存在');
        }

        $result = OrderLogic::applyStaffVisibleOrderAmounts($result, $staffScopeId);
        $canManageWholeOrder = $this->canStaffManageWholeOrder($result, $staffScopeId);
        $result['can_staff_manage_payment'] = $canManageWholeOrder;

        if (!empty($result['items']) && is_array($result['items'])) {
            foreach ($result['items'] as $index => $item) {
                $itemStaffId = (int)($item['staff_id'] ?? 0);
                if ($itemStaffId !== $staffScopeId) {
                    $result['items'][$index]['package_name'] = '--';
                    $result['items'][$index]['price'] = '--';
                    $result['items'][$index]['quantity'] = '--';
                    $result['items'][$index]['subtotal'] = '--';
                    $result['items'][$index]['remark'] = '--';
                    $result['items'][$index]['schedule_id'] = 0;
                    $result['items'][$index]['addons'] = [];
                    $result['items'][$index]['addon_amount'] = '--';
                    if (isset($result['items'][$index]['staff']) && is_array($result['items'][$index]['staff'])) {
                        $result['items'][$index]['staff']['avatar'] = '';
                    }
                }
            }
        }

        $result = $this->appendDirectRescheduleFlag($result, $staffScopeId, $canManageWholeOrder);
        return $this->data($result);
    }

    /**
     * @notes 我的订单确认
     * @return \think\response\Json
     */
    public function myOrderConfirm()
    {
        $staffScopeId = $this->getRequiredStaffScopeId();
        if ($staffScopeId <= 0) {
            return $this->failRequiredStaffScope();
        }

        $params = (new OrderValidate())->post()->goCheck('detail');
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }

        $result = OrderLogic::confirmByStaff((int)$params['id'], $staffScopeId, $this->adminId);
        if (true === $result) {
            return $this->success('确认成功', [], 1, 1);
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 删除订单
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new OrderValidate())->post()->goCheck('delete');
        if (StaffService::getStaffScopeId($this->adminId, $this->adminInfo) > 0) {
            return $this->fail('无权限操作');
        }
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
        $result = OrderLogic::delete((int)$params['id'], $this->adminId);
        if (true === $result) {
            return $this->success('删除成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 我的订单开始服务
     * @return \think\response\Json
     */
    public function myOrderStartService()
    {
        if ($this->getRequiredStaffScopeId() <= 0) {
            return $this->failRequiredStaffScope();
        }
        $params = (new OrderValidate())->post()->goCheck('detail');
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
        if ($response = $this->checkWholeOrderManageScope((int)$params['id'], '共享订单不支持当前整单履约操作')) {
            return $response;
        }
        $result = OrderLogic::startService((int)$params['id'], $this->adminId);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 我的订单完成
     * @return \think\response\Json
     */
    public function myOrderComplete()
    {
        if ($this->getRequiredStaffScopeId() <= 0) {
            return $this->failRequiredStaffScope();
        }
        $params = (new OrderValidate())->post()->goCheck('detail');
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
        if ($response = $this->checkWholeOrderManageScope((int)$params['id'], '共享订单不支持当前整单履约操作')) {
            return $response;
        }
        $result = OrderLogic::complete((int)$params['id'], $this->adminId);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 我的订单直接改期
     * @return \think\response\Json
     */
    public function myOrderDirectReschedule()
    {
        $staffScopeId = $this->getRequiredStaffScopeId();
        if ($staffScopeId <= 0) {
            return $this->failRequiredStaffScope();
        }
        $params = (new OrderValidate())->post()->goCheck('directReschedule');
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
        if ($response = $this->checkWholeOrderManageScope((int)$params['id'], '共享订单不支持服务人员直接改期，请联系管理员处理')) {
            return $response;
        }

        $params['admin_id'] = $this->adminId;
        $result = OrderLogic::directReschedule($params, $staffScopeId);
        if ($result !== false) {
            return $this->success('改期成功', $result, 1, 1);
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 我的订单统计
     * @return \think\response\Json
     */
    public function myOrderStatistics()
    {
        $staffScopeId = $this->getRequiredStaffScopeId();
        if ($staffScopeId <= 0) {
            return $this->failRequiredStaffScope();
        }
        $params = $this->request->get();
        $params['staff_id'] = $staffScopeId;
        $result = OrderLogic::statistics($params);
        return $this->data($result);
    }

    public function confirmLetterGenerate()
    {
        $params = (new OrderValidate())->post()->goCheck('confirmLetterGenerate');
        $orderId = (int)$params['id'];
        $staffId = (int)($params['staff_id'] ?? 0);
        $configId = (int)($params['config_id'] ?? 0);
        if ($response = $this->checkOrderScope($orderId)) {
            return $response;
        }
        if ($response = $this->checkScheduleConfirmLetterStaffScope($orderId, $staffId)) {
            return $response;
        }

        try {
            return $this->success('生成成功', StaffScheduleConfirmLetterService::generate(
                $orderId,
                $staffId,
                'admin',
                $this->adminId,
                $configId
            ), 1, 1);
        } catch (\Throwable $e) {
            return $this->fail(StaffScheduleConfirmLetterService::normalizeErrorMessage($e->getMessage()));
        }
    }

    public function confirmLetterPush()
    {
        return $this->fail('档期确认函不支持推送客户');
    }

    public function confirmLetterDetail()
    {
        $params = (new OrderValidate())->goCheck('confirmLetterDetail');
        $staffId = (int)($params['staff_id'] ?? 0);
        if ($staffId <= 0) {
            return $this->fail('请选择服务人员');
        }

        try {
            $letter = StaffScheduleConfirmLetterService::detail((int)$params['letter_id'], $staffId);
            if (!$letter) {
                return $this->fail('档期确认海报不存在');
            }
            if ($response = $this->checkOrderScope((int)($letter['order_id'] ?? 0))) {
                return $response;
            }
            if ($response = $this->checkScheduleConfirmLetterStaffScope((int)($letter['order_id'] ?? 0), $staffId)) {
                return $response;
            }
            return $this->data($letter);
        } catch (\Throwable $e) {
            return $this->fail(StaffScheduleConfirmLetterService::normalizeErrorMessage($e->getMessage()));
        }
    }

    public function confirmLetterHistory()
    {
        $params = (new OrderValidate())->goCheck('confirmLetterHistory');
        $orderId = (int)$params['id'];
        $staffId = (int)($params['staff_id'] ?? 0);
        if ($response = $this->checkOrderScope($orderId)) {
            return $response;
        }
        if ($response = $this->checkScheduleConfirmLetterStaffScope($orderId, $staffId)) {
            return $response;
        }

        return $this->data(StaffScheduleConfirmLetterService::history($orderId, $staffId));
    }

    public function confirmLetterAssets()
    {
        $params = (new OrderValidate())->post()->goCheck('confirmLetterAssets');
        $staffId = (int)($params['staff_id'] ?? 0);
        if ($staffId <= 0) {
            return $this->fail('请选择服务人员');
        }

        try {
            $letter = StaffScheduleConfirmLetterService::detail((int)$params['letter_id'], $staffId);
            if (!$letter) {
                return $this->fail('档期确认海报不存在');
            }
            if ($response = $this->checkOrderScope((int)($letter['order_id'] ?? 0))) {
                return $response;
            }
            if ($response = $this->checkScheduleConfirmLetterStaffScope((int)($letter['order_id'] ?? 0), $staffId)) {
                return $response;
            }
            return $this->success('图片已更新', StaffScheduleConfirmLetterService::regenerateAssets(
                (int)$params['letter_id'],
                (string)($params['snapshot_hash'] ?? ''),
                $staffId,
                true
            ), 1, 1);
        } catch (\Throwable $e) {
            return $this->fail(StaffScheduleConfirmLetterService::normalizeErrorMessage($e->getMessage()));
        }
    }

    /**
     * @notes 校验后台生成档期确认海报的服务人员范围
     */
    protected function checkScheduleConfirmLetterStaffScope(int $orderId, int $staffId)
    {
        if ($staffId <= 0) {
            return $this->fail('请选择服务人员');
        }

        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0 && $staffId !== $staffScopeId) {
            return $this->fail('无权限操作');
        }

        $exists = OrderItem::where('order_id', $orderId)
            ->where('staff_id', $staffId)
            ->whereIn('item_type', [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF])
            ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->find();
        if (!$exists) {
            return $this->fail('该服务人员未绑定当前订单，不能生成档期确认海报');
        }

        return null;
    }
}
