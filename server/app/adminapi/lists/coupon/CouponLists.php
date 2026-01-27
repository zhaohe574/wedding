<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 优惠券列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\coupon;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\coupon\Coupon;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsExcelInterface;

/**
 * 优惠券列表
 * Class CouponLists
 * @package app\adminapi\lists\coupon
 */
class CouponLists extends BaseAdminDataLists implements ListsSearchInterface, ListsExcelInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['coupon_type', 'valid_type', 'use_scope', 'status'],
            '%like%' => ['name'],
        ];
    }

    /**
     * @notes 自定义搜索条件
     * @return array
     */
    public function queryWhere(): array
    {
        $where = [];

        // 有效期状态筛选
        if (!empty($this->params['valid_status'])) {
            $now = time();
            switch ($this->params['valid_status']) {
                case 'not_started':
                    // 未开始
                    $where[] = ['valid_type', '=', Coupon::VALID_TYPE_FIXED];
                    $where[] = ['valid_start_time', '>', $now];
                    break;
                case 'in_progress':
                    // 进行中
                    $where[] = ['valid_type', '=', Coupon::VALID_TYPE_FIXED];
                    $where[] = ['valid_start_time', '<=', $now];
                    $where[] = ['valid_end_time', '>=', $now];
                    break;
                case 'ended':
                    // 已结束
                    $where[] = ['valid_type', '=', Coupon::VALID_TYPE_FIXED];
                    $where[] = ['valid_end_time', '<', $now];
                    $where[] = ['valid_end_time', '>', 0];
                    break;
            }
        }

        // 库存状态筛选
        if (!empty($this->params['stock_status'])) {
            switch ($this->params['stock_status']) {
                case 'in_stock':
                    // 有库存
                    $where[] = ['', 'exp', \think\facade\Db::raw('(total_count = 0 OR receive_count < total_count)')];
                    break;
                case 'out_of_stock':
                    // 已领完
                    $where[] = ['total_count', '>', 0];
                    $where[] = ['', 'exp', \think\facade\Db::raw('receive_count >= total_count')];
                    break;
            }
        }

        // 日期范围搜索
        if (!empty($this->params['start_date'])) {
            $where[] = ['create_time', '>=', strtotime($this->params['start_date'])];
        }
        if (!empty($this->params['end_date'])) {
            $where[] = ['create_time', '<=', strtotime($this->params['end_date'] . ' 23:59:59')];
        }

        return $where;
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $lists = Coupon::where($this->searchWhere)
            ->where($this->queryWhere())
            ->order('create_time desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            // PHP 8 类型转换：确保数值字段为正确类型
            $item['coupon_type'] = (int)$item['coupon_type'];
            $item['valid_type'] = (int)$item['valid_type'];
            $item['use_scope'] = (int)$item['use_scope'];
            $item['status'] = (int)$item['status'];
            $item['valid_start_time'] = (int)$item['valid_start_time'];
            $item['valid_end_time'] = (int)$item['valid_end_time'];
            $item['valid_days'] = (int)$item['valid_days'];
            $item['total_count'] = (int)$item['total_count'];
            $item['receive_count'] = (int)$item['receive_count'];
            $item['used_count'] = (int)$item['used_count'];
            $item['threshold_amount'] = (float)$item['threshold_amount'];
            $item['discount_amount'] = (float)$item['discount_amount'];
            $item['max_discount'] = (float)$item['max_discount'];
            
            $item['coupon_type_text'] = Coupon::getTypeDesc($item['coupon_type']);
            $item['valid_type_text'] = Coupon::getValidTypeDesc($item['valid_type']);
            $item['use_scope_text'] = Coupon::getScopeDesc($item['use_scope']);
            $item['status_text'] = Coupon::getStatusDesc($item['status']);
            
            // 创建时间处理：先转换为整数，再格式化
            $createTime = (int)$item['create_time'];
            $item['create_time_text'] = $createTime > 0 ? date('Y-m-d H:i:s', $createTime) : '';

            // 有效期显示
            if ($item['valid_type'] == Coupon::VALID_TYPE_FIXED) {
                $validStartTime = (int)$item['valid_start_time'];
                $validEndTime = (int)$item['valid_end_time'];
                $start = $validStartTime > 0 ? date('Y-m-d', $validStartTime) : '';
                $end = $validEndTime > 0 ? date('Y-m-d', $validEndTime) : '';
                $item['valid_period'] = $start . ' 至 ' . $end;
            } else {
                $item['valid_period'] = '领取后' . $item['valid_days'] . '天内有效';
            }

            // 剩余数量
            if ($item['total_count'] == 0) {
                $item['remain_count'] = '不限';
            } else {
                $item['remain_count'] = max(0, $item['total_count'] - $item['receive_count']);
            }

            // 使用率
            if ($item['receive_count'] == 0) {
                $item['use_rate'] = '0%';
            } else {
                $item['use_rate'] = round($item['used_count'] / $item['receive_count'] * 100, 2) . '%';
            }

            // 优惠描述
            $item['discount_desc'] = $this->getDiscountDesc($item);
        }

        return $lists;
    }

    /**
     * @notes 获取优惠描述
     * @param array $item
     * @return string
     */
    private function getDiscountDesc(array $item): string
    {
        $threshold = $item['threshold_amount'] ?? 0;
        $discount = $item['discount_amount'] ?? 0;
        $maxDiscount = $item['max_discount'] ?? 0;

        $thresholdText = $threshold > 0 ? "满{$threshold}元" : '无门槛';

        switch ($item['coupon_type']) {
            case Coupon::TYPE_FULL_REDUCTION:
                return "{$thresholdText}减{$discount}元";
            case Coupon::TYPE_DISCOUNT:
                $discountRate = $discount / 10;
                $text = "{$thresholdText}打{$discountRate}折";
                if ($maxDiscount > 0) {
                    $text .= "，最多优惠{$maxDiscount}元";
                }
                return $text;
            case Coupon::TYPE_DIRECT:
                return "立减{$discount}元";
            default:
                return '';
        }
    }

    /**
     * @notes 总数
     * @return int
     */
    public function count(): int
    {
        return Coupon::where($this->searchWhere)
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
            'id' => '优惠券ID',
            'name' => '优惠券名称',
            'coupon_type_text' => '类型',
            'discount_desc' => '优惠内容',
            'total_count' => '发放总量',
            'receive_count' => '已领取',
            'used_count' => '已使用',
            'use_rate' => '使用率',
            'valid_period' => '有效期',
            'status_text' => '状态',
            'create_time_text' => '创建时间',
        ];
    }

    /**
     * @notes 导出文件名
     * @return string
     */
    public function setFileName(): string
    {
        return '优惠券列表_' . date('YmdHis');
    }
}
