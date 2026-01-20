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
     * @notes 获取分类详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $category = ServiceCategory::with(['parent'])->find($id);
        if (!$category) {
            return [];
        }
        return $category->toArray();
    }

    /**
     * @notes 获取分类树形结构
     * @return array
     */
    public static function tree(): array
    {
        $list = ServiceCategory::where('delete_time', null)
            ->order('sort desc, id asc')
            ->field('id, pid, name, icon, is_show, sort, create_time')
            ->select()
            ->toArray();

        return self::buildTree($list);
    }

    /**
     * @notes 构建树形结构
     * @param array $list
     * @param int $pid
     * @return array
     */
    protected static function buildTree(array $list, int $pid = 0): array
    {
        $tree = [];
        foreach ($list as $item) {
            if ($item['pid'] == $pid) {
                $children = self::buildTree($list, $item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }

    /**
     * @notes 添加分类
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            // 检查同级是否存在同名分类
            $exists = ServiceCategory::where('pid', $params['pid'] ?? 0)
                ->where('name', $params['name'])
                ->where('delete_time', null)
                ->find();
            if ($exists) {
                throw new \Exception('同级分类下已存在相同名称的分类');
            }

            // 计算层级
            $level = 1;
            if (!empty($params['pid'])) {
                $parent = ServiceCategory::find($params['pid']);
                if ($parent) {
                    $level = $parent->level + 1;
                    if ($level > 3) {
                        throw new \Exception('分类最多支持3级');
                    }
                }
            }

            ServiceCategory::create([
                'pid' => $params['pid'] ?? 0,
                'name' => $params['name'],
                'icon' => $params['icon'] ?? '',
                'level' => $level,
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

            // 检查同级是否存在同名分类
            $exists = ServiceCategory::where('pid', $params['pid'] ?? $category->pid)
                ->where('name', $params['name'])
                ->where('id', '<>', $params['id'])
                ->where('delete_time', null)
                ->find();
            if ($exists) {
                throw new \Exception('同级分类下已存在相同名称的分类');
            }

            // 不能将分类设置为自己的子分类
            if (isset($params['pid']) && $params['pid'] == $params['id']) {
                throw new \Exception('不能将分类设置为自己的子分类');
            }

            // 检查是否将分类移动到其子分类下
            if (isset($params['pid']) && $params['pid'] != $category->pid) {
                $childIds = self::getChildIds($params['id']);
                if (in_array($params['pid'], $childIds)) {
                    throw new \Exception('不能将分类移动到其子分类下');
                }
            }

            // 计算层级
            $level = 1;
            $newPid = $params['pid'] ?? $category->pid;
            if ($newPid > 0) {
                $parent = ServiceCategory::find($newPid);
                if ($parent) {
                    $level = $parent->level + 1;
                    if ($level > 3) {
                        throw new \Exception('分类最多支持3级');
                    }
                }
            }

            $category->save([
                'pid' => $newPid,
                'name' => $params['name'],
                'icon' => $params['icon'] ?? $category->icon,
                'level' => $level,
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
     * @notes 获取所有子分类ID
     * @param int $id
     * @return array
     */
    protected static function getChildIds(int $id): array
    {
        $ids = [];
        $children = ServiceCategory::where('pid', $id)
            ->where('delete_time', null)
            ->column('id');

        foreach ($children as $childId) {
            $ids[] = $childId;
            $ids = array_merge($ids, self::getChildIds($childId));
        }

        return $ids;
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

            // 检查是否有子分类
            $childCount = ServiceCategory::where('pid', $params['id'])
                ->where('delete_time', null)
                ->count();
            if ($childCount > 0) {
                throw new \Exception('该分类下存在子分类，无法删除');
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
        $list = ServiceCategory::where('delete_time', null)
            ->where('is_show', 1)
            ->order('sort desc, id asc')
            ->field('id, pid, name, level')
            ->select()
            ->toArray();

        return self::buildTree($list);
    }
}
