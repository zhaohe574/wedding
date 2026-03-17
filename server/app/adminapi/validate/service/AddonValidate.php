<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 附加服务验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\service;

use app\common\model\service\ServiceAddon;
use app\common\model\staff\Staff;
use app\common\validate\BaseValidate;

/**
 * 附加服务验证器
 * Class AddonValidate
 * @package app\adminapi\validate\service
 */
class AddonValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkAddon',
        'name' => 'require|max:100',
        'price' => 'require|float|egt:0',
        'original_price' => 'float|egt:0',
        'image' => 'max:255',
        'description' => 'max:500',
        'sort' => 'integer|egt:0',
        'is_show' => 'in:0,1',
        'staff_id' => 'require|integer|gt:0|checkStaff',
    ];

    protected $message = [
        'id.require' => '请选择附加服务',
        'name.require' => '请输入附加服务名称',
        'name.max' => '附加服务名称最多100个字符',
        'price.require' => '请输入附加服务价格',
        'price.float' => '价格必须为数字',
        'price.egt' => '价格必须大于等于0',
        'original_price.float' => '原价必须为数字',
        'original_price.egt' => '原价必须大于等于0',
        'image.max' => '图片地址最多255个字符',
        'description.max' => '描述最多500个字符',
        'sort.integer' => '排序必须为整数',
        'sort.egt' => '排序必须大于等于0',
        'is_show.in' => '显示状态值错误',
        'staff_id.require' => '请选择所属人员',
        'staff_id.integer' => '所属人员ID必须为整数',
        'staff_id.gt' => '请选择所属人员',
    ];

    protected $scene = [
        'add' => ['name', 'price', 'original_price', 'image', 'description', 'sort', 'is_show', 'staff_id'],
        'edit' => ['id', 'name', 'price', 'original_price', 'image', 'description', 'sort', 'is_show', 'staff_id'],
        'detail' => ['id'],
        'delete' => ['id'],
        'status' => ['id', 'is_show'],
    ];

    /**
     * @notes 校验附加服务是否存在
     * @param mixed $value
     * @return bool|string
     */
    protected function checkAddon($value)
    {
        $addon = ServiceAddon::find((int)$value);
        if (!$addon) {
            return '附加服务不存在';
        }

        return true;
    }

    /**
     * @notes 校验所属人员
     * @param mixed $value
     * @return bool|string
     */
    protected function checkStaff($value)
    {
        $staff = Staff::find((int)$value);
        if (!$staff) {
            return '所属人员不存在';
        }

        return true;
    }
}
