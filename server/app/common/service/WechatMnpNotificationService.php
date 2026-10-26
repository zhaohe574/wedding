<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\user\UserTerminalEnum;
use app\common\model\user\UserAuth;
use app\common\model\wechat\MnpNotificationLog;
use app\common\model\wechat\MnpNotificationTemplate;
use app\common\service\wechat\WeChatMnpService;
use think\facade\Log;

/**
 * 微信小程序订阅通知核心服务类
 */
class WechatMnpNotificationService
{
    /**
     * @notes 发送小程序订阅消息场景通知
     */
    public static function sendScene(
        int $userId,
        string $scene,
        array $data = [],
        string $businessType = '',
        int $businessId = 0,
        string $page = '',
        array $options = []
    ): array {
        if ($userId <= 0 || empty($scene)) {
            return ['success' => false, 'sent' => false, 'channel' => 'mnp', 'msg' => '参数缺失'];
        }

        // 1. 查询该场景的小程序订阅模板配置
        $template = MnpNotificationTemplate::findEnabled($scene);
        if (!$template || empty($template->template_id)) {
            return [
                'success' => true,
                'sent' => false,
                'channel' => 'mnp',
                'msg' => "小程序场景[{$scene}]未配置或未开启订阅模板",
                'skipped' => true,
            ];
        }

        // 2. 查询用户小程序 OpenID
        $openid = self::resolveMnpOpenid($userId);
        if (empty($openid)) {
            self::recordLog($userId, '', $scene, $businessType, $businessId, $template->template_id, [], 'NO_OPENID', '用户未登录或未绑定微信小程序', 2);
            return [
                'success' => true,
                'sent' => false,
                'channel' => 'mnp',
                'msg' => '用户暂无小程序OpenID，已跳过小程序订阅消息',
                'skipped' => true,
            ];
        }

        // 3. 构建并清洗模板消息内容
        $content = self::formatMnpContent($template, $data, $scene);
        if (empty($content)) {
            self::recordLog($userId, $openid, $scene, $businessType, $businessId, $template->template_id, [], 'EMPTY_CONTENT', '模板数据映射后为空', 2);
            return [
                'success' => false,
                'sent' => false,
                'channel' => 'mnp',
                'msg' => '模板数据转换失败',
            ];
        }

        // 4. 幂等控制（同一用户同一业务当天内不重复发送相同订阅通知）
        $forceDispatch = !empty($options['force_dispatch']);
        $dedupeKey = hash('sha256', implode(':', [
            'mnp',
            $userId,
            $scene,
            $businessType,
            $businessId,
            $forceDispatch ? microtime(true) : date('Ymd'),
        ]));

        if (!$forceDispatch) {
            $existing = MnpNotificationLog::where('dedupe_key', $dedupeKey)
                ->where('send_status', MnpNotificationLog::STATUS_SENT)
                ->find();
            if ($existing) {
                return [
                    'success' => true,
                    'sent' => false,
                    'channel' => 'mnp',
                    'msg' => '当日已发送过该订阅通知，已幂等去重',
                    'log_id' => (int) $existing->id,
                ];
            }
        }

        // 5. 确定跳转页面路径
        $pagePath = $page ?: ($template->page_path ?: '');
        if (!empty($pagePath) && !empty($businessId) && !str_contains($pagePath, 'id=')) {
            $pagePath .= (str_contains($pagePath, '?') ? '&' : '?') . 'id=' . $businessId;
        }

        // 6. 调用微信小程序接口下发
        $logId = 0;
        try {
            $mnpService = new WeChatMnpService();
            $state = (string)config('project.mnp_state', 'formal');
            $res = $mnpService->sendSubscribeMessage($openid, (string)$template->template_id, $content, $pagePath, $state);

            $errcode = (int)($res['errcode'] ?? -1);
            $errmsg = (string)($res['errmsg'] ?? '');

            if ($errcode === 0) {
                $logId = self::recordLog(
                    $userId,
                    $openid,
                    $scene,
                    $businessType,
                    $businessId,
                    (string)$template->template_id,
                    $content,
                    '0',
                    '发送成功',
                    MnpNotificationLog::STATUS_SENT,
                    $dedupeKey,
                    (string)($res['msgid'] ?? '')
                );
                return [
                    'success' => true,
                    'sent' => true,
                    'channel' => 'mnp',
                    'msg' => '小程序订阅通知发送成功',
                    'log_id' => $logId,
                ];
            }

            // 用户未授权、拒绝或配额用尽（43101）是微信小程序的常见正常情况，优雅落库，不影响主链路
            $statusText = $errcode === 43101 ? '用户未在小程序端授权订阅或推送额度已用尽' : $errmsg;
            $logId = self::recordLog(
                $userId,
                $openid,
                $scene,
                $businessType,
                $businessId,
                (string)$template->template_id,
                $content,
                (string)$errcode,
                $statusText,
                MnpNotificationLog::STATUS_FAILED,
                $dedupeKey
            );

            return [
                'success' => $errcode === 43101, // 43101 视为业务允许的优雅降级
                'sent' => false,
                'channel' => 'mnp',
                'errcode' => $errcode,
                'msg' => $statusText,
                'log_id' => $logId,
            ];
        } catch (\Throwable $e) {
            Log::error('WechatMnpNotificationService error: ' . $e->getMessage());
            $logId = self::recordLog(
                $userId,
                $openid,
                $scene,
                $businessType,
                $businessId,
                (string)$template->template_id,
                $content,
                'EXCEPTION',
                mb_substr($e->getMessage(), 0, 450),
                MnpNotificationLog::STATUS_FAILED,
                $dedupeKey
            );
            return [
                'success' => false,
                'sent' => false,
                'channel' => 'mnp',
                'msg' => '发送异常: ' . $e->getMessage(),
                'log_id' => $logId,
            ];
        }
    }

    /**
     * @notes 解析用户小程序 OpenID
     */
    public static function resolveMnpOpenid(int $userId): string
    {
        $auth = UserAuth::where('user_id', $userId)
            ->where('terminal', UserTerminalEnum::WECHAT_MMP)
            ->order('id', 'desc')
            ->find();

        return (string)($auth->openid ?? '');
    }

    /**
     * @notes 格式化小程序订阅消息内容（微信字段规范与非空截断安全清洗）
     */
    public static function formatMnpContent(MnpNotificationTemplate $template, array $data, string $scene): array
    {
        $mapping = (array)$template->data_mapping;
        if (empty($mapping)) {
            return [];
        }

        $content = [];
        foreach ($mapping as $keyword => $dataKey) {
            $keyword = trim((string)$keyword);
            $dataKey = trim((string)$dataKey);
            if ($keyword === '' || $dataKey === '') {
                continue;
            }

            $value = $data[$dataKey] ?? $data[$keyword] ?? '';
            if ($value === '' || $value === null) {
                $value = self::resolveFallbackValue($dataKey, $keyword, $data, $scene);
            }
            if (is_array($value)) {
                $value = implode('，', array_map(static fn ($item) => (string) $item, $value));
            }

            $value = self::formatKeywordValue($keyword, (string)$value, $dataKey);
            $content[$keyword] = ['value' => $value];
        }

        return $content;
    }

    /**
     * @notes 针对微信小程序字段类型的规范清洗与保底截断
     */
    protected static function formatKeywordValue(string $keyword, string $value, string $dataKey): string
    {
        $type = preg_replace('/\d+$/', '', $keyword) ?: '';
        $text = trim(preg_replace('/[\r\n\t]+/', ' ', $value));

        if ($text === '') {
            $text = self::typeSafeFallback($keyword, $dataKey);
        }

        switch ($type) {
            case 'thing':
                // 事物描述：限制 20 个字以内
                return mb_substr($text, 0, 20, 'UTF-8');
            case 'character_string':
                // 字母数字或符号：限制 32 位
                $clean = preg_replace('/[^A-Za-z0-9_\-.\/]+/', '', $text) ?: 'SN' . date('YmdHis');
                return substr($clean, 0, 32);
            case 'time':
            case 'date':
                // 时间：限制标准格式
                if (!preg_match('/\d{4}[-\/]\d{2}[-\/]\d{2}/', $text)) {
                    $text = date('Y-m-d H:i');
                }
                return mb_substr($text, 0, 30, 'UTF-8');
            case 'amount':
                // 金额：保留2位小数
                $num = is_numeric($text) ? (float)$text : 0.0;
                return number_format($num, 2, '.', '');
            case 'phrase':
            case 'const':
                // 短语枚举：限制 5 个汉字以内
                return mb_substr($text, 0, 5, 'UTF-8');
            case 'name':
                // 姓名：限制 10 个字以内
                return mb_substr($text, 0, 10, 'UTF-8');
            default:
                return mb_substr($text, 0, 20, 'UTF-8');
        }
    }

    /**
     * @notes 智能字段别名自动互通
     */
    protected static function resolveFallbackValue(string $dataKey, string $keyword, array $data, string $scene): string
    {
        $aliasMap = [
            'order_sn' => ['ticket_sn', 'sn', 'order_no', 'out_trade_no'],
            'ticket_sn' => ['order_sn', 'sn', 'ticket_no'],
            'user_name' => ['contact_name', 'customer_name', 'nickname', 'realname'],
            'staff_name' => ['assignee_name', 'advisor_name', 'leader_name'],
            'service_date' => ['order_time', 'pay_time', 'service_time', 'update_time', 'create_time'],
            'package_name' => ['title', 'goods_name', 'item_name', 'ticket_name'],
            'ticket_name' => ['package_name', 'title'],
            'total_amount' => ['amount', 'pay_amount', 'price'],
            'title' => ['package_name', 'ticket_name', 'name', 'activity_name'],
            'remark_text' => ['remark', 'content', 'status_text', 'note'],
            'status_text' => ['remark_text', 'status_desc', 'result_text'],
        ];

        if (isset($aliasMap[$dataKey])) {
            foreach ($aliasMap[$dataKey] as $alias) {
                if (isset($data[$alias]) && trim((string)$data[$alias]) !== '') {
                    return trim((string)$data[$alias]);
                }
            }
        }

        return '';
    }

    /**
     * @notes 字段非空类型安全兜底
     */
    protected static function typeSafeFallback(string $keyword, string $dataKey): string
    {
        $type = preg_replace('/\d+$/', '', $keyword) ?: '';
        if ($type === 'character_string') {
            return 'SN' . date('YmdHis');
        }
        if ($type === 'amount') {
            return '0.00';
        }
        if (in_array($type, ['time', 'date'], true)) {
            return date('Y-m-d H:i');
        }
        if (in_array($type, ['phrase', 'const'], true)) {
            return '已确认';
        }
        if ($type === 'name') {
            return '贵宾客户';
        }
        if ($type === 'thing') {
            if (str_contains($dataKey, 'title') || str_contains($dataKey, 'package')) {
                return '婚礼定制服务';
            }
            if (str_contains($dataKey, 'staff')) {
                return '专属婚礼顾问';
            }
            if (str_contains($dataKey, 'ticket')) {
                return '标准活动名额';
            }
            return '备婚相关提醒';
        }
        return '--';
    }

    /**
     * @notes 记录小程序订阅通知日志
     */
    protected static function recordLog(
        int $userId,
        string $openid,
        string $scene,
        string $businessType,
        int $businessId,
        string $templateId,
        array $payload,
        string $errorCode,
        string $errorMsg,
        int $sendStatus,
        string $dedupeKey = '',
        string $requestId = ''
    ): int {
        try {
            $now = time();
            $log = MnpNotificationLog::create([
                'user_id' => $userId,
                'openid' => $openid,
                'scene' => $scene,
                'business_type' => $businessType,
                'business_id' => $businessId,
                'template_id' => $templateId,
                'payload' => $payload,
                'dedupe_key' => $dedupeKey ?: hash('sha256', $userId . ':' . $scene . ':' . microtime(true)),
                'send_status' => $sendStatus,
                'last_error_code' => $errorCode,
                'error_msg' => mb_substr($errorMsg, 0, 480, 'UTF-8'),
                'request_id' => $requestId,
                'send_time' => $sendStatus === MnpNotificationLog::STATUS_SENT ? $now : 0,
                'create_time' => $now,
                'update_time' => $now,
            ]);
            return (int)$log->id;
        } catch (\Throwable $e) {
            Log::error('Record MnpNotificationLog error: ' . $e->getMessage());
            return 0;
        }
    }
}
