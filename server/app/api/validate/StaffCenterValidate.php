<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员中心验证
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 服务人员中心验证
 * Class StaffCenterValidate
 * @package app\api\validate
 */
class StaffCenterValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'name' => 'require|length:1,50',
        'avatar' => 'max:255',
        'mobile' => 'mobile',
        'category_id' => 'integer|gt:0',
        'price' => 'float|egt:0',
        'experience_years' => 'integer|egt:0',
        'profile' => 'max:500',
        'service_desc' => 'max:1000',

        'title' => 'length:1,100',
        'cover' => 'max:255',
        'images' => 'array',
        'video' => 'max:255',
        'shoot_date' => 'dateFormat:Y-m-d',
        'location' => 'max:100',
        'sort' => 'integer|egt:0',
        'is_show' => 'in:0,1',

        'package_id' => 'require|integer|gt:0',
        'status' => 'in:0,1',
        'custom_price' => 'float|egt:0',
        'booking_type' => 'in:0,1',
        'allowed_time_slots' => 'array',

        'date' => 'require|dateFormat:Y-m-d',
        'time_slot' => 'in:0,1,2,3',

        'dynamic_type' => 'in:1,2,3,4',
        'content' => 'require',
        'allow_comment' => 'in:0,1',
    ];

    protected $message = [
        'id.require' => '参数错误',
        'id.integer' => '参数错误',
        'id.gt' => '参数错误',
        'name.require' => '请输入姓名',
        'name.length' => '姓名长度为1-50个字符',
        'mobile.mobile' => '手机号格式不正确',
        'category_id.integer' => '服务分类参数错误',
        'category_id.gt' => '请选择服务分类',
        'price.float' => '价格格式不正确',
        'price.egt' => '价格不能小于0',
        'experience_years.integer' => '从业年限格式不正确',
        'experience_years.egt' => '从业年限不能小于0',
        'profile.max' => '个人简介长度不能超过500',
        'service_desc.max' => '服务说明长度不能超过1000',

        'title.length' => '作品标题长度为1-100个字符',
        'shoot_date.dateFormat' => '拍摄日期格式错误',
        'is_show.in' => '作品状态参数错误',

        'package_id.require' => '请选择套餐',
        'package_id.integer' => '套餐参数错误',
        'package_id.gt' => '请选择套餐',
        'status.in' => '套餐状态参数错误',
        'custom_price.float' => '价格格式不正确',
        'custom_price.egt' => '价格不能小于0',
        'booking_type.in' => '预约类型参数错误',

        'date.require' => '请选择日期',
        'date.dateFormat' => '日期格式错误',
        'time_slot.in' => '时间段参数错误',

        'dynamic_type.in' => '动态类型参数错误',
        'content.require' => '请输入动态内容',
        'allow_comment.in' => '评论开关参数错误',
    ];

    public function sceneProfile(): StaffCenterValidate
    {
        return $this->only(['name', 'avatar', 'mobile', 'category_id', 'price', 'experience_years', 'profile', 'service_desc'])
            ->append('category_id', 'require');
    }

    public function sceneWorkAdd(): StaffCenterValidate
    {
        return $this->only(['title', 'cover', 'images', 'video', 'description', 'shoot_date', 'location', 'sort', 'is_show'])
            ->append('title', 'require')
            ->append('cover', 'require');
    }

    public function sceneWorkEdit(): StaffCenterValidate
    {
        return $this->only(['id', 'title', 'cover', 'images', 'video', 'description', 'shoot_date', 'location', 'sort', 'is_show']);
    }

    public function sceneWorkDelete(): StaffCenterValidate
    {
        return $this->only(['id']);
    }

    public function scenePackageAdd(): StaffCenterValidate
    {
        return $this->only(['package_id']);
    }

    public function scenePackageUpdate(): StaffCenterValidate
    {
        return $this->only(['package_id', 'price', 'original_price', 'custom_price', 'custom_slot_prices', 'booking_type', 'allowed_time_slots', 'status']);
    }

    public function scenePackageRemove(): StaffCenterValidate
    {
        return $this->only(['package_id']);
    }

    public function sceneScheduleSet(): StaffCenterValidate
    {
        return $this->only(['date', 'time_slot', 'status']);
    }

    public function sceneDynamicAdd(): StaffCenterValidate
    {
        return $this->only(['dynamic_type', 'title', 'content', 'images', 'video_url', 'video_cover', 'location', 'latitude', 'longitude', 'tags', 'allow_comment', 'order_id'])
            ->append('content', 'require');
    }

    public function sceneDynamicEdit(): StaffCenterValidate
    {
        return $this->only(['id', 'dynamic_type', 'title', 'content', 'images', 'video_url', 'video_cover', 'location', 'latitude', 'longitude', 'tags', 'allow_comment']);
    }

    public function sceneDynamicDelete(): StaffCenterValidate
    {
        return $this->only(['id']);
    }
}
