<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员证书列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\staff;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\staff\StaffCertificate;
use think\facade\Db;

/**
 * 工作人员证书列表
 * Class StaffCertificateLists
 * @package app\adminapi\lists\staff
 */
class StaffCertificateLists extends BaseAdminDataLists implements ListsSearchInterface
{
    private static ?array $staffCertificateFields = null;

    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [];
    }

    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function lists(): array
    {
        $list = $this->buildQuery()
            ->with(['staff' => function($query) {
                $query->field('id, name, sn');
            }])
            ->append(['verify_status_desc', 'is_expired'])
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        return array_map([$this, 'formatListItem'], $list);
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        return $this->buildQuery()->count();
    }

    /**
     * @notes 构造兼容旧字段的证书查询
     */
    private function buildQuery()
    {
        $query = StaffCertificate::where([]);

        $staffId = (int) ($this->params['staff_id'] ?? 0);
        if ($staffId > 0) {
            $query->where('staff_id', $staffId);
        }

        $name = trim((string) ($this->params['name'] ?? ''));
        if ($name !== '') {
            $query->whereLike('name', '%' . $name . '%');
        }

        $number = trim((string) ($this->params['sn'] ?? ''));
        if ($number !== '') {
            $query->whereLike($this->getCertificateNumberField(), '%' . $number . '%');
        }

        $verifyStatus = $this->params['verify_status'] ?? '';
        if ($verifyStatus !== '') {
            $query->where($this->getCertificateStatusField(), (int) $verifyStatus);
        }

        $type = trim((string) ($this->params['type'] ?? ''));
        if ($type !== '' && $this->hasCertificateField('type')) {
            $query->where('type', $type);
        }

        $staffScopeId = $this->getStaffScopeId();
        if ($staffScopeId > 0) {
            $query->where('staff_id', $staffScopeId);
        }

        return $query;
    }

    /**
     * @notes 统一补齐兼容字段
     */
    private function formatListItem(array $item): array
    {
        $item['type'] = trim((string) ($item['type'] ?? ''));
        $item['sn'] = trim((string) ($item['sn'] ?? $item['certificate_no'] ?? ''));
        $item['reject_reason'] = trim((string) ($item['reject_reason'] ?? ''));
        $item['verify_status'] = (int) (
            $item['verify_status']
                ?? $item['audit_status']
                ?? StaffCertificate::VERIFY_PENDING
        );

        return $item;
    }

    /**
     * @notes 获取证书状态字段名
     */
    private function getCertificateStatusField(): string
    {
        return $this->hasCertificateField('verify_status') ? 'verify_status' : 'audit_status';
    }

    /**
     * @notes 获取证书编号字段名
     */
    private function getCertificateNumberField(): string
    {
        return $this->hasCertificateField('sn') ? 'sn' : 'certificate_no';
    }

    /**
     * @notes 判断字段是否存在
     */
    private function hasCertificateField(string $field): bool
    {
        return isset($this->getCertificateFields()[$field]);
    }

    /**
     * @notes 读取证书表字段
     */
    private function getCertificateFields(): array
    {
        if (self::$staffCertificateFields !== null) {
            return self::$staffCertificateFields;
        }

        $fields = Db::name('staff_certificate')->getFields();
        self::$staffCertificateFields = is_array($fields) ? $fields : [];

        return self::$staffCertificateFields;
    }
}
