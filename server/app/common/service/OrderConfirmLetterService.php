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
    public const RENDER_SPEC_VERSION = 'v3';
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
    public const ERROR_ASSET_RUNTIME = 'ASSET_RUNTIME_UNAVAILABLE';
    public const ERROR_ASSET_FONT = 'ASSET_FONT_MISSING';
    public const ERROR_ASSET_RENDER = 'ASSET_RENDER_FAILED';
    protected const ASSET_FONTS = [
        [
            'family' => 'Noto Sans SC',
            'query' => '*Noto*Sans*SC*',
            'file' => 'NotoSansSC-VF.ttf',
        ],
        [
            'family' => 'Noto Serif SC',
            'query' => '*Noto*Serif*SC*',
            'file' => 'NotoSerifSC-wght.ttf',
        ],
    ];
    protected const ASSET_FONT_DIR = 'app/common/resource/fonts';
    protected const ASSET_PNG_RESOLUTION = 144;
    protected const DEFAULT_BRAND_NAME = '喜遇婚礼服务';
    protected const DEFAULT_BRAND_TAGLINE = 'MAISON DE MARIAGE · CONFIRMATION';
    protected const DEFAULT_FOOTER_NOTE = '请保存此确认函图片，作为婚礼服务安排与付款确认的纸本凭证。';

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
                    self::ensurePersistedAssets($current, true);
                    return self::buildLetterPayload($current, [
                        'reused_current' => true,
                    ]);
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

            self::ensurePersistedAssets($letter, true);

            return self::buildLetterPayload($letter, [
                'reused_current' => false,
            ]);
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
        self::regenerateAssets($letterId, $snapshotHash, true);
    }

    public static function regenerateAssets(int $letterId, string $snapshotHash = '', bool $force = true): array
    {
        return Db::transaction(function () use ($letterId, $snapshotHash, $force) {
            /** @var OrderConfirmLetter|null $letter */
            $letter = OrderConfirmLetter::where('id', $letterId)->lock(true)->find();
            if (!$letter) {
                throw new \RuntimeException('确认函不存在');
            }

            if ($snapshotHash !== '' && (string) $letter->snapshot_hash !== $snapshotHash) {
                throw new \RuntimeException(self::ERROR_STALE);
            }

            self::ensurePersistedAssets($letter, $force);

            return [
                'letter_id' => (int) $letter->id,
                'assets_saved' => true,
            ];
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
            self::ensurePersistedAssets($letter, true);
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
        if (!$letter) {
            return null;
        }

        self::ensurePersistedAssets($letter);
        return self::formatLetter($letter);
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
            self::ensurePersistedAssets($currentLetter);
            return self::formatLetter($currentLetter);
        }

        if ($allowFallback && $currentLetter) {
            self::ensurePersistedAssets($currentLetter);
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
        self::ensurePersistedAssets($letter);
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
            self::ERROR_ASSET_RUNTIME => '确认函图片生成环境缺少 Imagick 支持，请联系管理员处理',
            self::ERROR_ASSET_FONT => '确认函图片字体资源缺失，请联系管理员处理',
            self::ERROR_ASSET_RENDER => '确认函图片生成失败，请稍后重试',
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

        if (preg_match('/^https?:\/\//i', $normalized) !== 1) {
            return ltrim($normalized, '/');
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
        return self::hasPersistedAssets($letter);
    }

    protected static function ensurePersistedAssets(OrderConfirmLetter $letter, bool $force = false): void
    {
        if (!$force && self::hasPersistedAssets($letter)) {
            return;
        }

        $svgContent = self::renderSvgForLetter($letter);
        $persistedAssets = self::persistSvgAssets(
            (int) $letter->order_id,
            (string) $letter->snapshot_hash,
            $svgContent
        );

        $normalizedFullImageUrl = self::normalizeStoredImageUrl((string) ($persistedAssets['full_image_url'] ?? ''));
        if ($normalizedFullImageUrl === '') {
            throw new \RuntimeException(self::ERROR_ASSET_RENDER);
        }

        $normalizedThumbImageUrl = self::normalizeStoredImageUrl((string) ($persistedAssets['thumb_image_url'] ?? ''));
        $letter->full_image_url = $normalizedFullImageUrl;
        $letter->thumb_image_url = $normalizedThumbImageUrl !== '' ? $normalizedThumbImageUrl : $normalizedFullImageUrl;
        $letter->update_time = time();
        $letter->save();
    }

    protected static function renderSvgForLetter(OrderConfirmLetter $letter): string
    {
        $snapshot = is_array($letter->rendered_snapshot) ? $letter->rendered_snapshot : [];
        if (empty($snapshot)) {
            throw new \RuntimeException(self::ERROR_ASSET_RENDER);
        }

        $svgContent = OrderConfirmLetterRenderer::render($snapshot, [
            'render_spec_version' => self::normalizeRenderSpecVersion((string) $letter->render_spec_version),
        ]);
        if (trim($svgContent) === '') {
            throw new \RuntimeException(self::ERROR_ASSET_RENDER);
        }

        return $svgContent;
    }

    protected static function resolveCurrentViewableLetter(Order $order): ?OrderConfirmLetter
    {
        if ((int) ($order->current_confirm_letter_id ?? 0) <= 0) {
            return null;
        }

        /** @var OrderConfirmLetter|null $letter */
        $letter = OrderConfirmLetter::where('id', (int) $order->current_confirm_letter_id)->find();
        if (!$letter || !self::canUserViewLetter($order, $letter)) {
            return null;
        }

        return $letter;
    }

    protected static function canUserViewLetter(Order $order, OrderConfirmLetter $letter): bool
    {
        return (int) $letter->is_pushed === 1
            && (int) ($order->current_confirm_letter_id ?? 0) === (int) $letter->id
            && (int) $letter->is_outdated === OrderConfirmLetter::STATUS_ACTIVE
            && self::calculateEffectivePaidAmount((int) $order->id) > 0;
    }

    protected static function hasPersistedAssets(OrderConfirmLetter $letter): bool
    {
        $storedPath = self::normalizeStoredImageUrl((string) $letter->full_image_url);
        if ($storedPath === '') {
            return false;
        }

        if (preg_match('/^https?:\/\//i', $storedPath) === 1) {
            return true;
        }

        $absolutePath = self::resolveStoredAssetAbsolutePath($storedPath);
        return $absolutePath !== '' && is_file($absolutePath);
    }

    protected static function normalizeAssetUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        return self::normalizeStoredImageUrl($url);
    }

    protected static function formatAssetUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        return FileService::getFileUrl($url);
    }

    protected static function resolveStoredAssetAbsolutePath(string $storedPath): string
    {
        $normalized = trim($storedPath);
        if ($normalized === '' || preg_match('/^https?:\/\//i', $normalized) === 1) {
            return '';
        }

        return FileService::getFileUrl(ltrim($normalized, '/'), 'public_path');
    }

    protected static function persistSvgAssets(int $orderId, string $snapshotHash, string $svgContent): array
    {
        $svgContent = trim($svgContent);
        if ($svgContent === '' || stripos($svgContent, '<svg') === false) {
            throw new \RuntimeException(self::ERROR_ASSETS_MISSING);
        }

        $folder = 'uploads/order-confirm-letter/' . date('Ym');
        $hash = preg_replace('/[^a-z0-9]/i', '', $snapshotHash);
        $hash = $hash !== '' ? substr($hash, 0, 24) : substr(md5($svgContent), 0, 24);
        $relativePath = sprintf('%s/order-%d-%s.png', $folder, $orderId, $hash);
        $absolutePath = FileService::getFileUrl($relativePath, 'public_path');
        $directory = dirname($absolutePath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('确认函图片目录创建失败');
        }
        self::rasterizeSvgAssets($svgContent, $absolutePath);

        return [
            'full_image_url' => $relativePath,
            'thumb_image_url' => $relativePath,
        ];
    }

    protected static function rasterizeSvgAssets(string $svgContent, string $absolutePath): void
    {
        if (!extension_loaded('imagick') || !class_exists(\Imagick::class)) {
            throw new \RuntimeException(self::ERROR_ASSET_RUNTIME);
        }

        $fontDirectory = self::resolveAssetFontDirectory();
        $previousFontPath = getenv('MAGICK_FONT_PATH');
        $imagick = new \Imagick();

        try {
            putenv('MAGICK_FONT_PATH=' . $fontDirectory);
            self::ensureImagickFontReady();
            $imagick->setResolution(self::ASSET_PNG_RESOLUTION, self::ASSET_PNG_RESOLUTION);
            $imagick->setBackgroundColor(new \ImagickPixel('transparent'));
            $imagick->readImageBlob($svgContent);
            $imagick->setImageFormat('png');
            if (!$imagick->writeImage($absolutePath)) {
                throw new \RuntimeException(self::ERROR_ASSET_RENDER);
            }
        } catch (\Throwable $e) {
            @unlink($absolutePath);
            if ($e instanceof \RuntimeException && in_array($e->getMessage(), [
                self::ERROR_ASSET_RUNTIME,
                self::ERROR_ASSET_FONT,
                self::ERROR_ASSET_RENDER,
            ], true)) {
                throw $e;
            }

            throw new \RuntimeException(self::ERROR_ASSET_RENDER, 0, $e);
        } finally {
            $imagick->clear();
            $imagick->destroy();
            if ($previousFontPath === false || $previousFontPath === '') {
                putenv('MAGICK_FONT_PATH');
            } else {
                putenv('MAGICK_FONT_PATH=' . $previousFontPath);
            }
        }
    }

    protected static function resolveAssetFontDirectory(): string
    {
        $fontDirectory = rtrim((string) root_path(), '/\\')
            . DIRECTORY_SEPARATOR
            . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, self::ASSET_FONT_DIR);
        foreach (self::ASSET_FONTS as $font) {
            $fontFilePath = $fontDirectory . DIRECTORY_SEPARATOR . (string) ($font['file'] ?? '');
            if (!is_file($fontFilePath)) {
                throw new \RuntimeException(self::ERROR_ASSET_FONT);
            }
        }

        return $fontDirectory;
    }

    protected static function ensureImagickFontReady(): void
    {
        if (!method_exists(\Imagick::class, 'queryFonts')) {
            return;
        }

        try {
            foreach (self::ASSET_FONTS as $font) {
                $fonts = \Imagick::queryFonts((string) ($font['query'] ?? '*'));
                if (empty($fonts)) {
                    throw new \RuntimeException(self::ERROR_ASSET_FONT);
                }
            }
        } catch (\Throwable $e) {
            if ($e instanceof \RuntimeException && $e->getMessage() === self::ERROR_ASSET_FONT) {
                throw $e;
            }

            throw new \RuntimeException(self::ERROR_ASSET_FONT, 0, $e);
        }
    }
}
