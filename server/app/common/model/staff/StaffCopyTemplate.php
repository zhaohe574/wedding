<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员文案话术模板库模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\staff;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 服务人员文案话术模板模型
 * Class StaffCopyTemplate
 * @package app\common\model\staff
 */
class StaffCopyTemplate extends BaseModel
{
    use SoftDelete;

    protected $name = 'staff_copy_template';
    protected $deleteTime = 'delete_time';

    public const SCENE_INQUIRY = 'inquiry';
    public const SCENE_CONFIRM = 'confirm';
    public const SCENE_PRE_WEDDING = 'pre_wedding';
    public const SCENE_SERVICE_DAY = 'service_day';
    public const SCENE_AFTER_SERVICE = 'after_service';
    public const SCENE_WAITLIST = 'waitlist';

    public const SCENES = [
        self::SCENE_INQUIRY => '咨询回复',
        self::SCENE_CONFIRM => '锁档确认',
        self::SCENE_PRE_WEDDING => '婚前沟通',
        self::SCENE_SERVICE_DAY => '服务日提醒',
        self::SCENE_AFTER_SERVICE => '完工致谢',
        self::SCENE_WAITLIST => '候补通知',
    ];

    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    /**
     * 获取支持的场景选项
     */
    public static function getSceneOptions(): array
    {
        $options = [];
        foreach (self::SCENES as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }
        return $options;
    }

    /**
     * 获取内置参考话术（共 12 条，覆盖全部 6 个场景）
     */
    public static function getDefaultSystemTemplates(): array
    {
        return [
            [
                'id' => 0,
                'preset_key' => 'inquiry_available',
                'is_system' => true,
                'scene' => self::SCENE_INQUIRY,
                'scene_label' => self::SCENES[self::SCENE_INQUIRY],
                'title' => '首轮档期问候与介绍',
                'content' => '您好 {customer_name}，我是 {category_name} {staff_name}。很高兴收到您关于 {service_date} 档期的咨询！我目前已为您核实当日可约，参考服务价 {price_text}，方便的话可以发下婚礼酒店与流程需求，我帮您做初步规划。',
            ],
            [
                'id' => 0,
                'preset_key' => 'inquiry_package',
                'is_system' => true,
                'scene' => self::SCENE_INQUIRY,
                'scene_label' => self::SCENES[self::SCENE_INQUIRY],
                'title' => '套餐说明与试听约谈',
                'content' => '您好 {customer_name}，您的 {package_name} 档期目前空余，服务包含前期流程梳理、彩排沟通与现场执行。如您方便，我们可以约定 15 分钟线上语音对接仪式风格与音乐偏好。',
            ],
            [
                'id' => 0,
                'preset_key' => 'confirm_locked',
                'is_system' => true,
                'scene' => self::SCENE_CONFIRM,
                'scene_label' => self::SCENES[self::SCENE_CONFIRM],
                'title' => '锁档成功确认通知',
                'content' => '{customer_name} 您好！{service_date} 的 {package_name} 已为您正式锁定档期（地点：{service_address}）。我是您的 {category_name} {staff_name}，接下来请留意小程序里的新人问卷，填完后我会第一时间为您定制流程。',
            ],
            [
                'id' => 0,
                'preset_key' => 'confirm_offline',
                'is_system' => true,
                'scene' => self::SCENE_CONFIRM,
                'scene_label' => self::SCENES[self::SCENE_CONFIRM],
                'title' => '邀约核对场地与基本信息',
                'content' => '您好 {customer_name}，{service_date} 档期已确认预留。请帮我再次核对服务场地：{service_address} 是否有误，以及仪式大致开始时间，方便我同步团队协调时间。',
            ],
            [
                'id' => 0,
                'preset_key' => 'pre_wedding_questionnaire',
                'is_system' => true,
                'scene' => self::SCENE_PRE_WEDDING,
                'scene_label' => self::SCENES[self::SCENE_PRE_WEDDING],
                'title' => '提醒填写新人资料问卷',
                'content' => '{customer_name} 您好，距离 {service_date} 大喜之日越来越近啦！为了更懂你们的故事和避讳细节，麻烦抽空在订单里填写一下新人资料问卷，填好后我们约定时间顺一遍全天流程与音乐清单。',
            ],
            [
                'id' => 0,
                'preset_key' => 'pre_wedding_rehearsal',
                'is_system' => true,
                'scene' => self::SCENE_PRE_WEDDING,
                'scene_label' => self::SCENES[self::SCENE_PRE_WEDDING],
                'title' => '婚前一周流程对接确认',
                'content' => '{customer_name} 您好，{service_date} 在 {service_address} 的婚礼已进入倒计时。您提交的新人问卷要点我已详细看过，想跟您最后确认一遍迎宾时间、仪式入场音乐及亲友致辞名单，请问今天几点方便通话？',
            ],
            [
                'id' => 0,
                'preset_key' => 'service_day_arrival',
                'is_system' => true,
                'scene' => self::SCENE_SERVICE_DAY,
                'scene_label' => self::SCENES[self::SCENE_SERVICE_DAY],
                'title' => '大喜当日出发到场提醒',
                'content' => '{customer_name} 早安！新婚快乐，万事顺遂！我是 {staff_name}，今日为您执行 {package_name} 服务，已确认出发前往 {service_address}，到达后第一时间联系您对接现场细节，放心交给我们就好。',
            ],
            [
                'id' => 0,
                'preset_key' => 'service_day_standby',
                'is_system' => true,
                'scene' => self::SCENE_SERVICE_DAY,
                'scene_label' => self::SCENES[self::SCENE_SERVICE_DAY],
                'title' => '已到场就位并核对设备',
                'content' => '{customer_name} 您好，我已抵达 {service_address} 现场并完成设备音画试音。彩排与流程随时待命，请新人安心化妆准备，有临时调整随时告诉我。',
            ],
            [
                'id' => 0,
                'preset_key' => 'after_service_thanks',
                'is_system' => true,
                'scene' => self::SCENE_AFTER_SERVICE,
                'scene_label' => self::SCENES[self::SCENE_AFTER_SERVICE],
                'title' => '礼成致谢与好评邀请',
                'content' => '{customer_name} 您好，再次祝贺两位新婚大喜、百年好合！今天 {service_date} 在 {service_address} 的仪式圆满礼成，非常感谢你们对 {staff_name} 的信任。如果对本次服务满意，欢迎在小程序订单里留下宝贵评价～',
            ],
            [
                'id' => 0,
                'preset_key' => 'after_service_followup',
                'is_system' => true,
                'scene' => self::SCENE_AFTER_SERVICE,
                'scene_label' => self::SCENES[self::SCENE_AFTER_SERVICE],
                'title' => '后续素材与长期祝福',
                'content' => '{customer_name} 您好，今天婚礼现场氛围特别动人！后续如有亲友或纪念日需要服务，随时联系 {staff_name}，祝你们新婚快乐、生活甜甜蜜蜜！',
            ],
            [
                'id' => 0,
                'preset_key' => 'waitlist_released',
                'is_system' => true,
                'scene' => self::SCENE_WAITLIST,
                'scene_label' => self::SCENES[self::SCENE_WAITLIST],
                'title' => '档期释放优先通知',
                'content' => '{customer_name} 您好，好消息！您之前候补的 {service_date} {package_name} 刚刚释放出空档。我是 {staff_name}，系统已为您发送可约通知，如仍需要请尽快点击预约锁定，避免被其他新人抢先哦。',
            ],
            [
                'id' => 0,
                'preset_key' => 'waitlist_alternative',
                'is_system' => true,
                'scene' => self::SCENE_WAITLIST,
                'scene_label' => self::SCENES[self::SCENE_WAITLIST],
                'title' => '候补时段确认沟通',
                'content' => '{customer_name} 您好，感谢您的耐心候补。{service_date} 目前已恢复可预约状态，若您档期合适可直接小程序下单或先与我确认场地地点（{service_address}）安排。',
            ],
        ];
    }
}
