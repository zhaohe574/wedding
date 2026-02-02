<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端评价逻辑层
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\review\Review;
use app\common\model\review\ReviewTag;
use app\common\model\review\ReviewTagRelation;
use app\common\model\review\ReviewReply;
use app\common\model\review\ReviewLike;
use app\common\model\review\ReviewAppeal;
use app\common\model\review\ReviewRewardConfig;
use app\common\model\review\ReviewShareReward;
use app\common\model\review\StaffReviewStats;
use app\common\model\review\SensitiveWord;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use think\facade\Db;

/**
 * 小程序端评价逻辑层
 * Class ReviewLogic
 * @package app\api\logic
 */
class ReviewLogic extends BaseLogic
{
    /**
     * @notes 我的评价列表
     * @param array $params
     * @return array
     */
    public static function myReviews(array $params): array
    {
        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 10);

        $where = [
            ['user_id', '=', $params['user_id']],
        ];

        $total = Review::where($where)->count();
        
        $lists = Review::with(['staff', 'orderItem'])
            ->where($where)
            ->order('create_time desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['status_text'] = Review::getStatusDesc($item['status']);
            $item['score_level'] = Review::getScoreLevel($item['score']);
            $item['create_time_text'] = date('Y-m-d H:i', $item['create_time']);
        }

        return [
            'lists' => $lists,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'has_more' => $total > $page * $limit,
        ];
    }

    /**
     * @notes 待评价订单列表
     * @param array $params
     * @return array
     */
    public static function pendingOrders(array $params): array
    {
        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 10);

        // 获取已评价的订单项ID
        $reviewedItemIds = Review::where('user_id', $params['user_id'])
            ->column('order_item_id');

        // 查询已完成但未评价的订单项
        $where = [
            ['order.user_id', '=', $params['user_id']],
            ['order.status', '=', Order::STATUS_COMPLETED],
        ];

        $query = OrderItem::alias('item')
            ->join('order', 'order.id = item.order_id')
            ->with(['staff', 'order'])
            ->where($where);

        if (!empty($reviewedItemIds)) {
            $query->whereNotIn('item.id', $reviewedItemIds);
        }

        $total = $query->count();
        
        $lists = $query->field('item.*')
            ->order('order.complete_time desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return [
            'lists' => $lists,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'has_more' => $total > $page * $limit,
        ];
    }

    /**
     * @notes 服务人员评价列表
     * @param array $params
     * @return array
     */
    public static function staffReviews(array $params): array
    {
        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 10);
        $staffId = (int)($params['staff_id'] ?? 0);
        $scoreType = $params['score_type'] ?? ''; // good/medium/bad/image/video

        $where = [
            ['staff_id', '=', $staffId],
            ['status', '=', Review::STATUS_APPROVED],
            ['is_show', '=', 1],
        ];

        // 评分类型筛选
        switch ($scoreType) {
            case 'good':
                $where[] = ['score', '>=', 4];
                break;
            case 'medium':
                $where[] = ['score', '=', 3];
                break;
            case 'bad':
                $where[] = ['score', '<=', 2];
                break;
            case 'image':
                $where[] = ['images', '<>', ''];
                $where[] = ['images', '<>', '[]'];
                break;
            case 'video':
                $where[] = ['video', '<>', ''];
                break;
        }

        $total = Review::where($where)->count();
        
        $lists = Review::with(['user', 'tags', 'replies'])
            ->where($where)
            ->order('is_top desc, create_time desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        // 检查当前用户是否已点赞
        $currentUserId = $params['current_user_id'] ?? 0;
        $reviewIds = array_column($lists, 'id');
        $likedIds = $currentUserId ? ReviewLike::batchCheck($reviewIds, $currentUserId) : [];

        foreach ($lists as &$item) {
            $item['score_level'] = Review::getScoreLevel($item['score']);
            $item['create_time_text'] = date('Y-m-d', $item['create_time']);
            $item['is_liked'] = in_array($item['id'], $likedIds);
            
            // 匿名处理
            if ($item['is_anonymous'] && $item['user']) {
                $item['user']['nickname'] = self::anonymousName($item['user']['nickname']);
                $item['user']['avatar'] = '';
            }
        }

        return [
            'lists' => $lists,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'has_more' => $total > $page * $limit,
        ];
    }

    /**
     * @notes 评价详情
     * @param array $params
     * @return array|false
     */
    public static function detail(array $params)
    {
        $review = Review::with(['user', 'staff', 'order', 'orderItem', 'tags', 'replies'])
            ->find($params['id']);

        if (!$review) {
            self::setError('评价不存在');
            return false;
        }

        $data = $review->toArray();
        $data['status_text'] = Review::getStatusDesc($data['status']);
        $data['score_level'] = Review::getScoreLevel($data['score']);
        $data['create_time_text'] = date('Y-m-d H:i', $data['create_time']);

        // 检查是否已点赞
        $currentUserId = $params['current_user_id'] ?? 0;
        $data['is_liked'] = $currentUserId ? ReviewLike::isLiked($data['id'], $currentUserId) : false;

        // 匿名处理
        if ($data['is_anonymous'] && $data['user']) {
            $data['user']['nickname'] = self::anonymousName($data['user']['nickname']);
            $data['user']['avatar'] = '';
        }

        return $data;
    }

    /**
     * @notes 发布评价
     * @param array $params
     * @return array|false
     */
    public static function publish(array $params)
    {
        try {
            // 检查订单项
            $orderItem = OrderItem::with(['order'])->find($params['order_item_id']);
            if (!$orderItem) {
                self::setError('订单项不存在');
                return false;
            }

            // 检查订单状态
            if ($orderItem->order->status != Order::STATUS_COMPLETED) {
                self::setError('订单未完成，不能评价');
                return false;
            }

            // 检查是否已评价
            if (Review::hasReviewed($params['user_id'], $params['order_item_id'])) {
                self::setError('您已评价过此订单');
                return false;
            }

            // 敏感词检测
            if (!empty($params['content'])) {
                $filterResult = SensitiveWord::filter($params['content']);
                if ($filterResult['has_sensitive'] && $filterResult['level'] >= 2) {
                    self::setError('评价内容包含敏感词，请修改后重试');
                    return false;
                }
                $params['content'] = $filterResult['filtered'];
            }

            Db::startTrans();
            try {
                // 创建评价
                $reviewData = [
                    'order_id' => $orderItem->order_id,
                    'order_item_id' => $params['order_item_id'],
                    'user_id' => $params['user_id'],
                    'staff_id' => $orderItem->staff_id,
                    'score' => $params['score'] ?? 5,
                    'score_service' => $params['score_service'] ?? $params['score'] ?? 5,
                    'score_professional' => $params['score_professional'] ?? $params['score'] ?? 5,
                    'score_punctual' => $params['score_punctual'] ?? $params['score'] ?? 5,
                    'score_effect' => $params['score_effect'] ?? $params['score'] ?? 5,
                    'content' => $params['content'] ?? '',
                    'images' => $params['images'] ?? [],
                    'video' => $params['video'] ?? '',
                    'video_cover' => $params['video_cover'] ?? '',
                    'is_anonymous' => $params['is_anonymous'] ?? 0,
                    'service_date' => $orderItem->order->service_date,
                    'status' => Review::STATUS_PENDING, // 默认待审核
                ];

                $review = Review::createReview($reviewData);

                // 绑定标签
                if (!empty($params['tag_ids'])) {
                    ReviewTagRelation::bindTags($review->id, $params['tag_ids']);
                }

                // 计算并设置奖励积分
                $rewardPoints = ReviewRewardConfig::calculateReward(
                    $review->review_type,
                    $review->score,
                    mb_strlen($review->content),
                    count($review->images),
                    0 // TODO: 视频时长
                );
                if ($rewardPoints > 0) {
                    $review->save(['reward_points' => $rewardPoints]);
                }

                Db::commit();

                return [
                    'review_id' => $review->id,
                    'reward_points' => $rewardPoints,
                ];
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 追评
     * @param array $params
     * @return bool
     */
    public static function append(array $params): bool
    {
        try {
            $review = Review::find($params['id']);
            if (!$review) {
                self::setError('评价不存在');
                return false;
            }

            if ($review->user_id != $params['user_id']) {
                self::setError('只能追评自己的评价');
                return false;
            }

            // 敏感词检测
            $filterResult = SensitiveWord::filter($params['content']);
            if ($filterResult['has_sensitive'] && $filterResult['level'] >= 2) {
                self::setError('追评内容包含敏感词');
                return false;
            }

            ReviewReply::createReply([
                'review_id' => $params['id'],
                'user_id' => $params['user_id'],
                'reply_type' => ReviewReply::TYPE_USER,
                'content' => $filterResult['filtered'],
                'images' => $params['images'] ?? [],
                'status' => ReviewReply::STATUS_PENDING,
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 点赞/取消点赞
     * @param int $reviewId
     * @param int $userId
     * @return bool
     */
    public static function toggleLike(int $reviewId, int $userId): bool
    {
        return ReviewLike::toggle($reviewId, $userId);
    }

    /**
     * @notes 根据评分获取标签
     * @param int $score
     * @return array
     */
    public static function getTagsByScore(int $score): array
    {
        return ReviewTag::getTagsByScore($score);
    }

    /**
     * @notes 获取评价奖励规则
     * @return array
     */
    public static function getRewardRules(): array
    {
        return ReviewRewardConfig::getAllConfig();
    }

    /**
     * @notes 申请晒单奖励
     * @param array $params
     * @return bool
     */
    public static function applyShareReward(array $params): bool
    {
        try {
            $review = Review::find($params['review_id']);
            if (!$review) {
                self::setError('评价不存在');
                return false;
            }

            if ($review->user_id != $params['user_id']) {
                self::setError('只能为自己的评价申请奖励');
                return false;
            }

            // 检查是否已申请
            $exists = ReviewShareReward::where([
                'review_id' => $params['review_id'],
                'user_id' => $params['user_id'],
                'share_platform' => $params['share_platform'],
            ])->find();

            if ($exists) {
                self::setError('该平台已申请过奖励');
                return false;
            }

            ReviewShareReward::createReward([
                'review_id' => $params['review_id'],
                'user_id' => $params['user_id'],
                'share_platform' => $params['share_platform'],
                'share_url' => $params['share_url'] ?? '',
                'verify_image' => $params['verify_image'] ?? '',
                'reward_points' => 20, // 晒单奖励积分
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 服务人员评价统计
     * @param int $staffId
     * @return array
     */
    public static function staffStats(int $staffId): array
    {
        $stats = StaffReviewStats::where('staff_id', $staffId)->find();
        
        if (!$stats) {
            // 重新计算
            StaffReviewStats::recalculate($staffId);
            $stats = StaffReviewStats::where('staff_id', $staffId)->find();
        }

        return $stats ? $stats->toArray() : [
            'total_count' => 0,
            'good_count' => 0,
            'medium_count' => 0,
            'bad_count' => 0,
            'image_count' => 0,
            'video_count' => 0,
            'avg_score' => 5.00,
            'good_rate' => 100,
        ];
    }

    /**
     * @notes 提交申诉
     * @param array $params
     * @return bool
     */
    public static function submitAppeal(array $params): bool
    {
        try {
            $review = Review::find($params['review_id']);
            if (!$review) {
                self::setError('评价不存在');
                return false;
            }

            // 检查是否已申诉
            $exists = ReviewAppeal::where([
                'review_id' => $params['review_id'],
                'appeal_user_id' => $params['appeal_user_id'],
                'status' => ReviewAppeal::STATUS_PENDING,
            ])->find();

            if ($exists) {
                self::setError('已有待处理的申诉');
                return false;
            }

            ReviewAppeal::createAppeal([
                'review_id' => $params['review_id'],
                'appeal_user_id' => $params['appeal_user_id'],
                'appeal_type' => $params['appeal_type'],
                'appeal_reason' => $params['appeal_reason'],
                'evidence_images' => $params['evidence_images'] ?? [],
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 匿名处理昵称
     * @param string $nickname
     * @return string
     */
    private static function anonymousName(string $nickname): string
    {
        if (empty($nickname)) {
            return '匿名用户';
        }
        $len = mb_strlen($nickname);
        if ($len <= 2) {
            return mb_substr($nickname, 0, 1) . '*';
        }
        return mb_substr($nickname, 0, 1) . str_repeat('*', $len - 2) . mb_substr($nickname, -1);
    }
}
