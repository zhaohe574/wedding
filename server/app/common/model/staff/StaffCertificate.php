<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

namespace app\common\model\staff;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

/**
 * 工作人员证书模型
 * Class StaffCertificate
 * @package app\common\model\staff
 */
class StaffCertificate extends BaseModel
{
    use SoftDelete;

    protected $name = 'staff_certificate';
    protected $deleteTime = 'delete_time';

    // 审核状态
    const AUDIT_PENDING = 0;    // 待审核
    const AUDIT_PASS = 1;       // 已通过
    const AUDIT_REJECT = 2;     // 已拒绝

    // 审核状态别名（兼容）
    const VERIFY_PENDING = 0;   // 待审核
    const VERIFY_PASS = 1;      // 已通过
    const VERIFY_REJECT = 2;    // 已拒绝

    /**
     * @notes 关联工作人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    /**
     * @notes 证书图片获取器
     * @param $value
     * @return string
     */
    public function getImageAttr($value)
    {
        return trim($value) ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 证书图片设置器
     * @param $value
     * @return string
     */
    public function setImageAttr($value)
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
     * @notes 审核状态描述获取器（verify_status字段）
     * @param $value
     * @param $data
     * @return string
     */
    public function getVerifyStatusDescAttr($value, $data)
    {
        $statusMap = [
            self::VERIFY_PENDING => '待审核',
            self::VERIFY_PASS => '已通过',
            self::VERIFY_REJECT => '已拒绝',
        ];
        return $statusMap[$data['verify_status'] ?? 0] ?? '未知';
    }

    /**
     * @notes 是否过期
     * @param $value
     * @param $data
     * @return bool
     */
    public function getIsExpiredAttr($value, $data)
    {
        if (empty($data['expire_date'])) {
            return false;
        }
        return strtotime($data['expire_date']) < time();
    }
}
