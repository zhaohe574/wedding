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
        'reason' => 'require|checkCancelReason',
        'name' => 'max:100',
        'avatar' => 'max:255',
        'mobile' => 'mobile',
        'code' => 'regex:/^\d{4,8}$/',
        'category_id' => 'integer|gt:0',
        'experience_years' => 'integer|egt:0',
        'list_intro' => 'max:36',
        'profile' => 'max:500',
        'service_desc' => 'max:1000',
        'long_detail' => 'max:60000',
        'tag_ids' => 'array',
        'monthly_report_material' => 'array',

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
        'year' => 'integer|between:1970,2100',
        'keyword' => 'max:100',
        'page_no' => 'integer|gt:0',
        'page_size' => 'integer|gt:0',
        'addon_id' => 'require|integer|gt:0',
        'addon_ids' => 'array',

        'date' => 'require|dateFormat:Y-m-d',
        'service_name' => 'length:1,100',
        'customer_name' => 'max:50',
        'customer_mobile' => 'mobile',
        'region_name' => 'max:100',
        'service_address' => 'max:255',
        'request_id' => 'length:1,64',
        'manual_schedule_id' => 'require|integer|gt:0',
        'version' => 'integer|egt:1',

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
        'config_id' => 'integer|egt:0',
        'snapshot_hash' => 'max:128',
        'full_image_url' => 'max:500',
        'thumb_image_url' => 'max:500',
        'svg_content' => 'max:200000',
        'subtitle' => 'max:80',
        'content_template' => 'max:500',
        'footer_note' => 'max:160',
        'background_type' => 'in:color,image',
        'background_image' => 'max:500',
        'background_color' => 'max:20',
        'text_theme' => 'in:light,dark',
        'show_customer_alias' => 'in:0,1',
        'show_service_name' => 'in:0,1',
        'show_city' => 'in:0,1',
        'show_qrcode' => 'in:0,1',
        'qrcode_image' => 'max:500',
        'design_version' => 'max:60',
        'notify_status' => 'in:0,1,2,3',
        'range_days' => 'integer|between:1,30',
        'scene' => 'in:inquiry,confirm,pre_wedding,service_day,after_service,waitlist',
        'source_type' => 'in:order,manual,general',
        'target_id' => 'integer|egt:0',
        'category' => 'in:communication,preference,rehearsal,review',
        'is_pinned' => 'in:0,1',
        'remind_type' => 'in:pre_comm,questionnaire,rehearsal,balance,aftercare,other',
        'remind_date' => 'dateFormat:Y-m-d',
        'remind_time' => 'regex:/^\d{2}:\d{2}$/',
        'action' => 'in:done,reopen,snooze,ignore',
        'stage' => 'in:pre_wedding,rehearsal,wedding_day,after_service',
        'is_done' => 'in:0,1',
        'member_id' => 'integer|gt:0',
        'target_staff_id' => 'integer|gt:0',
        'filter' => 'in:all,upcoming,followup,completed',
        'scope' => 'in:pending,overdue,today,week,done,all',
    ];

    protected $message = [
        'id.require' => '参数错误',
        'id.integer' => '参数错误',
        'id.gt' => '参数错误',
        'reason.require' => '请填写取消原因',
        'reason.checkCancelReason' => '取消原因需为2-200个字符',
        'name.require' => '请输入姓名',
        'name.max' => '名称长度不能超过100个字符',
        'mobile.mobile' => '手机号格式不正确',
        'code.regex' => '验证码格式不正确',
        'category_id.integer' => '服务分类参数错误',
        'category_id.gt' => '请选择服务分类',
        'experience_years.integer' => '从业年限格式不正确',
        'experience_years.egt' => '从业年限不能小于0',
        'list_intro.max' => '列表页简介最多36个字符',
        'profile.max' => '个人简介长度不能超过500',
        'service_desc.max' => '服务说明长度不能超过1000',
        'long_detail.max' => '长图详情内容过长',
        'tag_ids.array' => '标签格式错误',
        'monthly_report_material.array' => '月报素材格式错误',

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
        'year.integer' => '年份参数错误',
        'year.between' => '年份参数错误',
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
        'service_name.require' => '请输入服务名称',
        'service_name.length' => '服务名称长度为1-100个字符',
        'customer_mobile.mobile' => '手机号格式不正确',
        'request_id.length' => '请求编号长度错误',
        'manual_schedule_id.require' => '请选择线下档期',
        'manual_schedule_id.gt' => '线下档期参数错误',

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
        'content_template.max' => '确认函正文模板最多500个字符',
        'footer_note.max' => '确认函页脚文案最多160个字符',
        'background_type.in' => '背景类型参数错误',
        'text_theme.in' => '文字主题参数错误',
        'notify_status.in' => '候补状态参数错误',
        'range_days.integer' => '时间范围参数错误',
        'range_days.between' => '时间范围仅支持1-30天',
        'scene.in' => '话术场景参数错误',
        'scene.require' => '请选择话术场景',
        'content.max' => '文案内容最多1000个字符',
        'source_type.require' => '请指定档案来源类型',
        'source_type.in' => '档案来源类型参数错误',
        'target_id.require' => '请选择关联新人档案',
        'target_id.gt' => '新人档案参数错误',
        'category.in' => '手记分类参数错误',
        'is_pinned.in' => '置顶参数错误',
        'remind_type.in' => '提醒类型参数错误',
        'remind_date.require' => '请选择提醒日期',
        'remind_date.dateFormat' => '提醒日期格式错误',
        'remind_time.regex' => '提醒时间格式应为HH:MM',
        'action.require' => '请指定提醒操作类型',
        'action.in' => '提醒操作类型错误',
        'stage.require' => '请选择筹备阶段',
        'stage.in' => '筹备阶段参数错误',
        'member_id.require' => '请选择团队成员',
        'member_id.gt' => '团队成员参数错误',
        'target_staff_id.require' => '请选择要添加的服务人员',
        'target_staff_id.gt' => '服务人员参数错误',
        'filter.in' => '筛选条件参数错误',
        'scope.in' => '提醒范围参数错误',
    ];

    public function sceneProfile(): StaffCenterValidate
    {
        // code 为变更手机号的短信验证码：非必填，仅在传值时校验 4-8 位数字格式；
        // 是否强制校验由 StaffCenterLogic::updateProfile 依据「手机号是否发生变更」判定。
        return $this->only(['name', 'avatar', 'mobile', 'code', 'category_id', 'experience_years', 'list_intro', 'profile', 'service_desc', 'long_detail', 'tag_ids', 'monthly_report_material'])
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
        return $this->only(['date', 'status', 'remark', 'version'])
            ->remove('version', 'egt')->append('version', 'require|egt:0')
            ->append('status', 'require|in:0,1');
    }

    public function sceneScheduleBookedYear(): StaffCenterValidate
    {
        return $this->only(['year', 'page_no', 'page_size']);
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

    public function sceneOrderCancel(): StaffCenterValidate
    {
        return $this->only(['id', 'reason']);
    }

    public function sceneOrderConfirmLetterGenerate(): StaffCenterValidate
    {
        return $this->only(['order_id', 'manual_schedule_id', 'config_id'])->remove('manual_schedule_id', 'require');
    }

    public function sceneManualScheduleAdd(): StaffCenterValidate
    {
        return $this->only(['date', 'service_name', 'customer_name', 'customer_mobile', 'region_name', 'service_address', 'remark', 'request_id'])
            ->append('date', 'require|dateFormat:Y-m-d')
            ->append('service_name', 'require|length:1,100')
            ->append('request_id', 'require|length:1,64');
    }

    public function sceneManualScheduleEdit(): StaffCenterValidate
    {
        return $this->only(['manual_schedule_id', 'date', 'service_name', 'customer_name', 'customer_mobile', 'region_name', 'service_address', 'remark', 'version'])
            ->append('manual_schedule_id', 'require|integer|gt:0')
            ->append('date', 'require|dateFormat:Y-m-d')
            ->append('service_name', 'require|length:1,100')
            ->append('version', 'require|integer|egt:1');
    }

    public function sceneManualScheduleAction(): StaffCenterValidate
    {
        return $this->only(['manual_schedule_id', 'version'])
            ->append('manual_schedule_id', 'require|integer|gt:0')
            ->append('version', 'require|integer|egt:1');
    }

    public function sceneManualScheduleDetail(): StaffCenterValidate
    {
        return $this->only(['manual_schedule_id']);
    }

    public function sceneScheduleConfirmLetterConfig(): StaffCenterValidate
    {
        return $this->only([
            'title',
            'subtitle',
            'content_template',
            'footer_note',
            'background_type',
            'background_image',
            'background_color',
            'text_theme',
            'show_customer_alias',
            'show_service_name',
            'show_city',
            'show_qrcode',
            'qrcode_image',
            'config_id',
            'design_version',
        ]);
    }

    public function sceneOrderConfirmLetterAsset(): StaffCenterValidate
    {
        return $this->only(['letter_id', 'snapshot_hash', 'full_image_url', 'thumb_image_url', 'svg_content'])
            ->append('snapshot_hash', 'require');
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
        return $this->only(['order_id', 'manual_schedule_id'])->remove('manual_schedule_id', 'require');
    }

    public function sceneSettlementLists(): StaffCenterValidate
    {
        return $this->only(['status', 'page_no', 'page_size']);
    }

    public function sceneSettlementReceive(): StaffCenterValidate
    {
        return $this->only(['id']);
    }

    public function sceneWaitlistLists(): StaffCenterValidate
    {
        return $this->only(['notify_status', 'page_no', 'page_size']);
    }

    public function sceneWaitlistNotify(): StaffCenterValidate
    {
        return $this->only(['id']);
    }

    public function sceneServiceDayPanel(): StaffCenterValidate
    {
        return $this->only(['date', 'range_days'])
            ->remove('date', 'require')
            ->append('date', 'dateFormat:Y-m-d');
    }

    public function sceneCopyTemplateLists(): StaffCenterValidate
    {
        return $this->only(['scene']);
    }

    public function sceneCopyTemplateSave(): StaffCenterValidate
    {
        return $this->only(['id', 'scene', 'title', 'content', 'sort'])
            ->remove('id', 'require|gt')
            ->append('id', 'integer|egt:0')
            ->append('scene', 'require')
            ->append('title', 'require|length:1,80')
            ->remove('content', 'checkContent')
            ->append('content', 'require|max:1000')
            ->append('sort', 'between:0,9999');
    }

    public function sceneCopyTemplateAction(): StaffCenterValidate
    {
        return $this->only(['id']);
    }

    public function sceneCoupleLists(): StaffCenterValidate
    {
        return $this->only(['filter', 'keyword']);
    }

    public function sceneCoupleDetail(): StaffCenterValidate
    {
        return $this->only(['source_type', 'target_id'])
            ->append('source_type', 'require|in:order,manual')
            ->append('target_id', 'require|integer|gt:0');
    }

    public function sceneCoupleSendQuestionnaire(): StaffCenterValidate
    {
        return $this->only(['order_id'])
            ->append('order_id', 'require|integer|gt:0');
    }

    public function sceneServiceNoteSave(): StaffCenterValidate
    {
        return $this->only(['id', 'source_type', 'target_id', 'category', 'content', 'images', 'is_pinned'])
            ->remove('id', 'require|gt')
            ->append('id', 'integer|egt:0')
            ->append('source_type', 'require|in:order,manual')
            ->append('target_id', 'require|integer|gt:0')
            ->remove('content', 'checkContent')
            ->append('content', 'require|max:1000');
    }

    public function sceneServiceNoteAction(): StaffCenterValidate
    {
        return $this->only(['id']);
    }

    public function sceneFollowupLists(): StaffCenterValidate
    {
        return $this->only(['scope', 'keyword']);
    }

    public function sceneFollowupSave(): StaffCenterValidate
    {
        return $this->only(['id', 'source_type', 'target_id', 'remind_type', 'title', 'remind_date', 'remind_time', 'remark'])
            ->remove('id', 'require|gt')
            ->append('id', 'integer|egt:0')
            ->append('source_type', 'require|in:order,manual,general')
            ->append('title', 'require|length:1,100')
            ->append('remind_date', 'require|dateFormat:Y-m-d')
            ->append('remark', 'max:500');
    }

    public function sceneFollowupAction(): StaffCenterValidate
    {
        return $this->only(['id', 'action'])
            ->append('action', 'require');
    }

    public function sceneFollowupDelete(): StaffCenterValidate
    {
        return $this->only(['id']);
    }

    public function sceneChecklistInitPresets(): StaffCenterValidate
    {
        return $this->only(['source_type', 'target_id'])
            ->append('source_type', 'require|in:order,manual')
            ->append('target_id', 'require|integer|gt:0');
    }

    public function sceneChecklistSave(): StaffCenterValidate
    {
        return $this->only(['id', 'source_type', 'target_id', 'stage', 'title', 'remark', 'sort'])
            ->remove('id', 'require|gt')
            ->append('id', 'integer|egt:0')
            ->append('source_type', 'require|in:order,manual')
            ->append('target_id', 'require|integer|gt:0')
            ->append('stage', 'require')
            ->append('title', 'require|length:1,120');
    }

    public function sceneChecklistItemAction(): StaffCenterValidate
    {
        return $this->only(['id']);
    }

    public function sceneTeamUpdateInfo(): StaffCenterValidate
    {
        return $this->only(['name', 'remark'])
            ->append('name', 'require|length:1,100');
    }

    public function sceneTeamMemberCandidates(): StaffCenterValidate
    {
        return $this->only(['keyword']);
    }

    public function sceneTeamMemberAdd(): StaffCenterValidate
    {
        return $this->only(['target_staff_id'])
            ->append('target_staff_id', 'require|integer|gt:0');
    }

    public function sceneTeamMemberToggleStatus(): StaffCenterValidate
    {
        return $this->only(['member_id', 'status'])
            ->append('member_id', 'require|integer|gt:0')
            ->append('status', 'require|in:0,1');
    }

    public function sceneTeamMemberRemove(): StaffCenterValidate
    {
        return $this->only(['member_id'])
            ->append('member_id', 'require|integer|gt:0');
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

    /**
     * @notes 取消原因需去除首尾空格后为 2-200 个字符
     * @param mixed $value
     * @return bool|string
     */
    protected function checkCancelReason($value)
    {
        $reason = trim((string)$value);
        $length = mb_strlen($reason, 'UTF-8');
        if ($length < 2 || $length > 200) {
            return '取消原因需为2-200个字符';
        }
        return true;
    }
}
