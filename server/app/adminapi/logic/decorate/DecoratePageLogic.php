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
use app\common\service\SplashAdDecorateService;


/**
 * 装修页面
 * Class DecoratePageLogic
 * @package app\adminapi\logic\theme
 */
class DecoratePageLogic extends BaseLogic
{
    private const HOME_PAGE_ID = 1;
    private const HOME_WIDGET_ORDER = [
        'banner',
        'home-brand',
        'home-feature-carousel',
        'home-service-categories',
    ];


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
            if ((int)$id === SplashAdDecorateService::PAGE_ID) {
                return SplashAdDecorateService::defaultPage();
            }
            return [];
        }
        
        // 解析并填充动态数据
        $pageData = DecorateDataService::parsePageData($pageData);
        if ((int)$id === SplashAdDecorateService::PAGE_ID) {
            return SplashAdDecorateService::normalizePage($pageData);
        }

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

        if ((int)$params['id'] === SplashAdDecorateService::PAGE_ID) {
            $updateData['type'] = SplashAdDecorateService::PAGE_TYPE;
            $updateData['data'] = SplashAdDecorateService::normalizeDataForSave($params['data']);
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
     * @notes 首页装修仅保留固定模块
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

        $normalizedWidgets = [];
        foreach ($data as $widget) {
            if (!is_array($widget)) {
                continue;
            }

            $widgetName = (string)($widget['name'] ?? '');
            if (!in_array($widgetName, self::HOME_WIDGET_ORDER, true)) {
                continue;
            }

            if (isset($normalizedWidgets[$widgetName])) {
                continue;
            }

            $normalizedWidgets[$widgetName] = self::normalizeHomeWidget($widgetName, $widget);
        }

        $filteredData = [];
        foreach (self::HOME_WIDGET_ORDER as $widgetName) {
            $filteredData[] = $normalizedWidgets[$widgetName]
                ?? self::buildDefaultHomeWidget($widgetName);
        }

        $filteredData = array_values($filteredData);

        if ($isJsonString) {
            return json_encode($filteredData, JSON_UNESCAPED_UNICODE);
        }

        return $filteredData;
    }

    /**
     * @notes 构建首页默认组件
     * @param string $widgetName
     * @return array
     */
    private static function buildDefaultHomeWidget(string $widgetName): array
    {
        return match ($widgetName) {
            'home-brand' => self::buildDefaultHomeBrandWidget(),
            'home-feature-carousel' => self::buildDefaultHomeFeatureCarouselWidget(),
            'home-service-categories' => self::buildDefaultHomeServiceCategoriesWidget(),
            default => self::buildDefaultHomeBannerWidget(),
        };
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
                'bg_style' => 1,
                'height' => null,
                'overlap_height' => 280,
                'data' => [
                    [
                        'is_show' => '1',
                        'image' => '',
                        'bg' => '',
                        'bg_color' => '#000000',
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
     * @notes 构建首页默认团队信息组件
     * @return array
     */
    private static function buildDefaultHomeBrandWidget(): array
    {
        return [
            'id' => uniqid('home_brand_', true),
            'title' => '团队信息',
            'name' => 'home-brand',
            'pageScope' => ['home'],
            'content' => [
                'enabled' => 1,
                'greeting' => 'Hello,',
                'team_name' => '我们是专业主持团队',
                'subtitle' => '专业主持，用心服务',
                'cta_text' => '立即预定',
                'cta_link' => [
                    'path' => '/pages/schedule_query/schedule_query',
                    'type' => 'shop',
                ],
            ],
            'styles' => [],
        ];
    }

    /**
     * @notes 构建首页默认预约图片区组件
     * @return array
     */
    private static function buildDefaultHomeFeatureCarouselWidget(): array
    {
        return [
            'id' => uniqid('home_feature_', true),
            'title' => '预约图片区',
            'name' => 'home-feature-carousel',
            'pageScope' => ['home'],
            'content' => [
                'enabled' => 1,
                'height' => 300,
                'autoplay' => 1,
                'interval' => 5,
                'data' => [
                    [
                        'is_show' => '1',
                        'image' => '',
                        'name' => '',
                        'link' => [
                            'path' => '/pages/schedule_query/schedule_query',
                            'type' => 'shop',
                        ],
                    ],
                ],
            ],
            'styles' => [],
        ];
    }

    /**
     * @notes 构建首页默认服务分类组件
     * @return array
     */
    private static function buildDefaultHomeServiceCategoriesWidget(): array
    {
        return [
            'id' => uniqid('home_service_', true),
            'title' => '服务分类',
            'name' => 'home-service-categories',
            'pageScope' => ['home'],
            'content' => [
                'enabled' => 1,
                'data' => [
                    [
                        'is_show' => '1',
                        'title' => '西式主持',
                        'subtitle' => 'WEDDING HOST',
                        'image' => '',
                        'size' => 'large',
                        'text_position' => 'bottom',
                        'link' => [
                            'path' => '/pages/schedule_query/schedule_query',
                            'type' => 'shop',
                            'query' => ['keyword' => '西式主持'],
                        ],
                    ],
                    [
                        'is_show' => '1',
                        'title' => '中式主持',
                        'subtitle' => 'CHINESE HOST',
                        'image' => '',
                        'size' => 'small',
                        'text_position' => 'bottom',
                        'link' => [
                            'path' => '/pages/schedule_query/schedule_query',
                            'type' => 'shop',
                            'query' => ['keyword' => '中式主持'],
                        ],
                    ],
                    [
                        'is_show' => '1',
                        'title' => '商务主持',
                        'subtitle' => 'BUSINESS HOST',
                        'image' => '',
                        'size' => 'small',
                        'text_position' => 'bottom',
                        'link' => [
                            'path' => '/pages/schedule_query/schedule_query',
                            'type' => 'shop',
                            'query' => ['keyword' => '商务主持'],
                        ],
                    ],
                    [
                        'is_show' => '1',
                        'title' => '主持培训课程',
                        'subtitle' => 'HOST TRAINING',
                        'image' => '',
                        'size' => 'wide',
                        'text_position' => 'bottom',
                        'link' => [
                            'path' => '/pages/news/news',
                            'type' => 'shop',
                        ],
                    ],
                ],
            ],
            'styles' => [],
        ];
    }

    /**
     * @notes 首页组件归一化
     * @param string $widgetName
     * @param array $widget
     * @return array
     */
    private static function normalizeHomeWidget(string $widgetName, array $widget): array
    {
        $hasRawContentData = isset($widget['content'])
            && is_array($widget['content'])
            && array_key_exists('data', $widget['content']);
        $rawContentData = $hasRawContentData ? $widget['content']['data'] : null;
        $defaultWidget = self::buildDefaultHomeWidget($widgetName);
        $widget = array_replace_recursive($defaultWidget, $widget);
        if ($widgetName === 'home-service-categories' && $hasRawContentData) {
            $widget['content']['data'] = $rawContentData;
        }

        return match ($widgetName) {
            'banner' => self::normalizeHomeBannerWidget($widget),
            'home-feature-carousel' => self::normalizeHomeFeatureCarouselWidget($widget),
            'home-service-categories' => self::normalizeHomeServiceCategoriesWidget($widget),
            default => $widget,
        };
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
        $content['overlap_height'] = max(0, min(520, (int)($content['overlap_height'] ?? 280)));
        $content['data'] = array_slice(self::normalizeListLikeValue($content['data'] ?? []), 0, 1);
        foreach ($content['data'] as &$item) {
            if (!is_array($item)) {
                $item = [];
            }
            $item['bg_color'] = (string)($item['bg_color'] ?? '#000000');
            $item['slogan'] = (string)($item['slogan'] ?? '');
            $item['slogan_color'] = (string)($item['slogan_color'] ?? '');
        }
        unset($item);
        $widget['content'] = $content;

        return $widget;
    }

    /**
     * @notes 首页预约图片区归一化
     * @param array $widget
     * @return array
     */
    private static function normalizeHomeFeatureCarouselWidget(array $widget): array
    {
        $content = isset($widget['content']) && is_array($widget['content']) ? $widget['content'] : [];
        $interval = (int)($content['interval'] ?? 5);
        if ($interval > 100) {
            $interval = (int)round($interval / 1000);
        }
        $content['interval'] = max(2, min(10, $interval ?: 5));
        $content['data'] = self::normalizeListLikeValue($content['data'] ?? []);
        $widget['content'] = $content;

        return $widget;
    }

    /**
     * @notes 首页服务分类归一化
     * @param array $widget
     * @return array
     */
    private static function normalizeHomeServiceCategoriesWidget(array $widget): array
    {
        $content = isset($widget['content']) && is_array($widget['content']) ? $widget['content'] : [];
        $content['data'] = self::normalizeListLikeValue($content['data'] ?? []);
        foreach ($content['data'] as &$item) {
            if (!is_array($item)) {
                $item = [];
            }
            if (!in_array((string)($item['size'] ?? ''), ['large', 'small', 'wide'], true)) {
                $item['size'] = 'small';
            }
            if (!in_array((string)($item['text_position'] ?? ''), ['top', 'middle', 'bottom'], true)) {
                $item['text_position'] = 'bottom';
            }
        }
        unset($item);
        $widget['content'] = $content;

        return $widget;
    }

    /**
     * @notes 兼容数字键对象和 JSON 字符串列表
     * @param mixed $value
     * @return array
     */
    private static function normalizeListLikeValue($value): array
    {
        if (is_string($value)) {
            $decodedValue = json_decode($value, true);
            $value = is_array($decodedValue) ? $decodedValue : [];
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values($value);
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
