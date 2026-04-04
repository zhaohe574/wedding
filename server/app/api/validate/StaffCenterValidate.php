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
        'experience_years' => 'integer|egt:0',
        'profile' => 'max:500',
        'service_desc' => 'max:1000',
        'tag_ids' => 'array',

        'title' => 'length:1,100',
        'cover' => 'max:255',
        'images' => 'array',
        'video' => 'max:255',
        'shoot_date' => 'dateFormat:Y-m-d',
        'location' => 'max:100',
        'sort' => 'integer|egt:0',
        'is_show' => 'in:0,1',
        'audit_status' => 'in:0,1,2',

        'package_id' => 'require|integer|gt:0',
        'category_id' => 'integer|gt:0',
        'price' => 'float|egt:0',
        'original_price' => 'float|egt:0',
        'image' => 'max:255',
        'description' => 'max:500',
        'region_prices' => 'array',
        'duration' => 'integer|egt:0',
        'content' => 'checkContent',
        'sort' => 'integer|egt:0',
        'is_show' => 'in:0,1',
        'is_recommend' => 'in:0,1',
        'status' => 'integer|egt:0',
        'remark' => 'max:255',
        'keyword' => 'max:100',
        'page_no' => 'integer|gt:0',
        'page_size' => 'integer|gt:0',
        'addon_id' => 'require|integer|gt:0',

        'date' => 'require|dateFormat:Y-m-d',

        'dynamic_type' => 'in:1,2',
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
        'experience_years.integer' => '从业年限格式不正确',
        'experience_years.egt' => '从业年限不能小于0',
        'profile.max' => '个人简介长度不能超过500',
        'service_desc.max' => '服务说明长度不能超过1000',
        'tag_ids.array' => '标签格式错误',

        'title.require' => '请输入标题',
        'title.length' => '标题长度为1-100个字符',
        'shoot_date.dateFormat' => '拍摄日期格式错误',
        'is_show.in' => '作品状态参数错误',
        'audit_status.in' => '审核状态参数错误',

        'package_id.require' => '请选择套餐',
        'package_id.integer' => '套餐参数错误',
        'package_id.gt' => '请选择套餐',
        'category_id.gt' => '请选择服务分类',
        'price.float' => '价格格式不正确',
        'price.egt' => '价格不能小于0',
        'original_price.float' => '原价格式不正确',
        'original_price.egt' => '原价不能小于0',
        'region_prices.array' => '地区价格格式错误',
        'duration.integer' => '服务时长格式不正确',
        'duration.egt' => '服务时长不能小于0',
        'content' => '内容格式错误',
        'status.integer' => '状态参数错误',
        'status.egt' => '状态参数错误',
        'remark.max' => '备注长度不能超过255',
        'keyword.max' => '关键词长度不能超过100',
        'page_no.integer' => '分页参数错误',
        'page_no.gt' => '分页参数错误',
        'page_size.integer' => '分页参数错误',
        'page_size.gt' => '分页参数错误',
        'addon_id.require' => '请选择附加项',
        'addon_id.integer' => '附加项参数错误',
        'addon_id.gt' => '请选择附加项',

        'date.require' => '请选择日期',
        'date.dateFormat' => '日期格式错误',

        'dynamic_type.in' => '动态类型参数错误',
        'content.require' => '请输入动态内容',
        'allow_comment.in' => '评论开关参数错误',
    ];

    public function sceneProfile(): StaffCenterValidate
    {
        return $this->only(['name', 'avatar', 'mobile', 'category_id', 'experience_years', 'profile', 'service_desc', 'tag_ids'])
            ->append('category_id', 'require');
    }

    public function sceneWorkAdd(): StaffCenterValidate
    {
        return $this->only(['title', 'cover', 'images', 'video', 'description', 'shoot_date', 'location', 'sort', 'is_show'])
            ->append('title', 'require')
            ->append('cover', 'require');
    }

    public function sceneWorkLists(): StaffCenterValidate
    {
        return $this->only(['is_show', 'audit_status', 'page_no', 'page_size']);
    }

    public function sceneWorkEdit(): StaffCenterValidate
    {
        return $this->only(['id', 'title', 'cover', 'images', 'video', 'description', 'shoot_date', 'location', 'sort', 'is_show']);
    }

    public function sceneWorkDetail(): StaffCenterValidate
    {
        return $this->only(['id']);
    }

    public function sceneWorkDelete(): StaffCenterValidate
    {
        return $this->only(['id']);
    }

    public function scenePackageAdd(): StaffCenterValidate
    {
        return $this->only(['name', 'price', 'original_price', 'description', 'image', 'region_prices', 'duration', 'sort', 'is_show', 'is_recommend'])
            ->append('name', 'require')
            ->append('price', 'require');
    }

    public function scenePackageUpdate(): StaffCenterValidate
    {
        return $this->only(['package_id', 'name', 'price', 'original_price', 'description', 'image', 'region_prices', 'duration', 'sort', 'is_show', 'is_recommend'])
            ->append('name', 'require')
            ->append('price', 'require');
    }

    public function scenePackageLists(): StaffCenterValidate
    {
        return $this->only(['is_show', 'is_recommend', 'page_no', 'page_size']);
    }

    public function scenePackageDetail(): StaffCenterValidate
    {
        return $this->only(['package_id']);
    }

    public function scenePackageRemove(): StaffCenterValidate
    {
        return $this->only(['package_id']);
    }

    public function sceneAddonLists(): StaffCenterValidate
    {
        return $this->only(['is_show', 'page_no', 'page_size']);
    }

    public function sceneAddonDetail(): StaffCenterValidate
    {
        return $this->only(['addon_id']);
    }

    public function sceneAddonAdd(): StaffCenterValidate
    {
        return $this->only(['name', 'price', 'original_price', 'description', 'image', 'sort', 'is_show'])
            ->append('name', 'require')
            ->append('price', 'require');
    }

    public function sceneAddonUpdate(): StaffCenterValidate
    {
        return $this->only(['addon_id', 'name', 'price', 'original_price', 'description', 'image', 'sort', 'is_show'])
            ->append('name', 'require')
            ->append('price', 'require');
    }

    public function sceneAddonRemove(): StaffCenterValidate
    {
        return $this->only(['addon_id']);
    }

    public function sceneScheduleSet(): StaffCenterValidate
    {
        return $this->only(['date', 'status', 'remark'])
            ->append('status', 'require|in:0,1');
    }

    public function sceneOrderLists(): StaffCenterValidate
    {
        return $this->only(['status', 'keyword', 'page_no', 'page_size']);
    }

    public function sceneDynamicLists(): StaffCenterValidate
    {
        return $this->only(['dynamic_type', 'status', 'page_no', 'page_size']);
    }

    public function sceneDynamicAdd(): StaffCenterValidate
    {
        return $this->only(['dynamic_type', 'title', 'content', 'images', 'video_url', 'video_cover', 'location', 'latitude', 'longitude', 'tags', 'allow_comment', 'order_id'])
            ->append('title', 'require')
            ->append('content', 'require');
    }

    public function sceneDynamicDetail(): StaffCenterValidate
    {
        return $this->only(['id']);
    }

    public function sceneDynamicEdit(): StaffCenterValidate
    {
        return $this->only(['id', 'dynamic_type', 'title', 'content', 'images', 'video_url', 'video_cover', 'location', 'latitude', 'longitude', 'tags', 'allow_comment'])
            ->append('title', 'require')
            ->append('content', 'require');
    }

    public function sceneDynamicDelete(): StaffCenterValidate
    {
        return $this->only(['id']);
    }

    public function sceneOrderDetail(): StaffCenterValidate
    {
        return $this->only(['id']);
    }

    public function sceneOrderConfirm(): StaffCenterValidate
    {
        return $this->only(['id']);
    }

    public function sceneOrderComplete(): StaffCenterValidate
    {
        return $this->only(['id']);
    }

    /**
     * @notes 兼容动态正文
     * @param mixed $value
     * @return bool|string
     */
    protected function checkContent($value)
    {
        if (is_array($value) || is_string($value) || $value === null) {
            return true;
        }
        return '内容格式错误';
    }
}
