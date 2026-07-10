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
                'video' => $params['video'] ?? ($params['video_url'] ?? ''),
                'description' => $params['description'] ?? '',
                'shoot_date' => self::normalizeNullableDate($params['shoot_date'] ?? null),
                'location' => $params['location'] ?? '',
                'sort' => $params['sort'] ?? 0,
                'is_show' => $params['is_show'] ?? 1,
                'is_cover' => $params['is_cover'] ?? 0,
                'audit_status' => StaffWork::AUDIT_PENDING,
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
                'video' => $params['video'] ?? ($params['video_url'] ?? $work->video),
                'description' => $params['description'] ?? $work->description,
                'shoot_date' => array_key_exists('shoot_date', $params)
                    ? self::normalizeNullableDate($params['shoot_date'])
                    : $work->shoot_date,
                'location' => $params['location'] ?? $work->location,
                'sort' => $params['sort'] ?? $work->sort,
                'is_show' => $params['is_show'] ?? $work->is_show,
                'is_cover' => $params['is_cover'] ?? $work->is_cover,
                'audit_status' => StaffWork::AUDIT_PENDING,
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
     * @notes 批量删除作品
     * @param array $ids
     * @return array
     */
    public static function batchDelete(array $ids): array
    {
        $successCount = 0;
        $failCount = 0;

        foreach (self::normalizeIds($ids) as $id) {
            if (self::delete(['id' => $id])) {
                $successCount++;
                continue;
            }

            $failCount++;
        }

        return [
            'success_count' => $successCount,
            'fail_count' => $failCount,
        ];
    }

    /**
     * @notes 规范化可空日期字段
     * @param mixed $value
     * @return string|null
     */
    private static function normalizeNullableDate($value): ?string
    {
        $date = trim((string) ($value ?? ''));

        return $date === '' ? null : $date;
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
     * @notes 审核作品
     * @param array $params
     * @return bool
     */
    public static function audit(array $params): bool
    {
        try {
            $work = StaffWork::find($params['id']);
            if (!$work) {
                throw new \Exception('作品不存在');
            }

            if ((int) $work->audit_status !== StaffWork::AUDIT_PENDING) {
                throw new \Exception('仅待审核作品可执行审核操作');
            }

            $work->audit_status = (int) $params['audit_status'];
            $work->update_time = time();
            $work->save();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 批量审核作品
     * @param array $ids
     * @param int $auditStatus
     * @return array
     */
    public static function batchAudit(array $ids, int $auditStatus): array
    {
        $successCount = 0;
        $failCount = 0;

        foreach (self::normalizeIds($ids) as $id) {
            if (self::audit(['id' => $id, 'audit_status' => $auditStatus])) {
                $successCount++;
                continue;
            }

            $failCount++;
        }

        return [
            'success_count' => $successCount,
            'fail_count' => $failCount,
        ];
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

    /**
     * @notes 规范化批量ID
     * @param array $ids
     * @return array
     */
    public static function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));
    }
}
