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
use app\common\service\storage\Driver as StorageDriver;
use think\facade\Db;
use think\facade\Log;

class OrderConfirmLetterService
{
    public const RENDER_SPEC_VERSION = 'v4.1';
    public const LEGACY_RENDER_SPEC_VERSION = 'v1';
    public const CONFIG_GROUP = 'order_confirmation_letter';
    public const CONFIG_KEY_REMARK_TEMPLATE = 'remark_template';
    public const CONFIG_KEY_PAYMENT_NODE = 'payment_node';
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
    public const ERROR_ASSET_FONT_FILE_MISSING = 'ASSET_FONT_FILE_MISSING';
    public const ERROR_ASSET_FONT_FILE_UNREADABLE = 'ASSET_FONT_FILE_UNREADABLE';
    public const ERROR_ASSET_FONT_RENDER = 'ASSET_FONT_RENDER_FAILED';
    public const ERROR_ASSET_FONT_UNRECOGNIZED = 'ASSET_FONT_UNRECOGNIZED';
    public const ERROR_ASSET_RENDER = 'ASSET_RENDER_FAILED';
    public const ERROR_ASSET_LOCAL_DIRECTORY = 'ASSET_LOCAL_DIRECTORY_FAILED';
    public const ERROR_ASSET_TEMP_DIRECTORY = 'ASSET_TEMP_DIRECTORY_FAILED';
    public const ERROR_ASSET_UPLOAD = 'ASSET_UPLOAD_FAILED';
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
    protected const ASSET_STORAGE_DIR = 'uploads/order-confirm-letter';
    protected const ASSET_TEMP_DIR = 'order-confirm-letter';
    protected const ASSET_PNG_RESOLUTION = 144;
    protected const DEFAULT_BRAND_NAME = '喜遇婚礼服务';
    protected const DEFAULT_BRAND_TAGLINE = 'MAISON DE MARIAGE · CONFIRMATION';
    protected const DEFAULT_FOOTER_NOTE = '请保存此确认函图片，作为婚礼服务安排与付款确认的纸本凭证。';
    protected const BRAND_LOGO_MAX_BYTES = 1048576;

    public static function getTemplateConfig(): array
    {
        return [
            'remark_template' => (string) ConfigService::get(self::CONFIG_GROUP, self::CONFIG_KEY_REMARK_TEMPLATE, ''),
            'payment_node' => self::getPaymentNodeConfig(),
        ];
    }

    public static function setTemplateConfig(array $params): void
    {
        ConfigService::set(self::CONFIG_GROUP, self::CONFIG_KEY_REMARK_TEMPLATE, trim((string) ($params['remark_template'] ?? '')));
        if (array_key_exists('payment_node', $params)) {
            ConfigService::set(
                self::CONFIG_GROUP,
                self::CONFIG_KEY_PAYMENT_NODE,
                self::normalizePaymentNode((string) ($params['payment_node'] ?? ''))
            );
        }
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
        return [];
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
            self::ERROR_ASSET_FONT_FILE_MISSING => '确认函图片字体文件不存在，请到订单确认函设置中检查字体配置',
            self::ERROR_ASSET_FONT_FILE_UNREADABLE => '确认函图片字体文件不可读，请检查字体文件权限',
            self::ERROR_ASSET_FONT_RENDER => '确认函图片中文渲染检测失败，请到订单确认函设置中检查字体检测结果',
            self::ERROR_ASSET_FONT_UNRECOGNIZED => '确认函图片字体未被 ImageMagick 系统字体库识别，已尝试应用内字体绘制',
            self::ERROR_ASSET_RENDER => '确认函图片生成失败，请稍后重试',
            self::ERROR_ASSET_LOCAL_DIRECTORY => '确认函图片目录创建失败，请检查 public/uploads 写入权限',
            self::ERROR_ASSET_TEMP_DIRECTORY => '确认函图片临时目录创建失败，请检查 runtime 写入权限',
            self::ERROR_ASSET_UPLOAD => '确认函图片上传云存储失败，请检查存储配置',
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
        $brandConfig = self::resolveBrandConfig();
        $fontSignature = OrderConfirmLetterFontService::getActiveFontSignature();
        $remarkContent = trim((string) $qualification['remark_template']);
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
            'remark_content' => $remarkContent,
            'brand_name' => $brandConfig['brand_name'],
            'brand_tagline' => $brandConfig['brand_tagline'],
            'brand_logo' => $brandConfig['brand_logo'],
            'brand_logo_data_uri' => $brandConfig['brand_logo_data_uri'],
            'payment_node' => self::getPaymentNodeConfig(),
            'footer_note' => $remarkContent !== '' ? $remarkContent : self::DEFAULT_FOOTER_NOTE,
            'font_signature' => $fontSignature,
        ];
    }

    protected static function getPaymentNodeConfig(): string
    {
        return self::normalizePaymentNode((string) ConfigService::get(
            self::CONFIG_GROUP,
            self::CONFIG_KEY_PAYMENT_NODE,
            ''
        ));
    }

    protected static function normalizePaymentNode(string $paymentNode): string
    {
        $paymentNode = trim($paymentNode);
        return $paymentNode !== '' ? $paymentNode : '婚礼前 3 日';
    }

    protected static function resolveBrandConfig(): array
    {
        $shopName = trim((string) ConfigService::get('website', 'shop_name', ''));
        $shopSlogan = trim((string) ConfigService::get('website', 'shop_slogan', ''));
        $shopLogo = trim((string) ConfigService::get('website', 'shop_logo', ''));

        return [
            'brand_name' => $shopName !== '' ? $shopName : self::DEFAULT_BRAND_NAME,
            'brand_tagline' => $shopSlogan !== '' ? $shopSlogan : self::DEFAULT_BRAND_TAGLINE,
            'brand_logo' => $shopLogo,
            'brand_logo_data_uri' => self::resolveBrandLogoDataUri($shopLogo),
        ];
    }

    protected static function resolveBrandLogoDataUri(string $logo): string
    {
        $logo = trim($logo);
        if ($logo === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $logo) === 1) {
            $dataUri = self::readLogoDataUriFromUrl($logo);
            if ($dataUri === '') {
                self::logAssetFailure('确认函品牌 logo 远程读取失败，已降级为文字章', [
                    'logo' => $logo,
                ]);
            }
            return $dataUri;
        }

        $localPath = self::resolveStoredAssetAbsolutePath($logo);
        if ($localPath !== '' && is_file($localPath)) {
            $dataUri = self::readLogoDataUriFromPath($localPath);
            if ($dataUri === '') {
                self::logAssetFailure('确认函品牌 logo 本地读取失败，已降级为文字章', [
                    'logo' => $logo,
                    'path' => $localPath,
                ]);
            }
            return $dataUri;
        }

        try {
            $url = FileService::getFileUrl($logo);
            if (preg_match('/^https?:\/\//i', $url) === 1) {
                $dataUri = self::readLogoDataUriFromUrl($url);
                if ($dataUri === '') {
                    self::logAssetFailure('确认函品牌 logo 存储域名读取失败，已降级为文字章', [
                        'logo' => $logo,
                        'url' => $url,
                    ]);
                }
                return $dataUri;
            }
        } catch (\Throwable $e) {
            self::logAssetFailure('确认函品牌 logo 解析异常，已降级为文字章', [
                'logo' => $logo,
                'error' => $e->getMessage(),
            ]);
            return '';
        }

        self::logAssetFailure('确认函品牌 logo 路径不存在，已降级为文字章', [
            'logo' => $logo,
            'path' => $localPath,
        ]);
        return '';
    }

    protected static function readLogoDataUriFromPath(string $path): string
    {
        clearstatcache(true, $path);
        $size = is_file($path) ? (int) filesize($path) : 0;
        if ($size <= 0 || $size > self::BRAND_LOGO_MAX_BYTES || !is_readable($path)) {
            return '';
        }

        $contents = @file_get_contents($path);
        if (!is_string($contents) || $contents === '') {
            return '';
        }

        $mime = self::detectLogoMime($path, $contents);
        return $mime !== '' ? 'data:' . $mime . ';base64,' . base64_encode($contents) : '';
    }

    protected static function readLogoDataUriFromUrl(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 2,
                'follow_location' => 1,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        $contents = @file_get_contents($url, false, $context, 0, self::BRAND_LOGO_MAX_BYTES + 1);
        if (!is_string($contents) || $contents === '' || strlen($contents) > self::BRAND_LOGO_MAX_BYTES) {
            return '';
        }

        $mime = self::detectLogoMime($url, $contents);
        return $mime !== '' ? 'data:' . $mime . ';base64,' . base64_encode($contents) : '';
    }

    protected static function detectLogoMime(string $source, string $contents): string
    {
        $path = parse_url($source, PHP_URL_PATH);
        $extension = strtolower((string) pathinfo(is_string($path) ? $path : $source, PATHINFO_EXTENSION));
        $mimes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
        ];
        if (isset($mimes[$extension])) {
            return $mimes[$extension];
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string) finfo_buffer($finfo, $contents);
                finfo_close($finfo);
                if (in_array($mime, $mimes, true)) {
                    return $mime;
                }
            }
        }

        return '';
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
            'rendered_snapshot' => self::normalizeRenderedSnapshot($letter),
        ];

        return array_merge($payload, $extra);
    }

    protected static function normalizeRenderedSnapshot(OrderConfirmLetter $letter): array
    {
        $snapshot = $letter->rendered_snapshot;
        if ($snapshot instanceof \stdClass) {
            $snapshot = self::decodeSnapshotObject($snapshot);
        } elseif (is_string($snapshot)) {
            $decoded = json_decode($snapshot, true);
            $snapshot = is_array($decoded) ? $decoded : [];
        } elseif (is_object($snapshot)) {
            $snapshot = self::decodeSnapshotObject($snapshot);
        }

        return is_array($snapshot) ? $snapshot : [];
    }

    protected static function decodeSnapshotObject(object $snapshot): array
    {
        $encoded = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded) || $encoded === '') {
            return [];
        }

        $decoded = json_decode($encoded, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected static function logEmptyRenderedSnapshot(OrderConfirmLetter $letter): void
    {
        self::logAssetFailure('确认函渲染快照为空或无法解析', [
            'letter_id' => (int) $letter->id,
            'order_id' => (int) $letter->order_id,
            'snapshot_hash' => (string) $letter->snapshot_hash,
            'render_spec_version' => (string) $letter->render_spec_version,
            'snapshot_type' => get_debug_type($letter->rendered_snapshot),
        ]);
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

        if (preg_match('/^https?:\/\//i', $normalized) === 1) {
            return FileService::getFileUrl($normalized);
        }

        if (!self::isLocalStorage() && is_file(self::resolveStoredAssetAbsolutePath($normalized))) {
            return self::formatLocalPublicImageUrl($normalized);
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
        $snapshot = self::normalizeRenderedSnapshot($letter);
        if (empty($snapshot)) {
            self::logEmptyRenderedSnapshot($letter);
            throw new \RuntimeException(self::ERROR_ASSET_RENDER);
        }

        $svgContent = OrderConfirmLetterRenderer::render($snapshot, [
            'render_spec_version' => self::normalizeRenderSpecVersion((string) $letter->render_spec_version),
            'font_options' => OrderConfirmLetterFontService::getActiveFontOptions(),
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

        if (!self::isLocalStorage()) {
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

        return rtrim(public_path(), '/\\')
            . DIRECTORY_SEPARATOR
            . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($normalized, '/\\'));
    }

    protected static function formatLocalPublicImageUrl(string $url): string
    {
        try {
            return FileService::format(request()->domain(), $url);
        } catch (\Throwable $e) {
            return '/' . ltrim($url, '/');
        }
    }

    protected static function persistSvgAssets(int $orderId, string $snapshotHash, string $svgContent): array
    {
        $svgContent = trim($svgContent);
        if ($svgContent === '' || stripos($svgContent, '<svg') === false) {
            throw new \RuntimeException(self::ERROR_ASSETS_MISSING);
        }

        $folder = self::ASSET_STORAGE_DIR . '/' . date('Ym');
        $hash = preg_replace('/[^a-z0-9]/i', '', $snapshotHash);
        $hash = $hash !== '' ? substr($hash, 0, 24) : substr(md5($svgContent), 0, 24);
        $fileName = sprintf('order-%d-%s.png', $orderId, $hash);

        if (!self::isLocalStorage()) {
            return self::persistSvgAssetsToCloud($folder, $fileName, $svgContent);
        }

        return self::persistSvgAssetsToLocal($folder, $fileName, $svgContent);
    }

    protected static function persistSvgAssetsToLocal(string $folder, string $fileName, string $svgContent): array
    {
        $relativePath = $folder . '/' . $fileName;
        $absolutePath = FileService::getFileUrl($relativePath, 'public_path');
        $directory = dirname($absolutePath);
        self::ensureAssetDirectory($directory, self::ERROR_ASSET_LOCAL_DIRECTORY, [
            'storage' => 'local',
            'folder' => $folder,
            'file_name' => $fileName,
            'public_path' => public_path(),
        ]);
        self::rasterizeSvgAssets($svgContent, $absolutePath);

        return [
            'full_image_url' => $relativePath,
            'thumb_image_url' => $relativePath,
        ];
    }

    protected static function persistSvgAssetsToCloud(string $folder, string $fileName, string $svgContent): array
    {
        $storage = self::getStorageDefault();
        $tempPath = self::buildTempAssetPath($fileName, $storage);

        try {
            self::rasterizeSvgAssets($svgContent, $tempPath);

            $storageConfig = ConfigService::get('storage') ?? ['local' => []];
            if (empty($storageConfig[$storage]) || !is_array($storageConfig[$storage])) {
                self::logAssetFailure('确认函图片云存储配置缺失', [
                    'storage' => $storage,
                    'folder' => $folder,
                    'file_name' => $fileName,
                ]);
                throw new \RuntimeException(self::ERROR_ASSET_UPLOAD);
            }

            $storageDriver = new StorageDriver([
                'default' => $storage,
                'engine' => $storageConfig,
            ]);
            $storageDriver->setUploadFileByReal($tempPath, $fileName);
            $uploadedFileName = str_replace('\\', '/', (string) $storageDriver->getFileName());
            if (!$storageDriver->upload($folder)) {
                self::logAssetFailure('确认函图片上传云存储失败', [
                    'storage' => $storage,
                    'folder' => $folder,
                    'file_name' => $uploadedFileName,
                    'driver_error' => $storageDriver->getError(),
                ]);
                throw new \RuntimeException(self::ERROR_ASSET_UPLOAD);
            }

            $relativePath = $folder . '/' . $uploadedFileName;
            return [
                'full_image_url' => $relativePath,
                'thumb_image_url' => $relativePath,
            ];
        } catch (\Throwable $e) {
            if ($e instanceof \RuntimeException && in_array($e->getMessage(), [
                self::ERROR_ASSET_RUNTIME,
                self::ERROR_ASSET_FONT,
                self::ERROR_ASSET_FONT_FILE_MISSING,
                self::ERROR_ASSET_FONT_FILE_UNREADABLE,
                self::ERROR_ASSET_FONT_RENDER,
                self::ERROR_ASSET_FONT_UNRECOGNIZED,
                self::ERROR_ASSET_RENDER,
                self::ERROR_ASSET_TEMP_DIRECTORY,
                self::ERROR_ASSET_UPLOAD,
            ], true)) {
                throw $e;
            }

            self::logAssetFailure('确认函图片上传云存储异常', [
                'storage' => $storage,
                'folder' => $folder,
                'file_name' => $fileName,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException(self::ERROR_ASSET_UPLOAD, 0, $e);
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    protected static function buildTempAssetPath(string $fileName, string $storage): string
    {
        $directory = rtrim(runtime_path(), '/\\') . DIRECTORY_SEPARATOR . self::ASSET_TEMP_DIR;
        self::ensureAssetDirectory($directory, self::ERROR_ASSET_TEMP_DIRECTORY, [
            'storage' => $storage,
            'folder' => self::ASSET_TEMP_DIR,
            'file_name' => $fileName,
            'runtime_path' => runtime_path(),
        ]);

        $pathInfo = pathinfo($fileName);
        $baseName = preg_replace('/[^a-z0-9_-]/i', '', (string) ($pathInfo['filename'] ?? 'confirm-letter'));
        $baseName = $baseName !== '' ? $baseName : 'confirm-letter';
        return $directory . DIRECTORY_SEPARATOR . $baseName . '-' . uniqid('', true) . '.png';
    }

    protected static function ensureAssetDirectory(string $directory, string $errorCode, array $context = []): void
    {
        clearstatcache(true, $directory);
        if (is_dir($directory)) {
            if (!is_writable($directory)) {
                self::logAssetFailure('确认函图片目录不可写', array_merge($context, [
                    'directory' => $directory,
                    'parent_directory' => dirname($directory),
                    'parent_exists' => is_dir(dirname($directory)) ? 1 : 0,
                    'parent_writable' => is_writable(dirname($directory)) ? 1 : 0,
                ]));
                throw new \RuntimeException($errorCode);
            }
            return;
        }

        $created = @mkdir($directory, 0775, true);
        clearstatcache(true, $directory);
        if ($created && is_dir($directory)) {
            @chmod($directory, 0775);
            if (!is_writable($directory)) {
                self::logAssetFailure('确认函图片目录创建后不可写', array_merge($context, [
                    'directory' => $directory,
                    'parent_directory' => dirname($directory),
                    'parent_exists' => is_dir(dirname($directory)) ? 1 : 0,
                    'parent_writable' => is_writable(dirname($directory)) ? 1 : 0,
                ]));
                throw new \RuntimeException($errorCode);
            }
            return;
        }

        self::logAssetFailure('确认函图片目录创建失败', array_merge($context, [
            'directory' => $directory,
            'parent_directory' => dirname($directory),
            'parent_exists' => is_dir(dirname($directory)) ? 1 : 0,
            'parent_writable' => is_writable(dirname($directory)) ? 1 : 0,
            'last_error' => error_get_last(),
        ]));
        throw new \RuntimeException($errorCode);
    }

    protected static function getStorageDefault(): string
    {
        $storage = strtolower(trim((string) ConfigService::get('storage', 'default', 'local')));
        return $storage !== '' ? $storage : 'local';
    }

    protected static function isLocalStorage(): bool
    {
        return self::getStorageDefault() === 'local';
    }

    protected static function logAssetFailure(string $message, array $context = []): void
    {
        try {
            Log::write('订单确认函图片资产处理失败：' . $message . '，上下文：' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            // 日志失败不应覆盖确认函生成的真实异常。
        }
    }

    protected static function rasterizeSvgAssets(string $svgContent, string $absolutePath): void
    {
        if (!extension_loaded('imagick') || !class_exists(\Imagick::class)) {
            throw new \RuntimeException(self::ERROR_ASSET_RUNTIME);
        }

        $imagick = new \Imagick();

        try {
            self::ensureImagickFontReady();
            $imagick->setResolution(self::ASSET_PNG_RESOLUTION, self::ASSET_PNG_RESOLUTION);
            $imagick->setBackgroundColor(new \ImagickPixel('transparent'));
            [$backgroundSvg, $textItems, $canvas] = self::stripSvgTextItems($svgContent);
            $imagick->readImageBlob($backgroundSvg);
            $imagick->setImageFormat('png');
            self::drawSvgTextItems($imagick, $textItems, $canvas);
            if (!$imagick->writeImage($absolutePath)) {
                throw new \RuntimeException(self::ERROR_ASSET_RENDER);
            }
        } catch (\Throwable $e) {
            @unlink($absolutePath);
            if ($e instanceof \RuntimeException && in_array($e->getMessage(), [
                self::ERROR_ASSET_RUNTIME,
                self::ERROR_ASSET_FONT,
                self::ERROR_ASSET_FONT_FILE_MISSING,
                self::ERROR_ASSET_FONT_FILE_UNREADABLE,
                self::ERROR_ASSET_FONT_RENDER,
                self::ERROR_ASSET_FONT_UNRECOGNIZED,
                self::ERROR_ASSET_RENDER,
            ], true)) {
                throw $e;
            }

            throw new \RuntimeException(self::ERROR_ASSET_RENDER, 0, $e);
        } finally {
            $imagick->clear();
            $imagick->destroy();
        }
    }

    protected static function stripSvgTextItems(string $svgContent): array
    {
        if (!class_exists(\DOMDocument::class)) {
            return [$svgContent, []];
        }

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadXML($svgContent);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$loaded) {
            return [$svgContent, [], []];
        }

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');
        $root = $document->documentElement;
        $canvas = [
            'width' => self::readSvgRootNumber($root, 'width', 0.0),
            'height' => self::readSvgRootNumber($root, 'height', 0.0),
        ];
        if ($root instanceof \DOMElement) {
            $viewBox = trim($root->getAttribute('viewBox'));
            $viewBoxParts = preg_split('/[\s,]+/', $viewBox) ?: [];
            if (count($viewBoxParts) === 4) {
                $canvas['width'] = (float) $viewBoxParts[2];
                $canvas['height'] = (float) $viewBoxParts[3];
            }
        }

        $nodes = [];
        foreach ($xpath->query('//svg:text') ?: [] as $node) {
            if ($node instanceof \DOMElement) {
                $nodes[] = $node;
            }
        }

        $textItems = [];
        foreach ($nodes as $node) {
            $text = trim((string) $node->textContent);
            if ($text === '') {
                $node->parentNode?->removeChild($node);
                continue;
            }

            $textItems[] = [
                'text' => html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8'),
                'x' => self::readSvgNumber($node, 'x', 0.0),
                'y' => self::readSvgNumber($node, 'y', 0.0),
                'font_size' => max(1.0, self::readSvgNumber($node, 'font-size', 16.0)),
                'fill' => self::normalizeSvgColor($node->getAttribute('fill') ?: '#000000'),
                'font_family' => $node->getAttribute('font-family'),
                'text_anchor' => $node->getAttribute('text-anchor') ?: 'start',
                'letter_spacing' => self::readSvgNumber($node, 'letter-spacing', 0.0),
                'translate' => self::resolveSvgTranslate($node),
            ];

            $node->parentNode?->removeChild($node);
        }

        $backgroundSvg = $document->saveXML($document->documentElement);
        return [is_string($backgroundSvg) ? $backgroundSvg : $svgContent, $textItems, $canvas];
    }

    protected static function drawSvgTextItems(\Imagick $imagick, array $textItems, array $canvas = []): void
    {
        if (empty($textItems)) {
            return;
        }

        $scaleX = !empty($canvas['width']) ? $imagick->getImageWidth() / (float) $canvas['width'] : 1.0;
        $scaleY = !empty($canvas['height']) ? $imagick->getImageHeight() / (float) $canvas['height'] : $scaleX;
        $scale = ($scaleX + $scaleY) / 2;
        $fontOptions = OrderConfirmLetterFontService::getActiveFontOptions();
        foreach ($textItems as $item) {
            $text = (string) ($item['text'] ?? '');
            if ($text === '') {
                continue;
            }

            $draw = new \ImagickDraw();
            try {
                $fontPath = self::resolveTextItemFontPath((string) ($item['font_family'] ?? ''), $fontOptions);
                if ($fontPath === '' || !is_file($fontPath)) {
                    self::logAssetFailure('确认函图片字体文件不存在', [
                        'render_spec_version' => self::RENDER_SPEC_VERSION,
                        'font_family' => (string) ($item['font_family'] ?? ''),
                        'font_options' => self::formatFontOptionsForLog($fontOptions),
                    ]);
                    throw new \RuntimeException(self::ERROR_ASSET_FONT_FILE_MISSING);
                }
                if (!is_readable($fontPath)) {
                    self::logAssetFailure('确认函图片字体文件不可读', [
                        'render_spec_version' => self::RENDER_SPEC_VERSION,
                        'font_family' => (string) ($item['font_family'] ?? ''),
                        'font_path' => $fontPath,
                    ]);
                    throw new \RuntimeException(self::ERROR_ASSET_FONT_FILE_UNREADABLE);
                }
                $fontSize = max(1.0, (float) ($item['font_size'] ?? 16.0) * $scale);
                $draw->setFont($fontPath);
                $draw->setFontSize($fontSize);
                $draw->setFillColor(new \ImagickPixel((string) ($item['fill'] ?? '#000000')));

                $x = ((float) ($item['x'] ?? 0.0) + (float) ($item['translate']['x'] ?? 0.0)) * $scaleX;
                $y = ((float) ($item['y'] ?? 0.0) + (float) ($item['translate']['y'] ?? 0.0)) * $scaleY;
                $letterSpacing = (float) ($item['letter_spacing'] ?? 0.0) * $scale;
                $textAnchor = (string) ($item['text_anchor'] ?? 'start');
                if ($letterSpacing !== 0.0 && self::isAsciiText($text)) {
                    self::drawTextWithLetterSpacing($imagick, $draw, $text, $x, $y, $letterSpacing, $textAnchor);
                    continue;
                }

                if ($textAnchor === 'middle') {
                    $metrics = $imagick->queryFontMetrics($draw, $text);
                    $x -= (float) ($metrics['textWidth'] ?? 0) / 2;
                } elseif ($textAnchor === 'end') {
                    $metrics = $imagick->queryFontMetrics($draw, $text);
                    $x -= (float) ($metrics['textWidth'] ?? 0);
                }
                $imagick->annotateImage($draw, $x, $y, 0, $text);
            } finally {
                $draw->clear();
                $draw->destroy();
            }
        }
    }

    protected static function formatFontOptionsForLog(array $fontOptions): array
    {
        return [
            'sans_file' => (string) ($fontOptions['sans_file'] ?? ''),
            'serif_file' => (string) ($fontOptions['serif_file'] ?? ''),
            'sans_path' => (string) ($fontOptions['sans_path'] ?? ''),
            'serif_path' => (string) ($fontOptions['serif_path'] ?? ''),
            'font_hash' => (string) (OrderConfirmLetterFontService::getActiveFontSignature($fontOptions)['hash'] ?? ''),
        ];
    }

    protected static function drawTextWithLetterSpacing(
        \Imagick $imagick,
        \ImagickDraw $draw,
        string $text,
        float $x,
        float $y,
        float $letterSpacing,
        string $textAnchor
    ): void {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (empty($chars)) {
            return;
        }

        $width = 0.0;
        $charMetrics = [];
        foreach ($chars as $char) {
            $metrics = $imagick->queryFontMetrics($draw, $char);
            $advance = (float) ($metrics['textWidth'] ?? 0);
            $charMetrics[] = [$char, $advance];
            $width += $advance;
        }
        $width += max(count($chars) - 1, 0) * $letterSpacing;
        if ($textAnchor === 'middle') {
            $x -= $width / 2;
        } elseif ($textAnchor === 'end') {
            $x -= $width;
        }

        foreach ($charMetrics as [$char, $advance]) {
            $imagick->annotateImage($draw, $x, $y, 0, (string) $char);
            $x += (float) $advance + $letterSpacing;
        }
    }

    protected static function resolveTextItemFontPath(string $fontFamily, array $fontOptions): string
    {
        $serifFamily = (string) ($fontOptions['serif_family'] ?? 'OrderConfirmLetterSerif');
        $sansPath = (string) ($fontOptions['sans_path'] ?? '');
        $serifPath = (string) ($fontOptions['serif_path'] ?? '');
        if (str_contains($fontFamily, $serifFamily)
            || str_contains($fontFamily, 'Noto Serif SC')
            || str_contains($fontFamily, 'Georgia')
            || str_contains($fontFamily, 'Times New Roman')
        ) {
            return $serifPath !== '' ? $serifPath : $sansPath;
        }
        return $sansPath !== '' ? $sansPath : $serifPath;
    }

    protected static function readSvgNumber(\DOMElement $node, string $attribute, float $default): float
    {
        $value = trim($node->getAttribute($attribute));
        if ($value === '' || preg_match('/-?\d+(?:\.\d+)?/', $value, $matches) !== 1) {
            return $default;
        }
        return (float) $matches[0];
    }

    protected static function readSvgRootNumber(?\DOMElement $node, string $attribute, float $default): float
    {
        if (!$node) {
            return $default;
        }
        return self::readSvgNumber($node, $attribute, $default);
    }

    protected static function resolveSvgTranslate(\DOMElement $node): array
    {
        $x = 0.0;
        $y = 0.0;
        $current = $node->parentNode;
        while ($current instanceof \DOMElement) {
            $transform = $current->getAttribute('transform');
            if ($transform !== '' && preg_match_all('/translate\(([^)]*)\)/', $transform, $matches)) {
                foreach ($matches[1] as $translate) {
                    $parts = preg_split('/[\s,]+/', trim((string) $translate)) ?: [];
                    $x += isset($parts[0]) ? (float) $parts[0] : 0.0;
                    $y += isset($parts[1]) ? (float) $parts[1] : 0.0;
                }
            }
            $current = $current->parentNode;
        }
        return ['x' => $x, 'y' => $y];
    }

    protected static function normalizeSvgColor(string $color): string
    {
        $color = trim($color);
        if ($color === '' || strtolower($color) === 'none') {
            return '#000000';
        }
        return $color;
    }

    protected static function isAsciiText(string $text): bool
    {
        return preg_match('/^[\x20-\x7E]+$/', $text) === 1;
    }

    protected static function resolveAssetFontDirectory(): string
    {
        return OrderConfirmLetterFontService::getFontDirectory();
    }

    protected static function ensureImagickFontReady(): void
    {
        $fontOptions = OrderConfirmLetterFontService::getActiveFontOptions();
        foreach (['sans_path', 'serif_path'] as $pathKey) {
            $path = (string) ($fontOptions[$pathKey] ?? '');
            if ($path === '' || !is_file($path)) {
                throw new \RuntimeException(self::ERROR_ASSET_FONT_FILE_MISSING);
            }
            if (!is_readable($path)) {
                throw new \RuntimeException(self::ERROR_ASSET_FONT_FILE_UNREADABLE);
            }
        }

        try {
            OrderConfirmLetterFontService::assertActiveFontsRenderable($fontOptions);
        } catch (\Throwable $e) {
            self::logAssetFailure('确认函图片中文渲染检测失败', [
                'sans_file' => (string) ($fontOptions['sans_file'] ?? ''),
                'serif_file' => (string) ($fontOptions['serif_file'] ?? ''),
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException(self::ERROR_ASSET_FONT_RENDER, 0, $e);
        }
    }
}
