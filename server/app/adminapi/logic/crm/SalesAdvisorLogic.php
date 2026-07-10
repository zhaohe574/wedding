<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 销售顾问管理逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\crm;

use app\common\logic\BaseLogic;
use app\common\model\crm\Customer;
use app\common\model\crm\SalesAdvisor;
use think\facade\Db;

/**
 * 销售顾问管理逻辑
 * Class SalesAdvisorLogic
 * @package app\adminapi\logic\crm
 */
class SalesAdvisorLogic extends BaseLogic
{
    /**
     * @notes 获取顾问详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $advisor = SalesAdvisor::where('id', $id)
            ->field(self::advisorFields())
            ->find();
        if (!$advisor) {
            return [];
        }

        $data = $advisor->append(['status_desc'])->toArray();
        $data['admin'] = self::getAdminInfo((int)($data['admin_id'] ?? 0));
        $data['load_text'] = (int)$data['current_customer_count'] . '/' . (int)$data['max_customer_count'];
        $data['active_customer_count'] = self::activeCustomerCount($id);
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
            self::checkUniqueAdvisor($params);

            SalesAdvisor::create(self::buildSaveData($params) + [
                'current_customer_count' => 0,
                'total_order_count' => 0,
                'total_order_amount' => 0,
                'conversion_rate' => 0,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            return true;
        } catch (\Throwable $e) {
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
            $advisor = SalesAdvisor::find((int)$params['id']);
            if (!$advisor) {
                throw new \Exception('顾问不存在');
            }

            self::checkUniqueAdvisor($params, (int)$advisor->id);

            $advisor->save(self::buildSaveData($params, $advisor) + [
                'update_time' => time(),
            ]);

            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除顾问
     * @param array $params
     * @return bool
     */
    public static function delete(array $params): bool
    {
        try {
            $advisorId = (int)$params['id'];
            $advisor = SalesAdvisor::find($advisorId);
            if (!$advisor) {
                throw new \Exception('顾问不存在');
            }

            if (self::activeCustomerCount($advisorId) > 0) {
                throw new \Exception('该顾问仍有新客户或跟进中客户，请先转移客户或将顾问改为休假/离职');
            }

            SalesAdvisor::destroy($advisorId);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 修改顾问状态
     * @param array $params
     * @return bool
     */
    public static function changeStatus(array $params): bool
    {
        try {
            $advisor = SalesAdvisor::find((int)$params['id']);
            if (!$advisor) {
                throw new \Exception('顾问不存在');
            }

            $advisor->save([
                'status' => (int)$params['status'],
                'update_time' => time(),
            ]);

            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 校准顾问当前客户数
     * @param array $params
     * @return bool
     */
    public static function syncCustomerCount(array $params): bool
    {
        try {
            $advisorId = (int)$params['id'];
            $advisor = SalesAdvisor::find($advisorId);
            if (!$advisor) {
                throw new \Exception('顾问不存在');
            }

            $count = self::activeCustomerCount($advisorId);
            $advisor->save([
                'current_customer_count' => $count,
                'update_time' => time(),
            ]);

            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 顾问状态选项
     * @return array
     */
    public static function statusOptions(): array
    {
        $options = [];
        foreach (SalesAdvisor::getStatusOptions() as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }
        return $options;
    }

    /**
     * @notes 组装保存数据
     * @param array $params
     * @param SalesAdvisor|null $advisor
     * @return array
     */
    private static function buildSaveData(array $params, ?SalesAdvisor $advisor = null): array
    {
        return [
            'admin_id' => (int)($params['admin_id'] ?? ($advisor->admin_id ?? 0)),
            'advisor_name' => trim((string)$params['advisor_name']),
            'avatar' => trim((string)($params['avatar'] ?? '')),
            'mobile' => trim((string)($params['mobile'] ?? '')),
            'wecom_userid' => trim((string)($params['wecom_userid'] ?? '')),
            'email' => trim((string)($params['email'] ?? '')),
            'areas' => self::normalizeJsonList($params['areas'] ?? []),
            'specialties' => self::normalizeJsonList($params['specialties'] ?? []),
            'max_customer_count' => max(1, (int)($params['max_customer_count'] ?? 100)),
            'status' => (int)($params['status'] ?? SalesAdvisor::STATUS_NORMAL),
            'sort' => max(0, (int)($params['sort'] ?? 0)),
        ];
    }

    /**
     * @notes 顾问公开输出字段
     * @return string
     */
    private static function advisorFields(): string
    {
        return 'id,admin_id,advisor_name,avatar,mobile,wecom_userid,email,areas,specialties,max_customer_count,current_customer_count,total_order_count,total_order_amount,conversion_rate,status,sort,create_time,update_time';
    }

    /**
     * @notes 规范化多选标签
     * @param mixed $value
     * @return array
     */
    private static function normalizeJsonList($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $value)));
        }

        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            $text = trim((string)$item);
            if ($text !== '' && !in_array($text, $result, true)) {
                $result[] = function_exists('mb_substr') ? mb_substr($text, 0, 50) : substr($text, 0, 50);
            }
        }

        return $result;
    }

    /**
     * @notes 检查顾问唯一性
     * @param array $params
     * @param int $ignoreId
     * @return void
     * @throws \Exception
     */
    private static function checkUniqueAdvisor(array $params, int $ignoreId = 0): void
    {
        $query = SalesAdvisor::where('advisor_name', trim((string)$params['advisor_name']));
        if ($ignoreId > 0) {
            $query->where('id', '<>', $ignoreId);
        }
        if ($query->find()) {
            throw new \Exception('已存在相同姓名的顾问');
        }

        $mobile = trim((string)($params['mobile'] ?? ''));
        if ($mobile !== '') {
            $query = SalesAdvisor::where('mobile', $mobile);
            if ($ignoreId > 0) {
                $query->where('id', '<>', $ignoreId);
            }
            if ($query->find()) {
                throw new \Exception('已存在相同手机号的顾问');
            }
        }
    }

    /**
     * @notes 活跃客户数
     * @param int $advisorId
     * @return int
     */
    private static function activeCustomerCount(int $advisorId): int
    {
        return Customer::where('advisor_id', $advisorId)
            ->whereIn('customer_status', [Customer::STATUS_NEW, Customer::STATUS_FOLLOWING])
            ->count();
    }

    /**
     * @notes 获取轻量管理员信息
     * @param int $adminId
     * @return array|null
     */
    private static function getAdminInfo(int $adminId): ?array
    {
        if ($adminId <= 0) {
            return null;
        }

        $admin = Db::name('admin')
            ->where('id', $adminId)
            ->field('id,name,account,avatar')
            ->find();

        return $admin ?: null;
    }
}
