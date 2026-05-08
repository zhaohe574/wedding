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
            $item['create_time_text'] = self::formatReviewTime($item['create_time'] ?? 0, 'Y-m-d H:i');
            $item['tags'] = self::buildDisplayTags($item);
            $item = array_merge($item, self::buildReviewGuide($item));
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
            ['order.order_status', '=', Order::STATUS_COMPLETED],
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
                $where[] = ['score', '>=', 3];
                $where[] = ['score', '<', 4];
                break;
            case 'bad':
                $where[] = ['score', '<', 3];
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
            $item['create_time_text'] = self::formatReviewTime($item['create_time'] ?? 0, 'Y-m-d');
            $item['is_liked'] = in_array($item['id'], $likedIds);
            $item['tags'] = self::buildDisplayTags($item);
            
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
        $data['create_time_text'] = self::formatReviewTime($data['create_time'] ?? 0, 'Y-m-d H:i');
        $data['tags'] = self::buildDisplayTags($data);
        $data = array_merge($data, self::buildReviewGuide($data));

        // 检查是否已点赞
        $currentUserId = $params['current_user_id'] ?? 0;
        $data['is_liked'] = $currentUserId ? ReviewLike::isLiked($data['id'], $currentUserId) : false;
        $data['is_owner'] = $currentUserId > 0 && (int)$currentUserId === (int)$review->user_id;
        $data['can_apply_share_reward'] = false;

        if ($data['is_owner']) {
            $shareRewards = ReviewShareReward::where('review_id', $review->id)
                ->where('user_id', $review->user_id)
                ->order('id', 'desc')
                ->select();

            $data['share_reward_records'] = [];
            foreach ($shareRewards as $shareReward) {
                $data['share_reward_records'][] = [
                    'id' => (int)$shareReward->id,
                    'share_platform' => (string)$shareReward->share_platform,
                    'platform_text' => ReviewShareReward::getPlatformDesc($shareReward->share_platform),
                    'status' => (int)$shareReward->status,
                    'status_text' => ReviewShareReward::getStatusDesc($shareReward->status),
                    'reward_points' => (int)$shareReward->reward_points,
                    'verify_image' => (string)$shareReward->verify_image,
                    'audit_time' => (int)$shareReward->audit_time,
                ];
            }
        }

        // 匿名处理
        if ($data['is_anonymous'] && $data['user'] && !$data['is_owner']) {
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
            $userId = (int)($params['user_id'] ?? 0);
            $orderItemId = (int)($params['order_item_id'] ?? 0);
            $score = (float)($params['score'] ?? 5);
            $scoreService = (int)($params['score_service'] ?? $score);
            $scoreProfessional = (int)($params['score_professional'] ?? $score);
            $scorePunctual = (int)($params['score_punctual'] ?? $score);
            $scoreEffect = (int)($params['score_effect'] ?? $score);

            // 检查订单项
            $orderItem = OrderItem::with(['order'])->find($orderItemId);
            if (!$orderItem) {
                self::setError('订单项不存在');
                return false;
            }

            // 检查订单状态
            if ((int)$orderItem->order->order_status !== Order::STATUS_COMPLETED) {
                self::setError('订单未完成，不能评价');
                return false;
            }

            // 检查是否已评价
            if (Review::hasReviewed($userId, $orderItemId)) {
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
                    'order_item_id' => $orderItemId,
                    'user_id' => $userId,
                    'staff_id' => $orderItem->staff_id,
                    'score' => $score,
                    'score_service' => $scoreService,
                    'score_professional' => $scoreProfessional,
                    'score_punctual' => $scorePunctual,
                    'score_effect' => $scoreEffect,
                    'content' => $params['content'] ?? '',
                    'custom_tags' => $params['custom_tags'] ?? [],
                    'images' => $params['images'] ?? [],
                    'video' => $params['video'] ?? '',
                    'video_cover' => $params['video_cover'] ?? '',
                    'is_anonymous' => (int)($params['is_anonymous'] ?? 0),
                    'service_date' => $orderItem->order->service_date,
                    'status' => Review::STATUS_PENDING, // 默认待审核
                ];

                $review = Review::createReview($reviewData);

                if (!empty($params['tag_ids'])) {
                    $tagIds = array_values(array_unique(array_filter(array_map('intval', $params['tag_ids']))));
                    ReviewTagRelation::bindTags($review->id, $tagIds);
                }

                Db::commit();

                return [
                    'review_id' => $review->id,
                    'reward_points' => 0,
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
        return [];
    }

    /**
     * @notes 申请晒单奖励
     * @param array $params
     * @return bool
     */
    public static function applyShareReward(array $params): bool
    {
        self::setError('晒单奖励功能已关闭');
        return false;
    }

    /**
     * @notes 服务人员评价统计
     * @param int $staffId
     * @return array
     */
    public static function staffStats(int $staffId): array
    {
        $reviewStartDate = date('Y-m-d', strtotime('-1 year'));
        $data = Review::where('staff_id', $staffId)
            ->where('status', Review::STATUS_APPROVED)
            ->where('is_show', 1)
            ->where('service_date', '>=', $reviewStartDate)
            ->whereNull('delete_time')
            ->field([
                'COUNT(*) as total_count',
                'SUM(CASE WHEN score >= 4 THEN 1 ELSE 0 END) as good_count',
                'SUM(CASE WHEN score >= 3 AND score < 4 THEN 1 ELSE 0 END) as medium_count',
                'SUM(CASE WHEN score < 3 THEN 1 ELSE 0 END) as bad_count',
                'SUM(CASE WHEN images != "" AND images != "[]" THEN 1 ELSE 0 END) as image_count',
                'SUM(CASE WHEN video != "" THEN 1 ELSE 0 END) as video_count',
                'AVG(score) as avg_score',
            ])
            ->find();

        $totalCount = (int)($data['total_count'] ?? 0);
        $goodCount = (int)($data['good_count'] ?? 0);

        return [
            'total_count' => $totalCount,
            'good_count' => $goodCount,
            'medium_count' => (int)($data['medium_count'] ?? 0),
            'bad_count' => (int)($data['bad_count'] ?? 0),
            'image_count' => (int)($data['image_count'] ?? 0),
            'video_count' => (int)($data['video_count'] ?? 0),
            'avg_score' => $totalCount > 0 ? round((float)($data['avg_score'] ?? 0), 2) : 0.00,
            'good_rate' => $totalCount > 0 ? round($goodCount / $totalCount * 100, 2) : 0,
        ];
    }

    /**
     * @notes 构建评价状态与奖励说明
     */
    private static function buildReviewGuide(array $review): array
    {
        $status = (int)($review['status'] ?? Review::STATUS_PENDING);
        $rewardGrantTime = (int)($review['reward_grant_time'] ?? 0);
        $rewardPoints = (int)($review['reward_points'] ?? 0);

        $statusSummary = '评价状态已更新，请留意审核结果。';
        if ($status === Review::STATUS_PENDING) {
            $statusSummary = '评价已提交，当前等待后台审核。';
        } elseif ($status === Review::STATUS_APPROVED) {
            $statusSummary = '评价已审核通过。';
        } elseif ($status === Review::STATUS_REJECTED) {
            $statusSummary = '评价未通过审核。';
        }

        $rewardStatusText = '';
        $rewardSummary = '';
        if ($rewardGrantTime > 0) {
            $rewardStatusText = '已发放';
            $rewardSummary = sprintf('历史奖励积分已发放，共 %d 积分。', $rewardPoints);
        }

        return [
            'status_summary' => $statusSummary,
            'reward_status_text' => $rewardStatusText,
            'reward_summary' => $rewardSummary,
        ];
    }

    /**
     * @notes 构建前端展示标签
     */
    private static function buildDisplayTags(array $review): array
    {
        $tags = [];
        foreach (($review['tags'] ?? []) as $tag) {
            if (!empty($tag['name'])) {
                $tags[] = $tag;
            }
        }

        $customTags = $review['custom_tags'] ?? [];
        if (is_string($customTags)) {
            $decoded = json_decode($customTags, true);
            $customTags = is_array($decoded) ? $decoded : [];
        }

        foreach ($customTags as $index => $name) {
            $name = trim((string)$name);
            if ($name === '') {
                continue;
            }
            $tags[] = [
                'id' => 'custom_' . $index,
                'name' => $name,
                'is_custom' => 1,
            ];
        }

        return $tags;
    }

    /**
     * @notes 格式化评价时间，兼容模型返回的时间戳或日期字符串
     * @param mixed $value
     * @param string $format
     * @return string
     */
    private static function formatReviewTime($value, string $format): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_int($value)) {
            $timestamp = $value;
        } elseif (is_numeric($value)) {
            $timestamp = (int)$value;
        } else {
            $timestamp = strtotime((string)$value);
        }

        return $timestamp ? date($format, $timestamp) : '';
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
