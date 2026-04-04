<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\service;

use app\common\validate\BaseValidate;

/**
 * 服务地区验证器
 */
class RegionValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'city_code' => 'require|max:12',
        'status' => 'in:0,1',
        'sort' => 'integer|egt:0',
    ];

    protected $message = [
        'id.require' => '请选择服务地区',
        'id.integer' => '服务地区参数错误',
        'id.gt' => '服务地区参数错误',
        'city_code.require' => '请选择城市',
        'city_code.max' => '城市编码格式错误',
        'status.in' => '状态参数错误',
        'sort.integer' => '排序必须为整数',
        'sort.egt' => '排序不能小于0',
    ];

    protected $scene = [
        'lists' => ['status'],
        'add' => ['city_code', 'status', 'sort'],
        'edit' => ['id', 'city_code', 'status', 'sort'],
        'delete' => ['id'],
        'status' => ['id', 'status'],
        'districtOptions' => ['city_code'],
    ];
}
