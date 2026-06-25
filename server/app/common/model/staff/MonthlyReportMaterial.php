<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 单量月报素材
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\staff;

use app\common\model\BaseModel;
use app\common\model\service\ServiceCategory;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

class MonthlyReportMaterial extends BaseModel
{
    use SoftDelete;

    protected $name = 'monthly_report_material';
    protected $deleteTime = 'delete_time';

    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    protected $append = [
        'photo_url',
        'avatar_photo_url',
        'half_body_photo_url',
        'staff_name',
        'category_id',
        'category_name',
        'status_desc',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    public function getPhotoUrlAttr($value, $data): string
    {
        $photo = trim((string)($data['photo'] ?? ''));
        if ($photo === '') {
            $photo = trim((string)($data['avatar_photo'] ?? ''));
        }
        return $photo !== ''
            ? FileService::getFileUrl($photo)
            : '';
    }

    public function getAvatarPhotoUrlAttr($value, $data): string
    {
        $photo = trim((string)($data['avatar_photo'] ?? ''));
        if ($photo === '') {
            $photo = trim((string)($data['photo'] ?? ''));
        }
        return $photo !== ''
            ? FileService::getFileUrl($photo)
            : '';
    }

    public function getHalfBodyPhotoUrlAttr($value, $data): string
    {
        $photo = trim((string)($data['half_body_photo'] ?? ''));
        if ($photo === '' && trim((string)($data['avatar_photo'] ?? '')) === '') {
            $photo = trim((string)($data['photo'] ?? ''));
        }
        return $photo !== ''
            ? FileService::getFileUrl($photo)
            : '';
    }

    public function setPhotoAttr($value): string
    {
        return trim((string)$value) !== '' ? FileService::setFileUrl((string)$value) : '';
    }

    public function setAvatarPhotoAttr($value): string
    {
        return trim((string)$value) !== '' ? FileService::setFileUrl((string)$value) : '';
    }

    public function setHalfBodyPhotoAttr($value): string
    {
        return trim((string)$value) !== '' ? FileService::setFileUrl((string)$value) : '';
    }

    public function getStaffNameAttr($value, $data): string
    {
        $staff = Staff::field('id,name')->find((int)($data['staff_id'] ?? 0));
        return (string)($staff->name ?? '');
    }

    public function getCategoryIdAttr($value, $data): int
    {
        $staff = Staff::field('id,category_id')->find((int)($data['staff_id'] ?? 0));
        return (int)($staff->category_id ?? 0);
    }

    public function getCategoryNameAttr($value, $data): string
    {
        $staff = Staff::field('id,category_id')->find((int)($data['staff_id'] ?? 0));
        if (!$staff) {
            return '';
        }
        $category = ServiceCategory::field('id,name')->find((int)($staff->category_id ?? 0));
        return (string)($category->name ?? '');
    }

    public function getStatusDescAttr($value, $data): string
    {
        return (int)($data['status'] ?? self::STATUS_DISABLED) === self::STATUS_ENABLED ? '启用' : '停用';
    }
}
