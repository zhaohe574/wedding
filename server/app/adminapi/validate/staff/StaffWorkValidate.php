<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员作品验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate\staff;

use app\common\validate\BaseValidate;
use app\common\model\staff\StaffWork;
use app\common\model\staff\Staff;

/**
 * 工作人员作品验证器
 * Class StaffWorkValidate
 * @package app\adminapi\validate\staff
 */
class StaffWorkValidate extends BaseValidate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id' => 'require|checkWork',
        'staff_id' => 'require|checkStaff',
        'title' => 'require|max:100',
        'type' => 'in:1,2',
        'cover' => 'max:255',
        'images' => 'array',
        'video' => 'max:255',
        'video_url' => 'max:255',
        'description' => 'max:500',
        'sort' => 'integer|egt:0',
        'is_show' => 'in:0,1',
        'is_cover' => 'in:0,1',
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'id.require' => '请选择作品',
        'staff_id.require' => '请选择工作人员',
        'title.require' => '请输入作品标题',
        'title.max' => '作品标题最多100个字符',
        'type.in' => '作品类型值错误',
        'cover.max' => '封面地址最多255个字符',
        'images.array' => '图片列表格式错误',
        'video.max' => '视频地址最多255个字符',
        'video_url.max' => '视频地址最多255个字符',
        'description.max' => '描述最多500个字符',
        'sort.integer' => '排序必须为整数',
        'sort.egt' => '排序必须大于等于0',
        'is_show.in' => '显示状态值错误',
        'is_cover.in' => '封面状态值错误',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'add' => ['staff_id', 'title', 'type', 'cover', 'images', 'video', 'video_url', 'description', 'sort', 'is_show', 'is_cover'],
        'edit' => ['id', 'title', 'type', 'cover', 'images', 'video', 'video_url', 'description', 'sort', 'is_show', 'is_cover'],
        'detail' => ['id'],
        'delete' => ['id'],
        'status' => ['id', 'is_show'],
    ];

    /**
     * @notes 验证作品是否存在
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkWork($value, $rule, $data)
    {
        $work = StaffWork::find($value);
        if (!$work) {
            return '作品不存在';
        }
        return true;
    }

    /**
     * @notes 验证工作人员是否存在
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkStaff($value, $rule, $data)
    {
        $staff = Staff::find($value);
        if (!$staff) {
            return '工作人员不存在';
        }
        return true;
    }
}
