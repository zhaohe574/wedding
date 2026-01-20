<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务套餐管理逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\service;

use app\common\logic\BaseLogic;
use app\common\model\service\ServicePackage;
use app\common\model\staff\StaffPackage;

/**
 * 服务套餐管理逻辑
 * Class PackageLogic
 * @package app\adminapi\logic\service
 */
class PackageLogic extends BaseLogic
{
    /**
     * @notes 获取套餐详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $package = ServicePackage::with(['category'])->find($id);
        if (!$package) {
            return [];
        }
        return $package->toArray();
    }

    /**
     * @notes 添加套餐
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            ServicePackage::create([
                'category_id' => $params['category_id'] ?? 0,
                'name' => $params['name'],
                'price' => $params['price'] ?? 0,
                'original_price' => $params['original_price'] ?? 0,
                'duration' => $params['duration'] ?? 0,
                'content' => $params['content'] ?? [],
                'description' => $params['description'] ?? '',
                'sort' => $params['sort'] ?? 0,
                'is_show' => $params['is_show'] ?? 1,
                'is_recommend' => $params['is_recommend'] ?? 0,
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
     * @notes 编辑套餐
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        try {
            $package = ServicePackage::find($params['id']);
            if (!$package) {
                throw new \Exception('套餐不存在');
            }

            $package->save([
                'category_id' => $params['category_id'] ?? $package->category_id,
                'name' => $params['name'],
                'price' => $params['price'] ?? $package->price,
                'original_price' => $params['original_price'] ?? $package->original_price,
                'duration' => $params['duration'] ?? $package->duration,
                'content' => $params['content'] ?? $package->content,
                'description' => $params['description'] ?? $package->description,
                'sort' => $params['sort'] ?? $package->sort,
                'is_show' => $params['is_show'] ?? $package->is_show,
                'is_recommend' => $params['is_recommend'] ?? $package->is_recommend,
                'update_time' => time(),
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除套餐
     * @param array $params
     * @return bool
     */
    public static function delete(array $params): bool
    {
        try {
            $package = ServicePackage::find($params['id']);
            if (!$package) {
                throw new \Exception('套餐不存在');
            }

            // 检查是否有关联的工作人员
            $staffCount = StaffPackage::where('package_id', $params['id'])->count();
            if ($staffCount > 0) {
                throw new \Exception('该套餐已被工作人员关联，无法删除');
            }

            ServicePackage::destroy($params['id']);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 修改套餐状态
     * @param array $params
     * @return bool
     */
    public static function changeStatus(array $params): bool
    {
        try {
            ServicePackage::update([
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
     * @notes 获取所有套餐
     * @param array $params
     * @return array
     */
    public static function getAll(array $params = []): array
    {
        $query = ServicePackage::where('delete_time', null)
            ->where('is_show', 1);

        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        return $query->order('sort desc, id desc')
            ->field('id, name, category_id, price, duration')
            ->select()
            ->toArray();
    }
}
