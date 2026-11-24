<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 装修点击跳转与参数归一化（服务端镜像）
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * 移动端装修跳转链接契约镜像。
 *
 * ⚠ 唯一 TS 真源：`shared/contracts/decorate-links.ts`
 *   新增页面路由、参数白名单或历史路径映射时，需与 TS 侧保持一致。
 */
class DecorateLinkService
{
    public const VALID_ACTIONS = [
        'make_phone_call',
        'wechat_contact',
        'copy_text',
    ];

    public const ACTION_LABELS = [
        'make_phone_call' => '拨打客服电话',
        'wechat_contact' => '微信原生客服会话',
        'copy_text' => '一键复制文本',
    ];

    public const TAB_PAGES = [
        '/pages/index/index',
        '/pages/feed/feed',
        '/pages/query/query',
        '/pages/user/user',
    ];

    /**
     * 历史老路径 → newuniapp 真实路径。
     */
    public const LEGACY_PATH_MAP = [
        '/pages/schedule_query/schedule_query' => '/pages/query/query',
        '/pages/staff_list/staff_list' => '/packages/pkg-catalog/pages/staff-list/staff-list',
        '/pages/staff_detail/staff_detail' => '/packages/pkg-catalog/pages/staff-detail/staff-detail',
        '/pages/staff_booking/staff_booking' => '/packages/pkg-catalog/pages/booking/booking',
        '/pages/news/news' => '/packages/pkg-catalog/pages/case-list/case-list',
        '/pages/news_detail/news_detail' => '/packages/pkg-catalog/pages/article-detail/article-detail',
        '/pages/dynamic/dynamic' => '/pages/feed/feed',
        '/pages/dynamic_detail/dynamic_detail' => '/packages/pkg-catalog/pages/feed-detail/feed-detail',
        '/pages/payment/payment' => '/packages/pkg-order/pages/payment-result/payment-result',
        '/packages/pages/customer_service/customer_service' => '/pages/customer-service/customer-service',
        '/packages/pages/customer_service/index' => '/pages/customer-service/customer-service',
        '/packages/pages/staff_detail/staff_detail' => '/packages/pkg-catalog/pages/staff-detail/staff-detail',
        '/packages/pages/staff_booking/staff_booking' => '/packages/pkg-catalog/pages/booking/booking',
        '/packages/pages/staff_list/staff_list' => '/packages/pkg-catalog/pages/staff-list/staff-list',
        '/packages/pages/dynamic_list/dynamic_list' => '/pages/feed/feed',
        '/packages/pages/news/news' => '/packages/pkg-catalog/pages/case-list/case-list',
        '/packages/pages/news_detail/news_detail' => '/packages/pkg-catalog/pages/article-detail/article-detail',
        '/packages/pages/order_detail/order_detail' => '/packages/pkg-order/pages/detail/detail',
        '/pages/order_detail/order_detail' => '/packages/pkg-order/pages/detail/detail',
        '/packages/pages/order_confirm/order_confirm' => '/packages/pkg-catalog/pages/booking/booking',
        '/packages/pages/aftersale/aftersale' => '/packages/pkg-service/pages/aftersale/index',
        '/pages/user_set/user_set' => '/packages/pkg-user/pages/settings/settings',
        '/pages/user_data/user_data' => '/packages/pkg-user/pages/profile/profile',
        '/pages/change_password/change_password' => '/packages/pkg-user/pages/settings/settings',
        '/pages/bind_mobile/bind_mobile' => '/packages/pkg-user/pages/profile/profile',
        '/packages/pages/collection/collection' => '/packages/pkg-user/pages/favorite/favorite',
        '/pages/collection/collection' => '/packages/pkg-user/pages/favorite/favorite',
        '/packages/pages/staff_favorite/staff_favorite' => '/packages/pkg-user/pages/favorite/favorite',
        '/packages/pages/my_activity/my_activity' => '/packages/pkg-user/pages/activity/activity',
        '/pages/my_activity/my_activity' => '/packages/pkg-user/pages/activity/activity',
        '/packages/pages/activity_registration/list' => '/packages/pkg-user/pages/activity/activity',
        '/pages/activity_registration/list' => '/packages/pkg-user/pages/activity/activity',
        '/packages/pages/activity_registration/detail' => '/packages/pkg-user/pages/activity/activity',
        '/packages/pages/notification/index' => '/pages/message/message',
        '/pages/notification/index' => '/pages/message/message',
        '/pages/order/list' => '/pages/order/order',
        '/packages/pages/waitlist/waitlist' => '/packages/pkg-order/pages/waitlist/waitlist',
        '/packages/pages/order_change/list' => '/packages/pkg-service/pages/change/list',
        '/packages/pages/order_change/apply_date' => '/packages/pkg-service/pages/change/apply-date',
        '/packages/pages/order_change/apply_pause' => '/packages/pkg-service/pages/change/apply-pause',
        '/packages/pages/order_change/apply_add_item' => '/packages/pkg-service/pages/change/apply-addon',
        '/packages/pages/order_change/pause_detail' => '/packages/pkg-service/pages/change/detail',
        '/packages/pages/order_change/change_detail' => '/packages/pkg-service/pages/change/detail',
        '/packages/pages/aftersale/index' => '/packages/pkg-service/pages/aftersale/index',
        '/pages/aftersale/index' => '/packages/pkg-service/pages/aftersale/index',
        '/packages/pages/aftersale/ticket' => '/packages/pkg-service/pages/aftersale/ticket-list',
        '/packages/pages/aftersale/create_ticket' => '/packages/pkg-service/pages/aftersale/ticket-create',
        '/packages/pages/aftersale/complaint' => '/packages/pkg-service/pages/aftersale/complaint-list',
        '/packages/pages/aftersale/create_complaint' => '/packages/pkg-service/pages/aftersale/complaint-create',
        '/packages/pages/aftersale/callback' => '/packages/pkg-service/pages/aftersale/callback-list',
        '/packages/pages/aftersale/ticket_detail' => '/packages/pkg-service/pages/aftersale/ticket-detail',
        '/packages/pages/review/list' => '/packages/pkg-order/pages/review/list',
        '/pages/review/list' => '/packages/pkg-order/pages/review/list',
        '/packages/pages/review/detail' => '/packages/pkg-order/pages/review/list',
        '/pages/as_us/as_us' => '/pages/about/about',
        '/packages/pages/agreement/agreement' => '/pages/agreement/agreement',
        '/packages/pages/case_list/case_list' => '/packages/pkg-catalog/pages/case-list/case-list',
        '/packages/pages/search/search' => '/pages/search/search',
        '/packages/pages/payment_result/payment_result' => '/packages/pkg-order/pages/payment-result/payment-result',
        '/packages/pages/dynamic_detail/dynamic_detail' => '/packages/pkg-catalog/pages/feed-detail/feed-detail',
        '/packages/pages/staff_center/staff_center' => '/packages/pkg-staff/pages/center/center',
        '/packages/pages/staff_profile/staff_profile' => '/packages/pkg-staff/pages/profile/profile',
        '/packages/pages/staff_order_list/staff_order_list' => '/packages/pkg-staff/pages/order-list/order-list',
        '/packages/pages/staff_order_detail/staff_order_detail' => '/packages/pkg-staff/pages/order-detail/order-detail',
        '/packages/pages/staff_schedule/staff_schedule' => '/packages/pkg-staff/pages/schedule/schedule',
        '/packages/pages/staff_certificate_list/staff_certificate_list' => '/packages/pkg-staff/pages/certificate-list/certificate-list',
        '/packages/pages/staff_work_list/staff_work_list' => '/packages/pkg-staff/pages/work-list/work-list',
        '/packages/pages/staff_work_detail/staff_work_detail' => '/packages/pkg-staff/pages/work-list/work-list',
        '/packages/pages/staff_package_list/staff_package_list' => '/packages/pkg-staff/pages/package-list/package-list',
        '/packages/pages/staff_addon_list/staff_addon_list' => '/packages/pkg-staff/pages/addon-list/addon-list',
        '/packages/pages/staff_dynamic_list/staff_dynamic_list' => '/packages/pkg-staff/pages/dynamic-list/dynamic-list',
        '/packages/pages/admin_dashboard/admin_dashboard' => '/packages/pkg-staff/pages/dashboard/dashboard',
        '/packages/pages/couple_questionnaire/detail' => '/packages/pkg-service/pages/questionnaire/detail',
        '/packages/pages/staff_settlement/staff_settlement' => '/packages/pkg-staff/pages/settlement-list/settlement-list',
        '/packages/pages/staff_schedule_confirm_letter/staff_schedule_confirm_letter' => '/packages/pkg-staff/pages/card-poster/card-poster?tab=poster',
    ];

    /**
     * 页面规范名称。
     */
    public const PAGE_NAMES = [
        '/pages/index/index' => '首页',
        '/pages/feed/feed' => '动态广场',
        '/pages/query/query' => '查档期',
        '/pages/user/user' => '我的',
        '/packages/pkg-catalog/pages/staff-list/staff-list' => '服务人员列表',
        '/packages/pkg-catalog/pages/case-list/case-list' => '真实案例',
        '/pages/search/search' => '站内搜索',
        '/pages/customer-service/customer-service' => '客服管家',
        '/packages/pkg-service/pages/concierge/concierge' => '我的咨询',
        '/pages/order/order' => '我的订单',
        '/packages/pkg-order/pages/waitlist/waitlist' => '我的候补',
        '/packages/pkg-order/pages/review/list' => '我的评价',
        '/packages/pkg-service/pages/change/list' => '变更记录',
        '/packages/pkg-service/pages/aftersale/index' => '售后服务',
        '/packages/pkg-service/pages/aftersale/ticket-list' => '服务工单',
        '/packages/pkg-service/pages/aftersale/ticket-create' => '提交工单',
        '/packages/pkg-service/pages/aftersale/complaint-list' => '投诉记录',
        '/packages/pkg-service/pages/aftersale/complaint-create' => '发起投诉',
        '/packages/pkg-service/pages/aftersale/callback-list' => '服务回访',
        '/packages/pkg-user/pages/favorite/favorite' => '我的收藏',
        '/packages/pkg-user/pages/activity/activity' => '我的活动',
        '/pages/message/message' => '消息中心',
        '/packages/pkg-user/pages/profile/profile' => '个人资料',
        '/packages/pkg-user/pages/settings/settings' => '设置',
        '/pages/about/about' => '关于我们',
        '/pages/agreement/agreement' => '协议与条款',
        '/packages/pkg-staff/pages/center/center' => '服务人员工作台',
        '/packages/pkg-staff/pages/order-list/order-list' => '服务订单管理',
        '/packages/pkg-staff/pages/schedule/schedule' => '档期管理',
        '/packages/pkg-staff/pages/profile/profile' => '人员资料',
        '/packages/pkg-staff/pages/certificate-list/certificate-list' => '证书管理',
        '/packages/pkg-staff/pages/work-list/work-list' => '作品管理',
        '/packages/pkg-staff/pages/package-list/package-list' => '套餐管理',
        '/packages/pkg-staff/pages/addon-list/addon-list' => '附加项管理',
        '/packages/pkg-staff/pages/dynamic-list/dynamic-list' => '动态管理',
        '/packages/pkg-staff/pages/settlement-list/settlement-list' => '我的结算',
        '/packages/pkg-staff/pages/dashboard/dashboard' => '经营驾驶舱',
    ];

    /**
     * 路由级结构化参数白名单规则。
     */
    private const ROUTE_PARAM_RULES = [
        '/pages/feed/feed' => [
            'dynamic_type' => ['enum', ['1', '2', '4']],
            'sort' => ['enum', ['latest', 'hot']],
            'tag' => ['text', 20],
            'keyword' => ['text', 30],
        ],
        '/pages/query/query' => [
            'category_id' => ['pos_int'],
            'tag_ids' => ['tag_ids', 5],
            'region' => ['region'],
            'date' => ['date'],
            'keyword' => ['text', 30],
        ],
        '/packages/pkg-catalog/pages/staff-list/staff-list' => [
            'category_id' => ['pos_int'],
            'category_name' => ['category_name', 24],
            'tag_ids' => ['tag_ids', 5],
            'region' => ['region'],
            'date' => ['date'],
            'keyword' => ['text', 30],
        ],
        '/packages/pkg-catalog/pages/case-list/case-list' => [
            'tag' => ['text', 20],
        ],
        '/pages/search/search' => [
            'tab' => ['enum', ['staff', 'article']],
            'keyword' => ['text', 30],
        ],
        '/packages/pkg-service/pages/concierge/concierge' => [
            'scene' => ['enum', ['support', 'order', 'aftersale']],
        ],
        '/pages/order/order' => [
            'tab' => ['enum', ['all', 'confirm', 'unpaid', 'service', 'refund']],
        ],
        '/pages/agreement/agreement' => [
            'type' => ['enum', ['privacy', 'service', 'booking', 'aftersale']],
        ],
        // 历史兼容非主选择器路径
        '/packages/pkg-catalog/pages/article-detail/article-detail' => [
            'id' => ['pos_int'],
        ],
        '/packages/pkg-catalog/pages/booking/booking' => [
            'staff_id' => ['pos_int'],
            'date' => ['date'],
        ],
        '/packages/pkg-order/pages/detail/detail' => [
            'id' => ['pos_int'],
        ],
        '/packages/pkg-order/pages/payment-result/payment-result' => [
            'id' => ['pos_int'],
            'order_id' => ['pos_int'],
        ],
        '/packages/pkg-service/pages/aftersale/ticket-detail' => [
            'id' => ['pos_int'],
        ],
        '/packages/pkg-service/pages/change/apply-date' => [
            'order_id' => ['pos_int'],
        ],
        '/packages/pkg-service/pages/change/apply-pause' => [
            'order_id' => ['pos_int'],
        ],
        '/packages/pkg-service/pages/change/apply-addon' => [
            'order_id' => ['pos_int'],
        ],
        '/packages/pkg-service/pages/change/detail' => [
            'id' => ['pos_int'],
        ],
        '/packages/pkg-service/pages/questionnaire/detail' => [
            'id' => ['pos_int'],
        ],
        '/packages/pkg-staff/pages/order-detail/order-detail' => [
            'id' => ['pos_int'],
        ],
        '/packages/pkg-staff/pages/card-poster/card-poster' => [
            'tab' => ['text', 20],
        ],
    ];

    /**
     * @notes 归一化链接对象：映射历史路径 + 按类型与参数白名单清洗
     * @param mixed $link
     * @return array
     */
    public static function normalize($link): array
    {
        if (!is_array($link)) {
            return ['path' => ''];
        }

        $rawPath = self::cleanScalar($link['path'] ?? '', 500);
        $rawType = self::cleanScalar($link['type'] ?? '', 40);
        $rawName = self::cleanScalar($link['name'] ?? '', 60);
        $rawQuery = is_array($link['query'] ?? null) ? $link['query'] : [];

        // 1. 快捷动作
        if ($rawType === 'action' || str_starts_with($rawPath, 'action://')) {
            $inlineAction = str_starts_with($rawPath, 'action://')
                ? substr($rawPath, strlen('action://'))
                : '';
            $q = self::mergeQuery([], $rawQuery);
            $action = $q['action'] ?? $inlineAction;
            if (!in_array($action, self::VALID_ACTIONS, true)) {
                return ['path' => ''];
            }
            $query = ['action' => $action];
            if ($action === 'make_phone_call') {
                $phone = self::cleanScalar($q['phone'] ?? '', 24);
                if ($phone !== '') {
                    $query['phone'] = $phone;
                }
            } elseif ($action === 'copy_text') {
                $text = self::cleanScalar($q['text'] ?? '', 200);
                $toast = self::cleanScalar($q['toast'] ?? '', 30);
                if ($text !== '') {
                    $query['text'] = $text;
                }
                if ($toast !== '') {
                    $query['toast'] = $toast;
                }
            }
            return [
                'path' => 'action://' . $action,
                'type' => 'action',
                'name' => $rawName !== '' ? $rawName : (self::ACTION_LABELS[$action] ?? '快捷动作'),
                'query' => $query,
            ];
        }

        // 2. 跳转小程序
        if ($rawType === 'mini_program' || $rawPath === 'mini_program') {
            $q = self::mergeQuery([], $rawQuery);
            $appId = self::cleanScalar($q['appId'] ?? '', 40);
            $mpPath = self::cleanScalar($q['path'] ?? '', 200);
            $mpQuery = self::cleanScalar($q['query'] ?? '', 200);
            $envVersion = in_array($q['env_version'] ?? '', ['trial', 'develop'], true)
                ? $q['env_version']
                : 'release';
            if ($appId === '' && $rawPath !== 'mini_program') {
                return ['path' => ''];
            }
            $query = ['env_version' => $envVersion];
            if ($appId !== '') {
                $query['appId'] = $appId;
            }
            if ($mpPath !== '') {
                $query['path'] = $mpPath;
            }
            if ($mpQuery !== '') {
                $query['query'] = $mpQuery;
            }
            return [
                'path' => 'mini_program',
                'type' => 'mini_program',
                'name' => $rawName !== '' ? $rawName : '跳转小程序',
                'query' => $query,
            ];
        }

        // 3. 外部网页直接把 URL 写在 path 里
        if (preg_match('/^https?:\/\//i', $rawPath) === 1) {
            return [
                'path' => '/pages/webview/webview',
                'type' => 'custom',
                'name' => $rawName !== '' ? $rawName : '外部网页',
                'query' => ['url' => $rawPath],
            ];
        }

        if ($rawPath === '') {
            return ['path' => ''];
        }

        // 先走历史路径映射 + 内联 query 拆解
        [$path, $mappedQuery] = self::mapLegacyPath($rawPath);
        $mergedQuery = self::mergeQuery($mappedQuery, $rawQuery);

        if ($path === '/pages/webview/webview' || $rawType === 'custom') {
            $url = self::cleanScalar($mergedQuery['url'] ?? '', 500);
            if (preg_match('/^https?:\/\//i', $url) !== 1) {
                return ['path' => ''];
            }
            return [
                'path' => '/pages/webview/webview',
                'type' => 'custom',
                'name' => $rawName !== '' ? $rawName : '外部网页',
                'query' => ['url' => $url],
            ];
        }

        // 4. 服务人员详情
        if ($path === '/packages/pkg-catalog/pages/staff-detail/staff-detail' || $rawType === 'staff') {
            $id = self::cleanPositiveInt($mergedQuery['id'] ?? ($link['id'] ?? ''));
            if ($id === '') {
                return ['path' => ''];
            }
            $query = ['id' => $id];
            $date = self::cleanDate($mergedQuery['date'] ?? '');
            if ($date !== '') {
                $query['date'] = $date;
            }
            return [
                'path' => '/packages/pkg-catalog/pages/staff-detail/staff-detail',
                'type' => 'staff',
                'id' => $id,
                'name' => $rawName !== '' ? $rawName : '服务人员详情',
                'query' => $query,
            ];
        }

        // 5. 动态详情
        if ($path === '/packages/pkg-catalog/pages/feed-detail/feed-detail' || $rawType === 'dynamic') {
            $id = self::cleanPositiveInt($mergedQuery['id'] ?? ($link['id'] ?? ''));
            if ($id === '') {
                return ['path' => ''];
            }
            return [
                'path' => '/packages/pkg-catalog/pages/feed-detail/feed-detail',
                'type' => 'dynamic',
                'id' => $id,
                'name' => $rawName !== '' ? $rawName : '动态详情',
                'query' => ['id' => $id],
            ];
        }

        // 6. 移动端页面（shop）与兼容映射页
        $sanitizedQuery = self::sanitizeShopQuery($path, $mergedQuery);
        $normalized = [
            'path' => $path,
            'type' => 'shop',
        ];
        $resolvedName = self::PAGE_NAMES[$path] ?? $rawName;
        if ($resolvedName !== '') {
            $normalized['name'] = $resolvedName;
        }
        if (in_array($path, self::TAB_PAGES, true) || !empty($link['canTab'])) {
            $normalized['canTab'] = true;
        }
        if ($sanitizedQuery !== []) {
            $normalized['query'] = $sanitizedQuery;
        }

        return $normalized;
    }

    /**
     * @notes 归一化个人中心（page id=2）的 quick-entry / my-service 链接
     * @param mixed $data
     * @return mixed
     */
    public static function normalizeUserPageData($data)
    {
        $isJsonString = is_string($data);
        if ($isJsonString) {
            $decoded = json_decode($data, true);
            $list = is_array($decoded) ? $decoded : [];
        } elseif (is_array($data)) {
            $list = $data;
        } else {
            return $data;
        }

        foreach ($list as &$widget) {
            if (!is_array($widget)) {
                continue;
            }
            $name = (string)($widget['name'] ?? '');
            if (!in_array($name, ['quick-entry', 'my-service'], true)) {
                continue;
            }
            if (!isset($widget['content']['data']) || !is_array($widget['content']['data'])) {
                continue;
            }
            foreach ($widget['content']['data'] as &$item) {
                if (!is_array($item)) {
                    continue;
                }
                if (isset($item['link'])) {
                    $item['link'] = self::normalize($item['link']);
                }
            }
            unset($item);
        }
        unset($widget);

        if ($isJsonString) {
            $encoded = json_encode($list, JSON_UNESCAPED_UNICODE);
            return is_string($encoded) ? $encoded : '[]';
        }

        return $list;
    }

    /**
     * @param string $rawPath
     * @return array{0: string, 1: array<string, string>}
     */
    private static function mapLegacyPath(string $rawPath): array
    {
        [$path, $inlineQuery] = self::parseInlineQuery($rawPath);
        $mapped = self::LEGACY_PATH_MAP[$path] ?? null;
        if ($mapped === null) {
            return [$path, $inlineQuery];
        }
        [$mappedPath, $mappedInlineQuery] = self::parseInlineQuery($mapped);
        return [$mappedPath, array_merge($mappedInlineQuery, $inlineQuery)];
    }

    /**
     * @param string $rawPath
     * @return array{0: string, 1: array<string, string>}
     */
    private static function parseInlineQuery(string $rawPath): array
    {
        $pos = strpos($rawPath, '?');
        if ($pos === false) {
            return [$rawPath, []];
        }
        $path = trim(substr($rawPath, 0, $pos));
        $search = substr($rawPath, $pos + 1);
        $inline = [];
        parse_str($search, $parsed);
        if (is_array($parsed)) {
            foreach ($parsed as $k => $v) {
                $key = trim((string)$k);
                $val = self::cleanScalar($v, 500);
                if ($key !== '' && $val !== '') {
                    $inline[$key] = $val;
                }
            }
        }
        return [$path, $inline];
    }

    private static function mergeQuery(array $inlineQuery, array $rawQuery): array
    {
        $out = $inlineQuery;
        foreach ($rawQuery as $k => $v) {
            $key = trim((string)$k);
            if ($key === '') {
                continue;
            }
            $val = self::cleanScalar($v, 500);
            if ($val === '') {
                unset($out[$key]);
            } else {
                $out[$key] = $val;
            }
        }
        return $out;
    }

    private static function sanitizeShopQuery(string $path, array $rawQuery): array
    {
        $rules = self::ROUTE_PARAM_RULES[$path] ?? [];
        if ($rules === []) {
            return [];
        }

        $result = [];
        foreach ($rules as $key => $rule) {
            $kind = $rule[0];
            if ($kind === 'region') {
                $cityCode = self::cleanScalar($rawQuery['city_code'] ?? '', 20);
                $districtCode = self::cleanScalar($rawQuery['district_code'] ?? '', 20);
                if ($cityCode !== '' && $districtCode !== '') {
                    $pCode = self::cleanScalar($rawQuery['province_code'] ?? '', 20);
                    $pName = self::cleanScalar($rawQuery['province_name'] ?? '', 30);
                    $cName = self::cleanScalar($rawQuery['city_name'] ?? '', 30);
                    $dName = self::cleanScalar($rawQuery['district_name'] ?? '', 30);
                    if ($pCode !== '') {
                        $result['province_code'] = $pCode;
                    }
                    if ($pName !== '') {
                        $result['province_name'] = $pName;
                    }
                    $result['city_code'] = $cityCode;
                    if ($cName !== '') {
                        $result['city_name'] = $cName;
                    }
                    $result['district_code'] = $districtCode;
                    if ($dName !== '') {
                        $result['district_name'] = $dName;
                    }
                }
                continue;
            }

            if ($kind === 'pos_int') {
                $n = self::cleanPositiveInt($rawQuery[$key] ?? '');
                if ($n !== '') {
                    $result[$key] = $n;
                }
                continue;
            }

            if ($kind === 'category_name') {
                if (!empty($result['category_id'])) {
                    $cname = self::cleanScalar($rawQuery[$key] ?? '', (int)($rule[1] ?? 24));
                    if ($cname !== '') {
                        $result[$key] = $cname;
                    }
                }
                continue;
            }

            if ($kind === 'tag_ids') {
                $tags = self::cleanTagIds($rawQuery[$key] ?? '', (int)($rule[1] ?? 5));
                if ($tags !== '') {
                    $result[$key] = $tags;
                }
                continue;
            }

            if ($kind === 'date') {
                $d = self::cleanDate($rawQuery[$key] ?? '');
                if ($d !== '') {
                    $result[$key] = $d;
                }
                continue;
            }

            if ($kind === 'enum') {
                $val = self::cleanScalar($rawQuery[$key] ?? '', 30);
                $allowed = $rule[1] ?? [];
                if ($val !== '' && in_array($val, $allowed, true)) {
                    $result[$key] = $val;
                }
                continue;
            }

            if ($kind === 'text') {
                $txt = self::cleanScalar($rawQuery[$key] ?? '', (int)($rule[1] ?? 30));
                if ($txt !== '') {
                    $result[$key] = $txt;
                }
            }
        }

        return $result;
    }

    private static function cleanScalar($value, int $maxLen = 200): string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return '';
        }
        $text = trim((string)$value);
        if ($text === '') {
            return '';
        }
        return function_exists('mb_substr')
            ? mb_substr($text, 0, $maxLen, 'UTF-8')
            : substr($text, 0, $maxLen);
    }

    private static function cleanPositiveInt($value): string
    {
        $raw = self::cleanScalar($value, 20);
        return preg_match('/^[1-9]\d*$/', $raw) === 1 ? $raw : '';
    }

    private static function cleanDate($value): string
    {
        $raw = self::cleanScalar($value, 20);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1 ? $raw : '';
    }

    private static function cleanTagIds($value, int $maxCount = 5): string
    {
        $raw = self::cleanScalar($value, 120);
        if ($raw === '') {
            return '';
        }
        $seen = [];
        $out = [];
        foreach (explode(',', $raw) as $seg) {
            $id = self::cleanPositiveInt($seg);
            if ($id === '' || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $out[] = $id;
            if (count($out) >= $maxCount) {
                break;
            }
        }
        return implode(',', $out);
    }
}
