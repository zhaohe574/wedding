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
use app\common\service\HomeDecorateService;
use app\common\service\PcDecorateService;
use app\common\service\SplashAdDecorateService;


/**
 * 装修页面
 * Class DecoratePageLogic
 * @package app\adminapi\logic\theme
 */
class DecoratePageLogic extends BaseLogic
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
            if ((int)$id === PcDecorateService::PAGE_ID) {
                return PcDecorateService::defaultPage();
            }
            return [];
        }
        
        // 解析并填充动态数据
        $pageData = DecorateDataService::parsePageData($pageData);
        if ((int)$id === SplashAdDecorateService::PAGE_ID) {
            return SplashAdDecorateService::normalizePage($pageData);
        }
        if ((int)$id === PcDecorateService::PAGE_ID) {
            return PcDecorateService::normalizePage($pageData);
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
        $pageId = (int)($params['id'] ?? 0);
        $pageData = DecoratePage::where(['id' => $pageId])->findOrEmpty();

        // 存量库可能缺行（例如开屏页 id=6 只在全新安装的 like.sql 里插入过）。
        // 旧逻辑在这里直接报「信息不存在」，运营侧表现为「点了保存没反应、配置永远不生效」。
        // 改为按页面类型自动补建，保证「能进编辑页就能保存」。
        if ($pageData->isEmpty()) {
            $defaults = self::buildDefaultPageRow($pageId);
            if ($defaults === null) {
                self::$error = '信息不存在';
                return false;
            }
            DecoratePage::create($defaults);
            $pageData = DecoratePage::where(['id' => $pageId])->findOrEmpty();
            if ($pageData->isEmpty()) {
                self::$error = '装修页初始化失败';
                return false;
            }
        }

        $updateData = [
            'id' => $pageId,
            'type' => $params['type'],
            'data' => $params['data'],
            'meta' => $params['meta'] ?? '',
        ];

        if (self::isHomePage($pageId)) {
            $updateData['data'] = self::filterHomePageData($params['data']);
            // meta 必须用前端传的：旧逻辑强制写回库里的旧值，
            // 导致后台改「页面标题 / 背景色」保存后永远不生效。
            $updateData['meta'] = $params['meta'] ?? ($pageData->meta ?? '');
        }

        if ($pageId === SplashAdDecorateService::PAGE_ID) {
            $updateData['type'] = SplashAdDecorateService::PAGE_TYPE;
            $updateData['data'] = SplashAdDecorateService::normalizeDataForSave($params['data']);
            $updateData['meta'] = $params['meta'] ?? ($pageData->meta ?? '');
        }

        if ($pageId === PcDecorateService::PAGE_ID) {
            $updateData['type'] = PcDecorateService::PAGE_TYPE;
            $updateData['data'] = PcDecorateService::normalizeDataForSave($params['data']);
            $updateData['meta'] = $pageData->meta ?? '';
        }

        DecoratePage::update($updateData);
        return true;
    }

    /**
     * @notes 按页面 id 生成可入库的默认行（仅在库里缺行时使用）
     * @param int $pageId
     * @return array|null
     */
    private static function buildDefaultPageRow(int $pageId): ?array
    {
        if ($pageId === SplashAdDecorateService::PAGE_ID) {
            $page = SplashAdDecorateService::defaultPage();
        } elseif ($pageId === PcDecorateService::PAGE_ID) {
            $page = PcDecorateService::defaultPage();
        } elseif ($pageId === self::HOME_PAGE_ID) {
            $widgets = array_map(
                static fn (string $widgetName): array => HomeDecorateService::buildDefaultWidget($widgetName),
                self::HOME_WIDGET_ORDER
            );
            $page = [
                'id' => self::HOME_PAGE_ID,
                'type' => 1,
                'name' => '首页装修',
                'data' => json_encode($widgets, JSON_UNESCAPED_UNICODE),
                'meta' => '',
            ];
        } else {
            return null;
        }

        return [
            'id' => $pageId,
            'type' => (int)$page['type'],
            'name' => (string)$page['name'],
            'data' => is_string($page['data'])
                ? $page['data']
                : json_encode($page['data'], JSON_UNESCAPED_UNICODE),
            'meta' => is_string($page['meta'] ?? null)
                ? $page['meta']
                : json_encode($page['meta'] ?? '', JSON_UNESCAPED_UNICODE),
        ];
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
     * @notes 首页装修仅保留固定模块（默认值 / 归一化统一在 HomeDecorateService）
     * @param mixed $data
     * @return array|string
     */
    private static function filterHomePageData($data)
    {
        return HomeDecorateService::filterPageData($data);
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
