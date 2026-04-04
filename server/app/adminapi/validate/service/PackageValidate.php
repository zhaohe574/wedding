<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务套餐验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\service;

use app\common\validate\BaseValidate;
use app\common\model\service\ServicePackage;
use app\common\model\staff\Staff;

/**
 * 服务套餐验证器
 * Class PackageValidate
 * @package app\adminapi\validate\service
 */
class PackageValidate extends BaseValidate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id' => 'require|checkPackage',
        'name' => 'require|max:100',
        'price' => 'require|float|egt:0',
        'original_price' => 'float|egt:0',
        'duration' => 'integer|egt:1',
        'image' => 'max:255',
        'description' => 'max:500',
        'sort' => 'integer|egt:0',
        'is_show' => 'in:0,1',
        'is_recommend' => 'in:0,1',
        'staff_id' => 'require|integer|gt:0|checkStaff',
        'region_prices' => 'array',
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'id.require' => '请选择套餐',
        'name.require' => '请输入套餐名称',
        'name.max' => '套餐名称最多100个字符',
        'price.require' => '请输入套餐价格',
        'price.float' => '价格必须为数字',
        'price.egt' => '价格必须大于等于0',
        'original_price.float' => '原价必须为数字',
        'original_price.egt' => '原价必须大于等于0',
        'image.max' => '图片地址最多255个字符',
        'description.max' => '描述最多500个字符',
        'sort.integer' => '排序必须为整数',
        'sort.egt' => '排序必须大于等于0',
        'is_show.in' => '显示状态值错误',
        'is_recommend.in' => '推荐状态值错误',
        'staff_id.require' => '请选择所属人员',
        'staff_id.integer' => '员工ID必须为整数',
        'staff_id.gt' => '请选择所属人员',
        'region_prices.array' => '地区价格参数格式错误',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'add' => ['name', 'price', 'original_price', 'duration', 'image', 'description', 'sort', 'is_show', 'is_recommend', 'staff_id', 'region_prices'],
        'edit' => ['id', 'name', 'price', 'original_price', 'duration', 'image', 'description', 'sort', 'is_show', 'is_recommend', 'staff_id', 'region_prices'],
        'detail' => ['id'],
        'delete' => ['id'],
        'status' => ['id', 'is_show'],
    ];

    /**
     * @notes 验证套餐是否存在
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkPackage($value, $rule, $data)
    {
        try {
            $package = ServicePackage::find($value);
            if (!$package) {
                return '套餐不存在';
            }
            return true;
        } catch (\Exception $e) {
            return '验证套餐失败: ' . $e->getMessage();
        }
    }

    /**
     * @notes 验证员工是否存在
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkStaff($value, $rule, $data)
    {
        $staff = Staff::find($value);
        if (!$staff) {
            return '所属员工不存在';
        }
        return true;
    }
}
