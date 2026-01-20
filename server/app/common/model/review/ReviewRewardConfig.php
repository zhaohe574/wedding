<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 评价奖励配置模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\review;

use app\common\model\BaseModel;

/**
 * 评价奖励配置模型
 * Class ReviewRewardConfig
 * @package app\common\model\review
 */
class ReviewRewardConfig extends BaseModel
{
    protected $name = 'review_reward_config';

    // 奖励类型
    const TYPE_TEXT = 1;    // 文字评价
    const TYPE_IMAGE = 2;   // 图文评价
    const TYPE_VIDEO = 3;   // 视频评价

    /**
     * @notes 奖励类型描述
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
     * @notes 获取奖励配置
     * @param int $type
     * @return array|null
     */
    public static function getConfig(int $type): ?array
    {
        $config = self::where('reward_type', $type)
            ->where('status', 1)
            ->find();
        return $config ? $config->toArray() : null;
    }

    /**
     * @notes 获取所有奖励配置
     * @return array
     */
    public static function getAllConfig(): array
    {
        return self::where('status', 1)
            ->order('reward_type asc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 计算评价奖励积分
     * @param int $reviewType 评价类型
     * @param int $score 评分
     * @param int $contentLength 内容长度
     * @param int $imageCount 图片数量
     * @param int $videoDuration 视频时长
     * @return int 奖励积分
     */
    public static function calculateReward(
        int $reviewType, 
        int $score, 
        int $contentLength = 0, 
        int $imageCount = 0, 
        int $videoDuration = 0
    ): int {
        $config = self::getConfig($reviewType);
        if (!$config) {
            return 0;
        }

        // 检查是否满足基本要求
        if ($contentLength < $config['min_content_length']) {
            return 0;
        }

        if ($reviewType == self::TYPE_IMAGE && $imageCount < $config['min_images']) {
            return 0;
        }

        if ($reviewType == self::TYPE_VIDEO && $videoDuration < $config['min_video_duration']) {
            return 0;
        }

        // 基础奖励
        $reward = $config['reward_points'];

        // 好评额外奖励
        if ($score >= 4 && $config['extra_points_for_good'] > 0) {
            $reward += $config['extra_points_for_good'];
        }

        return $reward;
    }
}
