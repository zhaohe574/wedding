<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

namespace app\common\model\service;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 风格标签模型
 * Class StyleTag
 * @package app\common\model\service
 */
class StyleTag extends BaseModel
{
    use SoftDelete;

    protected $name = 'style_tag';
    protected $deleteTime = 'delete_time';

    // 标签类型
    const TYPE_STYLE = 1;   // 风格
    const TYPE_SKILL = 2;   // 特长
    const TYPE_OTHER = 3;   // 其他

    /**
     * @notes 获取使用该标签的工作人员
     * @return \think\model\relation\BelongsToMany
     */
    public function staffs()
    {
        return $this->belongsToMany(
            \app\common\model\staff\Staff::class,
            \app\common\model\staff\StaffTag::class,
            'staff_id',
            'tag_id'
        );
    }

    /**
     * @notes 标签类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getTypeDescAttr($value, $data)
    {
        $typeMap = [
            self::TYPE_STYLE => '风格',
            self::TYPE_SKILL => '特长',
            self::TYPE_OTHER => '其他',
        ];
        return $typeMap[$data['type']] ?? '未知';
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getIsShowDescAttr($value, $data)
    {
        return $data['is_show'] ? '显示' : '隐藏';
    }

    /**
     * @notes 获取标签类型选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return [
            ['value' => self::TYPE_STYLE, 'label' => '风格'],
            ['value' => self::TYPE_SKILL, 'label' => '特长'],
            ['value' => self::TYPE_OTHER, 'label' => '其他'],
        ];
    }

    /**
     * @notes 按类型获取标签列表
     * @param int|null $type
     * @return array
     */
    public static function getTagsByType(?int $type = null): array
    {
        $query = self::where('delete_time', null)
            ->where('is_show', 1)
            ->order('sort desc, id asc');

        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query->select()->toArray();
    }
}
