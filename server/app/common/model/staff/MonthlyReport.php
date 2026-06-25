<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 单量月报确认记录
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\staff;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

class MonthlyReport extends BaseModel
{
    use SoftDelete;

    protected $name = 'monthly_report';
    protected $deleteTime = 'delete_time';

    protected $json = [
        'category_ids',
        'auto_snapshot',
        'final_snapshot',
        'template_snapshot',
        'rendered_snapshot',
        'issues',
    ];
    protected $jsonAssoc = true;

    public const CURRENT_NO = 0;
    public const CURRENT_YES = 1;

    protected $append = [
        'addition_image_full_url',
        'ranking_image_full_url',
        'top_image_full_url',
    ];

    public function getAdditionImageFullUrlAttr($value, $data): string
    {
        return self::formatImage((string)($data['addition_image_url'] ?? ''));
    }

    public function getRankingImageFullUrlAttr($value, $data): string
    {
        return self::formatImage((string)($data['ranking_image_url'] ?? ''));
    }

    public function getTopImageFullUrlAttr($value, $data): string
    {
        return self::formatImage((string)($data['top_image_url'] ?? ''));
    }

    protected static function formatImage(string $path): string
    {
        return trim($path) !== '' ? FileService::getFileUrl($path) : '';
    }
}
