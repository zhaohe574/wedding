<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 评价点赞模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\review;

use app\common\model\BaseModel;

/**
 * 评价点赞模型
 * Class ReviewLike
 * @package app\common\model\review
 */
class ReviewLike extends BaseModel
{
    protected $name = 'review_like';
    protected $updateTime = false;

    /**
     * @notes 关联评价
     * @return \think\model\relation\BelongsTo
     */
    public function review()
    {
        return $this->belongsTo(Review::class, 'review_id', 'id');
    }

    /**
     * @notes 切换点赞状态
     * @param int $reviewId
     * @param int $userId
     * @return bool 返回是否点赞
     */
    public static function toggle(int $reviewId, int $userId): bool
    {
        $exists = self::where([
            'review_id' => $reviewId,
            'user_id' => $userId,
        ])->find();

        if ($exists) {
            $exists->delete();
            Review::where('id', $reviewId)->where('like_count', '>', 0)->dec('like_count')->update();
            return false;
        } else {
            self::create([
                'review_id' => $reviewId,
                'user_id' => $userId,
            ]);
            Review::where('id', $reviewId)->inc('like_count')->update();
            return true;
        }
    }

    /**
     * @notes 检查用户是否已点赞
     * @param int $reviewId
     * @param int $userId
     * @return bool
     */
    public static function isLiked(int $reviewId, int $userId): bool
    {
        return self::where([
            'review_id' => $reviewId,
            'user_id' => $userId,
        ])->count() > 0;
    }

    /**
     * @notes 批量检查是否点赞
     * @param array $reviewIds
     * @param int $userId
     * @return array
     */
    public static function batchCheck(array $reviewIds, int $userId): array
    {
        if (empty($reviewIds)) {
            return [];
        }

        return self::whereIn('review_id', $reviewIds)
            ->where('user_id', $userId)
            ->column('review_id');
    }
}
