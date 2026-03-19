<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 互动站内消息服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\dynamic\Dynamic;
use app\common\model\dynamic\DynamicComment;
use app\common\model\dynamic\DynamicLike;
use app\common\model\dynamic\Follow;
use app\common\model\notification\Notification;
use app\common\model\staff\Staff;
use app\common\model\user\User;

/**
 * 统一处理关注、点赞、评论等互动类站内消息。
 */
class InteractNotificationService
{
    /**
     * 关注成功后通知被关注对象。
     */
    public static function notifyOnFollow(int $actorUserId, int $followType, int $followId): void
    {
        $actorName = self::getUserDisplayName($actorUserId);
        if ($followType === Follow::TYPE_STAFF) {
            $staff = Staff::field('id, user_id, name')->find($followId);
            if (!$staff || (int)$staff->user_id <= 0 || (int)$staff->user_id === $actorUserId) {
                return;
            }

            StationNotificationService::send(
                (int)$staff->user_id,
                Notification::TYPE_INTERACT,
                '您有新的关注',
                sprintf('%s关注了您，快去看看吧。', $actorName),
                StationNotificationService::TARGET_STAFF_DETAIL,
                (int)$staff->id,
                $actorUserId
            );
            return;
        }

        if ($followType === Follow::TYPE_USER && $followId > 0 && $followId !== $actorUserId) {
            StationNotificationService::send(
                $followId,
                Notification::TYPE_INTERACT,
                '您有新的关注',
                sprintf('%s关注了您。', $actorName),
                '',
                0,
                $actorUserId
            );
        }
    }

    /**
     * 点赞动态后通知动态作者。
     */
    public static function notifyOnDynamicLiked(int $actorUserId, int $dynamicId): void
    {
        $dynamic = Dynamic::field('id, user_id, user_type, staff_id')->find($dynamicId);
        if (!$dynamic) {
            return;
        }

        $recipientUserId = self::resolveDynamicOwnerUserId($dynamic);
        if ($recipientUserId <= 0 || $recipientUserId === $actorUserId) {
            return;
        }

        StationNotificationService::send(
            $recipientUserId,
            Notification::TYPE_INTERACT,
            '有人赞了您的动态',
            sprintf('%s赞了您的动态，快去看看互动吧。', self::getUserDisplayName($actorUserId)),
            StationNotificationService::TARGET_DYNAMIC_DETAIL,
            $dynamicId,
            $actorUserId
        );
    }

    /**
     * 点赞评论后通知评论作者。
     */
    public static function notifyOnCommentLiked(int $actorUserId, int $commentId): void
    {
        $comment = DynamicComment::field('id, user_id, dynamic_id')->find($commentId);
        if (!$comment || (int)$comment->user_id <= 0 || (int)$comment->user_id === $actorUserId) {
            return;
        }

        StationNotificationService::send(
            (int)$comment->user_id,
            Notification::TYPE_INTERACT,
            '有人赞了您的评论',
            sprintf('%s赞了您的评论。', self::getUserDisplayName($actorUserId)),
            StationNotificationService::TARGET_DYNAMIC_DETAIL,
            (int)$comment->dynamic_id,
            $actorUserId
        );
    }

    /**
     * 评论通过展示后通知动态作者 / 被回复人。
     */
    public static function notifyOnCommentVisible(int $commentId): void
    {
        $comment = DynamicComment::field('id, dynamic_id, user_id, reply_user_id, parent_id, content')
            ->find($commentId);
        if (!$comment) {
            return;
        }

        $dynamic = Dynamic::field('id, user_id, user_type, staff_id')->find((int)$comment->dynamic_id);
        if (!$dynamic) {
            return;
        }

        $actorUserId = (int)$comment->user_id;
        $actorName = self::getUserDisplayName($actorUserId);
        $contentSnippet = self::buildContentSnippet((string)$comment->content);

        $recipients = [];
        $dynamicOwnerUserId = self::resolveDynamicOwnerUserId($dynamic);
        if ($dynamicOwnerUserId > 0 && $dynamicOwnerUserId !== $actorUserId) {
            $recipients[$dynamicOwnerUserId] = [
                'title' => '您的动态收到新评论',
                'content' => sprintf('%s评论了您的动态：%s', $actorName, $contentSnippet),
            ];
        }

        $replyUserId = (int)$comment->reply_user_id;
        if ($replyUserId > 0 && $replyUserId !== $actorUserId) {
            $recipients[$replyUserId] = [
                'title' => '有人回复了您的评论',
                'content' => sprintf('%s回复了您：%s', $actorName, $contentSnippet),
            ];
        }

        foreach ($recipients as $recipientUserId => $payload) {
            StationNotificationService::send(
                (int)$recipientUserId,
                Notification::TYPE_INTERACT,
                (string)$payload['title'],
                (string)$payload['content'],
                StationNotificationService::TARGET_DYNAMIC_DETAIL,
                (int)$comment->dynamic_id,
                $actorUserId
            );
        }
    }

    /**
     * 评论审核结果通知评论作者。
     */
    public static function notifyCommentAuditResult(int $commentId, bool $approved, string $remark = ''): void
    {
        $comment = DynamicComment::field('id, dynamic_id, user_id, content')->find($commentId);
        if (!$comment || (int)$comment->user_id <= 0) {
            return;
        }

        $title = $approved ? '您的评论已通过审核' : '您的评论未通过审核';
        $content = $approved
            ? sprintf('您发布的评论已通过审核：%s', self::buildContentSnippet((string)$comment->content))
            : sprintf(
                '您发布的评论未通过审核%s',
                $remark !== '' ? '，原因：' . $remark : '，请修改后重试。'
            );

        StationNotificationService::send(
            (int)$comment->user_id,
            Notification::TYPE_INTERACT,
            $title,
            $content,
            StationNotificationService::TARGET_DYNAMIC_DETAIL,
            (int)$comment->dynamic_id
        );
    }

    /**
     * 获取互动发起者昵称。
     */
    private static function getUserDisplayName(int $userId): string
    {
        if ($userId <= 0) {
            return '有用户';
        }

        $user = User::field('nickname')->find($userId);
        $nickname = trim((string)($user->nickname ?? ''));
        return $nickname !== '' ? $nickname : '有用户';
    }

    /**
     * 解析动态作者对应的通知用户ID。
     */
    private static function resolveDynamicOwnerUserId(Dynamic $dynamic): int
    {
        if ((int)$dynamic->user_type === Dynamic::USER_TYPE_USER) {
            return (int)$dynamic->user_id;
        }

        if ((int)$dynamic->user_type === Dynamic::USER_TYPE_STAFF) {
            $staff = Staff::field('user_id')->find((int)$dynamic->staff_id);
            return (int)($staff->user_id ?? 0);
        }

        return 0;
    }

    /**
     * 截断互动文案中的评论摘要。
     */
    private static function buildContentSnippet(string $content): string
    {
        $content = trim(preg_replace('/\s+/', ' ', $content));
        if ($content === '') {
            return '查看详情';
        }

        return mb_strlen($content) > 24 ? mb_substr($content, 0, 24) . '...' : $content;
    }
}
