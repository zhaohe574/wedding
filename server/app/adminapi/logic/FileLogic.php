<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------

namespace app\adminapi\logic;


use app\common\logic\BaseLogic;
use app\common\enum\FileEnum;
use app\common\model\file\File;
use app\common\model\file\FileCate;
use app\common\model\staff\Staff;
use app\common\model\auth\Admin;
use app\common\service\ConfigService;
use app\common\service\StaffService;
use app\common\service\storage\Driver as StorageDriver;

/**
 * 文件逻辑层
 * Class FileLogic
 * @package app\adminapi\logic
 */
class FileLogic extends BaseLogic
{
    /**
     * @notes 限制当前后台账号可见的素材范围
     */
    public static function applyVisibleScope($query, int $adminId = 0, array $adminInfo = [])
    {
        if (self::isRootAdmin($adminInfo)) {
            return $query;
        }

        $adminSourceIds = self::getVisibleAdminSourceIds($adminId, $adminInfo);
        $userSourceIds = self::getBoundUserIdsByAdminId($adminId);

        return $query->where(function ($scope) use ($adminSourceIds, $userSourceIds) {
            if (!empty($adminSourceIds)) {
                $scope->where(function ($backendQuery) use ($adminSourceIds) {
                    $backendQuery->where('source', FileEnum::SOURCE_ADMIN)
                        ->whereIn('source_id', $adminSourceIds);
                });
            }

            if (!empty($userSourceIds)) {
                if (!empty($adminSourceIds)) {
                    $scope->whereOr(function ($frontendQuery) use ($userSourceIds) {
                        $frontendQuery->where('source', FileEnum::SOURCE_USER)
                            ->whereIn('source_id', $userSourceIds);
                    });
                } else {
                    $scope->where(function ($frontendQuery) use ($userSourceIds) {
                        $frontendQuery->where('source', FileEnum::SOURCE_USER)
                            ->whereIn('source_id', $userSourceIds);
                    });
                }
            }

            if (empty($adminSourceIds) && empty($userSourceIds)) {
                $scope->whereRaw('1 = 0');
            }
        });
    }

    /**
     * @notes 当前账号是否可操作指定素材
     */
    public static function canAccessFileIds(array $ids, int $adminId = 0, array $adminInfo = []): bool
    {
        $ids = self::normalizeIds($ids);
        if (empty($ids)) {
            return false;
        }

        if (self::isRootAdmin($adminInfo)) {
            return File::whereIn('id', $ids)->count() === count($ids);
        }

        $visibleIds = self::visibleFileIds($ids, $adminId, $adminInfo);
        return count($visibleIds) === count($ids);
    }

    /**
     * @notes 获取当前后台账号绑定的前台用户ID
     */
    public static function getBoundUserIdsByAdminId(int $adminId): array
    {
        if ($adminId <= 0) {
            return [];
        }

        $userIds = Staff::where('admin_id', $adminId)
            ->whereNull('delete_time')
            ->column('user_id');

        return self::normalizeIds($userIds);
    }

    /**
     * @notes 上传素材时解析自动分组
     */
    public static function resolveUploadCateId(int $cid, int $type, int $adminId = 0, array $adminInfo = []): int
    {
        if (self::isRootAdmin($adminInfo) && $cid > 0) {
            return $cid;
        }

        $cateName = self::getUploadCateName($adminId, $adminInfo);
        if ($cateName === '') {
            return $cid > 0 ? $cid : 0;
        }

        return self::ensureUploadCate($type, $cateName);
    }

    /**
     * @notes 移动文件
     * @param $params
     * @author 张无忌
     * @date 2021/7/28 15:29
     */
    public static function move($params, int $adminId = 0, array $adminInfo = []): bool
    {
        $ids = self::normalizeIds($params['ids'] ?? []);
        if (!self::assertCanAccessFiles($ids, $adminId, $adminInfo)) {
            return false;
        }

        (new File())->whereIn('id', $ids)
            ->update([
                'cid' => $params['cid'],
                'update_time' => time()
            ]);
        return true;
    }

    /**
     * @notes 重命名文件
     * @param $params
     * @author 张无忌
     * @date 2021/7/29 17:16
     */
    public static function rename($params, int $adminId = 0, array $adminInfo = []): bool
    {
        $ids = self::normalizeIds([$params['id'] ?? 0]);
        if (!self::assertCanAccessFiles($ids, $adminId, $adminInfo)) {
            return false;
        }

        (new File())->where('id', $ids[0])
            ->update([
                'name' => $params['name'],
                'update_time' => time()
            ]);
        return true;
    }

    /**
     * @notes 批量删除文件
     * @param $params
     * @author 张无忌
     * @date 2021/7/28 15:41
     */
    public static function delete($params, int $adminId = 0, array $adminInfo = []): bool
    {
        $ids = self::normalizeIds($params['ids'] ?? []);
        if (!self::assertCanAccessFiles($ids, $adminId, $adminInfo)) {
            return false;
        }

        $result = File::whereIn('id', $ids)->select();
        $StorageDriver = new StorageDriver([
            'default' => ConfigService::get('storage', 'default', 'local'),
            'engine'  => ConfigService::get('storage') ?? ['local'=>[]],
        ]);
        foreach ($result as $item) {
            $StorageDriver->delete($item['uri']);
        }
        File::destroy($ids);
        return true;
    }

    /**
     * @notes 添加文件分类
     * @param $params
     * @author 张无忌
     * @date 2021/7/28 11:32
     */
    public static function addCate($params)
    {
        FileCate::create([
            'type' => $params['type'],
            'pid' => $params['pid'],
            'name' => $params['name']
        ]);
    }

    /**
     * @notes 编辑文件分类
     * @param $params
     * @author 张无忌
     * @date 2021/7/28 14:03
     */
    public static function editCate($params)
    {
        FileCate::update([
            'name' => $params['name'],
            'update_time' => time()
        ], ['id' => $params['id']]);
    }

    /**
     * @notes 删除文件分类
     * @param $params
     * @author 张无忌
     * @date 2021/7/28 14:21
     */
    public static function delCate($params, int $adminId = 0, array $adminInfo = []): bool
    {
        $fileModel = new File();
        $cateModel = new FileCate();

        $cateIds = self::getCateIds($params['id']);
        array_push($cateIds, $params['id']);

        // 删除分类及子分类
        $fileIds = self::normalizeIds($fileModel->whereIn('cid', $cateIds)->column('id'));
        if (!empty($fileIds) && !self::assertCanAccessFiles($fileIds, $adminId, $adminInfo, '该分组包含无权限操作的素材，无法删除')) {
            return false;
        }

        $cateModel->whereIn('id', $cateIds)->update(['delete_time' => time()]);

        if (!empty($fileIds)) {
            return self::delete(['ids' => $fileIds], $adminId, $adminInfo);
        }
        return true;
    }


    /**
     * @notes 获取所有分类id
     * @param $parentId
     * @param array $cateArr
     * @return array
     * @author 段誉
     * @date 2024/2/7 15:03
     */
    public static function getCateIds($parentId, array $cateArr = []): array
    {
        $childIds = FileCate::where(['pid' => $parentId])->column('id');

        if (empty($childIds)) {
            return $childIds;
        } else {
            $allChildIds = $childIds;
            foreach ($childIds as $childId) {
                $allChildIds = array_merge($allChildIds, static::getCateIds($childId, $cateArr));
            }
            return $allChildIds;
        }
    }

    /**
     * @notes 当前账号是否超级管理员
     */
    private static function isRootAdmin(array $adminInfo): bool
    {
        return (int)($adminInfo['root'] ?? 0) === 1;
    }

    /**
     * @notes 获取后台来源素材可见上传者ID
     */
    private static function getVisibleAdminSourceIds(int $adminId, array $adminInfo): array
    {
        if (self::isStaffMaterialScope($adminId, $adminInfo)) {
            return $adminId > 0 ? [$adminId] : [];
        }

        return array_values(array_unique(array_filter([0, $adminId], static fn ($id) => $id >= 0)));
    }

    /**
     * @notes 当前账号是否按服务人员素材范围隔离
     */
    private static function isStaffMaterialScope(int $adminId, array $adminInfo): bool
    {
        if (self::isRootAdmin($adminInfo)) {
            return false;
        }

        return StaffService::isStaffRole($adminInfo) || self::hasStaffBindingByAdminId($adminId);
    }

    /**
     * @notes 当前后台账号是否绑定服务人员档案
     */
    private static function hasStaffBindingByAdminId(int $adminId): bool
    {
        if ($adminId <= 0) {
            return false;
        }

        return Staff::where('admin_id', $adminId)
            ->whereNull('delete_time')
            ->count() > 0;
    }

    /**
     * @notes 获取上传自动分组名称
     */
    private static function getUploadCateName(int $adminId, array $adminInfo): string
    {
        $staffName = self::getStaffNameByAdminId($adminId);
        if ($staffName !== '') {
            return $staffName;
        }

        $name = trim((string)($adminInfo['name'] ?? ''));
        if ($name === '') {
            $name = trim((string)($adminInfo['account'] ?? ''));
        }
        if ($name === '' && $adminId > 0) {
            $admin = Admin::field('name,account')->find($adminId);
            if ($admin) {
                $name = trim((string)($admin->name ?: $admin->account));
            }
        }

        return self::normalizeCateName($name);
    }

    /**
     * @notes 按后台账号获取服务人员名称
     */
    private static function getStaffNameByAdminId(int $adminId): string
    {
        if ($adminId <= 0) {
            return '';
        }

        $staffName = Staff::where('admin_id', $adminId)
            ->whereNull('delete_time')
            ->order('id', 'desc')
            ->value('name');

        return self::normalizeCateName((string)$staffName);
    }

    /**
     * @notes 确保上传自动分组存在
     */
    private static function ensureUploadCate(int $type, string $name): int
    {
        $type = in_array($type, [FileEnum::IMAGE_TYPE, FileEnum::VIDEO_TYPE, FileEnum::FILE_TYPE], true)
            ? $type
            : FileEnum::IMAGE_TYPE;
        $name = self::normalizeCateName($name);
        if ($name === '') {
            return 0;
        }

        $cate = FileCate::where('type', $type)
            ->where('pid', 0)
            ->where('name', $name)
            ->find();
        if ($cate) {
            return (int)$cate->id;
        }

        $cate = FileCate::create([
            'type' => $type,
            'pid' => 0,
            'name' => $name,
            'create_time' => time(),
            'update_time' => time(),
        ]);

        return (int)$cate->id;
    }

    /**
     * @notes 规范化自动分组名称
     */
    private static function normalizeCateName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        return function_exists('mb_substr')
            ? mb_substr($name, 0, 32, 'UTF-8')
            : substr($name, 0, 32);
    }

    /**
     * @notes 获取可见素材ID
     */
    private static function visibleFileIds(array $ids, int $adminId, array $adminInfo): array
    {
        $query = File::whereIn('id', $ids);
        self::applyVisibleScope($query, $adminId, $adminInfo);
        return self::normalizeIds($query->column('id'));
    }

    /**
     * @notes 校验素材操作权限
     */
    private static function assertCanAccessFiles(array $ids, int $adminId, array $adminInfo, string $message = '无权限操作部分素材'): bool
    {
        $ids = self::normalizeIds($ids);
        if (empty($ids)) {
            self::setError('请选择要操作的素材');
            return false;
        }

        if (!self::canAccessFileIds($ids, $adminId, $adminInfo)) {
            self::setError($message);
            return false;
        }

        return true;
    }

    /**
     * @notes 规范化ID列表
     */
    private static function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids), static fn ($id) => $id > 0)));
    }

}
