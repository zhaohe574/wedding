<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\auth\Admin;
use app\common\model\order\Order;
use app\common\model\staff\Staff;
use app\common\model\user\User;
use think\facade\Cache;
use think\facade\Db;

/** 本人录单边界，所有工作身份与金额均由服务端确定。 */
class StaffManualOrderService
{
    public static function identity(int $userId, bool $lock = false): Staff
    {
        $user = User::where('id', $userId)->where('is_disable', 0)->lock($lock)->find();
        $admin = Admin::where('user_id', $userId)->where('disable', 0)->lock($lock)->find();
        $staff = $admin ? Staff::where('user_id', $userId)->where('admin_id', (int)$admin->id)
            ->where('status', 1)->lock($lock)->find() : null;
        if (!$user || !$staff) throw new \RuntimeException('工作身份已失效，请联系管理员');
        return $staff;
    }

    public static function customers(int $userId, string $mobile): array
    {
        $staff = self::identity($userId);
        $rows = \app\adminapi\logic\order\OrderCustomerLogic::options($mobile, (int)$staff->admin_id);
        return array_map(static function ($row) use ($userId, $staff) {
            $token = bin2hex(random_bytes(24));
            Cache::set('staff_customer:' . hash('sha256', $token), [
                'operator' => $userId, 'staff_id' => (int)$staff->id, 'customer_id' => (int)$row['id'],
            ], 1800);
            return ['selection_token' => $token, 'nickname' => $row['nickname'],
                'mobile' => substr((string)$row['mobile'], 0, 3) . '****' . substr((string)$row['mobile'], -4)];
        }, $rows);
    }

    private static function parameters(Staff $staff, array $input): array
    {
        $params = array_intersect_key($input, array_flip([
            'service_date', 'service_address', 'province_code', 'province_name', 'city_code', 'city_name',
            'district_code', 'district_name', 'contact_name', 'contact_mobile', 'admin_remark',
            'main_package_id', 'addon_ids', 'payment_entry_mode',
        ]));
        $params['main_staff_id'] = (int)$staff->id;
        $params['admin_id'] = (int)$staff->admin_id;
        $params['discount_amount'] = 0;
        $params['payment_entry_mode'] = ($input['payment_entry_mode'] ?? '') === 'online_pending'
            ? 'online_pending' : 'offline_voucher';
        $date = (string)($params['service_date'] ?? '');
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date || $date < date('Y-m-d')) {
            throw new \InvalidArgumentException('请选择有效的未来服务日期');
        }
        return $params;
    }

    public static function options(int $userId, array $input): array
    {
        $staff = self::identity($userId);
        $params = self::parameters($staff, $input);
        return ['staff_id' => (int)$staff->id, 'staff_name' => (string)$staff->name,
            'packages' => ManualOrderService::getOfflineMainPackages($params),
            'addons' => BookingFlowService::getStaffBookingAddons((int)$staff->id, (int)($params['main_package_id'] ?? 0))];
    }

    private static function quote(array $params): array
    {
        $quote = ManualOrderService::estimateOffline($params);
        $quote['quote_hash'] = hash('sha256', json_encode([$params['main_staff_id'], $params['main_package_id'],
            $params['service_date'], $params['province_code'] ?? '', $params['city_code'] ?? '',
            $params['district_code'] ?? '', BookingFlowService::normalizeAddonIds($params['addon_ids'] ?? []), $quote], JSON_THROW_ON_ERROR));
        return $quote;
    }

    public static function preview(int $userId, array $input): array
    {
        return self::quote(self::parameters(self::identity($userId), $input));
    }

    public static function submitKey(int $userId, string $key): string
    {
        if (!preg_match('/^[a-zA-Z0-9_-]{16,80}$/', $key)) throw new \InvalidArgumentException('提交标识无效，请重新进入录单页面');
        return hash('sha256', $userId . ':' . $key);
    }

    public static function create(int $userId, array $input): array
    {
        return Db::transaction(static function () use ($userId, $input) {
            $staff = self::identity($userId, true);
            $key = self::submitKey($userId, (string)($input['submit_key'] ?? ''));
            $params = self::parameters($staff, $input);
            $hash = hash('sha256', json_encode([$params, $input['selection_token'] ?? '', $input['receipt'] ?? null], JSON_THROW_ON_ERROR));
            $existing = Order::where('staff_submit_key', $key)->lock(true)->find();
            if ($existing) {
                if (!hash_equals((string)$existing->staff_submit_hash, $hash)) throw new \RuntimeException('该提交已完成，不能使用同一标识更改订单');
                return self::result($existing);
            }
            foreach (['contact_name' => 50, 'service_address' => 255, 'admin_remark' => 500] as $field => $limit) {
                $params[$field] = trim((string)($params[$field] ?? ''));
                if (($field !== 'admin_remark' && $params[$field] === '') || mb_strlen($params[$field]) > $limit) {
                    throw new \InvalidArgumentException('请检查联系人、详细地址和备注长度');
                }
            }
            if (!preg_match('/^1[3-9]\d{9}$/', (string)($params['contact_mobile'] ?? ''))) throw new \InvalidArgumentException('请填写有效联系电话');
            $token = trim((string)($input['selection_token'] ?? ''));
            $params['bind_mode'] = 'temp';
            $params['user_id'] = 0;
            if ($token !== '') {
                $selection = Cache::get('staff_customer:' . hash('sha256', $token));
                if (!$selection || (int)$selection['operator'] !== $userId || (int)$selection['staff_id'] !== (int)$staff->id) {
                    throw new \RuntimeException('客户选择已过期，请重新查询并选择');
                }
                $customer = User::where('id', $selection['customer_id'])->where('is_disable', 0)->lock(true)->find();
                if (!$customer) throw new \RuntimeException('客户账号不可用，请重新选择');
                $params['user_id'] = (int)$customer->id;
                $params['bind_mode'] = 'user';
            }
            if (!$params['user_id'] && $params['payment_entry_mode'] === 'online_pending') {
                throw new \InvalidArgumentException('临时联系人仅支持线下待收款');
            }
            \app\common\model\service\ServicePackage::where('id', (int)$params['main_package_id'])->lock(true)->find();
            Db::name('service_package_region_price')->where('package_id', (int)$params['main_package_id'])->lock(true)->select();
            Db::name('service_package_addon')->where('package_id', (int)$params['main_package_id'])->lock(true)->select();
            Db::name('service_addon')->whereIn('id', BookingFlowService::normalizeAddonIds($params['addon_ids'] ?? []))->lock(true)->select();
            $quote = self::quote($params);
            if (!hash_equals($quote['quote_hash'], (string)($input['quote_hash'] ?? ''))) {
                return ['quote_changed' => true, 'quote' => $quote];
            }
            $result = ManualOrderService::create($params, Order::SOURCE_STAFF, $userId);
            $order = Order::where('id', $result['order_id'])->lock(true)->find();
            $order->save(['staff_submit_key' => $key, 'staff_submit_hash' => $hash]);
            if (!empty($input['receipt'])) {
                OrderReceiptService::submitLocked($order, $staff, $userId,
                    (array)$input['receipt'] + ['submit_key' => (string)$input['submit_key']]);
            }
            return self::result($order);
        });
    }

    private static function result(Order $order): array
    {
        return ['order_id' => (int)$order->id, 'order_sn' => (string)$order->order_sn,
            'order_status' => (int)$order->order_status,
            'receipt' => OrderReceiptService::history((int)$order->id)[0] ?? null];
    }
}
