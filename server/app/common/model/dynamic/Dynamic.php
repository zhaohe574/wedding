<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 动态模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\dynamic;

use app\common\model\BaseModel;
use app\common\model\user\User;
use app\common\model\staff\Staff;
use think\model\concern\SoftDelete;

/**
 * 动态模型
 * Class Dynamic
 * @package app\common\model\dynamic
 */
class Dynamic extends BaseModel
{
    use SoftDelete;

    protected $name = 'dynamic';
    protected $deleteTime = 'delete_time';

    // 发布者类型
    const USER_TYPE_USER = 1;       // 普通用户
    const USER_TYPE_STAFF = 2;      // 工作人员
    const USER_TYPE_OFFICIAL = 3;   // 官方

    // 动态类型
    const TYPE_IMAGE_TEXT = 1;  // 图文
    const TYPE_VIDEO = 2;       // 视频
    const TYPE_CASE = 3;        // 案例分享
    const TYPE_ACTIVITY = 4;    // 活动

    // 状态
    const STATUS_PENDING = 0;   // 待审核
    const STATUS_PUBLISHED = 1; // 已发布
    const STATUS_OFFLINE = 2;   // 已下架
    const STATUS_REJECTED = 3;  // 审核拒绝

    /**
     * @notes 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @notes 关联工作人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    /**
     * @notes 关联评论
     * @return \think\model\relation\HasMany
     */
    public function comments()
    {
        return $this->hasMany(DynamicComment::class, 'dynamic_id', 'id');
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
     * @notes 图片数组设置器
     * @param $value
     * @return string
     */
    public function setImagesAttr($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return $value ?: '';
    }

    /**
     * @notes 标签数组获取器
     * @param $value
     * @return array
     */
    public function getTagsArrAttr($value, $data): array
    {
        if (empty($data['tags'])) {
            return [];
        }
        return explode(',', $data['tags']);
    }

    /**
     * @notes 动态类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getDynamicTypeDescAttr($value, $data): string
    {
        $map = [
            self::TYPE_IMAGE_TEXT => '图文',
            self::TYPE_VIDEO => '视频',
            self::TYPE_CASE => '案例分享',
            self::TYPE_ACTIVITY => '活动',
        ];
        return $map[$data['dynamic_type']] ?? '未知';
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusDescAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_PUBLISHED => '已发布',
            self::STATUS_OFFLINE => '已下架',
            self::STATUS_REJECTED => '审核拒绝',
        ];
        return $map[$data['status']] ?? '未知';
    }

    /**
     * @notes 评论开关描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getAllowCommentDescAttr($value, $data): string
    {
        return ($data['allow_comment'] ?? 1) == 1 ? '允许' : '禁止';
    }

    /**
     * @notes 发布动态
     * @param int $userId
     * @param int $userType
     * @param array $data
     * @return array [bool $success, string $message, Dynamic|null $dynamic]
     */
    public static function publish(int $userId, int $userType, array $data): array
    {
        try {
            $dynamic = self::create([
                'user_id' => $userId,
                'user_type' => $userType,
                'staff_id' => $data['staff_id'] ?? 0,
                'dynamic_type' => $data['dynamic_type'] ?? self::TYPE_IMAGE_TEXT,
                'title' => $data['title'] ?? '',
                'content' => $data['content'] ?? '',
                'images' => $data['images'] ?? [],
                'video_url' => $data['video_url'] ?? '',
                'video_cover' => $data['video_cover'] ?? '',
                'location' => $data['location'] ?? '',
                'latitude' => $data['latitude'] ?? 0,
                'longitude' => $data['longitude'] ?? 0,
                'tags' => is_array($data['tags'] ?? '') ? implode(',', $data['tags']) : ($data['tags'] ?? ''),
                'allow_comment' => $data['allow_comment'] ?? 1, // 默认允许评论
                'order_id' => $data['order_id'] ?? 0,
                'status' => self::STATUS_PENDING, // 需审核
                'create_time' => time(),
                'update_time' => time(),
            ]);
            return [true, '发布成功，等待审核', $dynamic];
        } catch (\Exception $e) {
            return [false, '发布失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 审核动态
     * @param int $dynamicId
     * @param int $adminId
     * @param bool $approved
     * @param string $remark
     * @return array [bool $success, string $message]
     */
    public static function audit(int $dynamicId, int $adminId, bool $approved, string $remark = ''): array
    {
        $dynamic = self::find($dynamicId);
        if (!$dynamic) {
            return [false, '动态不存在'];
        }

        if ($dynamic->status != self::STATUS_PENDING) {
            return [false, '当前状态不可审核'];
        }

        $dynamic->status = $approved ? self::STATUS_PUBLISHED : self::STATUS_REJECTED;
        $dynamic->audit_admin_id = $adminId;
        $dynamic->audit_time = time();
        $dynamic->audit_remark = $remark;
        $dynamic->update_time = time();
        $dynamic->save();

        return [true, $approved ? '审核通过' : '已拒绝'];
    }

    /**
     * @notes 增加浏览量
     * @param int $dynamicId
     * @return bool
     */
    public static function incrementView(int $dynamicId): bool
    {
        return self::where('id', $dynamicId)->inc('view_count')->update() > 0;
    }

    /**
     * @notes 获取动态列表（小程序端）
     * @param array $params
     * @param int $userId 当前用户ID
     * @return array
     */
    public static function getList(array $params, int $userId = 0): array
    {
        $query = self::where('status', self::STATUS_PUBLISHED);

        // 动态类型筛选
        if (!empty($params['dynamic_type'])) {
            $query->where('dynamic_type', $params['dynamic_type']);
        }

        // 用户筛选
        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }

        // 工作人员筛选
        if (!empty($params['staff_id'])) {
            $query->where('staff_id', $params['staff_id']);
        }

        // 标签筛选
        if (!empty($params['tag'])) {
            $query->where('tags', 'like', '%' . $params['tag'] . '%');
        }

        // 排序
        $orderBy = $params['order_by'] ?? 'create_time';
        $orderDir = $params['order_dir'] ?? 'desc';
        
        if ($orderBy == 'hot') {
            $query->order('is_hot', 'desc')->order('like_count', 'desc');
        } else {
            $query->order('is_top', 'desc')->order($orderBy, $orderDir);
        }

        $list = $query->with(['user' => function ($q) {
                $q->field('id, nickname, avatar');
            }, 'staff' => function ($q) {
                $q->field('id, name, avatar');
            }])
            ->paginate((int)($params['page_size'] ?? 10))
            ->toArray();

        // 处理用户信息显示
        foreach ($list['data'] as &$item) {
            if ($item['user_type'] == self::USER_TYPE_OFFICIAL) {
                // 官方动态
                $item['user'] = [
                    'id' => 0,
                    'nickname' => '官方',
                    'avatar' => $item['user']['avatar'] ?? ''
                ];
            } elseif ($item['user_type'] == self::USER_TYPE_STAFF && !empty($item['staff'])) {
                // 工作人员动态
                $item['user'] = [
                    'id' => $item['staff']['id'],
                    'nickname' => $item['staff']['name'],
                    'avatar' => $item['staff']['avatar']
                ];
            }
            // 移除 staff 字段，避免冗余
            unset($item['staff']);
        }

        // 添加是否点赞、收藏信息
        if ($userId > 0 && !empty($list['data'])) {
            $dynamicIds = array_column($list['data'], 'id');
            $likedIds = DynamicLike::where('user_id', $userId)
                ->where('target_type', 1)
                ->whereIn('target_id', $dynamicIds)
                ->column('target_id');
            $collectedIds = DynamicCollect::where('user_id', $userId)
                ->whereIn('dynamic_id', $dynamicIds)
                ->column('dynamic_id');

            foreach ($list['data'] as &$item) {
                $item['is_liked'] = in_array($item['id'], $likedIds);
                $item['is_collected'] = in_array($item['id'], $collectedIds);
            }
        }

        return $list;
    }

    /**
     * @notes 获取动态类型选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return [
            ['value' => self::TYPE_IMAGE_TEXT, 'label' => '图文'],
            ['value' => self::TYPE_VIDEO, 'label' => '视频'],
            ['value' => self::TYPE_CASE, 'label' => '案例分享'],
            ['value' => self::TYPE_ACTIVITY, 'label' => '活动'],
        ];
    }

    /**
     * @notes 获取状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            ['value' => self::STATUS_PENDING, 'label' => '待审核'],
            ['value' => self::STATUS_PUBLISHED, 'label' => '已发布'],
            ['value' => self::STATUS_OFFLINE, 'label' => '已下架'],
            ['value' => self::STATUS_REJECTED, 'label' => '审核拒绝'],
        ];
    }
}
