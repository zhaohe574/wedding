<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 动态评论模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\dynamic;

use app\common\model\BaseModel;
use app\common\model\user\User;
use think\model\concern\SoftDelete;

/**
 * 动态评论模型
 * Class DynamicComment
 * @package app\common\model\dynamic
 */
class DynamicComment extends BaseModel
{
    use SoftDelete;

    protected $name = 'dynamic_comment';
    protected $deleteTime = 'delete_time';

    // 状态
    const STATUS_PENDING = 0;   // 待审核
    const STATUS_NORMAL = 1;    // 正常
    const STATUS_DELETED = 2;   // 已删除
    const STATUS_REJECTED = 3;  // 审核拒绝

    // 审核状态
    const REVIEW_STATUS_PENDING = 0;   // 待审核
    const REVIEW_STATUS_APPROVED = 1;  // 已通过
    const REVIEW_STATUS_REJECTED = 2;  // 已拒绝

    /**
     * @notes 关联动态
     * @return \think\model\relation\BelongsTo
     */
    public function dynamic()
    {
        return $this->belongsTo(Dynamic::class, 'dynamic_id', 'id');
    }

    /**
     * @notes 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @notes 关联回复用户
     * @return \think\model\relation\BelongsTo
     */
    public function replyUser()
    {
        return $this->belongsTo(User::class, 'reply_user_id', 'id');
    }

    /**
     * @notes 关联子评论
     * @return \think\model\relation\HasMany
     */
    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

    /**
     * @notes 图片数组获取器
     * @param $value
     * @return array
     */
    public function getImagesAttr($value): array
    {
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @notes 发表评论
     * @param int $dynamicId
     * @param int $userId
     * @param string $content
     * @param int $parentId
     * @param int $replyUserId
     * @param array $images
     * @return array [bool $success, string $message, DynamicComment|null $comment]
     */
    public static function addComment(int $dynamicId, int $userId, string $content, int $parentId = 0, int $replyUserId = 0, array $images = []): array
    {
        $dynamic = Dynamic::find($dynamicId);
        if (!$dynamic) {
            return [false, '动态不存在', null];
        }

        if ($dynamic->status != Dynamic::STATUS_PUBLISHED) {
            return [false, '该动态不可评论', null];
        }

        // 检查评论开关
        if ($dynamic->allow_comment != 1) {
            return [false, '该动态不允许评论', null];
        }

        // 敏感词检测
        // TODO: 实现敏感词过滤

        // 获取审核配置
        $reviewEnabled = (int)\app\common\service\ConfigService::get('dynamic', 'comment_review_enabled', 0);
        $reviewStatus = $reviewEnabled ? self::REVIEW_STATUS_PENDING : self::REVIEW_STATUS_APPROVED;

        try {
            $comment = self::create([
                'dynamic_id' => $dynamicId,
                'user_id' => $userId,
                'parent_id' => $parentId,
                'reply_user_id' => $replyUserId,
                'content' => $content,
                'images' => json_encode($images, JSON_UNESCAPED_UNICODE),
                'status' => self::STATUS_NORMAL,
                'review_status' => $reviewStatus, // 根据配置设置审核状态
                'ip' => request()->ip(),
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 只有审核通过的评论才更新计数
            if ($reviewStatus == self::REVIEW_STATUS_APPROVED) {
                // 更新动态评论数
                Dynamic::where('id', $dynamicId)->inc('comment_count')->update();

                // 更新父评论回复数
                if ($parentId > 0) {
                    self::where('id', $parentId)->inc('reply_count')->update();
                }
            }

            // TODO: 发送通知给动态作者/被回复者

            $message = $reviewEnabled ? '评论成功，等待审核' : '评论成功';
            return [true, $message, $comment];
        } catch (\Exception $e) {
            return [false, '评论失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 删除评论
     * @param int $commentId
     * @param int $userId
     * @return array [bool $success, string $message]
     */
    public static function deleteComment(int $commentId, int $userId): array
    {
        $comment = self::find($commentId);
        if (!$comment) {
            return [false, '评论不存在'];
        }

        if ($comment->user_id != $userId) {
            return [false, '无权删除此评论'];
        }

        $comment->delete();

        // 更新动态评论数
        Dynamic::where('id', $comment->dynamic_id)->dec('comment_count')->update();

        // 更新父评论回复数
        if ($comment->parent_id > 0) {
            self::where('id', $comment->parent_id)->dec('reply_count')->update();
        }

        return [true, '删除成功'];
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
        // 如果传入了 parent_id,则获取子评论列表
        $parentId = (int)($params['parent_id'] ?? 0);
        
        if ($parentId > 0) {
            // 获取子评论列表
            return self::getReplyList($parentId, $userId, $params);
        }
        
        // 获取主评论列表
        $query = self::where('dynamic_id', $dynamicId)
            ->where('parent_id', 0)
            ->where('status', self::STATUS_NORMAL)
            ->where('review_status', self::REVIEW_STATUS_APPROVED); // 只显示已通过审核的评论

        // 排序
        $sort = $params['sort'] ?? 'hot';
        if ($sort === 'new') {
            $query->order('create_time', 'desc');
        } else {
            // 热门排序：置顶 > 点赞数 > 时间
            $query->order('is_top', 'desc')
                ->order('like_count', 'desc')
                ->order('create_time', 'desc');
        }

        $list = $query->with(['user' => function ($q) {
                $q->field('id, nickname, avatar');
            }])
            ->paginate((int)($params['page_size'] ?? 10))
            ->toArray();

        // 不再默认加载子评论,只返回 reply_count
        // 前端根据 reply_count 决定是否显示"查看更多"按钮

        // 添加是否点赞信息
        if ($userId > 0 && !empty($list['data'])) {
            $commentIds = array_column($list['data'], 'id');
            $likedIds = DynamicLike::where('user_id', $userId)
                ->where('target_type', 2)
                ->whereIn('target_id', $commentIds)
                ->column('target_id');

            foreach ($list['data'] as &$item) {
                $item['is_liked'] = in_array($item['id'], $likedIds);
            }
        }

        return $list;
    }

    /**
     * @notes 获取子评论列表
     * @param int $parentId
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function getReplyList(int $parentId, int $userId = 0, array $params = []): array
    {
        $query = self::where('parent_id', $parentId)
            ->where('status', self::STATUS_NORMAL)
            ->where('review_status', self::REVIEW_STATUS_APPROVED)
            ->order('create_time', 'asc');

        $list = $query->with(['user' => function ($q) {
                $q->field('id, nickname, avatar');
            }, 'replyUser' => function ($q) {
                $q->field('id, nickname');
            }])
            ->paginate((int)($params['page_size'] ?? 20))
            ->toArray();

        // 处理用户信息
        foreach ($list['data'] as &$item) {
            $item['user_nickname'] = $item['user']['nickname'] ?? '匿名用户';
            $item['user_avatar'] = $item['user']['avatar'] ?? '';
            $item['reply_user_nickname'] = $item['reply_user']['nickname'] ?? '';
            
            // 移除原始关联数据
            unset($item['user'], $item['reply_user']);
        }

        // 添加是否点赞信息
        if ($userId > 0 && !empty($list['data'])) {
            $commentIds = array_column($list['data'], 'id');
            $likedIds = DynamicLike::where('user_id', $userId)
                ->where('target_type', 2)
                ->whereIn('target_id', $commentIds)
                ->column('target_id');

            foreach ($list['data'] as &$item) {
                $item['is_liked'] = in_array($item['id'], $likedIds);
            }
        }

        return $list;
    }

    /**
     * @notes 审核通过评论
     * @param int $commentId
     * @param int $adminId
     * @return array [bool $success, string $message]
     */
    public static function approveComment(int $commentId, int $adminId): array
    {
        $comment = self::find($commentId);
        if (!$comment) {
            return [false, '评论不存在'];
        }

        if ($comment->review_status != self::REVIEW_STATUS_PENDING) {
            return [false, '该评论已审核'];
        }

        $comment->review_status = self::REVIEW_STATUS_APPROVED;
        $comment->review_admin_id = $adminId;
        $comment->review_time = time();
        $comment->save();

        // 更新动态评论数
        Dynamic::where('id', $comment->dynamic_id)->inc('comment_count')->update();

        // 更新父评论回复数
        if ($comment->parent_id > 0) {
            self::where('id', $comment->parent_id)->inc('reply_count')->update();
        }

        // TODO: 发送通知给用户

        return [true, '审核通过'];
    }

    /**
     * @notes 拒绝评论
     * @param int $commentId
     * @param int $adminId
     * @param string $remark
     * @return array [bool $success, string $message]
     */
    public static function rejectComment(int $commentId, int $adminId, string $remark = ''): array
    {
        $comment = self::find($commentId);
        if (!$comment) {
            return [false, '评论不存在'];
        }

        if ($comment->review_status != self::REVIEW_STATUS_PENDING) {
            return [false, '该评论已审核'];
        }

        $comment->review_status = self::REVIEW_STATUS_REJECTED;
        $comment->review_admin_id = $adminId;
        $comment->review_time = time();
        $comment->review_remark = $remark;
        $comment->save();

        // TODO: 发送通知给用户

        return [true, '已拒绝'];
    }

    /**
     * @notes 审核状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getReviewStatusDescAttr($value, $data): string
    {
        $map = [
            self::REVIEW_STATUS_PENDING => '待审核',
            self::REVIEW_STATUS_APPROVED => '已通过',
            self::REVIEW_STATUS_REJECTED => '已拒绝',
        ];
        return $map[$data['review_status'] ?? 0] ?? '未知';
    }
}
