<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\order;

use app\adminapi\lists\BaseAdminDataLists;
use app\adminapi\logic\order\OrderLogic;
use app\common\lists\ListsExcelInterface;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;

/**
 * 订单列表
 * Class OrderLists
 * @package app\adminapi\lists\order
 */
class OrderLists extends BaseAdminDataLists implements ListsExcelInterface
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
        if ($staffScopeId > 0 && !empty($lists)) {
            $orderIds = array_column($lists, 'id');
            $pendingRows = OrderItem::whereIn('order_id', $orderIds)
                ->where('staff_id', $staffScopeId)
                ->where('confirm_status', 0)
                ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                ->field('order_id, COUNT(*) as pending_confirm_count')
                ->group('order_id')
                ->select()
                ->toArray();
            $pendingCounts = array_column($pendingRows, 'pending_confirm_count', 'order_id');
        }

        foreach ($lists as &$item) {
            if ($staffScopeId > 0) {
                $item = OrderLogic::applyStaffVisibleOrderAmounts($item, $staffScopeId);
            }
            $item['order_status_desc'] = $this->getStatusDesc($item['order_status']);
            $item['pay_status_desc'] = $this->getPayStatusDesc($item['pay_status']);
            $item['pay_type_desc'] = $this->getPayTypeDesc($item['pay_type']);
            $item['source_desc'] = $this->getSourceDesc($item['source']);
            $item['payment_mode'] = (float)($item['deposit_amount'] ?? 0) > 0 ? 'deposit' : 'full';
            $item['payment_mode_desc'] = $item['payment_mode'] === 'deposit' ? '定金支付' : '全款支付';
            $item['unpaid_amount'] = round(max((float)($item['pay_amount'] ?? 0) - (float)($item['paid_amount'] ?? 0), 0), 2);
            $item['need_pay_amount'] = $item['unpaid_amount'];
            if ($item['payment_mode'] === 'deposit') {
                if (!(int)($item['deposit_paid'] ?? 0)) {
                    $item['need_pay_amount'] = round((float)($item['deposit_amount'] ?? 0), 2);
                    $item['need_pay_label'] = '支付定金';
                } elseif (!(int)($item['balance_paid'] ?? 0)) {
                    $item['need_pay_amount'] = round((float)($item['balance_amount'] ?? 0), 2);
                    $item['need_pay_label'] = '支付尾款';
                } else {
                    $item['need_pay_amount'] = 0;
                    $item['need_pay_label'] = '无需支付';
                }
            } else {
                $item['need_pay_label'] = $item['unpaid_amount'] > 0 ? '立即支付' : '无需支付';
            }
            $item['pending_confirm_count'] = (int)($pendingCounts[$item['id']] ?? 0);
            $item['has_pending_confirm'] = $item['pending_confirm_count'] > 0 ? 1 : 0;
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
            'pay_status_desc' => '支付状态',
            'pay_type_desc' => '支付方式',
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
            'wedding_date' => '婚礼日期',
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
        $map = [
            Order::STATUS_PENDING_CONFIRM => '待确认',
            Order::STATUS_PENDING_PAY => '待支付',
            Order::STATUS_PAID => '已支付',
            Order::STATUS_IN_SERVICE => '服务中',
            Order::STATUS_COMPLETED => '已完成',
            Order::STATUS_REVIEWED => '已评价',
            Order::STATUS_CANCELLED => '已取消',
            Order::STATUS_PAUSED => '已暂停',
            Order::STATUS_REFUNDED => '已退款',
            Order::STATUS_USER_DELETED => '用户已删除',
        ];
        return $map[$status] ?? '未知';
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
}
