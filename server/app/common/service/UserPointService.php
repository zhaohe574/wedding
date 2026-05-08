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
        return true;
    }

    /**
     * @notes 发放晒单奖励积分
     */
    public static function grantShareReward(ReviewShareReward $reward): bool
    {
        return true;
    }
}
