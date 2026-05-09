<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 结算验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\financial;

use app\common\validate\BaseValidate;

/**
 * 结算验证器
 * Class SettlementValidate
 * @package app\adminapi\validate\financial
 */
class SettlementValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'ids' => 'require|array',
        'batch_id' => 'require|integer|gt:0',
        'settle_start_date' => 'require|date',
        'settle_end_date' => 'require|date',
        'start_date' => 'date',
        'end_date' => 'date',
        'batch_name' => 'max:100',
        'status' => 'require|in:1,2',
        'remark' => 'max:255',
        'staff_id' => 'integer|egt:0',
        'category_id' => 'integer|egt:0',
        'settlement_rate' => 'require|float|between:0,100',
        'min_amount' => 'float|egt:0',
        'settle_cycle' => 'in:1,2,3',
        'settle_delay_days' => 'integer|egt:0',
        'enabled' => 'in:0,1',
        'auto_send' => 'in:0,1',
        'transfer_scene_id' => 'max:64',
        'transfer_remark' => 'max:32',
        'user_recv_perception' => 'max:32',
        'quota_hint' => 'max:255',
        'manual_fallback' => 'in:0,1',
        'amount_name_threshold' => 'float|gt:0',
        'wechatpay_serial' => 'max:128',
        'wechatpay_public_key' => 'max:4096',
        'transfer_scene_report_infos' => 'max:2000',
    ];

    protected $message = [
        'id.require' => 'ID不能为空',
        'id.integer' => 'ID必须是整数',
        'id.gt' => 'ID必须大于0',
        'ids.require' => 'ID列表不能为空',
        'ids.array' => 'ID列表格式错误',
        'batch_id.require' => '批次ID不能为空',
        'settle_start_date.require' => '结算开始日期不能为空',
        'settle_start_date.date' => '结算开始日期格式错误',
        'settle_end_date.require' => '结算结束日期不能为空',
        'settle_end_date.date' => '结算结束日期格式错误',
        'settlement_rate.require' => '结算比例不能为空',
        'settlement_rate.between' => '结算比例必须在0-100之间',
    ];

    protected $scene = [
        'detail' => ['id'],
        'settle' => ['id'],
        'batchSettle' => ['ids'],
        'generate' => ['start_date', 'end_date'],
        'syncTransfer' => [],
        'saveTransferConfig' => ['enabled', 'auto_send', 'transfer_scene_id', 'transfer_remark', 'user_recv_perception', 'quota_hint', 'manual_fallback', 'amount_name_threshold', 'wechatpay_serial', 'wechatpay_public_key', 'transfer_scene_report_infos'],
        'createBatch' => ['settle_start_date', 'settle_end_date'],
        'auditBatch' => ['batch_id', 'status'],
        'addConfig' => ['settlement_rate', 'staff_id', 'category_id'],
        'editConfig' => ['id', 'settlement_rate'],
    ];
}
