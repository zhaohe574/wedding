<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------
namespace app\common\model\decorate;


use app\common\model\BaseModel;
use app\common\service\FileService;


/**
 * 装修配置-底部导航
 * Class DecorateTabbar
 * @package app\common\model\decorate
 */
class DecorateTabbar extends BaseModel
{
    // 设置json类型字段
    protected $json = ['link'];

    // 设置JSON数据返回数组
    protected $jsonAssoc = true;

    /**
     * @notes 默认底部导航配置
     * @return array
     */
    public static function defaultTabbarLists(): array
    {
        return [
            [
                'name' => '首页',
                'selected' => FileService::getFileUrl('resource/image/adminapi/default/tabbar_home_sel.png'),
                'unselected' => FileService::getFileUrl('resource/image/adminapi/default/tabbar_home.png'),
                'link' => [
                    'path' => '/pages/index/index',
                    'name' => '首页',
                    'type' => 'shop',
                    'canTab' => true,
                ],
                'is_show' => 1,
            ],
            [
                'name' => '动态',
                'selected' => FileService::getFileUrl('resource/image/adminapi/default/tabbar_text_sel.png'),
                'unselected' => FileService::getFileUrl('resource/image/adminapi/default/tabbar_text.png'),
                'link' => [
                    'path' => '/pages/dynamic/dynamic',
                    'name' => '动态',
                    'type' => 'shop',
                    'canTab' => true,
                ],
                'is_show' => 1,
            ],
            [
                'name' => '我的',
                'selected' => FileService::getFileUrl('resource/image/adminapi/default/tabbar_me_sel.png'),
                'unselected' => FileService::getFileUrl('resource/image/adminapi/default/tabbar_me.png'),
                'link' => [
                    'path' => '/pages/user/user',
                    'name' => '我的',
                    'type' => 'shop',
                    'canTab' => true,
                ],
                'is_show' => 1,
            ],
        ];
    }


    /**
     * @notes 获取底部导航列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/23 12:07
     */
    public static function getTabbarLists()
    {
        $tabbar = self::select()->toArray();

        if (empty($tabbar)) {
           return self::defaultTabbarLists();
        }

        foreach ($tabbar as &$item) {
            if (!empty($item['selected'])) {
                $item['selected'] = FileService::getFileUrl($item['selected']);
            }
            if (!empty($item['unselected'])) {
                $item['unselected'] = FileService::getFileUrl($item['unselected']);
            }
        }

        return $tabbar;
    }
}
