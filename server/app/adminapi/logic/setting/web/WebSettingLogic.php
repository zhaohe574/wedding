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

namespace app\adminapi\logic\setting\web;


use app\common\logic\BaseLogic;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\OrderConfirmLetterFontService;
use app\common\service\StaffScheduleConfirmLetterService;


/**
 * 网站设置
 * Class WebSettingLogic
 * @package app\adminapi\logic\setting
 */
class WebSettingLogic extends BaseLogic
{

    /**
     * @notes 获取网站信息
     * @return array
     * @author 段誉
     * @date 2021/12/28 15:43
     */
    public static function getWebsiteInfo(): array
    {
        $qrcodeConfig = StaffScheduleConfirmLetterService::getGlobalQrcodeConfig();
        return [
            'name' => ConfigService::get('website', 'name'),
            'web_favicon' => FileService::getFileUrl(ConfigService::get('website', 'web_favicon')),
            'web_logo' => FileService::getFileUrl(ConfigService::get('website', 'web_logo')),
            'login_image' => FileService::getFileUrl(ConfigService::get('website', 'login_image')),
            'shop_name' => ConfigService::get('website', 'shop_name'),
            'shop_slogan' => ConfigService::get('website', 'shop_slogan') ?: '',
            'shop_logo' => FileService::getFileUrl(ConfigService::get('website', 'shop_logo')),

            'pc_logo' => FileService::getFileUrl(ConfigService::get('website', 'pc_logo')),
            'pc_title' => ConfigService::get('website', 'pc_title', ''),
            'pc_ico' => FileService::getFileUrl(ConfigService::get('website', 'pc_ico')),
            'pc_desc' => ConfigService::get('website', 'pc_desc', ''),
            'pc_keywords' => ConfigService::get('website', 'pc_keywords', ''),

            'schedule_qrcode_image' => (string)($qrcodeConfig['schedule_qrcode_image_url'] ?? ''),
        ];
    }


    /**
     * @notes 设置网站信息
     * @param array $params
     * @author 段誉
     * @date 2021/12/28 15:43
     */
    public static function setWebsiteInfo(array $params)
    {
        $favicon = FileService::setFileUrl($params['web_favicon']);
        $logo = FileService::setFileUrl($params['web_logo']);
        $login = FileService::setFileUrl($params['login_image']);
        $shopLogo = FileService::setFileUrl($params['shop_logo']);
        $pcLogo = FileService::setFileUrl($params['pc_logo']);
        $pcIco = FileService::setFileUrl($params['pc_ico'] ?? '');

        ConfigService::set('website', 'name', $params['name']);
        ConfigService::set('website', 'web_favicon', $favicon);
        ConfigService::set('website', 'web_logo', $logo);
        ConfigService::set('website', 'login_image', $login);
        ConfigService::set('website', 'shop_name', $params['shop_name']);
        ConfigService::set('website', 'shop_slogan', $params['shop_slogan'] ?? '');
        ConfigService::set('website', 'shop_logo', $shopLogo);
        ConfigService::set('website', 'pc_logo', $pcLogo);

        ConfigService::set('website', 'pc_title', $params['pc_title']);
        ConfigService::set('website', 'pc_ico', $pcIco);
        ConfigService::set('website', 'pc_desc', $params['pc_desc'] ?? '');
        ConfigService::set('website', 'pc_keywords', $params['pc_keywords'] ?? '');

        if (array_key_exists('schedule_qrcode_image', $params)) {
            $qrcodeImage = trim((string)($params['schedule_qrcode_image'] ?? ''));
            if ($qrcodeImage !== '') {
                StaffScheduleConfirmLetterService::setGlobalQrcodeConfig([
                    StaffScheduleConfirmLetterService::CONFIG_KEY_SCHEDULE_QRCODE_IMAGE => $qrcodeImage,
                ]);
            } else {
                ConfigService::set(
                    OrderConfirmLetterFontService::CONFIG_GROUP,
                    StaffScheduleConfirmLetterService::CONFIG_KEY_SCHEDULE_QRCODE_IMAGE,
                    ''
                );
            }
        }

    }


    /**
     * @notes 获取版权备案
     * @return array
     * @author 段誉
     * @date 2021/12/28 16:09
     */
    public static function getCopyright() : array
    {
        return ConfigService::get('copyright', 'config', []);
    }


    /**
     * @notes 设置版权备案
     * @param array $params
     * @return bool
     * @author 段誉
     * @date 2022/8/8 16:33
     */
    public static function setCopyright(array $params)
    {
        try {
            if (!is_array($params['config'])) {
                throw new \Exception('参数异常');
            }
            ConfigService::set('copyright', 'config', $params['config'] ?? []);
            return true;
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }


    /**
     * @notes 设置政策协议
     * @param array $params
     * @author ljj
     * @date 2022/2/15 10:59 上午
     */
    public static function setAgreement(array $params)
    {
        // 4 份协议独立保存，按 array_key_exists 判断，兼容仅提交部分字段的调用方
        foreach (['service', 'privacy', 'booking', 'aftersale'] as $key) {
            $titleKey = $key . '_title';
            $contentKey = $key . '_content';
            if (array_key_exists($titleKey, $params)) {
                ConfigService::set('agreement', $titleKey, trim((string)($params[$titleKey] ?? '')));
            }
            if (array_key_exists($contentKey, $params)) {
                ConfigService::set('agreement', $contentKey, clear_file_domain((string)($params[$contentKey] ?? '')));
            }
        }
    }


    /**
     * @notes 获取政策协议
     * @return array
     * @author ljj
     * @date 2022/2/15 11:15 上午
     */
    public static function getAgreement() : array
    {
        $config = [
            'service_title' => (string)ConfigService::get('agreement', 'service_title', ''),
            'service_content' => (string)ConfigService::get('agreement', 'service_content', ''),
            'privacy_title' => (string)ConfigService::get('agreement', 'privacy_title', ''),
            'privacy_content' => (string)ConfigService::get('agreement', 'privacy_content', ''),
            'booking_title' => (string)ConfigService::get('agreement', 'booking_title', ''),
            'booking_content' => (string)ConfigService::get('agreement', 'booking_content', ''),
            'aftersale_title' => (string)ConfigService::get('agreement', 'aftersale_title', ''),
            'aftersale_content' => (string)ConfigService::get('agreement', 'aftersale_content', ''),
        ];

        foreach (['service', 'privacy', 'booking', 'aftersale'] as $key) {
            $config[$key . '_content'] = get_file_domain($config[$key . '_content']);
        }

        return $config;
    }

    /**
     * @notes 获取站点统计配置
     * @return array
     * @author yfdong
     * @date 2024/09/20 22:25
     */
    public static function getSiteStatistics()
    {
        return [
            'clarity_code' => ConfigService::get('siteStatistics', 'clarity_code')
        ];
    }

    /**
     * @notes 设置站点统计配置
     * @param array $params
     * @return void
     * @author yfdong
     * @date 2024/09/20 22:31
     */
    public static function setSiteStatistics(array $params)
    {
        ConfigService::set('siteStatistics', 'clarity_code', $params['clarity_code']);
    }
}
