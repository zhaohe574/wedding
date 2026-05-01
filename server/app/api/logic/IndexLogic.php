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

namespace app\api\logic;


use app\common\logic\BaseLogic;
use app\common\model\article\Article;
use app\common\model\decorate\DecoratePage;
use app\common\model\decorate\DecorateTabbar;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\DecorateDataService;
use app\common\service\LoginConfigService;
use app\common\service\SplashAdDecorateService;


/**
 * index
 * Class IndexLogic
 * @package app\api\logic
 */
class IndexLogic extends BaseLogic
{
    private const HOME_PAGE_ID = 1;
    private const HOME_WIDGET_ORDER = [
        'banner',
        'home-brand',
        'home-feature-carousel',
        'home-service-categories',
    ];

    /**
     * @notes 首页数据
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/21 19:15
     */
    public static function getIndexData()
    {
        // 装修配置
        $decoratePage = DecoratePage::findOrEmpty(1)->toArray();
        
        // 动态填充装修数据
        if (!empty($decoratePage)) {
            $decoratePage = DecorateDataService::parsePageData($decoratePage);
            $decoratePage = self::normalizeHomePageData($decoratePage, 1);
        }

        // 首页文章
        $field = [
            'id', 'title', 'desc', 'abstract', 'image',
            'author', 'click_actual', 'click_virtual', 'create_time'
        ];

        $article = Article::field($field)
            ->where(['is_show' => 1])
            ->order(['id' => 'desc'])
            ->limit(20)->append(['click'])
            ->hidden(['click_actual', 'click_virtual'])
            ->select()->toArray();

        return [
            'page' => $decoratePage,
            'article' => $article
        ];
    }


    /**
     * @notes 获取政策协议
     * @param string $type
     * @return array
     * @author 段誉
     * @date 2022/9/20 20:00
     */
    public static function getPolicyByType(string $type)
    {
        return [
            'title' => ConfigService::get('agreement', $type . '_title', ''),
            'content' => ConfigService::get('agreement', $type . '_content', ''),
        ];
    }


    /**
     * @notes 装修信息
     * @param $id
     * @return array
     * @author 段誉
     * @date 2022/9/21 18:37
     */
    public static function getDecorate($id)
    {
        $pageData = DecoratePage::field(['type', 'name', 'data', 'meta'])
            ->findOrEmpty($id)->toArray();
        
        if (empty($pageData)) {
            if ((int)$id === SplashAdDecorateService::PAGE_ID) {
                return SplashAdDecorateService::defaultPage();
            }
            return [];
        }
        
        // 动态填充业务数据
        $pageData = DecorateDataService::parsePageData($pageData);
        if ((int)$id === SplashAdDecorateService::PAGE_ID) {
            return SplashAdDecorateService::normalizePage($pageData);
        }

        return self::normalizeHomePageData($pageData, (int)$id);
    }


    /**
     * @notes 获取配置
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/21 19:38
     */
    public static function getConfigData()
    {
        // 底部导航
        $tabbar = DecorateTabbar::getTabbarLists();
        // 导航颜色
        $style = ConfigService::get('tabbar', 'style', config('project.decorate.tabbar_style'));
        // 登录配置
        $loginConfig = LoginConfigService::getConfig();
        // 网址信息
        $website = [
            'h5_favicon' => FileService::getFileUrl(ConfigService::get('website', 'h5_favicon')),
            'shop_name' => ConfigService::get('website', 'shop_name'),
            'shop_slogan' => ConfigService::get('website', 'shop_slogan') ?: '',
            'shop_logo' => FileService::getFileUrl(ConfigService::get('website', 'shop_logo')),
        ];
        // H5配置
        $webPage = [
            // 渠道状态 0-关闭 1-开启
            'status' => ConfigService::get('web_page', 'status', 1),
            // 关闭后渠道后访问页面 0-空页面 1-自定义链接
            'page_status' => ConfigService::get('web_page', 'page_status', 0),
            // 自定义链接
            'page_url' => ConfigService::get('web_page', 'page_url', ''),
            'url' => request()->domain() . '/mobile'
        ];

        // 备案信息
        $copyright = ConfigService::get('copyright', 'config', []);
        // 功能开关
        $featureSwitch = [
            'staff_center' => (int) ConfigService::get('feature_switch', 'staff_center', 1),
            'staff_admin' => (int) ConfigService::get('feature_switch', 'staff_admin', 1),
            'staff_tag_review_enabled' => (int) ConfigService::get('feature_switch', 'staff_tag_review_enabled', 0),
            'mini_program_review_mode' => (int) ConfigService::get('feature_switch', 'mini_program_review_mode', 0),
            'admin_dashboard' => (int) ConfigService::get('feature_switch', 'admin_dashboard', 1),
            'admin_dashboard_user_ids' => (string) ConfigService::get('feature_switch', 'admin_dashboard_user_ids', ''),
        ];

        return [
            'domain' => FileService::getFileUrl(),
            'style' => $style,
            'tabbar' => $tabbar,
            'login' => $loginConfig,
            'website' => $website,
            'webPage' => $webPage,
            'version'=> config('project.version'),
            'copyright' => $copyright,
            'feature_switch' => $featureSwitch,
        ];
    }

    /**
     * @notes 首页装修固定模块归一化
     * @param array $pageData
     * @param int $pageId
     * @return array
     */
    private static function normalizeHomePageData(array $pageData, int $pageId): array
    {
        if ($pageId !== self::HOME_PAGE_ID) {
            return $pageData;
        }

        $data = $pageData['data'];
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

        $data = [];
        foreach (self::HOME_WIDGET_ORDER as $widgetName) {
            $data[] = $normalizedWidgets[$widgetName] ?? self::buildDefaultHomeWidget($widgetName);
        }

        $pageData['data'] = $isJsonString
            ? json_encode($data, JSON_UNESCAPED_UNICODE)
            : $data;

        return $pageData;
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

        if ($widgetName === 'banner') {
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
        }

        if (in_array($widgetName, ['home-feature-carousel', 'home-service-categories'], true)) {
            $content = isset($widget['content']) && is_array($widget['content']) ? $widget['content'] : [];
            if ($widgetName === 'home-feature-carousel') {
                $interval = (int)($content['interval'] ?? 5);
                if ($interval > 100) {
                    $interval = (int)round($interval / 1000);
                }
                $content['interval'] = max(2, min(10, $interval ?: 5));
            }
            $content['data'] = self::normalizeListLikeValue($content['data'] ?? []);
            if ($widgetName === 'home-service-categories') {
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
            }
            $widget['content'] = $content;
        }

        return $widget;
    }

    /**
     * @notes 构建首页默认组件
     * @param string $widgetName
     * @return array
     */
    private static function buildDefaultHomeWidget(string $widgetName): array
    {
        return match ($widgetName) {
            'home-brand' => [
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
            ],
            'home-feature-carousel' => [
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
            ],
            'home-service-categories' => [
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
            ],
            default => [
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
            ],
        };
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

}
