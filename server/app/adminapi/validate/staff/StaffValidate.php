<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\staff;

use app\common\validate\BaseValidate;
use app\common\model\staff\Staff;

/**
 * 工作人员验证器
 * Class StaffValidate
 * @package app\adminapi\validate\staff
 */
class StaffValidate extends BaseValidate
{
    /**
     * @notes 验证规则
     * @var array
     */
    protected $rule = [
        'id' => 'require|checkStaff',
        'name' => 'require|length:1,50',
        'category_id' => 'require|integer|gt:0',
        'mobile' => 'mobile',
        'price' => 'float|egt:0',
        'experience_years' => 'integer|egt:0',
        'sort' => 'integer|egt:0',
        'status' => 'require|in:0,1',
        'is_recommend' => 'in:0,1',
    ];

    /**
     * @notes 错误提示
     * @var array
     */
    protected $message = [
        'id.require' => '请选择工作人员',
        'name.require' => '请输入姓名',
        'name.length' => '姓名长度为1-50个字符',
        'category_id.require' => '请选择服务分类',
        'category_id.integer' => '服务分类参数错误',
        'category_id.gt' => '请选择服务分类',
        'mobile.mobile' => '手机号格式不正确',
        'price.float' => '价格格式不正确',
        'price.egt' => '价格不能小于0',
        'experience_years.integer' => '从业年限格式不正确',
        'experience_years.egt' => '从业年限不能小于0',
        'sort.integer' => '排序格式不正确',
        'sort.egt' => '排序不能小于0',
        'status.require' => '请选择状态',
        'status.in' => '状态参数错误',
        'is_recommend.in' => '推荐参数错误',
    ];

    /**
     * @notes 添加场景
     * @return StaffValidate
     */
    public function sceneAdd(): StaffValidate
    {
        return $this->only(['name', 'category_id', 'mobile', 'price', 'experience_years', 'sort', 'status', 'is_recommend']);
    }

    /**
     * @notes 编辑场景
     * @return StaffValidate
     */
    public function sceneEdit(): StaffValidate
    {
        return $this->only(['id', 'name', 'category_id', 'mobile', 'price', 'experience_years', 'sort', 'status', 'is_recommend']);
    }

    /**
     * @notes 详情场景
     * @return StaffValidate
     */
    public function sceneDetail(): StaffValidate
    {
        return $this->only(['id']);
    }

    /**
     * @notes 删除场景
     * @return StaffValidate
     */
    public function sceneDelete(): StaffValidate
    {
        return $this->only(['id']);
    }

    /**
     * @notes 状态修改场景
     * @return StaffValidate
     */
    public function sceneStatus(): StaffValidate
    {
        return $this->only(['id', 'status']);
    }

    /**
     * @notes 检查工作人员是否存在
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    public function checkStaff($value, $rule, $data)
    {
        $staff = Staff::find($value);
        if (!$staff) {
            return '工作人员不存在';
        }
        return true;
    }
}
