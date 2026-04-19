<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单确认函服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\order\Order;
use app\common\model\order\OrderConfirmLetter;
use app\common\model\order\OrderConfirmLetterPushLog;
use app\common\model\order\OrderItem;
use app\common\model\order\Payment;
use app\common\model\order\Refund;
use think\facade\Db;

class OrderConfirmLetterService
{
    public const RENDER_SPEC_VERSION = 'v2';
    public const LEGACY_RENDER_SPEC_VERSION = 'v1';
    public const CONFIG_GROUP = 'order_confirmation_letter';
    public const CONFIG_KEY_REMARK_TEMPLATE = 'remark_template';
    public const ERROR_TEMPLATE = 'REMARK_TEMPLATE_MISSING';
    public const ERROR_CONTACT_NAME = 'CONTACT_NAME_MISSING';
    public const ERROR_CONTACT_MOBILE = 'CONTACT_MOBILE_MISSING';
    public const ERROR_SERVICE_DATE = 'SERVICE_DATE_MISSING';
    public const ERROR_SERVICE_ADDRESS = 'SERVICE_ADDRESS_MISSING';
    public const ERROR_SERVICE_STAFF = 'SERVICE_STAFF_MISSING';
    public const ERROR_TOTAL_AMOUNT = 'ORDER_TOTAL_AMOUNT_MISSING';
    public const ERROR_NOT_PAYABLE = 'ORDER_NOT_PAYABLE_FOR_CONFIRM';
    public const ERROR_USER = 'ORDER_USER_MISSING';
    public const ERROR_STALE = 'SNAPSHOT_STALE';
    public const ERROR_ASSETS_MISSING = 'ASSETS_MISSING';
    protected const DEFAULT_BRAND_NAME = '喜遇婚礼服务';
    protected const DEFAULT_BRAND_TAGLINE = 'Wedding Service Confirmation';
    protected const DEFAULT_FOOTER_NOTE = '本确认函用于确认婚礼服务安排，请以最新生成版本为准并妥善保存。';

    public static function getTemplateConfig(): array
    {
        return [
            'remark_template' => (string) ConfigService::get(self::CONFIG_GROUP, self::CONFIG_KEY_REMARK_TEMPLATE, ''),
        ];
    }

    public static function setTemplateConfig(array $params): void
    {
        ConfigService::set(self::CONFIG_GROUP, self::CONFIG_KEY_REMARK_TEMPLATE, trim((string) ($params['remark_template'] ?? '')));
    }

    public static function getRenderData(int $orderId, ?int $staffId = null): array
    {
        $order = self::getOrderWithRelations($orderId);
        if (!$order) {
            return [
                'can_generate' => false,
                'reason' => 'ORDER_NOT_FOUND',
                'rendered_snapshot' => [],
                'render_spec_version' => self::RENDER_SPEC_VERSION,
                'snapshot_hash' => '',
            ];
        }

        $qualification = self::checkQualification($order);
        if ($qualification['error']) {
            return [
                'can_generate' => false,
                'reason' => $qualification['error'],
                'rendered_snapshot' => [],
                'render_spec_version' => self::RENDER_SPEC_VERSION,
                'snapshot_hash' => '',
            ];
        }

        $snapshot = self::buildSnapshot($order, $qualification);

        return [
            'can_generate' => true,
            'reason' => '',
            'rendered_snapshot' => $snapshot,
            'render_spec_version' => self::RENDER_SPEC_VERSION,
            'snapshot_hash' => self::buildSnapshotHash($snapshot),
        ];
    }

    public static function generate(int $orderId, string $generatedByType, int $generatedById, ?int $staffId = null): array
    {
        return Db::transaction(function () use ($orderId, $generatedByType, $generatedById, $staffId) {
            $order = self::getOrderWithRelations($orderId, true);
            if (!$order) {
                throw new \RuntimeException('订单不存在');
            }

            $qualification = self::checkQualification($order);
            if ($qualification['error']) {
                throw new \RuntimeException($qualification['error']);
            }

            $snapshot = self::buildSnapshot($order, $qualification);
            $snapshotHash = self::buildSnapshotHash($snapshot);

            $currentId = (int) ($order->current_confirm_letter_id ?? 0);
            if ($currentId > 0) {
                /** @var OrderConfirmLetter|null $current */
                $current = OrderConfirmLetter::where('id', $currentId)->lock(true)->find();
                if ($current && (string) $current->snapshot_hash === $snapshotHash) {
                    return [
                        'letter_id' => (int) $current->id,
                        'order_id' => (int) $order->id,
                        'version' => (int) $current->version,
                        'reused_current' => true,
                        'rendered_snapshot' => $current->rendered_snapshot,
                        'render_spec_version' => self::normalizeRenderSpecVersion((string) $current->render_spec_version),
                        'snapshot_hash' => (string) $current->snapshot_hash,
                    ];
                }
            }

            $version = (int) OrderConfirmLetter::where('order_id', (int) $order->id)->lock(true)->max('version');
            $version++;

            if ($currentId > 0) {
                OrderConfirmLetter::where('id', $currentId)->update([
                    'is_outdated' => OrderConfirmLetter::STATUS_OUTDATED,
                    'update_time' => time(),
                ]);
            }

            $letter = OrderConfirmLetter::create([
                'order_id' => (int) $order->id,
                'version' => $version,
                'is_outdated' => OrderConfirmLetter::STATUS_ACTIVE,
                'is_pushed' => 0,
                'rendered_snapshot' => $snapshot,
                'render_spec_version' => self::RENDER_SPEC_VERSION,
                'snapshot_hash' => $snapshotHash,
                'full_image_url' => '',
                'thumb_image_url' => '',
                'customer_name' => $snapshot['customer_name'],
                'contact_mobile' => $snapshot['contact_mobile'],
                'service_date' => $snapshot['service_date'],
                'service_address' => $snapshot['service_address'],
                'service_staff_names' => implode('、', $snapshot['service_staff_names']),
                'order_total_amount' => $snapshot['order_total_amount'],
                'paid_label' => $snapshot['paid_label'],
                'paid_amount' => $snapshot['paid_amount'],
                'remain_amount' => $snapshot['remain_amount'],
                'confirm_date' => $snapshot['confirm_date'],
                'remark_content' => $snapshot['remark_content'],
                'generated_by_type' => $generatedByType,
                'generated_by_id' => $generatedById,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            $order->current_confirm_letter_id = (int) $letter->id;
            $order->update_time = time();
            $order->save();

            return [
                'letter_id' => (int) $letter->id,
                'order_id' => (int) $order->id,
                'version' => $version,
                'reused_current' => false,
                'rendered_snapshot' => $snapshot,
                'render_spec_version' => self::RENDER_SPEC_VERSION,
                'snapshot_hash' => $snapshotHash,
            ];
        });
    }

    public static function saveAssets(
        int $letterId,
        string $snapshotHash,
        string $fullImageUrl,
        string $thumbImageUrl,
        string $svgContent = ''
    ): void
    {
        Db::transaction(function () use ($letterId, $snapshotHash, $fullImageUrl, $thumbImageUrl, $svgContent) {
            /** @var OrderConfirmLetter|null $letter */
            $letter = OrderConfirmLetter::where('id', $letterId)->lock(true)->find();
            if (!$letter) {
                throw new \RuntimeException('确认函不存在');
            }
            if ((string) $letter->snapshot_hash !== $snapshotHash) {
                throw new \RuntimeException(self::ERROR_STALE);
            }
            $normalizedFullImageUrl = self::normalizeStoredImageUrl($fullImageUrl);
            if ($normalizedFullImageUrl === '') {
                throw new \RuntimeException(self::ERROR_ASSETS_MISSING);
            }

            $normalizedThumbImageUrl = self::normalizeStoredImageUrl($thumbImageUrl);

            $letter->full_image_url = $normalizedFullImageUrl;
            $letter->thumb_image_url = $normalizedThumbImageUrl !== '' ? $normalizedThumbImageUrl : $normalizedFullImageUrl;
            $letter->update_time = time();
            $letter->save();
        });
    }

    public static function push(int $letterId, int $operatorId): array
    {
        return Db::transaction(function () use ($letterId, $operatorId) {
            /** @var OrderConfirmLetter|null $letter */
            $letter = OrderConfirmLetter::where('id', $letterId)->lock(true)->find();
            if (!$letter) {
                throw new \RuntimeException('确认函不存在');
            }
            /** @var Order|null $order */
            $order = Order::where('id', (int) $letter->order_id)->lock(true)->find();
            if (!$order) {
                throw new \RuntimeException('订单不存在');
            }
            if ((int) ($order->current_confirm_letter_id ?? 0) !== (int) $letter->id || (int) $letter->is_outdated === OrderConfirmLetter::STATUS_OUTDATED) {
                throw new \RuntimeException('当前版本已失效，请重新生成确认函');
            }
            if (!self::hasSavedAssets($letter)) {
                throw new \RuntimeException(self::ERROR_ASSETS_MISSING);
            }
            if (self::calculateEffectivePaidAmount((int) $order->id) <= 0) {
                throw new \RuntimeException(self::ERROR_NOT_PAYABLE);
            }
            if ((int) $order->user_id <= 0) {
                throw new \RuntimeException(self::ERROR_USER);
            }

            $pushLog = OrderConfirmLetterPushLog::create([
                'letter_id' => (int) $letter->id,
                'order_id' => (int) $order->id,
                'user_id' => (int) $order->user_id,
                'push_channel' => 'station',
                'notification_id' => 0,
                'push_status' => OrderConfirmLetterPushLog::STATUS_SUCCESS,
                'error_msg' => '',
                'create_time' => time(),
            ]);

            StationNotificationService::send(
                (int) $order->user_id,
                0,
                '订单确认函已生成',
                '请及时查看您的订单确认函',
                StationNotificationService::TARGET_CONFIRM_LETTER_ORDER,
                (int) $order->id,
                $operatorId
            );

            $letter->is_pushed = 1;
            $letter->update_time = time();
            $letter->save();

            return [
                'push_log_id' => (int) $pushLog->id,
                'letter_id' => (int) $letter->id,
                'pushed_at' => date('Y-m-d H:i:s'),
            ];
        });
    }

    public static function currentForUser(int $orderId, int $userId): ?array
    {
        $order = Order::where('id', $orderId)->where('user_id', $userId)->find();
        if (!$order) {
            return null;
        }

        $letter = self::resolveCurrentEffectiveLetter($order);
        return $letter ? self::formatLetter($letter) : null;
    }

    public static function byIdForUser(int $letterId, int $userId, bool $allowFallback = false): ?array
    {
        $letter = OrderConfirmLetter::where('id', $letterId)->find();
        if (!$letter) {
            return null;
        }

        $order = Order::where('id', (int) $letter->order_id)->where('user_id', $userId)->find();
        if (!$order) {
            return null;
        }

        $currentLetter = self::resolveCurrentEffectiveLetter($order);
        if ($currentLetter && (int) $currentLetter->id === (int) $letter->id) {
            return self::formatLetter($currentLetter);
        }

        if ($currentLetter) {
            return self::buildLetterPayload($currentLetter, [
                'requested_letter_id' => (int) $letter->id,
                'stale_fallback' => 1,
                'fallback_reason' => 'CURRENT_EFFECTIVE_VERSION',
            ]);
        }

        return null;
    }

    public static function historyForUser(int $orderId, int $userId): array
    {
        $order = Order::where('id', $orderId)->where('user_id', $userId)->find();
        if (!$order || self::calculateEffectivePaidAmount($orderId) <= 0) {
            return [];
        }

        $currentLetterId = (int)($order->current_confirm_letter_id ?? 0);

        return OrderConfirmLetter::where('order_id', $orderId)
            ->order('version', 'desc')
            ->select()
            ->map(function (OrderConfirmLetter $letter) use ($currentLetterId) {
                $isCurrent = (int)$letter->id === $currentLetterId
                    && (int)$letter->is_outdated === OrderConfirmLetter::STATUS_ACTIVE;

                return [
                    'letter_id' => (int)$letter->id,
                    'order_id' => (int)$letter->order_id,
                    'version' => (int)$letter->version,
                    'confirm_date' => (string)$letter->confirm_date,
                    'is_current' => $isCurrent ? 1 : 0,
                    'is_outdated' => (int)$letter->is_outdated,
                    'is_pushed' => (int)$letter->is_pushed,
                    'render_spec_version' => self::normalizeRenderSpecVersion((string) $letter->render_spec_version),
                    'snapshot_hash' => (string) $letter->snapshot_hash,
                    'full_image_url' => self::formatPublicImageUrl((string) $letter->full_image_url),
                    'thumb_image_url' => self::formatPublicImageUrl((string) $letter->thumb_image_url),
                    'can_view' => ($isCurrent && (int)$letter->is_pushed === 1) ? 1 : 0,
                ];
            })
            ->toArray();
    }

    public static function detailForOrder(int $letterId, int $orderId): ?array
    {
        $letter = OrderConfirmLetter::where('id', $letterId)->where('order_id', $orderId)->find();
        if (!$letter) {
            return null;
        }
        return self::formatLetter($letter);
    }

    public static function history(int $orderId): array
    {
        return OrderConfirmLetter::where('order_id', $orderId)
            ->order('version', 'desc')
            ->select()
            ->map(function (OrderConfirmLetter $letter) {
                return [
                    'letter_id' => (int) $letter->id,
                    'order_id' => (int) $letter->order_id,
                    'version' => (int) $letter->version,
                    'confirm_date' => (string) $letter->confirm_date,
                    'is_current' => (int) $letter->is_outdated === OrderConfirmLetter::STATUS_ACTIVE ? 1 : 0,
                    'is_outdated' => (int) $letter->is_outdated,
                    'is_pushed' => (int) $letter->is_pushed,
                    'render_spec_version' => self::normalizeRenderSpecVersion((string) $letter->render_spec_version),
                    'snapshot_hash' => (string) $letter->snapshot_hash,
                    'full_image_url' => self::formatPublicImageUrl((string) $letter->full_image_url),
                    'thumb_image_url' => self::formatPublicImageUrl((string) $letter->thumb_image_url),
                ];
            })
            ->toArray();
    }

    public static function markOutdatedByOrderId(int $orderId): void
    {
        Db::transaction(function () use ($orderId) {
            $order = Order::where('id', $orderId)->lock(true)->find();
            if (!$order) {
                return;
            }
            self::invalidateCurrentLetter($order);
        });
    }

    public static function invalidateCurrentLetter(Order $order, bool $persist = true): void
    {
        $currentId = (int) ($order->current_confirm_letter_id ?? 0);
        if ($currentId <= 0) {
            return;
        }

        OrderConfirmLetter::where('id', $currentId)->update([
            'is_outdated' => OrderConfirmLetter::STATUS_OUTDATED,
            'update_time' => time(),
        ]);

        $order->current_confirm_letter_id = 0;
        if ($persist) {
            $order->update_time = time();
            $order->save();
        }
    }

    public static function normalizeErrorMessage(string $message): string
    {
        return match (trim($message)) {
            self::ERROR_TEMPLATE => '请先在系统设置中填写订单确认函备注模板',
            self::ERROR_CONTACT_NAME => '订单缺少联系人姓名，暂时无法生成确认函',
            self::ERROR_CONTACT_MOBILE => '订单缺少联系电话，暂时无法生成确认函',
            self::ERROR_SERVICE_DATE => '订单缺少服务日期，暂时无法生成确认函',
            self::ERROR_SERVICE_ADDRESS => '订单缺少服务地址，暂时无法生成确认函',
            self::ERROR_SERVICE_STAFF => '订单缺少服务人员信息，暂时无法生成确认函',
            self::ERROR_TOTAL_AMOUNT => '订单金额异常，暂时无法生成确认函',
            self::ERROR_NOT_PAYABLE => '订单尚未产生有效付款，暂时不能生成或推送确认函',
            self::ERROR_USER => '订单未绑定顾客账号，暂时无法推送确认函',
            self::ERROR_STALE => '确认函内容已发生变化，请重新生成后再保存',
            self::ERROR_ASSETS_MISSING => '请先完成确认函图片保存后再推送或查看',
            default => $message,
        };
    }

    public static function calculateEffectivePaidAmount(int $orderId): float
    {
        $paid = (float) Payment::where('order_id', $orderId)
            ->where('pay_status', Payment::STATUS_PAID)
            ->sum('pay_amount');
        $refunded = (float) Refund::where('order_id', $orderId)
            ->where('refund_status', Refund::STATUS_COMPLETED)
            ->sum('refund_amount');
        return round(max($paid - $refunded, 0), 2);
    }

    protected static function getOrderWithRelations(int $orderId, bool $lock = false): ?Order
    {
        $query = Order::with(['items']);
        if ($lock) {
            $query->lock(true);
        }
        /** @var Order|null $order */
        $order = $query->find($orderId);
        return $order;
    }

    protected static function checkQualification(Order $order): array
    {
        $remarkTemplate = trim((string) ConfigService::get(self::CONFIG_GROUP, self::CONFIG_KEY_REMARK_TEMPLATE, ''));
        if ($remarkTemplate === '') {
            return ['error' => self::ERROR_TEMPLATE];
        }
        if (trim((string) $order->contact_name) === '') {
            return ['error' => self::ERROR_CONTACT_NAME];
        }
        if (trim((string) $order->contact_mobile) === '') {
            return ['error' => self::ERROR_CONTACT_MOBILE];
        }
        if (trim((string) $order->service_date) === '') {
            return ['error' => self::ERROR_SERVICE_DATE];
        }
        if (trim((string) $order->service_address) === '') {
            return ['error' => self::ERROR_SERVICE_ADDRESS];
        }
        if ((float) $order->total_amount <= 0) {
            return ['error' => self::ERROR_TOTAL_AMOUNT];
        }
        $paidAmount = self::calculateEffectivePaidAmount((int) $order->id);
        if ($paidAmount <= 0) {
            return ['error' => self::ERROR_NOT_PAYABLE];
        }
        $staffNames = self::resolveServiceStaffNames($order);
        if (empty($staffNames)) {
            return ['error' => self::ERROR_SERVICE_STAFF];
        }

        return [
            'error' => '',
            'remark_template' => $remarkTemplate,
            'paid_amount' => $paidAmount,
            'staff_names' => $staffNames,
            'service_team_lines' => self::resolveServiceTeamLines($order),
        ];
    }

    protected static function buildSnapshot(Order $order, array $qualification): array
    {
        $totalAmount = round((float) $order->total_amount, 2);
        $paidAmount = round((float) $qualification['paid_amount'], 2);
        $remainAmount = round(max($totalAmount - $paidAmount, 0), 2);
        $paidLabel = $paidAmount >= $totalAmount ? '已付全款' : '已付定金';
        $serviceDate = self::normalizeServiceDate((string) $order->service_date);
        return [
            'title' => '订单确认函',
            'order_sn' => trim((string) ($order->order_sn ?? '')),
            'customer_name' => trim((string) $order->contact_name),
            'service_date' => $serviceDate,
            'service_date_label' => self::buildServiceDateLabel($serviceDate),
            'service_address' => trim((string) $order->service_address),
            'service_team_lines' => array_values($qualification['service_team_lines'] ?? []),
            'service_staff_names' => array_values($qualification['staff_names']),
            'order_total_amount' => number_format($totalAmount, 2, '.', ''),
            'paid_label' => $paidLabel,
            'paid_amount' => number_format($paidAmount, 2, '.', ''),
            'remain_amount' => number_format($remainAmount, 2, '.', ''),
            'confirm_date' => date('Y-m-d'),
            'contact_mobile' => trim((string) $order->contact_mobile),
            'remark_content' => trim((string) $qualification['remark_template']),
            'brand_name' => self::DEFAULT_BRAND_NAME,
            'brand_tagline' => self::DEFAULT_BRAND_TAGLINE,
            'footer_note' => self::DEFAULT_FOOTER_NOTE,
        ];
    }

    protected static function buildSnapshotHash(array $snapshot): string
    {
        return hash('sha256', self::RENDER_SPEC_VERSION . '|' . json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected static function resolveServiceStaffNames(Order $order): array
    {
        $names = [];
        foreach ($order->items as $item) {
            if (!in_array((int) $item->item_type, [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF], true)) {
                continue;
            }
            if ((int) $item->item_status === OrderItem::STATUS_CANCELLED) {
                continue;
            }
            $name = trim((string) ($item->staff_name ?? ''));
            if ($name === '' || in_array($name, $names, true)) {
                continue;
            }
            $names[] = $name;
        }
        return $names;
    }

    protected static function resolveServiceTeamLines(Order $order): array
    {
        $lines = [];
        foreach ($order->items as $item) {
            if (!in_array((int) $item->item_type, [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF], true)) {
                continue;
            }
            if ((int) $item->item_status === OrderItem::STATUS_CANCELLED) {
                continue;
            }

            $staffName = trim((string) ($item->staff_name ?? ''));
            if ($staffName === '') {
                continue;
            }

            $itemMeta = is_array($item->item_meta ?? null) ? $item->item_meta : [];
            $roleLabel = trim((string) ($itemMeta['role_label'] ?? ''));
            $packageName = trim((string) ($item->package_name ?? ''));
            $lineLabel = $roleLabel !== ''
                ? $roleLabel
                : ($packageName !== ''
                    ? $packageName
                    : ((int) $item->item_type === OrderItem::TYPE_RELATED_STAFF ? '协作服务' : '主服务'));

            $line = $lineLabel . '：' . $staffName;
            if (in_array($line, $lines, true)) {
                continue;
            }
            $lines[] = $line;
        }

        return $lines;
    }

    protected static function normalizeServiceDate(string $serviceDate): string
    {
        $timestamp = strtotime($serviceDate);
        if ($timestamp === false) {
            return trim($serviceDate);
        }

        return date('Y-m-d', $timestamp);
    }

    protected static function buildServiceDateLabel(string $serviceDate): string
    {
        $timestamp = strtotime($serviceDate);
        if ($timestamp === false) {
            return $serviceDate;
        }

        $weekdays = ['日', '一', '二', '三', '四', '五', '六'];
        return date('Y年m月d日', $timestamp) . ' 星期' . $weekdays[(int) date('w', $timestamp)];
    }

    protected static function formatLetter(OrderConfirmLetter $letter): array
    {
        return self::buildLetterPayload($letter, []);
    }

    protected static function buildLetterPayload(OrderConfirmLetter $letter, array $extra): array
    {
        $payload = [
            'letter_id' => (int) $letter->id,
            'order_id' => (int) $letter->order_id,
            'version' => (int) $letter->version,
            'is_current' => (int) $letter->is_outdated === OrderConfirmLetter::STATUS_ACTIVE ? 1 : 0,
            'is_outdated' => (int) $letter->is_outdated,
            'is_pushed' => (int) $letter->is_pushed,
            'render_spec_version' => self::normalizeRenderSpecVersion((string) $letter->render_spec_version),
            'snapshot_hash' => (string) $letter->snapshot_hash,
            'full_image_url' => self::formatPublicImageUrl((string) $letter->full_image_url),
            'thumb_image_url' => self::formatPublicImageUrl((string) $letter->thumb_image_url),
            'rendered_snapshot' => is_array($letter->rendered_snapshot) ? $letter->rendered_snapshot : [],
        ];

        return array_merge($payload, $extra);
    }

    protected static function resolveCurrentEffectiveLetter(Order $order): ?OrderConfirmLetter
    {
        $currentLetterId = (int) ($order->current_confirm_letter_id ?? 0);
        if ($currentLetterId <= 0 || self::calculateEffectivePaidAmount((int) $order->id) <= 0) {
            return null;
        }

        /** @var OrderConfirmLetter|null $letter */
        $letter = OrderConfirmLetter::where('id', $currentLetterId)->find();
        if (!$letter) {
            return null;
        }

        if ((int) $letter->is_outdated === OrderConfirmLetter::STATUS_OUTDATED || (int) $letter->is_pushed !== 1) {
            return null;
        }

        return $letter;
    }

    protected static function normalizeStoredImageUrl(string $url): string
    {
        $normalized = trim($url);
        if ($normalized === '') {
            return '';
        }

        return trim((string) FileService::setFileUrl($normalized));
    }

    protected static function formatPublicImageUrl(string $url): string
    {
        $normalized = trim($url);
        if ($normalized === '') {
            return '';
        }

        return FileService::getFileUrl($normalized);
    }

    protected static function normalizeRenderSpecVersion(string $renderSpecVersion): string
    {
        $normalized = trim($renderSpecVersion);
        return $normalized !== '' ? $normalized : self::LEGACY_RENDER_SPEC_VERSION;
    }

    protected static function hasSavedAssets(OrderConfirmLetter $letter): bool
    {
        return trim((string) $letter->full_image_url) !== '';
    }
}
