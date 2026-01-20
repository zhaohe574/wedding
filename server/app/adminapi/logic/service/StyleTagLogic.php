<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 风格标签管理逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\service;

use app\common\logic\BaseLogic;
use app\common\model\service\StyleTag;
use app\common\model\staff\StaffTag;

/**
 * 风格标签管理逻辑
 * Class StyleTagLogic
 * @package app\adminapi\logic\service
 */
class StyleTagLogic extends BaseLogic
{
    /**
     * @notes 获取标签详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $tag = StyleTag::find($id);
        if (!$tag) {
            return [];
        }
        return $tag->toArray();
    }

    /**
     * @notes 添加标签
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            // 检查是否存在同名标签
            $exists = StyleTag::where('name', $params['name'])
                ->where('type', $params['type'])
                ->where('delete_time', null)
                ->find();
            if ($exists) {
                throw new \Exception('相同类型下已存在该标签');
            }

            StyleTag::create([
                'name' => $params['name'],
                'type' => $params['type'] ?? StyleTag::TYPE_STYLE,
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
     * @notes 编辑标签
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        try {
            $tag = StyleTag::find($params['id']);
            if (!$tag) {
                throw new \Exception('标签不存在');
            }

            // 检查是否存在同名标签
            $exists = StyleTag::where('name', $params['name'])
                ->where('type', $params['type'] ?? $tag->type)
                ->where('id', '<>', $params['id'])
                ->where('delete_time', null)
                ->find();
            if ($exists) {
                throw new \Exception('相同类型下已存在该标签');
            }

            $tag->save([
                'name' => $params['name'],
                'type' => $params['type'] ?? $tag->type,
                'sort' => $params['sort'] ?? $tag->sort,
                'is_show' => $params['is_show'] ?? $tag->is_show,
                'update_time' => time(),
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除标签
     * @param array $params
     * @return bool
     */
    public static function delete(array $params): bool
    {
        try {
            $tag = StyleTag::find($params['id']);
            if (!$tag) {
                throw new \Exception('标签不存在');
            }

            // 检查是否有关联的工作人员
            $staffCount = StaffTag::where('tag_id', $params['id'])->count();
            if ($staffCount > 0) {
                throw new \Exception('该标签已被工作人员使用，无法删除');
            }

            StyleTag::destroy($params['id']);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 修改标签状态
     * @param array $params
     * @return bool
     */
    public static function changeStatus(array $params): bool
    {
        try {
            StyleTag::update([
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
     * @notes 获取所有标签
     * @param array $params
     * @return array
     */
    public static function getAll(array $params = []): array
    {
        $query = StyleTag::where('delete_time', null)
            ->where('is_show', 1);

        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        $list = $query->order('sort desc, id asc')
            ->field('id, name, type')
            ->select()
            ->toArray();

        // 按类型分组
        if (!empty($params['group_by_type'])) {
            $grouped = [];
            foreach ($list as $item) {
                $grouped[$item['type']][] = $item;
            }
            return $grouped;
        }

        return $list;
    }
}
