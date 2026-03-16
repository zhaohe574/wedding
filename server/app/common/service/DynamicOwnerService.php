<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\dynamic\Dynamic;
use app\common\model\staff\Staff;
use app\common\model\user\User;

/**
 * 动态归属解析服务
 */
class DynamicOwnerService
{
    public const STATE_NON_STAFF = 'non_staff';
    public const STATE_RESOLVED = 'resolved';
    public const STATE_UNRESOLVED = 'unresolved';

    protected static array $contextCache = [];

    /**
     * @notes 解析服务人员动态归属上下文
     */
    public static function resolveStaffOwnerContext(int $adminId, array $adminInfo): array
    {
        $roleIds = array_map('intval', $adminInfo['role_id'] ?? []);
        $cacheKey = implode(':', [
            $adminId,
            trim((string)($adminInfo['account'] ?? '')),
            implode(',', $roleIds),
        ]);

        if (isset(self::$contextCache[$cacheKey])) {
            return self::$contextCache[$cacheKey];
        }

        if (!StaffService::isStaffRole($adminInfo)) {
            return self::$contextCache[$cacheKey] = [
                'state' => self::STATE_NON_STAFF,
                'owner_staff_id' => 0,
            ];
        }

        $ownerStaffId = StaffService::getStaffScopeId($adminId, $adminInfo);
        if ($ownerStaffId <= 0) {
            $ownerStaffId = self::resolveOwnerStaffIdByAccount((string)($adminInfo['account'] ?? ''));
        }

        return self::$contextCache[$cacheKey] = [
            'state' => $ownerStaffId > 0 ? self::STATE_RESOLVED : self::STATE_UNRESOLVED,
            'owner_staff_id' => $ownerStaffId > 0 ? $ownerStaffId : 0,
        ];
    }

    /**
     * @notes 是否为服务人员角色
     */
    public static function isStaffContext(array $context): bool
    {
        return ($context['state'] ?? self::STATE_NON_STAFF) !== self::STATE_NON_STAFF;
    }

    /**
     * @notes 是否已解析出动态归属
     */
    public static function isResolvedContext(array $context): bool
    {
        return ($context['state'] ?? self::STATE_NON_STAFF) === self::STATE_RESOLVED
            && (int)($context['owner_staff_id'] ?? 0) > 0;
    }

    /**
     * @notes 动态读取权限缺失提示
     */
    public static function getOwnerViewDeniedMessage(): string
    {
        return '当前后台账号未关联可用的服务人员动态归属，无法查看或管理动态';
    }

    /**
     * @notes 动态写入权限缺失提示
     */
    public static function getOwnerManageDeniedMessage(): string
    {
        return '当前后台账号未关联可用的服务人员动态归属，无法发布或管理动态';
    }

    /**
     * @notes 为查询追加“本人动态”过滤
     */
    public static function applyOwnedStaffDynamicFilter($query, int $ownerStaffId): void
    {
        $query->where('user_type', Dynamic::USER_TYPE_STAFF)
            ->where(function ($subQuery) use ($ownerStaffId) {
                $subQuery->where('staff_id', $ownerStaffId)
                    ->whereOr('user_id', $ownerStaffId);
            });
    }

    /**
     * @notes 查询本人动态
     */
    public static function findOwnedStaffDynamic(int $dynamicId, int $ownerStaffId): ?Dynamic
    {
        $query = Dynamic::where('id', $dynamicId);
        self::applyOwnedStaffDynamicFilter($query, $ownerStaffId);
        return $query->find();
    }

    /**
     * @notes 按后台账号解析动态归属 staff_id
     */
    protected static function resolveOwnerStaffIdByAccount(string $account): int
    {
        $account = trim($account);
        if ($account === '') {
            return 0;
        }

        $ownerStaffId = self::matchUniqueStaffIdByUserField('mobile', $account);
        if ($ownerStaffId !== null) {
            return $ownerStaffId;
        }

        $ownerStaffId = self::matchUniqueStaffIdByUserField('account', $account);
        if ($ownerStaffId !== null) {
            return $ownerStaffId;
        }

        $ownerStaffId = self::matchUniqueStaffIdByStaffField('mobile_full', $account);
        if ($ownerStaffId !== null) {
            return $ownerStaffId;
        }

        return self::matchUniqueStaffIdByStaffField('mobile', $account) ?? 0;
    }

    /**
     * @notes 通过用户字段唯一匹配 staff_id
     * @return int|null null=未命中，0=命中不唯一，正整数=唯一 staff_id
     */
    protected static function matchUniqueStaffIdByUserField(string $field, string $value): ?int
    {
        $userIds = User::where($field, $value)
            ->whereNull('delete_time')
            ->column('id');

        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (empty($userIds)) {
            return null;
        }

        $staffIds = Staff::whereIn('user_id', $userIds)
            ->whereNull('delete_time')
            ->column('id');

        return self::normalizeMatchedStaffIds($staffIds);
    }

    /**
     * @notes 通过 staff 字段唯一匹配 staff_id
     * @return int|null null=未命中，0=命中不唯一，正整数=唯一 staff_id
     */
    protected static function matchUniqueStaffIdByStaffField(string $field, string $value): ?int
    {
        $staffIds = Staff::where($field, $value)
            ->whereNull('delete_time')
            ->column('id');

        return self::normalizeMatchedStaffIds($staffIds);
    }

    /**
     * @notes 归一化唯一匹配结果
     * @return int|null
     */
    protected static function normalizeMatchedStaffIds(array $staffIds): ?int
    {
        $staffIds = array_values(array_unique(array_map('intval', $staffIds)));
        if (empty($staffIds)) {
            return null;
        }

        if (count($staffIds) !== 1) {
            return 0;
        }

        return $staffIds[0];
    }
}
