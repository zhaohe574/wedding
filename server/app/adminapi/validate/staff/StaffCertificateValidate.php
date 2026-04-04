<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员证书验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\staff;

use app\common\validate\BaseValidate;
use app\common\model\staff\StaffCertificate;
use app\common\model\staff\Staff;

/**
 * 工作人员证书验证器
 * Class StaffCertificateValidate
 * @package app\adminapi\validate\staff
 */
class StaffCertificateValidate extends BaseValidate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id' => 'require|checkCertificate',
        'staff_id' => 'require|checkStaff',
        'name' => 'require|max:100',
        'type' => 'max:50',
        'sn' => 'max:100',
        'image' => 'max:255',
        'issue_org' => 'max:100',
        'issue_date' => 'date',
        'expire_date' => 'date',
        'verify_status' => 'require|in:0,1,2',
        'reject_reason' => 'max:255',
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'id.require' => '请选择证书',
        'staff_id.require' => '请选择工作人员',
        'name.require' => '请输入证书名称',
        'name.max' => '证书名称最多100个字符',
        'type.max' => '证书类型最多50个字符',
        'sn.max' => '证书编号最多100个字符',
        'image.max' => '证书图片地址最多255个字符',
        'issue_org.max' => '发证机构最多100个字符',
        'issue_date.date' => '发证日期格式错误',
        'expire_date.date' => '有效期格式错误',
        'verify_status.require' => '请选择审核状态',
        'verify_status.in' => '审核状态值错误',
        'reject_reason.max' => '拒绝原因最多255个字符',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'add' => ['staff_id', 'name', 'type', 'sn', 'image', 'issue_org', 'issue_date', 'expire_date'],
        'edit' => ['id', 'name', 'type', 'sn', 'image', 'issue_org', 'issue_date', 'expire_date'],
        'detail' => ['id'],
        'delete' => ['id'],
        'audit' => ['id', 'verify_status', 'reject_reason'],
    ];

    /**
     * @notes 验证证书是否存在
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkCertificate($value, $rule, $data)
    {
        $certificate = StaffCertificate::find($value);
        if (!$certificate) {
            return '证书不存在';
        }
        return true;
    }

    /**
     * @notes 验证工作人员是否存在
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkStaff($value, $rule, $data)
    {
        $staff = Staff::find($value);
        if (!$staff) {
            return '工作人员不存在';
        }
        return true;
    }
}
