<?php

declare(strict_types=1);

namespace app\adminapi\logic\notification;

use app\common\logic\BaseLogic;
use app\common\model\wechat\OaNotificationTemplate;
use app\common\service\ConfigService;
use app\common\service\WechatNotificationService;

/**
 * 公众号通知配置逻辑。
 */
class OaNotificationLogic extends BaseLogic
{
    public static function config(): array
    {
        return [
            'enabled' => (int) ConfigService::get('oa_notification', 'enabled', 0),
            'channel_mode' => (string) ConfigService::get('oa_notification', 'channel_mode', 'oa_only'),
        ];
    }

    public static function saveConfig(array $params): bool
    {
        ConfigService::set('oa_notification', 'enabled', (int) ($params['enabled'] ?? 0) === 1 ? '1' : '0');
        ConfigService::set('oa_notification', 'channel_mode', 'oa_only');
        return true;
    }

    public static function detail(int $id): array
    {
        $template = OaNotificationTemplate::find($id);
        return $template ? $template->toArray() : [];
    }

    public static function editTemplate(array $params): bool
    {
        try {
            $id = (int) ($params['id'] ?? 0);
            $template = OaNotificationTemplate::find($id);
            if (!$template) {
                throw new \RuntimeException('公众号通知模板不存在');
            }

            $templateId = trim((string) ($params['template_id'] ?? ''));
            if ($templateId === '') {
                throw new \RuntimeException('请填写公众号模板ID');
            }

            $mapping = $params['data_mapping'] ?? [];
            if (is_string($mapping)) {
                $mapping = json_decode($mapping, true);
            }
            if (!is_array($mapping)) {
                throw new \RuntimeException('字段映射必须是JSON对象');
            }

            $template->template_id = $templateId;
            $template->data_mapping = $mapping;
            $template->page_path = trim((string) ($params['page_path'] ?? $template->page_path));
            $template->status = (int) ($params['status'] ?? $template->status);
            $template->remark = trim((string) ($params['remark'] ?? $template->remark));
            $template->sort = (int) ($params['sort'] ?? $template->sort);
            $template->update_time = time();
            return $template->save() !== false;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function testSend(array $params): array
    {
        $data = $params['data'] ?? [];
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (!is_array($data)) {
            return ['success' => false, 'msg' => '测试数据必须是JSON对象'];
        }

        return WechatNotificationService::sendScene(
            (int) ($params['user_id'] ?? 0),
            trim((string) ($params['scene'] ?? '')),
            $data,
            'admin_test',
            (int) ($params['business_id'] ?? 0),
            trim((string) ($params['audience'] ?? OaNotificationTemplate::AUDIENCE_USER)),
            trim((string) ($params['page_path'] ?? '')),
            ['force_dispatch' => true]
        );
    }
}
