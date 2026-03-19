<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 附加服务管理逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\service;

use app\common\logic\BaseLogic;
use app\common\model\service\ServiceAddon;
use app\common\model\service\ServicePackageAddon;
use app\common\model\staff\Staff;

/**
 * 附加服务管理逻辑
 * Class AddonLogic
 * @package app\adminapi\logic\service
 */
class AddonLogic extends BaseLogic
{
    /**
     * @notes 获取详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $addon = ServiceAddon::where('id', $id)
            ->whereNull('delete_time')
            ->find();
        if (!$addon) {
            return [];
        }

        return $addon->append(['category_name', 'staff_name', 'is_show_desc'])->toArray();
    }

    /**
     * @notes 添加附加服务
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            $staffId = (int)($params['staff_id'] ?? 0);
            if ($staffId <= 0) {
                throw new \Exception('请选择所属人员');
            }

            ServiceAddon::create([
                'staff_id' => $staffId,
                'category_id' => self::resolveStaffCategoryId($staffId),
                'name' => trim((string)$params['name']),
                'price' => $params['price'] ?? 0,
                'original_price' => $params['original_price'] ?? 0,
                'image' => $params['image'] ?? '',
                'description' => $params['description'] ?? '',
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
     * @notes 编辑附加服务
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        try {
            $addon = ServiceAddon::find((int)$params['id']);
            if (!$addon) {
                throw new \Exception('附加服务不存在');
            }

            $staffId = (int)($params['staff_id'] ?? $addon->staff_id);
            if ($staffId <= 0) {
                throw new \Exception('请选择所属人员');
            }

            $addon->save([
                'staff_id' => $staffId,
                'category_id' => self::resolveStaffCategoryId($staffId),
                'name' => trim((string)$params['name']),
                'price' => $params['price'] ?? $addon->price,
                'original_price' => $params['original_price'] ?? $addon->original_price,
                'image' => $params['image'] ?? $addon->image,
                'description' => $params['description'] ?? $addon->description,
                'sort' => $params['sort'] ?? $addon->sort,
                'is_show' => $params['is_show'] ?? $addon->is_show,
                'update_time' => time(),
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除附加服务
     * @param array $params
     * @return bool
     */
    public static function delete(array $params): bool
    {
        try {
            $addon = ServiceAddon::find((int)$params['id']);
            if (!$addon) {
                throw new \Exception('附加服务不存在');
            }

            ServicePackageAddon::clearByAddonId((int)$params['id']);
            ServiceAddon::destroy((int)$params['id']);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 修改状态
     * @param array $params
     * @return bool
     */
    public static function changeStatus(array $params): bool
    {
        try {
            ServiceAddon::update([
                'id' => (int)$params['id'],
                'is_show' => (int)$params['is_show'],
                'update_time' => time(),
            ]);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取下拉数据
     * @param array $params
     * @return array
     */
    public static function getAll(array $params = []): array
    {
        $query = ServiceAddon::whereNull('delete_time')
            ->where('is_show', 1);

        if (array_key_exists('staff_id', $params) && $params['staff_id'] !== '') {
            $query->where('staff_id', (int)$params['staff_id']);
        }

        if (array_key_exists('category_id', $params) && $params['category_id'] !== '') {
            $query->where('category_id', (int)$params['category_id']);
        }

        if (!empty($params['name'])) {
            $query->whereLike('name', '%' . trim((string)$params['name']) . '%');
        }

        return $query->field('id, staff_id, category_id, name, price, original_price, description, image, sort, is_show')
            ->order('sort desc, id desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 读取所属人员当前服务分类
     * @param int $staffId
     * @return int
     */
    protected static function resolveStaffCategoryId(int $staffId): int
    {
        $staff = Staff::find($staffId);
        if (!$staff) {
            throw new \Exception('所属人员不存在');
        }

        $categoryId = (int)($staff->category_id ?? 0);
        if ($categoryId <= 0) {
            throw new \Exception('请先为所属人员设置服务分类');
        }

        return $categoryId;
    }
}
