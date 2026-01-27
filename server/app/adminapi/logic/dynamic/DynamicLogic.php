<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 动态业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\dynamic;

use app\common\logic\BaseLogic;
use app\common\model\dynamic\Dynamic;
use app\common\model\dynamic\DynamicComment;

/**
 * 动态业务逻辑
 * Class DynamicLogic
 * @package app\adminapi\logic\dynamic
 */
class DynamicLogic extends BaseLogic
{
    /**
     * @notes 获取动态详情
     * @param int $id
     * @return array|null
     */
    public static function detail(int $id): ?array
    {
        $dynamic = Dynamic::with([
            'comments' => function ($query) {
                $query->where('status', DynamicComment::STATUS_NORMAL)
                    ->order('is_top', 'desc')
                    ->order('like_count', 'desc')
                    ->limit(10);
            }
        ])->find($id);

        if (!$dynamic) {
            return null;
        }

        $data = $dynamic->toArray();
        $data['type_desc'] = self::getTypeDesc($dynamic->dynamic_type);
        $data['status_desc'] = self::getStatusDesc($dynamic->status);
        $data['user_type_desc'] = self::getUserTypeDesc($dynamic->user_type);

        // 获取发布者信息
        if ($dynamic->user_type == Dynamic::USER_TYPE_USER) {
            $user = \app\common\model\user\User::field('id, nickname, avatar')->find($dynamic->user_id);
            $data['publisher'] = $user ? $user->toArray() : null;
        } elseif ($dynamic->user_type == Dynamic::USER_TYPE_STAFF) {
            $staff = \app\common\model\staff\Staff::field('id, name, avatar')->find($dynamic->user_id);
            $data['publisher'] = $staff ? $staff->toArray() : null;
        } else {
            $data['publisher'] = ['id' => 0, 'nickname' => '官方', 'avatar' => ''];
        }

        return $data;
    }

    /**
     * @notes 审核动态
     * @param int $dynamicId
     * @param int $adminId
     * @param bool $approved
     * @param string $remark
     * @return bool
     */
    public static function audit(int $dynamicId, int $adminId, bool $approved, string $remark = ''): bool
    {
        [$success, $message] = Dynamic::audit($dynamicId, $adminId, $approved, $remark);
        if (!$success) {
            self::setError($message);
            return false;
        }
        return true;
    }

    /**
     * @notes 下架动态
     * @param int $dynamicId
     * @param int $adminId
     * @param string $reason
     * @return bool
     */
    public static function offline(int $dynamicId, int $adminId, string $reason = ''): bool
    {
        try {
            $dynamic = Dynamic::find($dynamicId);
            if (!$dynamic) {
                self::setError('动态不存在');
                return false;
            }

            if ($dynamic->status != Dynamic::STATUS_PUBLISHED) {
                self::setError('只有已发布的动态才能下架');
                return false;
            }

            $dynamic->status = Dynamic::STATUS_OFFLINE;
            $dynamic->audit_admin_id = $adminId;
            $dynamic->audit_time = time();
            $dynamic->audit_remark = $reason ?: '管理员下架';
            $dynamic->update_time = time();
            $dynamic->save();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 设置置顶
     * @param int $dynamicId
     * @param int $isTop
     * @return bool
     */
    public static function setTop(int $dynamicId, int $isTop): bool
    {
        try {
            $dynamic = Dynamic::find($dynamicId);
            if (!$dynamic) {
                self::setError('动态不存在');
                return false;
            }

            $dynamic->is_top = $isTop;
            $dynamic->update_time = time();
            $dynamic->save();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 设置热门
     * @param int $dynamicId
     * @param int $isHot
     * @return bool
     */
    public static function setHot(int $dynamicId, int $isHot): bool
    {
        try {
            $dynamic = Dynamic::find($dynamicId);
            if (!$dynamic) {
                self::setError('动态不存在');
                return false;
            }

            $dynamic->is_hot = $isHot;
            $dynamic->update_time = time();
            $dynamic->save();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除动态
     * @param int $dynamicId
     * @param int $adminId
     * @return bool
     */
    public static function delete(int $dynamicId, int $adminId): bool
    {
        try {
            $dynamic = Dynamic::find($dynamicId);
            if (!$dynamic) {
                self::setError('动态不存在');
                return false;
            }

            // 软删除
            $dynamic->delete();

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
    public static function deleteComment(int $commentId, int $adminId): bool
    {
        try {
            $comment = DynamicComment::find($commentId);
            if (!$comment) {
                self::setError('评论不存在');
                return false;
            }

            // 更新动态评论数
            Dynamic::where('id', $comment->dynamic_id)->dec('comment_count')->update();

            // 软删除
            $comment->delete();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 设置评论置顶
     * @param int $commentId
     * @param int $isTop
     * @return bool
     */
    public static function setCommentTop(int $commentId, int $isTop): bool
    {
        try {
            $comment = DynamicComment::find($commentId);
            if (!$comment) {
                self::setError('评论不存在');
                return false;
            }

            $comment->is_top = $isTop;
            $comment->update_time = time();
            $comment->save();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 动态统计
     * @param array $params
     * @return array
     */
    public static function statistics(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate . ' 23:59:59');

        $query = Dynamic::whereBetween('create_time', [$startTime, $endTime]);

        // 总动态数
        $totalDynamics = (clone $query)->count();

        // 各状态统计
        $statusCounts = [];
        foreach ([
            Dynamic::STATUS_PENDING => '待审核',
            Dynamic::STATUS_PUBLISHED => '已发布',
            Dynamic::STATUS_OFFLINE => '已下架',
            Dynamic::STATUS_REJECTED => '已拒绝',
        ] as $status => $label) {
            $statusCounts[] = [
                'status' => $status,
                'label' => $label,
                'count' => (clone $query)->where('status', $status)->count(),
            ];
        }

        // 各类型统计
        $typeCounts = [];
        foreach ([
            Dynamic::TYPE_IMAGE_TEXT => '图文',
            Dynamic::TYPE_VIDEO => '视频',
            Dynamic::TYPE_CASE => '案例',
            Dynamic::TYPE_ACTIVITY => '活动',
        ] as $type => $label) {
            $typeCounts[] = [
                'type' => $type,
                'label' => $label,
                'count' => (clone $query)->where('dynamic_type', $type)->count(),
            ];
        }

        // 互动统计
        $totalViews = (clone $query)->sum('view_count');
        $totalLikes = (clone $query)->sum('like_count');
        $totalComments = (clone $query)->sum('comment_count');
        $totalCollects = (clone $query)->sum('collect_count');

        // 今日数据
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = time();
        $todayDynamics = Dynamic::whereBetween('create_time', [$todayStart, $todayEnd])->count();
        $todayPending = Dynamic::whereBetween('create_time', [$todayStart, $todayEnd])
            ->where('status', Dynamic::STATUS_PENDING)
            ->count();

        return [
            'total_dynamics' => $totalDynamics,
            'status_counts' => $statusCounts,
            'type_counts' => $typeCounts,
            'interaction' => [
                'views' => (int)$totalViews,
                'likes' => (int)$totalLikes,
                'comments' => (int)$totalComments,
                'collects' => (int)$totalCollects,
            ],
            'today' => [
                'dynamics' => $todayDynamics,
                'pending' => $todayPending,
            ],
        ];
    }

    /**
     * @notes 管理员发布动态
     * @param int $adminId
     * @param array $params
     * @return bool
     */
    public static function add(int $adminId, array $params): bool
    {
        try {
            $data = [
                'user_id' => $adminId,
                'user_type' => Dynamic::USER_TYPE_OFFICIAL, // 官方发布
                'dynamic_type' => $params['dynamic_type'] ?? Dynamic::TYPE_IMAGE_TEXT,
                'title' => $params['title'] ?? '',
                'content' => $params['content'],
                'images' => $params['images'] ?? [],
                'video_url' => $params['video'] ?? '',
                'video_cover' => $params['video_cover'] ?? '',
                'location' => $params['location'] ?? '',
                'latitude' => $params['latitude'] ?? 0,
                'longitude' => $params['longitude'] ?? 0,
                'tags' => is_array($params['tags'] ?? '') ? implode(',', $params['tags']) : ($params['tags'] ?? ''),
                'allow_comment' => $params['allow_comment'] ?? 1, // 默认允许评论
                'status' => Dynamic::STATUS_PUBLISHED, // 管理员发布直接通过
                'create_time' => time(),
                'update_time' => time(),
            ];

            Dynamic::create($data);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 管理员编辑动态
     * @param int $dynamicId
     * @param array $params
     * @return bool
     */
    public static function edit(int $dynamicId, array $params): bool
    {
        try {
            $dynamic = Dynamic::find($dynamicId);
            if (!$dynamic) {
                self::setError('动态不存在');
                return false;
            }

            $dynamic->dynamic_type = $params['dynamic_type'] ?? $dynamic->dynamic_type;
            $dynamic->title = $params['title'] ?? $dynamic->title;
            $dynamic->content = $params['content'] ?? $dynamic->content;
            $dynamic->images = $params['images'] ?? $dynamic->images;
            $dynamic->video_url = $params['video'] ?? $dynamic->video_url;
            $dynamic->video_cover = $params['video_cover'] ?? $dynamic->video_cover;
            $dynamic->location = $params['location'] ?? $dynamic->location;
            $dynamic->latitude = $params['latitude'] ?? $dynamic->latitude;
            $dynamic->longitude = $params['longitude'] ?? $dynamic->longitude;
            $dynamic->tags = is_array($params['tags'] ?? '') ? implode(',', $params['tags']) : ($params['tags'] ?? $dynamic->tags);
            $dynamic->allow_comment = $params['allow_comment'] ?? $dynamic->allow_comment; // 更新评论开关
            $dynamic->update_time = time();
            $dynamic->save();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取动态类型选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return [
            ['value' => Dynamic::TYPE_IMAGE_TEXT, 'label' => '图文'],
            ['value' => Dynamic::TYPE_VIDEO, 'label' => '视频'],
            ['value' => Dynamic::TYPE_CASE, 'label' => '案例'],
            ['value' => Dynamic::TYPE_ACTIVITY, 'label' => '活动'],
        ];
    }

    /**
     * @notes 获取动态状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            ['value' => Dynamic::STATUS_PENDING, 'label' => '待审核'],
            ['value' => Dynamic::STATUS_PUBLISHED, 'label' => '已发布'],
            ['value' => Dynamic::STATUS_OFFLINE, 'label' => '已下架'],
            ['value' => Dynamic::STATUS_REJECTED, 'label' => '已拒绝'],
        ];
    }

    /**
     * @notes 获取类型描述
     * @param int $type
     * @return string
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

    /**
     * @notes 获取状态描述
     * @param int $status
     * @return string
     */
    protected static function getStatusDesc(int $status): string
    {
        $map = [
            Dynamic::STATUS_PENDING => '待审核',
            Dynamic::STATUS_PUBLISHED => '已发布',
            Dynamic::STATUS_OFFLINE => '已下架',
            Dynamic::STATUS_REJECTED => '已拒绝',
        ];
        return $map[$status] ?? '未知';
    }

    /**
     * @notes 获取用户类型描述
     * @param int $userType
     * @return string
     */
    protected static function getUserTypeDesc(int $userType): string
    {
        $map = [
            Dynamic::USER_TYPE_USER => '用户',
            Dynamic::USER_TYPE_STAFF => '工作人员',
            Dynamic::USER_TYPE_OFFICIAL => '官方',
        ];
        return $map[$userType] ?? '未知';
    }
}
