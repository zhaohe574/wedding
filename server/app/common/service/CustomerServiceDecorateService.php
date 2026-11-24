<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 客服设置页装修默认数据与归一化
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * 客服设置页装修数据契约（服务端镜像）。
 *
 * ⚠ 字段定义与 `shared/contracts/decorate.ts` 的 `CustomerServiceDecorateContent` 一一对应，
 *   改字段时两边必须一起改，`npm run decorate:check` 会校验字段对齐。
 *   小程序端通过 `GET /api/index/decorate?id=3` 获取本页配置。
 */
class CustomerServiceDecorateService
{
    public const PAGE_ID = 3;
    public const PAGE_TYPE = 3;
    public const PAGE_NAME = '客服设置';
    public const WIDGET_NAME = 'customer-service';

    public const ALLOWED_ICONS = ['check', 'pay', 'clock', 'like', 'star', 'notice'];

    /**
     * @notes 默认客服设置装修页
     * @return array
     */
    public static function defaultPage(): array
    {
        return [
            'id' => self::PAGE_ID,
            'type' => self::PAGE_TYPE,
            'name' => self::PAGE_NAME,
            'data' => [self::defaultWidget()],
            'meta' => '',
        ];
    }

    /**
     * @notes 默认客服设置组件
     * @return array
     */
    public static function defaultWidget(): array
    {
        return [
            'id' => 'customer_service_default',
            'title' => self::PAGE_NAME,
            'name' => self::WIDGET_NAME,
            'content' => [
                'enabled' => 1,
                'eyebrow' => 'CONCIERGE',
                'button_text' => '微信在线咨询',
                'phone_button_text' => '电话专线',
                'tips' => '排期查询、履约协助与售后处理，直接在此联系我们。',
                'show_quick_bar' => 1,
                'show_assurance' => 1,
                'assurance_title' => '服务保障',
                'assurance_subtitle' => '全程守护备婚与礼成',
                'assurances' => [
                    ['icon' => 'check', 'title' => '实名甄选', 'desc' => '服务人员严格认证'],
                    ['icon' => 'pay', 'title' => '按节点结算', 'desc' => '验收确认后付尾款'],
                    ['icon' => 'clock', 'title' => '快速响应', 'desc' => '时段内 2 小时跟进'],
                    ['icon' => 'like', 'title' => '全程跟进', 'desc' => '排期履约闭环协调'],
                ],
                'show_faq' => 1,
                'faq_title' => '常见问题',
                'faq_subtitle' => '点击展开指引',
                'faqs' => [
                    [
                        'q' => '遇到档期调整或时间变更怎么处理',
                        'a' => '在订单详情进入「订单售后」，提交改期申请并选期望的新档期。顾问会在 24 小时内与你确认档期与差价；审核期间原档期仍然保留，不会二次售出。',
                    ],
                    [
                        'q' => '服务过程中有分歧或不满意怎么办',
                        'a' => '直接联系专属客服管家，或在订单售后里提交诉求。平台会跟进到闭环，处理结果通过服务号通知。',
                    ],
                    [
                        'q' => '怎么查服务合同与尾款结算',
                        'a' => '订单详情里可以查看合同与每次支付的记录；尾款开放支付时会通过服务号提醒，金额以服务端账单为准。',
                    ],
                ],
                'footer_text' => '客服管家由平台统一安排 · 非服务时段留言将在下一时段优先回复',
            ],
            'styles' => [],
        ];
    }

    /**
     * @notes 归一化客服设置装修页
     * @param array $pageData
     * @return array
     */
    public static function normalizePage(array $pageData): array
    {
        $defaultPage = self::defaultPage();
        $pageData = array_replace($defaultPage, $pageData);

        $isJsonString = is_string($pageData['data'] ?? null);
        $data = self::decodeData($pageData['data'] ?? []);
        $widget = self::firstServiceWidget($data);

        $normalizedData = [self::normalizeWidget($widget)];
        $pageData['id'] = (int)($pageData['id'] ?? self::PAGE_ID);
        $pageData['type'] = self::PAGE_TYPE;
        $pageData['name'] = (string)(($pageData['name'] ?? '') ?: self::PAGE_NAME);
        $pageData['data'] = $isJsonString
            ? self::encodeData($normalizedData)
            : $normalizedData;
        $pageData['meta'] = $pageData['meta'] ?? '';

        return $pageData;
    }

    /**
     * @notes 归一化后转为数据库可保存的 JSON 字符串
     * @param mixed $data
     * @return string
     */
    public static function normalizeDataForSave($data): string
    {
        $pageData = self::normalizePage([
            'id' => self::PAGE_ID,
            'type' => self::PAGE_TYPE,
            'name' => self::PAGE_NAME,
            'data' => $data,
            'meta' => '',
        ]);

        if (is_string($pageData['data'])) {
            return $pageData['data'];
        }

        return self::encodeData($pageData['data']);
    }

    /**
     * @notes 归一化组件外层结构
     * @param mixed $widget
     * @return array
     */
    public static function normalizeWidget($widget): array
    {
        $widget = is_array($widget) ? $widget : [];
        $content = is_array($widget['content'] ?? null) ? $widget['content'] : [];

        return [
            'id' => self::cleanString($widget['id'] ?? null) ?: 'customer_service_default',
            'title' => self::PAGE_NAME,
            'name' => self::WIDGET_NAME,
            'content' => self::normalizeContent($content),
            'styles' => [],
        ];
    }

    /**
     * @notes 归一化组件内容（与 shared/contracts/decorate.ts 保持一致）
     * @param mixed $content
     * @return array
     */
    public static function normalizeContent($content): array
    {
        $source = is_array($content) ? $content : [];
        $defaults = self::defaultWidget()['content'];

        $buttonText = self::cleanString($source['button_text'] ?? ($source['buttonText'] ?? ''));

        return [
            'enabled' => self::toInt01($source['enabled'] ?? null, (int)$defaults['enabled']),
            'eyebrow' => array_key_exists('eyebrow', $source) && is_string($source['eyebrow'])
                ? trim($source['eyebrow'])
                : (string)$defaults['eyebrow'],
            'button_text' => $buttonText !== '' ? $buttonText : (string)$defaults['button_text'],
            'phone_button_text' => self::cleanString($source['phone_button_text'] ?? '') ?: (string)$defaults['phone_button_text'],
            'tips' => self::cleanString($source['tips'] ?? '') ?: (string)$defaults['tips'],
            'show_quick_bar' => self::toInt01($source['show_quick_bar'] ?? null, (int)$defaults['show_quick_bar']),
            'show_assurance' => self::toInt01($source['show_assurance'] ?? null, (int)$defaults['show_assurance']),
            'assurance_title' => self::cleanString($source['assurance_title'] ?? '') ?: (string)$defaults['assurance_title'],
            'assurance_subtitle' => array_key_exists('assurance_subtitle', $source) && is_string($source['assurance_subtitle'])
                ? trim($source['assurance_subtitle'])
                : (string)$defaults['assurance_subtitle'],
            'assurances' => is_array($source['assurances'] ?? null)
                ? self::normalizeAssurances($source['assurances'])
                : $defaults['assurances'],
            'show_faq' => self::toInt01($source['show_faq'] ?? null, (int)$defaults['show_faq']),
            'faq_title' => self::cleanString($source['faq_title'] ?? '') ?: (string)$defaults['faq_title'],
            'faq_subtitle' => array_key_exists('faq_subtitle', $source) && is_string($source['faq_subtitle'])
                ? trim($source['faq_subtitle'])
                : (string)$defaults['faq_subtitle'],
            'faqs' => is_array($source['faqs'] ?? null)
                ? self::normalizeFaqs($source['faqs'])
                : $defaults['faqs'],
            'footer_text' => array_key_exists('footer_text', $source) && is_string($source['footer_text'])
                ? trim($source['footer_text'])
                : (string)$defaults['footer_text'],
        ];
    }

    /**
     * @param array $items
     * @return array
     */
    private static function normalizeAssurances(array $items): array
    {
        $list = [];
        foreach (array_slice(array_values($items), 0, 6) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $icon = self::cleanString($item['icon'] ?? '');
            if (!in_array($icon, self::ALLOWED_ICONS, true)) {
                $icon = 'check';
            }
            $list[] = [
                'icon' => $icon,
                'title' => self::cleanString($item['title'] ?? ''),
                'desc' => self::cleanString($item['desc'] ?? ''),
            ];
        }
        return $list;
    }

    /**
     * @param array $items
     * @return array
     */
    private static function normalizeFaqs(array $items): array
    {
        $list = [];
        foreach (array_slice(array_values($items), 0, 10) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $list[] = [
                'q' => self::cleanString($item['q'] ?? ''),
                'a' => self::cleanString($item['a'] ?? ''),
            ];
        }
        return $list;
    }

    /**
     * @param mixed $data
     * @return array
     */
    private static function decodeData($data): array
    {
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($data)) {
            return [];
        }

        if (self::isListLike($data)) {
            return array_values($data);
        }

        if (($data['name'] ?? '') === self::WIDGET_NAME) {
            return [$data];
        }

        return [];
    }

    /**
     * @param array $data
     * @return array
     */
    private static function firstServiceWidget(array $data): array
    {
        foreach ($data as $widget) {
            if (is_array($widget) && (string)($widget['name'] ?? '') === self::WIDGET_NAME) {
                return $widget;
            }
        }

        return [];
    }

    /**
     * @param mixed $data
     * @return string
     */
    private static function encodeData($data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        return is_string($json) ? $json : '[]';
    }

    /**
     * @param mixed $value
     * @param int $fallback
     * @return int
     */
    private static function toInt01($value, int $fallback): int
    {
        if ($value === null || $value === '') {
            return $fallback === 1 ? 1 : 0;
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        return (int)$value === 1 ? 1 : 0;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function cleanString($value): string
    {
        return is_string($value) || is_numeric($value) ? trim((string)$value) : '';
    }

    /**
     * @param array $data
     * @return bool
     */
    private static function isListLike(array $data): bool
    {
        if ($data === []) {
            return true;
        }

        foreach (array_keys($data) as $key) {
            if (is_int($key) || (is_string($key) && ctype_digit($key))) {
                continue;
            }

            return false;
        }

        return true;
    }
}
