<?php

declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

/** 将新增操作权限登记到已有数据库，不改变角色授权。 */
class SyncAdminPermissions extends Command
{
    protected function configure()
    {
        $this->setName('sync_admin_permissions')->setDescription('补登记后台操作权限，不自动授予角色');
    }

    protected function execute(Input $input, Output $output)
    {
        $connection = (string)config('database.default', 'mysql');
        $prefix = (string)config('database.connections.' . $connection . '.prefix', 'la_');
        if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) {
            throw new \RuntimeException('数据库表前缀不合法');
        }
        $sql = file_get_contents(dirname(__DIR__, 3) . '/database/migrations/20260914_admin_permissions.sql');
        $sql = preg_replace('/^--.*$/m', '', $sql);
        Db::transaction(static function () use ($sql, $prefix) {
            foreach (explode(';', str_replace('`la_', '`' . $prefix, $sql)) as $statement) {
                if (trim($statement) !== '') Db::execute($statement);
            }
        });
        (new \app\common\cache\AdminAuthCache())->deleteTag();
        $output->writeln('权限登记完成，请按岗位需要在角色管理中分配新增操作。');
        return 0;
    }
}
