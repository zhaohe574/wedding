<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务队伍验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\staff;

use app\common\validate\BaseValidate;

/**
 * 服务队伍验证器
 */
class StaffTeamValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'name' => 'require|max:100',
        'leader_staff_id' => 'require|integer|gt:0',
        'member_ids' => 'array',
        'status' => 'in:0,1',
        'sort' => 'integer|egt:0',
        'remark' => 'max:255',
    ];

    protected $message = [
        'id.require' => '请选择服务队伍',
        'id.integer' => '服务队伍参数错误',
        'id.gt' => '服务队伍参数错误',
        'name.require' => '请输入队伍名称',
        'name.max' => '队伍名称最多100个字符',
        'leader_staff_id.require' => '请选择队长',
        'leader_staff_id.integer' => '队长参数错误',
        'leader_staff_id.gt' => '队长参数错误',
        'member_ids.array' => '队员格式错误',
        'status.in' => '状态值错误',
        'sort.integer' => '排序必须是整数',
        'sort.egt' => '排序必须大于等于0',
        'remark.max' => '备注最多255个字符',
    ];

    protected $scene = [
        'detail' => ['id'],
        'add' => ['name', 'leader_staff_id', 'member_ids', 'status', 'sort', 'remark'],
        'edit' => ['id', 'name', 'leader_staff_id', 'member_ids', 'status', 'sort', 'remark'],
        'delete' => ['id'],
        'status' => ['id', 'status'],
    ];
}
