<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员评价统计模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\review;

use app\common\model\BaseModel;
use app\common\model\staff\Staff;
use think\facade\Db;

/**
 * 服务人员评价统计模型
 * Class StaffReviewStats
 * @package app\common\model\review
 */
class StaffReviewStats extends BaseModel
{
    protected $name = 'staff_review_stats';
    protected $createTime = false;

    /**
     * @notes 关联服务人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    /**
     * @notes 获取或创建统计记录
     * @param int $staffId
     * @return StaffReviewStats
     */
    public static function getOrCreate(int $staffId): StaffReviewStats
    {
        $stats = self::where('staff_id', $staffId)->find();
        if (!$stats) {
            $stats = new self();
            $stats->staff_id = $staffId;
            $stats->save();
        }
        return $stats;
    }

    /**
     * @notes 重新计算人员评价统计
     * @param int $staffId
     * @return void
     */
    public static function recalculate(int $staffId): void
    {
        $stats = self::getOrCreate($staffId);

        // 统计各类评价数量
        $data = Review::where('staff_id', $staffId)
            ->where('status', Review::STATUS_APPROVED)
            ->field([
                'COUNT(*) as total_count',
                'SUM(CASE WHEN score >= 4 THEN 1 ELSE 0 END) as good_count',
                'SUM(CASE WHEN score >= 3 AND score < 4 THEN 1 ELSE 0 END) as medium_count',
                'SUM(CASE WHEN score < 3 THEN 1 ELSE 0 END) as bad_count',
                'SUM(CASE WHEN images != "" AND images != "[]" THEN 1 ELSE 0 END) as image_count',
                'SUM(CASE WHEN video != "" THEN 1 ELSE 0 END) as video_count',
                'AVG(score) as avg_score',
                'AVG(score_service) as avg_score_service',
                'AVG(score_professional) as avg_score_professional',
                'AVG(score_punctual) as avg_score_punctual',
                'AVG(score_effect) as avg_score_effect',
            ])
            ->find();

        if (!$data || (int)($data['total_count'] ?? 0) === 0) {
            $stats->save([
                'total_count' => 0,
                'good_count' => 0,
                'medium_count' => 0,
                'bad_count' => 0,
                'image_count' => 0,
                'video_count' => 0,
                'avg_score' => 0,
                'avg_score_service' => 0,
                'avg_score_professional' => 0,
                'avg_score_punctual' => 0,
                'avg_score_effect' => 0,
                'good_rate' => 0,
                'reply_rate' => 0,
                'update_time' => time(),
            ]);
            Staff::refreshServiceStats($staffId);
            return;
        }

        $totalCount = (int)($data['total_count'] ?? 0);
        $goodCount = (int)($data['good_count'] ?? 0);
        $mediumCount = (int)($data['medium_count'] ?? 0);
        $badCount = (int)($data['bad_count'] ?? 0);
        $imageCount = (int)($data['image_count'] ?? 0);
        $videoCount = (int)($data['video_count'] ?? 0);
        $avgScore = (float)($data['avg_score'] ?? 0);
        $avgScoreService = (float)($data['avg_score_service'] ?? 0);
        $avgScoreProfessional = (float)($data['avg_score_professional'] ?? 0);
        $avgScorePunctual = (float)($data['avg_score_punctual'] ?? 0);
        $avgScoreEffect = (float)($data['avg_score_effect'] ?? 0);

        // 计算好评率
        $goodRate = $totalCount > 0
            ? round($goodCount / $totalCount * 100, 2)
            : 0;

        // 计算回复率
        $repliedCount = Review::where('staff_id', $staffId)
            ->where('status', Review::STATUS_APPROVED)
            ->where('reply_count', '>', 0)
            ->count();
        $replyRate = $totalCount > 0
            ? round((int)$repliedCount / $totalCount * 100, 2)
            : 0;

        $stats->save([
            'total_count' => $totalCount,
            'good_count' => $goodCount,
            'medium_count' => $mediumCount,
            'bad_count' => $badCount,
            'image_count' => $imageCount,
            'video_count' => $videoCount,
            'avg_score' => round($avgScore, 2),
            'avg_score_service' => round($avgScoreService, 2),
            'avg_score_professional' => round($avgScoreProfessional, 2),
            'avg_score_punctual' => round($avgScorePunctual, 2),
            'avg_score_effect' => round($avgScoreEffect, 2),
            'good_rate' => $goodRate,
            'reply_rate' => $replyRate,
            'update_time' => time(),
        ]);

        Staff::refreshServiceStats($staffId);
    }

    /**
     * @notes 批量重新计算所有人员统计
     * @return int 处理数量
     */
    public static function recalculateAll(): int
    {
        $staffIds = Staff::whereNull('delete_time')->column('id');
        foreach ($staffIds as $staffId) {
            self::recalculate($staffId);
        }
        return count($staffIds);
    }
}
