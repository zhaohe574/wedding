<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端订单变更验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 订单变更验证器
 * Class OrderChangeValidate
 * @package app\api\validate
 */
class OrderChangeValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'order_id' => 'require|integer|gt:0',
        'order_item_id' => 'require|integer|gt:0',
        'new_date' => 'require|date',
        'new_time_slot' => 'require|integer|between:0,3',
        'new_staff_id' => 'require|integer|gt:0',
        'staff_id' => 'require|integer|gt:0',
        'package_id' => 'require|integer|gt:0',
        'service_date' => 'require|date',
        'time_slot' => 'require|integer|between:0,3',
        'reason' => 'max:255',
        'attach_images' => 'array|max:5',
        'proof_images' => 'array|max:10',
        'to_user_name' => 'require|max:50',
        'to_user_mobile' => 'require|mobile',
        'mobile' => 'require|mobile',
        'code' => 'require|length:6',
        'pause_type' => 'require|integer|in:1,2,3,4',
        'start_date' => 'require|date',
        'end_date' => 'require|date',
    ];

    protected $message = [
        'id.require' => '请选择记录',
        'id.integer' => 'ID格式错误',
        'order_id.require' => '请选择订单',
        'order_id.integer' => '订单ID格式错误',
        'order_item_id.require' => '请选择订单项',
        'order_item_id.integer' => '订单项ID格式错误',
        'new_date.require' => '请选择新服务日期',
        'new_date.date' => '新服务日期格式错误',
        'new_time_slot.require' => '请选择时间段',
        'new_time_slot.between' => '时间段参数错误',
        'new_staff_id.require' => '请选择新工作人员',
        'new_staff_id.integer' => '工作人员ID格式错误',
        'staff_id.require' => '请选择工作人员',
        'staff_id.integer' => '工作人员ID格式错误',
        'package_id.require' => '请选择服务套餐',
        'package_id.integer' => '套餐ID格式错误',
        'service_date.require' => '请选择服务日期',
        'service_date.date' => '服务日期格式错误',
        'time_slot.require' => '请选择时间段',
        'time_slot.between' => '时间段参数错误',
        'reason.max' => '原因最多255个字符',
        'attach_images.array' => '附件图片格式错误',
        'attach_images.max' => '附件图片最多5张',
        'proof_images.array' => '证明材料格式错误',
        'proof_images.max' => '证明材料最多10张',
        'to_user_name.require' => '请填写接收方姓名',
        'to_user_name.max' => '接收方姓名最多50个字符',
        'to_user_mobile.require' => '请填写接收方手机号',
        'to_user_mobile.mobile' => '接收方手机号格式错误',
        'mobile.require' => '请填写手机号',
        'mobile.mobile' => '手机号格式错误',
        'code.require' => '请填写验证码',
        'code.length' => '验证码为6位数字',
        'pause_type.require' => '请选择暂停类型',
        'pause_type.in' => '暂停类型参数错误',
        'start_date.require' => '请选择开始日期',
        'start_date.date' => '开始日期格式错误',
        'end_date.require' => '请选择结束日期',
        'end_date.date' => '结束日期格式错误',
    ];

    /**
     * @notes 详情场景
     * @return OrderChangeValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 检查订单场景
     * @return OrderChangeValidate
     */
    public function sceneCheckOrder()
    {
        return $this->only(['order_id']);
    }

    /**
     * @notes 改期场景
     * @return OrderChangeValidate
     */
    public function sceneDateChange()
    {
        return $this->only(['order_id', 'new_date', 'new_time_slot', 'reason', 'attach_images']);
    }

    /**
     * @notes 换人场景
     * @return OrderChangeValidate
     */
    public function sceneStaffChange()
    {
        return $this->only(['order_id', 'order_item_id', 'new_staff_id', 'reason', 'attach_images']);
    }

    /**
     * @notes 加项场景
     * @return OrderChangeValidate
     */
    public function sceneAddItem()
    {
        return $this->only(['order_id', 'staff_id', 'package_id', 'service_date', 'time_slot', 'reason']);
    }

    /**
     * @notes 转让场景
     * @return OrderChangeValidate
     */
    public function sceneTransfer()
    {
        return $this->only(['order_id', 'to_user_name', 'to_user_mobile', 'reason']);
    }

    /**
     * @notes 转让详情场景
     * @return OrderChangeValidate
     */
    public function sceneTransferDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 接收转让场景
     * @return OrderChangeValidate
     */
    public function sceneAcceptTransfer()
    {
        return $this->only(['id', 'mobile', 'code']);
    }

    /**
     * @notes 暂停场景
     * @return OrderChangeValidate
     */
    public function scenePause()
    {
        return $this->only(['order_id', 'pause_type', 'reason', 'start_date', 'end_date', 'proof_images'])
            ->append('reason', 'require');
    }

    /**
     * @notes 暂停详情场景
     * @return OrderChangeValidate
     */
    public function scenePauseDetail()
    {
        return $this->only(['id']);
    }
}
