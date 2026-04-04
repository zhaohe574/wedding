<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单暂停列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\order;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsExcelInterface;
use app\common\model\order\OrderPause;

/**
 * 订单暂停列表
 * Class OrderPauseLists
 * @package app\adminapi\lists\order
 */
class OrderPauseLists extends BaseAdminDataLists implements ListsExcelInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['pause_status', 'pause_type', 'order_id', 'user_id'],
            '%like%' => ['pause_sn', 'order_sn'],
            'between_time' => ['create_time', 'audit_time', 'resume_time'],
            'between_date' => ['pause_start_date', 'pause_end_date'],
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

        // 即将到期（指定天数内）
        if (!empty($this->params['expiring_days'])) {
            $targetDate = date('Y-m-d', strtotime("+{$this->params['expiring_days']} days"));
            $where[] = ['pause_status', '=', OrderPause::STATUS_PAUSED];
            $where[] = ['pause_end_date', '<=', $targetDate];
        }

        // 是否已提醒
        if (isset($this->params['reminded'])) {
            $where[] = ['reminded', '=', $this->params['reminded']];
        }

        return $where;
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $lists = OrderPause::with([
            'order' => function ($query) {
                $query->field('id, order_sn, user_id, order_status, pay_amount, service_date');
            },
            'user' => function ($query) {
                $query->field('id, nickname, avatar, mobile');
            },
        ])
            ->where($this->searchWhere)
            ->where($this->queryWhere())
            ->order($this->sortOrder ?: ['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['pause_status_desc'] = $this->getStatusDesc($item['pause_status']);
            $item['pause_type_desc'] = $this->getTypeDesc($item['pause_type']);
            
            // 计算剩余天数（暂停中）
            if ($item['pause_status'] == OrderPause::STATUS_PAUSED) {
                $remainDays = (strtotime($item['pause_end_date']) - time()) / 86400;
                $item['remain_days'] = max(0, ceil($remainDays));
            } else {
                $item['remain_days'] = null;
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
        return OrderPause::where($this->searchWhere)
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
            'pause_sn' => '暂停单号',
            'order_sn' => '订单编号',
            'user.nickname' => '用户昵称',
            'user.mobile' => '用户手机',
            'pause_type_desc' => '暂停类型',
            'pause_status_desc' => '暂停状态',
            'pause_reason' => '暂停原因',
            'pause_start_date' => '开始日期',
            'pause_end_date' => '结束日期',
            'pause_days' => '计划天数',
            'actual_pause_days' => '实际天数',
            'original_service_date' => '原服务日期',
            'new_service_date' => '新服务日期',
            'create_time' => '申请时间',
            'audit_time' => '审核时间',
            'resume_time' => '恢复时间',
        ];
    }

    /**
     * @notes 导出文件名
     * @return string
     */
    public function setFileName(): string
    {
        return '订单暂停列表';
    }

    /**
     * @notes 获取暂停类型描述
     * @param int $type
     * @return string
     */
    protected function getTypeDesc(int $type): string
    {
        $map = [
            OrderPause::TYPE_EPIDEMIC => '疫情',
            OrderPause::TYPE_EMERGENCY => '突发事件',
            OrderPause::TYPE_PERSONAL => '个人原因',
            OrderPause::TYPE_OTHER => '其他',
        ];
        return $map[$type] ?? '未知';
    }

    /**
     * @notes 获取暂停状态描述
     * @param int $status
     * @return string
     */
    protected function getStatusDesc(int $status): string
    {
        $map = [
            OrderPause::STATUS_PENDING => '待审核',
            OrderPause::STATUS_PAUSED => '暂停中',
            OrderPause::STATUS_RESUMED => '已恢复',
            OrderPause::STATUS_REJECTED => '已拒绝',
            OrderPause::STATUS_CANCELLED => '已取消',
        ];
        return $map[$status] ?? '未知';
    }
}
