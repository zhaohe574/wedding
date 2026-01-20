<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 评价模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\review;

use app\common\model\BaseModel;
use app\common\model\user\User;
use app\common\model\staff\Staff;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use think\model\concern\SoftDelete;

/**
 * 评价模型
 * Class Review
 * @package app\common\model\review
 */
class Review extends BaseModel
{
    use SoftDelete;

    protected $name = 'review';
    protected $deleteTime = 'delete_time';

    // 评价状态
    const STATUS_PENDING = 0;   // 待审核
    const STATUS_APPROVED = 1;  // 已通过
    const STATUS_REJECTED = 2;  // 已拒绝

    // 评价类型
    const TYPE_TEXT = 1;    // 文字评价
    const TYPE_IMAGE = 2;   // 图文评价
    const TYPE_VIDEO = 3;   // 视频评价

    // 评分等级
    const SCORE_GOOD = 4;       // 好评 4-5星
    const SCORE_MEDIUM = 3;     // 中评 3星
    const SCORE_BAD = 2;        // 差评 1-2星

    /**
     * @notes 状态描述
     * @param bool $value
     * @return array|string
     */
    public static function getStatusDesc($value = true)
    {
        $data = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_APPROVED => '已通过',
            self::STATUS_REJECTED => '已拒绝',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 类型描述
     * @param bool $value
     * @return array|string
     */
    public static function getTypeDesc($value = true)
    {
        $data = [
            self::TYPE_TEXT => '文字评价',
            self::TYPE_IMAGE => '图文评价',
            self::TYPE_VIDEO => '视频评价',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 获取评分等级
     * @param int $score
     * @return string
     */
    public static function getScoreLevel(int $score): string
    {
        if ($score >= 4) {
            return '好评';
        } elseif ($score == 3) {
            return '中评';
        } else {
            return '差评';
        }
    }

    /**
     * @notes 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->field('id,nickname,avatar,mobile');
    }

    /**
     * @notes 关联服务人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id')
            ->field('id,name,avatar,level_id');
    }

    /**
     * @notes 关联订单
     * @return \think\model\relation\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id')
            ->field('id,order_sn,service_date');
    }

    /**
     * @notes 关联订单项
     * @return \think\model\relation\BelongsTo
     */
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'id')
            ->field('id,staff_name,package_name,price');
    }

    /**
     * @notes 关联评价标签
     * @return \think\model\relation\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(ReviewTag::class, ReviewTagRelation::class, 'tag_id', 'review_id');
    }

    /**
     * @notes 关联回复
     * @return \think\model\relation\HasMany
     */
    public function replies()
    {
        return $this->hasMany(ReviewReply::class, 'review_id', 'id')
            ->where('status', ReviewReply::STATUS_APPROVED)
            ->order('create_time', 'asc');
    }

    /**
     * @notes 关联点赞
     * @return \think\model\relation\HasMany
     */
    public function likes()
    {
        return $this->hasMany(ReviewLike::class, 'review_id', 'id');
    }

    /**
     * @notes 关联申诉
     * @return \think\model\relation\HasMany
     */
    public function appeals()
    {
        return $this->hasMany(ReviewAppeal::class, 'review_id', 'id');
    }

    /**
     * @notes 图片获取器
     * @param $value
     * @return array
     */
    public function getImagesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @notes 图片设置器
     * @param $value
     * @return false|string
     */
    public function setImagesAttr($value)
    {
        return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
    }

    /**
     * @notes 状态文本获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusTextAttr($value, $data)
    {
        return self::getStatusDesc($data['status'] ?? 0);
    }

    /**
     * @notes 类型文本获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getReviewTypeTextAttr($value, $data)
    {
        return self::getTypeDesc($data['review_type'] ?? 1);
    }

    /**
     * @notes 评分等级获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getScoreLevelAttr($value, $data)
    {
        return self::getScoreLevel($data['score'] ?? 5);
    }

    /**
     * @notes 创建评价
     * @param array $data
     * @return Review
     */
    public static function createReview(array $data): Review
    {
        // 确定评价类型
        if (!empty($data['video'])) {
            $data['review_type'] = self::TYPE_VIDEO;
        } elseif (!empty($data['images']) && count($data['images']) > 0) {
            $data['review_type'] = self::TYPE_IMAGE;
        } else {
            $data['review_type'] = self::TYPE_TEXT;
        }

        // 计算综合评分（取平均值）
        if (isset($data['score_service']) && isset($data['score_professional']) 
            && isset($data['score_punctual']) && isset($data['score_effect'])) {
            $data['score'] = round(
                ($data['score_service'] + $data['score_professional'] 
                + $data['score_punctual'] + $data['score_effect']) / 4
            );
        }

        $review = new self();
        $review->save($data);
        
        return $review;
    }

    /**
     * @notes 检查用户是否已评价订单项
     * @param int $userId
     * @param int $orderItemId
     * @return bool
     */
    public static function hasReviewed(int $userId, int $orderItemId): bool
    {
        return self::where([
            'user_id' => $userId,
            'order_item_id' => $orderItemId,
        ])->count() > 0;
    }

    /**
     * @notes 检查用户是否已点赞
     * @param int $reviewId
     * @param int $userId
     * @return bool
     */
    public function isLikedBy(int $userId): bool
    {
        return ReviewLike::where([
            'review_id' => $this->id,
            'user_id' => $userId,
        ])->count() > 0;
    }

    /**
     * @notes 增加点赞数
     * @return void
     */
    public function incrementLike(): void
    {
        $this->inc('like_count')->update();
    }

    /**
     * @notes 减少点赞数
     * @return void
     */
    public function decrementLike(): void
    {
        $this->where('like_count', '>', 0)->dec('like_count')->update();
    }

    /**
     * @notes 增加回复数
     * @return void
     */
    public function incrementReply(): void
    {
        $this->inc('reply_count')->update();
    }
}
