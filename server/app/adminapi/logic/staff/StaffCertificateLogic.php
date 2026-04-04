<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员证书管理逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\staff;

use app\common\logic\BaseLogic;
use app\common\model\staff\StaffCertificate;
use app\common\model\staff\Staff;

/**
 * 工作人员证书管理逻辑
 * Class StaffCertificateLogic
 * @package app\adminapi\logic\staff
 */
class StaffCertificateLogic extends BaseLogic
{
    /**
     * @notes 获取证书详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $certificate = StaffCertificate::with(['staff'])->find($id);
        if (!$certificate) {
            return [];
        }
        return $certificate->toArray();
    }

    /**
     * @notes 添加证书
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            // 验证工作人员是否存在
            $staff = Staff::find($params['staff_id']);
            if (!$staff) {
                throw new \Exception('工作人员不存在');
            }

            StaffCertificate::create([
                'staff_id' => $params['staff_id'],
                'name' => $params['name'],
                'type' => $params['type'] ?? '',
                'sn' => $params['sn'] ?? '',
                'image' => $params['image'] ?? '',
                'issue_org' => $params['issue_org'] ?? '',
                'issue_date' => $params['issue_date'] ?? null,
                'expire_date' => $params['expire_date'] ?? null,
                'verify_status' => StaffCertificate::VERIFY_PENDING,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑证书
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        try {
            $certificate = StaffCertificate::find($params['id']);
            if (!$certificate) {
                throw new \Exception('证书不存在');
            }

            $certificate->save([
                'name' => $params['name'],
                'type' => $params['type'] ?? $certificate->type,
                'sn' => $params['sn'] ?? $certificate->sn,
                'image' => $params['image'] ?? $certificate->image,
                'issue_org' => $params['issue_org'] ?? $certificate->issue_org,
                'issue_date' => $params['issue_date'] ?? $certificate->issue_date,
                'expire_date' => $params['expire_date'] ?? $certificate->expire_date,
                'update_time' => time(),
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除证书
     * @param array $params
     * @return bool
     */
    public static function delete(array $params): bool
    {
        return StaffCertificate::destroy($params['id']);
    }

    /**
     * @notes 审核证书
     * @param array $params
     * @return bool
     */
    public static function audit(array $params): bool
    {
        try {
            $certificate = StaffCertificate::find($params['id']);
            if (!$certificate) {
                throw new \Exception('证书不存在');
            }

            $updateData = [
                'verify_status' => $params['verify_status'],
                'update_time' => time(),
            ];

            // 如果是拒绝，记录拒绝原因
            if ($params['verify_status'] == StaffCertificate::VERIFY_REJECT && !empty($params['reject_reason'])) {
                $updateData['reject_reason'] = $params['reject_reason'];
            }

            $certificate->save($updateData);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
