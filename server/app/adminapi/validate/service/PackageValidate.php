<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务套餐验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\service;

use app\common\validate\BaseValidate;
use app\common\model\service\ServicePackage;
use app\common\model\service\ServiceCategory;

/**
 * 服务套餐验证器
 * Class PackageValidate
 * @package app\adminapi\validate\service
 */
class PackageValidate extends BaseValidate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id' => 'require|checkPackage',
        'category_id' => 'require|checkCategory',
        'name' => 'require|max:100',
        'price' => 'require|float|egt:0',
        'original_price' => 'float|egt:0',
        'duration' => 'require|integer|egt:1',
        'content' => 'array',
        'description' => 'max:500',
        'sort' => 'integer|egt:0',
        'is_show' => 'in:0,1',
        'is_recommend' => 'in:0,1',
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'id.require' => '请选择套餐',
        'category_id.require' => '请选择所属分类',
        'name.require' => '请输入套餐名称',
        'name.max' => '套餐名称最多100个字符',
        'price.require' => '请输入套餐价格',
        'price.float' => '价格必须为数字',
        'price.egt' => '价格必须大于等于0',
        'original_price.float' => '原价必须为数字',
        'original_price.egt' => '原价必须大于等于0',
        'duration.require' => '请输入服务时长',
        'duration.integer' => '服务时长必须为整数',
        'duration.egt' => '服务时长必须大于等于1小时',
        'content.array' => '套餐内容格式错误',
        'description.max' => '描述最多500个字符',
        'sort.integer' => '排序必须为整数',
        'sort.egt' => '排序必须大于等于0',
        'is_show.in' => '显示状态值错误',
        'is_recommend.in' => '推荐状态值错误',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'add' => ['category_id', 'name', 'price', 'original_price', 'duration', 'content', 'description', 'sort', 'is_show', 'is_recommend'],
        'edit' => ['id', 'category_id', 'name', 'price', 'original_price', 'duration', 'content', 'description', 'sort', 'is_show', 'is_recommend'],
        'detail' => ['id'],
        'delete' => ['id'],
        'status' => ['id', 'is_show'],
    ];

    /**
     * @notes 验证套餐是否存在
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkPackage($value, $rule, $data)
    {
        $package = ServicePackage::find($value);
        if (!$package) {
            return '套餐不存在';
        }
        return true;
    }

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
            return '所属分类不存在';
        }
        return true;
    }
}
