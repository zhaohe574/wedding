<?php
// +----------------------------------------------------------------------
// | 素材清理逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic;

use app\common\logic\BaseLogic;
use app\common\model\file\File;
use app\common\model\file\FileCleanupLog;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\storage\Driver as StorageDriver;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use think\facade\Db;
use Throwable;

class MaterialCleanupLogic extends BaseLogic
{
    private const UPLOAD_ROOT = 'uploads';
    private const DEFAULT_RETENTION_DAYS = 30;
    private const MAX_PAGE_SIZE = 100;
    private const REFERENCE_ACTIVE = 'active';
    private const REFERENCE_STALE = 'stale';
    private const REFERENCE_ARCHIVE = 'archive';
    private const REFERENCE_NONE = 'none';

    private static array $tableFields = [];
    private static array $resolverCache = [];
    private static ?array $allowedExtensions = null;
    private static ?array $extensionTypeMap = null;

    private static function referenceRules(): array
    {
        return [
            [
                'table' => 'admin',
                'fields' => ['avatar'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '管理员头像',
                'where' => [
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'user',
                'fields' => ['avatar'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '用户头像',
                'where' => [
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'config',
                'fields' => ['value'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '系统配置',
                'source_fields' => ['type', 'name'],
                'source_template' => '配置：{type}.{name}',
            ],
            [
                'table' => 'article',
                'fields' => ['image', 'intro', 'content'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '已显示文章',
                'where' => [
                    ['is_show', '=', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'article',
                'fields' => ['image', 'intro', 'content'],
                'state' => self::REFERENCE_STALE,
                'label' => '未显示文章',
                'where' => [
                    ['is_show', '<>', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'article_cate',
                'fields' => ['image'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '已显示文章分类',
                'where' => [
                    ['is_show', '=', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'article_cate',
                'fields' => ['image'],
                'state' => self::REFERENCE_STALE,
                'label' => '未显示文章分类',
                'where' => [
                    ['is_show', '<>', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'decorate_page',
                'fields' => ['data', 'content', 'meta'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '当前装修页面',
                'where' => [
                    ['id', 'in', [1, 2, 3, 4, 5]],
                ],
            ],
            [
                'table' => 'decorate_page',
                'fields' => ['data', 'content', 'meta'],
                'state' => self::REFERENCE_STALE,
                'label' => '旧装修页面',
                'where' => [
                    ['id', 'not in', [1, 2, 3, 4, 5]],
                ],
            ],
            [
                'table' => 'decorate_tabbar',
                'fields' => ['selected', 'unselected', 'link', 'data', 'content', 'meta'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '启用底部导航',
                'where' => [
                    ['is_show', '=', 1],
                ],
            ],
            [
                'table' => 'decorate_tabbar',
                'fields' => ['selected', 'unselected', 'link', 'data', 'content', 'meta'],
                'state' => self::REFERENCE_STALE,
                'label' => '停用底部导航',
                'where' => [
                    ['is_show', '<>', 1],
                ],
            ],
            [
                'table' => 'staff',
                'fields' => ['avatar', 'profile', 'cover', 'service_desc', 'long_detail', 'monthly_report_material'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '启用服务人员',
                'where' => [
                    ['status', '=', 1],
                    ['audit_status', '=', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'staff',
                'fields' => ['avatar', 'profile', 'cover', 'service_desc', 'long_detail', 'monthly_report_material'],
                'state' => self::REFERENCE_STALE,
                'label' => '禁用或未通过服务人员',
                'where_any' => [
                    ['status', '<>', 1],
                    ['audit_status', '<>', 1],
                ],
                'where' => [
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'staff_banner',
                'fields' => ['file_url', 'cover_url'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '启用服务人员轮播',
                'resolver' => 'active_staff_ids',
                'resolver_field' => 'staff_id',
            ],
            [
                'table' => 'staff_banner',
                'fields' => ['file_url', 'cover_url'],
                'state' => self::REFERENCE_STALE,
                'label' => '非启用服务人员轮播',
                'resolver' => 'inactive_staff_ids',
                'resolver_field' => 'staff_id',
            ],
            [
                'table' => 'staff_work',
                'fields' => ['cover', 'images', 'video', 'video_url', 'content', 'description'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '已显示作品',
                'where' => [
                    ['is_show', '=', 1],
                    ['audit_status', '=', 1],
                    ['delete_time', 'null'],
                ],
                'resolver' => 'active_staff_ids',
                'resolver_field' => 'staff_id',
            ],
            [
                'table' => 'staff_work',
                'fields' => ['cover', 'images', 'video', 'video_url', 'content', 'description'],
                'state' => self::REFERENCE_STALE,
                'label' => '隐藏或未通过作品',
                'where' => [
                    ['delete_time', 'null'],
                ],
                'where_any' => [
                    ['is_show', '<>', 1],
                    ['audit_status', '<>', 1],
                ],
            ],
            [
                'table' => 'staff_work',
                'fields' => ['cover', 'images', 'video', 'video_url', 'content', 'description'],
                'state' => self::REFERENCE_STALE,
                'label' => '非启用服务人员作品',
                'where' => [
                    ['delete_time', 'null'],
                    ['is_show', '=', 1],
                    ['audit_status', '=', 1],
                ],
                'resolver' => 'inactive_staff_ids',
                'resolver_field' => 'staff_id',
            ],
            [
                'table' => 'staff_certificate',
                'fields' => ['image', 'images', 'file_url'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '已通过证书',
                'where' => [
                    ['delete_time', 'null'],
                ],
                'status_field_candidates' => ['verify_status', 'audit_status'],
                'status_value' => 1,
                'resolver' => 'active_staff_ids',
                'resolver_field' => 'staff_id',
            ],
            [
                'table' => 'staff_certificate',
                'fields' => ['image', 'images', 'file_url'],
                'state' => self::REFERENCE_STALE,
                'label' => '非当前展示证书',
                'where' => [
                    ['delete_time', 'null'],
                ],
                'status_field_candidates' => ['verify_status', 'audit_status'],
                'status_operator' => '<>',
                'status_value' => 1,
            ],
            [
                'table' => 'service_category',
                'fields' => ['image', 'icon', 'cover'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '已显示服务分类',
                'where' => [
                    ['is_show', '=', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'service_category',
                'fields' => ['image', 'icon', 'cover'],
                'state' => self::REFERENCE_STALE,
                'label' => '隐藏服务分类',
                'where' => [
                    ['is_show', '<>', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'service_package',
                'fields' => ['image', 'cover', 'images', 'content', 'detail', 'description'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '已上架套餐',
                'where' => [
                    ['is_show', '=', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'service_package',
                'fields' => ['image', 'cover', 'images', 'content', 'detail', 'description'],
                'state' => self::REFERENCE_STALE,
                'label' => '下架套餐',
                'where' => [
                    ['is_show', '<>', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'service_addon',
                'fields' => ['image', 'cover', 'content', 'detail', 'description'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '已上架附加项',
                'where' => [
                    ['is_show', '=', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'service_addon',
                'fields' => ['image', 'cover', 'content', 'detail', 'description'],
                'state' => self::REFERENCE_STALE,
                'label' => '下架附加项',
                'where' => [
                    ['is_show', '<>', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'style_tag',
                'fields' => ['icon', 'image'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '已显示风格标签',
                'where' => [
                    ['is_show', '=', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'style_tag',
                'fields' => ['icon', 'image'],
                'state' => self::REFERENCE_STALE,
                'label' => '隐藏风格标签',
                'where' => [
                    ['is_show', '<>', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'dynamic',
                'fields' => ['images', 'video_url', 'video_cover', 'cover', 'content', 'activity_poster'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '已发布动态',
                'where' => [
                    ['status', '=', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'dynamic',
                'fields' => ['images', 'video_url', 'video_cover', 'cover', 'content', 'activity_poster'],
                'state' => self::REFERENCE_STALE,
                'label' => '非发布动态',
                'where' => [
                    ['status', '<>', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'dynamic_comment',
                'fields' => ['images', 'video', 'video_cover', 'content'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '正常动态评论',
                'where' => [
                    ['status', '=', 1],
                    ['review_status', '=', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'dynamic_comment',
                'fields' => ['images', 'video', 'video_cover', 'content'],
                'state' => self::REFERENCE_STALE,
                'label' => '非正常动态评论',
                'where' => [
                    ['delete_time', 'null'],
                ],
                'where_any' => [
                    ['status', '<>', 1],
                    ['review_status', '<>', 1],
                ],
            ],
            [
                'table' => 'review',
                'fields' => ['images', 'video', 'video_url', 'video_cover', 'content'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '已显示评价',
                'where' => [
                    ['status', '=', 1],
                    ['is_show', '=', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'review',
                'fields' => ['images', 'video', 'video_url', 'video_cover', 'content'],
                'state' => self::REFERENCE_STALE,
                'label' => '隐藏或未通过评价',
                'where' => [
                    ['delete_time', 'null'],
                ],
                'where_any' => [
                    ['status', '<>', 1],
                    ['is_show', '<>', 1],
                ],
            ],
            [
                'table' => 'review_reply',
                'fields' => ['images', 'content'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '已通过评价回复',
                'where' => [
                    ['status', '=', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'review_reply',
                'fields' => ['images', 'content'],
                'state' => self::REFERENCE_STALE,
                'label' => '未通过评价回复',
                'where' => [
                    ['status', '<>', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'notification',
                'fields' => ['image', 'cover', 'content'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '通知内容',
                'where' => [
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'dev_pay_config',
                'fields' => ['icon', 'params', 'config'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '支付配置',
            ],
            [
                'table' => 'dev_pay_way',
                'fields' => ['icon', 'params', 'config'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '启用支付方式',
                'where' => [
                    ['status', '=', 1],
                ],
            ],
            [
                'table' => 'dev_pay_way',
                'fields' => ['icon', 'params', 'config'],
                'state' => self::REFERENCE_STALE,
                'label' => '停用支付方式',
                'where' => [
                    ['status', '<>', 1],
                ],
            ],
            [
                'table' => 'sales_advisor',
                'fields' => ['avatar', 'contact_qr_code', 'qrcode', 'qr_code'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '销售顾问资料',
                'where' => [
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'customer',
                'fields' => ['avatar', 'images', 'contact_qr_code'],
                'state' => self::REFERENCE_ARCHIVE,
                'label' => '客户资料凭证',
                'where' => [
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'follow_record',
                'fields' => ['images', 'attachments', 'content'],
                'state' => self::REFERENCE_ARCHIVE,
                'label' => '客户跟进凭证',
                'where' => [
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'after_sale_ticket',
                'fields' => ['images', 'videos', 'attachments', 'content'],
                'state' => self::REFERENCE_ARCHIVE,
                'label' => '售后工单凭证',
                'where' => [
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'after_sale_ticket_log',
                'fields' => ['images', 'videos', 'attachments', 'content'],
                'state' => self::REFERENCE_ARCHIVE,
                'label' => '售后处理凭证',
            ],
            [
                'table' => 'complaint',
                'fields' => ['images', 'videos', 'attachments', 'content'],
                'state' => self::REFERENCE_ARCHIVE,
                'label' => '投诉凭证',
                'where' => [
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'service_callback',
                'fields' => ['images', 'videos', 'attachments', 'content'],
                'state' => self::REFERENCE_ARCHIVE,
                'label' => '服务回访凭证',
                'where' => [
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'order_confirm_letter',
                'fields' => ['full_image_url', 'thumb_image_url', 'rendered_snapshot', 'snapshot', 'design_config'],
                'state' => self::REFERENCE_ARCHIVE,
                'label' => '订单确认函凭证',
            ],
            [
                'table' => 'order_confirm_letter_push_log',
                'fields' => ['image_url', 'payload', 'response'],
                'state' => self::REFERENCE_ARCHIVE,
                'label' => '订单确认函推送凭证',
            ],
            [
                'table' => 'staff_schedule_confirm_letter_config',
                'fields' => ['config', 'qrcode_image', 'schedule_qrcode_image', 'background_image', 'design_config'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '启用默认档期确认函模板',
                'where' => [
                    ['status', '=', 1],
                    ['is_default', '=', 1],
                ],
            ],
            [
                'table' => 'staff_schedule_confirm_letter_config',
                'fields' => ['config', 'qrcode_image', 'schedule_qrcode_image', 'background_image', 'design_config'],
                'state' => self::REFERENCE_STALE,
                'label' => '非默认或停用档期确认函模板',
                'where_any' => [
                    ['status', '<>', 1],
                    ['is_default', '<>', 1],
                ],
            ],
            [
                'table' => 'staff_schedule_confirm_letter',
                'fields' => ['full_image_url', 'thumb_image_url', 'rendered_snapshot', 'snapshot', 'design_config'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '当前档期确认函',
                'where' => [
                    ['is_outdated', '=', 0],
                ],
            ],
            [
                'table' => 'staff_schedule_confirm_letter',
                'fields' => ['full_image_url', 'thumb_image_url', 'rendered_snapshot', 'snapshot', 'design_config'],
                'state' => self::REFERENCE_STALE,
                'label' => '过期档期确认函',
                'where' => [
                    ['is_outdated', '<>', 0],
                ],
            ],
            [
                'table' => 'monthly_report_material',
                'fields' => ['photo', 'avatar_photo', 'half_body_photo'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '启用月报素材',
                'where' => [
                    ['status', '=', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'monthly_report_material',
                'fields' => ['photo', 'avatar_photo', 'half_body_photo'],
                'state' => self::REFERENCE_STALE,
                'label' => '停用月报素材',
                'where' => [
                    ['status', '<>', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'monthly_report_template',
                'fields' => ['design_config', 'preview_image', 'rendered_snapshot'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '启用默认月报模板',
                'where' => [
                    ['status', '=', 1],
                    ['is_default', '=', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'monthly_report_template',
                'fields' => ['design_config', 'preview_image', 'rendered_snapshot'],
                'state' => self::REFERENCE_STALE,
                'label' => '非默认或停用月报模板',
                'where' => [
                    ['delete_time', 'null'],
                ],
                'where_any' => [
                    ['status', '<>', 1],
                    ['is_default', '<>', 1],
                ],
            ],
            [
                'table' => 'monthly_report',
                'fields' => ['addition_image_url', 'ranking_image_url', 'top_image_url', 'rendered_snapshot', 'template_snapshot'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '当前月报',
                'where' => [
                    ['is_current', '=', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'monthly_report',
                'fields' => ['addition_image_url', 'ranking_image_url', 'top_image_url', 'rendered_snapshot', 'template_snapshot'],
                'state' => self::REFERENCE_STALE,
                'label' => '历史月报',
                'where' => [
                    ['is_current', '<>', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'couple_questionnaire',
                'fields' => ['content', 'config'],
                'state' => self::REFERENCE_ACTIVE,
                'label' => '启用新人问卷配置',
                'where' => [
                    ['status', '=', 1],
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'couple_questionnaire_version',
                'fields' => ['content', 'config'],
                'state' => self::REFERENCE_ARCHIVE,
                'label' => '新人问卷版本凭证',
                'where' => [
                    ['delete_time', 'null'],
                ],
            ],
            [
                'table' => 'couple_questionnaire_answer',
                'fields' => ['answer', 'content'],
                'state' => self::REFERENCE_ARCHIVE,
                'label' => '新人问卷答案凭证',
                'where' => [
                    ['delete_time', 'null'],
                ],
            ],
        ];
    }

    public static function summary(array $params): array
    {
        $result = self::buildCandidates($params);
        $stats = $result['stats'];

        return [
            'candidate_count' => count($result['candidates']),
            'registered_count' => $stats['registered_count'],
            'orphan_count' => $stats['orphan_count'],
            'total_size' => $stats['total_size'],
            'total_size_desc' => self::formatBytes($stats['total_size']),
            'protected_count' => $stats['protected_count'],
            'protected_size' => $stats['protected_size'],
            'protected_size_desc' => self::formatBytes($stats['protected_size']),
            'referenced_count' => $stats['referenced_count'],
            'active_referenced_count' => $stats['active_referenced_count'],
            'archive_referenced_count' => $stats['archive_referenced_count'],
            'stale_referenced_count' => $stats['stale_referenced_count'],
            'scanned_file_count' => $stats['scanned_file_count'],
            'registered_file_count' => $stats['registered_file_count'],
            'retention_days' => self::DEFAULT_RETENTION_DAYS,
            'scan_scope' => str_replace('\\', '/', rtrim(public_path(), '/\\') . '/' . self::UPLOAD_ROOT),
            'storage_default' => ConfigService::get('storage', 'default', 'local'),
            'scan_time' => date('Y-m-d H:i:s'),
        ];
    }

    public static function lists(array $params): array
    {
        $page = max(1, (int)($params['page_no'] ?? 1));
        $size = min(self::MAX_PAGE_SIZE, max(1, (int)($params['page_size'] ?? 20)));
        $result = self::buildCandidates($params);
        $candidates = $result['candidates'];
        $offset = ($page - 1) * $size;

        return [
            'lists' => array_slice($candidates, $offset, $size),
            'count' => count($candidates),
            'extend' => [
                'retention_days' => self::DEFAULT_RETENTION_DAYS,
                'total_size' => $result['stats']['total_size'],
                'total_size_desc' => self::formatBytes($result['stats']['total_size']),
            ],
        ];
    }

    public static function delete(array $params, int $adminId): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $params['ids'] ?? []))));
        $scan = self::buildCandidates([]);
        $candidateMap = [];
        foreach ($scan['candidates'] as $candidate) {
            $candidateMap[$candidate['id']] = $candidate;
        }

        $storageDriver = self::localStorageDriver();
        $successItems = [];
        $failedItems = [];
        $deletedCount = 0;
        $registeredCount = 0;
        $orphanCount = 0;
        $freeSize = 0;

        foreach ($ids as $candidateId) {
            $candidate = $candidateMap[$candidateId] ?? null;
            if (!$candidate) {
                $failedItems[] = [
                    'id' => $candidateId,
                    'uri' => '',
                    'reason' => '素材已被当前业务引用、业务凭证引用、仍在保护期内或不存在，已跳过',
                ];
                continue;
            }

            try {
                if ($candidate['source_type'] === 'registered') {
                    self::deleteRegisteredCandidate($candidate, $storageDriver);
                    $registeredCount++;
                } else {
                    self::deleteOrphanCandidate($candidate);
                    $orphanCount++;
                }

                $deletedCount++;
                $freeSize += (int)$candidate['size'];
                $successItems[] = [
                    'id' => $candidate['id'],
                    'uri' => $candidate['uri'],
                    'source_type' => $candidate['source_type'],
                    'size' => $candidate['size'],
                ];
            } catch (Throwable $e) {
                $failedItems[] = [
                    'id' => $candidate['id'],
                    'uri' => $candidate['uri'],
                    'reason' => $e->getMessage(),
                ];
            }
        }

        self::writeCleanupLog($adminId, [
            'delete_count' => $deletedCount,
            'registered_count' => $registeredCount,
            'orphan_count' => $orphanCount,
            'free_size' => $freeSize,
            'failed_count' => count($failedItems),
            'items' => $successItems,
            'failed_detail' => $failedItems,
        ]);

        return [
            'requested_count' => count($ids),
            'deleted_count' => $deletedCount,
            'registered_count' => $registeredCount,
            'orphan_count' => $orphanCount,
            'failed_count' => count($failedItems),
            'free_size' => $freeSize,
            'free_size_desc' => self::formatBytes($freeSize),
            'failed_items' => $failedItems,
        ];
    }

    private static function buildCandidates(array $params): array
    {
        $params = self::normalizeParams($params);
        $cutoffTime = time() - self::DEFAULT_RETENTION_DAYS * 86400;
        $references = self::collectReferences();
        $localFiles = self::scanLocalFiles();
        $registeredFiles = self::registeredFiles();
        $registeredUris = [];
        $candidates = [];
        $stats = [
            'registered_count' => 0,
            'orphan_count' => 0,
            'total_size' => 0,
            'protected_count' => 0,
            'protected_size' => 0,
            'referenced_count' => 0,
            'active_referenced_count' => 0,
            'archive_referenced_count' => 0,
            'stale_referenced_count' => 0,
            'scanned_file_count' => count($localFiles),
            'registered_file_count' => count($registeredFiles),
        ];

        foreach ($registeredFiles as $file) {
            $uri = self::normalizeUploadUri((string)($file['uri'] ?? ''));
            if (!$uri || !self::isEligibleUploadUri($uri)) {
                continue;
            }

            $registeredUris[$uri] = true;
            $disk = $localFiles[$uri] ?? null;
            $timestamp = self::resolveRegisteredTimestamp($file, $disk);
            $candidate = self::makeRegisteredCandidate($file, $uri, $disk, $timestamp);
            $referenceInfo = self::referenceInfo($uri, $references);
            self::applyReferenceInfo($candidate, $referenceInfo);

            if (!self::matchesFilters($candidate, $params)) {
                continue;
            }

            if ($referenceInfo['blocked']) {
                self::countReferencedStats($stats, $referenceInfo['state']);
                continue;
            }

            if ($timestamp >= $cutoffTime) {
                $stats['protected_count']++;
                $stats['protected_size'] += (int)$candidate['size'];
                continue;
            }

            $stats['registered_count']++;
            $stats['total_size'] += (int)$candidate['size'];
            if ($referenceInfo['state'] === self::REFERENCE_STALE) {
                $stats['stale_referenced_count']++;
            }
            $candidates[] = $candidate;
        }

        foreach ($localFiles as $uri => $disk) {
            if (isset($registeredUris[$uri])) {
                continue;
            }

            $timestamp = (int)($disk['mtime'] ?? 0);
            $candidate = self::makeOrphanCandidate($uri, $disk, $timestamp);
            $referenceInfo = self::referenceInfo($uri, $references);
            self::applyReferenceInfo($candidate, $referenceInfo);

            if (!self::matchesFilters($candidate, $params)) {
                continue;
            }

            if ($referenceInfo['blocked']) {
                self::countReferencedStats($stats, $referenceInfo['state']);
                continue;
            }

            if ($timestamp >= $cutoffTime) {
                $stats['protected_count']++;
                $stats['protected_size'] += (int)$candidate['size'];
                continue;
            }

            $stats['orphan_count']++;
            $stats['total_size'] += (int)$candidate['size'];
            if ($referenceInfo['state'] === self::REFERENCE_STALE) {
                $stats['stale_referenced_count']++;
            }
            $candidates[] = $candidate;
        }

        usort($candidates, static function (array $left, array $right): int {
            if ($left['size'] !== $right['size']) {
                return $right['size'] <=> $left['size'];
            }
            return $left['mtime'] <=> $right['mtime'];
        });

        return [
            'candidates' => $candidates,
            'stats' => $stats,
        ];
    }

    private static function normalizeParams(array $params): array
    {
        $source = (string)($params['source'] ?? 'all');
        if (!in_array($source, ['all', 'registered', 'orphan'], true)) {
            $source = 'all';
        }

        $type = (int)($params['type'] ?? 0);
        if (!in_array($type, [0, 10, 20, 30], true)) {
            $type = 0;
        }

        $referenceState = (string)($params['reference_state'] ?? 'all');
        if (!in_array($referenceState, ['all', self::REFERENCE_NONE, self::REFERENCE_STALE], true)) {
            $referenceState = 'all';
        }

        return [
            'source' => $source,
            'type' => $type,
            'keyword' => trim((string)($params['keyword'] ?? '')),
            'reference_state' => $referenceState,
        ];
    }

    private static function registeredFiles(): array
    {
        try {
            return Db::name('file')
                ->field('id,cid,source_id,source,type,name,uri,create_time,update_time')
                ->whereNull('delete_time')
                ->select()
                ->toArray();
        } catch (Throwable) {
            return [];
        }
    }

    private static function scanLocalFiles(): array
    {
        $root = rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR . self::UPLOAD_ROOT;
        $rootReal = is_dir($root) ? realpath($root) : false;
        if (!$rootReal) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootReal, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $filename = $fileInfo->getFilename();
            if ($filename === 'index.html' || str_starts_with($filename, '.')) {
                continue;
            }

            $relative = self::UPLOAD_ROOT . '/' . ltrim(str_replace('\\', '/', substr($fileInfo->getPathname(), strlen($rootReal))), '/');
            $uri = self::normalizeUploadUri($relative);
            if (!$uri || !self::isEligibleUploadUri($uri)) {
                continue;
            }

            $files[$uri] = [
                'uri' => $uri,
                'size' => (int)$fileInfo->getSize(),
                'mtime' => (int)$fileInfo->getMTime(),
            ];
        }

        return $files;
    }

    private static function collectReferences(): array
    {
        $references = self::emptyReferenceBuckets();

        foreach (self::referenceRules() as $rule) {
            self::collectRuleReferences($references, $rule);
        }

        return $references;
    }

    private static function collectRuleReferences(array &$references, array $rule): void
    {
        $table = (string)($rule['table'] ?? '');
        $fields = (array)($rule['fields'] ?? []);
        $state = (string)($rule['state'] ?? self::REFERENCE_ACTIVE);
        if ($table === '' || !isset($references[$state])) {
            return;
        }

        $availableFields = self::existingFields($table, $fields);
        if (!$availableFields) {
            return;
        }

        $tableFields = self::getTableFields($table);
        $sourceFields = self::existingFields($table, (array)($rule['source_fields'] ?? []));
        $selectFields = array_values(array_unique(array_merge($availableFields, $sourceFields)));
        $resolverField = (string)($rule['resolver_field'] ?? '');
        if ($resolverField !== '' && isset($tableFields[$resolverField])) {
            $selectFields[] = $resolverField;
        }

        $query = Db::name($table)->field(implode(',', array_values(array_unique($selectFields))));
        self::applyRuleWhere($query, $tableFields, (array)($rule['where'] ?? []));
        self::applyRuleWhereAny($query, $tableFields, (array)($rule['where_any'] ?? []));
        self::applyRuleStatusField($query, $tableFields, $rule);
        if (!self::ruleContainsField($rule, 'delete_time') && isset($tableFields['delete_time'])) {
            $query->whereNull('delete_time');
        }
        if (!self::applyResolverFilter($query, $tableFields, $rule)) {
            return;
        }

        try {
            $rows = $query->select()->toArray();
        } catch (Throwable) {
            return;
        }

        foreach ($rows as $row) {
            $source = self::formatReferenceSource($rule, $row);
            foreach ($availableFields as $field) {
                self::extractReferencesFromValue($row[$field] ?? '', $source . '.' . $field, $references[$state]);
            }
        }
    }

    private static function extractReferencesFromValue($value, string $source, array &$references): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                self::extractReferencesFromValue($item, $source, $references);
            }
            return;
        }

        if (!is_scalar($value)) {
            return;
        }

        $text = str_replace('\/', '/', trim((string)$value));
        if ($text === '') {
            return;
        }

        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            self::extractReferencesFromValue($decoded, $source, $references);
        }

        if (stripos($text, self::UPLOAD_ROOT . '/') === false) {
            return;
        }

        preg_match_all('/(?:https?:\/\/[^\s"\'<>]+)?\/?uploads\/[^\s"\'<>\)\]\}\\\\]+/i', $text, $matches);
        foreach ($matches[0] ?? [] as $match) {
            $uri = self::normalizeUploadUri($match);
            if (!$uri) {
                continue;
            }

            $references[$uri] ??= [];
            if (count($references[$uri]) < 5) {
                $references[$uri][] = $source;
            }
        }
    }

    private static function emptyReferenceBuckets(): array
    {
        return [
            self::REFERENCE_ACTIVE => [],
            self::REFERENCE_STALE => [],
            self::REFERENCE_ARCHIVE => [],
        ];
    }

    private static function applyRuleWhere($query, array $tableFields, array $conditions): void
    {
        foreach ($conditions as $condition) {
            if (!is_array($condition) || count($condition) < 2) {
                continue;
            }

            $field = (string)$condition[0];
            $operator = strtolower((string)$condition[1]);
            $value = $condition[2] ?? null;
            if ($field === '' || !isset($tableFields[$field])) {
                continue;
            }

            self::applyQueryCondition($query, $field, $operator, $value);
        }
    }

    private static function applyRuleWhereAny($query, array $tableFields, array $conditions): void
    {
        $available = [];
        foreach ($conditions as $condition) {
            if (!is_array($condition) || count($condition) < 2) {
                continue;
            }

            $field = (string)$condition[0];
            if ($field !== '' && isset($tableFields[$field])) {
                $available[] = $condition;
            }
        }

        if (!$available) {
            return;
        }

        $query->where(static function ($where) use ($available) {
            $first = true;
            foreach ($available as $condition) {
                $field = (string)$condition[0];
                $operator = strtolower((string)$condition[1]);
                $value = $condition[2] ?? null;
                if ($first) {
                    self::applyQueryCondition($where, $field, $operator, $value);
                    $first = false;
                    continue;
                }

                self::applyQueryCondition($where, $field, $operator, $value, true);
            }
        });
    }

    private static function applyRuleStatusField($query, array $tableFields, array $rule): void
    {
        $candidates = (array)($rule['status_field_candidates'] ?? []);
        if (!$candidates) {
            return;
        }

        foreach ($candidates as $field) {
            $field = (string)$field;
            if ($field === '' || !isset($tableFields[$field])) {
                continue;
            }

            $query->where(
                $field,
                (string)($rule['status_operator'] ?? '='),
                $rule['status_value'] ?? 1
            );
            return;
        }
    }

    private static function applyQueryCondition($query, string $field, string $operator, $value = null, bool $or = false): void
    {
        $method = $or ? 'whereOr' : 'where';

        switch ($operator) {
            case 'null':
                $query->whereNull($field, $or ? 'OR' : 'AND');
                break;
            case 'not null':
            case 'notnull':
                $query->whereNotNull($field, $or ? 'OR' : 'AND');
                break;
            case 'in':
                $or ? $query->whereOr($field, 'in', (array)$value) : $query->whereIn($field, (array)$value);
                break;
            case 'not in':
            case 'notin':
                $or ? $query->whereOr($field, 'not in', (array)$value) : $query->whereNotIn($field, (array)$value);
                break;
            case '<>':
            case '!=':
                $query->$method($field, '<>', $value);
                break;
            default:
                $query->$method($field, $operator ?: '=', $value);
                break;
        }
    }

    private static function applyResolverFilter($query, array $tableFields, array $rule): bool
    {
        $resolver = (string)($rule['resolver'] ?? '');
        $field = (string)($rule['resolver_field'] ?? '');
        if ($resolver === '' || $field === '' || !isset($tableFields[$field])) {
            return true;
        }

        $ids = self::resolveReferenceIds($resolver);
        if (!$ids) {
            return false;
        }

        $query->whereIn($field, $ids);
        return true;
    }

    private static function resolveReferenceIds(string $resolver): array
    {
        if (array_key_exists($resolver, self::$resolverCache)) {
            return self::$resolverCache[$resolver];
        }

        $ids = [];
        try {
            if ($resolver === 'active_staff_ids') {
                $ids = self::queryStaffIds(true);
            } elseif ($resolver === 'inactive_staff_ids') {
                $ids = self::queryStaffIds(false);
            }
        } catch (Throwable) {
            $ids = [];
        }

        self::$resolverCache[$resolver] = array_values(array_unique(array_map('intval', $ids)));
        return self::$resolverCache[$resolver];
    }

    private static function queryStaffIds(bool $active): array
    {
        if (!self::tableExists('staff')) {
            return [];
        }

        $fields = self::getTableFields('staff');
        $query = Db::name('staff')->field('id');
        if (isset($fields['delete_time'])) {
            $query->whereNull('delete_time');
        }

        if ($active) {
            if (isset($fields['status'])) {
                $query->where('status', 1);
            }
            if (isset($fields['audit_status'])) {
                $query->where('audit_status', 1);
            }
        } else {
            $query->where(static function ($where) use ($fields) {
                $hasCondition = false;
                if (isset($fields['status'])) {
                    $where->where('status', '<>', 1);
                    $hasCondition = true;
                }
                if (isset($fields['audit_status'])) {
                    $hasCondition
                        ? $where->whereOr('audit_status', '<>', 1)
                        : $where->where('audit_status', '<>', 1);
                }
            });
        }

        return array_map('intval', $query->column('id'));
    }

    private static function ruleContainsField(array $rule, string $field): bool
    {
        foreach (['where', 'where_any'] as $key) {
            foreach ((array)($rule[$key] ?? []) as $condition) {
                if (is_array($condition) && (string)($condition[0] ?? '') === $field) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function formatReferenceSource(array $rule, array $row): string
    {
        $template = (string)($rule['source_template'] ?? '');
        if ($template !== '') {
            return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', static function (array $matches) use ($row): string {
                return (string)($row[$matches[1]] ?? '');
            }, $template) ?? $template;
        }

        $label = (string)($rule['label'] ?? '');
        $table = (string)($rule['table'] ?? '');
        return $label !== '' ? $label : $table;
    }

    private static function referenceInfo(string $uri, array $references): array
    {
        if (isset($references[self::REFERENCE_ACTIVE][$uri])) {
            return [
                'state' => self::REFERENCE_ACTIVE,
                'blocked' => true,
                'sources' => $references[self::REFERENCE_ACTIVE][$uri],
            ];
        }

        if (isset($references[self::REFERENCE_ARCHIVE][$uri])) {
            return [
                'state' => self::REFERENCE_ARCHIVE,
                'blocked' => true,
                'sources' => $references[self::REFERENCE_ARCHIVE][$uri],
            ];
        }

        if (isset($references[self::REFERENCE_STALE][$uri])) {
            return [
                'state' => self::REFERENCE_STALE,
                'blocked' => false,
                'sources' => $references[self::REFERENCE_STALE][$uri],
            ];
        }

        return [
            'state' => self::REFERENCE_NONE,
            'blocked' => false,
            'sources' => [],
        ];
    }

    private static function applyReferenceInfo(array &$candidate, array $referenceInfo): void
    {
        $state = (string)($referenceInfo['state'] ?? self::REFERENCE_NONE);
        $sources = array_values(array_unique(array_map('strval', (array)($referenceInfo['sources'] ?? []))));

        $candidate['reference_state'] = $state === self::REFERENCE_STALE ? self::REFERENCE_STALE : self::REFERENCE_NONE;
        $candidate['reference_state_desc'] = $state === self::REFERENCE_STALE ? '仅历史引用' : '未发现引用';
        $candidate['reference_sources'] = array_slice($sources, 0, 5);
        $candidate['risk_level'] = $state === self::REFERENCE_STALE ? 'medium' : 'low';
        $candidate['risk_level_desc'] = $state === self::REFERENCE_STALE ? '中风险' : '低风险';

        if ($state === self::REFERENCE_STALE) {
            $candidate['reason'] = '仅在下架、禁用、旧模板或历史版本中发现引用';
            $candidate['risk_tip'] = '该素材曾被历史内容引用，当前未处于启用或展示状态，删除前会再次复核。';
            return;
        }

        $candidate['risk_tip'] = '仅表示在当前启用业务字段中未发现引用，删除前会再次复核。';
    }

    private static function countReferencedStats(array &$stats, string $state): void
    {
        $stats['referenced_count']++;
        if ($state === self::REFERENCE_ARCHIVE) {
            $stats['archive_referenced_count']++;
            return;
        }

        $stats['active_referenced_count']++;
    }

    private static function makeRegisteredCandidate(array $file, string $uri, ?array $disk, int $timestamp): array
    {
        $type = (int)($file['type'] ?? 0) ?: self::inferType($uri);
        $size = $disk ? (int)($disk['size'] ?? 0) : 0;

        return self::formatCandidate([
            'id' => 'file:' . (int)$file['id'],
            'file_id' => (int)$file['id'],
            'source_type' => 'registered',
            'source_desc' => '素材记录',
            'type' => $type,
            'name' => trim((string)($file['name'] ?? '')) ?: basename($uri),
            'uri' => $uri,
            'size' => $size,
            'mtime' => $timestamp,
            'create_time' => self::parseTimestamp($file['create_time'] ?? 0),
            'exists' => (bool)$disk,
            'reason' => $disk ? '素材记录未发现业务引用' : '素材记录未发现业务引用，且本地文件已不存在',
        ]);
    }

    private static function makeOrphanCandidate(string $uri, array $disk, int $timestamp): array
    {
        return self::formatCandidate([
            'id' => 'orphan:' . self::base64UrlEncode($uri),
            'file_id' => 0,
            'source_type' => 'orphan',
            'source_desc' => '孤儿文件',
            'type' => self::inferType($uri),
            'name' => basename($uri),
            'uri' => $uri,
            'size' => (int)($disk['size'] ?? 0),
            'mtime' => $timestamp,
            'create_time' => 0,
            'exists' => true,
            'reason' => '本地文件未登记到素材表，且未发现业务引用',
        ]);
    }

    private static function formatCandidate(array $candidate): array
    {
        $candidate['type_desc'] = self::typeDesc((int)$candidate['type']);
        $candidate['url'] = FileService::format(request()->domain(), $candidate['uri']);
        $candidate['size_desc'] = self::formatBytes((int)$candidate['size']);
        $candidate['mtime_desc'] = $candidate['mtime'] > 0 ? date('Y-m-d H:i:s', (int)$candidate['mtime']) : '-';
        $candidate['create_time_desc'] = $candidate['create_time'] > 0 ? date('Y-m-d H:i:s', (int)$candidate['create_time']) : '-';
        $candidate['can_delete'] = true;
        $candidate['reference_state'] ??= self::REFERENCE_NONE;
        $candidate['reference_state_desc'] ??= '未发现引用';
        $candidate['reference_sources'] ??= [];
        $candidate['risk_level'] ??= 'low';
        $candidate['risk_level_desc'] ??= '低风险';
        $candidate['risk_tip'] ??= '仅表示在当前启用业务字段中未发现引用，删除前会再次复核。';
        return $candidate;
    }

    private static function matchesFilters(array $candidate, array $params): bool
    {
        if ($params['source'] !== 'all' && $candidate['source_type'] !== $params['source']) {
            return false;
        }

        if ((int)$params['type'] !== 0 && (int)$candidate['type'] !== (int)$params['type']) {
            return false;
        }

        if (($params['reference_state'] ?? 'all') !== 'all'
            && ($candidate['reference_state'] ?? self::REFERENCE_NONE) !== $params['reference_state']
        ) {
            return false;
        }

        if ($params['keyword'] !== '') {
            $keyword = mb_strtolower($params['keyword']);
            $haystack = mb_strtolower(
                ($candidate['name'] ?? '') . ' '
                . ($candidate['uri'] ?? '') . ' '
                . implode(' ', (array)($candidate['reference_sources'] ?? []))
            );
            if (mb_strpos($haystack, $keyword) === false) {
                return false;
            }
        }

        return true;
    }

    private static function deleteRegisteredCandidate(array $candidate, StorageDriver $storageDriver): void
    {
        $fileId = (int)($candidate['file_id'] ?? 0);
        $file = File::where('id', $fileId)->findOrEmpty();
        if ($file->isEmpty()) {
            throw new \RuntimeException('素材记录已不存在');
        }

        $uri = self::normalizeUploadUri((string)$file['uri']);
        if ($uri !== $candidate['uri'] || !self::isEligibleUploadUri($uri)) {
            throw new \RuntimeException('素材路径已变化，已跳过');
        }

        if ($candidate['exists'] && !$storageDriver->delete($uri)) {
            throw new \RuntimeException('本地文件删除失败');
        }

        File::destroy([$fileId]);
    }

    private static function deleteOrphanCandidate(array $candidate): void
    {
        $uri = self::normalizeUploadUri((string)($candidate['uri'] ?? ''));
        $path = self::absoluteUploadPath($uri);
        if (!$path) {
            throw new \RuntimeException('素材路径不安全，已跳过');
        }

        if (is_file($path) && !@unlink($path)) {
            throw new \RuntimeException('本地文件删除失败');
        }
    }

    private static function localStorageDriver(): StorageDriver
    {
        return new StorageDriver([
            'default' => 'local',
            'engine' => ConfigService::get('storage') ?? ['local' => []],
        ], 'local');
    }

    private static function writeCleanupLog(int $adminId, array $data): void
    {
        if (!self::tableExists('file_cleanup_log')) {
            return;
        }

        try {
            FileCleanupLog::create([
                'admin_id' => $adminId,
                'delete_count' => (int)$data['delete_count'],
                'registered_count' => (int)$data['registered_count'],
                'orphan_count' => (int)$data['orphan_count'],
                'free_size' => (int)$data['free_size'],
                'failed_count' => (int)$data['failed_count'],
                'items' => json_encode($data['items'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'failed_detail' => json_encode($data['failed_detail'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'create_time' => time(),
            ]);
        } catch (Throwable) {
            // 审计日志失败不能影响已经完成的清理结果。
        }
    }

    private static function resolveRegisteredTimestamp(array $file, ?array $disk): int
    {
        $createTime = self::parseTimestamp($file['create_time'] ?? 0);
        if ($createTime > 0) {
            return $createTime;
        }

        $updateTime = self::parseTimestamp($file['update_time'] ?? 0);
        if ($updateTime > 0) {
            return $updateTime;
        }

        return (int)($disk['mtime'] ?? 0);
    }

    private static function parseTimestamp($value): int
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        if (is_int($value) || is_float($value)) {
            return max(0, (int)$value);
        }

        $value = trim((string)$value);
        if ($value === '') {
            return 0;
        }

        if (ctype_digit($value)) {
            return max(0, (int)$value);
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? 0 : max(0, $timestamp);
    }

    private static function normalizeUploadUri(string $value): string
    {
        $value = html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace('\\', '/', $value);
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value) ?? '';
        $value = trim($value, " \t\n\r\0\x0B\"'`<>),;");
        $value = preg_split('/[?#]/', $value)[0] ?? '';
        $pos = stripos($value, self::UPLOAD_ROOT . '/');
        if ($pos === false) {
            return '';
        }

        $value = substr($value, $pos);
        $value = rawurldecode($value);
        $value = preg_replace('#/+#', '/', $value) ?? '';
        $value = trim($value, '/');

        if (!self::isSafeUploadUri($value)) {
            return '';
        }

        return $value;
    }

    private static function isEligibleUploadUri(string $uri): bool
    {
        if (!self::isSafeUploadUri($uri)) {
            return false;
        }

        $extension = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
        return $extension !== '' && in_array($extension, self::allowedExtensions(), true);
    }

    private static function isSafeUploadUri(string $uri): bool
    {
        if ($uri === '' || !str_starts_with($uri, self::UPLOAD_ROOT . '/')) {
            return false;
        }

        if (str_contains($uri, '..') || str_contains($uri, "\0")) {
            return false;
        }

        $basename = basename($uri);
        return $basename !== '' && $basename !== 'index.html' && !str_starts_with($basename, '.');
    }

    private static function absoluteUploadPath(string $uri): string
    {
        if (!self::isSafeUploadUri($uri)) {
            return '';
        }

        $publicRoot = realpath(public_path());
        $uploadRoot = realpath(rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR . self::UPLOAD_ROOT);
        if (!$publicRoot || !$uploadRoot) {
            return '';
        }

        $path = $publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $uri);
        $directory = realpath(dirname($path));
        if (!$directory || !str_starts_with($directory, $uploadRoot)) {
            return '';
        }

        return $path;
    }

    private static function allowedExtensions(): array
    {
        if (self::$allowedExtensions !== null) {
            return self::$allowedExtensions;
        }

        $image = config('project.file_image') ?: [];
        $video = config('project.file_video') ?: [];
        $file = config('project.file_file') ?: [];
        self::$allowedExtensions = array_values(array_unique(array_map('strtolower', array_merge($image, $video, $file))));

        return self::$allowedExtensions;
    }

    private static function extensionTypeMap(): array
    {
        if (self::$extensionTypeMap !== null) {
            return self::$extensionTypeMap;
        }

        self::$extensionTypeMap = [];
        foreach ((config('project.file_image') ?: []) as $extension) {
            self::$extensionTypeMap[strtolower($extension)] = 10;
        }
        foreach ((config('project.file_video') ?: []) as $extension) {
            self::$extensionTypeMap[strtolower($extension)] = 20;
        }
        foreach ((config('project.file_file') ?: []) as $extension) {
            self::$extensionTypeMap[strtolower($extension)] = 30;
        }

        return self::$extensionTypeMap;
    }

    private static function inferType(string $uri): int
    {
        $extension = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
        return self::extensionTypeMap()[$extension] ?? 30;
    }

    private static function typeDesc(int $type): string
    {
        return match ($type) {
            10 => '图片',
            20 => '视频',
            default => '文件',
        };
    }

    private static function existingFields(string $table, array $fields): array
    {
        $tableFields = self::getTableFields($table);
        if (!$tableFields) {
            return [];
        }

        return array_values(array_filter($fields, static function (string $field) use ($tableFields): bool {
            return isset($tableFields[$field]);
        }));
    }

    private static function tableExists(string $table): bool
    {
        return (bool)self::getTableFields($table);
    }

    private static function getTableFields(string $table): array
    {
        if (array_key_exists($table, self::$tableFields)) {
            return self::$tableFields[$table];
        }

        try {
            $fields = Db::name($table)->getFields();
            self::$tableFields[$table] = is_array($fields) ? $fields : [];
        } catch (Throwable) {
            self::$tableFields[$table] = [];
        }

        return self::$tableFields[$table];
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;
        $size = (float)$bytes;
        while ($size >= 1024 && $index < count($units) - 1) {
            $size /= 1024;
            $index++;
        }

        return round($size, $index === 0 ? 0 : 2) . ' ' . $units[$index];
    }
}
