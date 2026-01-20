<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 发票管理逻辑层
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\financial;

use app\common\logic\BaseLogic;
use app\common\model\financial\Invoice;

/**
 * 发票管理逻辑层
 * Class InvoiceLogic
 * @package app\adminapi\logic\financial
 */
class InvoiceLogic extends BaseLogic
{
    /**
     * @notes 发票详情
     */
    public static function detail(int $id): array
    {
        $invoice = Invoice::with(['user', 'order'])->find($id);
        if (!$invoice) {
            return [];
        }
        
        $data = $invoice->toArray();
        $data['invoice_type_text'] = Invoice::getTypeDesc($invoice->invoice_type);
        $data['title_type_text'] = Invoice::getTitleTypeDesc($invoice->title_type);
        $data['status_text'] = Invoice::getStatusDesc($invoice->status);
        
        return $data;
    }

    /**
     * @notes 开票
     */
    public static function issue(array $params, int $adminId): bool
    {
        try {
            $invoice = Invoice::find($params['id']);
            if (!$invoice) {
                self::setError('发票记录不存在');
                return false;
            }
            
            return $invoice->issue($adminId, $params['invoice_no'], $params['invoice_url'] ?? '');
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 开票失败
     */
    public static function fail(array $params): bool
    {
        try {
            $invoice = Invoice::find($params['id']);
            if (!$invoice) {
                self::setError('发票记录不存在');
                return false;
            }
            
            return $invoice->fail($params['fail_reason']);
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 作废发票
     */
    public static function void(array $params): bool
    {
        try {
            $invoice = Invoice::find($params['id']);
            if (!$invoice) {
                self::setError('发票记录不存在');
                return false;
            }
            
            return $invoice->void($params['void_reason']);
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 发票统计
     */
    public static function statistics(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate . ' 23:59:59');
        
        $query = Invoice::whereBetweenTime('create_time', $startTime, $endTime);
        
        return [
            'total_count' => (clone $query)->count(),
            'pending_count' => (clone $query)->where('status', Invoice::STATUS_PENDING)->count(),
            'issued_count' => (clone $query)->where('status', Invoice::STATUS_ISSUED)->count(),
            'void_count' => (clone $query)->where('status', Invoice::STATUS_VOID)->count(),
            'total_amount' => (clone $query)->where('status', Invoice::STATUS_ISSUED)->sum('amount'),
        ];
    }

    /**
     * @notes 发票类型选项
     */
    public static function typeOptions(): array
    {
        $types = Invoice::getTypeDesc();
        $result = [];
        foreach ($types as $value => $label) {
            $result[] = [
                'value' => $value,
                'label' => $label,
            ];
        }
        return $result;
    }
}
