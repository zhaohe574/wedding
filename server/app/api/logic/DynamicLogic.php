<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端动态逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\dynamic\Dynamic;
use app\common\model\dynamic\DynamicComment;
use app\common\model\dynamic\DynamicLike;
use app\common\model\dynamic\DynamicCollect;
use app\common\model\dynamic\Follow;
use app\common\model\notification\Notification;

/**
 * 小程序端动态逻辑
 * Class DynamicLogic
 * @package app\api\logic
 */
class DynamicLogic extends BaseLogic
{
    /**
     * @notes 获取动态列表
     * @param array $params
     * @param int $userId
     * @return array
     */
    public static function getDynamicList(array $params, int $userId = 0): array
    {
        return Dynamic::getList($params, $userId);
    }

    /**
     * @notes 获取动态详情
     * @param int $dynamicId
     * @param int $userId
     * @return array|null
     */
    public static function getDynamicDetail(int $dynamicId, int $userId = 0): ?array
    {
        $dynamic = Dynamic::with(['user' => function ($q) {
                $q->field('id, nickname, avatar');
            }, 'staff' => function ($q) {
                $q->field('id, name, avatar');
            }])
            ->find($dynamicId);

        if (!$dynamic || $dynamic->status != Dynamic::STATUS_PUBLISHED) {
            return null;
        }

        // 增加浏览量
        Dynamic::incrementView($dynamicId);

        $data = $dynamic->toArray();
        $data['type_desc'] = self::getTypeDesc($dynamic->dynamic_type);

        // 处理发布者信息
        if ($dynamic->user_type == Dynamic::USER_TYPE_OFFICIAL) {
            // 官方动态
            $data['user_nickname'] = '官方';
            $data['user_avatar'] = '';
        } elseif ($dynamic->user_type == Dynamic::USER_TYPE_STAFF && !empty($data['staff'])) {
            // 工作人员动态
            $data['user_nickname'] = $data['staff']['name'];
            $data['user_avatar'] = $data['staff']['avatar'];
        } elseif (!empty($data['user'])) {
            // 普通用户动态
            $data['user_nickname'] = $data['user']['nickname'];
            $data['user_avatar'] = $data['user']['avatar'];
        } else {
            $data['user_nickname'] = '匿名用户';
            $data['user_avatar'] = '';
        }

        // 移除原始的 user 和 staff 对象
        unset($data['user'], $data['staff']);

        // 是否点赞/收藏
        if ($userId > 0) {
            $data['is_liked'] = DynamicLike::isLiked($userId, DynamicLike::TARGET_DYNAMIC, $dynamicId);
            $data['is_collected'] = DynamicCollect::isCollected($userId, $dynamicId);
            
            // 是否关注
            if ($dynamic->user_type == Dynamic::USER_TYPE_USER) {
                $data['is_followed'] = Follow::where('user_id', $userId)
                    ->where('follow_type', Follow::TYPE_USER)
                    ->where('follow_id', $dynamic->user_id)
                    ->count() > 0;
            } elseif ($dynamic->user_type == Dynamic::USER_TYPE_STAFF) {
                $data['is_followed'] = Follow::where('user_id', $userId)
                    ->where('follow_type', Follow::TYPE_STAFF)
                    ->where('follow_id', $dynamic->staff_id)
                    ->count() > 0;
            } else {
                $data['is_followed'] = false;
            }
        } else {
            $data['is_liked'] = false;
            $data['is_collected'] = false;
            $data['is_followed'] = false;
        }

        return $data;
    }

    /**
     * @notes 发布动态
     * @param array $params
     * @return array
     */
    public static function publishDynamic(array $params): array
    {
        [$success, $message, $dynamic] = Dynamic::publish(
            $params['user_id'],
            Dynamic::USER_TYPE_USER,
            $params
        );

        if ($success && $dynamic) {
            return ['success' => true, 'message' => $message, 'dynamic_id' => $dynamic->id];
        }
        return ['success' => false, 'message' => $message];
    }

    /**
     * @notes 删除动态
     * @param int $dynamicId
     * @param int $userId
     * @return array
     */
    public static function deleteDynamic(int $dynamicId, int $userId): array
    {
        $dynamic = Dynamic::where('user_id', $userId)->find($dynamicId);
        if (!$dynamic) {
            return ['success' => false, 'message' => '动态不存在或无权删除'];
        }

        $dynamic->delete();
        return ['success' => true, 'message' => '删除成功'];
    }

    /**
     * @notes 点赞/取消点赞
     * @param int $dynamicId
     * @param int $userId
     * @return array
     */
    public static function toggleLike(int $dynamicId, int $userId): array
    {
        [$success, $message, $isLiked] = DynamicLike::toggleLike($userId, DynamicLike::TARGET_DYNAMIC, $dynamicId);
        return ['success' => $success, 'message' => $message, 'is_liked' => $isLiked];
    }

    /**
     * @notes 收藏/取消收藏
     * @param int $dynamicId
     * @param int $userId
     * @return array
     */
    public static function toggleCollect(int $dynamicId, int $userId): array
    {
        [$success, $message, $isCollected] = DynamicCollect::toggleCollect($userId, $dynamicId);
        return ['success' => $success, 'message' => $message, 'is_collected' => $isCollected];
    }

    /**
     * @notes 获取评论列表
     * @param int $dynamicId
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function getCommentList(int $dynamicId, int $userId = 0, array $params = []): array
    {
        return DynamicComment::getCommentList($dynamicId, $userId, $params);
    }

    /**
     * @notes 发表评论
     * @param int $dynamicId
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function addComment(int $dynamicId, int $userId, array $params): array
    {
        [$success, $message, $comment] = DynamicComment::addComment(
            $dynamicId,
            $userId,
            $params['content'],
            (int)($params['parent_id'] ?? 0),
            (int)($params['reply_user_id'] ?? 0),
            $params['images'] ?? []
        );

        if ($success && $comment) {
            return ['success' => true, 'message' => $message, 'comment_id' => $comment->id];
        }
        return ['success' => false, 'message' => $message];
    }

    /**
     * @notes 删除评论
     * @param int $commentId
     * @param int $userId
     * @return array
     */
    public static function deleteComment(int $commentId, int $userId): array
    {
        return DynamicComment::deleteComment($commentId, $userId);
    }

    /**
     * @notes 评论点赞
     * @param int $commentId
     * @param int $userId
     * @return array
     */
    public static function toggleCommentLike(int $commentId, int $userId): array
    {
        [$success, $message, $isLiked] = DynamicLike::toggleLike($userId, DynamicLike::TARGET_COMMENT, $commentId);
        return ['success' => $success, 'message' => $message, 'is_liked' => $isLiked];
    }

    /**
     * @notes 获取用户动态
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function getUserDynamics(int $userId, array $params): array
    {
        $params['user_id'] = $userId;
        return Dynamic::getList($params, $userId);
    }

    /**
     * @notes 获取用户收藏
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function getUserCollections(int $userId, array $params): array
    {
        return DynamicCollect::getUserCollections($userId, $params);
    }

    /**
     * @notes 获取用户点赞
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function getUserLikes(int $userId, array $params): array
    {
        $likedIds = DynamicLike::where('user_id', $userId)
            ->where('target_type', DynamicLike::TARGET_DYNAMIC)
            ->order('id', 'desc')
            ->column('target_id');

        if (empty($likedIds)) {
            return ['data' => [], 'total' => 0];
        }

        $list = Dynamic::whereIn('id', $likedIds)
            ->where('status', Dynamic::STATUS_PUBLISHED)
            ->with(['user' => function ($q) {
                $q->field('id, nickname, avatar');
            }])
            ->paginate($params['page_size'] ?? 10)
            ->toArray();

        return $list;
    }

    /**
     * @notes 获取热门标签
     * @return array
     */
    public static function getHotTags(): array
    {
        // 从已发布的动态中统计标签
        $dynamics = Dynamic::where('status', Dynamic::STATUS_PUBLISHED)
            ->whereNotNull('tags')
            ->where('tags', '<>', '')
            ->order('create_time', 'desc')
            ->limit(1000)
            ->column('tags');

        $tagCounts = [];
        foreach ($dynamics as $tagStr) {
            $tags = explode(',', $tagStr);
            foreach ($tags as $tag) {
                $tag = trim($tag);
                if (!empty($tag)) {
                    $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                }
            }
        }

        arsort($tagCounts);
        $hotTags = array_slice(array_keys($tagCounts), 0, 20);

        return $hotTags;
    }

    /**
     * @notes 关注/取消关注
     * @param int $userId
     * @param int $followType
     * @param int $followId
     * @return array
     */
    public static function toggleFollow(int $userId, int $followType, int $followId): array
    {
        [$success, $message, $isFollowed] = Follow::toggleFollow($userId, $followType, $followId);
        return ['success' => $success, 'message' => $message, 'is_followed' => $isFollowed];
    }

    /**
     * @notes 获取关注列表
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function getFollowingList(int $userId, array $params): array
    {
        return Follow::getFollowList($userId, $params['follow_type'] ?? 0, $params);
    }

    /**
     * @notes 获取粉丝列表
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function getFansList(int $userId, array $params): array
    {
        return Follow::getFansList($userId, Follow::TYPE_USER, $params);
    }

    /**
     * @notes 获取通知列表
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function getNotifications(int $userId, array $params): array
    {
        $query = Notification::where('user_id', $userId);

        if (!empty($params['type'])) {
            $query->where('notify_type', $params['type']);
        }

        $list = $query->order('id', 'desc')
            ->paginate($params['page_size'] ?? 20)
            ->toArray();

        return $list;
    }

    /**
     * @notes 获取未读消息数量
     * @param int $userId
     * @return array
     */
    public static function getUnreadCount(int $userId): array
    {
        return Notification::getUnreadCountByType($userId);
    }

    /**
     * @notes 标记消息已读
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public static function markNotificationRead(int $notificationId, int $userId): bool
    {
        return Notification::markRead($notificationId, $userId);
    }

    /**
     * @notes 标记全部已读
     * @param int $userId
     * @param int $type
     * @return bool
     */
    public static function markAllNotificationsRead(int $userId, int $type = 0): bool
    {
        return Notification::markAllRead($userId, $type) >= 0;
    }

    /**
     * @notes 获取类型描述
     */
    protected static function getTypeDesc(int $type): string
    {
        $map = [
            Dynamic::TYPE_IMAGE_TEXT => '图文',
            Dynamic::TYPE_VIDEO => '视频',
            Dynamic::TYPE_CASE => '案例',
            Dynamic::TYPE_ACTIVITY => '活动',
        ];
        return $map[$type] ?? '未知';
    }
}
