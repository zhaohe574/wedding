<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员证书管理逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\staff;

use app\common\logic\BaseLogic;
use app\common\model\staff\StaffCertificate;
use app\common\model\staff\Staff;
use think\facade\Db;

/**
 * 工作人员证书管理逻辑
 * Class StaffCertificateLogic
 * @package app\adminapi\logic\staff
 */
class StaffCertificateLogic extends BaseLogic
{
    private static ?array $staffCertificateFields = null;

    /**
     * @notes 获取证书详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $certificate = StaffCertificate::with([
            'staff' => function ($query) {
                $query->field('id, name, sn');
            },
        ])->find($id);
        if (!$certificate) {
            return [];
        }

        return self::formatCertificateDetail($certificate->toArray(), $certificate);
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

            $createData = [
                'staff_id' => $params['staff_id'],
                'name' => $params['name'],
                'image' => $params['image'] ?? '',
                'issue_org' => $params['issue_org'] ?? '',
                'issue_date' => self::normalizeNullableDate($params['issue_date'] ?? null),
                'expire_date' => self::normalizeNullableDate($params['expire_date'] ?? null),
                'create_time' => time(),
                'update_time' => time(),
            ];
            if (self::hasStaffCertificateField('type')) {
                $createData['type'] = $params['type'] ?? '';
            }
            if (self::hasStaffCertificateField('reject_reason')) {
                $createData['reject_reason'] = '';
            }
            self::syncCertificateNumberPayload(
                $createData,
                trim((string) ($params['sn'] ?? ''))
            );
            self::syncCertificateStatusPayload($createData, StaffCertificate::VERIFY_PENDING);

            StaffCertificate::create($createData);

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

            $certificateData = $certificate->getData();
            $saveData = [
                'name' => $params['name'],
                'image' => $params['image'] ?? ($certificateData['image'] ?? ''),
                'issue_org' => $params['issue_org'] ?? ($certificateData['issue_org'] ?? ''),
                'issue_date' => array_key_exists('issue_date', $params)
                    ? self::normalizeNullableDate($params['issue_date'])
                    : ($certificateData['issue_date'] ?? null),
                'expire_date' => array_key_exists('expire_date', $params)
                    ? self::normalizeNullableDate($params['expire_date'])
                    : ($certificateData['expire_date'] ?? null),
                'update_time' => time(),
            ];
            if (self::hasStaffCertificateField('type')) {
                $saveData['type'] = $params['type'] ?? ($certificateData['type'] ?? '');
            }
            if (self::hasStaffCertificateField('reject_reason')) {
                $saveData['reject_reason'] = '';
            }
            self::syncCertificateNumberPayload(
                $saveData,
                trim((string) (
                    $params['sn']
                        ?? ($certificateData['sn'] ?? $certificateData['certificate_no'] ?? '')
                ))
            );
            self::syncCertificateStatusPayload($saveData, StaffCertificate::VERIFY_PENDING);

            $certificate->save($saveData);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 规范化可空日期字段
     * @param mixed $value
     * @return string|null
     */
    private static function normalizeNullableDate($value): ?string
    {
        $date = trim((string) ($value ?? ''));

        return $date === '' ? null : $date;
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

            if (self::getCertificateVerifyStatus($certificate) !== StaffCertificate::VERIFY_PENDING) {
                throw new \Exception('仅待审核证书可执行审核操作');
            }

            $verifyStatus = (int) $params['verify_status'];
            $rejectReason = trim((string) ($params['reject_reason'] ?? ''));

            if ($verifyStatus === StaffCertificate::VERIFY_REJECT && $rejectReason === '') {
                throw new \Exception('请输入拒绝原因');
            }

            $updateData = [
                'update_time' => time(),
            ];
            if (self::hasStaffCertificateField('reject_reason')) {
                $updateData['reject_reason'] = '';
            }
            self::syncCertificateStatusPayload($updateData, $verifyStatus);

            // 如果是拒绝，记录拒绝原因
            if (
                $verifyStatus === StaffCertificate::VERIFY_REJECT
                && self::hasStaffCertificateField('reject_reason')
            ) {
                $updateData['reject_reason'] = $rejectReason;
            }

            $certificate->save($updateData);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取证书字段集合
     */
    private static function getStaffCertificateFields(): array
    {
        if (self::$staffCertificateFields !== null) {
            return self::$staffCertificateFields;
        }

        $fields = Db::name('staff_certificate')->getFields();
        self::$staffCertificateFields = is_array($fields) ? $fields : [];

        return self::$staffCertificateFields;
    }

    /**
     * @notes 判断证书字段是否存在
     */
    private static function hasStaffCertificateField(string $field): bool
    {
        return isset(self::getStaffCertificateFields()[$field]);
    }

    /**
     * @notes 获取证书当前审核状态
     */
    private static function getCertificateVerifyStatus(StaffCertificate $certificate): int
    {
        $data = $certificate->getData();

        return (int) ($data['verify_status'] ?? $data['audit_status'] ?? StaffCertificate::VERIFY_PENDING);
    }

    /**
     * @notes 统一格式化证书详情
     */
    private static function formatCertificateDetail(
        array $detail,
        ?StaffCertificate $certificate = null
    ): array {
        $certificate = $certificate ?? new StaffCertificate();
        $certificate->data($detail, true);

        $detail['type'] = trim((string) ($detail['type'] ?? ''));
        $detail['sn'] = trim((string) ($detail['sn'] ?? $detail['certificate_no'] ?? ''));
        $detail['reject_reason'] = trim((string) ($detail['reject_reason'] ?? ''));
        $detail['verify_status'] = self::getCertificateVerifyStatus($certificate);
        $detail['verify_status_desc'] = $certificate->getAttr('verify_status_desc');
        $detail['is_expired'] = $certificate->getAttr('is_expired');

        return $detail;
    }

    /**
     * @notes 同步写入证书状态字段
     */
    private static function syncCertificateStatusPayload(array &$payload, int $status): void
    {
        if (self::hasStaffCertificateField('verify_status')) {
            $payload['verify_status'] = $status;
        }
        if (self::hasStaffCertificateField('audit_status')) {
            $payload['audit_status'] = $status;
        }
    }

    /**
     * @notes 同步写入证书编号字段
     */
    private static function syncCertificateNumberPayload(array &$payload, string $number): void
    {
        if (self::hasStaffCertificateField('sn')) {
            $payload['sn'] = $number;
        }
        if (self::hasStaffCertificateField('certificate_no')) {
            $payload['certificate_no'] = $number;
        }
    }
}
