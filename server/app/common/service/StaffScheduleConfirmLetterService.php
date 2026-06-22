<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员档期确认函服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffScheduleConfirmLetter;
use app\common\model\staff\StaffScheduleConfirmLetterConfig;
use app\common\service\storage\Driver as StorageDriver;
use think\facade\Db;
use think\facade\Log;

class StaffScheduleConfirmLetterService
{
    public const RENDER_SPEC_VERSION = 'staff-schedule-designer-v2';
    public const DEFAULT_TITLE = '档期已定';
    public const DEFAULT_SUBTITLE = 'SCHEDULE RESERVED';
    public const DEFAULT_CONTENT_TEMPLATE = '{service_date_label}，{customer_alias}的{service_name}档期已确认，感谢信任。';
    public const DEFAULT_FOOTER_NOTE = '档期已预定，感谢信任与选择。';

    public const ERROR_STALE = 'STAFF_SCHEDULE_CONFIRM_LETTER_STALE';
    public const ERROR_NOT_BOUND = 'STAFF_NOT_BOUND_TO_ORDER';
    public const ERROR_ORDER_STATUS = 'ORDER_STATUS_NOT_ALLOWED';
    public const ERROR_NOT_LOCKED = 'ORDER_NOT_LOCKED_OR_PAID';
    public const ERROR_SERVICE_DATE = 'SERVICE_DATE_MISSING';
    public const ERROR_CONFIG_NOT_FOUND = 'STAFF_SCHEDULE_CONFIRM_LETTER_CONFIG_NOT_FOUND';
    public const ERROR_CONFIG_DISABLED = 'STAFF_SCHEDULE_CONFIRM_LETTER_CONFIG_DISABLED';
    public const ERROR_CONFIG_LAST_ACTIVE = 'STAFF_SCHEDULE_CONFIRM_LETTER_CONFIG_LAST_ACTIVE';
    public const ERROR_ASSET_RUNTIME = 'STAFF_SCHEDULE_CONFIRM_LETTER_ASSET_RUNTIME';
    public const ERROR_ASSET_RENDER = 'STAFF_SCHEDULE_CONFIRM_LETTER_ASSET_RENDER';
    public const ERROR_ASSET_DIRECTORY = 'STAFF_SCHEDULE_CONFIRM_LETTER_ASSET_DIRECTORY';
    public const ERROR_ASSET_UPLOAD = 'STAFF_SCHEDULE_CONFIRM_LETTER_ASSET_UPLOAD';
    public const ERROR_ASSET_FONT_FILE_MISSING = 'STAFF_SCHEDULE_CONFIRM_LETTER_FONT_FILE_MISSING';
    public const ERROR_ASSET_FONT_FILE_UNREADABLE = 'STAFF_SCHEDULE_CONFIRM_LETTER_FONT_FILE_UNREADABLE';
    public const ERROR_ASSET_FONT_RENDER = 'STAFF_SCHEDULE_CONFIRM_LETTER_FONT_RENDER';
    public const ERROR_QRCODE_MISSING = 'STAFF_SCHEDULE_CONFIRM_LETTER_QRCODE_MISSING';

    public const CONFIG_KEY_SCHEDULE_QRCODE_IMAGE = 'schedule_qrcode_image';
    public const QRCODE_MIN_SIZE = 160;
    public const QRCODE_DEFAULT_SIZE = 204;

    protected const ASSET_STORAGE_DIR = 'uploads/staff-schedule-confirm-letter';
    protected const ASSET_TEMP_DIR = 'staff_schedule_confirm_letter';
    protected const ASSET_RASTER_RESOLUTION = 96;
    protected const ASSET_JPEG_QUALITY = 82;
    protected const ASSET_FILE_EXTENSION = 'jpg';
    protected const ASSET_FILE_VERSION = 'r3';
    protected const ASSET_MIN_VALID_BYTES = 4096;
    protected const SVG_IMAGE_MAX_BYTES = 12 * 1024 * 1024;
    protected const SVG_TRANSPARENT_PIXEL = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
    protected const CONFIG_STATUS_ACTIVE = 1;
    protected const CONFIG_STATUS_DISABLED = 0;

    public static function getGlobalQrcodeConfig(): array
    {
        $image = self::getGlobalQrcodeImage();
        return [
            'schedule_qrcode_image' => $image,
            'schedule_qrcode_image_url' => self::formatPublicImageUrl($image),
            'qrcode_configured' => $image !== '' ? 1 : 0,
            'qrcode_min_size' => self::QRCODE_MIN_SIZE,
        ];
    }

    public static function setGlobalQrcodeConfig(array $params): array
    {
        $image = self::normalizeStoredFileUrl((string)($params[self::CONFIG_KEY_SCHEDULE_QRCODE_IMAGE] ?? ''));
        if ($image === '') {
            throw new \RuntimeException('请上传档期确认海报统一二维码');
        }
        ConfigService::set(OrderConfirmLetterService::CONFIG_GROUP, self::CONFIG_KEY_SCHEDULE_QRCODE_IMAGE, $image);
        return self::getGlobalQrcodeConfig();
    }

    public static function listConfigs(int $staffId, bool $includeDisabled = false): array
    {
        self::ensureDefaultConfig($staffId);
        $query = StaffScheduleConfirmLetterConfig::where('staff_id', $staffId)
            ->order('is_default', 'desc')
            ->order('sort', 'desc')
            ->order('id', 'asc');
        if (!$includeDisabled) {
            $query->where('status', self::CONFIG_STATUS_ACTIVE);
        }

        return $query->select()
            ->map(static function (StaffScheduleConfirmLetterConfig $config): array {
                return self::formatConfigSummary($config);
            })
            ->toArray();
    }

    public static function getConfig(int $staffId, int $configId = 0): array
    {
        $defaults = self::defaultConfig($staffId);
        /** @var StaffScheduleConfirmLetterConfig|null $config */
        $config = self::findConfig($staffId, $configId, $configId <= 0);
        if (!$config && $configId > 0) {
            throw new \RuntimeException(self::ERROR_CONFIG_NOT_FOUND);
        }
        if (!$config) {
            $config = self::createDefaultConfig($staffId);
        }

        return self::formatConfig(array_merge($defaults, $config->toArray()));
    }

    public static function saveConfig(int $staffId, array $params): array
    {
        $payload = self::normalizeConfigPayload($staffId, $params);
        $now = time();

        return Db::transaction(function () use ($staffId, $params, $payload, $now) {
            $configId = (int)($params['config_id'] ?? 0);
            /** @var StaffScheduleConfirmLetterConfig|null $config */
            $config = $configId > 0 ? self::findConfig($staffId, $configId, false, true) : null;
            if ($configId > 0 && !$config) {
                throw new \RuntimeException(self::ERROR_CONFIG_NOT_FOUND);
            }
            if ($config && (int)$config->status !== self::CONFIG_STATUS_ACTIVE) {
                throw new \RuntimeException(self::ERROR_CONFIG_DISABLED);
            }

            $payload['template_name'] = self::normalizeTemplateName((string)($params['template_name'] ?? ($config->template_name ?? '')));
            $payload['sort'] = self::clampInt((int)($params['sort'] ?? ($config->sort ?? 0)), 0, 9999);
            $payload['status'] = self::CONFIG_STATUS_ACTIVE;
            if ($config) {
                $payload['template_version'] = (int)($config->template_version ?: 1);
                $payload['is_default'] = (int)($params['is_default'] ?? $config->is_default) === 1 ? 1 : 0;
                $payload['update_time'] = $now;
                $config->save($payload);
            } else {
                $payload['staff_id'] = $staffId;
                $payload['template_version'] = self::nextTemplateVersion($staffId);
                $payload['is_default'] = (int)($params['is_default'] ?? 0) === 1 || self::activeConfigCount($staffId) <= 0 ? 1 : 0;
                $payload['create_time'] = $now;
                $payload['update_time'] = $now;
                $config = StaffScheduleConfirmLetterConfig::create($payload);
            }

            if ((int)$config->is_default === 1) {
                self::clearOtherDefaultConfigs($staffId, (int)$config->id);
            } elseif (self::defaultConfigRecord($staffId) === null) {
                $config->is_default = 1;
                $config->save(['is_default' => 1, 'update_time' => $now]);
            }

            return self::formatConfigResponse($staffId, (int)$config->id, true);
        });
    }

    public static function copyConfig(int $staffId, int $configId, string $templateName = ''): array
    {
        return Db::transaction(function () use ($staffId, $configId, $templateName) {
            /** @var StaffScheduleConfirmLetterConfig|null $source */
            $source = self::findConfig($staffId, $configId, false, true);
            if (!$source) {
                throw new \RuntimeException(self::ERROR_CONFIG_NOT_FOUND);
            }
            $data = $source->toArray();
            unset($data['id']);
            $now = time();
            $data['staff_id'] = $staffId;
            $data['template_name'] = self::normalizeTemplateName($templateName !== '' ? $templateName : (string)$source->template_name . ' 副本');
            $data['template_version'] = self::nextTemplateVersion($staffId);
            $data['is_default'] = 0;
            $data['status'] = self::CONFIG_STATUS_ACTIVE;
            $data['sort'] = (int)$source->sort;
            $data['create_time'] = $now;
            $data['update_time'] = $now;
            $config = StaffScheduleConfirmLetterConfig::create($data);
            return self::formatConfigResponse($staffId, (int)$config->id, true);
        });
    }

    public static function setDefaultConfig(int $staffId, int $configId): array
    {
        return Db::transaction(function () use ($staffId, $configId) {
            /** @var StaffScheduleConfirmLetterConfig|null $config */
            $config = self::findConfig($staffId, $configId, false, true);
            if (!$config) {
                throw new \RuntimeException(self::ERROR_CONFIG_NOT_FOUND);
            }
            if ((int)$config->status !== self::CONFIG_STATUS_ACTIVE) {
                throw new \RuntimeException(self::ERROR_CONFIG_DISABLED);
            }
            $now = time();
            self::clearOtherDefaultConfigs($staffId, $configId);
            $config->save(['is_default' => 1, 'update_time' => $now]);
            return self::formatConfigResponse($staffId, $configId, true);
        });
    }

    public static function disableConfig(int $staffId, int $configId): array
    {
        return Db::transaction(function () use ($staffId, $configId) {
            /** @var StaffScheduleConfirmLetterConfig|null $config */
            $config = self::findConfig($staffId, $configId, false, true);
            if (!$config) {
                throw new \RuntimeException(self::ERROR_CONFIG_NOT_FOUND);
            }
            if ((int)$config->status !== self::CONFIG_STATUS_ACTIVE) {
                return self::formatConfigResponse($staffId, 0, true);
            }
            if (self::activeConfigCount($staffId) <= 1) {
                throw new \RuntimeException(self::ERROR_CONFIG_LAST_ACTIVE);
            }
            $now = time();
            $wasDefault = (int)$config->is_default === 1;
            $config->save([
                'status' => self::CONFIG_STATUS_DISABLED,
                'is_default' => 0,
                'update_time' => $now,
            ]);
            if ($wasDefault) {
                $fallback = self::defaultConfigRecord($staffId) ?: StaffScheduleConfirmLetterConfig::where('staff_id', $staffId)
                    ->where('status', self::CONFIG_STATUS_ACTIVE)
                    ->order('sort', 'desc')
                    ->order('id', 'asc')
                    ->find();
                if ($fallback) {
                    $fallback->save(['is_default' => 1, 'update_time' => $now]);
                }
            }
            return self::formatConfigResponse($staffId, 0, true);
        });
    }

    public static function saveLiteConfig(int $staffId, int $configId, array $params): array
    {
        $current = self::getConfig($staffId, $configId);
        $design = self::mergeLiteDesignConfig(
            is_array($current['design_config'] ?? null) ? $current['design_config'] : self::defaultDesignConfig()
        , $params);
        $payload = [
            'design_config' => $design,
            'background_type' => (string)($design['background']['type'] ?? 'color'),
            'background_image' => (string)($design['background']['image'] ?? ''),
            'background_color' => (string)($design['background']['color'] ?? '#191713'),
        ];

        $allowedLiteFields = self::extractEditableFields($design);
        foreach (['title', 'subtitle', 'content_template', 'footer_note'] as $field) {
            if (!in_array($field, $allowedLiteFields, true)) {
                continue;
            }
            if (array_key_exists($field, $params)) {
                $payload[$field] = $params[$field];
            }
        }

        $payload['config_id'] = (int)$current['config_id'];
        return self::saveConfig($staffId, $payload);
    }

    public static function previewConfig(int $staffId, array $params = []): array
    {
        $configId = (int)($params['config_id'] ?? 0);
        $currentConfig = self::getConfig($staffId, $configId);
        if (empty($params)) {
            $config = $currentConfig;
        } elseif (is_array($params['design_config'] ?? null)) {
            $config = self::formatConfig(array_merge($currentConfig, self::normalizeConfigPayload($staffId, $params)));
        } else {
            $mergedDesign = self::mergeLiteDesignConfig(
                is_array($currentConfig['design_config'] ?? null) ? $currentConfig['design_config'] : self::defaultDesignConfig(),
                $params
            );
            $config = self::formatConfig(array_merge($currentConfig, self::normalizeConfigPayload($staffId, array_merge($params, [
                'design_config' => $mergedDesign,
            ]))));
        }
        $staff = Staff::find($staffId);
        $snapshot = self::buildPreviewSnapshot($config, $staff);

        return [
            'config' => $config,
            'preview' => [
                'render_spec_version' => self::RENDER_SPEC_VERSION,
                'rendered_snapshot' => $snapshot,
                'snapshot_hash' => self::buildSnapshotHash($snapshot),
                'svg_content' => StaffScheduleConfirmLetterRenderer::render($snapshot, [
                    'font_options' => OrderConfirmLetterFontService::getActiveFontOptions(),
                ]),
            ],
        ];
    }

    public static function generate(int $orderId, int $staffId, string $source = 'staff', int $operatorId = 0, int $configId = 0): array
    {
        return Db::transaction(function () use ($orderId, $staffId, $source, $operatorId, $configId) {
            $order = self::getOrderWithRelations($orderId, true);
            if (!$order) {
                throw new \RuntimeException('订单不存在');
            }
            $staff = Staff::find($staffId);
            if (!$staff) {
                throw new \RuntimeException('服务人员不存在');
            }

            $item = self::resolveStaffOrderItem($order, $staffId);
            self::checkGenerateQualification($order, $item);
            $config = self::getConfig($staffId, $configId);
            if ((int)($config['status'] ?? self::CONFIG_STATUS_ACTIVE) !== self::CONFIG_STATUS_ACTIVE) {
                throw new \RuntimeException(self::ERROR_CONFIG_DISABLED);
            }
            $snapshot = self::buildSnapshot($order, $item, $staff, $config);
            $snapshotHash = self::buildSnapshotHash($snapshot);

            /** @var StaffScheduleConfirmLetter|null $sameLetter */
            $sameLetter = StaffScheduleConfirmLetter::where('order_id', $orderId)
                ->where('staff_id', $staffId)
                ->where('snapshot_hash', $snapshotHash)
                ->where('is_outdated', StaffScheduleConfirmLetter::STATUS_ACTIVE)
                ->lock(true)
                ->find();
            if ($sameLetter) {
                self::ensurePersistedAssets($sameLetter);
                return self::formatLetter($sameLetter);
            }

            StaffScheduleConfirmLetter::where('order_id', $orderId)
                ->where('staff_id', $staffId)
                ->where('is_outdated', StaffScheduleConfirmLetter::STATUS_ACTIVE)
                ->update([
                    'is_outdated' => StaffScheduleConfirmLetter::STATUS_OUTDATED,
                    'update_time' => time(),
                ]);

            $version = (int) StaffScheduleConfirmLetter::where('order_id', $orderId)
                ->where('staff_id', $staffId)
                ->max('version') + 1;

            /** @var StaffScheduleConfirmLetter $letter */
            $letter = StaffScheduleConfirmLetter::create([
                'order_id' => $orderId,
                'staff_id' => $staffId,
                'config_id' => (int)($config['config_id'] ?? 0),
                'config_name' => (string)($config['template_name'] ?? '默认海报'),
                'config_template_version' => (int)($config['template_version'] ?? 1),
                'version' => $version,
                'confirm_date' => date('Y-m-d'),
                'rendered_snapshot' => $snapshot,
                'snapshot_hash' => $snapshotHash,
                'render_spec_version' => self::RENDER_SPEC_VERSION,
                'full_image_url' => '',
                'thumb_image_url' => '',
                'is_outdated' => StaffScheduleConfirmLetter::STATUS_ACTIVE,
                'generate_source' => self::normalizeSource($source),
                'generate_staff_id' => $source === 'staff' ? $staffId : 0,
                'generate_admin_id' => $source === 'admin' ? $operatorId : 0,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            self::ensurePersistedAssets($letter, true);
            return self::formatLetter($letter);
        });
    }

    public static function detail(int $letterId, int $staffId): ?array
    {
        /** @var StaffScheduleConfirmLetter|null $letter */
        $letter = StaffScheduleConfirmLetter::where('id', $letterId)
            ->where('staff_id', $staffId)
            ->find();
        if (!$letter) {
            return null;
        }

        self::ensurePersistedAssets($letter);
        return self::formatLetter($letter);
    }

    public static function history(int $orderId, int $staffId): array
    {
        return StaffScheduleConfirmLetter::where('order_id', $orderId)
            ->where('staff_id', $staffId)
            ->order('version', 'desc')
            ->select()
            ->map(static function (StaffScheduleConfirmLetter $letter): array {
                return self::formatLetterSummary($letter);
            })
            ->toArray();
    }

    public static function regenerateAssets(int $letterId, string $snapshotHash, int $staffId, bool $force = true): array
    {
        return Db::transaction(function () use ($letterId, $snapshotHash, $staffId, $force) {
            /** @var StaffScheduleConfirmLetter|null $letter */
            $letter = StaffScheduleConfirmLetter::where('id', $letterId)
                ->where('staff_id', $staffId)
                ->lock(true)
                ->find();
            if (!$letter) {
                throw new \RuntimeException('档期确认函不存在');
            }
            if ($snapshotHash !== '' && (string)$letter->snapshot_hash !== $snapshotHash) {
                throw new \RuntimeException(self::ERROR_STALE);
            }
            self::ensurePersistedAssets($letter, $force);

            return [
                'letter_id' => (int)$letter->id,
                'assets_saved' => true,
            ];
        });
    }

    public static function normalizeErrorMessage(string $message): string
    {
        return match (trim($message)) {
            self::ERROR_STALE => '档期确认函内容已更新，请重新生成海报',
            self::ERROR_NOT_BOUND => '当前服务人员未绑定该订单服务项，不能生成档期确认函',
            self::ERROR_ORDER_STATUS => '订单已取消、退款或删除，不能生成档期确认函',
            self::ERROR_NOT_LOCKED => '订单尚未支付或确认锁档，不能生成已预定档期确认函',
            self::ERROR_SERVICE_DATE => '订单缺少服务日期，暂时无法生成档期确认函',
            self::ERROR_CONFIG_NOT_FOUND => '海报模板不存在或无权限访问',
            self::ERROR_CONFIG_DISABLED => '海报模板已停用，不能用于生成',
            self::ERROR_CONFIG_LAST_ACTIVE => '至少需要保留一个启用的海报模板',
            self::ERROR_ASSET_RUNTIME => '档期确认函图片生成环境缺少 Imagick 支持，请联系管理员处理',
            self::ERROR_ASSET_RENDER => '档期确认函图片生成失败，请稍后重试',
            self::ERROR_ASSET_DIRECTORY => '档期确认函图片目录创建失败，请检查 public/uploads 写入权限',
            self::ERROR_ASSET_UPLOAD => '档期确认函图片上传云存储失败，请检查存储配置',
            self::ERROR_ASSET_FONT_FILE_MISSING => '档期确认函图片字体文件不存在，请到确认函资源设置中检查字体配置',
            self::ERROR_ASSET_FONT_FILE_UNREADABLE => '档期确认函图片字体文件不可读，请检查字体文件权限',
            self::ERROR_ASSET_FONT_RENDER => '档期确认函图片中文渲染检测失败，请到确认函资源设置中检查字体检测结果',
            self::ERROR_QRCODE_MISSING => '请先在系统设置中上传档期确认海报统一二维码',
            default => $message,
        };
    }

    protected static function defaultConfig(int $staffId): array
    {
        return [
            'config_id' => 0,
            'staff_id' => $staffId,
            'template_name' => '默认海报',
            'template_version' => 1,
            'is_default' => 1,
            'status' => self::CONFIG_STATUS_ACTIVE,
            'sort' => 0,
            'title' => self::DEFAULT_TITLE,
            'subtitle' => self::DEFAULT_SUBTITLE,
            'content_template' => self::DEFAULT_CONTENT_TEMPLATE,
            'footer_note' => self::DEFAULT_FOOTER_NOTE,
            'background_type' => 'color',
            'background_image' => '',
            'background_color' => '#191713',
            'text_theme' => 'light',
            'show_customer_alias' => 1,
            'show_service_name' => 1,
            'show_city' => 1,
            'show_qrcode' => 1,
            'qrcode_image' => self::getGlobalQrcodeImage(),
            'design_version' => self::RENDER_SPEC_VERSION,
            'design_config' => self::defaultDesignConfig(),
            'editable_fields' => self::defaultEditableFields(),
            'versions' => [],
        ];
    }

    protected static function normalizeConfigPayload(int $staffId, array $params): array
    {
        $defaults = self::defaultConfig($staffId);
        $backgroundType = in_array((string)($params['background_type'] ?? ''), ['color', 'image'], true)
            ? (string)$params['background_type']
            : (string)$defaults['background_type'];
        $textTheme = in_array((string)($params['text_theme'] ?? ''), ['light', 'dark'], true)
            ? (string)$params['text_theme']
            : (string)$defaults['text_theme'];

        $designConfig = self::normalizeDesignConfig(
            is_array($params['design_config'] ?? null) ? $params['design_config'] : ($defaults['design_config'] ?? [])
        );
        $background = is_array($designConfig['background'] ?? null) ? $designConfig['background'] : [];

        return [
            'title' => self::limitText((string)($params['title'] ?? $defaults['title']), 40, $defaults['title']),
            'subtitle' => self::limitText((string)($params['subtitle'] ?? $defaults['subtitle']), 80, $defaults['subtitle']),
            'content_template' => self::limitText((string)($params['content_template'] ?? $defaults['content_template']), 500, $defaults['content_template']),
            'footer_note' => self::limitText((string)($params['footer_note'] ?? $defaults['footer_note']), 160, $defaults['footer_note']),
            'background_type' => (string)($background['type'] ?? $backgroundType),
            'background_image' => self::normalizeStoredFileUrl((string)($background['image'] ?? ($params['background_image'] ?? ''))),
            'background_color' => self::normalizeColor((string)($background['color'] ?? ($params['background_color'] ?? $defaults['background_color'])), $defaults['background_color']),
            'text_theme' => $textTheme,
            'show_customer_alias' => self::toSwitch($params['show_customer_alias'] ?? 1),
            'show_service_name' => self::toSwitch($params['show_service_name'] ?? 1),
            'show_city' => self::toSwitch($params['show_city'] ?? 1),
            'show_qrcode' => 1,
            'qrcode_image' => self::getGlobalQrcodeImage(),
            'design_version' => self::RENDER_SPEC_VERSION,
            'design_config' => $designConfig,
        ];
    }

    protected static function formatConfig(array $config): array
    {
        $configId = (int)($config['id'] ?? $config['config_id'] ?? 0);
        $formatted = self::normalizeConfigPayload((int)($config['staff_id'] ?? 0), $config);
        $formatted['config_id'] = $configId;
        $formatted['staff_id'] = (int)($config['staff_id'] ?? 0);
        $formatted['template_name'] = self::normalizeTemplateName((string)($config['template_name'] ?? '默认海报'));
        $formatted['template_version'] = max(1, (int)($config['template_version'] ?? 1));
        $formatted['is_default'] = (int)($config['is_default'] ?? 0) === 1 ? 1 : 0;
        $formatted['status'] = (int)($config['status'] ?? self::CONFIG_STATUS_ACTIVE) === self::CONFIG_STATUS_DISABLED
            ? self::CONFIG_STATUS_DISABLED
            : self::CONFIG_STATUS_ACTIVE;
        $formatted['sort'] = (int)($config['sort'] ?? 0);
        $formatted['background_image_url'] = self::formatPublicImageUrl($formatted['background_image']);
        $formatted['qrcode_image'] = self::getGlobalQrcodeImage();
        $formatted['qrcode_image_url'] = self::formatPublicImageUrl($formatted['qrcode_image']);
        $formatted['design_version'] = self::RENDER_SPEC_VERSION;
        $formatted['design_config'] = self::formatDesignConfigForClient($formatted['design_config']);
        $formatted['editable_fields'] = self::extractEditableFields($formatted['design_config']);
        return $formatted;
    }

    protected static function formatConfigResponse(int $staffId, int $configId = 0, bool $includeDisabled = false): array
    {
        $config = self::getConfig($staffId, $configId);
        $config['versions'] = self::listConfigs($staffId, $includeDisabled);
        return $config;
    }

    protected static function formatConfigSummary(StaffScheduleConfirmLetterConfig $config): array
    {
        return [
            'config_id' => (int)$config->id,
            'staff_id' => (int)$config->staff_id,
            'template_name' => self::normalizeTemplateName((string)$config->template_name),
            'template_version' => max(1, (int)$config->template_version),
            'is_default' => (int)$config->is_default === 1 ? 1 : 0,
            'status' => (int)$config->status === self::CONFIG_STATUS_DISABLED ? self::CONFIG_STATUS_DISABLED : self::CONFIG_STATUS_ACTIVE,
            'sort' => (int)$config->sort,
            'design_version' => self::RENDER_SPEC_VERSION,
            'update_time' => (int)$config->update_time,
        ];
    }

    protected static function ensureDefaultConfig(int $staffId): StaffScheduleConfirmLetterConfig
    {
        $config = self::defaultConfigRecord($staffId, true);
        if ($config) {
            return $config;
        }
        /** @var StaffScheduleConfirmLetterConfig|null $anyConfig */
        $anyConfig = StaffScheduleConfirmLetterConfig::where('staff_id', $staffId)
            ->order('id', 'asc')
            ->find();
        if ($anyConfig) {
            $anyConfig->save([
                'template_name' => self::normalizeTemplateName((string)$anyConfig->template_name),
                'template_version' => max(1, (int)$anyConfig->template_version),
                'is_default' => 1,
                'status' => self::CONFIG_STATUS_ACTIVE,
                'update_time' => time(),
            ]);
            self::clearOtherDefaultConfigs($staffId, (int)$anyConfig->id);
            return $anyConfig;
        }

        return self::createDefaultConfig($staffId);
    }

    protected static function createDefaultConfig(int $staffId): StaffScheduleConfirmLetterConfig
    {
        $payload = self::normalizeConfigPayload($staffId, []);
        $now = time();
        /** @var StaffScheduleConfirmLetterConfig $config */
        $config = StaffScheduleConfirmLetterConfig::create(array_merge($payload, [
            'staff_id' => $staffId,
            'template_name' => '默认海报',
            'template_version' => 1,
            'is_default' => 1,
            'status' => self::CONFIG_STATUS_ACTIVE,
            'sort' => 0,
            'create_time' => $now,
            'update_time' => $now,
        ]));
        return $config;
    }

    protected static function findConfig(int $staffId, int $configId = 0, bool $fallbackDefault = false, bool $lock = false): ?StaffScheduleConfirmLetterConfig
    {
        $query = StaffScheduleConfirmLetterConfig::where('staff_id', $staffId);
        if ($lock) {
            $query->lock(true);
        }
        /** @var StaffScheduleConfirmLetterConfig|null $config */
        $config = $configId > 0
            ? $query->where('id', $configId)->find()
            : null;
        if (!$config && $fallbackDefault) {
            $config = self::defaultConfigRecord($staffId, true, $lock);
        }
        return $config;
    }

    protected static function defaultConfigRecord(int $staffId, bool $createIfMissing = false, bool $lock = false): ?StaffScheduleConfirmLetterConfig
    {
        $query = StaffScheduleConfirmLetterConfig::where('staff_id', $staffId)
            ->where('status', self::CONFIG_STATUS_ACTIVE)
            ->where('is_default', 1)
            ->order('sort', 'desc')
            ->order('id', 'asc');
        if ($lock) {
            $query->lock(true);
        }
        /** @var StaffScheduleConfirmLetterConfig|null $config */
        $config = $query->find();
        if (!$config) {
            $fallbackQuery = StaffScheduleConfirmLetterConfig::where('staff_id', $staffId)
                ->where('status', self::CONFIG_STATUS_ACTIVE)
                ->order('sort', 'desc')
                ->order('id', 'asc');
            if ($lock) {
                $fallbackQuery->lock(true);
            }
            $config = $fallbackQuery->find();
            if ($config) {
                $config->save(['is_default' => 1, 'update_time' => time()]);
                self::clearOtherDefaultConfigs($staffId, (int)$config->id);
            }
        }
        if (!$config && $createIfMissing) {
            return self::createDefaultConfig($staffId);
        }
        return $config;
    }

    protected static function clearOtherDefaultConfigs(int $staffId, int $keepConfigId): void
    {
        StaffScheduleConfirmLetterConfig::where('staff_id', $staffId)
            ->where('id', '<>', $keepConfigId)
            ->where('is_default', 1)
            ->update([
                'is_default' => 0,
                'update_time' => time(),
            ]);
    }

    protected static function activeConfigCount(int $staffId): int
    {
        return (int)StaffScheduleConfirmLetterConfig::where('staff_id', $staffId)
            ->where('status', self::CONFIG_STATUS_ACTIVE)
            ->count();
    }

    protected static function nextTemplateVersion(int $staffId): int
    {
        return (int)StaffScheduleConfirmLetterConfig::where('staff_id', $staffId)->max('template_version') + 1;
    }

    protected static function buildSnapshot(Order $order, OrderItem $item, Staff $staff, array $config): array
    {
        $serviceDate = trim((string)($item->service_date ?: $order->service_date));
        if ($serviceDate === '') {
            throw new \RuntimeException(self::ERROR_SERVICE_DATE);
        }

        $serviceName = self::resolveServiceName($item);
        $cityLabel = self::resolveCityLabel($order);
        $customerAlias = (int)$config['show_customer_alias'] === 1
            ? self::maskCustomerAlias((string)$order->contact_name)
            : '新人';
        $variables = [
            'service_date_label' => self::formatServiceDateLabel($serviceDate),
            'customer_alias' => $customerAlias,
            'service_name' => (int)$config['show_service_name'] === 1 ? $serviceName : '婚礼服务',
            'city_label' => (int)$config['show_city'] === 1 ? $cityLabel : '',
            'staff_name' => trim((string)$staff->name),
        ];
        $contentText = self::renderContentTemplate((string)$config['content_template'], $variables);
        $qrcodeImage = self::requireGlobalQrcodeImage();
        $designConfig = self::buildSnapshotDesignConfig($config, $qrcodeImage);

        return [
            'render_spec_version' => self::RENDER_SPEC_VERSION,
            'design_version' => self::RENDER_SPEC_VERSION,
            'config_id' => (int)($config['config_id'] ?? 0),
            'config_name' => (string)($config['template_name'] ?? '默认海报'),
            'config_template_version' => (int)($config['template_version'] ?? 1),
            'design_config' => $designConfig,
            'variables' => $variables,
            'title' => (string)$config['title'],
            'subtitle' => (string)$config['subtitle'],
            'content_template' => (string)$config['content_template'],
            'content_text' => $contentText,
            'footer_note' => (string)$config['footer_note'],
            'background_type' => (string)$config['background_type'],
            'background_image' => (string)$config['background_type'] === 'image'
                ? self::formatPublicImageUrl((string)$config['background_image'])
                : '',
            'background_color' => (string)$config['background_color'],
            'text_theme' => (string)$config['text_theme'],
            'show_customer_alias' => (int)$config['show_customer_alias'],
            'show_service_name' => (int)$config['show_service_name'],
            'show_city' => (int)$config['show_city'],
            'show_qrcode' => 1,
            'qrcode_image' => self::formatPublicImageUrl($qrcodeImage),
            'order_id' => (int)$order->id,
            'staff_id' => (int)$staff->id,
            'staff_name' => trim((string)$staff->name),
            'service_date_label' => self::formatServiceDateLabel($serviceDate),
            'customer_alias' => $customerAlias,
            'service_name' => (int)$config['show_service_name'] === 1 ? $serviceName : '',
            'city_label' => (int)$config['show_city'] === 1 ? $cityLabel : '',
            'confirm_date' => date('Y-m-d'),
        ];
    }

    protected static function buildPreviewSnapshot(array $config, ?Staff $staff): array
    {
        $staffName = trim((string)($staff->name ?? '服务人员'));
        $serviceDateLabel = date('Y年m月d日', strtotime('+30 days'));
        $customerAlias = (int)$config['show_customer_alias'] === 1 ? '张姓新人' : '新人';
        $serviceName = (int)$config['show_service_name'] === 1 ? '婚礼跟拍' : '';
        $cityLabel = (int)$config['show_city'] === 1 ? '杭州 西湖区' : '';
        $variables = [
            'service_date_label' => $serviceDateLabel,
            'customer_alias' => $customerAlias,
            'service_name' => $serviceName !== '' ? $serviceName : '婚礼服务',
            'city_label' => $cityLabel,
            'staff_name' => $staffName,
        ];
        $qrcodeImage = self::requireGlobalQrcodeImage();
        $designConfig = self::buildSnapshotDesignConfig($config, $qrcodeImage);

        return [
            'render_spec_version' => self::RENDER_SPEC_VERSION,
            'design_version' => self::RENDER_SPEC_VERSION,
            'config_id' => (int)($config['config_id'] ?? 0),
            'config_name' => (string)($config['template_name'] ?? '默认海报'),
            'config_template_version' => (int)($config['template_version'] ?? 1),
            'design_config' => $designConfig,
            'variables' => $variables,
            'title' => (string)$config['title'],
            'subtitle' => (string)$config['subtitle'],
            'content_template' => (string)$config['content_template'],
            'content_text' => self::renderContentTemplate((string)$config['content_template'], $variables),
            'footer_note' => (string)$config['footer_note'],
            'background_type' => (string)$config['background_type'],
            'background_image' => (string)$config['background_type'] === 'image'
                ? self::formatPublicImageUrl((string)$config['background_image'])
                : '',
            'background_color' => (string)$config['background_color'],
            'text_theme' => (string)$config['text_theme'],
            'show_customer_alias' => (int)$config['show_customer_alias'],
            'show_service_name' => (int)$config['show_service_name'],
            'show_city' => (int)$config['show_city'],
            'show_qrcode' => 1,
            'qrcode_image' => self::formatPublicImageUrl($qrcodeImage),
            'order_id' => 0,
            'staff_id' => (int)($config['staff_id'] ?? 0),
            'staff_name' => $staffName,
            'service_date_label' => $serviceDateLabel,
            'customer_alias' => $customerAlias,
            'service_name' => $serviceName,
            'city_label' => $cityLabel,
            'confirm_date' => date('Y-m-d'),
        ];
    }

    protected static function defaultDesignConfig(): array
    {
        return [
            'canvas' => [
                'width' => 1080,
                'height' => 1920,
            ],
            'background' => [
                'type' => 'color',
                'color' => '#191713',
                'image' => '',
                'fit' => 'cover',
                'opacity' => 1,
            ],
            'layers' => [
                [
                    'id' => 'frame',
                    'type' => 'rect',
                    'x' => 86,
                    'y' => 148,
                    'w' => 908,
                    'h' => 1624,
                    'z' => 1,
                    'visible' => 1,
                    'locked' => 0,
                    'opacity' => 0.38,
                    'rotate' => 0,
                    'fill' => '#12100D',
                    'stroke' => '#D8C08B',
                    'strokeWidth' => 2,
                    'radius' => 42,
                ],
                [
                    'id' => 'title',
                    'type' => 'text',
                    'x' => 180,
                    'y' => 310,
                    'w' => 720,
                    'h' => 130,
                    'z' => 2,
                    'visible' => 1,
                    'locked' => 0,
                    'opacity' => 1,
                    'rotate' => 0,
                    'text' => '档期已定',
                    'fontSize' => 96,
                    'fontWeight' => '700',
                    'lineHeight' => 1.18,
                    'align' => 'center',
                    'color' => '#FFF7E6',
                    'editable' => 1,
                    'field' => 'title',
                ],
                [
                    'id' => 'subtitle',
                    'type' => 'text',
                    'x' => 180,
                    'y' => 452,
                    'w' => 720,
                    'h' => 60,
                    'z' => 3,
                    'visible' => 1,
                    'locked' => 0,
                    'opacity' => 1,
                    'rotate' => 0,
                    'text' => 'SCHEDULE RESERVED',
                    'fontSize' => 30,
                    'fontWeight' => '500',
                    'lineHeight' => 1.2,
                    'align' => 'center',
                    'color' => '#D8C08B',
                    'editable' => 1,
                    'field' => 'subtitle',
                ],
                [
                    'id' => 'divider',
                    'type' => 'line',
                    'x' => 270,
                    'y' => 560,
                    'w' => 540,
                    'h' => 0,
                    'z' => 4,
                    'visible' => 1,
                    'locked' => 0,
                    'opacity' => 0.72,
                    'rotate' => 0,
                    'stroke' => '#D8C08B',
                    'strokeWidth' => 2,
                ],
                [
                    'id' => 'content',
                    'type' => 'text',
                    'x' => 160,
                    'y' => 720,
                    'w' => 760,
                    'h' => 360,
                    'z' => 5,
                    'visible' => 1,
                    'locked' => 0,
                    'opacity' => 1,
                    'rotate' => 0,
                    'text' => '{service_date_label}，{customer_alias}的{service_name}档期已确认，感谢信任。',
                    'fontSize' => 54,
                    'fontWeight' => '400',
                    'lineHeight' => 1.42,
                    'align' => 'center',
                    'color' => '#FFF7E6',
                    'editable' => 1,
                    'field' => 'content_template',
                ],
                [
                    'id' => 'date',
                    'type' => 'text',
                    'x' => 260,
                    'y' => 1110,
                    'w' => 560,
                    'h' => 60,
                    'z' => 6,
                    'visible' => 1,
                    'locked' => 0,
                    'opacity' => 1,
                    'rotate' => 0,
                    'text' => '{service_date_label}',
                    'fontSize' => 34,
                    'fontWeight' => '500',
                    'lineHeight' => 1.2,
                    'align' => 'center',
                    'color' => '#D8C08B',
                ],
                [
                    'id' => 'service',
                    'type' => 'text',
                    'x' => 260,
                    'y' => 1170,
                    'w' => 560,
                    'h' => 60,
                    'z' => 7,
                    'visible' => 1,
                    'locked' => 0,
                    'opacity' => 1,
                    'rotate' => 0,
                    'text' => '{service_name}',
                    'fontSize' => 34,
                    'fontWeight' => '500',
                    'lineHeight' => 1.2,
                    'align' => 'center',
                    'color' => '#D8C08B',
                ],
                [
                    'id' => 'city',
                    'type' => 'text',
                    'x' => 260,
                    'y' => 1230,
                    'w' => 560,
                    'h' => 60,
                    'z' => 8,
                    'visible' => 1,
                    'locked' => 0,
                    'opacity' => 1,
                    'rotate' => 0,
                    'text' => '{city_label}',
                    'fontSize' => 34,
                    'fontWeight' => '500',
                    'lineHeight' => 1.2,
                    'align' => 'center',
                    'color' => '#D8C08B',
                ],
                [
                    'id' => 'qrcode',
                    'type' => 'qrcode',
                    'x' => 438,
                    'y' => 1398,
                    'w' => self::QRCODE_DEFAULT_SIZE,
                    'h' => self::QRCODE_DEFAULT_SIZE,
                    'z' => 9,
                    'visible' => 1,
                    'locked' => 0,
                    'opacity' => 1,
                    'rotate' => 0,
                    'src' => '',
                    'padding' => 24,
                    'radius' => 18,
                    'editable' => 0,
                    'field' => '',
                ],
                [
                    'id' => 'footer',
                    'type' => 'text',
                    'x' => 180,
                    'y' => 1640,
                    'w' => 720,
                    'h' => 92,
                    'z' => 10,
                    'visible' => 1,
                    'locked' => 0,
                    'opacity' => 1,
                    'rotate' => 0,
                    'text' => '档期已预定，感谢信任与选择。',
                    'fontSize' => 30,
                    'fontWeight' => '400',
                    'lineHeight' => 1.45,
                    'align' => 'center',
                    'color' => '#B7A27A',
                    'editable' => 1,
                    'field' => 'footer_note',
                ],
                [
                    'id' => 'staff',
                    'type' => 'text',
                    'x' => 260,
                    'y' => 1780,
                    'w' => 560,
                    'h' => 48,
                    'z' => 11,
                    'visible' => 1,
                    'locked' => 0,
                    'opacity' => 1,
                    'rotate' => 0,
                    'text' => '{staff_name}',
                    'fontSize' => 28,
                    'fontWeight' => '400',
                    'lineHeight' => 1.2,
                    'align' => 'center',
                    'color' => '#B7A27A',
                ],
            ],
        ];
    }

    protected static function defaultEditableFields(): array
    {
        return [
            'title',
            'subtitle',
            'content_template',
            'footer_note',
            'background',
        ];
    }

    protected static function normalizeDesignConfig(array $design): array
    {
        if (empty($design)) {
            $design = self::defaultDesignConfig();
        }
        $default = self::defaultDesignConfig();
        $canvas = is_array($design['canvas'] ?? null) ? $design['canvas'] : $default['canvas'];
        $background = is_array($design['background'] ?? null) ? $design['background'] : $default['background'];
        $layers = is_array($design['layers'] ?? null) ? $design['layers'] : $default['layers'];

        return [
            'canvas' => [
                'width' => 1080,
                'height' => 1920,
            ],
            'background' => [
                'type' => in_array((string)($background['type'] ?? 'color'), ['color', 'image'], true) ? (string)$background['type'] : 'color',
                'color' => self::normalizeColor((string)($background['color'] ?? '#191713'), '#191713'),
                'image' => self::normalizeStoredFileUrl((string)($background['image'] ?? '')),
                'fit' => self::normalizeBackgroundFit((string)($background['fit'] ?? 'cover')),
                'opacity' => self::clampFloat((float)($background['opacity'] ?? 1), 0, 1),
            ],
            'layers' => self::normalizeDesignLayers($layers),
        ];
    }

    protected static function normalizeDesignLayers(array $layers): array
    {
        $normalized = [];
        $hasQrcode = false;
        $allowedTypes = ['text', 'image', 'qrcode', 'rect', 'line'];
        foreach (array_slice($layers, 0, 80) as $index => $layer) {
            if (!is_array($layer)) {
                continue;
            }
            $type = in_array((string)($layer['type'] ?? 'text'), $allowedTypes, true) ? (string)$layer['type'] : 'text';
            $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($layer['id'] ?? ''));
            if ($id === '') {
                $id = 'layer_' . ($index + 1);
            }
            $base = [
                'id' => substr($id, 0, 64),
                'type' => $type,
                'x' => self::clampInt((int)($layer['x'] ?? 0), -2160, 4320),
                'y' => self::clampInt((int)($layer['y'] ?? 0), -3840, 7680),
                'w' => self::clampInt((int)($layer['w'] ?? 120), 1, 2160),
                'h' => $type === 'line'
                    ? self::clampInt((int)($layer['h'] ?? 0), 0, 3840)
                    : self::clampInt((int)($layer['h'] ?? 80), 1, 3840),
                'z' => self::clampInt((int)($layer['z'] ?? $index), -999, 999),
                'visible' => self::toSwitch($layer['visible'] ?? 1),
                'locked' => self::toSwitch($layer['locked'] ?? 0),
                'opacity' => $type === 'qrcode' ? 1 : self::clampFloat((float)($layer['opacity'] ?? 1), 0, 1),
                'rotate' => self::clampFloat((float)($layer['rotate'] ?? 0), -360, 360),
            ];
            if ($type === 'text') {
                $base += [
                    'text' => self::sanitizeTemplateText((string)($layer['text'] ?? '')),
                    'fontSize' => self::clampInt((int)($layer['fontSize'] ?? 42), 12, 180),
                    'fontWeight' => in_array((string)($layer['fontWeight'] ?? '400'), ['300', '400', '500', '600', '700', '800', '900'], true) ? (string)$layer['fontWeight'] : '400',
                    'lineHeight' => self::clampFloat((float)($layer['lineHeight'] ?? 1.35), 0.8, 3),
                    'align' => in_array((string)($layer['align'] ?? 'center'), ['left', 'center', 'right'], true) ? (string)$layer['align'] : 'center',
                    'color' => self::normalizeColor((string)($layer['color'] ?? '#FFF7E6'), '#FFF7E6'),
                    'editable' => self::toSwitch($layer['editable'] ?? 0),
                    'field' => self::normalizeEditableField((string)($layer['field'] ?? '')),
                ];
            } elseif ($type === 'image') {
                $base += [
                    'src' => self::normalizeStoredFileUrl((string)($layer['src'] ?? '')),
                    'fit' => (string)($layer['fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover',
                    'backgroundFill' => self::normalizeOptionalColor((string)($layer['backgroundFill'] ?? '')),
                    'radius' => self::clampInt((int)($layer['radius'] ?? 0), 0, 240),
                    'editable' => self::toSwitch($layer['editable'] ?? 0),
                    'field' => self::normalizeEditableField((string)($layer['field'] ?? '')),
                ];
            } elseif ($type === 'qrcode') {
                if ($hasQrcode) {
                    continue;
                }
                $hasQrcode = true;
                $base['id'] = 'qrcode';
                $base['w'] = self::clampInt((int)($layer['w'] ?? self::QRCODE_DEFAULT_SIZE), self::QRCODE_MIN_SIZE, 2160);
                $base['h'] = self::clampInt((int)($layer['h'] ?? self::QRCODE_DEFAULT_SIZE), self::QRCODE_MIN_SIZE, 3840);
                $base['visible'] = 1;
                $base += [
                    'src' => '',
                    'padding' => self::clampInt((int)($layer['padding'] ?? 18), 0, 80),
                    'radius' => self::clampInt((int)($layer['radius'] ?? 18), 0, 120),
                    'editable' => 0,
                    'field' => '',
                ];
            } elseif ($type === 'rect') {
                $base += [
                    'fill' => self::normalizeColor((string)($layer['fill'] ?? '#FFFFFF'), '#FFFFFF'),
                    'stroke' => self::normalizeColor((string)($layer['stroke'] ?? ''), ''),
                    'strokeWidth' => self::clampInt((int)($layer['strokeWidth'] ?? 0), 0, 40),
                    'radius' => self::clampInt((int)($layer['radius'] ?? 0), 0, 240),
                ];
            } else {
                $base += [
                    'stroke' => self::normalizeColor((string)($layer['stroke'] ?? '#D8C08B'), '#D8C08B'),
                    'strokeWidth' => self::clampInt((int)($layer['strokeWidth'] ?? 2), 1, 40),
                ];
            }
            $normalized[] = $base;
        }

        if (empty($normalized)) {
            return self::defaultDesignConfig()['layers'];
        }

        if (!$hasQrcode) {
            $normalized[] = self::defaultQrcodeLayer(self::nextLayerZ($normalized));
        }

        return $normalized;
    }

    protected static function formatDesignConfigForClient(array $design): array
    {
        $design = self::normalizeDesignConfig($design);
        $design['background']['image_url'] = self::formatPublicImageUrl((string)$design['background']['image']);
        foreach ($design['layers'] as &$layer) {
            if (($layer['type'] ?? '') === 'qrcode') {
                $layer['src_url'] = self::formatPublicImageUrl(self::getGlobalQrcodeImage());
                continue;
            }
            if (($layer['type'] ?? '') === 'image') {
                $layer['src_url'] = self::formatPublicImageUrl((string)($layer['src'] ?? ''));
            }
        }
        unset($layer);
        return $design;
    }

    protected static function buildSnapshotDesignConfig(array $config, string $qrcodeImage = ''): array
    {
        $design = self::normalizeDesignConfig(is_array($config['design_config'] ?? null) ? $config['design_config'] : []);
        $design['background']['image'] = (string)$design['background']['type'] === 'image'
            ? self::formatPublicImageUrl((string)$design['background']['image'])
            : '';
        $design['background']['fit'] = self::normalizeBackgroundFit((string)($design['background']['fit'] ?? 'cover'));
        $qrcodeImage = $qrcodeImage !== '' ? $qrcodeImage : self::requireGlobalQrcodeImage();
        $qrcodePublicUrl = self::formatPublicImageUrl($qrcodeImage);
        foreach ($design['layers'] as &$layer) {
            if (($layer['type'] ?? '') === 'image') {
                $layer['src'] = self::formatPublicImageUrl((string)($layer['src'] ?? ''));
            }
            if (($layer['type'] ?? '') === 'qrcode') {
                $layer['src'] = $qrcodePublicUrl;
                $layer['visible'] = 1;
                $layer['editable'] = 0;
                $layer['field'] = '';
                $layer['w'] = self::clampInt((int)($layer['w'] ?? self::QRCODE_DEFAULT_SIZE), self::QRCODE_MIN_SIZE, 2160);
                $layer['h'] = self::clampInt((int)($layer['h'] ?? self::QRCODE_DEFAULT_SIZE), self::QRCODE_MIN_SIZE, 3840);
            }
        }
        unset($layer);
        return $design;
    }

    protected static function mergeLiteDesignConfig(array $design, array $params): array
    {
        $design = self::normalizeDesignConfig($design);
        if (isset($params['background_type'])) {
            $design['background']['type'] = in_array((string)$params['background_type'], ['color', 'image'], true) ? (string)$params['background_type'] : $design['background']['type'];
        }
        if (isset($params['background_color'])) {
            $design['background']['color'] = self::normalizeColor((string)$params['background_color'], (string)$design['background']['color']);
        }
        if (isset($params['background_image'])) {
            $design['background']['image'] = self::normalizeStoredFileUrl((string)$params['background_image']);
        }
        if (isset($params['background_fit'])) {
            $design['background']['fit'] = self::normalizeBackgroundFit((string)$params['background_fit']);
        }

        $fieldMap = [
            'title' => 'title',
            'subtitle' => 'subtitle',
            'content_template' => 'content_template',
            'footer_note' => 'footer_note',
        ];
        foreach ($design['layers'] as &$layer) {
            $field = (string)($layer['field'] ?? '');
            if ((int)($layer['editable'] ?? 0) !== 1 || !isset($fieldMap[$field])) {
                continue;
            }
            $paramKey = $fieldMap[$field];
            if (!array_key_exists($paramKey, $params)) {
                continue;
            }
            if (($layer['type'] ?? '') === 'qrcode') {
                $layer['src'] = '';
                $layer['visible'] = 1;
                $layer['editable'] = 0;
                $layer['field'] = '';
            } elseif (($layer['type'] ?? '') === 'text') {
                $layer['text'] = self::sanitizeTemplateText((string)$params[$paramKey]);
            }
        }
        unset($layer);

        return self::normalizeDesignConfig($design);
    }

    protected static function extractEditableFields(array $design): array
    {
        $fields = ['background'];
        foreach ($design['layers'] ?? [] as $layer) {
            if (($layer['type'] ?? '') === 'qrcode') {
                continue;
            }
            if ((int)($layer['editable'] ?? 0) === 1 && !empty($layer['field'])) {
                $fields[] = (string)$layer['field'];
            }
        }
        return array_values(array_unique(array_filter($fields)));
    }

    protected static function defaultQrcodeLayer(int $z = 9): array
    {
        return [
            'id' => 'qrcode',
            'type' => 'qrcode',
            'x' => 438,
            'y' => 1398,
            'w' => self::QRCODE_DEFAULT_SIZE,
            'h' => self::QRCODE_DEFAULT_SIZE,
            'z' => $z,
            'visible' => 1,
            'locked' => 0,
            'opacity' => 1,
            'rotate' => 0,
            'src' => '',
            'padding' => 18,
            'radius' => 18,
            'editable' => 0,
            'field' => '',
        ];
    }

    protected static function nextLayerZ(array $layers): int
    {
        if (empty($layers)) {
            return 9;
        }
        return max(array_map(static fn($layer) => (int)($layer['z'] ?? 0), $layers)) + 1;
    }

    protected static function normalizeBackgroundFit(string $fit): string
    {
        return $fit === 'contain' ? 'contain' : 'cover';
    }

    protected static function getGlobalQrcodeImage(): string
    {
        return self::normalizeStoredFileUrl((string)ConfigService::get(
            OrderConfirmLetterService::CONFIG_GROUP,
            self::CONFIG_KEY_SCHEDULE_QRCODE_IMAGE,
            ''
        ));
    }

    protected static function requireGlobalQrcodeImage(): string
    {
        $image = self::getGlobalQrcodeImage();
        if ($image === '') {
            throw new \RuntimeException(self::ERROR_QRCODE_MISSING);
        }
        return $image;
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

    protected static function resolveStaffOrderItem(Order $order, int $staffId): OrderItem
    {
        foreach ($order->items as $item) {
            if ((int)$item->staff_id !== $staffId) {
                continue;
            }
            if (!in_array((int)$item->item_type, [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF], true)) {
                continue;
            }
            if ((int)$item->item_status === OrderItem::STATUS_CANCELLED) {
                continue;
            }
            return $item;
        }

        throw new \RuntimeException(self::ERROR_NOT_BOUND);
    }

    protected static function checkGenerateQualification(Order $order, OrderItem $item): void
    {
        if (in_array((int)$order->order_status, [
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
            Order::STATUS_USER_DELETED,
            Order::STATUS_REFUNDING,
        ], true)) {
            throw new \RuntimeException(self::ERROR_ORDER_STATUS);
        }

        $hasPaid = OrderConfirmLetterService::calculateEffectivePaidAmount((int)$order->id) > 0;
        $hasLock = (int)$item->confirm_status === 1
            || (int)$order->order_status >= Order::STATUS_PENDING_PAY;
        if (!$hasPaid && !$hasLock) {
            throw new \RuntimeException(self::ERROR_NOT_LOCKED);
        }
    }

    protected static function buildSnapshotHash(array $snapshot): string
    {
        return hash('sha256', self::RENDER_SPEC_VERSION . '|' . json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected static function ensurePersistedAssets(StaffScheduleConfirmLetter $letter, bool $force = false): void
    {
        if (!$force && self::hasPersistedAssets($letter)) {
            return;
        }

        $snapshot = is_array($letter->rendered_snapshot) ? $letter->rendered_snapshot : [];
        if (empty($snapshot)) {
            throw new \RuntimeException(self::ERROR_ASSET_RENDER);
        }

        $svgContent = StaffScheduleConfirmLetterRenderer::render($snapshot, [
            'font_options' => OrderConfirmLetterFontService::getActiveFontOptions(),
        ]);
        $assets = self::persistSvgAssets((int)$letter->order_id, (int)$letter->staff_id, (string)$letter->snapshot_hash, $svgContent);
        $fullImageUrl = self::normalizeStoredFileUrl((string)($assets['full_image_url'] ?? ''));
        if ($fullImageUrl === '') {
            throw new \RuntimeException(self::ERROR_ASSET_RENDER);
        }

        $thumbImageUrl = self::normalizeStoredFileUrl((string)($assets['thumb_image_url'] ?? ''));
        $letter->full_image_url = $fullImageUrl;
        $letter->thumb_image_url = $thumbImageUrl !== '' ? $thumbImageUrl : $fullImageUrl;
        $letter->update_time = time();
        $letter->save();
    }

    protected static function persistSvgAssets(int $orderId, int $staffId, string $snapshotHash, string $svgContent): array
    {
        $svgContent = trim($svgContent);
        if ($svgContent === '' || stripos($svgContent, '<svg') === false) {
            throw new \RuntimeException(self::ERROR_ASSET_RENDER);
        }

        $folder = self::ASSET_STORAGE_DIR . '/' . date('Ym');
        $hash = preg_replace('/[^a-z0-9]/i', '', $snapshotHash);
        $hash = $hash !== '' ? substr($hash, 0, 24) : substr(md5($svgContent), 0, 24);
        $fileName = sprintf(
            'order-%d-staff-%d-%s-%s.%s',
            $orderId,
            $staffId,
            $hash,
            self::ASSET_FILE_VERSION,
            self::ASSET_FILE_EXTENSION
        );

        if (self::getStorageDefault() !== 'local') {
            return self::persistSvgAssetsToCloud($folder, $fileName, $svgContent);
        }

        return self::persistSvgAssetsToLocal($folder, $fileName, $svgContent);
    }

    protected static function persistSvgAssetsToLocal(string $folder, string $fileName, string $svgContent): array
    {
        $relativePath = $folder . '/' . $fileName;
        $absolutePath = FileService::getFileUrl($relativePath, 'public_path');
        self::ensureAssetDirectory(dirname($absolutePath));
        self::rasterizeSvgAssets($svgContent, $absolutePath);

        return [
            'full_image_url' => $relativePath,
            'thumb_image_url' => $relativePath,
        ];
    }

    protected static function persistSvgAssetsToCloud(string $folder, string $fileName, string $svgContent): array
    {
        $storage = self::getStorageDefault();
        $tempPath = self::buildTempAssetPath($fileName);

        try {
            self::rasterizeSvgAssets($svgContent, $tempPath);
            $storageConfig = ConfigService::get('storage') ?? ['local' => []];
            if (empty($storageConfig[$storage]) || !is_array($storageConfig[$storage])) {
                throw new \RuntimeException(self::ERROR_ASSET_UPLOAD);
            }

            $storageDriver = new StorageDriver([
                'default' => $storage,
                'engine' => $storageConfig,
            ]);
            $storageDriver->setUploadFileByReal($tempPath, $fileName);
            $uploadedFileName = str_replace('\\', '/', (string)$storageDriver->getFileName());
            if (!$storageDriver->upload($folder)) {
                throw new \RuntimeException(self::ERROR_ASSET_UPLOAD);
            }

            $relativePath = $folder . '/' . $uploadedFileName;
            return [
                'full_image_url' => $relativePath,
                'thumb_image_url' => $relativePath,
            ];
        } catch (\Throwable $e) {
            self::logAssetFailure('档期确认函图片上传失败', [
                'storage' => $storage,
                'folder' => $folder,
                'file_name' => $fileName,
                'error' => $e->getMessage(),
            ]);
            if ($e instanceof \RuntimeException && in_array($e->getMessage(), [
                self::ERROR_ASSET_RUNTIME,
                self::ERROR_ASSET_RENDER,
                self::ERROR_ASSET_UPLOAD,
                self::ERROR_ASSET_FONT_FILE_MISSING,
                self::ERROR_ASSET_FONT_FILE_UNREADABLE,
                self::ERROR_ASSET_FONT_RENDER,
            ], true)) {
                throw $e;
            }
            throw new \RuntimeException(self::ERROR_ASSET_UPLOAD, 0, $e);
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
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
            $svgContent = self::prepareSvgForRasterization($svgContent);
            $imagick->setResolution(self::ASSET_RASTER_RESOLUTION, self::ASSET_RASTER_RESOLUTION);
            $imagick->setBackgroundColor(new \ImagickPixel('white'));
            [$backgroundSvg, $textItems, $canvas] = self::stripSvgTextItems($svgContent);
            $imagick->readImageBlob($backgroundSvg);
            self::flattenImageForJpeg($imagick);
            self::drawSvgTextItems($imagick, $textItems, $canvas);
            self::configureJpegOutput($imagick);
            if (!$imagick->writeImage($absolutePath)) {
                throw new \RuntimeException(self::ERROR_ASSET_RENDER);
            }
        } catch (\Throwable $e) {
            @unlink($absolutePath);
            if ($e instanceof \RuntimeException && in_array($e->getMessage(), [
                self::ERROR_ASSET_RUNTIME,
                self::ERROR_ASSET_RENDER,
                self::ERROR_ASSET_FONT_FILE_MISSING,
                self::ERROR_ASSET_FONT_FILE_UNREADABLE,
                self::ERROR_ASSET_FONT_RENDER,
            ], true)) {
                throw $e;
            }
            throw new \RuntimeException(self::ERROR_ASSET_RENDER, 0, $e);
        } finally {
            $imagick->clear();
            $imagick->destroy();
        }
    }

    protected static function flattenImageForJpeg(\Imagick $imagick): void
    {
        if (defined('\Imagick::ALPHACHANNEL_REMOVE')) {
            $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
        }

        if (method_exists($imagick, 'mergeImageLayers')) {
            try {
                $flattened = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                if ($flattened instanceof \Imagick) {
                    $imagick->clear();
                    $imagick->addImage($flattened);
                    $flattened->clear();
                    $flattened->destroy();
                }
            } catch (\Throwable $e) {
                // 旧版本 Imagick 压平失败时继续使用当前图层，后续 JPEG 会自动丢弃透明通道。
            }
        }
    }

    protected static function configureJpegOutput(\Imagick $imagick): void
    {
        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
        $imagick->setImageCompressionQuality(self::ASSET_JPEG_QUALITY);
        $imagick->stripImage();
        if (method_exists($imagick, 'setInterlaceScheme')) {
            $imagick->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
        }
    }

    protected static function stripSvgTextItems(string $svgContent): array
    {
        if (!class_exists(\DOMDocument::class)) {
            return [$svgContent, [], []];
        }

        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadXML($svgContent, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);
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
                $canvas['width'] = (float)$viewBoxParts[2];
                $canvas['height'] = (float)$viewBoxParts[3];
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
            $text = trim((string)$node->textContent);
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

        $backgroundSvg = $document->documentElement ? $document->saveXML($document->documentElement) : false;
        return [is_string($backgroundSvg) ? $backgroundSvg : $svgContent, $textItems, $canvas];
    }

    protected static function drawSvgTextItems(\Imagick $imagick, array $textItems, array $canvas = []): void
    {
        if (empty($textItems)) {
            return;
        }

        $scaleX = !empty($canvas['width']) ? $imagick->getImageWidth() / (float)$canvas['width'] : 1.0;
        $scaleY = !empty($canvas['height']) ? $imagick->getImageHeight() / (float)$canvas['height'] : $scaleX;
        $scale = ($scaleX + $scaleY) / 2;
        $fontOptions = OrderConfirmLetterFontService::getActiveFontOptions();

        foreach ($textItems as $item) {
            $text = (string)($item['text'] ?? '');
            if ($text === '') {
                continue;
            }

            $draw = new \ImagickDraw();
            try {
                $fontPath = self::resolveTextItemFontPath((string)($item['font_family'] ?? ''), $fontOptions);
                if ($fontPath === '' || !is_file($fontPath)) {
                    self::logAssetFailure('档期确认函字体文件不存在', [
                        'font_family' => (string)($item['font_family'] ?? ''),
                        'font_options' => self::formatFontOptionsForLog($fontOptions),
                    ]);
                    throw new \RuntimeException(self::ERROR_ASSET_FONT_FILE_MISSING);
                }
                if (!is_readable($fontPath)) {
                    self::logAssetFailure('档期确认函字体文件不可读', [
                        'font_family' => (string)($item['font_family'] ?? ''),
                        'font_path' => $fontPath,
                    ]);
                    throw new \RuntimeException(self::ERROR_ASSET_FONT_FILE_UNREADABLE);
                }

                $fontSize = max(1.0, (float)($item['font_size'] ?? 16.0) * $scale);
                $draw->setFont($fontPath);
                $draw->setFontSize($fontSize);
                $draw->setFillColor(new \ImagickPixel((string)($item['fill'] ?? '#000000')));

                $x = ((float)($item['x'] ?? 0.0) + (float)($item['translate']['x'] ?? 0.0)) * $scaleX;
                $y = ((float)($item['y'] ?? 0.0) + (float)($item['translate']['y'] ?? 0.0)) * $scaleY;
                $letterSpacing = (float)($item['letter_spacing'] ?? 0.0) * $scale;
                $textAnchor = (string)($item['text_anchor'] ?? 'start');
                if ($letterSpacing !== 0.0 && self::isAsciiText($text)) {
                    self::drawTextWithLetterSpacing($imagick, $draw, $text, $x, $y, $letterSpacing, $textAnchor);
                    continue;
                }

                if ($textAnchor === 'middle') {
                    $metrics = $imagick->queryFontMetrics($draw, $text);
                    $x -= (float)($metrics['textWidth'] ?? 0) / 2;
                } elseif ($textAnchor === 'end') {
                    $metrics = $imagick->queryFontMetrics($draw, $text);
                    $x -= (float)($metrics['textWidth'] ?? 0);
                }
                $imagick->annotateImage($draw, $x, $y, 0, $text);
            } finally {
                $draw->clear();
                $draw->destroy();
            }
        }
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
            $advance = (float)($metrics['textWidth'] ?? 0);
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
            $imagick->annotateImage($draw, $x, $y, 0, (string)$char);
            $x += (float)$advance + $letterSpacing;
        }
    }

    protected static function resolveTextItemFontPath(string $fontFamily, array $fontOptions): string
    {
        $serifFamily = (string)($fontOptions['serif_family'] ?? 'OrderConfirmLetterSerif');
        $sansPath = (string)($fontOptions['sans_path'] ?? '');
        $serifPath = (string)($fontOptions['serif_path'] ?? '');
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
        return (float)$matches[0];
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
                    $parts = preg_split('/[\s,]+/', trim((string)$translate)) ?: [];
                    $x += isset($parts[0]) ? (float)$parts[0] : 0.0;
                    $y += isset($parts[1]) ? (float)$parts[1] : 0.0;
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

    protected static function ensureImagickFontReady(): void
    {
        $fontOptions = OrderConfirmLetterFontService::getActiveFontOptions();
        foreach (['sans_path', 'serif_path'] as $pathKey) {
            $path = (string)($fontOptions[$pathKey] ?? '');
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
            self::logAssetFailure('档期确认函中文字体渲染检测失败', [
                'font_options' => self::formatFontOptionsForLog($fontOptions),
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException(self::ERROR_ASSET_FONT_RENDER, 0, $e);
        }
    }

    protected static function formatFontOptionsForLog(array $fontOptions): array
    {
        return [
            'sans_file' => (string)($fontOptions['sans_file'] ?? ''),
            'serif_file' => (string)($fontOptions['serif_file'] ?? ''),
            'sans_path' => (string)($fontOptions['sans_path'] ?? ''),
            'serif_path' => (string)($fontOptions['serif_path'] ?? ''),
            'font_hash' => (string)(OrderConfirmLetterFontService::getActiveFontSignature($fontOptions)['hash'] ?? ''),
        ];
    }

    protected static function prepareSvgForRasterization(string $svgContent): string
    {
        if (stripos($svgContent, '<image') === false) {
            return $svgContent;
        }

        if (class_exists(\DOMDocument::class)) {
            $previousUseInternalErrors = libxml_use_internal_errors(true);
            $document = new \DOMDocument('1.0', 'UTF-8');
            $loaded = $document->loadXML($svgContent, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseInternalErrors);

            if ($loaded) {
                $changed = false;
                /** @var \DOMElement $image */
                foreach ($document->getElementsByTagName('image') as $image) {
                    $href = self::getSvgImageHref($image);
                    if ($href === '') {
                        continue;
                    }
                    $nextHref = self::resolveRasterizableImageHref($href);
                    if ($nextHref === '' || $nextHref === $href) {
                        continue;
                    }
                    self::setSvgImageHref($image, $nextHref);
                    $changed = true;
                }

                if ($changed && $document->documentElement) {
                    return (string)$document->saveXML($document->documentElement);
                }
                return $svgContent;
            }
        }

        return self::prepareSvgForRasterizationByRegex($svgContent);
    }

    protected static function prepareSvgForRasterizationByRegex(string $svgContent): string
    {
        return (string)preg_replace_callback(
            '/(<image\b[^>]*?\s(?:href|xlink:href)=)(["\'])(.*?)(\2)/i',
            static function (array $matches): string {
                $nextHref = self::resolveRasterizableImageHref((string)$matches[3]);
                if ($nextHref === '' || $nextHref === (string)$matches[3]) {
                    return (string)$matches[0];
                }
                return (string)$matches[1]
                    . (string)$matches[2]
                    . htmlspecialchars($nextHref, ENT_QUOTES | ENT_XML1, 'UTF-8')
                    . (string)$matches[4];
            },
            $svgContent
        );
    }

    protected static function getSvgImageHref(\DOMElement $image): string
    {
        $href = trim((string)$image->getAttribute('href'));
        if ($href !== '') {
            return html_entity_decode($href, ENT_QUOTES | ENT_XML1, 'UTF-8');
        }

        $xlinkHref = trim((string)$image->getAttributeNS('http://www.w3.org/1999/xlink', 'href'));
        if ($xlinkHref !== '') {
            return html_entity_decode($xlinkHref, ENT_QUOTES | ENT_XML1, 'UTF-8');
        }

        $legacyHref = trim((string)$image->getAttribute('xlink:href'));
        return $legacyHref !== '' ? html_entity_decode($legacyHref, ENT_QUOTES | ENT_XML1, 'UTF-8') : '';
    }

    protected static function setSvgImageHref(\DOMElement $image, string $href): void
    {
        if ($image->hasAttribute('href')) {
            $image->setAttribute('href', $href);
            return;
        }

        if ($image->hasAttributeNS('http://www.w3.org/1999/xlink', 'href')) {
            $image->setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', $href);
            return;
        }

        if ($image->hasAttribute('xlink:href')) {
            $image->setAttribute('xlink:href', $href);
            return;
        }

        $image->setAttribute('href', $href);
    }

    protected static function resolveRasterizableImageHref(string $href): string
    {
        $href = trim(html_entity_decode($href, ENT_QUOTES | ENT_XML1, 'UTF-8'));
        if ($href === '' || preg_match('/^data:/i', $href) === 1) {
            return $href;
        }

        $localPath = self::resolveSvgLocalImagePath($href);
        if ($localPath !== '') {
            $dataUri = self::buildImageDataUriFromLocalPath($localPath);
            if ($dataUri !== '') {
                return $dataUri;
            }
        }

        if (preg_match('/^https?:\/\//i', $href) === 1) {
            $dataUri = self::downloadRemoteImageDataUri($href);
            if ($dataUri !== '') {
                return $dataUri;
            }
        }

        self::logAssetFailure('档期确认函图片引用无法内联，已使用透明占位避免整图渲染失败', [
            'href' => self::sanitizeAssetHrefForLog($href),
        ]);
        return self::SVG_TRANSPARENT_PIXEL;
    }

    protected static function resolveSvgLocalImagePath(string $href): string
    {
        $path = '';
        $decodedHref = trim(html_entity_decode($href, ENT_QUOTES | ENT_XML1, 'UTF-8'));
        if (preg_match('/^file:\/\//i', $decodedHref) === 1) {
            $path = (string)parse_url($decodedHref, PHP_URL_PATH);
        } elseif (preg_match('/^https?:\/\//i', $decodedHref) === 1) {
            $path = (string)parse_url($decodedHref, PHP_URL_PATH);
        } elseif (str_starts_with($decodedHref, '//')) {
            $path = (string)parse_url('https:' . $decodedHref, PHP_URL_PATH);
        } else {
            $path = (string)(parse_url($decodedHref, PHP_URL_PATH) ?: $decodedHref);
        }

        $path = rawurldecode(str_replace('\\', '/', trim($path)));
        if ($path === '') {
            return '';
        }

        $relativePath = ltrim($path, '/');
        if (!self::isSafeRelativeAssetPath($relativePath)) {
            return '';
        }

        $publicPath = rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        clearstatcache(true, $publicPath);
        return is_file($publicPath) && is_readable($publicPath) ? $publicPath : '';
    }

    protected static function isSafeRelativeAssetPath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        foreach (explode('/', str_replace('\\', '/', $path)) as $part) {
            if ($part === '..') {
                return false;
            }
        }
        return true;
    }

    protected static function buildImageDataUriFromLocalPath(string $path): string
    {
        clearstatcache(true, $path);
        $size = is_file($path) ? (int)filesize($path) : 0;
        if ($size <= 0 || $size > self::SVG_IMAGE_MAX_BYTES || !is_readable($path)) {
            return '';
        }

        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            return '';
        }

        $mime = self::detectImageMime($content, $path);
        if (!self::isEmbeddableImageMime($mime)) {
            return '';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    protected static function downloadRemoteImageDataUri(string $url): string
    {
        if (!extension_loaded('curl') || !function_exists('curl_init')) {
            return '';
        }
        if (!self::isSafeRemoteImageUrl($url)) {
            self::logAssetFailure('档期确认函远程图片地址不允许下载', [
                'href' => self::sanitizeAssetHrefForLog($url),
            ]);
            return '';
        }

        return self::downloadRemoteImageDataUriWithRedirects($url, 0);
    }

    protected static function downloadRemoteImageDataUriWithRedirects(string $url, int $redirectCount): string
    {
        if ($redirectCount > 3 || !self::isSafeRemoteImageUrl($url)) {
            return '';
        }

        $buffer = '';
        $location = '';
        $curl = curl_init($url);
        if ($curl === false) {
            return '';
        }

        curl_setopt_array($curl, [
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'GelinshePosterRenderer/1.0',
            CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use (&$location): int {
                if (stripos($headerLine, 'Location:') === 0) {
                    $location = trim(substr($headerLine, 9));
                }
                return strlen($headerLine);
            },
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$buffer): int {
                $buffer .= $chunk;
                if (strlen($buffer) > self::SVG_IMAGE_MAX_BYTES) {
                    return 0;
                }
                return strlen($chunk);
            },
        ]);
        if (defined('CURLOPT_PROTOCOLS')) {
            curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        }
        if (defined('CURLOPT_REDIR_PROTOCOLS')) {
            curl_setopt($curl, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        }

        $result = curl_exec($curl);
        $httpCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $contentType = (string)curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($httpCode >= 300 && $httpCode < 400 && $location !== '') {
            $redirectUrl = self::buildRedirectUrl($url, $location);
            return $redirectUrl !== ''
                ? self::downloadRemoteImageDataUriWithRedirects($redirectUrl, $redirectCount + 1)
                : '';
        }

        if ($result === false || $httpCode < 200 || $httpCode >= 300 || $buffer === '' || strlen($buffer) > self::SVG_IMAGE_MAX_BYTES) {
            self::logAssetFailure('档期确认函远程图片下载失败', [
                'href' => self::sanitizeAssetHrefForLog($url),
                'http_code' => $httpCode,
                'error' => $error,
            ]);
            return '';
        }

        $mime = self::normalizeImageMime((string)preg_replace('/;.*/', '', $contentType));
        if (!self::isEmbeddableImageMime($mime)) {
            $mime = self::detectImageMime($buffer, $url);
        }
        if (!self::isEmbeddableImageMime($mime)) {
            return '';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($buffer);
    }

    protected static function buildRedirectUrl(string $baseUrl, string $location): string
    {
        $location = trim($location);
        if ($location === '') {
            return '';
        }
        if (preg_match('/^https?:\/\//i', $location) === 1) {
            return $location;
        }
        if (str_starts_with($location, '//')) {
            $scheme = (string)(parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https');
            return $scheme . ':' . $location;
        }

        $base = parse_url($baseUrl);
        if (!is_array($base) || empty($base['scheme']) || empty($base['host'])) {
            return '';
        }

        $port = isset($base['port']) ? ':' . (int)$base['port'] : '';
        if (str_starts_with($location, '/')) {
            return $base['scheme'] . '://' . $base['host'] . $port . $location;
        }

        $path = (string)($base['path'] ?? '/');
        $directory = rtrim(substr($path, 0, (int)strrpos($path . '/', '/')), '/');
        return $base['scheme'] . '://' . $base['host'] . $port
            . self::normalizeUrlPath(($directory !== '' ? $directory . '/' : '/') . $location);
    }

    protected static function normalizeUrlPath(string $path): string
    {
        $parts = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }
        return '/' . implode('/', $parts);
    }

    protected static function isSafeRemoteImageUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = strtolower(trim((string)($parts['host'] ?? ''), '[]'));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isPublicIpAddress($host);
        }

        $ips = [];
        if (function_exists('dns_get_record')) {
            foreach (dns_get_record($host, DNS_A + DNS_AAAA) ?: [] as $record) {
                foreach (['ip', 'ipv6'] as $key) {
                    if (!empty($record[$key])) {
                        $ips[] = (string)$record[$key];
                    }
                }
            }
        }
        if (empty($ips) && function_exists('gethostbynamel')) {
            $ips = gethostbynamel($host) ?: [];
        }
        if (empty($ips)) {
            return false;
        }

        foreach ($ips as $ip) {
            if (!self::isPublicIpAddress($ip)) {
                return false;
            }
        }
        return true;
    }

    protected static function isPublicIpAddress(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }
        return false;
    }

    protected static function detectImageMime(string $content, string $path = ''): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = self::normalizeImageMime((string)finfo_buffer($finfo, $content));
                finfo_close($finfo);
                if ($mime !== '') {
                    return $mime;
                }
            }
        }

        if (function_exists('mime_content_type') && is_file($path)) {
            $mime = self::normalizeImageMime((string)@mime_content_type($path));
            if ($mime !== '') {
                return $mime;
            }
        }

        $extension = strtolower((string)pathinfo((string)(parse_url($path, PHP_URL_PATH) ?: $path), PATHINFO_EXTENSION));
        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => '',
        };
    }

    protected static function normalizeImageMime(string $mime): string
    {
        $mime = strtolower(trim($mime));
        return match ($mime) {
            'image/jpg', 'image/pjpeg' => 'image/jpeg',
            default => $mime,
        };
    }

    protected static function isEmbeddableImageMime(string $mime): bool
    {
        return in_array(self::normalizeImageMime($mime), [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
        ], true);
    }

    protected static function sanitizeAssetHrefForLog(string $href): string
    {
        $parts = parse_url($href);
        if (is_array($parts)) {
            $safe = '';
            if (!empty($parts['scheme'])) {
                $safe .= $parts['scheme'] . '://';
            }
            if (!empty($parts['host'])) {
                $safe .= $parts['host'];
            }
            $safe .= (string)($parts['path'] ?? '');
            return mb_substr($safe, 0, 300, 'UTF-8');
        }
        return mb_substr($href, 0, 300, 'UTF-8');
    }

    protected static function hasPersistedAssets(StaffScheduleConfirmLetter $letter): bool
    {
        $storedPath = self::normalizeStoredFileUrl((string)$letter->full_image_url);
        if ($storedPath === '') {
            return false;
        }
        if (!self::isCurrentAssetPath($storedPath)) {
            return false;
        }
        if (preg_match('/^https?:\/\//i', $storedPath) === 1) {
            return true;
        }
        if (self::getStorageDefault() !== 'local') {
            return true;
        }
        $absolutePath = FileService::getFileUrl($storedPath, 'public_path');
        return self::isUsableLocalImageAsset($absolutePath);
    }

    protected static function isCurrentAssetPath(string $storedPath): bool
    {
        $path = (string)(parse_url($storedPath, PHP_URL_PATH) ?: $storedPath);
        $extension = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
        if ($extension !== self::ASSET_FILE_EXTENSION) {
            return false;
        }
        return str_contains(basename($path), '-' . self::ASSET_FILE_VERSION . '.' . self::ASSET_FILE_EXTENSION);
    }

    protected static function isUsableLocalImageAsset(string $absolutePath): bool
    {
        clearstatcache(true, $absolutePath);
        if (!is_file($absolutePath)) {
            return false;
        }

        $fileSize = (int)filesize($absolutePath);
        if ($fileSize < self::ASSET_MIN_VALID_BYTES) {
            self::logAssetFailure('档期确认函图片资产过小，已判定为无效并准备重生', [
                'path' => $absolutePath,
                'size' => $fileSize,
            ]);
            return false;
        }
        return true;
    }

    protected static function formatLetter(StaffScheduleConfirmLetter $letter): array
    {
        return [
            'letter_id' => (int)$letter->id,
            'order_id' => (int)$letter->order_id,
            'staff_id' => (int)$letter->staff_id,
            'config_id' => (int)($letter->config_id ?? 0),
            'config_name' => (string)($letter->config_name ?: '历史配置'),
            'config_template_version' => (int)($letter->config_template_version ?? 0),
            'version' => (int)$letter->version,
            'confirm_date' => (string)$letter->confirm_date,
            'is_current' => (int)$letter->is_outdated === StaffScheduleConfirmLetter::STATUS_ACTIVE ? 1 : 0,
            'is_outdated' => (int)$letter->is_outdated,
            'render_spec_version' => (string)$letter->render_spec_version,
            'snapshot_hash' => (string)$letter->snapshot_hash,
            'rendered_snapshot' => is_array($letter->rendered_snapshot) ? $letter->rendered_snapshot : [],
            'full_image_url' => self::formatPublicImageUrl((string)$letter->full_image_url),
            'thumb_image_url' => self::formatPublicImageUrl((string)$letter->thumb_image_url),
            'generate_source' => (string)$letter->generate_source,
            'create_time' => (int)$letter->create_time,
        ];
    }

    protected static function formatLetterSummary(StaffScheduleConfirmLetter $letter): array
    {
        return [
            'letter_id' => (int)$letter->id,
            'order_id' => (int)$letter->order_id,
            'staff_id' => (int)$letter->staff_id,
            'config_id' => (int)($letter->config_id ?? 0),
            'config_name' => (string)($letter->config_name ?: '历史配置'),
            'config_template_version' => (int)($letter->config_template_version ?? 0),
            'version' => (int)$letter->version,
            'confirm_date' => (string)$letter->confirm_date,
            'is_current' => (int)$letter->is_outdated === StaffScheduleConfirmLetter::STATUS_ACTIVE ? 1 : 0,
            'is_outdated' => (int)$letter->is_outdated,
            'render_spec_version' => (string)$letter->render_spec_version,
            'snapshot_hash' => (string)$letter->snapshot_hash,
            'full_image_url' => self::formatPublicImageUrl((string)$letter->full_image_url),
            'thumb_image_url' => self::formatPublicImageUrl((string)$letter->thumb_image_url),
        ];
    }

    protected static function resolveServiceName(OrderItem $item): string
    {
        $packageName = trim((string)$item->package_name);
        if ($packageName !== '') {
            return $packageName;
        }
        $meta = is_array($item->item_meta) ? $item->item_meta : [];
        foreach (['service_name', 'package_name', 'role_label', 'role_name'] as $key) {
            $value = trim((string)($meta[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }
        return '婚礼服务';
    }

    protected static function resolveCityLabel(Order $order): string
    {
        $parts = array_filter([
            trim((string)($order->service_city ?? '')),
            trim((string)($order->service_district ?? '')),
        ]);
        return implode(' ', $parts);
    }

    protected static function maskCustomerAlias(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '新人';
        }
        $first = function_exists('mb_substr') ? mb_substr($name, 0, 1, 'UTF-8') : substr($name, 0, 1);
        if ($first === '') {
            return '新人';
        }
        if (preg_match('/^\p{Han}$/u', $first) === 1) {
            return $first . '姓新人';
        }
        return '新人';
    }

    protected static function renderContentTemplate(string $template, array $vars): string
    {
        $template = trim($template) !== '' ? trim($template) : self::DEFAULT_CONTENT_TEMPLATE;
        foreach ($vars as $key => $value) {
            $template = str_replace('{' . $key . '}', (string)$value, $template);
        }
        return trim($template);
    }

    protected static function formatServiceDateLabel(string $date): string
    {
        $timestamp = strtotime($date);
        if (!$timestamp) {
            return trim($date);
        }
        return date('Y年m月d日', $timestamp);
    }

    protected static function normalizeStoredFileUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (preg_match('/^https?:\/\//i', $url) === 1) {
            try {
                return trim((string)FileService::setFileUrl($url));
            } catch (\Throwable $e) {
                return $url;
            }
        }
        return ltrim($url, '/');
    }

    protected static function formatPublicImageUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (preg_match('/^https?:\/\//i', $url) === 1) {
            return $url;
        }
        try {
            return FileService::getFileUrl($url);
        } catch (\Throwable $e) {
            return '/' . ltrim($url, '/');
        }
    }

    protected static function normalizeSource(string $source): string
    {
        return in_array($source, ['staff', 'admin'], true) ? $source : 'staff';
    }

    protected static function normalizeTemplateName(string $name): string
    {
        return self::limitText($name, 40, '默认海报');
    }

    protected static function normalizeColor(string $color, string $fallback): string
    {
        $color = trim($color);
        if ($color === '' && $fallback === '') {
            return '';
        }

        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color) === 1) {
            return strtoupper($color);
        }

        if (preg_match('/^rgba?\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})(?:\s*,\s*([01](?:\.\d+)?|\.\d+))?\s*\)$/i', $color, $matches) === 1) {
            $red = self::clampInt((int)$matches[1], 0, 255);
            $green = self::clampInt((int)$matches[2], 0, 255);
            $blue = self::clampInt((int)$matches[3], 0, 255);
            if (isset($matches[4]) && $matches[4] !== '') {
                $alpha = self::clampFloat((float)$matches[4], 0.0, 1.0);
                return sprintf('rgba(%d, %d, %d, %s)', $red, $green, $blue, self::formatDecimal($alpha));
            }

            return sprintf('rgb(%d, %d, %d)', $red, $green, $blue);
        }

        return $fallback === $color ? $fallback : self::normalizeColor($fallback, '');
    }

    protected static function normalizeOptionalColor(string $color): string
    {
        $color = trim($color);
        return $color === '' ? '' : self::normalizeColor($color, '');
    }

    protected static function sanitizeTemplateText(string $text): string
    {
        $text = self::limitText($text, 500, '');
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $text, $matches);
        $allowed = ['service_date_label', 'customer_alias', 'service_name', 'city_label', 'staff_name'];
        foreach (($matches[1] ?? []) as $name) {
            if (!in_array($name, $allowed, true)) {
                $text = str_replace('{' . $name . '}', '', $text);
            }
        }
        return $text;
    }

    protected static function normalizeEditableField(string $field): string
    {
        return in_array($field, self::defaultEditableFields(), true) ? $field : '';
    }

    protected static function clampInt(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    protected static function clampFloat(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    protected static function formatDecimal(float $value): string
    {
        return rtrim(rtrim(sprintf('%.4F', $value), '0'), '.');
    }

    protected static function limitText(string $value, int $limit, string $fallback): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }
        return function_exists('mb_substr') ? mb_substr($value, 0, $limit, 'UTF-8') : substr($value, 0, $limit);
    }

    protected static function toSwitch($value): int
    {
        return (int)$value === 1 ? 1 : 0;
    }

    protected static function getStorageDefault(): string
    {
        $storage = strtolower(trim((string)ConfigService::get('storage', 'default', 'local')));
        return $storage !== '' ? $storage : 'local';
    }

    protected static function buildTempAssetPath(string $fileName): string
    {
        $directory = rtrim(runtime_path(), '/\\') . DIRECTORY_SEPARATOR . self::ASSET_TEMP_DIR;
        self::ensureAssetDirectory($directory);
        $pathInfo = pathinfo($fileName);
        $baseName = preg_replace('/[^a-z0-9_-]/i', '', (string)($pathInfo['filename'] ?? 'staff-schedule-confirm-letter'));
        $extension = strtolower((string)($pathInfo['extension'] ?? self::ASSET_FILE_EXTENSION));
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : self::ASSET_FILE_EXTENSION;
        return $directory . DIRECTORY_SEPARATOR . ($baseName !== '' ? $baseName : 'staff-schedule-confirm-letter') . '-' . uniqid('', true) . '.' . $extension;
    }

    protected static function ensureAssetDirectory(string $directory): void
    {
        clearstatcache(true, $directory);
        if (is_dir($directory)) {
            if (!is_writable($directory)) {
                throw new \RuntimeException(self::ERROR_ASSET_DIRECTORY);
            }
            return;
        }
        if (!@mkdir($directory, 0775, true) && !is_dir($directory)) {
            self::logAssetFailure('档期确认函图片目录创建失败', [
                'directory' => $directory,
                'last_error' => error_get_last(),
            ]);
            throw new \RuntimeException(self::ERROR_ASSET_DIRECTORY);
        }
        @chmod($directory, 0775);
    }

    protected static function logAssetFailure(string $message, array $context = []): void
    {
        try {
            Log::write($message . '，上下文：' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            // 日志失败不应覆盖真实异常。
        }
    }
}
