<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

namespace app\common\model\service;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 服务分类模型
 * Class ServiceCategory
 * @package app\common\model\service
 */
class ServiceCategory extends BaseModel
{
    use SoftDelete;

    protected $name = 'service_category';
    protected $deleteTime = 'delete_time';

    /**
     * @notes 获取该分类下的工作人员数量
     * @return \think\model\relation\HasMany
     */
    public function staffs()
    {
        return $this->hasMany(\app\common\model\staff\Staff::class, 'category_id', 'id');
    }

    /**
     * @notes 获取该分类下的套餐
     * @return \think\model\relation\HasMany
     */
    public function packages()
    {
        return $this->hasMany(ServicePackage::class, 'category_id', 'id');
    }

    /**
     * @notes 图标获取器
     * @param $value
     * @return string
     */
    public function getIconAttr($value)
    {
        return trim($value) ? \app\common\service\FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 图标设置器
     * @param $value
     * @return string
     */
    public function setIconAttr($value)
    {
        return trim($value) ? \app\common\service\FileService::setFileUrl($value) : '';
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getIsShowDescAttr($value, $data)
    {
        return $data['is_show'] ? '显示' : '隐藏';
    }

    /**
     * @notes 获取所有分类(扁平列表)
     * @return array
     */
    public static function getCategoryList(): array
    {
        return self::where('delete_time', null)
            ->where('is_show', 1)
            ->order('sort desc, id asc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取分类树形结构
     * @param int $pid 父级ID
     * @return array
     */
    public static function getCategoryTree(int $pid = 0): array
    {
        $list = self::where('delete_time', null)
            ->where('is_show', 1)
            ->where('pid', $pid)
            ->order('sort desc, id asc')
            ->field('id, pid, name, icon, sort')
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            // 递归获取子分类
            $children = self::getCategoryTree($item['id']);
            if (!empty($children)) {
                $item['children'] = $children;
            }
        }

        return $list;
    }
}
