<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

namespace app\common\model\service;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 服务套餐模型
 * Class ServicePackage
 * @package app\common\model\service
 */
class ServicePackage extends BaseModel
{
    use SoftDelete;

    protected $name = 'service_package';
    protected $deleteTime = 'delete_time';

    /**
     * @notes 获取所属分类
     * @return \think\model\relation\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id', 'id');
    }

    /**
     * @notes 套餐内容获取器(JSON转数组)
     * @param $value
     * @return array
     */
    public function getContentAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @notes 套餐内容设置器(数组转JSON)
     * @param $value
     * @return false|string
     */
    public function setContentAttr($value)
    {
        return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
    }

    /**
     * @notes 分类名称获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getCategoryNameAttr($value, $data)
    {
        $category = ServiceCategory::find($data['category_id']);
        return $category ? $category->name : '';
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getIsShowDescAttr($value, $data)
    {
        return $data['is_show'] ? '上架' : '下架';
    }

    /**
     * @notes 推荐描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getIsRecommendDescAttr($value, $data)
    {
        return $data['is_recommend'] ? '是' : '否';
    }

    /**
     * @notes 服务时长格式化获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getDurationDescAttr($value, $data)
    {
        return $data['duration'] . '小时';
    }
}
