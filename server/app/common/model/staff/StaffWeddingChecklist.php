<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员婚礼 Checklist 模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\staff;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 服务人员婚礼 Checklist 模型
 * Class StaffWeddingChecklist
 * @package app\common\model\staff
 */
class StaffWeddingChecklist extends BaseModel
{
    use SoftDelete;

    protected $name = 'staff_wedding_checklist';
    protected $deleteTime = 'delete_time';

    public const SOURCE_ORDER = 'order';
    public const SOURCE_MANUAL = 'manual';

    public const STAGE_PRE_WEDDING = 'pre_wedding';
    public const STAGE_REHEARSAL = 'rehearsal';
    public const STAGE_WEDDING_DAY = 'wedding_day';
    public const STAGE_AFTER_SERVICE = 'after_service';

    public const STAGES = [
        self::STAGE_PRE_WEDDING => '筹备期',
        self::STAGE_REHEARSAL => '彩排沟通',
        self::STAGE_WEDDING_DAY => '婚礼当天',
        self::STAGE_AFTER_SERVICE => '仪式后交付',
    ];

    /**
     * @notes 获取婚礼阶段选项
     */
    public static function getStageOptions(): array
    {
        $options = [];
        foreach (self::STAGES as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }
        return $options;
    }

    /**
     * @notes 获取标准婚礼 8 项预设清单
     */
    public static function getDefaultChecklistTemplates(): array
    {
        return [
            [
                'stage' => self::STAGE_PRE_WEDDING,
                'title' => '收集并审阅新人故事与家庭问卷',
                'remark' => '重点确认称呼规范、家族避讳事项与仪式期待',
                'sort' => 80,
            ],
            [
                'stage' => self::STAGE_PRE_WEDDING,
                'title' => '确认婚礼音乐清单及重要环节配乐',
                'remark' => '核对迎宾、入场、交换誓言、敬茶等节点音源版本',
                'sort' => 70,
            ],
            [
                'stage' => self::STAGE_REHEARSAL,
                'title' => '与新人及酒店方对齐仪式动线与彩排时间',
                'remark' => '明确入场节奏、交接位置与话筒/灯光配合',
                'sort' => 60,
            ],
            [
                'stage' => self::STAGE_REHEARSAL,
                'title' => '同步同场执行团队人员对接群与时间表',
                'remark' => '与摄影/摄像/督导确认转场窗口及应急联系人',
                'sort' => 50,
            ],
            [
                'stage' => self::STAGE_WEDDING_DAY,
                'title' => '提前到场完成音视频试音与现场物料点验',
                'remark' => '检查备用麦克风、手卡/台本及服装造型状态',
                'sort' => 40,
            ],
            [
                'stage' => self::STAGE_WEDDING_DAY,
                'title' => '迎宾前再次确认双方父母及关键亲友席位',
                'remark' => '避免改口茶、证婚人发言或祝酒环节漏人或错序',
                'sort' => 30,
            ],
            [
                'stage' => self::STAGE_AFTER_SERVICE,
                'title' => '整理仪式亮点照片/成片并发送致谢祝福',
                'remark' => '沉淀现场口碑评价素材并记录复盘要点',
                'sort' => 20,
            ],
            [
                'stage' => self::STAGE_AFTER_SERVICE,
                'title' => '核对尾款结算进度与口碑回访邀约',
                'remark' => '确认订单已完成履约归档并礼貌邀请好评',
                'sort' => 10,
            ],
        ];
    }
}
