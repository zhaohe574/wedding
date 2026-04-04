<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务分类验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\service;

use app\common\validate\BaseValidate;
use app\common\model\service\ServiceCategory;

/**
 * 服务分类验证器
 * Class CategoryValidate
 * @package app\adminapi\validate\service
 */
class CategoryValidate extends BaseValidate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id' => 'require|checkCategory',
        'name' => 'require|max:50',
        'icon' => 'max:255',
        'image' => 'max:255',
        'booking_butler_enabled' => 'in:0,1',
        'booking_butler_category_id' => 'integer|egt:0',
        'booking_director_enabled' => 'in:0,1',
        'booking_director_category_id' => 'integer|egt:0',
        'sort' => 'integer|egt:0',
        'is_show' => 'in:0,1',
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'id.require' => '请选择分类',
        'name.require' => '请输入分类名称',
        'name.max' => '分类名称最多50个字符',
        'icon.max' => '图标地址最多255个字符',
        'image.max' => '图片地址最多255个字符',
        'booking_butler_enabled.in' => '婚礼管家开关参数错误',
        'booking_butler_category_id.integer' => '婚礼管家关联分类参数错误',
        'booking_butler_category_id.egt' => '婚礼管家关联分类参数错误',
        'booking_director_enabled.in' => '婚礼督导开关参数错误',
        'booking_director_category_id.integer' => '婚礼督导关联分类参数错误',
        'booking_director_category_id.egt' => '婚礼督导关联分类参数错误',
        'sort.integer' => '排序必须为整数',
        'sort.egt' => '排序必须大于等于0',
        'is_show.in' => '显示状态值错误',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'add' => ['name', 'icon', 'image', 'booking_butler_enabled', 'booking_butler_category_id', 'booking_director_enabled', 'booking_director_category_id', 'sort', 'is_show'],
        'edit' => ['id', 'name', 'icon', 'image', 'booking_butler_enabled', 'booking_butler_category_id', 'booking_director_enabled', 'booking_director_category_id', 'sort', 'is_show'],
        'detail' => ['id'],
        'delete' => ['id'],
        'status' => ['id', 'is_show'],
    ];

    /**
     * @notes 验证分类是否存在
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkCategory($value, $rule, $data)
    {
        $category = ServiceCategory::find($value);
        if (!$category) {
            return '分类不存在';
        }
        return true;
    }
}
