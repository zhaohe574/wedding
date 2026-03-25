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
namespace app\adminapi\logic\decorate;

use app\common\logic\BaseLogic;
use app\common\model\decorate\DecorateTabbar;
use app\common\service\ConfigService;
use app\common\service\FileService;


/**
 * 装修配置-底部导航
 * Class DecorateTabbarLogic
 * @package app\adminapi\logic\decorate
 */
class DecorateTabbarLogic extends BaseLogic
{
    /**
     * @notes 固定底部导航项
     * @return array
     */
    protected static function defaultTabbarList(): array
    {
        return [
            [
                'name' => '首页',
                'selected' => '',
                'unselected' => '',
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
                'selected' => '',
                'unselected' => '',
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
                'selected' => '',
                'unselected' => '',
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
     * @notes 规范化底部导航项，只保留设计稿固定的 3 项
     * @param array $tabbars
     * @return array
     */
    protected static function normalizeTabbarList(array $tabbars): array
    {
        $tabbarMap = [];
        foreach ($tabbars as $item) {
            $path = trim((string)($item['link']['path'] ?? ''));
            if ($path === '') {
                continue;
            }

            $tabbarMap[$path] = $item;
        }

        $result = [];
        foreach (self::defaultTabbarList() as $defaultItem) {
            $path = $defaultItem['link']['path'];
            $currentItem = $tabbarMap[$path] ?? [];

            $result[] = [
                'name' => $defaultItem['name'],
                'selected' => $currentItem['selected'] ?? $defaultItem['selected'],
                'unselected' => $currentItem['unselected'] ?? $defaultItem['unselected'],
                'link' => $defaultItem['link'],
                'is_show' => 1,
            ];
        }

        return $result;
    }

    /**
     * @notes 获取底部导航详情
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/7 16:58
     */
    public static function detail(): array
    {
        $list = DecorateTabbar::getTabbarLists();
        $style = ConfigService::get('tabbar', 'style', config('project.decorate.tabbar_style'));
        return ['style' => $style, 'list' => self::normalizeTabbarList($list)];
    }


    /**
     * @notes 底部导航保存
     * @param $params
     * @return bool
     * @throws \Exception
     * @author 段誉
     * @date 2022/9/7 17:19
     */
    public static function save($params): bool
    {
        $model = new DecorateTabbar();
        // 删除旧配置数据
        $model->where('id', '>', 0)->delete();

        // 保存数据
        $tabbars = self::normalizeTabbarList($params['list'] ?? []);
        $data = [];
        foreach ($tabbars as $item) {
            $data[] = [
                'name' => $item['name'],
                'selected' => FileService::setFileUrl($item['selected']),
                'unselected' => FileService::setFileUrl($item['unselected']),
                'link' => $item['link'],
                'is_show' => $item['is_show'] ?? 0,
            ];
        }
        $model->saveAll($data);

        if (!empty($params['style'])) {
            ConfigService::set('tabbar', 'style', $params['style']);
        }
        return true;
    }

}
