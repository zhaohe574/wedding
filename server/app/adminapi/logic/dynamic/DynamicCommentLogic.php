<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 动态评论审核逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\dynamic;

use app\common\logic\BaseLogic;
use app\common\model\dynamic\DynamicComment;
use app\common\service\ConfigService;

/**
 * 动态评论审核逻辑
 * Class DynamicCommentLogic
 * @package app\adminapi\logic\dynamic
 */
class DynamicCommentLogic extends BaseLogic
{
    /**
     * @notes 获取评论详情
     * @param int $id
     * @return array|null
     */
    public static function detail(int $id): ?array
    {
        $comment = DynamicComment::with([
            'user' => function($q) {
                $q->field('id, nickname, avatar, mobile');
            },
            'dynamic' => function($q) {
                $q->field('id, user_id, user_type, dynamic_type, title, content, images, video_url, video_cover, location, tags, view_count, like_count, comment_count, collect_count, status, create_time');
            }
        ])->find($id);

        if (!$comment) {
            return null;
        }

        $data = $comment->toArray();
        
        // 添加审核状态描述
        $data['review_status_desc'] = self::getReviewStatusDesc($comment->review_status);
        
        // 如果有动态信息，添加动态的额外信息
        if ($data['dynamic']) {
            $data['dynamic']['type_desc'] = self::getDynamicTypeDesc($data['dynamic']['dynamic_type']);
            $data['dynamic']['status_desc'] = self::getDynamicStatusDesc($data['dynamic']['status']);
            
            // 获取动态发布者信息
            $data['dynamic']['publisher'] = self::getDynamicPublisher(
                $data['dynamic']['user_type'], 
                $data['dynamic']['user_id']
            );
            
            // 处理图片数组
            if (!is_array($data['dynamic']['images'])) {
                $data['dynamic']['images'] = $data['dynamic']['images'] ? json_decode($data['dynamic']['images'], true) : [];
            }
        }
        
        // 如果是回复评论，获取父评论信息
        if ($comment->parent_id > 0) {
            $parentComment = DynamicComment::with(['user' => function($q) {
                $q->field('id, nickname, avatar');
            }])->find($comment->parent_id);
            
            if ($parentComment) {
                $data['parent_comment'] = $parentComment->toArray();
            }
        }

        return $data;
    }

    /**
     * @notes 获取动态发布者信息
     * @param int $userType
     * @param int $userId
     * @return array|null
     */
    protected static function getDynamicPublisher(int $userType, int $userId): ?array
    {
        if ($userType == 1) { // 用户
            $user = \app\common\model\user\User::field('id, nickname, avatar')->find($userId);
            return $user ? $user->toArray() : null;
        } elseif ($userType == 2) { // 工作人员
            $staff = \app\common\model\staff\Staff::field('id, name as nickname, avatar')->find($userId);
            return $staff ? $staff->toArray() : null;
        } else { // 官方
            return ['id' => 0, 'nickname' => '官方', 'avatar' => ''];
        }
    }

    /**
     * @notes 获取审核状态描述
     * @param int $status
     * @return string
     */
    protected static function getReviewStatusDesc(int $status): string
    {
        $map = [
            DynamicComment::REVIEW_STATUS_PENDING => '待审核',
            DynamicComment::REVIEW_STATUS_APPROVED => '已通过',
            DynamicComment::REVIEW_STATUS_REJECTED => '已拒绝',
        ];
        return $map[$status] ?? '未知';
    }

    /**
     * @notes 获取动态类型描述
     * @param int $type
     * @return string
     */
    protected static function getDynamicTypeDesc(int $type): string
    {
        $map = [
            1 => '图文',
            2 => '视频',
            3 => '案例',
            4 => '活动',
        ];
        return $map[$type] ?? '未知';
    }

    /**
     * @notes 获取动态状态描述
     * @param int $status
     * @return string
     */
    protected static function getDynamicStatusDesc(int $status): string
    {
        $map = [
            0 => '待审核',
            1 => '已发布',
            2 => '已下架',
            3 => '已拒绝',
        ];
        return $map[$status] ?? '未知';
    }
    /**
     * @notes 审核通过评论
     * @param int $commentId
     * @param int $adminId
     * @return bool
     */
    public static function approve(int $commentId, int $adminId): bool
    {
        [$success, $message] = DynamicComment::approveComment($commentId, $adminId);
        if (!$success) {
            self::setError($message);
        }
        return $success;
    }


    /**
     * @notes 拒绝评论
     * @param int $commentId
     * @param int $adminId
     * @param string $remark
     * @return bool
     */
    public static function reject(int $commentId, int $adminId, string $remark): bool
    {
        [$success, $message] = DynamicComment::rejectComment($commentId, $adminId, $remark);
        if (!$success) {
            self::setError($message);
        }
        return $success;
    }

    /**
     * @notes 批量审核通过
     * @param array $commentIds
     * @param int $adminId
     * @return array
     */
    public static function batchApprove(array $commentIds, int $adminId): array
    {
        $successCount = 0;
        $failCount = 0;

        foreach ($commentIds as $commentId) {
            [$success] = DynamicComment::approveComment($commentId, $adminId);
            if ($success) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        return [
            'success_count' => $successCount,
            'fail_count' => $failCount,
        ];
    }

    /**
     * @notes 批量拒绝
     * @param array $commentIds
     * @param int $adminId
     * @param string $remark
     * @return array
     */
    public static function batchReject(array $commentIds, int $adminId, string $remark): array
    {
        $successCount = 0;
        $failCount = 0;

        foreach ($commentIds as $commentId) {
            [$success] = DynamicComment::rejectComment($commentId, $adminId, $remark);
            if ($success) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        return [
            'success_count' => $successCount,
            'fail_count' => $failCount,
        ];
    }

    /**
     * @notes 获取评论审核配置
     * @return int
     */
    public static function getReviewConfig(): int
    {
        return (int)ConfigService::get('dynamic', 'comment_review_enabled', 0);
    }

    /**
     * @notes 设置评论审核配置
     * @param int $enabled
     * @return bool
     */
    public static function setReviewConfig(int $enabled): bool
    {
        try {
            ConfigService::set('dynamic', 'comment_review_enabled', $enabled);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除评论
     * @param int $commentId
     * @param int $adminId
     * @return bool
     */
    public static function delete(int $commentId, int $adminId): bool
    {
        try {
            $comment = DynamicComment::find($commentId);
            if (!$comment) {
                self::setError('评论不存在');
                return false;
            }

            // 更新动态评论数
            if ($comment->review_status == DynamicComment::REVIEW_STATUS_APPROVED) {
                \app\common\model\dynamic\Dynamic::where('id', $comment->dynamic_id)
                    ->dec('comment_count')
                    ->update();
            }

            // 软删除
            $comment->delete();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 批量删除评论
     * @param array $commentIds
     * @param int $adminId
     * @return array
     */
    public static function batchDelete(array $commentIds, int $adminId): array
    {
        $successCount = 0;
        $failCount = 0;

        foreach ($commentIds as $commentId) {
            $result = self::delete($commentId, $adminId);
            if ($result) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        return [
            'success_count' => $successCount,
            'fail_count' => $failCount,
        ];
    }
}
