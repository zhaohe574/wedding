<?php

declare(strict_types=1);

namespace app\adminapi\logic\order;

use app\common\model\user\User;
use think\facade\Cache;

/** 线下建单客户选择：只允许完整手机号精确查询，禁止枚举全站用户。 */
class OrderCustomerLogic
{
    public static function options(string $keyword, int $adminId): array
    {
        $keyword = trim($keyword);
        if (!preg_match('/^1[3-9]\d{9}$/', $keyword)) {
            throw new \InvalidArgumentException('请输入客户完整的 11 位手机号');
        }
        if ($adminId <= 0) {
            throw new \InvalidArgumentException('请重新登录');
        }
        $key = 'order_customer_lookup:' . $adminId . ':' . (int)floor(time() / 60);
        // 使用缓存自增限制精确查询频率，结果不包含账号或风险等级。
        if (!Cache::has($key)) {
            Cache::set($key, 0, 120);
        }
        if (Cache::inc($key) > 30) {
            throw new \RuntimeException('查询过于频繁，请稍后再试');
        }
        $customers = User::where('mobile', $keyword)->where('is_disable', 0)
            ->field('id,nickname,mobile')->limit(5)->select()->toArray();
        foreach ($customers as $customer) {
            Cache::set('order_customer_selection:' . $adminId . ':' . $customer['id'], true, 1800);
        }
        return $customers;
    }

    public static function assertSelected(int $adminId, int $userId): void
    {
        if (!Cache::get('order_customer_selection:' . $adminId . ':' . $userId)) {
            throw new \InvalidArgumentException('客户选择已过期，请重新输入完整手机号查询');
        }
    }
}
