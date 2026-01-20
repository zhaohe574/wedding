<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 评价管理逻辑层
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\review;

use app\common\logic\BaseLogic;
use app\common\model\review\Review;
use app\common\model\review\ReviewReply;
use app\common\model\review\ReviewTag;
use app\common\model\review\StaffReviewStats;
use think\facade\Db;

/**
 * 评价管理逻辑层
 * Class ReviewLogic
 * @package app\adminapi\logic\review
 */
class ReviewLogic extends BaseLogic
{
    /**
     * @notes 评价详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $review = Review::with(['user', 'staff', 'order', 'orderItem', 'tags', 'replies'])
            ->find($id);
        
        if (!$review) {
            return [];
        }

        $data = $review->toArray();
        $data['status_text'] = Review::getStatusDesc($data['status']);
        $data['review_type_text'] = Review::getTypeDesc($data['review_type']);
        $data['score_level'] = Review::getScoreLevel($data['score']);
        
        return $data;
    }

    /**
     * @notes 审核评价
     * @param array $params
     * @return bool
     */
    public static function audit(array $params): bool
    {
        try {
            $review = Review::find($params['id']);
            if (!$review) {
                self::setError('评价不存在');
                return false;
            }

            if ($review->status != Review::STATUS_PENDING) {
                self::setError('该评价已审核');
                return false;
            }

            Db::startTrans();
            try {
                $updateData = [
                    'status' => $params['status'],
                    'admin_id' => $params['admin_id'],
                    'audit_time' => time(),
                ];

                if ($params['status'] == Review::STATUS_REJECTED) {
                    $updateData['reject_reason'] = $params['reject_reason'] ?? '';
                }

                $review->save($updateData);

                // 如果审核通过，更新人员统计
                if ($params['status'] == Review::STATUS_APPROVED) {
                    StaffReviewStats::recalculate($review->staff_id);
                }

                Db::commit();
                return true;
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
     * @notes 批量审核
     * @param array $params
     * @return bool
     */
    public static function batchAudit(array $params): bool
    {
        try {
            Db::startTrans();
            try {
                $staffIds = [];
                foreach ($params['ids'] as $id) {
                    $review = Review::find($id);
                    if ($review && $review->status == Review::STATUS_PENDING) {
                        $review->save([
                            'status' => $params['status'],
                            'admin_id' => $params['admin_id'],
                            'audit_time' => time(),
                            'reject_reason' => $params['reject_reason'] ?? '',
                        ]);
                        if ($params['status'] == Review::STATUS_APPROVED) {
                            $staffIds[] = $review->staff_id;
                        }
                    }
                }

                // 更新相关人员统计
                $staffIds = array_unique($staffIds);
                foreach ($staffIds as $staffId) {
                    StaffReviewStats::recalculate($staffId);
                }

                Db::commit();
                return true;
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
     * @notes 置顶/取消置顶
     * @param int $id
     * @return bool
     */
    public static function toggleTop(int $id): bool
    {
        try {
            $review = Review::find($id);
            if (!$review) {
                self::setError('评价不存在');
                return false;
            }

            $review->save([
                'is_top' => $review->is_top ? 0 : 1,
                'top_time' => $review->is_top ? 0 : time(),
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 显示/隐藏评价
     * @param int $id
     * @return bool
     */
    public static function toggleShow(int $id): bool
    {
        try {
            $review = Review::find($id);
            if (!$review) {
                self::setError('评价不存在');
                return false;
            }

            $review->save([
                'is_show' => $review->is_show ? 0 : 1,
            ]);

            // 更新人员统计
            StaffReviewStats::recalculate($review->staff_id);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除评价
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        try {
            $review = Review::find($id);
            if (!$review) {
                self::setError('评价不存在');
                return false;
            }

            $staffId = $review->staff_id;
            $review->delete();

            // 更新人员统计
            StaffReviewStats::recalculate($staffId);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 商家回复
     * @param array $params
     * @return bool
     */
    public static function reply(array $params): bool
    {
        try {
            $review = Review::find($params['id']);
            if (!$review) {
                self::setError('评价不存在');
                return false;
            }

            ReviewReply::createReply([
                'review_id' => $params['id'],
                'admin_id' => $params['admin_id'],
                'reply_type' => ReviewReply::TYPE_MERCHANT,
                'content' => $params['content'],
                'status' => ReviewReply::STATUS_APPROVED,
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 评价统计
     * @param array $params
     * @return array
     */
    public static function statistics(array $params): array
    {
        $where = [];
        if (!empty($params['start_date']) && !empty($params['end_date'])) {
            $where[] = ['create_time', '>=', strtotime($params['start_date'])];
            $where[] = ['create_time', '<=', strtotime($params['end_date'] . ' 23:59:59')];
        }

        // 总评价数
        $totalCount = Review::where($where)->count();

        // 待审核数
        $pendingCount = Review::where($where)
            ->where('status', Review::STATUS_PENDING)
            ->count();

        // 已通过数
        $approvedCount = Review::where($where)
            ->where('status', Review::STATUS_APPROVED)
            ->count();

        // 好评数
        $goodCount = Review::where($where)
            ->where('status', Review::STATUS_APPROVED)
            ->where('score', '>=', 4)
            ->count();

        // 中评数
        $mediumCount = Review::where($where)
            ->where('status', Review::STATUS_APPROVED)
            ->where('score', '=', 3)
            ->count();

        // 差评数
        $badCount = Review::where($where)
            ->where('status', Review::STATUS_APPROVED)
            ->where('score', '<=', 2)
            ->count();

        // 有图评价数
        $imageCount = Review::where($where)
            ->where('status', Review::STATUS_APPROVED)
            ->where('images', '<>', '')
            ->where('images', '<>', '[]')
            ->count();

        // 有视频评价数
        $videoCount = Review::where($where)
            ->where('status', Review::STATUS_APPROVED)
            ->where('video', '<>', '')
            ->count();

        // 平均分
        $avgScore = Review::where($where)
            ->where('status', Review::STATUS_APPROVED)
            ->avg('score') ?? 0;

        // 好评率
        $goodRate = $approvedCount > 0 ? round($goodCount / $approvedCount * 100, 2) : 0;

        return [
            'total_count' => $totalCount,
            'pending_count' => $pendingCount,
            'approved_count' => $approvedCount,
            'good_count' => $goodCount,
            'medium_count' => $mediumCount,
            'bad_count' => $badCount,
            'image_count' => $imageCount,
            'video_count' => $videoCount,
            'avg_score' => round($avgScore, 2),
            'good_rate' => $goodRate,
        ];
    }

    /**
     * @notes 人员评价排行
     * @param array $params
     * @return array
     */
    public static function staffRanking(array $params): array
    {
        $limit = $params['limit'] ?? 10;
        $orderBy = $params['order_by'] ?? 'avg_score';

        return StaffReviewStats::with(['staff'])
            ->where('total_count', '>', 0)
            ->order($orderBy, 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * @notes 评分分布统计
     * @param array $params
     * @return array
     */
    public static function scoreDistribution(array $params): array
    {
        $where = [['status', '=', Review::STATUS_APPROVED]];
        if (!empty($params['staff_id'])) {
            $where[] = ['staff_id', '=', $params['staff_id']];
        }

        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $count = Review::where($where)
                ->where('score', $i)
                ->count();
            $distribution[] = [
                'score' => $i,
                'count' => $count,
                'label' => $i . '星',
            ];
        }

        return $distribution;
    }

    /**
     * @notes 热门标签统计
     * @param array $params
     * @return array
     */
    public static function hotTags(array $params): array
    {
        $limit = $params['limit'] ?? 20;

        return ReviewTag::where('status', 1)
            ->order('use_count', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}
