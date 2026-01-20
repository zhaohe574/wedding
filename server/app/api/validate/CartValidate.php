<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端购物车验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 小程序端购物车验证器
 * Class CartValidate
 * @package app\api\validate
 */
class CartValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'ids' => 'require|array',
        'staff_id' => 'require|integer|gt:0',
        'date' => 'require|date',
        'time_slot' => 'integer|between:0,3',
        'package_id' => 'integer|egt:0',
        'remark' => 'max:255',
        'share_code' => 'require|length:16',
        'plan_name' => 'require|max:50',
        'cart_ids' => 'require|array',
        'plan_id' => 'require|integer|gt:0',
        'plan_id_1' => 'require|integer|gt:0',
        'plan_id_2' => 'require|integer|gt:0',
    ];

    protected $message = [
        'id.require' => '请选择购物车项',
        'id.integer' => 'ID格式错误',
        'ids.require' => '请选择购物车项',
        'ids.array' => 'ID列表格式错误',
        'staff_id.require' => '请选择工作人员',
        'staff_id.integer' => '工作人员ID格式错误',
        'date.require' => '请选择日期',
        'date.date' => '日期格式错误',
        'time_slot.between' => '时间段参数错误',
        'package_id.egt' => '套餐ID格式错误',
        'remark.max' => '备注最多255个字符',
        'share_code.require' => '请输入分享码',
        'share_code.length' => '分享码格式错误',
        'plan_name.require' => '请输入方案名称',
        'plan_name.max' => '方案名称最多50个字符',
        'cart_ids.require' => '请选择购物车项',
        'cart_ids.array' => '购物车项格式错误',
        'plan_id.require' => '请选择方案',
        'plan_id.integer' => '方案ID格式错误',
        'plan_id_1.require' => '请选择第一个方案',
        'plan_id_2.require' => '请选择第二个方案',
    ];

    /**
     * @notes 添加场景
     * @return CartValidate
     */
    public function sceneAdd()
    {
        return $this->only(['staff_id', 'date', 'time_slot', 'package_id', 'remark']);
    }

    /**
     * @notes 更新场景
     * @return CartValidate
     */
    public function sceneUpdate()
    {
        return $this->only(['id', 'date', 'time_slot', 'remark'])
            ->remove('date', 'require')
            ->remove('time_slot', 'require');
    }

    /**
     * @notes 删除场景
     * @return CartValidate
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 批量删除场景
     * @return CartValidate
     */
    public function sceneBatchDelete()
    {
        return $this->only(['ids']);
    }

    /**
     * @notes 分享码场景
     * @return CartValidate
     */
    public function sceneShareCode()
    {
        return $this->only(['share_code']);
    }

    /**
     * @notes 保存方案场景
     * @return CartValidate
     */
    public function sceneSavePlan()
    {
        return $this->only(['plan_name', 'cart_ids', 'remark'])
            ->remove('remark', 'require');
    }

    /**
     * @notes 方案详情场景
     * @return CartValidate
     */
    public function scenePlanDetail()
    {
        return $this->only(['plan_id']);
    }

    /**
     * @notes 比较方案场景
     * @return CartValidate
     */
    public function sceneCompare()
    {
        return $this->only(['plan_id_1', 'plan_id_2']);
    }
}
