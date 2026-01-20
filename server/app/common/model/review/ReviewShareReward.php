<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 晒单奖励记录模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\review;

use app\common\model\BaseModel;
use app\common\model\user\User;

/**
 * 晒单奖励记录模型
 * Class ReviewShareReward
 * @package app\common\model\review
 */
class ReviewShareReward extends BaseModel
{
    protected $name = 'review_share_reward';

    // 状态
    const STATUS_PENDING = 0;   // 待审核
    const STATUS_APPROVED = 1;  // 已通过
    const STATUS_REJECTED = 2;  // 已拒绝

    // 分享平台
    const PLATFORM_WECHAT = 'wechat';       // 微信好友
    const PLATFORM_MOMENTS = 'moments';     // 朋友圈
    const PLATFORM_WEIBO = 'weibo';         // 微博
    const PLATFORM_DOUYIN = 'douyin';       // 抖音
    const PLATFORM_XIAOHONGSHU = 'xiaohongshu'; // 小红书

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
     * @notes 平台描述
     * @param bool $value
     * @return array|string
     */
    public static function getPlatformDesc($value = true)
    {
        $data = [
            self::PLATFORM_WECHAT => '微信好友',
            self::PLATFORM_MOMENTS => '朋友圈',
            self::PLATFORM_WEIBO => '微博',
            self::PLATFORM_DOUYIN => '抖音',
            self::PLATFORM_XIAOHONGSHU => '小红书',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 关联评价
     * @return \think\model\relation\BelongsTo
     */
    public function review()
    {
        return $this->belongsTo(Review::class, 'review_id', 'id');
    }

    /**
     * @notes 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->field('id,nickname,avatar');
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
     * @notes 平台文本获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPlatformTextAttr($value, $data)
    {
        return self::getPlatformDesc($data['share_platform'] ?? '');
    }

    /**
     * @notes 创建晒单奖励申请
     * @param array $data
     * @return ReviewShareReward
     */
    public static function createReward(array $data): ReviewShareReward
    {
        $reward = new self();
        $reward->save($data);
        return $reward;
    }

    /**
     * @notes 审核通过
     * @param int $adminId
     * @return void
     */
    public function approve(int $adminId): void
    {
        $this->save([
            'status' => self::STATUS_APPROVED,
            'admin_id' => $adminId,
            'audit_time' => time(),
        ]);

        // TODO: 发放积分奖励
    }

    /**
     * @notes 审核拒绝
     * @param int $adminId
     * @return void
     */
    public function reject(int $adminId): void
    {
        $this->save([
            'status' => self::STATUS_REJECTED,
            'admin_id' => $adminId,
            'audit_time' => time(),
        ]);
    }
}
