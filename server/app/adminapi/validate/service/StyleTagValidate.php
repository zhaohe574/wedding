<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 风格标签验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\service;

use app\common\validate\BaseValidate;
use app\common\model\service\StyleTag;

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
        'sort.integer' => '排序必须为整数',
        'sort.egt' => '排序必须大于等于0',
        'is_show.in' => '显示状态值错误',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'add' => ['name', 'type', 'sort', 'is_show'],
        'edit' => ['id', 'name', 'type', 'sort', 'is_show'],
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
}
