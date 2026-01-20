<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 评价标签关联模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\review;

use think\model\Pivot;

/**
 * 评价标签关联模型
 * Class ReviewTagRelation
 * @package app\common\model\review
 */
class ReviewTagRelation extends Pivot
{
    protected $name = 'review_tag_relation';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    /**
     * @notes 批量添加标签关联
     * @param int $reviewId
     * @param array $tagIds
     * @return void
     */
    public static function bindTags(int $reviewId, array $tagIds): void
    {
        if (empty($tagIds)) {
            return;
        }

        $data = [];
        foreach ($tagIds as $tagId) {
            $data[] = [
                'review_id' => $reviewId,
                'tag_id' => $tagId,
                'create_time' => time(),
            ];
        }

        self::insertAll($data);

        // 更新标签使用次数
        ReviewTag::whereIn('id', $tagIds)->inc('use_count')->update();
    }

    /**
     * @notes 删除评价的所有标签关联
     * @param int $reviewId
     * @return void
     */
    public static function unbindTags(int $reviewId): void
    {
        self::where('review_id', $reviewId)->delete();
    }
}
