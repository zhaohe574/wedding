<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\service;

use app\common\model\BaseModel;
use app\common\model\staff\Staff;
use think\model\concern\SoftDelete;

/**
 * 附加服务模型
 * Class ServiceAddon
 * @package app\common\model\service
 */
class ServiceAddon extends BaseModel
{
    use SoftDelete;

    protected $name = 'service_addon';
    protected $deleteTime = 'delete_time';

    /**
     * @notes 关联服务人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    /**
     * @notes 关联服务分类
     * @return \think\model\relation\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id', 'id');
    }

    /**
     * @notes 分类名称获取器
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getCategoryNameAttr($value, $data): string
    {
        $categoryId = (int)($data['category_id'] ?? 0);
        if ($categoryId <= 0) {
            return '';
        }

        $category = ServiceCategory::find($categoryId);
        return $category ? (string)$category->name : '';
    }

    /**
     * @notes 人员名称获取器
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getStaffNameAttr($value, $data): string
    {
        $staffId = (int)($data['staff_id'] ?? 0);
        if ($staffId <= 0) {
            return '';
        }

        $staff = Staff::find($staffId);
        return $staff ? (string)$staff->name : '';
    }

    /**
     * @notes 状态描述获取器
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getIsShowDescAttr($value, $data): string
    {
        return !empty($data['is_show']) ? '上架' : '下架';
    }
}
