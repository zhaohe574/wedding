<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 单量月报模板
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\staff;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class MonthlyReportTemplate extends BaseModel
{
    use SoftDelete;

    protected $name = 'monthly_report_template';
    protected $deleteTime = 'delete_time';

    protected $json = ['design_config'];
    protected $jsonAssoc = true;

    public const TYPE_ADDITION = 'addition';
    public const TYPE_RANKING = 'ranking';
    public const TYPE_TOP = 'top';

    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    protected $append = ['type_desc', 'status_desc'];

    public function getTypeDescAttr($value, $data): string
    {
        return self::getTypeText((string)($data['template_type'] ?? ''));
    }

    public function getStatusDescAttr($value, $data): string
    {
        return (int)($data['status'] ?? self::STATUS_DISABLED) === self::STATUS_ENABLED ? '启用' : '停用';
    }

    public static function types(): array
    {
        return [self::TYPE_ADDITION, self::TYPE_RANKING, self::TYPE_TOP];
    }

    public static function getTypeText(string $type): string
    {
        return [
            self::TYPE_ADDITION => '新增档期',
            self::TYPE_RANKING => '共执行榜',
            self::TYPE_TOP => '月度单王',
        ][$type] ?? '未知模板';
    }
}
