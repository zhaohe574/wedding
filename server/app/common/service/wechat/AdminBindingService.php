<?php
declare(strict_types=1);

namespace app\common\service\wechat;

/** 保留旧调用的明确错误，工作账号关系已迁移至管理员专用入口。 */
class AdminBindingService
{
    public static function entry(int $adminId): array
    {
        return \app\common\service\AccountBindingService::detail($adminId);
    }
    public static function claim(int $userId, string $code): void
    {
        throw new \RuntimeException('账号关联由授权管理员统一管理');
    }
    public static function confirm(int $adminId, string $code): void
    {
        throw new \RuntimeException('账号关联由授权管理员统一管理');
    }
    public static function revoke(int $adminId): void
    {
        throw new \RuntimeException('账号关联由授权管理员统一管理');
    }
}
