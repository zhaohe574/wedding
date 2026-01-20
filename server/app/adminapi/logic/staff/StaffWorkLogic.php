<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员作品管理逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\staff;

use app\common\logic\BaseLogic;
use app\common\model\staff\StaffWork;
use app\common\model\staff\Staff;

/**
 * 工作人员作品管理逻辑
 * Class StaffWorkLogic
 * @package app\adminapi\logic\staff
 */
class StaffWorkLogic extends BaseLogic
{
    /**
     * @notes 获取作品详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $work = StaffWork::with(['staff'])->find($id);
        if (!$work) {
            return [];
        }
        return $work->toArray();
    }

    /**
     * @notes 添加作品
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            // 验证工作人员是否存在
            $staff = Staff::find($params['staff_id']);
            if (!$staff) {
                throw new \Exception('工作人员不存在');
            }

            StaffWork::create([
                'staff_id' => $params['staff_id'],
                'title' => $params['title'],
                'type' => $params['type'] ?? StaffWork::TYPE_IMAGE,
                'cover' => $params['cover'] ?? '',
                'images' => $params['images'] ?? [],
                'video_url' => $params['video_url'] ?? '',
                'description' => $params['description'] ?? '',
                'sort' => $params['sort'] ?? 0,
                'is_show' => $params['is_show'] ?? 1,
                'is_cover' => $params['is_cover'] ?? 0,
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
     * @notes 编辑作品
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        try {
            $work = StaffWork::find($params['id']);
            if (!$work) {
                throw new \Exception('作品不存在');
            }

            $work->save([
                'title' => $params['title'],
                'type' => $params['type'] ?? $work->type,
                'cover' => $params['cover'] ?? $work->cover,
                'images' => $params['images'] ?? $work->images,
                'video_url' => $params['video_url'] ?? $work->video_url,
                'description' => $params['description'] ?? $work->description,
                'sort' => $params['sort'] ?? $work->sort,
                'is_show' => $params['is_show'] ?? $work->is_show,
                'is_cover' => $params['is_cover'] ?? $work->is_cover,
                'update_time' => time(),
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除作品
     * @param array $params
     * @return bool
     */
    public static function delete(array $params): bool
    {
        return StaffWork::destroy($params['id']);
    }

    /**
     * @notes 修改作品状态
     * @param array $params
     * @return bool
     */
    public static function changeStatus(array $params): bool
    {
        try {
            StaffWork::update([
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
     * @notes 设为封面
     * @param int $id
     * @return bool
     */
    public static function setCover(int $id): bool
    {
        try {
            $work = StaffWork::find($id);
            if (!$work) {
                throw new \Exception('作品不存在');
            }

            // 取消该工作人员其他作品的封面状态
            StaffWork::where('staff_id', $work->staff_id)
                ->where('id', '<>', $id)
                ->update(['is_cover' => 0, 'update_time' => time()]);

            // 设置当前作品为封面
            $work->save([
                'is_cover' => 1,
                'update_time' => time(),
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
