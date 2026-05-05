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

namespace app\adminapi\logic\setting;

use app\common\logic\BaseLogic;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\WeComMessageService;

/**
 * 客服设置逻辑
 * Class CustomerServiceLogic
 * @package app\adminapi\logic\setting
 */
class CustomerServiceLogic extends BaseLogic
{
    public const SECRET_MASK = '******';

    /**
     * @notes 获取客服设置
     * @return array
     * @author ljj
     * @date 2022/2/15 12:05 下午
     */
    public static function getConfig()
    {
        $qrCode = ConfigService::get('customer_service', 'qr_code');
        $qrCode = empty($qrCode) ? '' : FileService::getFileUrl($qrCode);
        $config = [
            'qr_code' => $qrCode,
            'wechat' => ConfigService::get('customer_service', 'wechat', ''),
            'phone' => ConfigService::get('customer_service', 'phone', ''),
            'service_time' => ConfigService::get('customer_service', 'service_time', ''),
            'contact_link' => ConfigService::get('customer_service', 'contact_link', ''),
            'tips' => ConfigService::get('customer_service', 'tips', ''),
            'wecom_enabled' => (int) ConfigService::get('customer_service', 'wecom_enabled', 0),
            'wecom_corp_id' => ConfigService::get('customer_service', 'wecom_corp_id', ''),
            'wecom_secret' => ConfigService::get('customer_service', 'wecom_secret', '') ? self::SECRET_MASK : '',
            'wecom_secret_filled' => ConfigService::get('customer_service', 'wecom_secret', '') ? 1 : 0,
            'wecom_agent_id' => (int) ConfigService::get('customer_service', 'wecom_agent_id', 0),
            'wecom_card_mode' => self::normalizeWecomCardMode(ConfigService::get('customer_service', 'wecom_card_mode', 'mini_first')),
            'mnp_app_id_filled' => ConfigService::get('mnp_setting', 'app_id', '') ? 1 : 0,
        ];
        return $config;
    }

    /**
     * @notes 设置客服设置
     * @param $params
     * @author ljj
     * @date 2022/2/15 12:11 下午
     */
    public static function setConfig($params)
    {
        $allowField = ['qr_code','wechat','phone','service_time','contact_link','tips', 'wecom_enabled', 'wecom_corp_id', 'wecom_secret', 'wecom_agent_id', 'wecom_card_mode'];
        foreach($params as $key => $value) {
            if(in_array($key, $allowField)) {
                if ($key == 'qr_code') {
                    $value = FileService::setFileUrl($value);
                }
                if ($key === 'wecom_secret') {
                    $value = trim((string) $value);
                    if ($value === '' || $value === self::SECRET_MASK) {
                        continue;
                    }
                }
                if (in_array($key, ['wecom_enabled', 'wecom_agent_id'], true)) {
                    $value = (int) $value;
                }
                if ($key === 'wecom_card_mode') {
                    $value = self::normalizeWecomCardMode($value);
                }
                ConfigService::set('customer_service', $key, $value);
            }
        }
    }

    public static function testWecomMessage(array $params): array
    {
        $wecomUserid = trim((string) ($params['wecom_userid'] ?? ''));
        if ($wecomUserid === '') {
            return ['success' => false, 'message' => '请输入企微成员ID'];
        }
        if (mb_strlen($wecomUserid) > 64) {
            return ['success' => false, 'message' => '企微成员ID长度不能超过64个字符'];
        }

        $content = trim((string) ($params['content'] ?? ''));
        if ($content === '') {
            $content = '这是一条婚庆管理系统企业微信测试消息。';
        }
        if (mb_strlen($content) > 500) {
            return ['success' => false, 'message' => '测试内容不能超过500个字符'];
        }

        $description = WeComMessageService::buildTextCardDescription(
            '企业微信通知测试',
            '收到此卡片说明企业微信应用消息可用。',
            [
                '测试内容' => $content,
                '接收成员ID' => $wecomUserid,
                '发送时间' => date('Y-m-d H:i:s'),
            ],
            '请确认卡片内容、跳转和接收成员均符合预期。'
        );

        $success = WeComMessageService::sendTextCardToUsers(
            [$wecomUserid],
            '企业微信通知测试',
            $description,
            WeComMessageService::buildBackendUrl('/admin/setting/wecom'),
            '查看配置',
            [
                'mini_pagepath' => WeComMessageService::buildWecomNoticePagePath('wecom_test'),
            ]
        );
        if (!$success) {
            return [
                'success' => false,
                'message' => WeComMessageService::getLastError() ?: '发送失败，请检查企微配置与成员ID',
            ];
        }

        $channelDesc = WeComMessageService::getLastSendChannelDesc();
        return [
            'success' => true,
            'message' => $channelDesc !== '' ? '测试消息已发送：' . $channelDesc : '测试消息已发送',
            'send_channel' => WeComMessageService::getLastSendChannel(),
            'send_channel_desc' => $channelDesc,
        ];
    }

    private static function normalizeWecomCardMode($value): string
    {
        $value = trim((string) $value);
        return in_array($value, ['mini_first', 'backend_only'], true) ? $value : 'mini_first';
    }
}
