<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员服务手记模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\staff;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 服务人员服务手记模型
 * Class StaffServiceNote
 * @package app\common\model\staff
 */
class StaffServiceNote extends BaseModel
{
    use SoftDelete;

    protected $name = 'staff_service_note';
    protected $deleteTime = 'delete_time';

    public const SOURCE_ORDER = 'order';
    public const SOURCE_MANUAL = 'manual';

    public const CATEGORY_COMMUNICATION = 'communication';
    public const CATEGORY_PREFERENCE = 'preference';
    public const CATEGORY_REHEARSAL = 'rehearsal';
    public const CATEGORY_REVIEW = 'review';

    public const CATEGORIES = [
        self::CATEGORY_COMMUNICATION => '沟通纪要',
        self::CATEGORY_PREFERENCE => '喜好避讳',
        self::CATEGORY_REHEARSAL => '彩排记录',
        self::CATEGORY_REVIEW => '现场复盘',
    ];

    /**
     * @notes 获取手记分类选项
     */
    public static function getCategoryOptions(): array
    {
        $options = [];
        foreach (self::CATEGORIES as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }
        return $options;
    }

    /**
     * @notes 解码手记附图数组
     * @param mixed $raw
     * @return string[]
     */
    public static function decodeImages($raw): array
    {
        if (is_array($raw)) {
            $list = $raw;
        } elseif (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            $list = is_array($decoded) ? $decoded : [];
        } else {
            $list = [];
        }

        $clean = [];
        foreach ($list as $item) {
            $url = trim((string)$item);
            if ($url !== '') {
                $clean[] = $url;
            }
            if (count($clean) >= 4) {
                break;
            }
        }
        return $clean;
    }
}
