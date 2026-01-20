<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

namespace app\common\model\staff;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

/**
 * 工作人员作品模型
 * Class StaffWork
 * @package app\common\model\staff
 */
class StaffWork extends BaseModel
{
    use SoftDelete;

    protected $name = 'staff_work';
    protected $deleteTime = 'delete_time';

    // 作品类型
    const TYPE_IMAGE = 1;       // 图片
    const TYPE_VIDEO = 2;       // 视频

    // 审核状态
    const AUDIT_PENDING = 0;    // 待审核
    const AUDIT_PASS = 1;       // 已通过
    const AUDIT_REJECT = 2;     // 已拒绝

    /**
     * @notes 关联工作人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    /**
     * @notes 封面获取器
     * @param $value
     * @return string
     */
    public function getCoverAttr($value)
    {
        return trim($value) ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 封面设置器
     * @param $value
     * @return string
     */
    public function setCoverAttr($value)
    {
        return trim($value) ? FileService::setFileUrl($value) : '';
    }

    /**
     * @notes 图片获取器(JSON转数组并补全URL)
     * @param $value
     * @return array
     */
    public function getImagesAttr($value)
    {
        if (empty($value)) {
            return [];
        }
        $images = json_decode($value, true) ?: [];
        return array_map(function ($img) {
            return FileService::getFileUrl($img);
        }, $images);
    }

    /**
     * @notes 图片设置器(数组转JSON并去除域名)
     * @param $value
     * @return string
     */
    public function setImagesAttr($value)
    {
        if (empty($value)) {
            return '';
        }
        if (is_string($value)) {
            $value = json_decode($value, true) ?: [];
        }
        $images = array_map(function ($img) {
            return FileService::setFileUrl($img);
        }, $value);
        return json_encode($images, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @notes 视频获取器
     * @param $value
     * @return string
     */
    public function getVideoAttr($value)
    {
        return trim($value) ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 视频设置器
     * @param $value
     * @return string
     */
    public function setVideoAttr($value)
    {
        return trim($value) ? FileService::setFileUrl($value) : '';
    }

    /**
     * @notes 审核状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getAuditStatusDescAttr($value, $data)
    {
        $statusMap = [
            self::AUDIT_PENDING => '待审核',
            self::AUDIT_PASS => '已通过',
            self::AUDIT_REJECT => '已拒绝',
        ];
        return $statusMap[$data['audit_status'] ?? 0] ?? '未知';
    }

    /**
     * @notes 显示状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getIsShowDescAttr($value, $data)
    {
        return ($data['is_show'] ?? 0) ? '显示' : '隐藏';
    }

    /**
     * @notes 作品类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getTypeDescAttr($value, $data)
    {
        $typeMap = [
            self::TYPE_IMAGE => '图片',
            self::TYPE_VIDEO => '视频',
        ];
        return $typeMap[$data['type'] ?? 1] ?? '未知';
    }

    /**
     * @notes 增加浏览数
     * @param int $workId
     * @return void
     */
    public static function incrementViewCount(int $workId): void
    {
        self::where('id', $workId)->inc('view_count')->update();
    }

    /**
     * @notes 增加点赞数
     * @param int $workId
     * @return void
     */
    public static function incrementLikeCount(int $workId): void
    {
        self::where('id', $workId)->inc('like_count')->update();
    }

    /**
     * @notes 减少点赞数
     * @param int $workId
     * @return void
     */
    public static function decrementLikeCount(int $workId): void
    {
        self::where('id', $workId)->where('like_count', '>', 0)->dec('like_count')->update();
    }
}
