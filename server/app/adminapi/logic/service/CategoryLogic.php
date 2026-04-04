<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务分类管理逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\service;

use app\common\logic\BaseLogic;
use app\common\model\service\ServiceCategory;
use app\common\model\staff\Staff;
use think\facade\Db;

/**
 * 服务分类管理逻辑
 * Class CategoryLogic
 * @package app\adminapi\logic\service
 */
class CategoryLogic extends BaseLogic
{
    /**
     * @notes 解析预约关联角色配置
     * @param array $params
     * @param ServiceCategory|null $currentCategory
     * @return array
     * @throws \Exception
     */
    private static function resolveBookingRolePayload(array $params, ?ServiceCategory $currentCategory = null): array
    {
        $currentCategoryId = (int)($currentCategory->id ?? 0);

        $butlerEnabled = (int)($params['booking_butler_enabled'] ?? ($currentCategory->booking_butler_enabled ?? 0));
        $butlerCategoryId = $butlerEnabled === 1
            ? (int)($params['booking_butler_category_id'] ?? ($currentCategory->booking_butler_category_id ?? 0))
            : 0;
        self::validateRelatedCategory($butlerEnabled, $butlerCategoryId, '婚礼管家', $currentCategoryId);

        $directorEnabled = (int)($params['booking_director_enabled'] ?? ($currentCategory->booking_director_enabled ?? 0));
        $directorCategoryId = $directorEnabled === 1
            ? (int)($params['booking_director_category_id'] ?? ($currentCategory->booking_director_category_id ?? 0))
            : 0;
        self::validateRelatedCategory($directorEnabled, $directorCategoryId, '婚礼督导', $currentCategoryId);

        return [
            'booking_butler_enabled' => $butlerEnabled,
            'booking_butler_category_id' => $butlerCategoryId,
            'booking_director_enabled' => $directorEnabled,
            'booking_director_category_id' => $directorCategoryId,
        ];
    }

    /**
     * @notes 校验关联分类
     * @param int $enabled
     * @param int $relatedCategoryId
     * @param string $roleLabel
     * @param int $currentCategoryId
     * @return void
     * @throws \Exception
     */
    private static function validateRelatedCategory(
        int $enabled,
        int $relatedCategoryId,
        string $roleLabel,
        int $currentCategoryId = 0
    ): void {
        if ($enabled !== 1) {
            return;
        }

        if ($relatedCategoryId <= 0) {
            throw new \Exception($roleLabel . '关联分类不能为空');
        }

        if ($currentCategoryId > 0 && $currentCategoryId === $relatedCategoryId) {
            throw new \Exception($roleLabel . '关联分类不能选择当前分类');
        }

        $relatedCategory = ServiceCategory::where('id', $relatedCategoryId)
            ->whereNull('delete_time')
            ->find();
        if (!$relatedCategory) {
            throw new \Exception($roleLabel . '关联分类不存在');
        }
    }

    /**
     * @notes 获取分类详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $category = ServiceCategory::find($id);
        if (!$category) {
            return [];
        }
        return $category->toArray();
    }

    /**
     * @notes 获取分类树形结构（为保持向后兼容，返回扁平列表）
     * @return array
     */
    public static function tree(): array
    {
        return ServiceCategory::where('delete_time', null)
            ->order('sort desc, id asc')
            ->field('id, name, icon, is_show, sort, create_time, booking_butler_enabled, booking_butler_category_id, booking_director_enabled, booking_director_category_id')
            ->select()
            ->toArray();
    }

    /**
     * @notes 添加分类
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            // 检查是否存在同名分类
            $exists = ServiceCategory::where('name', $params['name'])
                ->where('delete_time', null)
                ->find();
            if ($exists) {
                throw new \Exception('已存在相同名称的分类');
            }

            $bookingRolePayload = self::resolveBookingRolePayload($params);
            ServiceCategory::create([
                'name' => $params['name'],
                'icon' => $params['icon'] ?? '',
                'image' => $params['image'] ?? '',
                'booking_butler_enabled' => $bookingRolePayload['booking_butler_enabled'],
                'booking_butler_category_id' => $bookingRolePayload['booking_butler_category_id'],
                'booking_director_enabled' => $bookingRolePayload['booking_director_enabled'],
                'booking_director_category_id' => $bookingRolePayload['booking_director_category_id'],
                'sort' => $params['sort'] ?? 0,
                'is_show' => $params['is_show'] ?? 1,
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
     * @notes 编辑分类
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        try {
            $category = ServiceCategory::find($params['id']);
            if (!$category) {
                throw new \Exception('分类不存在');
            }

            // 检查是否存在同名分类
            $exists = ServiceCategory::where('name', $params['name'])
                ->where('id', '<>', $params['id'])
                ->where('delete_time', null)
                ->find();
            if ($exists) {
                throw new \Exception('已存在相同名称的分类');
            }

            $bookingRolePayload = self::resolveBookingRolePayload($params, $category);
            $category->save([
                'name' => $params['name'],
                'icon' => $params['icon'] ?? $category->icon,
                'image' => $params['image'] ?? $category->image,
                'booking_butler_enabled' => $bookingRolePayload['booking_butler_enabled'],
                'booking_butler_category_id' => $bookingRolePayload['booking_butler_category_id'],
                'booking_director_enabled' => $bookingRolePayload['booking_director_enabled'],
                'booking_director_category_id' => $bookingRolePayload['booking_director_category_id'],
                'sort' => $params['sort'] ?? $category->sort,
                'is_show' => $params['is_show'] ?? $category->is_show,
                'update_time' => time(),
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除分类
     * @param array $params
     * @return bool
     */
    public static function delete(array $params): bool
    {
        try {
            $category = ServiceCategory::find($params['id']);
            if (!$category) {
                throw new \Exception('分类不存在');
            }

            // 检查是否有关联的工作人员
            $staffCount = Staff::where('category_id', $params['id'])
                ->where('delete_time', null)
                ->count();
            if ($staffCount > 0) {
                throw new \Exception('该分类下存在工作人员，无法删除');
            }

            ServiceCategory::destroy($params['id']);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 修改分类状态
     * @param array $params
     * @return bool
     */
    public static function changeStatus(array $params): bool
    {
        try {
            ServiceCategory::update([
                'id' => $params['id'],
                'is_show' => $params['is_show'],
                'update_time' => time(),
            ]);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取所有分类
     * @return array
     */
    public static function getAll(): array
    {
        return ServiceCategory::where('delete_time', null)
            ->where('is_show', 1)
            ->order('sort desc, id asc')
            ->field('id, name, icon, sort, is_show, booking_butler_enabled, booking_butler_category_id, booking_director_enabled, booking_director_category_id')
            ->select()
            ->toArray();
    }
}
