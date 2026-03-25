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
use app\common\model\decorate\DecoratePage;
use app\common\service\DecorateDataService;


/**
 * 装修页面
 * Class DecoratePageLogic
 * @package app\adminapi\logic\theme
 */
class DecoratePageLogic extends BaseLogic
{
    private const HOME_PAGE_ID = 1;
    private const HOME_ALLOWED_WIDGETS = ['banner'];


    /**
     * @notes 获取详情
     * @param $id
     * @return array
     * @author 段誉
     * @date 2022/9/14 18:41
     */
    public static function getDetail($id)
    {
        $pageData = DecoratePage::findOrEmpty($id)->toArray();
        
        if (empty($pageData)) {
            return [];
        }
        
        // 解析并填充动态数据
        $pageData = DecorateDataService::parsePageData($pageData);

        return self::filterDetailByPageId($pageData);
    }


    /**
     * @notes 保存装修配置
     * @param $params
     * @return bool
     * @author 段誉
     * @date 2022/9/15 9:37
     */
    public static function save($params)
    {
        $pageData = DecoratePage::where(['id' => $params['id']])->findOrEmpty();
        if ($pageData->isEmpty()) {
            self::$error = '信息不存在';
            return false;
        }

        $updateData = [
            'id' => $params['id'],
            'type' => $params['type'],
            'data' => $params['data'],
            'meta' => $params['meta'] ?? '',
        ];

        if (self::isHomePage((int)$params['id'])) {
            $updateData['data'] = self::filterHomePageData($params['data']);
            $updateData['meta'] = $pageData->meta ?? '';
        }

        DecoratePage::update($updateData);
        return true;
    }

    /**
     * @notes 按页面类型过滤详情
     * @param array $pageData
     * @return array
     */
    private static function filterDetailByPageId(array $pageData): array
    {
        if (!self::isHomePage((int)($pageData['id'] ?? 0))) {
            return $pageData;
        }

        $pageData['data'] = self::filterHomePageData($pageData['data'] ?? []);
        return $pageData;
    }

    /**
     * @notes 首页装修仅保留轮播图组件
     * @param mixed $data
     * @return array|string
     */
    private static function filterHomePageData($data)
    {
        $isJsonString = is_string($data);
        if ($isJsonString) {
            $decodedData = json_decode($data, true);
            $data = is_array($decodedData) ? $decodedData : [];
        }

        if (!is_array($data)) {
            $data = [];
        }

        $filteredData = [];
        foreach ($data as $widget) {
            if (!is_array($widget)) {
                continue;
            }

            if (!in_array((string)($widget['name'] ?? ''), self::HOME_ALLOWED_WIDGETS, true)) {
                continue;
            }

            $widget = self::normalizeHomeBannerWidget($widget);
            $filteredData[] = $widget;
        }

        if (empty($filteredData)) {
            $filteredData = [self::buildDefaultHomeBannerWidget()];
        }

        $filteredData = array_values($filteredData);

        if ($isJsonString) {
            return json_encode($filteredData, JSON_UNESCAPED_UNICODE);
        }

        return $filteredData;
    }

    /**
     * @notes 构建首页默认轮播图组件
     * @return array
     */
    private static function buildDefaultHomeBannerWidget(): array
    {
        return [
            'id' => uniqid('banner_', true),
            'title' => '首页轮播图',
            'name' => 'banner',
            'pageScope' => ['home'],
            'content' => [
                'enabled' => 1,
                'style' => 1,
                'bg_style' => 0,
                'height' => null,
                'data' => [
                    [
                        'is_show' => '1',
                        'image' => '',
                        'bg' => '',
                        'name' => '',
                        'slogan' => '',
                        'slogan_top' => null,
                        'slogan_color' => '',
                        'link' => [],
                    ],
                ],
            ],
            'styles' => [],
        ];
    }

    /**
     * @notes 首页轮播图固定常规模式
     * @param array $widget
     * @return array
     */
    private static function normalizeHomeBannerWidget(array $widget): array
    {
        if ((string)($widget['name'] ?? '') !== 'banner') {
            return $widget;
        }

        $content = isset($widget['content']) && is_array($widget['content']) ? $widget['content'] : [];
        $content['style'] = 1;
        $widget['content'] = $content;

        return $widget;
    }

    /**
     * @notes 判断是否为首页装修
     * @param int $pageId
     * @return bool
     */
    private static function isHomePage(int $pageId): bool
    {
        return $pageId === self::HOME_PAGE_ID;
    }



}
