<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端评价逻辑层
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\ContentMediaCheckTask;
use app\common\model\review\Review;
use app\common\model\review\ReviewTag;
use app\common\model\review\ReviewTagRelation;
use app\common\model\review\ReviewReply;
use app\common\model\review\ReviewLike;
use app\common\model\review\StaffReviewStats;
use app\common\model\review\SensitiveWord;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\order\OrderLog;
use app\common\service\MediaCheckService;
use app\common\service\UserRiskControlService;
use app\common\service\WechatSecurityService;
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

        $orderId = (int)($params['order_id'] ?? 0);
        if ($orderId > 0) {
            $where[] = ['order_id', '=', $orderId];
        }

        $total = Review::where($where)->count();
        
        $lists = Review::with(['staff', 'order', 'orderItem'])
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
            $item = self::stripInternalFields(array_merge($item, self::buildReviewGuide($item)));
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

        $orderId = (int)($params['order_id'] ?? 0);
        if ($orderId > 0) {
            $where[] = ['order.id', '=', $orderId];
        }

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

            $item = self::stripInternalFields($item);
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
        $data = self::stripInternalFields(array_merge($data, self::buildReviewGuide($data)));

        // 检查是否已点赞
        $currentUserId = $params['current_user_id'] ?? 0;
        $data['is_liked'] = $currentUserId ? ReviewLike::isLiked($data['id'], $currentUserId) : false;
        $data['is_owner'] = $currentUserId > 0 && (int)$currentUserId === (int)$review->user_id;
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

            // 检查订单项与归属权限
            $orderItem = OrderItem::with(['order'])->find($orderItemId);
            if (!$orderItem || !$orderItem->order) {
                self::setError('订单项不存在');
                return false;
            }

            if ((int)$orderItem->order->user_id !== $userId) {
                self::setError('无权评价该订单');
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

            // ① 本地敏感词库：离线快路径，命中禁止级直接拒绝，不依赖接口可用性
            $content = trim((string)($params['content'] ?? ''));
            $wechatHit = false;
            $wechatCheckResult = [];
            if ($content !== '') {
                $filterResult = SensitiveWord::filter($content);
                if ($filterResult['has_sensitive'] && $filterResult['level'] >= SensitiveWord::LEVEL_FORBID) {
                    self::setError('评价内容包含敏感词，请修改后重试');
                    return false;
                }
                $content = $filterResult['filtered'];

                // ② 微信内容安全：仅用于“标记风险”，不用于拒绝。
                //    msgSecCheck 存在误判，命中后落待审交人工复核，与动态评论（DynamicComment::addComment）保持同一口径。
                //    注意文本检测接口是 fail-open：超时/异常返回 hit=false，不会被当成“合规”，
                //    因为评价本来就一律进待审，不会因 fail-open 被直接放行到前台。
                $wechatCheckResult = WechatSecurityService::checkText(
                    $userId,
                    $content,
                    WechatSecurityService::SCENE_COMMENT
                );
                $wechatHit = (bool)($wechatCheckResult['hit'] ?? false);
            }

            // ③ 高风险用户强制待审（评论已用同一判断）
            $forceReview = UserRiskControlService::shouldForceContentReview($userId);

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
                    'content' => $content,
                    'custom_tags' => $params['custom_tags'] ?? [],
                    'images' => $params['images'] ?? [],
                    'video' => $params['video'] ?? '',
                    'video_cover' => $params['video_cover'] ?? '',
                    'is_anonymous' => (int)($params['is_anonymous'] ?? 0),
                    'service_date' => $orderItem->order->service_date,
                    // 评价当前一律落待审（后台人工审核）；命中微信检测或高风险用户只补原因，
                    // 不会因为“没命中”就自动通过，也不会因为命中就丢掉用户内容。
                    'status' => Review::STATUS_PENDING,
                    'security_remark' => self::buildSecurityRemark($wechatHit, $forceReview, $wechatCheckResult),
                ];

                $review = Review::createReview($reviewData);

                if (!empty($params['tag_ids'])) {
                    $tagIds = array_values(array_unique(array_filter(array_map('intval', $params['tag_ids']))));
                    ReviewTagRelation::bindTags($review->id, $tagIds);
                }

                // 若该订单所有有效可评价服务项均已评价，则将订单状态推进至已评价
                $orderId = (int)$orderItem->order_id;
                $reviewableIds = OrderItem::where('order_id', $orderId)
                    ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                    ->whereIn('item_type', [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF])
                    ->column('id');
                if (empty($reviewableIds)) {
                    $reviewableIds = OrderItem::where('order_id', $orderId)
                        ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                        ->column('id');
                }
                $reviewableIds = array_values(array_unique(array_map('intval', $reviewableIds ?: [])));
                if (!empty($reviewableIds)) {
                    $reviewedIds = Review::where('user_id', $userId)
                        ->whereIn('order_item_id', $reviewableIds)
                        ->column('order_item_id');
                    $reviewedIds = array_values(array_unique(array_map('intval', $reviewedIds ?: [])));
                    $remaining = array_diff($reviewableIds, $reviewedIds);
                    if (empty($remaining)) {
                        $lockedOrder = Order::where('id', $orderId)->lock(true)->find();
                        if ($lockedOrder && (int)$lockedOrder->order_status === Order::STATUS_COMPLETED) {
                            $lockedOrder->order_status = Order::STATUS_REVIEWED;
                            $lockedOrder->update_time = time();
                            $lockedOrder->save();
                            OrderLog::addLog(
                                $orderId,
                                OrderLog::OPERATOR_USER,
                                $userId,
                                'review',
                                Order::STATUS_COMPLETED,
                                Order::STATUS_REVIEWED,
                                '用户完成订单评价'
                            );
                        }
                    }
                }

                Db::commit();

                // ④ 图片异步检测：提交即算“待回调”，命中/超时都只标记，不改变“待审”状态。
                //    必须放在 commit 之后：微信回调可能先于事务提交到达，需要能查到 biz_id。
                try {
                    $images = (array)($review->images ?? []);
                    if (!empty($images)) {
                        MediaCheckService::submit(
                            $userId,
                            $images,
                            ContentMediaCheckTask::BIZ_REVIEW,
                            (int)$review->id,
                            WechatSecurityService::SCENE_COMMENT
                        );
                    }
                } catch (\Throwable $e) {
                    // 图片检测失败不影响评价发布，评价仍在待审队列里
                    \think\facade\Log::warning('评价图片安全检测提交失败：review_id=' . $review->id . '，message=' . $e->getMessage());
                }

                return [
                    'review_id' => $review->id,
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

            $userId = (int)$params['user_id'];

            // ① 本地敏感词库：离线快路径
            $content = trim((string)($params['content'] ?? ''));
            $filterResult = SensitiveWord::filter($content);
            if ($filterResult['has_sensitive'] && $filterResult['level'] >= SensitiveWord::LEVEL_FORBID) {
                self::setError('追评内容包含敏感词');
                return false;
            }
            $content = $filterResult['filtered'];

            // ② 微信内容安全：命中不拒绝，只记日志；追评本来就一律 STATUS_PENDING
            $wechatCheckResult = $content === ''
                ? []
                : WechatSecurityService::checkText($userId, $content, WechatSecurityService::SCENE_COMMENT);
            $wechatHit = (bool)($wechatCheckResult['hit'] ?? false);

            // ③ 高风险用户强制待审；追评状态恒为待审，这里只记录判定结果便于排查
            $forceReview = UserRiskControlService::shouldForceContentReview($userId);
            if ($wechatHit || $forceReview) {
                \think\facade\Log::info(
                    '追评命中安全策略：review_id=' . $params['id']
                    . '，user_id=' . $userId
                    . '，wechat_hit=' . ($wechatHit ? '1' : '0')
                    . '，force_review=' . ($forceReview ? '1' : '0')
                    . '，suggest=' . (string)($wechatCheckResult['suggest'] ?? '')
                    . '，label=' . (int)($wechatCheckResult['label'] ?? 0)
                );
            }

            $reply = ReviewReply::createReply([
                'review_id' => $params['id'],
                'user_id' => $userId,
                'reply_type' => ReviewReply::TYPE_USER,
                'content' => $content,
                'images' => $params['images'] ?? [],
                'status' => ReviewReply::STATUS_PENDING,
            ]);

            // ③ 图片异步检测：追评图片同样对未登录用户公开，必须一起送检
            try {
                $images = (array)($reply->images ?? []);
                if (!empty($images)) {
                    MediaCheckService::submit(
                        $userId,
                        $images,
                        ContentMediaCheckTask::BIZ_REVIEW_REPLY,
                        (int)$reply->id,
                        WechatSecurityService::SCENE_COMMENT
                    );
                }
            } catch (\Throwable $e) {
                \think\facade\Log::warning('追评图片安全检测提交失败：reply_id=' . $reply->id . '，message=' . $e->getMessage());
            }

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
     * @notes 剔除只给后台看的字段，避免内容安全备注随公开接口外泄
     * @param array $review
     * @return array
     */
    private static function stripInternalFields(array $review): array
    {
        unset($review['security_remark']);
        return $review;
    }

    /**
     * @notes 构建审核备注（与动态评论 buildSecurityReviewRemark 同一口径）
     */
    private static function buildSecurityRemark(bool $wechatCheckHit, bool $forceReview, array $wechatCheckResult): string
    {
        if ($wechatCheckHit) {
            return sprintf(
                '微信文本安全检测命中：suggest=%s，label=%s，prob=%s，trace_id=%s',
                (string)($wechatCheckResult['suggest'] ?? ''),
                (string)($wechatCheckResult['label'] ?? ''),
                (string)($wechatCheckResult['prob'] ?? ''),
                (string)($wechatCheckResult['trace_id'] ?? '')
            );
        }

        if ($forceReview) {
            return '当前账号风险等级配置要求评价强制进入审核';
        }

        return '';
    }

    /**
     * @notes 构建评价审核状态
     */
    private static function buildReviewGuide(array $review): array
    {
        $status = (int)($review['status'] ?? Review::STATUS_PENDING);

        $statusSummary = '评价状态已更新，请留意审核结果。';
        if ($status === Review::STATUS_PENDING) {
            $statusSummary = '评价已提交，当前等待后台审核。';
        } elseif ($status === Review::STATUS_APPROVED) {
            $statusSummary = '评价已审核通过。';
        } elseif ($status === Review::STATUS_REJECTED) {
            $statusSummary = '评价未通过审核。';
        }

        return [
            'status_summary' => $statusSummary,
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
