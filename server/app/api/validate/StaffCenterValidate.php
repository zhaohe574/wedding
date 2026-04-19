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
        'name' => 'max:100',
        'avatar' => 'max:255',
        'mobile' => 'mobile',
        'category_id' => 'integer|gt:0',
        'experience_years' => 'integer|egt:0',
        'profile' => 'max:500',
        'service_desc' => 'max:1000',
        'long_detail' => 'max:60000',
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
        'addon_ids' => 'array',

        'date' => 'require|dateFormat:Y-m-d',

        'type' => 'max:50',
        'sn' => 'max:100',
        'issue_org' => 'max:100',
        'issue_date' => 'dateFormat:Y-m-d',
        'expire_date' => 'dateFormat:Y-m-d',
        'verify_status' => 'in:0,1,2',
        'reject_reason' => 'max:255',

        'dynamic_type' => 'in:1,2',
        'allow_comment' => 'in:0,1',
        'letter_id' => 'require|integer|gt:0',
        'order_id' => 'integer|gt:0',
        'snapshot_hash' => 'max:128',
        'full_image_url' => 'max:500',
        'thumb_image_url' => 'max:500',
    ];

    protected $message = [
        'id.require' => '参数错误',
        'id.integer' => '参数错误',
        'id.gt' => '参数错误',
        'name.require' => '请输入姓名',
        'name.max' => '名称长度不能超过100个字符',
        'mobile.mobile' => '手机号格式不正确',
        'category_id.integer' => '服务分类参数错误',
        'category_id.gt' => '请选择服务分类',
        'experience_years.integer' => '从业年限格式不正确',
        'experience_years.egt' => '从业年限不能小于0',
        'profile.max' => '个人简介长度不能超过500',
        'service_desc.max' => '服务说明长度不能超过1000',
        'long_detail.max' => '长图详情内容过长',
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
        'addon_ids.array' => '附加项列表格式错误',

        'date.require' => '请选择日期',
        'date.dateFormat' => '日期格式错误',

        'type.max' => '证书类型最多50个字符',
        'sn.max' => '证书编号最多100个字符',
        'issue_org.max' => '发证机构最多100个字符',
        'issue_date.dateFormat' => '发证日期格式错误',
        'expire_date.dateFormat' => '有效期格式错误',
        'verify_status.in' => '审核状态参数错误',
        'reject_reason.max' => '拒绝原因长度不能超过255',

        'dynamic_type.in' => '动态类型参数错误',
        'content.require' => '请输入动态内容',
        'allow_comment.in' => '评论开关参数错误',
        'letter_id.require' => '请选择确认函',
        'letter_id.integer' => '确认函参数错误',
        'letter_id.gt' => '确认函参数错误',
        'order_id.integer' => '订单参数错误',
        'order_id.gt' => '订单参数错误',
        'snapshot_hash.require' => '确认函快照已失效，请重新生成后再保存',
        'snapshot_hash.max' => '确认函快照参数错误',
        'full_image_url.require' => '请先完成确认函图片保存',
        'full_image_url.max' => '确认函图片地址过长',
        'thumb_image_url.max' => '确认函缩略图地址过长',
    ];

    public function sceneProfile(): StaffCenterValidate
    {
        return $this->only(['name', 'avatar', 'mobile', 'category_id', 'experience_years', 'profile', 'service_desc', 'long_detail', 'tag_ids'])
            ->append('name', 'require|length:1,50')
            ->append('category_id', 'require');
    }

    public function sceneWorkAdd(): StaffCenterValidate
    {
        return $this->only(['title', 'cover', 'images', 'video', 'description', 'shoot_date', 'location', 'sort', 'is_show'])
            ->append('title', 'require')
            ->append('cover', 'require');
    }

    public function sceneCertificateLists(): StaffCenterValidate
    {
        return $this->only(['verify_status', 'name', 'sn', 'page_no', 'page_size']);
    }

    public function sceneCertificateDetail(): StaffCenterValidate
    {
        return $this->only(['id']);
    }

    public function sceneCertificateAdd(): StaffCenterValidate
    {
        return $this->only(['name', 'type', 'sn', 'image', 'issue_org', 'issue_date', 'expire_date'])
            ->append('name', 'require');
    }

    public function sceneCertificateEdit(): StaffCenterValidate
    {
        return $this->only(['id', 'name', 'type', 'sn', 'image', 'issue_org', 'issue_date', 'expire_date'])
            ->append('name', 'require');
    }

    public function sceneCertificateDelete(): StaffCenterValidate
    {
        return $this->only(['id']);
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
        return $this->only(['name', 'price', 'original_price', 'description', 'image', 'region_prices', 'duration', 'sort', 'is_show', 'is_recommend', 'addon_ids'])
            ->append('name', 'require')
            ->append('price', 'require');
    }

    public function scenePackageUpdate(): StaffCenterValidate
    {
        return $this->only(['package_id', 'name', 'price', 'original_price', 'description', 'image', 'region_prices', 'duration', 'sort', 'is_show', 'is_recommend', 'addon_ids'])
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

    public function sceneOrderStartService(): StaffCenterValidate
    {
        return $this->only(['id']);
    }

    public function sceneOrderConfirmLetterGenerate(): StaffCenterValidate
    {
        return $this->only(['order_id']);
    }

    public function sceneOrderConfirmLetterAsset(): StaffCenterValidate
    {
        return $this->only(['letter_id', 'snapshot_hash', 'full_image_url', 'thumb_image_url'])
            ->append('snapshot_hash', 'require')
            ->append('full_image_url', 'require');
    }

    public function sceneOrderConfirmLetterPush(): StaffCenterValidate
    {
        return $this->only(['letter_id']);
    }

    public function sceneOrderConfirmLetterDetail(): StaffCenterValidate
    {
        return $this->only(['letter_id']);
    }

    public function sceneOrderConfirmLetterHistory(): StaffCenterValidate
    {
        return $this->only(['order_id']);
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
