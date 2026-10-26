<?php

declare(strict_types=1);

namespace app\adminapi\logic\notification;

use app\common\logic\BaseLogic;
use app\common\model\wechat\MnpNotificationTemplate;
use app\common\service\WechatMnpNotificationService;

/**
 * 小程序订阅通知管理逻辑
 */
class MnpNotificationLogic extends BaseLogic
{
    public static function detail(int $id): array
    {
        $template = MnpNotificationTemplate::find($id);
        return $template ? $template->toArray() : [];
    }

    public static function editTemplate(array $params): bool
    {
        try {
            $id = (int) ($params['id'] ?? 0);
            $template = MnpNotificationTemplate::find($id);
            if (!$template) {
                throw new \RuntimeException('小程序通知模板不存在');
            }

            $templateId = trim((string) ($params['template_id'] ?? ''));
            $mapping = $params['data_mapping'] ?? [];
            if (is_string($mapping)) {
                $mapping = json_decode($mapping, true);
            }
            if (!is_array($mapping)) {
                throw new \RuntimeException('字段映射必须是JSON对象');
            }

            $template->title = trim((string) ($params['title'] ?? $template->title));
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

        $userId = (int) ($params['user_id'] ?? 0);
        $scene = trim((string) ($params['scene'] ?? ''));

        if ($userId <= 0) {
            return ['success' => false, 'msg' => '请填写接收测试通知的有效用户ID'];
        }
        if ($scene === '') {
            return ['success' => false, 'msg' => '请选择业务场景'];
        }

        return WechatMnpNotificationService::sendScene(
            $userId,
            $scene,
            $data,
            'admin_test',
            (int) ($params['business_id'] ?? 0),
            '',
            ['force_dispatch' => true]
        );
    }
}
