<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务套餐验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\service;

use app\common\validate\BaseValidate;
use app\common\model\service\ServicePackage;
use app\common\model\service\ServiceCategory;
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
        'category_id' => 'require|checkCategory',
        'name' => 'require|max:100',
        'price' => 'require|float|egt:0',
        'original_price' => 'float|egt:0',
        'duration' => 'require|integer|egt:1',
        'content' => 'array',
        'description' => 'max:500',
        'sort' => 'integer|egt:0',
        'is_show' => 'in:0,1',
        'is_recommend' => 'in:0,1',
        'staff_id' => 'integer|egt:0|checkStaff',
        'package_type' => 'in:1,2',
        'slot_prices' => 'array|checkSlotPrices',
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'id.require' => '请选择套餐',
        'category_id.require' => '请选择所属分类',
        'name.require' => '请输入套餐名称',
        'name.max' => '套餐名称最多100个字符',
        'price.require' => '请输入套餐价格',
        'price.float' => '价格必须为数字',
        'price.egt' => '价格必须大于等于0',
        'original_price.float' => '原价必须为数字',
        'original_price.egt' => '原价必须大于等于0',
        'duration.require' => '请输入服务时长',
        'duration.integer' => '服务时长必须为整数',
        'duration.egt' => '服务时长必须大于等于1小时',
        'content.array' => '套餐内容格式错误',
        'description.max' => '描述最多500个字符',
        'sort.integer' => '排序必须为整数',
        'sort.egt' => '排序必须大于等于0',
        'is_show.in' => '显示状态值错误',
        'is_recommend.in' => '推荐状态值错误',
        'staff_id.integer' => '员工ID必须为整数',
        'staff_id.egt' => '员工ID必须大于等于0',
        'package_type.in' => '套餐类型值错误',
        'slot_prices.array' => '时段价格格式错误',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'add' => ['category_id', 'name', 'price', 'original_price', 'duration', 'content', 'description', 'sort', 'is_show', 'is_recommend', 'staff_id', 'package_type', 'slot_prices'],
        'edit' => ['id', 'category_id', 'name', 'price', 'original_price', 'duration', 'content', 'description', 'sort', 'is_show', 'is_recommend', 'staff_id', 'package_type', 'slot_prices'],
        'detail' => ['id'],
        'delete' => ['id'],
        'status' => ['id', 'is_show'],
        'slotPrices' => ['id', 'slot_prices'],
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
        $package = ServicePackage::find($value);
        if (!$package) {
            return '套餐不存在';
        }
        return true;
    }

    /**
     * @notes 验证分类是否存在
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkCategory($value, $rule, $data)
    {
        $category = ServiceCategory::find($value);
        if (!$category) {
            return '所属分类不存在';
        }
        return true;
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
        if (empty($value) || $value == 0) {
            return true; // 0表示全局套餐，不需要验证
        }
        $staff = Staff::find($value);
        if (!$staff) {
            return '所属员工不存在';
        }
        return true;
    }

    /**
     * @notes 验证时段价格格式
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkSlotPrices($value, $rule, $data)
    {
        if (empty($value)) {
            return true;
        }

        if (!is_array($value)) {
            return '时段价格格式错误';
        }

        foreach ($value as $index => $slot) {
            if (!isset($slot['start_time']) || !isset($slot['end_time']) || !isset($slot['price'])) {
                return "时段价格第" . ($index + 1) . "项缺少必要字段";
            }

            // 验证时间格式 HH:mm
            if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $slot['start_time'])) {
                return "时段价格第" . ($index + 1) . "项开始时间格式错误，应为HH:mm格式";
            }

            if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $slot['end_time'])) {
                return "时段价格第" . ($index + 1) . "项结束时间格式错误，应为HH:mm格式";
            }

            // 验证价格
            if (!is_numeric($slot['price']) || $slot['price'] < 0) {
                return "时段价格第" . ($index + 1) . "项价格必须为大于等于0的数字";
            }

            // 验证结束时间大于开始时间
            if (strtotime($slot['end_time']) <= strtotime($slot['start_time'])) {
                return "时段价格第" . ($index + 1) . "项结束时间必须大于开始时间";
            }
        }

        return true;
    }
}
