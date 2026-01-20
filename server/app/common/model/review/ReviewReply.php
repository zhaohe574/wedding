<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 评价回复模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\review;

use app\common\model\BaseModel;
use app\common\model\user\User;
use app\common\model\staff\Staff;
use think\model\concern\SoftDelete;

/**
 * 评价回复模型
 * Class ReviewReply
 * @package app\common\model\review
 */
class ReviewReply extends BaseModel
{
    use SoftDelete;

    protected $name = 'review_reply';
    protected $deleteTime = 'delete_time';

    // 回复类型
    const TYPE_USER = 1;    // 用户追评
    const TYPE_MERCHANT = 2; // 商家回复
    const TYPE_STAFF = 3;   // 人员回复

    // 状态
    const STATUS_PENDING = 0;   // 待审核
    const STATUS_APPROVED = 1;  // 已通过
    const STATUS_REJECTED = 2;  // 已拒绝

    /**
     * @notes 回复类型描述
     * @param bool $value
     * @return array|string
     */
    public static function getTypeDesc($value = true)
    {
        $data = [
            self::TYPE_USER => '用户追评',
            self::TYPE_MERCHANT => '商家回复',
            self::TYPE_STAFF => '人员回复',
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
     * @notes 关联服务人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id')
            ->field('id,name,avatar');
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
     * @notes 类型文本获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getReplyTypeTextAttr($value, $data)
    {
        return self::getTypeDesc($data['reply_type'] ?? 1);
    }

    /**
     * @notes 创建回复
     * @param array $data
     * @return ReviewReply
     */
    public static function createReply(array $data): ReviewReply
    {
        $reply = new self();
        $reply->save($data);

        // 更新评价回复数
        Review::where('id', $data['review_id'])->inc('reply_count')->update();

        return $reply;
    }
}
