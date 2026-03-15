<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 用户积分服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\review\Review;
use app\common\model\review\ReviewShareReward;
use app\common\model\user\User;

class UserPointService
{
    /**
     * @notes 增加用户积分
     */
    public static function addPoints(
        int $userId,
        int $points,
        int $changeType,
        string $sourceSn,
        string $remark,
        array $extra = []
    ): bool {
        if ($points <= 0) {
            return true;
        }

        $user = User::lock(true)->find($userId);
        if (!$user) {
            return false;
        }

        $user->user_points = (int)($user->user_points ?? 0) + $points;
        $user->save();

        $log = AccountLogLogic::add(
            $userId,
            $changeType,
            AccountLogEnum::INC,
            $points,
            $sourceSn,
            $remark,
            $extra
        );

        return $log !== false;
    }

    /**
     * @notes 发放评价奖励积分
     */
    public static function grantReviewReward(Review $review): bool
    {
        $rewardPoints = (int)($review->reward_points ?? 0);
        if ($rewardPoints <= 0 || (int)($review->reward_grant_time ?? 0) > 0) {
            return true;
        }

        $result = self::addPoints(
            (int)$review->user_id,
            $rewardPoints,
            AccountLogEnum::UP_INC_REVIEW_REWARD,
            'REVIEW-' . $review->id,
            '评价审核通过奖励积分',
            ['review_id' => (int)$review->id]
        );

        if (!$result) {
            return false;
        }

        $review->save(['reward_grant_time' => time()]);
        return true;
    }

    /**
     * @notes 发放晒单奖励积分
     */
    public static function grantShareReward(ReviewShareReward $reward): bool
    {
        $rewardPoints = (int)($reward->reward_points ?? 0);
        if ($rewardPoints <= 0 || (int)($reward->reward_grant_time ?? 0) > 0) {
            return true;
        }

        $result = self::addPoints(
            (int)$reward->user_id,
            $rewardPoints,
            AccountLogEnum::UP_INC_SHARE_REWARD,
            'SHARE-' . $reward->id,
            '晒单审核通过奖励积分',
            [
                'review_id' => (int)$reward->review_id,
                'share_reward_id' => (int)$reward->id,
                'share_platform' => (string)$reward->share_platform,
            ]
        );

        if (!$result) {
            return false;
        }

        $reward->save(['reward_grant_time' => time()]);
        return true;
    }
}
