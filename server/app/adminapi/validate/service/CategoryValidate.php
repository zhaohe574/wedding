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
        'pid' => 'integer|egt:0|checkParent',
        'name' => 'require|max:50',
        'icon' => 'max:255',
        'sort' => 'integer|egt:0',
        'is_show' => 'in:0,1',
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'id.require' => '请选择分类',
        'pid.integer' => '父级分类ID必须为整数',
        'pid.egt' => '父级分类ID必须大于等于0',
        'name.require' => '请输入分类名称',
        'name.max' => '分类名称最多50个字符',
        'icon.max' => '图标地址最多255个字符',
        'sort.integer' => '排序必须为整数',
        'sort.egt' => '排序必须大于等于0',
        'is_show.in' => '显示状态值错误',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'add' => ['pid', 'name', 'icon', 'sort', 'is_show'],
        'edit' => ['id', 'pid', 'name', 'icon', 'sort', 'is_show'],
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

    /**
     * @notes 验证父级分类是否合法
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkParent($value, $rule, $data)
    {
        if ($value == 0) {
            return true;
        }

        $parent = ServiceCategory::find($value);
        if (!$parent) {
            return '父级分类不存在';
        }

        // 编辑时不能将自己设为父级
        if (isset($data['id']) && $data['id'] == $value) {
            return '不能将分类设置为自己的子分类';
        }

        return true;
    }
}
