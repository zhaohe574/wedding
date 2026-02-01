<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

namespace app\common\model\staff;

use app\common\model\BaseModel;
use app\common\service\FileService;

/**
 * 人员轮播图模型
 * Class StaffBanner
 * @package app\common\model\staff
 */
class StaffBanner extends BaseModel
{
    protected $name = 'staff_banner';

    // 类型常量
    const TYPE_IMAGE = 1;   // 图片
    const TYPE_VIDEO = 2;   // 视频

    /**
     * @notes 文件地址获取器
     * @param $value
     * @return string
     */
    public function getFileUrlAttr($value)
    {
        return trim($value) ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 文件地址设置器
     * @param $value
     * @return string
     */
    public function setFileUrlAttr($value)
    {
        return trim($value) ? FileService::setFileUrl($value) : '';
    }

    /**
     * @notes 封面图获取器
     * @param $value
     * @return string
     */
    public function getCoverUrlAttr($value)
    {
        return trim($value) ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 封面图设置器
     * @param $value
     * @return string
     */
    public function setCoverUrlAttr($value)
    {
        return trim($value) ? FileService::setFileUrl($value) : '';
    }

    /**
     * @notes 关联人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    /**
     * @notes 获取类型选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return [
            ['value' => self::TYPE_IMAGE, 'label' => '图片'],
            ['value' => self::TYPE_VIDEO, 'label' => '视频'],
        ];
    }
}
