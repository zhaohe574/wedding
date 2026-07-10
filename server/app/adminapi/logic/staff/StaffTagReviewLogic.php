<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员标签审核逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\staff;

use app\common\logic\BaseLogic;
use app\common\model\staff\StaffTagApply;
use app\common\service\StaffTagReviewService;

class StaffTagReviewLogic extends BaseLogic
{
    public static function detail(int $id): array
    {
        $apply = StaffTagApply::with([
            'staff' => function ($query) {
                $query->field('id, name, avatar, mobile_full, category_id');
            }
        ])->find($id);

        if (!$apply) {
            self::setError('标签申请不存在');
            return [];
        }

        $data = StaffTagReviewService::appendApplyDisplay($apply->toArray());
        $data['staff_name'] = $data['staff']['name'] ?? '';
        $data['staff_avatar'] = $data['staff']['avatar'] ?? '';
        $data['staff_mobile'] = $data['staff']['mobile_full'] ?? '';
        $data['category_id'] = (int) ($data['staff']['category_id'] ?? 0);
        $data['category_name'] = $data['staff']['category_name'] ?? '';

        return $data;
    }

    public static function approve(int $id, int $adminId): bool
    {
        try {
            return StaffTagReviewService::approve($id, $adminId);
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function reject(int $id, int $adminId, string $rejectReason): bool
    {
        try {
            return StaffTagReviewService::reject($id, $adminId, $rejectReason);
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 批量审核通过标签申请
     */
    public static function batchApprove(array $ids, int $adminId): array
    {
        $successCount = 0;
        $failCount = 0;

        foreach (self::normalizeIds($ids) as $id) {
            if (self::approve($id, $adminId)) {
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
     * @notes 批量审核拒绝标签申请
     */
    public static function batchReject(array $ids, int $adminId, string $rejectReason): array
    {
        $successCount = 0;
        $failCount = 0;

        foreach (self::normalizeIds($ids) as $id) {
            if (self::reject($id, $adminId, $rejectReason)) {
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
     * @notes 规范化批量ID
     */
    public static function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));
    }
}
