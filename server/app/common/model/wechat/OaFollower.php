<?php

declare(strict_types=1);

namespace app\common\model\wechat;

use app\common\model\BaseModel;

/**
 * 公众号粉丝与系统用户的绑定状态。
 */
class OaFollower extends BaseModel
{
    protected $name = 'wechat_oa_follower';

    public const STATUS_UNFOLLOWED = 0;
    public const STATUS_FOLLOWED = 1;

    public static function findByUserId(int $userId): ?self
    {
        return self::where('user_id', $userId)->find();
    }

    public static function findByOpenid(string $openid): ?self
    {
        return self::where('openid', trim($openid))->find();
    }

    public static function upsertFollowed(
        int $userId,
        string $openid,
        string $unionid = '',
        string $source = 'subscribe'
    ): self {
        $openid = trim($openid);
        $unionid = trim($unionid);
        $now = time();
        $follower = self::findByOpenid($openid) ?: self::findByUserId($userId) ?: new self();
        $wasFollowed = (int) ($follower->follow_status ?? self::STATUS_UNFOLLOWED) === self::STATUS_FOLLOWED;
        $follower->user_id = $userId;
        $follower->openid = $openid;
        $follower->unionid = $unionid !== '' ? $unionid : (string) ($follower->unionid ?? '');
        $follower->follow_status = self::STATUS_FOLLOWED;
        $follower->source = $source;
        if (!$wasFollowed) {
            $follower->followed_time = $now;
        }
        $follower->unfollowed_time = 0;
        $follower->last_event_time = $now;
        $follower->update_time = $now;
        if (!$follower->id) {
            $follower->create_time = $now;
        }
        $follower->save();
        return $follower;
    }

    public static function markUnfollowed(string $openid): bool
    {
        $follower = self::findByOpenid($openid);
        if (!$follower) {
            return false;
        }

        $now = time();
        if ((int) $follower->follow_status === self::STATUS_FOLLOWED) {
            $follower->unfollowed_time = $now;
        }
        $follower->follow_status = self::STATUS_UNFOLLOWED;
        $follower->last_event_time = $now;
        $follower->update_time = $now;
        return $follower->save() !== false;
    }

    public static function isFollowed(int $userId): bool
    {
        return (bool) self::where('user_id', $userId)
            ->where('follow_status', self::STATUS_FOLLOWED)
            ->where('openid', '<>', '')
            ->find();
    }
}
