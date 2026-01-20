<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 评价标签模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\review;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 评价标签模型
 * Class ReviewTag
 * @package app\common\model\review
 */
class ReviewTag extends BaseModel
{
    use SoftDelete;

    protected $name = 'review_tag';
    protected $deleteTime = 'delete_time';

    // 标签类型
    const TYPE_GOOD = 1;    // 好评标签
    const TYPE_MEDIUM = 2;  // 中评标签
    const TYPE_BAD = 3;     // 差评标签

    /**
     * @notes 类型描述
     * @param bool $value
     * @return array|string
     */
    public static function getTypeDesc($value = true)
    {
        $data = [
            self::TYPE_GOOD => '好评标签',
            self::TYPE_MEDIUM => '中评标签',
            self::TYPE_BAD => '差评标签',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 关联评价
     * @return \think\model\relation\BelongsToMany
     */
    public function reviews()
    {
        return $this->belongsToMany(Review::class, ReviewTagRelation::class, 'review_id', 'tag_id');
    }

    /**
     * @notes 类型文本获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getTypeTextAttr($value, $data)
    {
        return self::getTypeDesc($data['type'] ?? 1);
    }

    /**
     * @notes 获取标签列表（按类型分组）
     * @return array
     */
    public static function getGroupedList(): array
    {
        $list = self::where('status', 1)
            ->order('type asc, sort asc')
            ->select()
            ->toArray();

        $grouped = [
            'good' => [],
            'medium' => [],
            'bad' => [],
        ];

        foreach ($list as $item) {
            switch ($item['type']) {
                case self::TYPE_GOOD:
                    $grouped['good'][] = $item;
                    break;
                case self::TYPE_MEDIUM:
                    $grouped['medium'][] = $item;
                    break;
                case self::TYPE_BAD:
                    $grouped['bad'][] = $item;
                    break;
            }
        }

        return $grouped;
    }

    /**
     * @notes 根据评分获取对应的标签
     * @param int $score
     * @return array
     */
    public static function getTagsByScore(int $score): array
    {
        if ($score >= 4) {
            $type = self::TYPE_GOOD;
        } elseif ($score == 3) {
            $type = self::TYPE_MEDIUM;
        } else {
            $type = self::TYPE_BAD;
        }

        return self::where('status', 1)
            ->where('type', $type)
            ->order('sort asc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 增加使用次数
     * @return void
     */
    public function incrementUseCount(): void
    {
        $this->inc('use_count')->update();
    }
}
