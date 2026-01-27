<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 用户优惠券列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\coupon;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\coupon\UserCoupon;
use app\common\model\coupon\Coupon;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsExcelInterface;

/**
 * 用户优惠券列表
 * Class UserCouponLists
 * @package app\adminapi\lists\coupon
 */
class UserCouponLists extends BaseAdminDataLists implements ListsSearchInterface, ListsExcelInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['coupon_id', 'user_id', 'status'],
            '%like%' => ['coupon_sn'],
        ];
    }

    /**
     * @notes 自定义搜索条件
     * @return array
     */
    public function queryWhere(): array
    {
        $where = [];

        // 用户昵称/手机号搜索
        if (!empty($this->params['keyword'])) {
            $userIds = \app\common\model\user\User::where(function ($query) {
                $query->where('nickname', 'like', '%' . $this->params['keyword'] . '%')
                    ->whereOr('mobile', 'like', '%' . $this->params['keyword'] . '%');
            })->column('id');
            $where[] = ['user_id', 'in', $userIds];
        }

        // 优惠券名称搜索
        if (!empty($this->params['coupon_name'])) {
            $couponIds = Coupon::where('name', 'like', '%' . $this->params['coupon_name'] . '%')
                ->column('id');
            $where[] = ['coupon_id', 'in', $couponIds];
        }

        // 优惠券类型筛选
        if (!empty($this->params['coupon_type'])) {
            $couponIds = Coupon::where('coupon_type', $this->params['coupon_type'])
                ->column('id');
            $where[] = ['coupon_id', 'in', $couponIds];
        }

        // 领取日期范围
        if (!empty($this->params['receive_start_date'])) {
            $where[] = ['receive_time', '>=', strtotime($this->params['receive_start_date'])];
        }
        if (!empty($this->params['receive_end_date'])) {
            $where[] = ['receive_time', '<=', strtotime($this->params['receive_end_date'] . ' 23:59:59')];
        }

        // 使用日期范围
        if (!empty($this->params['use_start_date'])) {
            $where[] = ['use_time', '>=', strtotime($this->params['use_start_date'])];
        }
        if (!empty($this->params['use_end_date'])) {
            $where[] = ['use_time', '<=', strtotime($this->params['use_end_date'] . ' 23:59:59')];
        }

        // 有效期状态
        if (!empty($this->params['valid_status'])) {
            $now = time();
            switch ($this->params['valid_status']) {
                case 'valid':
                    // 在有效期内
                    $where[] = ['valid_start_time', '<=', $now];
                    $where[] = ['valid_end_time', '>=', $now];
                    break;
                case 'expired':
                    // 已过期
                    $where[] = ['valid_end_time', '<', $now];
                    $where[] = ['valid_end_time', '>', 0];
                    break;
            }
        }

        return $where;
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $lists = UserCoupon::with(['user', 'coupon', 'order'])
            ->where($this->searchWhere)
            ->where($this->queryWhere())
            ->order('create_time desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            // PHP 8 类型转换
            $item['receive_time'] = (int)$item['receive_time'];
            $item['use_time'] = (int)$item['use_time'];
            $item['valid_start_time'] = (int)$item['valid_start_time'];
            $item['valid_end_time'] = (int)$item['valid_end_time'];
            $item['status'] = (int)$item['status'];
            
            $item['status_text'] = UserCoupon::getStatusDesc($item['status']);
            $item['receive_time_text'] = $item['receive_time'] ? date('Y-m-d H:i:s', $item['receive_time']) : '-';
            $item['use_time_text'] = $item['use_time'] ? date('Y-m-d H:i:s', $item['use_time']) : '-';

            // 有效期显示
            $start = $item['valid_start_time'] ? date('Y-m-d', $item['valid_start_time']) : '';
            $end = $item['valid_end_time'] ? date('Y-m-d', $item['valid_end_time']) : '';
            $item['valid_period'] = $start . ' 至 ' . $end;

            // 优惠券信息
            if (!empty($item['coupon'])) {
                $item['coupon_name'] = $item['coupon']['name'] ?? '';
                $item['coupon_type'] = $item['coupon']['coupon_type'] ?? 1;
                $item['coupon_type_text'] = Coupon::getTypeDesc($item['coupon_type']);
                $item['threshold_amount'] = $item['coupon']['threshold_amount'] ?? 0;
                $item['discount_amount'] = $item['coupon']['discount_amount'] ?? 0;
            }

            // 用户信息
            if (!empty($item['user'])) {
                $item['user_nickname'] = $item['user']['nickname'] ?? '';
                $item['user_mobile'] = $item['user']['mobile'] ?? '';
                $item['user_avatar'] = $item['user']['avatar'] ?? '';
            }

            // 订单信息
            if (!empty($item['order'])) {
                $item['order_sn'] = $item['order']['order_sn'] ?? '';
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
        return UserCoupon::where($this->searchWhere)
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
            'id' => 'ID',
            'coupon_sn' => '优惠券码',
            'coupon_name' => '优惠券名称',
            'coupon_type_text' => '优惠券类型',
            'user_nickname' => '用户昵称',
            'user_mobile' => '用户手机',
            'status_text' => '状态',
            'valid_period' => '有效期',
            'receive_time_text' => '领取时间',
            'use_time_text' => '使用时间',
            'order_sn' => '使用订单',
        ];
    }

    /**
     * @notes 导出文件名
     * @return string
     */
    public function setFileName(): string
    {
        return '用户优惠券列表_' . date('YmdHis');
    }
}
