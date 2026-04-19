<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\order;

use app\common\validate\BaseValidate;

/**
 * 订单验证器
 * Class OrderValidate
 * @package app\adminapi\validate\order
 */
class OrderValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'user_id' => 'integer|egt:0',
        'order_type' => 'integer|in:1,2,3',
        'items' => 'require|array|min:1',
        'bind_mode' => 'require|in:user,temp',
        'service_date' => 'date',
        'province_code' => 'max:12',
        'province_name' => 'max:50',
        'city_code' => 'max:12',
        'city_name' => 'max:50',
        'district_code' => 'max:12',
        'district_name' => 'max:50',
        'service_address' => 'max:255',
        'contact_name' => 'require|max:50',
        'contact_mobile' => 'require|mobile',
        'discount_amount' => 'float|egt:0',
        'admin_remark' => 'max:500',
        'reason' => 'max:255',
        'remark' => 'max:500',
        'pay_type' => 'require|integer|in:1,2,3',
        'pay_amount' => 'require|float|gt:0',
        'approved' => 'require|integer|in:0,1',
        'payment_entry_mode' => 'require|in:online_pending,offline_voucher,offline_paid',
        'role_key' => 'require|in:butler,director',
        'main_staff_id' => 'require|integer|gt:0',
        'main_package_id' => 'require|integer|gt:0',
        'addon_ids' => 'array',
        'butler_staff_id' => 'integer|egt:0',
        'butler_package_id' => 'integer|egt:0',
        'director_staff_id' => 'integer|egt:0',
        'director_package_id' => 'integer|egt:0',
        'letter_id' => 'require|integer|gt:0',
        'snapshot_hash' => 'max:128',
        'full_image_url' => 'max:500',
        'thumb_image_url' => 'max:500',
        'svg_content' => 'max:200000',
    ];

    protected $message = [
        'id.require' => '请选择订单',
        'id.integer' => '订单ID格式错误',
        'user_id.integer' => '用户ID格式错误',
        'user_id.egt' => '用户ID格式错误',
        'order_type.in' => '订单类型参数错误',
        'bind_mode.require' => '请选择客户绑定方式',
        'bind_mode.in' => '客户绑定方式参数错误',
        'items.require' => '请添加订单项',
        'items.array' => '订单项格式错误',
        'items.min' => '至少添加一个订单项',
        'service_date.date' => '服务日期格式错误',
        'service_address.max' => '服务地址最多255个字符',
        'contact_name.require' => '请填写联系人',
        'contact_name.max' => '联系人姓名最多50个字符',
        'contact_mobile.require' => '请填写联系电话',
        'contact_mobile.mobile' => '联系电话格式错误',
        'discount_amount.egt' => '优惠金额不能为负数',
        'admin_remark.max' => '备注最多500个字符',
        'reason.max' => '原因最多255个字符',
        'remark.max' => '备注最多500个字符',
        'pay_type.require' => '请选择支付类型',
        'pay_type.in' => '支付类型参数错误',
        'pay_amount.require' => '请填写支付金额',
        'pay_amount.gt' => '支付金额必须大于0',
        'approved.require' => '请选择审核结果',
        'approved.in' => '审核结果参数错误',
        'payment_entry_mode.require' => '请选择付款录入方式',
        'payment_entry_mode.in' => '付款录入方式参数错误',
        'role_key.require' => '请选择协作角色',
        'role_key.in' => '协作角色参数错误',
        'main_staff_id.require' => '请选择主服务人员',
        'main_staff_id.integer' => '主服务人员参数错误',
        'main_staff_id.gt' => '请选择主服务人员',
        'main_package_id.require' => '请选择主套餐',
        'main_package_id.integer' => '主套餐参数错误',
        'main_package_id.gt' => '请选择主套餐',
        'addon_ids.array' => '附加项格式错误',
        'letter_id.require' => '请选择确认函',
        'letter_id.integer' => '确认函参数错误',
        'letter_id.gt' => '确认函参数错误',
        'snapshot_hash.require' => '确认函快照已失效，请重新生成后再保存',
        'snapshot_hash.max' => '确认函快照参数错误',
        'full_image_url.require' => '请先完成确认函图片保存',
        'full_image_url.max' => '确认函图片地址过长',
        'thumb_image_url.max' => '确认函缩略图地址过长',
    ];

    /**
     * @notes 详情场景
     * @return OrderValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 新增场景
     * @return OrderValidate
     */
    public function sceneAdd()
    {
        return $this->only([
            'user_id',
            'order_type',
            'items',
            'service_date',
            'province_code',
            'province_name',
            'city_code',
            'city_name',
            'district_code',
            'district_name',
            'service_address',
            'contact_name',
            'contact_mobile',
            'discount_amount',
            'admin_remark'
        ]);
    }

    public function sceneAddEstimate()
    {
        return $this->only([
            'items',
            'discount_amount',
        ])->remove('items', 'require');
    }

    /**
     * @notes 线下主套餐列表
     * @return OrderValidate
     */
    public function sceneOfflineMainPackages()
    {
        return $this->only([
            'service_date',
            'province_code',
            'province_name',
            'city_code',
            'city_name',
            'district_code',
            'district_name',
            'main_staff_id',
        ])->append('service_date', 'require')
            ->append('city_code', 'require')
            ->append('district_code', 'require');
    }

    /**
     * @notes 线下协作角色候选人
     * @return OrderValidate
     */
    public function sceneOfflineRoleCandidates()
    {
        return $this->only([
            'service_date',
            'province_code',
            'province_name',
            'city_code',
            'city_name',
            'district_code',
            'district_name',
            'role_key',
            'main_staff_id',
        ])->append('service_date', 'require')
            ->append('city_code', 'require')
            ->append('district_code', 'require')
            ->append('main_staff_id', 'require');
    }

    /**
     * @notes 线下建单金额预估
     * @return OrderValidate
     */
    public function sceneEstimateOffline()
    {
        return $this->only([
            'service_date',
            'province_code',
            'province_name',
            'city_code',
            'city_name',
            'district_code',
            'district_name',
            'main_staff_id',
            'main_package_id',
            'addon_ids',
            'butler_staff_id',
            'butler_package_id',
            'director_staff_id',
            'director_package_id',
            'discount_amount',
            'payment_entry_mode',
        ])->append('service_date', 'require')
            ->append('city_code', 'require')
            ->append('district_code', 'require');
    }

    /**
     * @notes 新增线下订单
     * @return OrderValidate
     */
    public function sceneAddOffline()
    {
        return $this->only([
            'bind_mode',
            'user_id',
            'contact_name',
            'contact_mobile',
            'service_date',
            'province_code',
            'province_name',
            'city_code',
            'city_name',
            'district_code',
            'district_name',
            'service_address',
            'main_staff_id',
            'main_package_id',
            'addon_ids',
            'butler_staff_id',
            'butler_package_id',
            'director_staff_id',
            'director_package_id',
            'discount_amount',
            'payment_entry_mode',
            'admin_remark',
        ])->append('service_date', 'require')
            ->append('city_code', 'require')
            ->append('district_code', 'require');
    }

    /**
     * @notes 编辑场景
     * @return OrderValidate
     */
    public function sceneEdit()
    {
        return $this->only([
            'id',
            'service_date',
            'province_code',
            'province_name',
            'city_code',
            'city_name',
            'district_code',
            'district_name',
            'service_address',
            'contact_name',
            'contact_mobile',
            'admin_remark'
        ])
            ->remove('contact_name', 'require')
            ->remove('contact_mobile', 'require');
    }

    /**
     * @notes 取消场景
     * @return OrderValidate
     */
    public function sceneCancel()
    {
        return $this->only(['id', 'reason']);
    }

    /**
     * @notes 删除场景
     * @return OrderValidate
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 备注场景
     * @return OrderValidate
     */
    public function sceneRemark()
    {
        return $this->only(['id', 'remark'])
            ->append('remark', 'require');
    }

    /**
     * @notes 确认支付场景
     * @return OrderValidate
     */
    public function sceneConfirmPay()
    {
        return $this->only(['id', 'pay_type', 'pay_amount']);
    }

    /**
     * @notes 审核凭证场景
     * @return OrderValidate
     */
    public function sceneAuditVoucher()
    {
        return $this->only(['id', 'approved', 'remark'])
            ->remove('remark', 'require');
    }

    public function sceneConfirmLetterGenerate()
    {
        return $this->only(['id']);
    }

    public function sceneConfirmLetterPush()
    {
        return $this->only(['letter_id']);
    }

    public function sceneConfirmLetterDetail()
    {
        return $this->only(['letter_id']);
    }

    public function sceneConfirmLetterHistory()
    {
        return $this->only(['id']);
    }

    public function sceneConfirmLetterAssets()
    {
        return $this->only(['letter_id', 'snapshot_hash', 'full_image_url', 'thumb_image_url'])
            ->append('snapshot_hash', 'require')
            ->append('full_image_url', 'require');
    }
}
