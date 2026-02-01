<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 风格标签验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\service;

use app\common\validate\BaseValidate;
use app\common\model\service\StyleTag;
use app\common\model\service\ServiceCategory;

/**
 * 风格标签验证器
 * Class StyleTagValidate
 * @package app\adminapi\validate\service
 */
class StyleTagValidate extends BaseValidate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id' => 'require|checkTag',
        'name' => 'require|max:50',
        'type' => 'require|in:1,2,3',
        'category_id' => 'require|integer|gt:0|checkCategory',
        'sort' => 'integer|egt:0',
        'is_show' => 'in:0,1',
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'id.require' => '请选择标签',
        'name.require' => '请输入标签名称',
        'name.max' => '标签名称最多50个字符',
        'type.require' => '请选择标签类型',
        'type.in' => '标签类型值错误',
        'category_id.require' => '请选择服务分类',
        'category_id.integer' => '服务分类参数错误',
        'category_id.gt' => '请选择服务分类',
        'sort.integer' => '排序必须为整数',
        'sort.egt' => '排序必须大于等于0',
        'is_show.in' => '显示状态值错误',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'add' => ['name', 'type', 'category_id', 'sort', 'is_show'],
        'edit' => ['id', 'name', 'type', 'category_id', 'sort', 'is_show'],
        'detail' => ['id'],
        'delete' => ['id'],
        'status' => ['id', 'is_show'],
    ];

    /**
     * @notes 验证标签是否存在
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkTag($value, $rule, $data)
    {
        $tag = StyleTag::find($value);
        if (!$tag) {
            return '标签不存在';
        }
        return true;
    }

    /**
     * @notes 校验服务分类是否存在
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkCategory($value, $rule, $data)
    {
        $category = ServiceCategory::where('id', $value)
            ->where('delete_time', null)
            ->find();
        if (!$category) {
            return '服务分类不存在';
        }
        return true;
    }
}
