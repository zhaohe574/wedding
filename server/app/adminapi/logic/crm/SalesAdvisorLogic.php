<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 销售顾问业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\crm;

use app\common\logic\BaseLogic;
use app\common\model\crm\SalesAdvisor;
use app\common\model\crm\Customer;
use app\common\model\crm\CustomerAssignLog;
use think\facade\Db;

/**
 * 销售顾问业务逻辑
 * Class SalesAdvisorLogic
 * @package app\adminapi\logic\crm
 */
class SalesAdvisorLogic extends BaseLogic
{
    /**
     * @notes 获取顾问详情
     * @param int $id
     * @return array|null
     */
    public static function detail(int $id): ?array
    {
        $advisor = SalesAdvisor::with([
            'admin' => function ($query) {
                $query->field('id, name, avatar');
            }
        ])->find($id);

        if (!$advisor) {
            return null;
        }

        $data = $advisor->toArray();
        $data['status_desc'] = $advisor->status_desc;
        $data['can_assign'] = $advisor->canAssignCustomer();

        return $data;
    }

    /**
     * @notes 添加顾问
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            // 检查手机号唯一性
            if (!empty($params['mobile'])) {
                $exists = SalesAdvisor::where('mobile', $params['mobile'])->find();
                if ($exists) {
                    self::setError('该手机号已存在');
                    return false;
                }
            }

            SalesAdvisor::create([
                'admin_id' => $params['admin_id'] ?? 0,
                'advisor_name' => $params['advisor_name'],
                'avatar' => $params['avatar'] ?? '',
                'mobile' => $params['mobile'] ?? '',
                'wechat' => $params['wechat'] ?? '',
                'email' => $params['email'] ?? '',
                'areas' => $params['areas'] ?? [],
                'specialties' => $params['specialties'] ?? [],
                'max_customer_count' => $params['max_customer_count'] ?? 100,
                'status' => $params['status'] ?? SalesAdvisor::STATUS_NORMAL,
                'sort' => $params['sort'] ?? 0,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑顾问
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        try {
            $advisor = SalesAdvisor::find($params['id']);
            if (!$advisor) {
                self::setError('顾问不存在');
                return false;
            }

            // 检查手机号唯一性
            if (!empty($params['mobile']) && $params['mobile'] != $advisor->mobile) {
                $exists = SalesAdvisor::where('mobile', $params['mobile'])
                    ->where('id', '<>', $params['id'])
                    ->find();
                if ($exists) {
                    self::setError('该手机号已存在');
                    return false;
                }
            }

            $updateData = [];
            $allowFields = [
                'admin_id', 'advisor_name', 'avatar', 'mobile', 'wechat', 'email',
                'areas', 'specialties', 'max_customer_count', 'status', 'sort'
            ];

            foreach ($allowFields as $field) {
                if (isset($params[$field])) {
                    $updateData[$field] = $params[$field];
                }
            }

            if (!empty($updateData)) {
                $updateData['update_time'] = time();
                SalesAdvisor::where('id', $params['id'])->update($updateData);
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除顾问
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        try {
            $advisor = SalesAdvisor::find($id);
            if (!$advisor) {
                self::setError('顾问不存在');
                return false;
            }

            // 检查是否还有客户
            if ($advisor->current_customer_count > 0) {
                self::setError('该顾问还有客户，请先转移客户后再删除');
                return false;
            }

            $advisor->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 更新顾问状态
     * @param int $id
     * @param int $status
     * @return bool
     */
    public static function updateStatus(int $id, int $status): bool
    {
        try {
            $advisor = SalesAdvisor::find($id);
            if (!$advisor) {
                self::setError('顾问不存在');
                return false;
            }

            SalesAdvisor::where('id', $id)->update([
                'status' => $status,
                'update_time' => time(),
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 转移客户
     * @param int $fromAdvisorId
     * @param int $toAdvisorId
     * @param int $adminId
     * @param string $reason
     * @return array
     */
    public static function transferCustomers(int $fromAdvisorId, int $toAdvisorId, int $adminId, string $reason = ''): array
    {
        Db::startTrans();
        try {
            $fromAdvisor = SalesAdvisor::find($fromAdvisorId);
            if (!$fromAdvisor) {
                self::setError('原顾问不存在');
                return ['success' => 0, 'fail' => 0];
            }

            $toAdvisor = SalesAdvisor::find($toAdvisorId);
            if (!$toAdvisor) {
                self::setError('目标顾问不存在');
                return ['success' => 0, 'fail' => 0];
            }

            // 获取原顾问的所有活跃客户
            $customers = Customer::where('advisor_id', $fromAdvisorId)
                ->whereIn('customer_status', [Customer::STATUS_NEW, Customer::STATUS_FOLLOWING])
                ->select();

            $success = 0;
            $fail = 0;

            foreach ($customers as $customer) {
                // 检查目标顾问是否还能接收
                $toAdvisor = SalesAdvisor::find($toAdvisorId);
                if (!$toAdvisor->canAssignCustomer()) {
                    $fail++;
                    continue;
                }

                // 转移客户
                Customer::where('id', $customer->id)->update([
                    'advisor_id' => $toAdvisorId,
                    'assign_time' => time(),
                    'update_time' => time(),
                ]);

                // 记录日志
                CustomerAssignLog::record(
                    $customer->id,
                    $fromAdvisorId,
                    $toAdvisorId,
                    CustomerAssignLog::TYPE_TRANSFER,
                    $reason ?: '顾问离职/休假客户转移',
                    $adminId
                );

                // 更新客户数
                SalesAdvisor::decrementCustomerCount($fromAdvisorId);
                SalesAdvisor::incrementCustomerCount($toAdvisorId);

                $success++;
            }

            Db::commit();
            return [
                'success' => $success,
                'fail' => $fail,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return ['success' => 0, 'fail' => 0];
        }
    }

    /**
     * @notes 获取顾问业绩统计
     * @param int $advisorId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function getPerformanceStats(int $advisorId, string $startDate = '', string $endDate = ''): array
    {
        return SalesAdvisor::getPerformanceStats($advisorId, $startDate, $endDate);
    }

    /**
     * @notes 获取顾问客户列表
     * @param int $advisorId
     * @return array
     */
    public static function getAdvisorCustomers(int $advisorId): array
    {
        return Customer::where('advisor_id', $advisorId)
            ->whereIn('customer_status', [Customer::STATUS_NEW, Customer::STATUS_FOLLOWING])
            ->order('next_follow_time asc, intention_level asc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return SalesAdvisor::getStatusOptions();
    }

    /**
     * @notes 获取顾问简单列表(下拉选择用)
     * @param bool $onlyAvailable 仅可用顾问
     * @return array
     */
    public static function getSimpleList(bool $onlyAvailable = false): array
    {
        $query = SalesAdvisor::field('id, advisor_name, mobile, status, current_customer_count, max_customer_count');
        
        if ($onlyAvailable) {
            $query->where('status', SalesAdvisor::STATUS_NORMAL)
                ->whereRaw('current_customer_count < max_customer_count');
        }
        
        return $query->order('sort desc, id asc')
            ->select()
            ->toArray();
    }
}
