<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端 v2 聚合逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\api\logic\StaffCenterLogic;
use app\common\model\order\Order;
use app\common\model\order\OrderChange;
use app\common\model\order\OrderItem;
use app\common\model\questionnaire\CoupleQuestionnaireTask;
use app\common\model\review\Review;
use app\common\service\ConfigService;
use think\facade\Log;

/**
 * v2 聚合接口逻辑。
 *
 * 设计原则：
 *   1. 只做编排，不重写业务规则 —— 全部复用 v1 的 Logic 方法，避免两套口径漂移。
 *   2. 分段容错 —— 任一子块失败只降级该块并在 degraded 里标记，不让整页 500。
 *   3. 只减请求数，不改语义 —— 字段与 v1 保持一致，客户端可以逐块灰度切换。
 *
 * 影响的消费方：仅小程序新端。PC / 管理后台 / 老 uniapp 全部走 v1，不受影响。
 */
class V2Logic
{
    /** 降级标记，用于客户端上报与排查 */
    private static array $degraded = [];

    /**
     * 首页聚合：config + 装修 + 推荐人员 + 资讯 + 未读
     */
    public static function home(int $userId): array
    {
        self::$degraded = [];

        $config = self::safe('config', static fn () => IndexLogic::getConfigData(), []);
        $indexData = self::safe('page', static fn () => IndexLogic::getIndexData(), ['page' => [], 'article' => []]);
        // 0 = 全部：首页推荐位横向平铺，不再截断到 8 位
        $staff = self::safe('staff_recommend', static fn () => StaffLogic::recommend(0), []);

        $unread = $userId > 0
            ? self::safe('unread', static fn () => NotificationLogic::unreadCount($userId), self::emptyUnread())
            : self::emptyUnread();

        $page = is_array($indexData['page'] ?? null) ? $indexData['page'] : [];
        $articles = is_array($indexData['article'] ?? null) ? $indexData['article'] : [];

        return [
            'config' => $config,
            'page' => [
                // 装修数据保持 v1 的 JSON 字符串形态，客户端用 parseDecoratePage 解析
                'data' => (string)($page['data'] ?? '[]'),
                'meta' => (string)($page['meta'] ?? '[]'),
            ],
            'staff_recommend' => self::normalizeStaffCards($staff),
            'article_latest' => array_slice(self::normalizeArticles($articles), 0, 5),
            'unread' => self::normalizeUnread($unread),
            'server_time' => time(),
            'degraded' => self::$degraded,
        ];
    }

    /**
     * 我的聚合：用户 + 订单计数 + 待办 + 婚期 + 未读 + 角色 + 开关
     */
    public static function me(int $userId, array $userInfo): array
    {
        self::$degraded = [];

        $user = self::safe('user', static fn () => UserLogic::center($userInfo), []);
        $stats = self::safe('order_stats', static fn () => OrderLogic::getUserOrderStatistics($userId), []);
        $wedding = self::safe('wedding', static fn () => UserLogic::weddingDate($userId), []);
        // 备婚进度：只在存在有效婚期订单时查询，无订单不产生额外 SQL；
        // 失败只降级 wedding_progress，不影响 wedding.date / days_remaining
        $weddingProgress = !empty($wedding['has_order'])
            ? self::safe('wedding_progress', static fn () => WeddingProgressLogic::summary($userId), null)
            : null;
        $unread = self::safe('unread', static fn () => NotificationLogic::unreadCount($userId), self::emptyUnread());
        $todo = self::safe('todo', static fn () => self::buildTodo($userId), self::emptyTodo());

        // 服务人员待办：开关关闭或非服务人员时直接零值，普通用户不产生额外查询
        $staffCenterEnabled = (int)ConfigService::get('feature_switch', 'staff_center', 1);
        $staffTodo = (int)($user['is_staff'] ?? 0) === 1 && $staffCenterEnabled === 1
            ? self::safe('staff_todo', static fn () => StaffCenterLogic::getTodoSummary($userId), self::emptyStaffTodo())
            : self::emptyStaffTodo();

        return [
            'user' => [
                'id' => (int)($user['id'] ?? $userId),
                'nickname' => (string)($user['nickname'] ?? ''),
                'avatar' => (string)($user['avatar'] ?? ''),
                'mobile' => (string)($user['mobile'] ?? ''),
                'is_new_user' => (int)($user['is_new_user'] ?? 0),
                'sex' => (int)($user['sex'] ?? 0),
            ],
            'order_stats' => [
                'pending_confirm' => (int)($stats['pending_confirm'] ?? 0),
                'pending_pay' => (int)($stats['pending_pay'] ?? 0),
                'pending_service' => (int)($stats['pending_service'] ?? 0),
                'in_service' => (int)($stats['in_service'] ?? 0),
                'completed' => (int)($stats['completed'] ?? 0) + (int)($stats['reviewed'] ?? 0),
                'refunding' => (int)($stats['refunding'] ?? 0),
                'refunded' => (int)($stats['refund'] ?? 0),
                'all' => (int)($stats['all'] ?? 0),
            ],
            'todo' => $todo,
            'staff_todo' => $staffTodo,
            'wedding' => [
                'date' => (string)($wedding['wedding_date'] ?? ''),
                'days_remaining' => (int)($wedding['days_remaining'] ?? 0),
                'venue' => (string)($wedding['wedding_venue'] ?? ''),
                'order_sn' => (string)($wedding['order_sn'] ?? ''),
                'progress' => $weddingProgress,
            ],
            'unread' => self::normalizeUnread($unread),
            'roles' => [
                'is_staff' => (int)($user['is_staff'] ?? 0),
                'admin_dashboard_access' => (int)($user['admin_dashboard_access'] ?? 0),
            ],
            'feature_switch' => [
                'staff_center' => $staffCenterEnabled,
                // 服务人员中心写能力灰度：工作台写入口是否开放（默认开启）
                'staff_center_write' => (int)ConfigService::get('feature_switch', 'staff_center_write', 1),
                'staff_admin' => (int)ConfigService::get('feature_switch', 'staff_admin', 1),
                'staff_tag_review_enabled' => (int)ConfigService::get('feature_switch', 'staff_tag_review_enabled', 0),
                'mini_program_review_mode' => (int)ConfigService::get('feature_switch', 'mini_program_review_mode', 0),
                'admin_dashboard' => (int)ConfigService::get('feature_switch', 'admin_dashboard', 1),
            ],
            'server_time' => time(),
            'degraded' => self::$degraded,
        ];
    }

    /**
     * 人员详情聚合：资料 + 套餐 + 附加项 + 作品 + 评价 + 档期 + 角色配置
     */
    public static function staff(int $staffId, int $userId, array $params): ?array
    {
        self::$degraded = [];

        $date = (string)($params['date'] ?? '');
        $regionContext = [
            'province_code' => (string)($params['province_code'] ?? ''),
            'city_code' => (string)($params['city_code'] ?? ''),
            'district_code' => (string)($params['district_code'] ?? ''),
        ];

        $detail = self::safe('staff', static fn () => StaffLogic::detail($staffId, $userId, $regionContext, $date), []);
        if (empty($detail) || empty($detail['id'])) {
            return null;
        }

        $works = self::safe('works', static fn () => StaffLogic::works($staffId), []);
        $reviewParams = ['staff_id' => $staffId, 'page' => 1, 'limit' => 3] + $params;
        $reviews = self::safe('review', static fn () => ReviewLogic::staffReviews($reviewParams), []);
        $reviewStats = self::safe('review_stats', static fn () => ReviewLogic::staffStats($staffId), []);

        return [
            'staff' => [
                'id' => (int)$detail['id'],
                'name' => (string)($detail['name'] ?? ''),
                'avatar' => (string)($detail['avatar'] ?? ''),
                'cover' => (string)($detail['cover'] ?? ''),
                'rating' => $detail['rating'] ?? 0,
                'order_count' => (int)($detail['order_count'] ?? 0),
                'price' => $detail['price'] ?? 0,
                'has_price' => (int)($detail['has_price'] ?? 1),
                'price_text' => (string)($detail['price_text'] ?? ''),
                'tags' => is_array($detail['tags'] ?? null) ? $detail['tags'] : [],
                'profile' => (string)($detail['profile'] ?? ''),
                'list_intro' => (string)($detail['list_intro'] ?? ''),
                'intro' => (string)($detail['intro'] ?? ''),
                // 服务说明：详情页「服务说明」区块的唯一数据源，v2 聚合不能丢
                'service_desc' => (string)($detail['service_desc'] ?? ''),
                'is_favorite' => (int)($detail['is_favorite'] ?? 0),
                'is_recommend' => (int)($detail['is_recommend'] ?? 0),
                'experience_years' => (int)($detail['experience_years'] ?? 0),
                'category_name' => (string)($detail['category_name'] ?? ''),
            ],
            'packages' => is_array($detail['packages'] ?? null) ? $detail['packages'] : [],
            'addons' => is_array($detail['addons'] ?? null) ? $detail['addons'] : [],
            'works' => [
                'list' => array_slice(is_array($works) ? $works : [], 0, 6),
                'total' => is_array($works) ? count($works) : 0,
                'page' => 1,
                'page_size' => 6,
                'has_more' => is_array($works) && count($works) > 6,
            ],
            'review' => [
                'stats' => is_array($reviewStats) ? $reviewStats : [],
                'list' => is_array($reviews['lists'] ?? null) ? $reviews['lists'] : (is_array($reviews) ? $reviews : []),
                'total' => (int)($reviews['count'] ?? $reviews['total'] ?? 0),
            ],
            'availability' => [
                'date' => $date,
                'is_available' => (bool)($detail['schedule_available'] ?? true),
                'message' => (string)($detail['schedule_message'] ?? ''),
            ],
            // 详情页「查看订单」：当前用户在该人员当前日期的有效订单
            'my_order' => $detail['my_order'] ?? null,
            'role_configs' => is_array($detail['related_role_configs'] ?? null) ? $detail['related_role_configs'] : [],
            'banner' => [
                // 与 v1 的 getStaffBannerConfig() 同源，v2 不再裁掉展示配置
                'mode' => (int)($detail['banner_mode'] ?? 1),
                'small_height' => (int)($detail['banner_small_height'] ?? 400),
                'large_height' => (int)($detail['banner_large_height'] ?? 600),
                'indicator_style' => (int)($detail['banner_indicator_style'] ?? 1),
                'autoplay' => (int)($detail['banner_autoplay'] ?? 1),
                'interval' => (int)($detail['banner_interval'] ?? 3000),
                'list' => is_array($detail['banners'] ?? null) ? $detail['banners'] : [],
            ],
            // 长图详情是详情页「详情」页签的一部分，v2 聚合不能丢
            'long_detail' => (string)($detail['long_detail'] ?? ''),
            'server_time' => time(),
            'degraded' => self::$degraded,
        ];
    }

    /**
     * 订单详情聚合：订单 + 服务项 + 支付 + 日志 + 动作矩阵 + 待办关联
     *
     * 动作矩阵由服务端算，客户端只渲染 —— 老端在页面里用 6 个字段组合判断，
     * 3,302 行里有近 400 行在算按钮该不该显示。
     */
    public static function order(int $orderId, int $userId): ?array
    {
        self::$degraded = [];

        // 主数据不能走 safe()：被吞掉就会把「加载失败」说成「订单不存在」，
        // 用户看到的错误提示完全错位。这里让异常上抛，由控制器返回真实原因。
        try {
            $detail = OrderLogic::getOrderDetail($orderId, $userId);
        } catch (\Throwable $e) {
            Log::error('[v2.order] 加载失败：' . $e->getMessage());
            throw new \RuntimeException('订单加载失败，请稍后重试');
        }

        if (empty($detail)) {
            return null;
        }

        $status = (int)($detail['order_status'] ?? -1);
        $needPay = (float)($detail['need_pay_amount'] ?? 0);

        [$canChange, $changeMessage] = self::safe(
            'change_check',
            static function () use ($orderId): array {
                $result = OrderChange::checkCanChange($orderId);
                return is_array($result) && count($result) === 2 ? $result : [false, ''];
            },
            [false, '']
        );

        $offlineActive = (int)($detail['payment_channel'] ?? Order::PAYMENT_CHANNEL_ONLINE) === Order::PAYMENT_CHANNEL_OFFLINE
            || (int)($detail['offline_collection_available'] ?? 0) === 1;
        $receiptPending = !empty($detail['receipt_pending']);
        $voucherPending = trim((string)($detail['pay_voucher'] ?? '')) !== ''
            && (int)($detail['pay_voucher_status'] ?? -1) === Order::VOUCHER_STATUS_PENDING;
        $pendingReselectItems = is_array($detail['pending_reselect_items'] ?? null)
            ? $detail['pending_reselect_items']
            : [];
        $pendingQuestionnaire = self::safe(
            'pending_questionnaire',
            static fn () => self::buildPendingQuestionnaire($orderId, $userId),
            null
        );

        $actions = [
            'pay' => $status === Order::STATUS_PENDING_PAY && $needPay > 0 && !$offlineActive,
            'cancel' => in_array($status, [Order::STATUS_PENDING_CONFIRM, Order::STATUS_PENDING_PAY], true)
                && (int)($detail['complete_time'] ?? 0) === 0
                && !$receiptPending,
            'confirm_complete' => (int)($detail['can_user_complete'] ?? 0) === 1,
            'apply_refund' => (int)($detail['can_user_refund'] ?? 0) === 1,
            'delete' => in_array($status, [
                Order::STATUS_COMPLETED,
                Order::STATUS_REVIEWED,
                Order::STATUS_CANCELLED,
                Order::STATUS_REFUNDED,
            ], true),
            'change_date' => (bool)$canChange,
            'pause' => (bool)$canChange && (int)($detail['is_paused'] ?? 0) === 0,
            'review' => $status === Order::STATUS_COMPLETED,
            'fill_questionnaire' => !empty($pendingQuestionnaire) && (int)($pendingQuestionnaire['is_issued'] ?? 0) === 1,
            'upload_voucher' => $status === Order::STATUS_PENDING_PAY
                && $needPay > 0
                && $offlineActive
                && !$receiptPending
                && !$voucherPending,
            'contact_advisor' => true,
            'reselect_collaborator' => !empty($pendingReselectItems),
        ];

        $reasons = [];
        if (!$actions['pay']) {
            if ($status === Order::STATUS_PENDING_CONFIRM) {
                $reasons['pay'] = '待商家确认';
            } elseif ($offlineActive && $status === Order::STATUS_PENDING_PAY) {
                $reasons['pay'] = $voucherPending ? '线下凭证审核中' : '当前需线下付款并上传凭证';
            } elseif ($needPay <= 0) {
                $reasons['pay'] = '无需支付';
            } elseif ($status !== Order::STATUS_PENDING_PAY) {
                $reasons['pay'] = '当前状态不可支付';
            }
        }
        if (!$actions['change_date'] && (string)$changeMessage !== '') {
            $reasons['change_date'] = (string)$changeMessage;
        }
        if (!$actions['fill_questionnaire'] && !empty($pendingQuestionnaire)) {
            $reasons['fill_questionnaire'] = '待发放问卷';
        }

        $items = is_array($detail['items'] ?? null) ? $detail['items'] : [];

        return [
            'order' => [
                'id' => (int)$detail['id'],
                'order_sn' => (string)($detail['order_sn'] ?? ''),
                'order_status' => $status,
                'order_status_desc' => (string)($detail['order_status_desc'] ?? ''),
                'pay_status' => (int)($detail['pay_status'] ?? 0),
                'total_amount' => $detail['total_amount'] ?? 0,
                'discount_amount' => $detail['discount_amount'] ?? 0,
                'pay_amount' => $detail['pay_amount'] ?? 0,
                'paid_amount' => $detail['paid_amount'] ?? 0,
                'payment_channel' => (int)($detail['payment_channel'] ?? Order::PAYMENT_CHANNEL_ONLINE),
                'complete_time' => (int)($detail['complete_time'] ?? 0),
                'confirm_remain_seconds' => (int)($detail['confirm_remain_seconds'] ?? 0),
                'service_date' => (string)($detail['service_date'] ?? ''),
                'contact_name' => (string)($detail['contact_name'] ?? ''),
                'contact_mobile' => (string)($detail['contact_mobile'] ?? ''),
                'address' => (string)($detail['service_address'] ?? ''),
                'create_time' => (string)($detail['create_time'] ?? ''),
            ],
            'items' => $items,
            'payments' => is_array($detail['payments'] ?? null) ? $detail['payments'] : [],
            'logs' => is_array($detail['logs'] ?? null) ? $detail['logs'] : [],
            'actions' => $actions,
            'action_reasons' => $reasons,
            'pending_reselect_items' => $pendingReselectItems,
            'pending_review' => $status === Order::STATUS_COMPLETED
                ? self::safe('pending_review', static fn () => self::buildPendingReview($orderId, $userId, $items), null)
                : null,
            'pending_questionnaire' => $pendingQuestionnaire,
            'pay' => [
                'need_pay' => (string)($detail['need_pay'] ?? ''),
                'need_pay_amount' => $detail['need_pay_amount'] ?? 0,
                'pay_remain_seconds' => (int)($detail['pay_remain_seconds'] ?? 0),
                'current_pay_stage' => (string)($detail['current_pay_stage'] ?? ''),
                'refundable_amount' => $detail['refundable_amount'] ?? 0,
                'can_user_refund' => (int)($detail['can_user_refund'] ?? 0),
                'deposit_amount' => $detail['deposit_amount'] ?? 0,
                'balance_amount' => $detail['balance_amount'] ?? 0,
                'deposit_paid' => (int)($detail['deposit_paid'] ?? 0),
                'balance_paid' => (int)($detail['balance_paid'] ?? 0),
                'unpaid_amount' => $detail['unpaid_amount'] ?? 0,
            ],
            'offline' => [
                'enabled' => (int)($detail['offline_collection_enabled'] ?? 0) === 1,
                'available' => (int)($detail['offline_collection_available'] ?? 0) === 1,
                // 注意：v1 这个字段是「联系入口描述对象」而非字符串
                //   {scene, entry_path, api_path, title, tips}
                // 早期版本按字符串强转，会抛 Array to string conversion。
                'contact' => self::normalizeContact($detail['offline_collection_contact'] ?? null),
                'pay_voucher' => (string)($detail['pay_voucher'] ?? ''),
                'pay_voucher_status' => $detail['pay_voucher_status'] ?? null,
                'pay_voucher_audit_remark' => (string)($detail['pay_voucher_audit_remark'] ?? ''),
                'receipt_pending' => $receiptPending,
            ],
            'server_time' => time(),
            'degraded' => self::$degraded,
        ];
    }

    /**
     * 下单预检：报价 + 明细 + 结构化校验 + 报价指纹
     *
     * 报价指纹用于下单时校验价格是否变化 —— 老端靠客户端比对金额，跨端不可靠。
     */
    public static function bookingPreview(int $userId, array $params): array
    {
        self::$degraded = [];

        $preview = self::safe('preview', static fn () => OrderLogic::previewOrder($userId, $params), []);

        $validations = [];
        // previewOrder 的返回结构是 { success, data: { items, service_amount, total_amount... }, message }
        // 早期实现直接读根层，导致所有金额恒为 0、items 恒为空 —— 确认页只能显示 0。
        $failed = is_array($preview) && ($preview['success'] ?? true) === false;
        $payload = is_array($preview) && is_array($preview['data'] ?? null) ? $preview['data'] : $preview;
        if (!is_array($payload)) $payload = [];

        if ($failed) {
            $message = (string)($preview['message'] ?? '');
            $validations[] = [
                'code' => 'BOOKING_INVALID',
                'level' => 'error',
                'message' => $message !== '' ? $message : '当前选择不可预约，请返回修改',
            ];
        } elseif (empty($payload)) {
            $validations[] = ['code' => 'PREVIEW_FAILED', 'level' => 'error', 'message' => '价格计算失败，请稍后重试'];
        }
        foreach (self::collectValidationMessages($payload) as $message) {
            $validations[] = ['code' => 'BOOKING_INVALID', 'level' => 'error', 'message' => $message];
        }
        // 失败时 message 会同时来自手动分支与 payload，按 code+message 去重，避免同一条错误上墙两次
        $seen = [];
        $validations = array_values(array_filter($validations, static function (array $item) use (&$seen): bool {
            $key = ($item['code'] ?? '') . '|' . ($item['message'] ?? '');
            if (isset($seen[$key])) {
                return false;
            }
            $seen[$key] = true;
            return true;
        }));

        $quoteAmount = $payload['total_amount'] ?? 0;
        $quoteHash = hash('sha256', json_encode([
            (int)($params['staff_id'] ?? 0),
            (int)($params['package_id'] ?? 0),
            (string)($params['date'] ?? ''),
            self::normalizeIds($params['addon_ids'] ?? []),
            (string)$quoteAmount,
            (string)($payload['pay_amount'] ?? ''),
        ], JSON_UNESCAPED_UNICODE));
        // 报价失败/为空/金额为 0 时不发指纹：前端以“有指纹”判定报价成功，
        // 发一个 0 元指纹会让确认页显示 ¥0.00 而不是“报价失败”。
        $quoteOk = !$failed && !empty($payload) && (float)$quoteAmount > 0;

        return [
            'quote' => [
                'service_amount' => $payload['service_amount'] ?? 0,
                'addon_amount' => $payload['addon_amount'] ?? 0,
                'total_amount' => $quoteAmount,
                'pay_amount' => $payload['pay_amount'] ?? 0,
                'deposit_amount' => $payload['deposit_amount'] ?? 0,
                'balance_amount' => $payload['balance_amount'] ?? 0,
                'payment_mode' => (string)($payload['payment_mode'] ?? ''),
                'payment_mode_desc' => (string)($payload['payment_mode_desc'] ?? ''),
            ],
            'items' => is_array($payload['items'] ?? null) ? $payload['items'] : [],
            'validations' => $validations,
            'quote_hash' => $quoteOk ? $quoteHash : '',
            // 10 分钟有效：足够用户填联系人，又不会让过期价格长期生效
            'expires_at' => time() + 600,
            'contact_default' => [
                'name' => (string)($payload['contact_name'] ?? ''),
                'mobile' => (string)($payload['contact_mobile'] ?? ''),
            ],
            'degraded' => self::$degraded,
        ];
    }

    // ==================== 内部工具 ====================

    /**
     * 分段容错：失败只降级当前块，记录到 degraded。
     */
    private static function safe(string $key, callable $fn, $default)
    {
        try {
            $result = $fn();
            return $result === null ? $default : $result;
        } catch (\Throwable $e) {
            self::$degraded[] = $key;
            Log::error('[v2] ' . $key . ' 降级：' . $e->getMessage());
            return $default;
        }
    }

    private static function emptyUnread(): array
    {
        return ['total' => 0, 'system' => 0, 'order' => 0, 'interact' => 0];
    }

    private static function emptyTodo(): array
    {
        return [
            'pending_review' => 0,
            'pending_questionnaire' => 0,
            'pending_waitlist' => 0,
            'aftersale_unfinished' => 0,
        ];
    }

    private static function emptyStaffTodo(): array
    {
        return [
            'pending_confirm_orders' => 0,
            'today_service_count' => 0,
            'pending_settlement_count' => 0,
            'pending_waitlist_count' => 0,
            'pending_followup_count' => 0,
            'total' => 0,
        ];
    }

    private static function normalizeUnread(array $unread): array
    {
        $system = (int)($unread['system'] ?? 0);
        $order = (int)($unread['order'] ?? 0);
        $interact = (int)($unread['interact'] ?? 0);
        $total = (int)($unread['total'] ?? 0);

        return [
            'total' => $total > 0 ? $total : $system + $order + $interact,
            'system' => $system,
            'order' => $order,
            'interact' => $interact,
        ];
    }

    private static function normalizeStaffCards($staff): array
    {
        if (!is_array($staff)) {
            return [];
        }
        $result = [];
        foreach ($staff as $item) {
            if (!is_array($item)) {
                continue;
            }
            $result[] = [
                'id' => (int)($item['id'] ?? 0),
                'name' => (string)($item['name'] ?? ''),
                'avatar' => (string)($item['avatar'] ?? ''),
                'rating' => $item['rating'] ?? 0,
                'order_count' => (int)($item['order_count'] ?? 0),
                'price' => $item['price'] ?? 0,
                'tags' => is_array($item['tags'] ?? null) ? $item['tags'] : [],
                // 推荐位卡片要能明确标出「推荐」，前台不再靠「这个接口只有推荐」去猜
                'is_recommend' => (int)($item['is_recommend'] ?? 0),
            ];
        }
        return $result;
    }

    private static function normalizeArticles(array $articles): array
    {
        $result = [];
        foreach ($articles as $item) {
            if (!is_array($item)) {
                continue;
            }
            $result[] = [
                'id' => (int)($item['id'] ?? 0),
                'title' => (string)($item['title'] ?? ''),
                'desc' => (string)($item['desc'] ?? ''),
                'abstract' => (string)($item['abstract'] ?? ''),
                'image' => (string)($item['image'] ?? ''),
                'author' => (string)($item['author'] ?? ''),
                'click' => $item['click'] ?? 0,
                'create_time' => (string)($item['create_time'] ?? ''),
            ];
        }
        return $result;
    }

    /**
     * 我的页待办计数。
     * 只统计「用户能立刻处理」的，已过期/已取消的不算，避免数字虚高。
     *
     * 待评价口径必须复用 ReviewLogic::pendingOrders（已完成且未评价的订单项），
     * 不能用 Review::STATUS_PENDING —— 那是「评价待审核」，语义完全不同。
     */
    private static function buildTodo(int $userId): array
    {
        $pendingReview = (int)(ReviewLogic::pendingOrders([
            'user_id' => $userId,
            'page' => 1,
            'limit' => 1,
        ])['total'] ?? 0);

        $pendingQuestionnaire = CoupleQuestionnaireTask::where('user_id', $userId)
            ->whereNull('delete_time')
            ->whereIn('status', [CoupleQuestionnaireTask::STATUS_PENDING, CoupleQuestionnaireTask::STATUS_VIEWED])
            ->whereIn('send_status', [
                CoupleQuestionnaireTask::SEND_STATUS_SENT,
                CoupleQuestionnaireTask::SEND_STATUS_FAILED,
            ])
            ->where(static function ($query) {
                $query->where('expire_time', 0)->whereOr('expire_time', '>', time());
            })
            ->count();

        $pendingWaitlist = \app\common\model\schedule\Waitlist::where('user_id', $userId)
            ->whereIn('notify_status', [
                \app\common\model\schedule\Waitlist::NOTIFY_STATUS_PENDING,
                \app\common\model\schedule\Waitlist::NOTIFY_STATUS_NOTIFIED,
            ])
            ->count();

        $aftersaleUnfinished = \app\common\model\aftersale\AfterSaleTicket::where('user_id', $userId)
            ->whereIn('status', [
                \app\common\model\aftersale\AfterSaleTicket::STATUS_PENDING,
                \app\common\model\aftersale\AfterSaleTicket::STATUS_PROCESSING,
                \app\common\model\aftersale\AfterSaleTicket::STATUS_CONFIRMING,
            ])
            ->count();

        return [
            'pending_review' => $pendingReview,
            'pending_questionnaire' => $pendingQuestionnaire,
            'pending_waitlist' => $pendingWaitlist,
            'aftersale_unfinished' => $aftersaleUnfinished,
        ];
    }

    /** 本单里第一个尚未评价的服务项 */
    private static function buildPendingReview(int $orderId, int $userId, array $items): ?array
    {
        $reviewableItems = [];
        $fallbackItems = [];
        foreach ($items as $item) {
            if (!is_array($item) || empty($item['id']) || (int)($item['item_status'] ?? 0) === OrderItem::STATUS_CANCELLED) {
                continue;
            }
            $itemId = (int)$item['id'];
            $itemType = (int)($item['item_type'] ?? OrderItem::TYPE_SERVICE);
            $meta = [
                'id' => $itemId,
                'order_id' => $orderId,
                'staff_name' => (string)($item['staff']['name'] ?? $item['staff_name'] ?? ''),
                'package_name' => (string)($item['package']['name'] ?? $item['package_name'] ?? ''),
            ];
            if (in_array($itemType, [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF], true)) {
                $reviewableItems[] = $meta;
            } else {
                $fallbackItems[] = $meta;
            }
        }
        $candidates = !empty($reviewableItems) ? $reviewableItems : $fallbackItems;
        if (empty($candidates)) {
            return null;
        }

        $itemIds = array_column($candidates, 'id');
        $reviewed = Review::where('user_id', $userId)
            ->whereIn('order_item_id', $itemIds)
            ->column('order_item_id');
        $reviewed = array_map('intval', $reviewed ?: []);

        foreach ($candidates as $candidate) {
            if (!in_array((int)$candidate['id'], $reviewed, true)) {
                return $candidate;
            }
        }
        return null;
    }

    /** 本单关联的待填写问卷 */
    private static function buildPendingQuestionnaire(int $orderId, int $userId): ?array
    {
        $task = CoupleQuestionnaireTask::where('user_id', $userId)
            ->where('order_id', $orderId)
            ->whereNull('delete_time')
            ->whereIn('status', [CoupleQuestionnaireTask::STATUS_PENDING, CoupleQuestionnaireTask::STATUS_VIEWED])
            ->where(static function ($query) {
                $query->where('expire_time', 0)->whereOr('expire_time', '>', time());
            })
            ->order('id', 'desc')
            ->find();

        if (!$task) {
            return null;
        }

        $sendStatus = (int)($task->send_status ?? CoupleQuestionnaireTask::SEND_STATUS_PENDING);
        $isIssued = in_array($sendStatus, [
            CoupleQuestionnaireTask::SEND_STATUS_SENT,
            CoupleQuestionnaireTask::SEND_STATUS_FAILED,
        ], true);

        return [
            'id' => (int)$task->id,
            'title' => (string)($task->title_snapshot ?: ($task->title ?? '新人问卷')),
            'status' => (int)$task->status,
            'send_status' => $sendStatus,
            'is_issued' => $isIssued ? 1 : 0,
            'planned_send_time' => (int)($task->planned_send_time ?? 0) > 0
                ? date('Y-m-d H:i', (int)$task->planned_send_time)
                : '',
        ];
    }

    /** 从 preview 结果里提取结构化校验信息 */
    private static function collectValidationMessages($preview): array
    {
        if (!is_array($preview)) {
            return [];
        }
        $messages = [];
        foreach (['error', 'errors', 'validation_errors', 'message'] as $key) {
            $value = $preview[$key] ?? null;
            if (is_string($value) && $value !== '') {
                $messages[] = $value;
            } elseif (is_array($value)) {
                foreach ($value as $item) {
                    if (is_string($item) && $item !== '') {
                        $messages[] = $item;
                    } elseif (is_array($item) && !empty($item['message'])) {
                        $messages[] = (string)$item['message'];
                    }
                }
            }
        }
        return array_values(array_unique($messages));
    }

    private static function normalizeIds($ids): array
    {
        if (!is_array($ids)) {
            return [];
        }
        $result = array_map('intval', $ids);
        sort($result);
        return $result;
    }

    /**
     * 统一「联系入口」描述对象。
     * v1 可能返回数组（{scene, entry_path, api_path, title, tips}）或纯字符串，
     * 两种都收敛成同一个结构，客户端不用分支判断。
     */
    private static function normalizeContact($contact): array
    {
        if (is_array($contact)) {
            return [
                'title' => (string)($contact['title'] ?? '联系顾问'),
                'tips' => (string)($contact['tips'] ?? ''),
                'scene' => (string)($contact['scene'] ?? 'order_detail'),
                'entry_path' => (string)($contact['entry_path'] ?? ''),
                'api_path' => (string)($contact['api_path'] ?? ''),
            ];
        }

        return [
            'title' => is_string($contact) && $contact !== '' ? $contact : '联系顾问',
            'tips' => '',
            'scene' => 'order_detail',
            'entry_path' => '',
            'api_path' => '',
        ];
    }
}
