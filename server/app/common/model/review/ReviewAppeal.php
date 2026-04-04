<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 评价申诉模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\review;

use app\common\model\BaseModel;
use app\common\model\user\User;
use app\common\model\staff\Staff;

/**
 * 评价申诉模型
 * Class ReviewAppeal
 * @package app\common\model\review
 */
class ReviewAppeal extends BaseModel
{
    protected $name = 'review_appeal';

    // 申诉类型
    const TYPE_MALICIOUS = 1;   // 恶意差评
    const TYPE_FAKE = 2;        // 虚假评价
    const TYPE_PRIVACY = 3;     // 侵犯隐私
    const TYPE_OTHER = 4;       // 其他

    // 状态
    const STATUS_PENDING = 0;   // 待处理
    const STATUS_APPROVED = 1;  // 已通过
    const STATUS_REJECTED = 2;  // 已驳回

    // 处理动作
    const ACTION_NONE = 0;      // 无
    const ACTION_DELETE = 1;    // 删除评价
    const ACTION_HIDE = 2;      // 隐藏评价
    const ACTION_WARN = 3;      // 警告用户

    /**
     * @notes 申诉类型描述
     * @param bool $value
     * @return array|string
     */
    public static function getTypeDesc($value = true)
    {
        $data = [
            self::TYPE_MALICIOUS => '恶意差评',
            self::TYPE_FAKE => '虚假评价',
            self::TYPE_PRIVACY => '侵犯隐私',
            self::TYPE_OTHER => '其他',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 状态描述
     * @param bool $value
     * @return array|string
     */
    public static function getStatusDesc($value = true)
    {
        $data = [
            self::STATUS_PENDING => '待处理',
            self::STATUS_APPROVED => '已通过',
            self::STATUS_REJECTED => '已驳回',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 处理动作描述
     * @param bool $value
     * @return array|string
     */
    public static function getActionDesc($value = true)
    {
        $data = [
            self::ACTION_NONE => '无',
            self::ACTION_DELETE => '删除评价',
            self::ACTION_HIDE => '隐藏评价',
            self::ACTION_WARN => '警告用户',
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
     * @notes 关联申诉用户
     * @return \think\model\relation\BelongsTo
     */
    public function appealUser()
    {
        return $this->belongsTo(User::class, 'appeal_user_id', 'id')
            ->field('id,nickname,avatar');
    }

    /**
     * @notes 关联申诉人员
     * @return \think\model\relation\BelongsTo
     */
    public function appealStaff()
    {
        return $this->belongsTo(Staff::class, 'appeal_staff_id', 'id')
            ->field('id,name,avatar');
    }

    /**
     * @notes 证据图片获取器
     * @param $value
     * @return array
     */
    public function getEvidenceImagesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @notes 证据图片设置器
     * @param $value
     * @return false|string
     */
    public function setEvidenceImagesAttr($value)
    {
        return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
    }

    /**
     * @notes 类型文本获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getAppealTypeTextAttr($value, $data)
    {
        return self::getTypeDesc($data['appeal_type'] ?? 1);
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
     * @notes 创建申诉
     * @param array $data
     * @return ReviewAppeal
     */
    public static function createAppeal(array $data): ReviewAppeal
    {
        $appeal = new self();
        $appeal->save($data);
        return $appeal;
    }

    /**
     * @notes 处理申诉
     * @param int $adminId
     * @param int $status
     * @param string $result
     * @param int $action
     * @return void
     */
    public function handle(int $adminId, int $status, string $result, int $action = 0): void
    {
        $this->save([
            'status' => $status,
            'handle_admin_id' => $adminId,
            'handle_result' => $result,
            'handle_action' => $action,
            'handle_time' => time(),
        ]);

        // 根据处理动作更新评价
        if ($status == self::STATUS_APPROVED && $action > 0) {
            $review = Review::find($this->review_id);
            if ($review) {
                switch ($action) {
                    case self::ACTION_DELETE:
                        $review->delete();
                        break;
                    case self::ACTION_HIDE:
                        $review->save(['is_show' => 0]);
                        break;
                }
            }
        }
    }
}
