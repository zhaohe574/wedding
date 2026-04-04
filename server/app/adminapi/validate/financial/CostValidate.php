<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 成本验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\financial;

use app\common\validate\BaseValidate;

/**
 * 成本验证器
 * Class CostValidate
 * @package app\adminapi\validate\financial
 */
class CostValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'ids' => 'require|array',
        'order_id' => 'integer|egt:0',
        'order_item_id' => 'integer|egt:0',
        'staff_id' => 'integer|egt:0',
        'cost_type' => 'require|in:1,2,3,4,5',
        'cost_name' => 'require|max:100',
        'cost_amount' => 'require|float|gt:0',
        'unit_price' => 'float|egt:0',
        'quantity' => 'float|gt:0',
        'service_date' => 'date',
        'remark' => 'max:255',
    ];

    protected $message = [
        'id.require' => 'ID不能为空',
        'cost_type.require' => '成本类型不能为空',
        'cost_type.in' => '成本类型不正确',
        'cost_name.require' => '成本名称不能为空',
        'cost_amount.require' => '成本金额不能为空',
        'cost_amount.gt' => '成本金额必须大于0',
    ];

    protected $scene = [
        'detail' => ['id'],
        'add' => ['cost_type', 'cost_name', 'cost_amount'],
        'edit' => ['id', 'cost_type', 'cost_name', 'cost_amount'],
        'batch' => ['ids'],
    ];
}
