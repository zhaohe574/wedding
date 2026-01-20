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
     * @notes 获取子分类
     * @return \think\model\relation\HasMany
     */
    public function children()
    {
        return $this->hasMany(ServiceCategory::class, 'pid', 'id');
    }

    /**
     * @notes 获取上级分类
     * @return \think\model\relation\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(ServiceCategory::class, 'pid', 'id');
    }

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
     * @notes 获取所有分类(树形结构)
     * @return array
     */
    public static function getCategoryTree()
    {
        $list = self::where('delete_time', null)
            ->where('is_show', 1)
            ->order('sort desc, id asc')
            ->select()
            ->toArray();

        return self::buildTree($list);
    }

    /**
     * @notes 构建树形结构
     * @param array $list
     * @param int $pid
     * @return array
     */
    protected static function buildTree(array $list, int $pid = 0): array
    {
        $tree = [];
        foreach ($list as $item) {
            if ($item['pid'] == $pid) {
                $children = self::buildTree($list, $item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }
}
