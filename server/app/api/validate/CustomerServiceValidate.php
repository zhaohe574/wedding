<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 用户咨询入口校验器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 用户咨询入口校验器
 */
class CustomerServiceValidate extends BaseValidate
{
    protected $rule = [
        'scene' => 'require|in:home,staff_detail,order_detail,aftersale,package_detail',
        'staff_id' => 'integer|egt:0',
        'order_id' => 'integer|egt:0',
        'category_id' => 'integer|egt:0',
    ];

    protected $message = [
        'scene.require' => '请选择咨询场景',
        'scene.in' => '咨询场景参数错误',
        'staff_id.integer' => '服务人员参数错误',
        'order_id.integer' => '订单参数错误',
        'category_id.integer' => '分类参数错误',
    ];

    /**
     * @notes 启动咨询场景
     * @return CustomerServiceValidate
     */
    public function sceneStartConsult(): CustomerServiceValidate
    {
        return $this->only(['scene', 'staff_id', 'order_id', 'category_id']);
    }
}
