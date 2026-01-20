<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单变更列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\order;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsExcelInterface;
use app\common\model\order\OrderChange;

/**
 * 订单变更列表
 * Class OrderChangeLists
 * @package app\adminapi\lists\order
 */
class OrderChangeLists extends BaseAdminDataLists implements ListsExcelInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['change_type', 'change_status', 'order_id', 'user_id'],
            '%like%' => ['change_sn', 'order_sn'],
            'between_time' => ['create_time', 'audit_time', 'execute_time'],
        ];
    }

    /**
     * @notes 额外搜索条件
     * @return array
     */
    public function queryWhere(): array
    {
        $where = [];

        // 用户关键词搜索
        if (!empty($this->params['user_keyword'])) {
            $userIds = \app\common\model\user\User::where('nickname|mobile', 'like', '%' . $this->params['user_keyword'] . '%')
                ->column('id');
            if (!empty($userIds)) {
                $where[] = ['user_id', 'in', $userIds];
            } else {
                $where[] = ['user_id', '=', 0];
            }
        }

        // 差价范围（换人类型）
        if (isset($this->params['has_price_diff']) && $this->params['has_price_diff']) {
            $where[] = ['change_type', '=', OrderChange::TYPE_STAFF];
            $where[] = ['price_diff', '<>', 0];
        }

        return $where;
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $lists = OrderChange::with([
            'order' => function ($query) {
                $query->field('id, order_sn, user_id, order_status, service_date');
            },
            'user' => function ($query) {
                $query->field('id, nickname, avatar, mobile');
            },
            'oldStaff' => function ($query) {
                $query->field('id, name, avatar');
            },
            'newStaff' => function ($query) {
                $query->field('id, name, avatar');
            },
            'addStaff' => function ($query) {
                $query->field('id, name, avatar');
            },
        ])
            ->where($this->searchWhere)
            ->where($this->queryWhere())
            ->order($this->sortOrder ?: ['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['change_type_desc'] = $this->getTypeDesc($item['change_type']);
            $item['change_status_desc'] = $this->getStatusDesc($item['change_status']);
        }

        return $lists;
    }

    /**
     * @notes 总数
     * @return int
     */
    public function count(): int
    {
        return OrderChange::where($this->searchWhere)
            ->where($this->queryWhere())
            ->count();
    }

    /**
     * @notes 导出字段
     * @return array
     */
    public function setExcelFields(): array
    {
        return [
            'change_sn' => '变更单号',
            'order_sn' => '订单编号',
            'user.nickname' => '用户昵称',
            'user.mobile' => '用户手机',
            'change_type_desc' => '变更类型',
            'change_status_desc' => '变更状态',
            'old_service_date' => '原服务日期',
            'new_service_date' => '新服务日期',
            'old_staff_name' => '原人员',
            'new_staff_name' => '新人员',
            'price_diff' => '差价',
            'apply_reason' => '申请原因',
            'create_time' => '申请时间',
            'audit_time' => '审核时间',
            'execute_time' => '执行时间',
        ];
    }

    /**
     * @notes 导出文件名
     * @return string
     */
    public function setFileName(): string
    {
        return '订单变更列表';
    }

    /**
     * @notes 获取变更类型描述
     * @param int $type
     * @return string
     */
    protected function getTypeDesc(int $type): string
    {
        $map = [
            OrderChange::TYPE_DATE => '改期',
            OrderChange::TYPE_STAFF => '换人',
            OrderChange::TYPE_ADD_ITEM => '加项',
        ];
        return $map[$type] ?? '未知';
    }

    /**
     * @notes 获取变更状态描述
     * @param int $status
     * @return string
     */
    protected function getStatusDesc(int $status): string
    {
        $map = [
            OrderChange::STATUS_PENDING => '待审核',
            OrderChange::STATUS_APPROVED => '审核通过',
            OrderChange::STATUS_REJECTED => '审核拒绝',
            OrderChange::STATUS_EXECUTED => '已执行',
            OrderChange::STATUS_CANCELLED => '已取消',
        ];
        return $map[$status] ?? '未知';
    }
}
