<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 关注模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\dynamic;

use app\common\model\BaseModel;
use app\common\model\user\User;
use app\common\model\staff\Staff;

/**
 * 关注模型
 * Class Follow
 * @package app\common\model\dynamic
 */
class Follow extends BaseModel
{
    protected $name = 'follow';

    // 关注类型
    const TYPE_USER = 1;    // 用户
    const TYPE_STAFF = 2;   // 工作人员

    /**
     * @notes 关注/取消关注
     * @param int $userId
     * @param int $followType
     * @param int $followId
     * @return array [bool $success, string $message, bool $isFollowed]
     */
    public static function toggleFollow(int $userId, int $followType, int $followId): array
    {
        // 不能关注自己
        if ($followType == self::TYPE_USER && $userId == $followId) {
            return [false, '不能关注自己', false];
        }

        // 检查被关注者是否存在
        if ($followType == self::TYPE_USER) {
            $target = User::find($followId);
        } else {
            $target = Staff::find($followId);
        }
        
        if (!$target) {
            return [false, '关注对象不存在', false];
        }

        $exists = self::where('user_id', $userId)
            ->where('follow_type', $followType)
            ->where('follow_id', $followId)
            ->find();

        if ($exists) {
            // 取消关注
            $exists->delete();
            return [true, '取消关注', false];
        } else {
            // 关注
            self::create([
                'user_id' => $userId,
                'follow_type' => $followType,
                'follow_id' => $followId,
                'create_time' => time(),
            ]);
            
            // TODO: 发送通知
            
            return [true, '关注成功', true];
        }
    }

    /**
     * @notes 检查是否已关注
     * @param int $userId
     * @param int $followType
     * @param int $followId
     * @return bool
     */
    public static function isFollowed(int $userId, int $followType, int $followId): bool
    {
        return self::where('user_id', $userId)
            ->where('follow_type', $followType)
            ->where('follow_id', $followId)
            ->count() > 0;
    }

    /**
     * @notes 获取用户关注列表
     * @param int $userId
     * @param int $followType
     * @param array $params
     * @return array
     */
    public static function getFollowList(int $userId, int $followType = 0, array $params = []): array
    {
        $query = self::where('user_id', $userId);
        
        if ($followType > 0) {
            $query->where('follow_type', $followType);
        }

        $list = $query->order('create_time', 'desc')
            ->paginate($params['page_size'] ?? 20)
            ->toArray();

        // 获取关注对象信息
        if (!empty($list['data'])) {
            $userIds = [];
            $staffIds = [];
            foreach ($list['data'] as $item) {
                if ($item['follow_type'] == self::TYPE_USER) {
                    $userIds[] = $item['follow_id'];
                } else {
                    $staffIds[] = $item['follow_id'];
                }
            }

            $users = [];
            $staffs = [];
            if (!empty($userIds)) {
                $users = User::whereIn('id', $userIds)->column('id, nickname, avatar', 'id');
            }
            if (!empty($staffIds)) {
                $staffs = Staff::whereIn('id', $staffIds)->column('id, name, avatar', 'id');
            }

            foreach ($list['data'] as &$item) {
                if ($item['follow_type'] == self::TYPE_USER) {
                    $item['target'] = $users[$item['follow_id']] ?? null;
                } else {
                    $item['target'] = $staffs[$item['follow_id']] ?? null;
                }
            }
        }

        return $list;
    }

    /**
     * @notes 获取粉丝列表
     * @param int $targetId
     * @param int $targetType
     * @param array $params
     * @return array
     */
    public static function getFansList(int $targetId, int $targetType, array $params = []): array
    {
        $query = self::where('follow_type', $targetType)
            ->where('follow_id', $targetId);

        $list = $query->order('create_time', 'desc')
            ->paginate($params['page_size'] ?? 20)
            ->toArray();

        // 获取粉丝用户信息
        if (!empty($list['data'])) {
            $userIds = array_column($list['data'], 'user_id');
            $users = User::whereIn('id', $userIds)->column('id, nickname, avatar', 'id');

            foreach ($list['data'] as &$item) {
                $item['user'] = $users[$item['user_id']] ?? null;
            }
        }

        return $list;
    }

    /**
     * @notes 获取关注数量
     * @param int $userId
     * @return int
     */
    public static function getFollowCount(int $userId): int
    {
        return self::where('user_id', $userId)->count();
    }

    /**
     * @notes 获取粉丝数量
     * @param int $targetId
     * @param int $targetType
     * @return int
     */
    public static function getFansCount(int $targetId, int $targetType): int
    {
        return self::where('follow_type', $targetType)
            ->where('follow_id', $targetId)
            ->count();
    }
}
