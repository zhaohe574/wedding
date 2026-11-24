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
use app\common\model\staff\StaffCertificate;
use app\common\model\staff\StaffWork;
use app\common\model\user\User;
use think\facade\Log;

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

            StationNotificationService::send((int)$staff->user_id,
                Notification::TYPE_INTERACT,
                '您有新的关注',
                sprintf('%s关注了您，快去看看吧。', $actorName),
                StationNotificationService::TARGET_STAFF_DETAIL,
                (int)$staff->id,
                $actorUserId,
                ['event' => 'notifyOnFollow', 'instance' => json_encode([$actorUserId, $followType, $followId]), 'audience' => 'staff']);
            return;
        }

        if ($followType === Follow::TYPE_USER && $followId > 0 && $followId !== $actorUserId) {
            StationNotificationService::send($followId,
                Notification::TYPE_INTERACT,
                '您有新的关注',
                sprintf('%s关注了您。', $actorName),
                '',
                0,
                $actorUserId,
                ['event' => 'notifyOnFollow', 'instance' => json_encode([$actorUserId, $followType, $followId])]);
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

        StationNotificationService::send($recipientUserId,
                Notification::TYPE_INTERACT,
                '有人赞了您的动态',
                sprintf('%s赞了您的动态，快去看看互动吧。', self::getUserDisplayName($actorUserId)),
                StationNotificationService::TARGET_DYNAMIC_DETAIL,
                $dynamicId,
                $actorUserId,
                ['event' => 'notifyOnDynamicLiked', 'instance' => json_encode([$actorUserId, $dynamicId])]);
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

        StationNotificationService::send((int)$comment->user_id,
                Notification::TYPE_INTERACT,
                '有人赞了您的评论',
                sprintf('%s赞了您的评论。', self::getUserDisplayName($actorUserId)),
                StationNotificationService::TARGET_DYNAMIC_DETAIL,
                (int)$comment->dynamic_id,
                $actorUserId,
                ['event' => 'notifyOnCommentLiked', 'instance' => json_encode([$actorUserId, $commentId])]);
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
            StationNotificationService::send((int)$recipientUserId,
                Notification::TYPE_INTERACT,
                (string)$payload['title'],
                (string)$payload['content'],
                StationNotificationService::TARGET_DYNAMIC_DETAIL,
                (int)$comment->dynamic_id,
                $actorUserId,
                ['event' => 'notifyOnCommentVisible', 'instance' => json_encode([$commentId])]);
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

        StationNotificationService::send((int)$comment->user_id,
                Notification::TYPE_INTERACT,
                $title,
                $content,
                StationNotificationService::TARGET_DYNAMIC_DETAIL,
                (int)$comment->dynamic_id,
                0,
                ['event' => 'notifyCommentAuditResult', 'instance' => json_encode([$commentId, $approved])]);
    }

    /**
     * 动态审核结果通知动态作者。
     *
     * 链路与 notifyCommentAuditResult 一致：StationNotificationService::send →
     * BusinessNotificationService::record（event + instance 幂等）。服务人员动态的
     * dynamic.user_id 存的是 staff_id，owner 反查走 staff.user_id（resolveDynamicOwnerUserId）。
     * 任何异常只记日志不抛出，通知失败不影响审核结果。
     */
    public static function notifyDynamicAuditResult(int $dynamicId, bool $approved, string $remark = ''): void
    {
        try {
            $dynamic = Dynamic::field('id, user_id, user_type, staff_id, title, status')->find($dynamicId);
            if (!$dynamic) {
                return;
            }

            $recipientUserId = self::resolveDynamicOwnerUserId($dynamic);
            if ($recipientUserId <= 0) {
                return;
            }

            $snippet = self::buildContentSnippet((string)$dynamic->title);
            $subject = ($snippet !== '' && $snippet !== '查看详情') ? '《' . $snippet . '》' : '';
            $title = $approved ? '您的动态已通过审核' : '您的动态未通过审核';
            $content = $approved
                ? '您发布的动态' . $subject . '已通过审核，现在可以查看了。'
                : '您发布的动态' . $subject . '未通过审核'
                    . ($remark !== '' ? '，原因：' . $remark : '，请修改后重新发布。');

            StationNotificationService::send($recipientUserId,
                Notification::TYPE_INTERACT,
                $title,
                $content,
                // target_type 用 staff_dynamic：接收人是服务人员本人，落点为其自己的动态列表
                //（前端 ROUTES.staff_dynamic → dynamic-list），被拒动态可直达重新编辑；
                // 公共 case-list 对服务人员无操作意义，故不复用 TARGET_DYNAMIC_DETAIL。
                StationNotificationService::TARGET_STAFF_DYNAMIC,
                $dynamicId,
                0,
                ['event' => 'notifyDynamicAuditResult', 'instance' => json_encode([$dynamicId, $approved])]);
        } catch (\Throwable $e) {
            Log::warning('动态审核结果通知失败：dynamic_id=' . $dynamicId . '，message=' . $e->getMessage());
        }
    }

    /**
     * 作品审核结果通知服务人员。
     */
    public static function notifyStaffWorkAuditResult(int $workId, bool $approved, string $remark = ''): void
    {
        try {
            $work = StaffWork::field('id, staff_id, title, update_time')->find($workId);
            if (!$work) {
                return;
            }
            self::sendStaffAssetAuditNotification(
                '作品',
                (string)$work->title,
                (int)$work->staff_id,
                StationNotificationService::TARGET_STAFF_WORK,
                $workId,
                'notifyStaffWorkAuditResult',
                $approved,
                (int)($work->update_time ?? 0),
                $remark
            );
        } catch (\Throwable $e) {
            Log::warning('作品审核结果通知失败：work_id=' . $workId . '，message=' . $e->getMessage());
        }
    }

    /**
     * 证书审核结果通知服务人员。
     */
    public static function notifyStaffCertificateAuditResult(int $certificateId, bool $approved, string $remark = ''): void
    {
        try {
            $certificate = StaffCertificate::field('id, staff_id, name, update_time')->find($certificateId);
            if (!$certificate) {
                return;
            }
            self::sendStaffAssetAuditNotification(
                '证书',
                (string)$certificate->name,
                (int)$certificate->staff_id,
                StationNotificationService::TARGET_STAFF_CERTIFICATE,
                $certificateId,
                'notifyStaffCertificateAuditResult',
                $approved,
                (int)($certificate->update_time ?? 0),
                $remark
            );
        } catch (\Throwable $e) {
            Log::warning('证书审核结果通知失败：certificate_id=' . $certificateId . '，message=' . $e->getMessage());
        }
    }

    private static function sendStaffAssetAuditNotification(
        string $resourceLabel,
        string $rawTitle,
        int $staffId,
        string $targetType,
        int $targetId,
        string $eventName,
        bool $approved,
        int $updateTime,
        string $remark
    ): void {
        $staff = Staff::field('user_id')->find($staffId);
        $recipientUserId = (int)($staff->user_id ?? 0);
        if ($recipientUserId <= 0) {
            return;
        }

        $snippet = self::buildContentSnippet($rawTitle);
        $subject = ($snippet !== '' && $snippet !== '查看详情') ? '《' . $snippet . '》' : '';
        $title = $approved ? "您的{$resourceLabel}已通过审核" : "您的{$resourceLabel}未通过审核";
        $content = $approved
            ? "您提交的{$resourceLabel}{$subject}已通过审核。"
            : "您提交的{$resourceLabel}{$subject}未通过审核"
                . ($remark !== '' ? '，原因：' . $remark : '，请修改后重新提交。');

        StationNotificationService::send(
            $recipientUserId,
            Notification::TYPE_SYSTEM,
            $title,
            $content,
            $targetType,
            $targetId,
            0,
            ['event' => $eventName, 'instance' => json_encode([$targetId, $approved, $updateTime])]
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
