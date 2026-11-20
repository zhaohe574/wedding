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
use app\common\service\HomeDecorateService;
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
    // 与 shared/contracts/decorate.ts 的 DECORATE_PAGES[home].widgets 保持一致。
    // home-feature-carousel 已下线：小程序从未渲染过它，继续保留只会产生「配了不生效」的错觉。
    private const HOME_WIDGET_ORDER = [
        'banner',
        'home-brand',
        'home-service-categories',
        'home-flow',
        'home-popup-ad',
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
        $type = trim($type);
        $title = trim((string)ConfigService::get('agreement', $type . '_title', ''));
        $rawContent = (string)ConfigService::get('agreement', $type . '_content', '');

        // 内容为空时返回 null 而不是 ''，让前端能区分“后台没配置”和“配置了但正文为空”。
        // 注意：只把“去掉标签后没有任何可见文字”的情况判为空，避免 <p><br></p> 这类空壳被判成未配置。
        $content = $rawContent;
        if (trim(strip_tags(str_replace('&nbsp;', ' ', $rawContent))) === '') {
            $content = null;
        }

        return [
            'title' => $title,
            'content' => $content,
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
            // 开屏素材补绝对地址：小程序会在 /index/config 之前用它渲染，拿不到 domain
            return SplashAdDecorateService::withAbsoluteMedia(
                SplashAdDecorateService::normalizePage($pageData)
            );
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
        // 这三个字段是小程序端**唯一的品牌真源**（名称 / 图标 / Slogan），必须做「有效值」判定，
        // 否则会把模板默认值当成真品牌下发：
        //   1. ConfigService 在库里没有配置时会回落到 config/project.php 的 likeadmin 模板值；
        //   2. FileService::getFileUrl('') 会拼出「域名 + /」这种不可用地址。
        // 判定为无效时统一下发空串，由前端走「本地缓存 → 内置兜底」，不要把 likeadmin 显示给用户。
        $shopName = trim((string) ConfigService::get('website', 'shop_name', ''));
        if ($shopName === '' || strcasecmp($shopName, 'likeadmin') === 0) {
            $shopName = '';
        }
        $shopSlogan = trim((string) ConfigService::get('website', 'shop_slogan', ''));
        $shopLogoRaw = trim((string) ConfigService::get('website', 'shop_logo', ''));
        // 模板自带的占位图标（public/resource/image/adminapi/default/）不是本店图标
        $isTemplateLogo = $shopLogoRaw === ''
            || strpos($shopLogoRaw, 'resource/image/adminapi/default/') === 0;
        $website = [
            'shop_name' => $shopName,
            'shop_slogan' => $shopSlogan,
            'shop_logo' => $isTemplateLogo ? '' : FileService::getFileUrl($shopLogoRaw),
        ];
        // 备案信息
        $copyright = ConfigService::get('copyright', 'config', []);
        // 功能开关
        $featureSwitch = [
            'staff_center' => (int) ConfigService::get('feature_switch', 'staff_center', 1),
            // 服务人员中心写能力灰度：工作台写入口（资料/动态/作品/证书）是否开放
            'staff_center_write' => (int) ConfigService::get('feature_switch', 'staff_center_write', 0),
            'staff_admin' => (int) ConfigService::get('feature_switch', 'staff_admin', 1),
            'staff_tag_review_enabled' => (int) ConfigService::get('feature_switch', 'staff_tag_review_enabled', 0),
            'mini_program_review_mode' => (int) ConfigService::get('feature_switch', 'mini_program_review_mode', 0),
            'admin_dashboard' => (int) ConfigService::get('feature_switch', 'admin_dashboard', 1),
        ];
        $appUpdate = [
            'version' => (string) ConfigService::get('app_update', 'version', config('project.version')),
            'force_update' => (int) ConfigService::get('app_update', 'force_update', 0),
            'update_title' => (string) ConfigService::get('app_update', 'update_title', '新版本已准备好'),
            'update_content' => (string) ConfigService::get('app_update', 'update_content', '为保证支付、订单和档期功能正常，请重启后继续使用。'),
        ];

        return [
            'domain' => FileService::getFileUrl(),
            'style' => $style,
            'tabbar' => $tabbar,
            'login' => $loginConfig,
            'website' => $website,
            'version'=> config('project.version'),
            'copyright' => $copyright,
            'feature_switch' => $featureSwitch,
            'app_update' => $appUpdate,
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

        // 默认值 / 白名单 / 字段归一化统一在 HomeDecorateService，
        // 与后台详情、保存共用同一份实现，避免两处漂移。
        $pageData['data'] = HomeDecorateService::filterPageData($pageData['data'] ?? []);

        return $pageData;
    }


}
