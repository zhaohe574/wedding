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
        'wedding_date' => 'max:20',
        'city' => 'max:50',
        'district' => 'max:50',
        'customer_name' => 'max:50',
        'customer_mobile' => 'require|mobile',
        'customer_wechat' => 'max:50',
        'budget_range' => 'max:50',
        'service_needs' => 'array',
        'remark' => 'max:500',
    ];

    protected $message = [
        'scene.require' => '请选择咨询场景',
        'scene.in' => '咨询场景参数错误',
        'staff_id.integer' => '服务人员参数错误',
        'order_id.integer' => '订单参数错误',
        'category_id.integer' => '分类参数错误',
        'wedding_date.max' => '婚期格式错误',
        'city.max' => '城市最多50个字符',
        'district.max' => '区域最多50个字符',
        'customer_name.max' => '称呼最多50个字符',
        'customer_mobile.require' => '请填写联系手机号',
        'customer_mobile.mobile' => '请填写正确的手机号',
        'customer_wechat.max' => '微信号最多50个字符',
        'budget_range.max' => '预算范围最多50个字符',
        'service_needs.array' => '服务需求格式错误',
        'remark.max' => '补充说明最多500个字符',
    ];

    /**
     * @notes 启动咨询场景
     * @return CustomerServiceValidate
     */
    public function sceneStartConsult(): CustomerServiceValidate
    {
        return $this->only(['scene', 'staff_id', 'order_id', 'category_id', 'wedding_date', 'city', 'district']);
    }

    /**
     * @notes 客户留资与预约回电
     * @return CustomerServiceValidate
     */
    public function sceneSubmitIntention(): CustomerServiceValidate
    {
        return $this->only([
            'customer_name',
            'customer_mobile',
            'customer_wechat',
            'wedding_date',
            'city',
            'district',
            'budget_range',
            'service_needs',
            'remark',
        ]);
    }
}
