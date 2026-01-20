<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

namespace app\common\model\staff;

use app\common\model\BaseModel;

/**
 * 工作人员标签关联模型
 * Class StaffTag
 * @package app\common\model\staff
 */
class StaffTag extends BaseModel
{
    protected $name = 'staff_tag';

    /**
     * @notes 关联工作人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    /**
     * @notes 关联标签
     * @return \think\model\relation\BelongsTo
     */
    public function tag()
    {
        return $this->belongsTo(\app\common\model\service\StyleTag::class, 'tag_id', 'id');
    }

    /**
     * @notes 批量设置标签
     * @param int $staffId
     * @param array $tagIds
     * @return void
     */
    public static function setTags(int $staffId, array $tagIds): void
    {
        // 删除原有标签
        self::where('staff_id', $staffId)->delete();

        // 添加新标签
        if (!empty($tagIds)) {
            $data = [];
            $time = time();
            foreach ($tagIds as $tagId) {
                $data[] = [
                    'staff_id' => $staffId,
                    'tag_id' => $tagId,
                    'create_time' => $time,
                ];
            }
            (new self())->saveAll($data);
        }
    }

    /**
     * @notes 获取工作人员的标签ID列表
     * @param int $staffId
     * @return array
     */
    public static function getTagIds(int $staffId): array
    {
        return self::where('staff_id', $staffId)->column('tag_id');
    }
}
