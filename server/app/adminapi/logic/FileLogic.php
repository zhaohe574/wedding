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
use app\common\model\auth\AdminRole;
use app\common\model\auth\SystemRole;
use app\common\service\ConfigService;
use app\common\service\storage\Driver as StorageDriver;

/**
 * 文件逻辑层
 * 素材空间模型：
 * - 个人素材：source=0/source_id=后台账号ID，或绑定服务人员前台上传 source=1/source_id=前台用户ID；
 *   对应个人分组 is_public=0, admin_id=后台账号ID。
 * - 公共素材：source=0/source_id=0；对应公共分组 is_public=1, admin_id=0。
 * - 素材管理员（isMaterialManager）：超级管理员(root=1)或「管理员」角色，可管理公共素材与全员素材。
 * Class FileLogic
 * @package app\adminapi\logic
 */
class FileLogic extends BaseLogic
{
    /**
     * @notes 素材空间范围：个人
     */
    public const SCOPE_MINE = 'mine';

    /**
     * @notes 素材空间范围：公共
     */
    public const SCOPE_PUBLIC = 'public';

    /**
     * @notes 素材空间范围：全部（仅素材管理员可用）
     */
    public const SCOPE_ALL = 'all';

    /**
     * @notes 当前账号是否素材管理员（超级管理员或「管理员」角色）
     */
    public static function isMaterialManager(int $adminId = 0, array $adminInfo = []): bool
    {
        if (self::isRootAdmin($adminInfo)) {
            return true;
        }

        $roleIds = array_map('intval', $adminInfo['role_id'] ?? []);
        if (empty($roleIds) && $adminId > 0) {
            $roleIds = array_map('intval', AdminRole::where('admin_id', $adminId)->column('role_id'));
        }
        if (empty($roleIds)) {
            return false;
        }

        $managerRoleIds = array_map('intval', SystemRole::where('name', '管理员')->whereNull('delete_time')->column('id'));
        if (empty($managerRoleIds)) {
            return false;
        }

        return !empty(array_intersect($managerRoleIds, $roleIds));
    }

    /**
     * @notes 规范化素材空间范围（非管理员请求 all 时强制降级为 mine）
     */
    public static function normalizeScope(string $scope, int $adminId = 0, array $adminInfo = []): string
    {
        if (!in_array($scope, [self::SCOPE_MINE, self::SCOPE_PUBLIC, self::SCOPE_ALL], true)) {
            return self::SCOPE_MINE;
        }

        if ($scope === self::SCOPE_ALL && !self::isMaterialManager($adminId, $adminInfo)) {
            return self::SCOPE_MINE;
        }

        return $scope;
    }

    /**
     * @notes 限制素材列表可见范围（source/source_id 维度）
     */
    public static function applyVisibleScope($query, int $adminId = 0, array $adminInfo = [], string $scope = self::SCOPE_MINE, string $ownerAdminId = '')
    {
        $scope = self::normalizeScope($scope, $adminId, $adminInfo);

        if ($scope === self::SCOPE_PUBLIC) {
            return $query->where('source', FileEnum::SOURCE_ADMIN)
                ->where('source_id', 0);
        }

        if ($scope === self::SCOPE_ALL) {
            // 已通过 normalizeScope 保证为素材管理员
            if ($ownerAdminId === '0' || $ownerAdminId === 'public') {
                return $query->where('source', FileEnum::SOURCE_ADMIN)
                    ->where('source_id', 0);
            }

            $targetAdminId = (int)$ownerAdminId;
            if ($targetAdminId > 0) {
                $targetUserIds = self::getBoundUserIdsByAdminId($targetAdminId);
                return $query->where(function ($ownerQuery) use ($targetAdminId, $targetUserIds) {
                    $ownerQuery->where(function ($backendQuery) use ($targetAdminId) {
                        $backendQuery->where('source', FileEnum::SOURCE_ADMIN)
                            ->where('source_id', $targetAdminId);
                    });
                    if (!empty($targetUserIds)) {
                        $ownerQuery->whereOr(function ($frontendQuery) use ($targetUserIds) {
                            $frontendQuery->where('source', FileEnum::SOURCE_USER)
                                ->whereIn('source_id', $targetUserIds);
                        });
                    }
                });
            }

            return $query;
        }

        // SCOPE_MINE：仅本人后台素材 + 绑定服务人员的前台素材，绝不混入公共素材（source_id=0）
        $adminSourceIds = $adminId > 0 ? [$adminId] : [];
        $userSourceIds = self::getBoundUserIdsByAdminId($adminId);

        return $query->where(function ($scopeQuery) use ($adminSourceIds, $userSourceIds) {
            if (!empty($adminSourceIds)) {
                $scopeQuery->where(function ($backendQuery) use ($adminSourceIds) {
                    $backendQuery->where('source', FileEnum::SOURCE_ADMIN)
                        ->whereIn('source_id', $adminSourceIds);
                });
            }

            if (!empty($userSourceIds)) {
                if (!empty($adminSourceIds)) {
                    $scopeQuery->whereOr(function ($frontendQuery) use ($userSourceIds) {
                        $frontendQuery->where('source', FileEnum::SOURCE_USER)
                            ->whereIn('source_id', $userSourceIds);
                    });
                } else {
                    $scopeQuery->where(function ($frontendQuery) use ($userSourceIds) {
                        $frontendQuery->where('source', FileEnum::SOURCE_USER)
                            ->whereIn('source_id', $userSourceIds);
                    });
                }
            }

            if (empty($adminSourceIds) && empty($userSourceIds)) {
                $scopeQuery->whereRaw('1 = 0');
            }
        });
    }

    /**
     * @notes 限制分组列表可见范围（is_public/admin_id 维度）
     */
    public static function applyCateVisibleScope($query, int $adminId = 0, array $adminInfo = [], string $scope = self::SCOPE_MINE, string $ownerAdminId = '')
    {
        $scope = self::normalizeScope($scope, $adminId, $adminInfo);

        if ($scope === self::SCOPE_PUBLIC) {
            return $query->where('is_public', 1);
        }

        if ($scope === self::SCOPE_ALL) {
            // 已通过 normalizeScope 保证为素材管理员
            if ($ownerAdminId === '0' || $ownerAdminId === 'public') {
                return $query->where('is_public', 1);
            }

            $targetAdminId = (int)$ownerAdminId;
            if ($targetAdminId > 0) {
                return $query->where('is_public', 0)
                    ->where('admin_id', $targetAdminId);
            }

            return $query;
        }

        // SCOPE_MINE：仅本人个人分组
        if ($adminId <= 0) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('is_public', 0)
            ->where('admin_id', $adminId);
    }

    /**
     * @notes 当前账号是否可操作指定素材（非管理员不可操作公共素材与他人素材）
     */
    public static function canAccessFileIds(array $ids, int $adminId = 0, array $adminInfo = []): bool
    {
        $ids = self::normalizeIds($ids);
        if (empty($ids)) {
            return false;
        }

        if (self::isMaterialManager($adminId, $adminInfo)) {
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
     * @notes 上传素材时解析上传者ID（公共素材要求管理员权限，source_id 固定为 0）
     */
    public static function resolveUploadSourceId(int $adminId = 0, array $adminInfo = [], string $scope = self::SCOPE_MINE): int
    {
        if ($scope === self::SCOPE_PUBLIC) {
            if (!self::isMaterialManager($adminId, $adminInfo)) {
                throw new \RuntimeException('仅管理员可上传公共素材');
            }
            return 0;
        }

        return $adminId > 0 ? $adminId : 0;
    }

    /**
     * @notes 上传素材时解析目标分组
     */
    public static function resolveUploadCateId(int $cid, int $type, int $adminId = 0, array $adminInfo = [], string $scope = self::SCOPE_MINE): int
    {
        if ($scope === self::SCOPE_PUBLIC) {
            if (!self::isMaterialManager($adminId, $adminInfo)) {
                throw new \RuntimeException('仅管理员可上传公共素材');
            }

            if ($cid > 0) {
                $exists = FileCate::where('id', $cid)
                    ->where('is_public', 1)
                    ->where('type', $type)
                    ->count();
                if ($exists > 0) {
                    return $cid;
                }
            }
            return 0;
        }

        // 个人空间：优先落入当前选中的本人个人分组
        if ($cid > 0 && $adminId > 0) {
            $exists = FileCate::where('id', $cid)
                ->where('is_public', 0)
                ->where('admin_id', $adminId)
                ->where('type', $type)
                ->count();
            if ($exists > 0) {
                return $cid;
            }
        }

        // 业务页直接上传：自动创建账号同名个人分组
        if ($adminId <= 0) {
            return 0;
        }

        $cateName = self::getUploadCateName($adminId, $adminInfo);
        if ($cateName === '') {
            return 0;
        }

        return self::ensureUploadCate($type, $cateName, $adminId, 0);
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

        $files = File::whereIn('id', $ids)->field(['id', 'source', 'source_id'])->select();
        $hasPublic = false;
        $hasPrivate = false;
        $privateAdminOwnerIds = [];
        $hasUserSource = false;
        foreach ($files as $file) {
            if ((int)$file->source === FileEnum::SOURCE_ADMIN && (int)$file->source_id === 0) {
                $hasPublic = true;
                continue;
            }

            $hasPrivate = true;
            if ((int)$file->source === FileEnum::SOURCE_ADMIN) {
                $privateAdminOwnerIds[] = (int)$file->source_id;
            } else {
                $hasUserSource = true;
            }
        }

        if ($hasPublic && $hasPrivate) {
            self::setError('公共素材与个人素材不能同时移动');
            return false;
        }

        $cid = (int)($params['cid'] ?? 0);
        if ($cid > 0) {
            $targetCate = FileCate::where('id', $cid)->find();
            if (!$targetCate) {
                self::setError('目标分组不存在');
                return false;
            }

            $targetIsPublic = (int)$targetCate->is_public === 1;
            if ($hasPublic && !$targetIsPublic) {
                self::setError('公共素材与个人素材不能跨空间移动');
                return false;
            }
            if ($hasPrivate && $targetIsPublic) {
                self::setError('公共素材与个人素材不能跨空间移动');
                return false;
            }

            if ($hasPrivate && !$targetIsPublic) {
                $privateAdminOwnerIds = array_values(array_unique(array_filter($privateAdminOwnerIds)));
                if (!self::isMaterialManager($adminId, $adminInfo)) {
                    // 非管理员只能移入自己的个人分组
                    if ((int)$targetCate->admin_id !== $adminId) {
                        self::setError('无权限移动到该分组');
                        return false;
                    }
                } elseif (!$hasUserSource && !empty($privateAdminOwnerIds)
                    && !in_array((int)$targetCate->admin_id, $privateAdminOwnerIds, true)) {
                    // 管理员跨人移动：个人素材只能移入素材归属账号的个人分组
                    self::setError('无权限移动到该分组');
                    return false;
                }
            }
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
    public static function addCate($params, int $adminId = 0, array $adminInfo = []): bool
    {
        $scope = (string)($params['scope'] ?? self::SCOPE_MINE);
        if ($scope === self::SCOPE_PUBLIC) {
            if (!self::isMaterialManager($adminId, $adminInfo)) {
                self::setError('仅管理员可管理公共素材分组');
                return false;
            }
            $isPublic = 1;
            $ownerAdminId = 0;
        } else {
            if ($adminId <= 0) {
                self::setError('无权限操作');
                return false;
            }
            $isPublic = 0;
            $ownerAdminId = $adminId;
        }

        $pid = (int)($params['pid'] ?? 0);
        if ($pid > 0) {
            $parent = FileCate::where('id', $pid)->find();
            if (!$parent
                || (int)$parent->type !== (int)$params['type']
                || (int)$parent->is_public !== $isPublic
                || (int)$parent->admin_id !== $ownerAdminId) {
                self::setError('上级分组无权使用或不存在');
                return false;
            }
        }

        FileCate::create([
            'type' => $params['type'],
            'pid' => $pid,
            'name' => $params['name'],
            'is_public' => $isPublic,
            'admin_id' => $ownerAdminId,
        ]);
        return true;
    }

    /**
     * @notes 编辑文件分类
     * @param $params
     * @author 张无忌
     * @date 2021/7/28 14:03
     */
    public static function editCate($params, int $adminId = 0, array $adminInfo = []): bool
    {
        $cate = FileCate::where('id', (int)($params['id'] ?? 0))->find();
        if (!$cate) {
            self::setError('分组不存在');
            return false;
        }

        if (!self::canManageCate($cate, $adminId, $adminInfo)) {
            self::setError('无权限操作该分组');
            return false;
        }

        FileCate::update([
            'name' => $params['name'],
            'update_time' => time()
        ], ['id' => $params['id']]);
        return true;
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

        $cate = FileCate::where('id', (int)($params['id'] ?? 0))->find();
        if (!$cate) {
            self::setError('分组不存在');
            return false;
        }

        if (!self::canManageCate($cate, $adminId, $adminInfo)) {
            self::setError('无权限操作该分组');
            return false;
        }

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
     * @notes 获取素材归属筛选项（公共素材 + 全部后台账号），仅素材管理员可用
     */
    public static function getOwnerOptions(): array
    {
        $options = [['value' => '0', 'label' => '公共素材']];

        $staffNames = Staff::where('admin_id', '>', 0)
            ->whereNull('delete_time')
            ->order('id', 'desc')
            ->column('name', 'admin_id');

        $admins = Admin::whereNull('delete_time')
            ->where('disable', 0)
            ->order('id', 'asc')
            ->field(['id', 'name', 'account'])
            ->select();

        foreach ($admins as $admin) {
            $adminId = (int)$admin->id;
            $label = trim((string)($staffNames[$adminId] ?? ''));
            if ($label === '') {
                $label = trim((string)($admin->name ?: $admin->account));
            }
            if ($label === '') {
                $label = (string)$adminId;
            }
            $options[] = ['value' => (string)$adminId, 'label' => $label];
        }

        return $options;
    }

    /**
     * @notes 按批次解析文件归属元数据：is_public、owner_name（以文件ID为键）
     */
    public static function resolveOwnerMeta(array $rows): array
    {
        $adminIds = [];
        foreach ($rows as $row) {
            if ((int)($row['source'] ?? FileEnum::SOURCE_ADMIN) === FileEnum::SOURCE_ADMIN
                && (int)($row['source_id'] ?? 0) > 0) {
                $adminIds[] = (int)$row['source_id'];
            }
        }
        $adminIds = array_values(array_unique($adminIds));

        $adminNames = [];
        if (!empty($adminIds)) {
            $staffNames = Staff::whereIn('admin_id', $adminIds)
                ->whereNull('delete_time')
                ->order('id', 'desc')
                ->column('name', 'admin_id');

            $admins = Admin::whereIn('id', $adminIds)
                ->field(['id', 'name', 'account'])
                ->select();
            $adminById = [];
            foreach ($admins as $admin) {
                $adminById[(int)$admin->id] = $admin;
            }

            foreach ($adminIds as $adminId) {
                $name = trim((string)($staffNames[$adminId] ?? ''));
                if ($name === '' && isset($adminById[$adminId])) {
                    $name = trim((string)($adminById[$adminId]->name ?: $adminById[$adminId]->account));
                }
                $adminNames[$adminId] = $name !== '' ? $name : '账号#' . $adminId;
            }
        }

        $meta = [];
        foreach ($rows as $row) {
            $source = (int)($row['source'] ?? FileEnum::SOURCE_ADMIN);
            $sourceId = (int)($row['source_id'] ?? 0);
            $id = (int)($row['id'] ?? 0);

            if ($source === FileEnum::SOURCE_ADMIN && $sourceId === 0) {
                $meta[$id] = ['is_public' => true, 'owner_name' => '公共素材'];
            } elseif ($source === FileEnum::SOURCE_ADMIN) {
                $meta[$id] = ['is_public' => false, 'owner_name' => $adminNames[$sourceId] ?? ('账号#' . $sourceId)];
            } else {
                $meta[$id] = ['is_public' => false, 'owner_name' => '前台用户#' . $sourceId];
            }
        }

        return $meta;
    }

    /**
     * @notes 当前账号是否超级管理员
     */
    private static function isRootAdmin(array $adminInfo): bool
    {
        return (int)($adminInfo['root'] ?? 0) === 1;
    }

    /**
     * @notes 当前账号是否可管理指定分组（非管理员仅可操作自己的个人分组）
     */
    private static function canManageCate($cate, int $adminId, array $adminInfo): bool
    {
        if (self::isMaterialManager($adminId, $adminInfo)) {
            return true;
        }

        return (int)$cate->is_public !== 1 && (int)$cate->admin_id === $adminId;
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
     * @notes 确保上传自动分组存在（个人分组带 is_public=0, admin_id=账号ID；公共分组 admin_id 固定为 0）
     */
    private static function ensureUploadCate(int $type, string $name, int $adminId = 0, int $isPublic = 0): int
    {
        $type = in_array($type, [FileEnum::IMAGE_TYPE, FileEnum::VIDEO_TYPE, FileEnum::FILE_TYPE], true)
            ? $type
            : FileEnum::IMAGE_TYPE;
        $name = self::normalizeCateName($name);
        if ($name === '') {
            return 0;
        }

        $ownerAdminId = $isPublic === 1 ? 0 : $adminId;

        $cate = FileCate::where('type', $type)
            ->where('pid', 0)
            ->where('name', $name)
            ->where('is_public', $isPublic)
            ->where('admin_id', $ownerAdminId)
            ->find();
        if ($cate) {
            return (int)$cate->id;
        }

        $cate = FileCate::create([
            'type' => $type,
            'pid' => 0,
            'name' => $name,
            'is_public' => $isPublic,
            'admin_id' => $ownerAdminId,
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
     * @notes 获取可见素材ID（严格按本人素材范围，不含公共素材）
     */
    private static function visibleFileIds(array $ids, int $adminId, array $adminInfo): array
    {
        $query = File::whereIn('id', $ids);
        self::applyVisibleScope($query, $adminId, $adminInfo, self::SCOPE_MINE);
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
