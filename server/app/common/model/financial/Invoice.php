<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 发票记录模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\financial;

use app\common\model\BaseModel;
use app\common\model\user\User;
use app\common\model\order\Order;

/**
 * 发票记录模型
 * Class Invoice
 * @package app\common\model\financial
 */
class Invoice extends BaseModel
{
    protected $name = 'invoice';

    // 发票类型
    const TYPE_E_NORMAL = 1;    // 电子普票
    const TYPE_E_SPECIAL = 2;   // 电子专票
    const TYPE_P_NORMAL = 3;    // 纸质普票
    const TYPE_P_SPECIAL = 4;   // 纸质专票

    // 抬头类型
    const TITLE_TYPE_PERSONAL = 1;   // 个人
    const TITLE_TYPE_COMPANY = 2;    // 企业

    // 状态
    const STATUS_PENDING = 0;    // 待开票
    const STATUS_PROCESSING = 1; // 开票中
    const STATUS_ISSUED = 2;     // 已开票
    const STATUS_FAILED = 3;     // 开票失败
    const STATUS_VOID = 4;       // 已作废

    /**
     * @notes 发票类型描述
     */
    public static function getTypeDesc($value = true)
    {
        $data = [
            self::TYPE_E_NORMAL => '电子普票',
            self::TYPE_E_SPECIAL => '电子专票',
            self::TYPE_P_NORMAL => '纸质普票',
            self::TYPE_P_SPECIAL => '纸质专票',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 抬头类型描述
     */
    public static function getTitleTypeDesc($value = true)
    {
        $data = [
            self::TITLE_TYPE_PERSONAL => '个人',
            self::TITLE_TYPE_COMPANY => '企业',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 状态描述
     */
    public static function getStatusDesc($value = true)
    {
        $data = [
            self::STATUS_PENDING => '待开票',
            self::STATUS_PROCESSING => '开票中',
            self::STATUS_ISSUED => '已开票',
            self::STATUS_FAILED => '开票失败',
            self::STATUS_VOID => '已作废',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->field('id, nickname, avatar, mobile');
    }

    /**
     * @notes 关联订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id')
            ->field('id, order_sn, total_amount, pay_amount');
    }

    /**
     * @notes 生成发票编号
     */
    public static function generateInvoiceSn(): string
    {
        return 'INV' . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * @notes 申请开票
     */
    public static function apply(array $data): self
    {
        $invoice = new self();
        $invoice->invoice_sn = self::generateInvoiceSn();
        $invoice->order_id = $data['order_id'];
        $invoice->user_id = $data['user_id'];
        $invoice->invoice_type = $data['invoice_type'] ?? self::TYPE_E_NORMAL;
        $invoice->title_type = $data['title_type'] ?? self::TITLE_TYPE_PERSONAL;
        $invoice->invoice_title = $data['invoice_title'];
        $invoice->tax_no = $data['tax_no'] ?? '';
        $invoice->bank_name = $data['bank_name'] ?? '';
        $invoice->bank_account = $data['bank_account'] ?? '';
        $invoice->company_address = $data['company_address'] ?? '';
        $invoice->company_phone = $data['company_phone'] ?? '';
        $invoice->amount = $data['amount'];
        $invoice->email = $data['email'] ?? '';
        $invoice->receiver_name = $data['receiver_name'] ?? '';
        $invoice->receiver_phone = $data['receiver_phone'] ?? '';
        $invoice->receiver_address = $data['receiver_address'] ?? '';
        $invoice->status = self::STATUS_PENDING;
        $invoice->remark = $data['remark'] ?? '';
        $invoice->save();
        return $invoice;
    }

    /**
     * @notes 开始开票
     */
    public function startIssue(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }
        $this->status = self::STATUS_PROCESSING;
        return $this->save();
    }

    /**
     * @notes 开票成功
     */
    public function issue(int $adminId, string $invoiceNo, string $invoiceUrl = ''): bool
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING])) {
            return false;
        }
        $this->status = self::STATUS_ISSUED;
        $this->invoice_no = $invoiceNo;
        $this->invoice_url = $invoiceUrl;
        $this->issue_time = time();
        $this->issue_admin_id = $adminId;
        return $this->save();
    }

    /**
     * @notes 开票失败
     */
    public function fail(string $reason): bool
    {
        $this->status = self::STATUS_FAILED;
        $this->fail_reason = $reason;
        return $this->save();
    }

    /**
     * @notes 作废发票
     */
    public function void(string $reason): bool
    {
        if ($this->status !== self::STATUS_ISSUED) {
            return false;
        }
        $this->status = self::STATUS_VOID;
        $this->void_reason = $reason;
        $this->void_time = time();
        return $this->save();
    }

    /**
     * @notes 检查订单是否已开票
     */
    public static function hasInvoice(int $orderId): bool
    {
        return self::where('order_id', $orderId)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_ISSUED])
            ->count() > 0;
    }

    /**
     * @notes 获取用户的发票列表
     */
    public static function getUserInvoices(int $userId, int $page = 1, int $pageSize = 10): array
    {
        $query = self::where('user_id', $userId)
            ->with(['order'])
            ->order('create_time', 'desc');
        
        $total = $query->count();
        $list = $query->page($page, $pageSize)->select();
        
        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }
}
