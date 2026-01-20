<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 动态点赞模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\dynamic;

use app\common\model\BaseModel;

/**
 * 动态点赞模型
 * Class DynamicLike
 * @package app\common\model\dynamic
 */
class DynamicLike extends BaseModel
{
    protected $name = 'dynamic_like';

    // 目标类型
    const TARGET_DYNAMIC = 1;   // 动态
    const TARGET_COMMENT = 2;   // 评论

    /**
     * @notes 点赞/取消点赞
     * @param int $userId
     * @param int $targetType
     * @param int $targetId
     * @return array [bool $success, string $message, bool $isLiked]
     */
    public static function toggleLike(int $userId, int $targetType, int $targetId): array
    {
        $exists = self::where('user_id', $userId)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->find();

        if ($exists) {
            // 取消点赞
            $exists->delete();
            self::updateLikeCount($targetType, $targetId, -1);
            return [true, '取消点赞', false];
        } else {
            // 点赞
            self::create([
                'user_id' => $userId,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'create_time' => time(),
            ]);
            self::updateLikeCount($targetType, $targetId, 1);
            
            // TODO: 发送通知
            
            return [true, '点赞成功', true];
        }
    }

    /**
     * @notes 更新点赞数
     * @param int $targetType
     * @param int $targetId
     * @param int $delta
     */
    protected static function updateLikeCount(int $targetType, int $targetId, int $delta): void
    {
        if ($targetType == self::TARGET_DYNAMIC) {
            if ($delta > 0) {
                Dynamic::where('id', $targetId)->inc('like_count')->update();
            } else {
                Dynamic::where('id', $targetId)->dec('like_count')->update();
            }
        } elseif ($targetType == self::TARGET_COMMENT) {
            if ($delta > 0) {
                DynamicComment::where('id', $targetId)->inc('like_count')->update();
            } else {
                DynamicComment::where('id', $targetId)->dec('like_count')->update();
            }
        }
    }

    /**
     * @notes 检查是否已点赞
     * @param int $userId
     * @param int $targetType
     * @param int $targetId
     * @return bool
     */
    public static function isLiked(int $userId, int $targetType, int $targetId): bool
    {
        return self::where('user_id', $userId)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->count() > 0;
    }

    /**
     * @notes 获取用户点赞的动态ID列表
     * @param int $userId
     * @param array $dynamicIds
     * @return array
     */
    public static function getUserLikedDynamicIds(int $userId, array $dynamicIds): array
    {
        return self::where('user_id', $userId)
            ->where('target_type', self::TARGET_DYNAMIC)
            ->whereIn('target_id', $dynamicIds)
            ->column('target_id');
    }
}
