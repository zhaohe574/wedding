<?php

declare(strict_types=1);

namespace app\adminapi\logic\notification;

use app\common\logic\BaseLogic;
use app\common\model\wechat\OaNotificationTemplate;
use app\common\service\ConfigService;
use app\common\service\WechatNotificationService;
use app\common\service\wechat\WeChatOaService;

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

    /**
     * 调用微信官方接口获取已添加到账号下的模板列表。
     *
     * 对应微信接口：GET /cgi-bin/template/get_all_private_template
     */
    public static function getWechatTemplates(): array
    {
        try {
            $service = new WeChatOaService();
            $result = $service->getAllPrivateTemplates();
            if (isset($result['errcode']) && (int)$result['errcode'] !== 0) {
                $errcode = (int)$result['errcode'];
                $errmsg = (string)($result['errmsg'] ?? '');
                if ($errcode === 40164) {
                    preg_match('/invalid ip ([0-9\.]+)/', $errmsg, $matches);
                    $ip = $matches[1] ?? '当前出口IP';
                    throw new \RuntimeException("微信接口提示IP未在白名单中(错误码40164)。请登录微信公众平台【设置与开发->基本配置->IP白名单】添加当前IP: {$ip} 后重试。");
                }
                throw new \RuntimeException("微信接口返回错误 [{$errcode}]: {$errmsg}");
            }
            return $result['template_list'] ?? [];
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($msg, '40164') !== false) {
                preg_match('/invalid ip ([0-9\.]+)/', $msg, $matches);
                $ip = $matches[1] ?? '当前出口IP';
                throw new \RuntimeException("微信接口提示IP未在白名单中(错误码40164)。请登录微信公众平台【设置与开发->基本配置->IP白名单】添加当前IP: {$ip} 后重试。");
            }
            throw new \RuntimeException($msg);
        }
    }

    /**
     * 同步并绑定微信服务号消息模板。
     *
     * 支持传入手动确认的绑定列表，或自动基于微信模板标题与关键词进行匹配绑定。
     */
    public static function syncTemplates(array $params = []): array
    {
        try {
            // 1. 如果传入了显式绑定配置
            $bindings = $params['bindings'] ?? [];
            if (!empty($bindings) && is_array($bindings)) {
                $updated = 0;
                foreach ($bindings as $item) {
                    $scene = trim((string)($item['scene'] ?? ''));
                    $templateId = trim((string)($item['template_id'] ?? ''));
                    if ($scene === '' || $templateId === '') {
                        continue;
                    }
                    $template = OaNotificationTemplate::where('scene', $scene)->find();
                    if ($template) {
                        $template->template_id = $templateId;
                        if (!empty($item['data_mapping']) && is_array($item['data_mapping'])) {
                            $template->data_mapping = $item['data_mapping'];
                        }
                        if (isset($item['status'])) {
                            $template->status = (int)$item['status'];
                        }
                        if (!empty($item['remark'])) {
                            $template->remark = trim((string)$item['remark']);
                        }
                        $template->update_time = time();
                        $template->save();
                        $updated++;
                    }
                }
                return ['updated_count' => $updated, 'msg' => "成功更新 {$updated} 个场景模板配置"];
            }

            // 2. 自动从微信服务号拉取模板列表
            $wechatTemplates = self::getWechatTemplates();
            if (empty($wechatTemplates)) {
                return ['updated_count' => 0, 'templates' => [], 'msg' => '微信服务号账号下未查询到已添加的模板，请先在微信公众平台添加模板'];
            }

            // 关键词映射规则（与微信服务号《生活服务>婚庆服务》类目模板库全面对齐）
            $sceneKeywords = [
                'order_update' => ['订单生成成功', '订单生成', '预约成功', '新订单'],
                'staff_order' => ['接单成功', '接单', '新任务派发', '任务通知', '派单通知', '工作安排', '订单提醒'],
                'staff_refund' => ['拒单', '退款提醒', '退款申请', '退款通知'],
                'ticket_update' => ['工单处理提醒', '工单', '售后进度', '服务单', '反馈结果'],
                'staff_aftersale' => ['工单处理提醒', '工单', '售后工单', '售后协同', '投诉处理'],
                'staff_schedule' => ['服务时间预约成功', '团队成员预约成功', '档期锁定', '排期通知', '日程通知', '工作提醒'],
                'staff_change' => ['顾客拍摄已改期', '婚礼订单改期', '服务改期', '顾客祈请改期', '改期提醒', '服务变更协同', '变更通知', '订单改动'],
                'change_result' => ['订单申诉结果', '婚礼订单改期', '服务改期', '订单申请结果', '顾客拍摄已改期', '变更结果', '改期审核', '服务变更', '申请结果'],
                'settlement_update' => ['收款成功', '管理费缴纳成功', '订单完成', '结算', '打款', '提现', '到账通知'],
                'waitlist_release' => ['团队成员预约成功', '服务时间预约成功', '候补', '排队成功', '名额释放', '档期空出'],
                'waitlist_expired' => ['订单超时取消', '订单取消', '排队失效', '候补失效', '名额超时', '预约失效'],
                'questionnaire_update' => ['婚礼需求确认', '服务需求确认', '备婚需求', '需求确认', '婚礼需求问卷', '需求问卷', '备婚问卷', '用户资料审核通过', '个人资料审核结果', '问卷', '调查问卷'],
                'activity_update' => ['活动报名成功', '活动报名', '活动通知', '报名成功', '拍摄预约成功', '服务时间预约成功'],
                'staff_pause' => ['订单申诉结果', '个人资料审核结果', '订单申请结果', '档期暂停', '请假', '暂停接单'],
                'staff_internal' => ['订阅模板', '内部通知', '系统公告', '工作通知']
            ];


            // 已知标准模板字段预设
            $templatePresets = [
                'H5dTD9xT90-dOXf1GUXIGuOs_ROKUCaqDASOZmR2zt8' => [
                    'thing12' => 'staff_name',
                    'thing2' => 'package_name',
                    'time8' => 'service_date',
                    'thing10' => 'hotel_name',
                    'amount13' => 'total_amount',
                ],
                'zRgTnFCTLiXN54GNSYTqaU9b_aTywGmL34ROId_qN_4' => [
                    'character_string1' => 'order_sn',
                    'time2' => 'order_time',
                ],
                'ZvVDAtr4cJNdP-SFKotwCQVzX5HPtUns5vTFO6C05nI' => [
                    'time1' => 'service_date',
                    'thing2' => 'hotel_name',
                    'thing4' => 'staff_name',
                ],
                '2LfkTjUakVHJhvVPvhMvFcTytY2K-6lN3XkjEI_6Fo4' => [
                    'character_string2' => 'ticket_sn',
                    'thing3' => 'package_name',
                    'time4' => 'service_date',
                    'thing5' => 'hotel_name',
                ],
                'CiCHAwhKVNbQnyiWYevaj807sxyye5fJDMaUrZ2twfo' => [
                    'content' => 'content',
                ],
            ];

            $systemTemplates = OaNotificationTemplate::select()->toArray();
            $matched = [];
            $updated = 0;

            foreach ($systemTemplates as $sysTpl) {
                $scene = $sysTpl['scene'];
                $keywords = $sceneKeywords[$scene] ?? [];
                foreach ($wechatTemplates as $wxTpl) {
                    $tplId = $wxTpl['template_id'] ?? '';
                    $title = $wxTpl['title'] ?? '';
                    $isMatch = false;
                    foreach ($keywords as $kw) {
                        if (mb_strpos($title, $kw) !== false) {
                            $isMatch = true;
                            break;
                        }
                    }
                    if ($isMatch) {
                        if (isset($templatePresets[$tplId])) {
                            $newMapping = $templatePresets[$tplId];
                        } else {
                            // 提取占位符
                            preg_match_all('/\{\{([a-zA-Z0-9_]+)\.DATA\}\}/', $wxTpl['content'] ?? '', $m);
                            $placeholders = $m[1] ?? [];
                            
                            $existingMapping = is_array($sysTpl['data_mapping']) ? $sysTpl['data_mapping'] : (json_decode((string)$sysTpl['data_mapping'], true) ?: []);
                            $newMapping = [];
                            $existingValues = array_values($existingMapping);
                            foreach ($placeholders as $idx => $ph) {
                                $val = $existingValues[$idx] ?? 'content';
                                $newMapping[$ph] = $val;
                            }
                            if (empty($newMapping)) {
                                $newMapping = $existingMapping;
                            }
                        }

                        $model = OaNotificationTemplate::find($sysTpl['id']);
                        if ($model) {
                            $model->template_id = $tplId;
                            $model->data_mapping = $newMapping;
                            $model->status = 1;
                            $model->remark = $title;
                            $model->update_time = time();
                            $model->save();
                            $updated++;
                            $matched[] = [
                                'scene' => $scene,
                                'template_id' => $tplId,
                                'title' => $title
                            ];
                        }
                        break;
                    }
                }
            }


            return [
                'updated_count' => $updated,
                'matched' => $matched,
                'wechat_templates_count' => count($wechatTemplates),
                'msg' => "成功匹配并更新 {$updated} 个场景模板"
            ];
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return ['updated_count' => 0, 'msg' => $e->getMessage()];
        }
    }
}

