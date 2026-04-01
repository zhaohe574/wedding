<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员标签审核服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\service\StyleTag;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffTag;
use app\common\model\staff\StaffTagApply;

class StaffTagReviewService
{
    public static function isReviewEnabled(): bool
    {
        return self::getReviewEnabledValue() === 1;
    }

    public static function getReviewEnabledValue(): int
    {
        return (int) ConfigService::get('feature_switch', 'staff_tag_review_enabled', 0);
    }

    public static function handleSelfTagUpdate(
        int $staffId,
        int $categoryId,
        array $tagIds,
        array $context = []
    ): array {
        $normalizedTagIds = self::normalizeTagIds($tagIds);
        self::validateTagIdsForCategory($normalizedTagIds, $categoryId);

        $currentTagIds = self::getEffectiveTagIds($staffId);
        $pendingApply = self::getPendingApply($staffId);

        if ($normalizedTagIds === $currentTagIds) {
            if ($pendingApply) {
                $pendingApply->delete();
            }

            return [
                'action' => 'applied',
                'message' => '标签未变化',
            ];
        }

        if (!self::isReviewEnabled()) {
            StaffTag::setTags($staffId, $normalizedTagIds);
            self::clearPendingApply($staffId);

            return [
                'action' => 'applied',
                'message' => '标签修改已生效',
            ];
        }

        $payload = [
            'current_tag_ids' => self::encodeTagIds($currentTagIds),
            'apply_tag_ids' => self::encodeTagIds($normalizedTagIds),
            'source' => (int) ($context['source'] ?? StaffTagApply::SOURCE_UNIAPP),
            'status' => StaffTagApply::STATUS_PENDING,
            'reject_reason' => '',
            'submit_user_id' => (int) ($context['submit_user_id'] ?? 0),
            'submit_admin_id' => (int) ($context['submit_admin_id'] ?? 0),
            'audit_admin_id' => 0,
            'audit_time' => 0,
            'update_time' => time(),
        ];

        if ($pendingApply) {
            $pendingApply->save(array_merge($payload, [
                'create_time' => time(),
            ]));
        } else {
            StaffTagApply::create(array_merge($payload, [
                'staff_id' => $staffId,
                'create_time' => time(),
            ]));
        }

        return [
            'action' => 'pending',
            'message' => '标签修改已提交审核',
        ];
    }

    public static function syncEffectiveTags(int $staffId, int $categoryId, array $tagIds, bool $clearPending = true): void
    {
        $normalizedTagIds = self::normalizeTagIds($tagIds);
        self::validateTagIdsForCategory($normalizedTagIds, $categoryId);
        StaffTag::setTags($staffId, $normalizedTagIds);

        if ($clearPending) {
            self::clearPendingApply($staffId);
        }
    }

    public static function approve(int $applyId, int $adminId): bool
    {
        $apply = StaffTagApply::find($applyId);
        if (!$apply) {
            throw new \RuntimeException('标签申请不存在');
        }
        if ((int) $apply->status !== StaffTagApply::STATUS_PENDING) {
            throw new \RuntimeException('仅支持审核待审核申请');
        }

        $staff = Staff::find((int) $apply->staff_id);
        if (!$staff) {
            throw new \RuntimeException('服务人员不存在');
        }

        $applyTagIds = self::decodeTagIds((string) $apply->apply_tag_ids);
        self::syncEffectiveTags((int) $staff->id, (int) $staff->category_id, $applyTagIds, false);

        $apply->save([
            'status' => StaffTagApply::STATUS_APPROVED,
            'reject_reason' => '',
            'audit_admin_id' => $adminId,
            'audit_time' => time(),
            'update_time' => time(),
        ]);

        return true;
    }

    public static function reject(int $applyId, int $adminId, string $rejectReason): bool
    {
        $apply = StaffTagApply::find($applyId);
        if (!$apply) {
            throw new \RuntimeException('标签申请不存在');
        }
        if ((int) $apply->status !== StaffTagApply::STATUS_PENDING) {
            throw new \RuntimeException('仅支持审核待审核申请');
        }

        $apply->save([
            'status' => StaffTagApply::STATUS_REJECTED,
            'reject_reason' => trim($rejectReason),
            'audit_admin_id' => $adminId,
            'audit_time' => time(),
            'update_time' => time(),
        ]);

        return true;
    }

    public static function clearPendingApply(int $staffId): void
    {
        StaffTagApply::where('staff_id', $staffId)
            ->where('status', StaffTagApply::STATUS_PENDING)
            ->delete();
    }

    public static function getProfileTagState(int $staffId): array
    {
        $currentTagIds = self::getEffectiveTagIds($staffId);
        $currentTagNames = self::getTagNames($currentTagIds);
        $pendingApply = self::getPendingApply($staffId);
        $latestRejectedApply = null;
        if (!$pendingApply) {
            $latestApply = self::getLatestApply($staffId);
            if ($latestApply && (int) $latestApply->status === StaffTagApply::STATUS_REJECTED) {
                $latestRejectedApply = $latestApply;
            }
        }
        $displayApply = $pendingApply ?: $latestRejectedApply;

        $pendingTagIds = $pendingApply
            ? self::decodeTagIds((string) $pendingApply->apply_tag_ids)
            : [];

        return [
            'tag_ids' => $currentTagIds,
            'tag_names' => $currentTagNames,
            'pending_tag_ids' => $pendingTagIds,
            'pending_tag_names' => self::getTagNames($pendingTagIds),
            'tag_apply_status' => $displayApply ? (int) $displayApply->status : null,
            'tag_apply_status_desc' => $displayApply ? StaffTagApply::getStatusDesc((int) $displayApply->status) : '',
            'tag_apply_reject_reason' => $displayApply ? (string) $displayApply->reject_reason : '',
            'staff_tag_review_enabled' => self::getReviewEnabledValue(),
        ];
    }

    public static function appendApplyDisplay(array $row): array
    {
        $currentTagIds = self::decodeTagIds($row['current_tag_ids'] ?? []);
        $applyTagIds = self::decodeTagIds($row['apply_tag_ids'] ?? []);

        $row['current_tag_ids'] = $currentTagIds;
        $row['apply_tag_ids'] = $applyTagIds;
        $row['current_tag_names'] = self::getTagNames($currentTagIds);
        $row['apply_tag_names'] = self::getTagNames($applyTagIds);
        $row['status_desc'] = StaffTagApply::getStatusDesc((int) ($row['status'] ?? 0));
        $row['source_desc'] = StaffTagApply::getSourceDesc((int) ($row['source'] ?? 0));

        return $row;
    }

    public static function normalizeTagIds($tagIds): array
    {
        if (!is_array($tagIds)) {
            return [];
        }

        $normalized = [];
        foreach ($tagIds as $tagId) {
            $value = (int) $tagId;
            if ($value <= 0 || in_array($value, $normalized, true)) {
                continue;
            }
            $normalized[] = $value;
        }

        return $normalized;
    }

    public static function decodeTagIds($value): array
    {
        if (is_array($value)) {
            return self::normalizeTagIds($value);
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return self::normalizeTagIds($decoded);
    }

    public static function encodeTagIds(array $tagIds): string
    {
        return json_encode(self::normalizeTagIds($tagIds), JSON_UNESCAPED_UNICODE);
    }

    public static function getEffectiveTagIds(int $staffId): array
    {
        return self::normalizeTagIds(StaffTag::getTagIds($staffId));
    }

    public static function validateTagIdsForCategory(array $tagIds, int $categoryId): void
    {
        if (empty($tagIds)) {
            return;
        }
        if ($categoryId <= 0) {
            throw new \RuntimeException('请选择服务分类后再配置标签');
        }

        $tags = StyleTag::whereIn('id', $tagIds)
            ->whereNull('delete_time')
            ->where('is_show', 1)
            ->field('id, category_id')
            ->select()
            ->toArray();

        if (count($tags) !== count($tagIds)) {
            throw new \RuntimeException('存在无效标签，请刷新后重试');
        }

        $tagMap = array_column($tags, null, 'id');
        foreach ($tagIds as $tagId) {
            $tag = $tagMap[$tagId] ?? null;
            if (!$tag) {
                throw new \RuntimeException('存在无效标签，请刷新后重试');
            }

            $tagCategoryId = (int) ($tag['category_id'] ?? 0);
            if ($tagCategoryId !== 0 && $tagCategoryId !== $categoryId) {
                throw new \RuntimeException('标签与当前服务分类不匹配');
            }
        }
    }

    public static function getPendingApply(int $staffId): ?StaffTagApply
    {
        return StaffTagApply::where('staff_id', $staffId)
            ->where('status', StaffTagApply::STATUS_PENDING)
            ->order('id', 'desc')
            ->find();
    }

    public static function getLatestApply(int $staffId): ?StaffTagApply
    {
        return StaffTagApply::where('staff_id', $staffId)
            ->order('id', 'desc')
            ->find();
    }

    public static function getTagNames(array $tagIds): array
    {
        if (empty($tagIds)) {
            return [];
        }

        $rows = StyleTag::whereIn('id', $tagIds)
            ->whereNull('delete_time')
            ->field('id, name')
            ->select()
            ->toArray();

        $nameMap = array_column($rows, 'name', 'id');
        $result = [];
        foreach ($tagIds as $tagId) {
            if (!isset($nameMap[$tagId])) {
                continue;
            }
            $result[] = (string) $nameMap[$tagId];
        }

        return $result;
    }
}
