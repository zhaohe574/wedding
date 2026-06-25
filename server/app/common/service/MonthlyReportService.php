<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 单量月报服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\order\Payment;
use app\common\model\service\ServiceCategory;
use app\common\model\staff\MonthlyReport;
use app\common\model\staff\MonthlyReportMaterial;
use app\common\model\staff\MonthlyReportTemplate;
use app\common\model\staff\Staff;
use app\common\service\storage\Driver as StorageDriver;
use think\facade\Db;
use think\facade\Log;

class MonthlyReportService
{
    public const RENDER_SPEC_VERSION = 'monthly-report-designer-v1';

    public const ERROR_ASSET_RUNTIME = 'MONTHLY_REPORT_ASSET_RUNTIME';
    public const ERROR_ASSET_RENDER = 'MONTHLY_REPORT_ASSET_RENDER';
    public const ERROR_ASSET_DIRECTORY = 'MONTHLY_REPORT_ASSET_DIRECTORY';
    public const ERROR_ASSET_UPLOAD = 'MONTHLY_REPORT_ASSET_UPLOAD';
    public const ERROR_CONFIRM_BLOCKED = 'MONTHLY_REPORT_CONFIRM_BLOCKED';

    protected const ASSET_STORAGE_DIR = 'uploads/monthly-report';
    protected const ASSET_TEMP_DIR = 'monthly_report';
    protected const ASSET_FILE_EXTENSION = 'jpg';
    protected const ASSET_FILE_VERSION = 'r1';
    protected const ASSET_RASTER_RESOLUTION = 96;
    protected const ASSET_JPEG_QUALITY = 84;
    protected const ASSET_MIN_VALID_BYTES = 4096;
    protected const SVG_IMAGE_MAX_BYTES = 12 * 1024 * 1024;
    protected const SVG_TRANSPARENT_PIXEL = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';

    public static function normalizeErrorMessage(string $message): string
    {
        return match ($message) {
            self::ERROR_ASSET_RUNTIME => '服务器缺少图片渲染组件，请检查 Imagick 环境',
            self::ERROR_ASSET_RENDER => '月报图片渲染失败，请检查模板图片和字体配置',
            self::ERROR_ASSET_DIRECTORY => '月报图片目录不可写',
            self::ERROR_ASSET_UPLOAD => '月报图片上传失败',
            self::ERROR_CONFIRM_BLOCKED => '月报存在阻断问题，请处理后再确认',
            default => $message,
        };
    }

    public static function materialLists(array $params): array
    {
        $page = max(1, (int)($params['page_no'] ?? $params['page'] ?? 1));
        $pageSize = max(1, min(100, (int)($params['page_size'] ?? 20)));
        $query = MonthlyReportMaterial::with(['staff'])
            ->whereNull('delete_time')
            ->order('sort desc, id desc');

        if (($params['status'] ?? '') !== '') {
            $query->where('status', (int)$params['status']);
        }
        $keyword = trim((string)($params['keyword'] ?? ''));
        if ($keyword !== '') {
            $staffIds = Staff::whereLike('name|sn', '%' . $keyword . '%')->column('id');
            $query->where(function ($q) use ($keyword, $staffIds) {
                $q->whereLike('english_name', '%' . $keyword . '%');
                if (!empty($staffIds)) {
                    $q->whereOr('staff_id', 'in', $staffIds);
                }
            });
        }
        if (!empty($params['category_id'])) {
            $staffIds = Staff::where('category_id', (int)$params['category_id'])->column('id');
            $query->whereIn('staff_id', !empty($staffIds) ? $staffIds : [0]);
        }

        $total = (clone $query)->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        return [
            'lists' => $list,
            'list' => $list,
            'total' => $total,
            'count' => $total,
            'page_no' => $page,
            'page_size' => $pageSize,
        ];
    }

    public static function materialSave(array $params): array
    {
        $id = (int)($params['id'] ?? 0);
        $staffId = (int)($params['staff_id'] ?? 0);
        if ($staffId <= 0) {
            throw new \RuntimeException('请选择绑定服务人员');
        }
        $staff = Staff::find($staffId);
        if (!$staff) {
            throw new \RuntimeException('服务人员不存在');
        }

        $exists = MonthlyReportMaterial::where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->when($id > 0, static function ($query) use ($id) {
                $query->where('id', '<>', $id);
            })
            ->find();
        if ($exists) {
            throw new \RuntimeException('该服务人员已绑定月报素材');
        }

        $hasAvatarPhoto = array_key_exists('avatar_photo', $params);
        $hasHalfBodyPhoto = array_key_exists('half_body_photo', $params);
        $legacyPhoto = self::normalizeStoredFileUrl((string)($params['photo'] ?? ''));
        $avatarPhoto = self::normalizeStoredFileUrl((string)($params['avatar_photo'] ?? ''));
        $halfBodyPhoto = self::normalizeStoredFileUrl((string)($params['half_body_photo'] ?? ''));
        if (!$hasAvatarPhoto && $avatarPhoto === '' && $legacyPhoto !== '') {
            $avatarPhoto = $legacyPhoto;
        }
        if (!$hasHalfBodyPhoto && $halfBodyPhoto === '' && $legacyPhoto !== '') {
            $halfBodyPhoto = $legacyPhoto;
        }
        if ($legacyPhoto === '') {
            $legacyPhoto = $avatarPhoto;
        }

        $sort = array_key_exists('sort', $params)
            ? max(0, (int)$params['sort'])
            : ($id > 0 ? null : 0);

        $payload = [
            'staff_id' => $staffId,
            'photo' => $legacyPhoto,
            'avatar_photo' => $avatarPhoto,
            'half_body_photo' => $halfBodyPhoto,
            'english_name' => mb_substr(trim((string)($params['english_name'] ?? '')), 0, 80, 'UTF-8'),
            'chinese_name' => mb_substr(trim((string)$staff->name), 0, 80, 'UTF-8'),
            'status' => (int)($params['status'] ?? MonthlyReportMaterial::STATUS_ENABLED) === MonthlyReportMaterial::STATUS_ENABLED
                ? MonthlyReportMaterial::STATUS_ENABLED
                : MonthlyReportMaterial::STATUS_DISABLED,
            'update_time' => time(),
        ];
        if ($sort !== null) {
            $payload['sort'] = $sort;
        }

        if ($id > 0) {
            $material = MonthlyReportMaterial::where('id', $id)->whereNull('delete_time')->find();
            if (!$material) {
                throw new \RuntimeException('月报素材不存在');
            }
            $material->save($payload);
        } else {
            $payload['create_time'] = time();
            $material = MonthlyReportMaterial::create($payload);
        }

        return self::formatMaterial($material);
    }

    public static function materialByStaffId(int $staffId): array
    {
        if ($staffId <= 0) {
            return self::emptyMaterial($staffId);
        }

        $material = MonthlyReportMaterial::where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->find();

        return $material ? self::formatMaterial($material) : self::emptyMaterial($staffId);
    }

    public static function materialSaveForStaff(int $staffId, array $params): array
    {
        if ($staffId <= 0) {
            throw new \RuntimeException('请选择绑定服务人员');
        }

        $material = MonthlyReportMaterial::where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->find();
        $current = $material ? self::formatMaterial($material) : self::emptyMaterial($staffId);

        $avatarPhoto = array_key_exists('avatar_photo', $params)
            ? self::normalizeStoredFileUrl((string)$params['avatar_photo'])
            : (string)($current['avatar_photo'] ?? '');
        $halfBodyPhoto = array_key_exists('half_body_photo', $params)
            ? self::normalizeStoredFileUrl((string)$params['half_body_photo'])
            : (string)($current['half_body_photo'] ?? '');
        $englishName = array_key_exists('english_name', $params)
            ? (string)$params['english_name']
            : (string)($current['english_name'] ?? '');
        $staff = Staff::field('id,name')->find($staffId);
        $chineseName = (string)($staff->name ?? $current['staff_name'] ?? '');

        if (!$material && $avatarPhoto === '' && $halfBodyPhoto === '' && trim($englishName) === '' && trim($chineseName) === '') {
            return $current;
        }

        $legacyPhoto = array_key_exists('photo', $params)
            ? self::normalizeStoredFileUrl((string)$params['photo'])
            : '';

        $saveParams = [
            'id' => (int)($current['id'] ?? 0),
            'staff_id' => $staffId,
            'photo' => $legacyPhoto,
            'avatar_photo' => $avatarPhoto,
            'half_body_photo' => $halfBodyPhoto,
            'english_name' => $englishName,
            'chinese_name' => $chineseName,
            'status' => array_key_exists('status', $params)
                ? (int)$params['status']
                : (int)($current['status'] ?? MonthlyReportMaterial::STATUS_ENABLED),
        ];
        if (array_key_exists('sort', $params)) {
            $saveParams['sort'] = (int)$params['sort'];
        }

        return self::materialSave($saveParams);
    }

    public static function materialDelete(int $id): bool
    {
        $material = MonthlyReportMaterial::where('id', $id)->whereNull('delete_time')->find();
        if (!$material) {
            throw new \RuntimeException('月报素材不存在');
        }
        return (bool)$material->delete();
    }

    public static function staffOptions(array $params = []): array
    {
        $query = Staff::where('status', Staff::STATUS_ENABLE)
            ->whereNull('delete_time')
            ->field('id,sn,name,avatar,category_id,status,sort');
        if (!empty($params['category_id'])) {
            $query->where('category_id', (int)$params['category_id']);
        }
        $keyword = trim((string)($params['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->whereLike('name|sn', '%' . $keyword . '%');
        }
        return $query->order('sort desc,id desc')->limit(200)->select()->toArray();
    }

    public static function categoryOptions(): array
    {
        return ServiceCategory::whereNull('delete_time')
            ->where('is_show', 1)
            ->field('id,name,sort')
            ->order('sort desc,id asc')
            ->select()
            ->toArray();
    }

    public static function templateConfig(string $type, int $templateId = 0, bool $includeDisabled = true): array
    {
        $type = self::normalizeTemplateType($type);
        if ($templateId > 0) {
            $template = MonthlyReportTemplate::where('id', $templateId)
                ->where('template_type', $type)
                ->whereNull('delete_time')
                ->find();
        } else {
            $template = MonthlyReportTemplate::where('template_type', $type)
                ->where('status', MonthlyReportTemplate::STATUS_ENABLED)
                ->whereNull('delete_time')
                ->order('is_default desc, sort desc, id asc')
                ->find();
        }

        if (!$template) {
            $template = self::createDefaultTemplate($type);
        }

        $data = self::formatTemplate($template);
        $data['versions'] = self::templateList($type, $includeDisabled);
        return $data;
    }

    public static function templateList(string $type, bool $includeDisabled = true): array
    {
        $type = self::normalizeTemplateType($type);
        $query = MonthlyReportTemplate::where('template_type', $type)->whereNull('delete_time');
        if (!$includeDisabled) {
            $query->where('status', MonthlyReportTemplate::STATUS_ENABLED);
        }
        return $query->order('status desc,is_default desc,sort desc,id asc')
            ->select()
            ->map(static fn(MonthlyReportTemplate $template): array => self::formatTemplateSummary($template))
            ->toArray();
    }

    public static function templateSave(array $params): array
    {
        $type = self::normalizeTemplateType((string)($params['template_type'] ?? MonthlyReportTemplate::TYPE_ADDITION));
        $id = (int)($params['template_id'] ?? $params['id'] ?? 0);
        $templateName = trim((string)($params['template_name'] ?? ''));
        if ($templateName === '') {
            $templateName = MonthlyReportTemplate::getTypeText($type) . '模板';
        }

        $designConfig = is_array($params['design_config'] ?? null)
            ? self::normalizeDesignConfig($params['design_config'], $type)
            : self::defaultDesignConfig($type);
        $payload = [
            'template_type' => $type,
            'template_name' => mb_substr($templateName, 0, 80, 'UTF-8'),
            'is_default' => (int)($params['is_default'] ?? 0) === 1 ? 1 : 0,
            'status' => (int)($params['status'] ?? MonthlyReportTemplate::STATUS_ENABLED) === MonthlyReportTemplate::STATUS_ENABLED
                ? MonthlyReportTemplate::STATUS_ENABLED
                : MonthlyReportTemplate::STATUS_DISABLED,
            'sort' => max(0, (int)($params['sort'] ?? 0)),
            'design_version' => self::RENDER_SPEC_VERSION,
            'design_config' => $designConfig,
            'update_time' => time(),
        ];

        return Db::transaction(function () use ($id, $type, $payload) {
            if ($id > 0) {
                $template = MonthlyReportTemplate::where('id', $id)->whereNull('delete_time')->lock(true)->find();
                if (!$template) {
                    throw new \RuntimeException('月报模板不存在');
                }
                $template->save($payload);
            } else {
                $payload['template_version'] = self::nextTemplateVersion($type);
                $payload['create_time'] = time();
                $template = MonthlyReportTemplate::create($payload);
            }

            if ((int)$template->is_default === 1 && (int)$template->status === MonthlyReportTemplate::STATUS_ENABLED) {
                self::markTemplateDefault((int)$template->id, $type);
            }

            $data = self::formatTemplate($template);
            $data['versions'] = self::templateList($type, true);
            return $data;
        });
    }

    public static function templateCopy(int $templateId, string $name = ''): array
    {
        $template = MonthlyReportTemplate::where('id', $templateId)->whereNull('delete_time')->find();
        if (!$template) {
            throw new \RuntimeException('月报模板不存在');
        }
        $type = (string)$template->template_type;
        $copy = MonthlyReportTemplate::create([
            'template_type' => $type,
            'template_name' => mb_substr(trim($name) !== '' ? trim($name) : ((string)$template->template_name . ' 副本'), 0, 80, 'UTF-8'),
            'template_version' => self::nextTemplateVersion($type),
            'is_default' => 0,
            'status' => MonthlyReportTemplate::STATUS_ENABLED,
            'sort' => (int)$template->sort,
            'design_version' => self::RENDER_SPEC_VERSION,
            'design_config' => self::normalizeDesignConfig(is_array($template->design_config) ? $template->design_config : [], $type),
            'create_time' => time(),
            'update_time' => time(),
        ]);
        $data = self::formatTemplate($copy);
        $data['versions'] = self::templateList($type, true);
        return $data;
    }

    public static function templateSetDefault(int $templateId): array
    {
        $template = MonthlyReportTemplate::where('id', $templateId)->whereNull('delete_time')->find();
        if (!$template) {
            throw new \RuntimeException('月报模板不存在');
        }
        if ((int)$template->status !== MonthlyReportTemplate::STATUS_ENABLED) {
            throw new \RuntimeException('停用模板不能设为默认');
        }
        self::markTemplateDefault((int)$template->id, (string)$template->template_type);
        $template->is_default = 1;
        $template->update_time = time();
        $template->save();
        $data = self::formatTemplate($template);
        $data['versions'] = self::templateList((string)$template->template_type, true);
        return $data;
    }

    public static function templateDisable(int $templateId): array
    {
        $template = MonthlyReportTemplate::where('id', $templateId)->whereNull('delete_time')->find();
        if (!$template) {
            throw new \RuntimeException('月报模板不存在');
        }
        $activeCount = MonthlyReportTemplate::where('template_type', (string)$template->template_type)
            ->where('status', MonthlyReportTemplate::STATUS_ENABLED)
            ->whereNull('delete_time')
            ->count();
        if ($activeCount <= 1 && (int)$template->status === MonthlyReportTemplate::STATUS_ENABLED) {
            throw new \RuntimeException('至少保留一个启用模板');
        }
        $template->status = MonthlyReportTemplate::STATUS_DISABLED;
        $template->is_default = 0;
        $template->update_time = time();
        $template->save();
        $data = self::formatTemplate($template);
        $data['versions'] = self::templateList((string)$template->template_type, true);
        return $data;
    }

    public static function templatePreview(array $params): array
    {
        $type = self::normalizeTemplateType((string)($params['template_type'] ?? MonthlyReportTemplate::TYPE_ADDITION));
        $config = is_array($params['design_config'] ?? null)
            ? self::normalizeDesignConfig($params['design_config'], $type)
            : self::templateConfig($type)['design_config'];
        $snapshot = self::buildRenderSnapshot($type, [
            'report_month' => self::normalizeReportMonth((string)($params['report_month'] ?? '')),
            'addition_count' => 204,
            'executed_count' => 233,
            'ranking_staffs' => self::demoStaffs(18),
            'top_staffs' => self::demoStaffs(2, 23),
        ], $config);

        $svgContent = MonthlyReportRenderer::render($snapshot, [
            'font_options' => OrderConfirmLetterFontService::getActiveFontOptions(),
        ]);

        return [
            'render_spec_version' => self::RENDER_SPEC_VERSION,
            'rendered_snapshot' => $snapshot,
            'snapshot_hash' => self::buildSnapshotHash($snapshot),
            'svg_content' => self::prepareSvgForRasterization($svgContent),
        ];
    }

    public static function preview(array $params): array
    {
        $context = self::buildPreviewContext($params);
        return self::formatPreviewContext($context, true);
    }

    public static function confirm(array $params, int $adminId): array
    {
        $context = self::buildPreviewContext($params);
        if (self::hasBlockingIssues($context['issues'])) {
            throw new \RuntimeException(self::ERROR_CONFIRM_BLOCKED);
        }

        return Db::transaction(function () use ($context, $adminId) {
            $reportMonth = (string)$context['report_month'];
            MonthlyReport::where('report_month', $reportMonth)
                ->where('is_current', MonthlyReport::CURRENT_YES)
                ->update([
                    'is_current' => MonthlyReport::CURRENT_NO,
                    'update_time' => time(),
                ]);

            $version = (int)MonthlyReport::where('report_month', $reportMonth)->max('version') + 1;
            $assets = self::persistReportAssets($reportMonth, $version, $context['rendered_snapshots']);

            $report = MonthlyReport::create([
                'report_month' => $reportMonth,
                'version' => $version,
                'is_current' => MonthlyReport::CURRENT_YES,
                'category_ids' => $context['category_ids'],
                'addition_count' => (int)$context['final_snapshot']['addition_count'],
                'executed_count' => (int)$context['final_snapshot']['executed_count'],
                'top_count' => (int)$context['final_snapshot']['top_count'],
                'auto_snapshot' => $context['auto_snapshot'],
                'final_snapshot' => $context['final_snapshot'],
                'template_snapshot' => $context['template_snapshot'],
                'rendered_snapshot' => $context['rendered_snapshots'],
                'snapshot_hash' => self::buildSnapshotHash($context['rendered_snapshots']),
                'issues' => $context['issues'],
                'addition_image_url' => (string)($assets['addition'] ?? ''),
                'ranking_image_url' => (string)($assets['ranking'] ?? ''),
                'top_image_url' => (string)($assets['top'] ?? ''),
                'confirm_admin_id' => $adminId,
                'confirm_time' => time(),
                'create_time' => time(),
                'update_time' => time(),
            ]);

            return self::formatReport($report);
        });
    }

    public static function latest(string $reportMonth = ''): ?array
    {
        $query = MonthlyReport::where('is_current', MonthlyReport::CURRENT_YES)
            ->whereNull('delete_time')
            ->order('report_month desc,id desc');
        if (trim($reportMonth) !== '') {
            $query->where('report_month', self::normalizeReportMonth($reportMonth));
        }
        $report = $query->find();
        return $report ? self::formatReport($report) : null;
    }

    public static function history(array $params): array
    {
        $page = max(1, (int)($params['page_no'] ?? $params['page'] ?? 1));
        $pageSize = max(1, min(100, (int)($params['page_size'] ?? 20)));
        $query = MonthlyReport::whereNull('delete_time')->order('report_month desc, version desc');
        if (!empty($params['report_month'])) {
            $query->where('report_month', self::normalizeReportMonth((string)$params['report_month']));
        }
        $total = (clone $query)->count();
        $list = $query->page($page, $pageSize)->select()
            ->map(static fn(MonthlyReport $report): array => self::formatReportSummary($report))
            ->toArray();
        return [
            'lists' => $list,
            'list' => $list,
            'total' => $total,
            'count' => $total,
            'page_no' => $page,
            'page_size' => $pageSize,
        ];
    }

    public static function detail(int $id): ?array
    {
        $report = MonthlyReport::where('id', $id)->whereNull('delete_time')->find();
        return $report ? self::formatReport($report) : null;
    }

    public static function regenerateAssets(int $id, string $snapshotHash = ''): array
    {
        return Db::transaction(function () use ($id, $snapshotHash) {
            $report = MonthlyReport::where('id', $id)->whereNull('delete_time')->lock(true)->find();
            if (!$report) {
                throw new \RuntimeException('月报记录不存在');
            }
            if ($snapshotHash !== '' && (string)$report->snapshot_hash !== $snapshotHash) {
                throw new \RuntimeException('月报快照已变化，请刷新后重试');
            }
            $renderedSnapshots = is_array($report->rendered_snapshot) ? $report->rendered_snapshot : [];
            if (empty($renderedSnapshots)) {
                throw new \RuntimeException(self::ERROR_ASSET_RENDER);
            }
            $assets = self::persistReportAssets((string)$report->report_month, (int)$report->version, $renderedSnapshots, true);
            $report->addition_image_url = (string)($assets['addition'] ?? '');
            $report->ranking_image_url = (string)($assets['ranking'] ?? '');
            $report->top_image_url = (string)($assets['top'] ?? '');
            $report->update_time = time();
            $report->save();
            return self::formatReport($report);
        });
    }

    protected static function buildPreviewContext(array $params): array
    {
        $reportMonth = self::normalizeReportMonth((string)($params['report_month'] ?? ''));
        $categoryIds = self::normalizeIds($params['category_ids'] ?? []);
        $autoSnapshot = self::buildAutoSnapshot($reportMonth, $categoryIds);
        $finalSnapshot = self::mergeFinalSnapshot($autoSnapshot, is_array($params['draft'] ?? null) ? $params['draft'] : []);
        $templates = self::resolveTemplates(is_array($params['template_ids'] ?? null) ? $params['template_ids'] : []);
        $issues = self::buildIssues($reportMonth, $categoryIds, $finalSnapshot, $templates);
        $renderedSnapshots = self::buildRenderedSnapshots($reportMonth, $finalSnapshot, $templates);

        return [
            'report_month' => $reportMonth,
            'category_ids' => $categoryIds,
            'auto_snapshot' => $autoSnapshot,
            'final_snapshot' => $finalSnapshot,
            'template_snapshot' => self::formatTemplateSnapshot($templates),
            'rendered_snapshots' => $renderedSnapshots,
            'issues' => $issues,
        ];
    }

    protected static function buildAutoSnapshot(string $reportMonth, array $categoryIds): array
    {
        [$startDate, $endDate, $startTime, $endTime] = self::resolveMonthRange($reportMonth);
        $validOrderStatuses = self::validOrderStatuses();
        $invalidPayStatuses = self::invalidPayStatuses();
        $itemTypes = [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF];
        $categoryIds = !empty($categoryIds) ? $categoryIds : [0];

        $firstPaymentSql = Payment::alias('p')
            ->field('p.order_id, MIN(p.pay_time) AS first_pay_time')
            ->where('p.pay_status', Payment::STATUS_PAID)
            ->whereIn('p.pay_type', [Payment::TYPE_DEPOSIT, Payment::TYPE_FULL])
            ->where('p.pay_time', '>', 0)
            ->group('p.order_id')
            ->buildSql();

        $additionCount = (int)OrderItem::alias('oi')
            ->leftJoin('la_order o', 'o.id = oi.order_id')
            ->leftJoin('la_staff s', 's.id = oi.staff_id')
            ->join([$firstPaymentSql => 'fp'], 'fp.order_id = oi.order_id')
            ->whereBetween('fp.first_pay_time', [$startTime, $endTime])
            ->whereIn('s.category_id', $categoryIds)
            ->whereIn('oi.item_type', $itemTypes)
            ->where('oi.item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->whereIn('o.order_status', $validOrderStatuses)
            ->whereNotIn('o.pay_status', $invalidPayStatuses)
            ->whereNull('o.delete_time')
            ->whereNull('s.delete_time')
            ->count();

        $rows = OrderItem::alias('oi')
            ->leftJoin('la_order o', 'o.id = oi.order_id')
            ->leftJoin('la_staff s', 's.id = oi.staff_id')
            ->field([
                'oi.staff_id',
                'MAX(oi.staff_name) AS order_staff_name',
                'MAX(s.name) AS staff_name',
                'MAX(s.category_id) AS category_id',
                'COUNT(oi.id) AS count',
            ])
            ->whereBetween('oi.service_date', [$startDate, $endDate])
            ->whereIn('s.category_id', $categoryIds)
            ->whereIn('oi.item_type', $itemTypes)
            ->where('oi.item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->whereIn('o.order_status', $validOrderStatuses)
            ->whereNotIn('o.pay_status', $invalidPayStatuses)
            ->whereNull('o.delete_time')
            ->whereNull('s.delete_time')
            ->group('oi.staff_id')
            ->select()
            ->toArray();

        $materials = self::materialMap(array_map(static fn($row) => (int)($row['staff_id'] ?? 0), $rows));
        $ranking = [];
        foreach ($rows as $row) {
            $staffId = (int)($row['staff_id'] ?? 0);
            $material = $materials[$staffId] ?? null;
            $count = (int)($row['count'] ?? 0);
            if ($count <= 0) {
                continue;
            }
            $ranking[] = self::buildStaffSnapshotRow($staffId, $count, (string)($row['staff_name'] ?: $row['order_staff_name'] ?: ''), $material);
        }

        usort($ranking, static function (array $a, array $b): int {
            if ((int)$a['count_raw'] !== (int)$b['count_raw']) {
                return (int)$b['count_raw'] <=> (int)$a['count_raw'];
            }
            if ((int)$a['material_sort'] !== (int)$b['material_sort']) {
                return (int)$b['material_sort'] <=> (int)$a['material_sort'];
            }
            return (int)$a['staff_id'] <=> (int)$b['staff_id'];
        });

        foreach ($ranking as $index => &$item) {
            $item['rank'] = $index + 1;
        }
        unset($item);

        $executedCount = array_sum(array_map(static fn($row) => (int)($row['count_raw'] ?? 0), $ranking));
        $topCount = !empty($ranking) ? (int)$ranking[0]['count_raw'] : 0;
        $topStaffs = array_values(array_filter($ranking, static fn($row) => (int)($row['count_raw'] ?? 0) === $topCount && $topCount > 0));

        return [
            'report_month' => $reportMonth,
            'report_month_label' => self::formatReportMonthLabel($reportMonth),
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ],
            'category_ids' => $categoryIds === [0] ? [] : $categoryIds,
            'addition_count' => $additionCount,
            'executed_count' => $executedCount,
            'top_count' => $topCount,
            'ranking_staffs' => $ranking,
            'top_staffs' => $topStaffs,
            'copywriting' => self::defaultCopywriting($reportMonth),
        ];
    }

    protected static function mergeFinalSnapshot(array $autoSnapshot, array $draft): array
    {
        $snapshot = $autoSnapshot;
        foreach (['addition_count', 'executed_count', 'top_count'] as $key) {
            if (array_key_exists($key, $draft)) {
                $snapshot[$key] = max(0, (int)$draft[$key]);
            }
        }
        if (isset($draft['copywriting']) && is_array($draft['copywriting'])) {
            $snapshot['copywriting'] = array_merge($snapshot['copywriting'] ?? [], self::sanitizeCopywriting($draft['copywriting']));
        }

        if (isset($draft['ranking_staffs']) && is_array($draft['ranking_staffs'])) {
            $snapshot['ranking_staffs'] = self::mergeStaffRows($snapshot['ranking_staffs'] ?? [], $draft['ranking_staffs']);
            if (!array_key_exists('executed_count', $draft)) {
                $snapshot['executed_count'] = array_sum(array_map(static fn($row) => (int)($row['count_raw'] ?? 0), $snapshot['ranking_staffs']));
            }
        }

        usort($snapshot['ranking_staffs'], static function (array $a, array $b): int {
            if ((int)$a['count_raw'] !== (int)$b['count_raw']) {
                return (int)$b['count_raw'] <=> (int)$a['count_raw'];
            }
            return (int)$a['rank'] <=> (int)$b['rank'];
        });
        foreach ($snapshot['ranking_staffs'] as $index => &$row) {
            $row['rank'] = $index + 1;
            $row['count'] = self::formatCount((int)$row['count_raw']);
        }
        unset($row);

        $topCount = !empty($snapshot['ranking_staffs']) ? (int)$snapshot['ranking_staffs'][0]['count_raw'] : 0;
        $snapshot['top_count'] = $topCount;
        $snapshot['top_staffs'] = array_values(array_filter($snapshot['ranking_staffs'], static fn($row) => (int)($row['count_raw'] ?? 0) === $topCount && $topCount > 0));

        return $snapshot;
    }

    protected static function mergeStaffRows(array $baseRows, array $draftRows): array
    {
        $baseMap = [];
        foreach ($baseRows as $row) {
            $baseMap[(int)($row['staff_id'] ?? 0)] = $row;
        }
        $draftStaffIds = [];
        foreach ($draftRows as $draftRow) {
            if (is_array($draftRow) && (int)($draftRow['staff_id'] ?? 0) > 0) {
                $draftStaffIds[] = (int)$draftRow['staff_id'];
            }
        }
        $extraStaffIds = array_values(array_diff(array_unique($draftStaffIds), array_keys($baseMap)));
        $extraMaterials = self::materialMap($extraStaffIds);
        $extraStaffMap = [];
        if (!empty($extraStaffIds)) {
            $extraStaffRows = Staff::whereIn('id', $extraStaffIds)
                ->whereNull('delete_time')
                ->field('id,name')
                ->select()
                ->toArray();
            foreach ($extraStaffRows as $staffRow) {
                $extraStaffMap[(int)($staffRow['id'] ?? 0)] = (string)($staffRow['name'] ?? '');
            }
        }

        $result = [];
        foreach ($draftRows as $index => $draftRow) {
            if (!is_array($draftRow)) {
                continue;
            }
            $staffId = (int)($draftRow['staff_id'] ?? 0);
            if ($staffId <= 0) {
                continue;
            }
            if (isset($baseMap[$staffId])) {
                $row = $baseMap[$staffId];
            } else {
                $staffName = trim((string)($draftRow['staff_name'] ?? $extraStaffMap[$staffId] ?? ''));
                if ($staffName === '') {
                    continue;
                }
                $row = self::buildStaffSnapshotRow($staffId, 0, $staffName, $extraMaterials[$staffId] ?? null);
            }
            if (array_key_exists('count', $draftRow) || array_key_exists('count_raw', $draftRow)) {
                $row['count_raw'] = max(0, (int)($draftRow['count_raw'] ?? $draftRow['count'] ?? 0));
                $row['count'] = self::formatCount((int)$row['count_raw']);
            }
            if ((int)$row['count_raw'] <= 0) {
                continue;
            }
            $row['rank'] = $index + 1;
            $result[] = $row;
        }
        return $result;
    }

    protected static function buildIssues(string $reportMonth, array $categoryIds, array $snapshot, array $templates): array
    {
        $issues = [];
        if (empty($categoryIds)) {
            $issues[] = self::issue('category_required', '请选择本次月报统计的服务分类', true);
        }
        if (count($snapshot['top_staffs'] ?? []) > 3) {
            $issues[] = self::issue('top_tie_overflow', '并列单王超过 3 人，请修正场次或排序后再确认', true);
        }

        foreach (($snapshot['ranking_staffs'] ?? []) as $row) {
            if ((int)($row['ranking_material_complete'] ?? $row['material_complete'] ?? 0) !== 1) {
                $issues[] = self::issue(
                    'missing_ranking_material',
                    sprintf('「%s」缺少头像素材或英文名/拼音，请先补齐月报素材', (string)($row['staff_name'] ?? '服务人员')),
                    true,
                    ['staff_id' => (int)($row['staff_id'] ?? 0)]
                );
            }
        }
        foreach (($snapshot['top_staffs'] ?? []) as $row) {
            if ((int)($row['top_material_complete'] ?? $row['material_complete'] ?? 0) !== 1) {
                $issues[] = self::issue(
                    'missing_top_material',
                    sprintf('「%s」缺少半身素材或英文名/拼音，请先补齐月报素材', (string)($row['staff_name'] ?? '服务人员')),
                    true,
                    ['staff_id' => (int)($row['staff_id'] ?? 0)]
                );
            }
        }

        $qrcodeImage = self::getGlobalQrcodeImage();
        foreach ($templates as $type => $template) {
            $design = is_array($template['design_config'] ?? null) ? $template['design_config'] : [];
            if (self::designContainsQrcode($design) && $qrcodeImage === '') {
                $issues[] = self::issue('qrcode_missing', MonthlyReportTemplate::getTypeText((string)$type) . '模板包含二维码，但全局二维码未配置', true);
            }
        }

        if (empty($snapshot['ranking_staffs'])) {
            $issues[] = self::issue('empty_ranking', self::formatReportMonthLabel($reportMonth) . '暂无有效执行场次，确认后榜单为空', false);
        }

        return $issues;
    }

    protected static function buildRenderedSnapshots(string $reportMonth, array $finalSnapshot, array $templates): array
    {
        $result = [];
        foreach (MonthlyReportTemplate::types() as $type) {
            $template = $templates[$type];
            $result[$type] = self::buildRenderSnapshot($type, $finalSnapshot, $template['design_config']);
        }
        return $result;
    }

    protected static function buildRenderSnapshot(string $type, array $snapshot, array $designConfig): array
    {
        $qrcodeImage = self::getGlobalQrcodeImage();
        $designConfig = self::buildSnapshotDesignConfig($designConfig, $type, $qrcodeImage);
        if ($type === MonthlyReportTemplate::TYPE_RANKING) {
            $designConfig = self::applyRankingAutoHeight($designConfig, is_array($snapshot['ranking_staffs'] ?? null) ? $snapshot['ranking_staffs'] : []);
        }
        $copywriting = is_array($snapshot['copywriting'] ?? null) ? $snapshot['copywriting'] : [];
        $reportMonth = (string)($snapshot['report_month'] ?? self::defaultReportMonth());
        $variables = [
            'report_month' => $reportMonth,
            'report_month_label' => (string)($snapshot['report_month_label'] ?? self::formatReportMonthLabel($reportMonth)),
            'addition_count' => (string)($snapshot['addition_count'] ?? 0),
            'executed_count' => (string)($snapshot['executed_count'] ?? 0),
            'top_count' => (string)($snapshot['top_count'] ?? 0),
            'addition_title' => (string)($copywriting['addition_title'] ?? 'ADDITION'),
            'addition_subtitle' => (string)($copywriting['addition_subtitle'] ?? self::formatReportMonthLabel($reportMonth) . '新增婚礼档期'),
            'ranking_title' => (string)($copywriting['ranking_title'] ?? self::formatReportMonthLabel($reportMonth) . '共计执行'),
            'top_title' => (string)($copywriting['top_title'] ?? 'THE MOST'),
            'footer_note' => (string)($copywriting['footer_note'] ?? '感谢您的选择'),
        ];

        $rankingStaffs = self::prepareRenderStaffRows($snapshot['ranking_staffs'] ?? [], MonthlyReportTemplate::TYPE_RANKING);
        $topStaffs = self::prepareRenderStaffRows($snapshot['top_staffs'] ?? [], MonthlyReportTemplate::TYPE_TOP);
        $variables['top_staff_names'] = implode('、', array_values(array_filter(array_map(static function (array $row): string {
            return trim((string)($row['chinese_name'] ?? ''));
        }, $topStaffs))));

        return [
            'render_spec_version' => self::RENDER_SPEC_VERSION,
            'design_version' => self::RENDER_SPEC_VERSION,
            'template_type' => $type,
            'report_month' => $reportMonth,
            'report_month_label' => $variables['report_month_label'],
            'design_config' => $designConfig,
            'variables' => $variables,
            'ranking_staffs' => $rankingStaffs,
            'top_staffs' => $topStaffs,
            'qrcode_image' => $qrcodeImage !== '' ? self::formatPublicImageUrl($qrcodeImage) : '',
        ];
    }

    protected static function prepareRenderStaffRows(array $rows, string $source): array
    {
        return array_values(array_map(static function ($row) use ($source): array {
            $item = is_array($row) ? $row : [];
            $legacyPhotoUrl = (string)($item['photo_url'] ?? '');
            $avatarPhotoUrl = (string)($item['avatar_photo_url'] ?? '');
            $halfBodyPhotoUrl = (string)($item['half_body_photo_url'] ?? '');
            if ($avatarPhotoUrl === '') {
                $avatarPhotoUrl = $legacyPhotoUrl;
            }
            if ($halfBodyPhotoUrl === '') {
                $halfBodyPhotoUrl = $legacyPhotoUrl;
            }

            $item['avatar_photo_url'] = $avatarPhotoUrl;
            $item['half_body_photo_url'] = $halfBodyPhotoUrl;
            $item['photo_url'] = $source === MonthlyReportTemplate::TYPE_TOP ? $halfBodyPhotoUrl : $avatarPhotoUrl;
            return $item;
        }, $rows));
    }

    protected static function applyRankingAutoHeight(array $designConfig, array $rankingStaffs): array
    {
        $canvas = is_array($designConfig['canvas'] ?? null) ? $designConfig['canvas'] : [];
        if ((int)($canvas['auto_height'] ?? 1) !== 1) {
            return $designConfig;
        }

        $layers = is_array($designConfig['layers'] ?? null) ? $designConfig['layers'] : [];
        $repeaterIndex = null;
        $repeater = [];
        foreach ($layers as $index => $layer) {
            if (!is_array($layer)) {
                continue;
            }
            if (($layer['type'] ?? '') === 'repeater' && (string)($layer['source'] ?? 'ranking') === 'ranking' && (int)($layer['visible'] ?? 1) === 1) {
                $repeaterIndex = $index;
                $repeater = $layer;
                break;
            }
        }
        if ($repeaterIndex === null) {
            return $designConfig;
        }

        $columns = self::clampInt((int)($repeater['columns'] ?? 3), 1, 6);
        $cardHeight = self::clampInt((int)($repeater['card_height'] ?? 300), 80, 2160);
        $gapY = self::clampInt((int)($repeater['gap_y'] ?? 24), 0, 320);
        $limit = self::clampInt((int)($repeater['limit'] ?? 0), 0, 120);
        $staffCount = count($rankingStaffs);
        $displayCount = $limit > 0 ? min($staffCount, $limit) : $staffCount;
        $displayCount = max(1, $displayCount);
        $actualRows = max(1, (int)ceil($displayCount / $columns));
        $baseCount = $limit > 0 ? max(1, $limit) : $displayCount;
        $baseRows = max($actualRows, (int)ceil($baseCount / $columns));

        $repeaterY = self::clampInt((int)($repeater['y'] ?? 0), -5200, 10400);
        $baseBottom = $repeaterY + ($baseRows * $cardHeight) + max(0, $baseRows - 1) * $gapY;
        $actualBottom = $repeaterY + ($actualRows * $cardHeight) + max(0, $actualRows - 1) * $gapY;

        $oldHeight = self::clampInt((int)($canvas['height'] ?? 3600), 480, 5200);
        $minHeight = self::clampInt((int)($canvas['min_height'] ?? 1920), 480, 5200);
        $newHeight = self::clampInt(max($minHeight, $oldHeight + ($actualBottom - $baseBottom)), 480, 5200);
        $shift = $newHeight - $oldHeight;

        if ($shift !== 0) {
            foreach ($layers as $index => &$layer) {
                if (!is_array($layer) || $index === $repeaterIndex) {
                    continue;
                }
                if ((int)($layer['y'] ?? 0) >= $baseBottom) {
                    $layer['y'] = self::clampInt((int)$layer['y'] + $shift, -5200, 10400);
                }
            }
            unset($layer);
        }

        $layers[$repeaterIndex]['h'] = $actualRows * $cardHeight + max(0, $actualRows - 1) * $gapY;
        $designConfig['layers'] = $layers;
        $designConfig['canvas']['height'] = $newHeight;
        $designConfig['canvas']['min_height'] = $minHeight;
        $designConfig['canvas']['auto_height'] = 1;
        return $designConfig;
    }

    protected static function resolveTemplates(array $templateIds): array
    {
        $templates = [];
        foreach (MonthlyReportTemplate::types() as $type) {
            $templateId = (int)($templateIds[$type] ?? 0);
            $templates[$type] = self::templateConfig($type, $templateId, false);
        }
        return $templates;
    }

    protected static function persistReportAssets(string $reportMonth, int $version, array $renderedSnapshots, bool $force = true): array
    {
        $assets = [];
        foreach (MonthlyReportTemplate::types() as $type) {
            $snapshot = is_array($renderedSnapshots[$type] ?? null) ? $renderedSnapshots[$type] : [];
            if (empty($snapshot)) {
                throw new \RuntimeException(self::ERROR_ASSET_RENDER);
            }
            $svg = MonthlyReportRenderer::render($snapshot, [
                'font_options' => OrderConfirmLetterFontService::getActiveFontOptions(),
            ]);
            $assets[$type] = self::persistSvgAsset($reportMonth, $version, $type, self::buildSnapshotHash($snapshot), $svg);
        }
        return $assets;
    }

    protected static function persistSvgAsset(string $reportMonth, int $version, string $type, string $snapshotHash, string $svgContent): string
    {
        $svgContent = trim($svgContent);
        if ($svgContent === '' || stripos($svgContent, '<svg') === false) {
            throw new \RuntimeException(self::ERROR_ASSET_RENDER);
        }

        $folder = self::ASSET_STORAGE_DIR . '/' . str_replace('-', '', $reportMonth);
        $hash = preg_replace('/[^a-z0-9]/i', '', $snapshotHash);
        $hash = $hash !== '' ? substr($hash, 0, 20) : substr(md5($svgContent), 0, 20);
        $fileName = sprintf('%s-v%d-%s-%s.%s', $type, $version, $hash, self::ASSET_FILE_VERSION, self::ASSET_FILE_EXTENSION);

        if (self::getStorageDefault() !== 'local') {
            return self::persistSvgAssetToCloud($folder, $fileName, $svgContent);
        }
        return self::persistSvgAssetToLocal($folder, $fileName, $svgContent);
    }

    protected static function persistSvgAssetToLocal(string $folder, string $fileName, string $svgContent): string
    {
        $relativePath = $folder . '/' . $fileName;
        $absolutePath = FileService::getFileUrl($relativePath, 'public_path');
        self::ensureAssetDirectory(dirname($absolutePath));
        self::rasterizeSvgAsset($svgContent, $absolutePath);
        return $relativePath;
    }

    protected static function persistSvgAssetToCloud(string $folder, string $fileName, string $svgContent): string
    {
        $storage = self::getStorageDefault();
        $tempPath = self::buildTempAssetPath($fileName);
        try {
            self::rasterizeSvgAsset($svgContent, $tempPath);
            $storageConfig = ConfigService::get('storage') ?? ['local' => []];
            if (empty($storageConfig[$storage]) || !is_array($storageConfig[$storage])) {
                throw new \RuntimeException(self::ERROR_ASSET_UPLOAD);
            }
            $storageDriver = new StorageDriver([
                'default' => $storage,
                'engine' => $storageConfig,
            ]);
            $storageDriver->setUploadFileByReal($tempPath, $fileName);
            if (!$storageDriver->upload($folder)) {
                throw new \RuntimeException(self::ERROR_ASSET_UPLOAD);
            }
            return $folder . '/' . str_replace('\\', '/', (string)$storageDriver->getFileName());
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    protected static function rasterizeSvgAsset(string $svgContent, string $absolutePath): void
    {
        if (!extension_loaded('imagick') || !class_exists(\Imagick::class)) {
            throw new \RuntimeException(self::ERROR_ASSET_RUNTIME);
        }
        $imagick = new \Imagick();
        try {
            $svgContent = self::prepareSvgForRasterization($svgContent);
            $imagick->setResolution(self::ASSET_RASTER_RESOLUTION, self::ASSET_RASTER_RESOLUTION);
            $imagick->setBackgroundColor(new \ImagickPixel('white'));
            $imagick->readImageBlob($svgContent);
            if (defined('\Imagick::ALPHACHANNEL_REMOVE')) {
                $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
            }
            if (method_exists($imagick, 'mergeImageLayers')) {
                $flattened = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                if ($flattened instanceof \Imagick) {
                    $imagick->clear();
                    $imagick->addImage($flattened);
                    $flattened->clear();
                    $flattened->destroy();
                }
            }
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $imagick->setImageCompressionQuality(self::ASSET_JPEG_QUALITY);
            $imagick->stripImage();
            if (!$imagick->writeImage($absolutePath)) {
                throw new \RuntimeException(self::ERROR_ASSET_RENDER);
            }
            if (!self::isUsableLocalImageAsset($absolutePath)) {
                throw new \RuntimeException(self::ERROR_ASSET_RENDER);
            }
        } catch (\Throwable $e) {
            @unlink($absolutePath);
            if ($e instanceof \RuntimeException) {
                throw $e;
            }
            throw new \RuntimeException(self::ERROR_ASSET_RENDER, 0, $e);
        } finally {
            $imagick->clear();
            $imagick->destroy();
        }
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

        self::logAssetFailure('单量月报图片引用无法内联，已使用透明占位避免整图渲染失败', [
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
            self::logAssetFailure('单量月报远程图片地址不允许下载', [
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
            CURLOPT_USERAGENT => 'GelinsheMonthlyReportRenderer/1.0',
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
            self::logAssetFailure('单量月报远程图片下载失败', [
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

    protected static function defaultDesignConfig(string $type): array
    {
        return match ($type) {
            MonthlyReportTemplate::TYPE_RANKING => self::defaultRankingDesign(),
            MonthlyReportTemplate::TYPE_TOP => self::defaultTopDesign(),
            default => self::defaultAdditionDesign(),
        };
    }

    protected static function defaultAdditionDesign(): array
    {
        return [
            'canvas' => ['width' => 1080, 'height' => 1920],
            'background' => ['type' => 'color', 'color' => '#1B1510', 'image' => '', 'fit' => 'cover', 'opacity' => 1],
            'layers' => [
                self::textLayer('brand', 250, 150, 580, 80, 1, '格林社 · 衡水', 42, '#F4D08E', '700'),
                self::textLayer('title', 130, 390, 820, 120, 2, '{addition_title}', 96, '#F8CB79', '300'),
                self::textLayer('subtitle', 180, 520, 720, 70, 3, '{addition_subtitle}', 34, '#FFFFFF', '500'),
                self::textLayer('count', 55, 660, 970, 440, 4, '{addition_count}', 360, '#F5D18B', '800'),
                self::textLayer('unit', 500, 1135, 80, 54, 5, '场', 36, '#FFFFFF', '600'),
                self::textLayer('thanks', 250, 1310, 580, 64, 6, 'THANKS FOR', 48, '#F8CB79', '300'),
                self::textLayer('footer', 250, 1400, 580, 120, 7, '{footer_note}', 30, '#FFFFFF', '500'),
                self::qrcodeLayer(438, 1570, 204, 204, 8),
            ],
        ];
    }

    protected static function defaultRankingDesign(): array
    {
        return [
            'canvas' => ['width' => 1080, 'height' => 3600, 'auto_height' => 1, 'min_height' => 1920],
            'background' => ['type' => 'color', 'color' => '#185C81', 'image' => '', 'fit' => 'cover', 'opacity' => 1],
            'layers' => [
                self::repeaterLayer('ranking', 30, 72, 3, 300, 300, 42, 42, 1, 24),
                self::textLayer('total', 30, 2960, 480, 220, 2, '{executed_count}', 210, '#FFF9ED', '300', 'left'),
                self::textLayer('summary', 34, 3180, 620, 62, 3, '*{ranking_title}', 40, '#FFFFFF', '400', 'left'),
                self::qrcodeLayer(40, 3340, 170, 170, 4),
                self::textLayer('brand', 725, 3305, 280, 100, 5, '格林社', 58, '#FFFFFF', '700'),
                self::textLayer('brand2', 650, 3420, 360, 50, 6, '主持就找格林社 圆满呈现每一刻', 22, '#FFFFFF', '500'),
            ],
        ];
    }

    protected static function defaultTopDesign(): array
    {
        return [
            'canvas' => ['width' => 1080, 'height' => 1920],
            'background' => ['type' => 'color', 'color' => '#F8F3EC', 'image' => '', 'fit' => 'cover', 'opacity' => 1],
            'layers' => [
                self::textLayer('top-title', 0, 18, 1080, 255, 1, '{top_title}', 196, '#C84A00', '900', 'center'),
                self::rectLayer('hero-block', 12, 620, 1056, 770, 2, '#C84A00'),
                self::repeaterLayer('top-staffs', 42, 360, 3, 310, 860, 22, 0, 3, 3, 'top', self::topCardLayers(), 'center'),
                self::textLayer('brand-mark', 420, 1080, 240, 150, 4, "格林社\nGREEN SOCIETY CLUB\n衡水", 56, '#FFFFFF', '800', 'center'),
                self::textLayer('slogan', 230, 1282, 620, 58, 5, '主持就找格林社  圆满呈现每一刻', 38, '#FFFFFF', '700', 'center'),
                self::textLayer('year', 14, 1468, 190, 82, 6, '2026', 72, '#C84A00', '300', 'left'),
                self::textLayer('month-en', 210, 1474, 180, 76, 7, "JAN.\n{report_month_label}", 26, '#111111', '700', 'left'),
                self::textLayer('award', 34, 1574, 330, 72, 8, '*月度单王', 52, '#111111', '400', 'left'),
                self::textLayer('names', 385, 1416, 310, 54, 9, '{top_staff_names}', 34, '#111111', '500', 'center'),
                self::textLayer('center-title', 385, 1468, 310, 205, 10, "VIDING\nWANGCE", 76, '#C84A00', '900', 'center'),
                self::textLayer('center-script', 360, 1518, 360, 150, 11, "yiding\nwangce", 60, '#111111', '300', 'center'),
                self::textLayer('honor', 330, 1666, 420, 86, 12, "以实力 荣获本月人气之星\n以专业 获得新人广泛认可", 28, '#111111', '400', 'center'),
                self::textLayer('count-label', 760, 1478, 260, 60, 13, '本月共计主持', 34, '#C84A00', '500', 'right'),
                self::textLayer('count', 735, 1525, 225, 135, 14, '{top_count}', 128, '#111111', '300', 'right'),
                self::textLayer('unit', 970, 1604, 48, 40, 15, '场', 24, '#C84A00', '500', 'left'),
                self::textLayer('contact', 14, 1768, 360, 120, 16, "*联系我们\nCONTACT US\n河北省衡水市桃城区汇宁创业A座", 25, '#111111', '400', 'left'),
                self::qrcodeLayer(858, 1740, 170, 170, 17),
                self::textLayer('qrcode-note', 812, 1906, 250, 36, 18, '扫码预约主持服务', 22, '#111111', '500', 'center'),
            ],
        ];
    }

    protected static function topCardLayers(): array
    {
        return [
            ['id' => 'photo', 'type' => 'image', 'x' => 0, 'y' => 0, 'w' => 310, 'h' => 860, 'src' => '{half_body_photo_url}', 'fit' => 'contain', 'backgroundFill' => 'rgba(255,255,255,0)', 'radius' => 0],
        ];
    }

    protected static function textLayer(string $id, int $x, int $y, int $w, int $h, int $z, string $text, int $fontSize, string $color, string $fontWeight = '400', string $align = 'center'): array
    {
        return [
            'id' => $id,
            'type' => 'text',
            'x' => $x,
            'y' => $y,
            'w' => $w,
            'h' => $h,
            'z' => $z,
            'visible' => 1,
            'locked' => 0,
            'opacity' => 1,
            'rotate' => 0,
            'text' => $text,
            'fontSize' => $fontSize,
            'fontWeight' => $fontWeight,
            'lineHeight' => 1.15,
            'align' => $align,
            'color' => $color,
            'scaleX' => 1,
            'scaleY' => 1,
            'letterSpacing' => 0,
            'fillType' => 'solid',
            'gradientFrom' => $color,
            'gradientTo' => $color,
            'gradientAngle' => 90,
            'fillImage' => '',
            'fillImageFit' => 'cover',
            'textStrokeColor' => '#000000',
            'textStrokeWidth' => 0,
            'textStrokeOpacity' => 1,
            'shadowColor' => '#000000',
            'shadowBlur' => 0,
            'shadowOffsetX' => 0,
            'shadowOffsetY' => 0,
            'shadowOpacity' => 0.35,
            'artPreset' => 'default',
        ];
    }

    protected static function qrcodeLayer(int $x, int $y, int $w, int $h, int $z): array
    {
        return [
            'id' => 'qrcode',
            'type' => 'qrcode',
            'x' => $x,
            'y' => $y,
            'w' => $w,
            'h' => $h,
            'z' => $z,
            'visible' => 1,
            'locked' => 0,
            'opacity' => 1,
            'rotate' => 0,
            'src' => '',
            'padding' => 18,
            'radius' => 12,
        ];
    }

    protected static function rectLayer(string $id, int $x, int $y, int $w, int $h, int $z, string $fill, string $stroke = '', int $strokeWidth = 0, int $radius = 0): array
    {
        return [
            'id' => $id,
            'type' => 'rect',
            'x' => $x,
            'y' => $y,
            'w' => $w,
            'h' => $h,
            'z' => $z,
            'visible' => 1,
            'locked' => 0,
            'opacity' => 1,
            'rotate' => 0,
            'fill' => $fill,
            'stroke' => $stroke,
            'strokeWidth' => $strokeWidth,
            'radius' => $radius,
        ];
    }

    protected static function repeaterLayer(
        string $id,
        int $x,
        int $y,
        int $columns,
        int $cardWidth,
        int $cardHeight,
        int $gapX,
        int $gapY,
        int $z,
        int $limit,
        string $source = 'ranking',
        array $cardLayers = [],
        string $align = 'left'
    ): array {
        return [
            'id' => $id,
            'type' => 'repeater',
            'source' => $source,
            'x' => $x,
            'y' => $y,
            'w' => $columns * $cardWidth + ($columns - 1) * $gapX,
            'h' => $cardHeight,
            'z' => $z,
            'visible' => 1,
            'locked' => 0,
            'opacity' => 1,
            'rotate' => 0,
            'columns' => $columns,
            'card_width' => $cardWidth,
            'card_height' => $cardHeight,
            'gap_x' => $gapX,
            'gap_y' => $gapY,
            'limit' => $limit,
            'align' => in_array($align, ['left', 'center', 'right'], true) ? $align : 'left',
            'card_layers' => !empty($cardLayers) ? $cardLayers : [
                ['id' => 'photo', 'type' => 'image', 'x' => 0, 'y' => 0, 'w' => $cardWidth, 'h' => $cardHeight, 'src' => '{avatar_photo_url}', 'fit' => 'cover', 'radius' => 0],
                ['id' => 'english', 'type' => 'text', 'x' => 18, 'y' => 24, 'w' => $cardWidth - 36, 'h' => 36, 'text' => '{english_name}', 'fontSize' => 28, 'fontWeight' => '800', 'align' => 'left', 'color' => '#FFFFFF'],
                ['id' => 'name', 'type' => 'text', 'x' => 18, 'y' => 66, 'w' => $cardWidth - 36, 'h' => 34, 'text' => '*{chinese_name}', 'fontSize' => 24, 'fontWeight' => '500', 'align' => 'left', 'color' => '#FFFFFF'],
                ['id' => 'count', 'type' => 'text', 'x' => 18, 'y' => 110, 'w' => 120, 'h' => 90, 'text' => '{count}', 'fontSize' => 78, 'fontWeight' => '300', 'align' => 'left', 'color' => '#FFF9ED'],
            ],
        ];
    }

    protected static function normalizeDesignConfig(array $design, string $type): array
    {
        $default = self::defaultDesignConfig($type);
        $canvas = is_array($design['canvas'] ?? null) ? $design['canvas'] : [];
        $background = is_array($design['background'] ?? null) ? $design['background'] : [];
        $layers = is_array($design['layers'] ?? null) ? $design['layers'] : $default['layers'];
        $normalizedCanvas = [
            'width' => self::clampInt((int)($canvas['width'] ?? $default['canvas']['width']), 320, 2160),
            'height' => self::clampInt((int)($canvas['height'] ?? $default['canvas']['height']), 480, 5200),
        ];
        if ($type === MonthlyReportTemplate::TYPE_RANKING) {
            $normalizedCanvas['auto_height'] = (int)($canvas['auto_height'] ?? $default['canvas']['auto_height'] ?? 1) === 1 ? 1 : 0;
            $normalizedCanvas['min_height'] = self::clampInt((int)($canvas['min_height'] ?? $default['canvas']['min_height'] ?? 1920), 480, 5200);
        }
        $rawBackgroundType = (string)($background['type'] ?? 'color');

        return [
            'canvas' => $normalizedCanvas,
            'background' => [
                'type' => in_array($rawBackgroundType, ['color', 'image'], true) ? $rawBackgroundType : 'color',
                'color' => self::normalizeColor((string)($background['color'] ?? $default['background']['color']), (string)$default['background']['color']),
                'image' => self::normalizeStoredFileUrl((string)($background['image'] ?? '')),
                'fit' => (string)($background['fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover',
                'opacity' => self::clampFloat((float)($background['opacity'] ?? 1), 0, 1),
            ],
            'layers' => self::normalizeLayers($layers),
        ];
    }

    protected static function normalizeLayers(array $layers): array
    {
        $normalized = [];
        foreach (array_slice($layers, 0, 100) as $index => $layer) {
            if (!is_array($layer)) {
                continue;
            }
            $rawLayerType = (string)($layer['type'] ?? 'text');
            $type = in_array($rawLayerType, ['text', 'image', 'qrcode', 'rect', 'line', 'repeater'], true)
                ? $rawLayerType
                : 'text';
            $base = [
                'id' => self::normalizeLayerId((string)($layer['id'] ?? ('layer_' . ($index + 1)))),
                'type' => $type,
                'x' => self::clampInt((int)($layer['x'] ?? 0), -2160, 4320),
                'y' => self::clampInt((int)($layer['y'] ?? 0), -5200, 10400),
                'w' => self::clampInt((int)($layer['w'] ?? 120), 1, 2160),
                'h' => $type === 'line' ? self::clampInt((int)($layer['h'] ?? 0), 0, 5200) : self::clampInt((int)($layer['h'] ?? 80), 1, 5200),
                'z' => self::clampInt((int)($layer['z'] ?? $index), -999, 999),
                'visible' => (int)($layer['visible'] ?? 1) === 1 ? 1 : 0,
                'locked' => (int)($layer['locked'] ?? 0) === 1 ? 1 : 0,
                'opacity' => self::clampFloat((float)($layer['opacity'] ?? 1), 0, 1),
                'rotate' => self::clampFloat((float)($layer['rotate'] ?? 0), -360, 360),
            ];
            if ($type === 'text') {
                $rawFontWeight = (string)($layer['fontWeight'] ?? '400');
                $rawAlign = (string)($layer['align'] ?? 'center');
                $base += [
                    'text' => mb_substr((string)($layer['text'] ?? ''), 0, 1000, 'UTF-8'),
                    'fontSize' => self::clampInt((int)($layer['fontSize'] ?? 42), 10, 400),
                    'fontWeight' => in_array($rawFontWeight, ['300', '400', '500', '600', '700', '800', '900'], true) ? $rawFontWeight : '400',
                    'lineHeight' => self::clampFloat((float)($layer['lineHeight'] ?? 1.25), 0.8, 3),
                    'align' => in_array($rawAlign, ['left', 'center', 'right'], true) ? $rawAlign : 'center',
                    'color' => self::normalizeColor((string)($layer['color'] ?? '#FFF7E6'), '#FFF7E6'),
                ] + self::normalizeTextArtStyle($layer, '#FFF7E6');
            } elseif ($type === 'image') {
                $base += [
                    'src' => self::normalizeStoredFileUrl((string)($layer['src'] ?? '')),
                    'fit' => (string)($layer['fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover',
                    'backgroundFill' => self::normalizeOptionalColor((string)($layer['backgroundFill'] ?? '')),
                    'radius' => self::clampInt((int)($layer['radius'] ?? 0), 0, 260),
                ];
            } elseif ($type === 'qrcode') {
                $base += [
                    'src' => '',
                    'padding' => self::clampInt((int)($layer['padding'] ?? 18), 0, 80),
                    'radius' => self::clampInt((int)($layer['radius'] ?? 18), 0, 120),
                ];
            } elseif ($type === 'rect') {
                $base += [
                    'fill' => self::normalizeColor((string)($layer['fill'] ?? '#FFFFFF'), '#FFFFFF'),
                    'stroke' => self::normalizeOptionalColor((string)($layer['stroke'] ?? '')),
                    'strokeWidth' => self::clampInt((int)($layer['strokeWidth'] ?? 0), 0, 40),
                    'radius' => self::clampInt((int)($layer['radius'] ?? 0), 0, 260),
                ];
            } elseif ($type === 'line') {
                $base += [
                    'stroke' => self::normalizeColor((string)($layer['stroke'] ?? '#D8C08B'), '#D8C08B'),
                    'strokeWidth' => self::clampInt((int)($layer['strokeWidth'] ?? 2), 1, 40),
                ];
            } else {
                $rawRepeaterAlign = (string)($layer['align'] ?? 'left');
                $base += [
                    'source' => (string)($layer['source'] ?? 'ranking') === 'top' ? 'top' : 'ranking',
                    'columns' => self::clampInt((int)($layer['columns'] ?? 3), 1, 6),
                    'card_width' => self::clampInt((int)($layer['card_width'] ?? 300), 80, 2160),
                    'card_height' => self::clampInt((int)($layer['card_height'] ?? 300), 80, 2160),
                    'gap_x' => self::clampInt((int)($layer['gap_x'] ?? 24), 0, 320),
                    'gap_y' => self::clampInt((int)($layer['gap_y'] ?? 24), 0, 320),
                    'limit' => self::clampInt((int)($layer['limit'] ?? 0), 0, 120),
                    'align' => in_array($rawRepeaterAlign, ['left', 'center', 'right'], true) ? $rawRepeaterAlign : 'left',
                    'card_layers' => self::normalizeCardLayers(is_array($layer['card_layers'] ?? null) ? $layer['card_layers'] : []),
                ];
            }
            $normalized[] = $base;
        }
        return $normalized;
    }

    protected static function normalizeCardLayers(array $layers): array
    {
        $normalized = [];
        foreach (array_slice($layers, 0, 30) as $index => $layer) {
            if (!is_array($layer)) {
                continue;
            }
            $rawLayerType = (string)($layer['type'] ?? 'text');
            $type = in_array($rawLayerType, ['text', 'image', 'rect', 'line'], true) ? $rawLayerType : 'text';
            $base = [
                'id' => self::normalizeLayerId((string)($layer['id'] ?? ('card_' . ($index + 1)))),
                'type' => $type,
                'x' => self::clampInt((int)($layer['x'] ?? 0), -2160, 4320),
                'y' => self::clampInt((int)($layer['y'] ?? 0), -5200, 10400),
                'w' => self::clampInt((int)($layer['w'] ?? 120), 1, 2160),
                'h' => $type === 'line' ? self::clampInt((int)($layer['h'] ?? 0), 0, 5200) : self::clampInt((int)($layer['h'] ?? 80), 1, 5200),
                'visible' => (int)($layer['visible'] ?? 1) === 1 ? 1 : 0,
                'opacity' => self::clampFloat((float)($layer['opacity'] ?? 1), 0, 1),
                'rotate' => self::clampFloat((float)($layer['rotate'] ?? 0), -360, 360),
            ];
            if ($type === 'text') {
                $rawFontWeight = (string)($layer['fontWeight'] ?? '400');
                $rawAlign = (string)($layer['align'] ?? 'left');
                $base += [
                    'text' => mb_substr((string)($layer['text'] ?? ''), 0, 500, 'UTF-8'),
                    'fontSize' => self::clampInt((int)($layer['fontSize'] ?? 28), 10, 400),
                    'fontWeight' => in_array($rawFontWeight, ['300', '400', '500', '600', '700', '800', '900'], true) ? $rawFontWeight : '400',
                    'lineHeight' => self::clampFloat((float)($layer['lineHeight'] ?? 1.2), 0.8, 3),
                    'align' => in_array($rawAlign, ['left', 'center', 'right'], true) ? $rawAlign : 'left',
                    'color' => self::normalizeColor((string)($layer['color'] ?? '#FFFFFF'), '#FFFFFF'),
                ] + self::normalizeTextArtStyle($layer, '#FFFFFF');
            } elseif ($type === 'image') {
                $base += [
                    'src' => mb_substr((string)($layer['src'] ?? '{photo_url}'), 0, 500, 'UTF-8'),
                    'fit' => (string)($layer['fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover',
                    'backgroundFill' => self::normalizeOptionalColor((string)($layer['backgroundFill'] ?? '')),
                    'radius' => self::clampInt((int)($layer['radius'] ?? 0), 0, 260),
                ];
            } elseif ($type === 'rect') {
                $base += [
                    'fill' => self::normalizeColor((string)($layer['fill'] ?? '#FFFFFF'), '#FFFFFF'),
                    'stroke' => self::normalizeOptionalColor((string)($layer['stroke'] ?? '')),
                    'strokeWidth' => self::clampInt((int)($layer['strokeWidth'] ?? 0), 0, 40),
                    'radius' => self::clampInt((int)($layer['radius'] ?? 0), 0, 260),
                ];
            } else {
                $base += [
                    'stroke' => self::normalizeColor((string)($layer['stroke'] ?? '#D8C08B'), '#D8C08B'),
                    'strokeWidth' => self::clampInt((int)($layer['strokeWidth'] ?? 2), 1, 40),
                ];
            }
            $normalized[] = $base;
        }
        return $normalized;
    }

    protected static function normalizeTextArtStyle(array $layer, string $fallbackColor): array
    {
        $rawFillType = (string)($layer['fillType'] ?? 'solid');
        $fillType = in_array($rawFillType, ['solid', 'linear', 'image'], true)
            ? $rawFillType
            : 'solid';
        $fillImage = self::normalizeStoredFileUrl((string)($layer['fillImage'] ?? $layer['fillImageUrl'] ?? ''));
        $rawFillImageFit = (string)($layer['fillImageFit'] ?? 'cover');
        $fillImageFit = in_array($rawFillImageFit, ['cover', 'contain', 'stretch'], true)
            ? $rawFillImageFit
            : 'cover';
        $artPreset = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($layer['artPreset'] ?? 'default')) ?: 'default';

        return [
            'scaleX' => self::clampFloat((float)($layer['scaleX'] ?? 1), 0.2, 3),
            'scaleY' => self::clampFloat((float)($layer['scaleY'] ?? 1), 0.2, 3),
            'letterSpacing' => self::clampFloat((float)($layer['letterSpacing'] ?? 0), -20, 80),
            'fillType' => $fillType,
            'gradientFrom' => self::normalizeColor((string)($layer['gradientFrom'] ?? $fallbackColor), $fallbackColor),
            'gradientTo' => self::normalizeColor((string)($layer['gradientTo'] ?? $fallbackColor), $fallbackColor),
            'gradientAngle' => self::clampInt((int)($layer['gradientAngle'] ?? 90), 0, 360),
            'fillImage' => $fillImage,
            'fillImageFit' => $fillImageFit,
            'textStrokeColor' => self::normalizeColor((string)($layer['textStrokeColor'] ?? '#000000'), '#000000'),
            'textStrokeWidth' => self::clampFloat((float)($layer['textStrokeWidth'] ?? 0), 0, 24),
            'textStrokeOpacity' => self::clampFloat((float)($layer['textStrokeOpacity'] ?? 1), 0, 1),
            'shadowColor' => self::normalizeColor((string)($layer['shadowColor'] ?? '#000000'), '#000000'),
            'shadowBlur' => self::clampFloat((float)($layer['shadowBlur'] ?? 0), 0, 80),
            'shadowOffsetX' => self::clampFloat((float)($layer['shadowOffsetX'] ?? 0), -120, 120),
            'shadowOffsetY' => self::clampFloat((float)($layer['shadowOffsetY'] ?? 0), -120, 120),
            'shadowOpacity' => self::clampFloat((float)($layer['shadowOpacity'] ?? 0.35), 0, 1),
            'artPreset' => mb_substr($artPreset, 0, 40, 'UTF-8'),
        ];
    }

    protected static function buildSnapshotDesignConfig(array $design, string $type, string $qrcodeImage = ''): array
    {
        $design = self::normalizeDesignConfig($design, $type);
        $design['background']['image'] = (string)$design['background']['type'] === 'image'
            ? self::formatPublicImageUrl((string)$design['background']['image'])
            : '';
        foreach ($design['layers'] as &$layer) {
            if (($layer['type'] ?? '') === 'image') {
                $layer['src'] = self::formatPublicImageUrl((string)($layer['src'] ?? ''));
            }
            if (($layer['type'] ?? '') === 'text') {
                $layer['fillImage'] = self::formatPublicImageUrl((string)($layer['fillImage'] ?? ''));
            }
            if (($layer['type'] ?? '') === 'qrcode') {
                $layer['src'] = $qrcodeImage !== '' ? self::formatPublicImageUrl($qrcodeImage) : '';
            }
            if (($layer['type'] ?? '') === 'repeater' && is_array($layer['card_layers'] ?? null)) {
                foreach ($layer['card_layers'] as &$cardLayer) {
                    if (($cardLayer['type'] ?? '') === 'text') {
                        $cardLayer['fillImage'] = self::formatPublicImageUrl((string)($cardLayer['fillImage'] ?? ''));
                    }
                }
                unset($cardLayer);
            }
        }
        unset($layer);
        return $design;
    }

    protected static function createDefaultTemplate(string $type): MonthlyReportTemplate
    {
        return MonthlyReportTemplate::create([
            'template_type' => $type,
            'template_name' => MonthlyReportTemplate::getTypeText($type) . '默认模板',
            'template_version' => self::nextTemplateVersion($type),
            'is_default' => 1,
            'status' => MonthlyReportTemplate::STATUS_ENABLED,
            'sort' => 0,
            'design_version' => self::RENDER_SPEC_VERSION,
            'design_config' => self::defaultDesignConfig($type),
            'create_time' => time(),
            'update_time' => time(),
        ]);
    }

    protected static function markTemplateDefault(int $templateId, string $type): void
    {
        MonthlyReportTemplate::where('template_type', $type)
            ->where('id', '<>', $templateId)
            ->whereNull('delete_time')
            ->update([
                'is_default' => 0,
                'update_time' => time(),
            ]);
    }

    protected static function nextTemplateVersion(string $type): int
    {
        return (int)MonthlyReportTemplate::where('template_type', $type)->max('template_version') + 1;
    }

    protected static function materialMap(array $staffIds): array
    {
        $staffIds = array_values(array_unique(array_filter(array_map('intval', $staffIds))));
        if (empty($staffIds)) {
            return [];
        }
        $materials = MonthlyReportMaterial::whereIn('staff_id', $staffIds)
            ->whereNull('delete_time')
            ->select();
        $map = [];
        foreach ($materials as $material) {
            $map[(int)$material->staff_id] = self::formatMaterial($material);
        }
        return $map;
    }

    protected static function buildStaffSnapshotRow(int $staffId, int $count, string $staffName, ?array $material): array
    {
        $legacyPhotoUrl = (string)($material['photo_url'] ?? '');
        $avatarPhotoUrl = (string)($material['avatar_photo_url'] ?? '');
        $halfBodyPhotoUrl = (string)($material['half_body_photo_url'] ?? '');
        if ($avatarPhotoUrl === '') {
            $avatarPhotoUrl = $legacyPhotoUrl;
        }
        if ($halfBodyPhotoUrl === '') {
            $halfBodyPhotoUrl = $legacyPhotoUrl;
        }
        $englishName = trim((string)($material['english_name'] ?? ''));
        $chineseName = trim($staffName);
        $baseComplete = $englishName !== '' && $chineseName !== '' && (int)($material['status'] ?? 0) === MonthlyReportMaterial::STATUS_ENABLED;
        $rankingMaterialComplete = $avatarPhotoUrl !== '' && $baseComplete;
        $topMaterialComplete = $halfBodyPhotoUrl !== '' && $baseComplete;
        return [
            'staff_id' => $staffId,
            'staff_name' => $staffName,
            'english_name' => $englishName,
            'chinese_name' => $chineseName,
            'photo_url' => $avatarPhotoUrl,
            'avatar_photo_url' => $avatarPhotoUrl,
            'half_body_photo_url' => $halfBodyPhotoUrl,
            'count_raw' => $count,
            'count' => self::formatCount($count),
            'rank' => 0,
            'material_id' => (int)($material['id'] ?? 0),
            'material_sort' => (int)($material['sort'] ?? 0),
            'material_complete' => $rankingMaterialComplete && $topMaterialComplete ? 1 : 0,
            'ranking_material_complete' => $rankingMaterialComplete ? 1 : 0,
            'top_material_complete' => $topMaterialComplete ? 1 : 0,
        ];
    }

    protected static function formatPreviewContext(array $context, bool $includeSvg = false): array
    {
        $payload = [
            'report_month' => $context['report_month'],
            'category_ids' => $context['category_ids'],
            'auto_snapshot' => $context['auto_snapshot'],
            'draft' => $context['final_snapshot'],
            'template_snapshot' => $context['template_snapshot'],
            'issues' => $context['issues'],
            'can_confirm' => self::hasBlockingIssues($context['issues']) ? 0 : 1,
            'rendered_snapshots' => $context['rendered_snapshots'],
            'snapshot_hash' => self::buildSnapshotHash($context['rendered_snapshots']),
        ];
        if ($includeSvg) {
            $payload['svg'] = [];
            foreach ($context['rendered_snapshots'] as $type => $snapshot) {
                $svgContent = MonthlyReportRenderer::render($snapshot, [
                    'font_options' => OrderConfirmLetterFontService::getActiveFontOptions(),
                ]);
                $payload['svg'][$type] = self::prepareSvgForRasterization($svgContent);
            }
        }
        return $payload;
    }

    protected static function formatMaterial(MonthlyReportMaterial $material): array
    {
        $data = $material->toArray();
        $data['photo'] = self::normalizeStoredFileUrl((string)($material->getData('photo') ?? ''));
        $data['avatar_photo'] = self::normalizeStoredFileUrl((string)($material->getData('avatar_photo') ?? ''));
        $data['half_body_photo'] = self::normalizeStoredFileUrl((string)($material->getData('half_body_photo') ?? ''));
        if ($data['avatar_photo'] === '') {
            $data['avatar_photo'] = $data['photo'];
        }
        if ($data['half_body_photo'] === '') {
            $data['half_body_photo'] = $data['photo'];
        }
        if ($data['photo'] === '') {
            $data['photo'] = $data['avatar_photo'] !== '' ? $data['avatar_photo'] : $data['half_body_photo'];
        }
        $data['photo_url'] = self::formatPublicImageUrl($data['photo']);
        $data['avatar_photo_url'] = self::formatPublicImageUrl($data['avatar_photo']);
        $data['half_body_photo_url'] = self::formatPublicImageUrl($data['half_body_photo']);
        $data['chinese_name'] = (string)($data['staff_name'] ?? '');
        return $data;
    }

    protected static function emptyMaterial(int $staffId): array
    {
        return [
            'id' => 0,
            'staff_id' => $staffId,
            'photo' => '',
            'photo_url' => '',
            'avatar_photo' => '',
            'avatar_photo_url' => '',
            'half_body_photo' => '',
            'half_body_photo_url' => '',
            'english_name' => '',
            'chinese_name' => '',
            'sort' => 0,
            'status' => MonthlyReportMaterial::STATUS_ENABLED,
            'status_desc' => '启用',
        ];
    }

    protected static function formatTemplate(MonthlyReportTemplate $template): array
    {
        $data = $template->toArray();
        $type = self::normalizeTemplateType((string)($data['template_type'] ?? MonthlyReportTemplate::TYPE_ADDITION));
        $data['template_id'] = (int)$data['id'];
        $data['design_version'] = self::RENDER_SPEC_VERSION;
        $data['design_config'] = self::formatDesignConfigForClient(is_array($template->design_config) ? $template->design_config : [], $type);
        return $data;
    }

    protected static function formatTemplateSummary(MonthlyReportTemplate $template): array
    {
        return [
            'template_id' => (int)$template->id,
            'template_type' => (string)$template->template_type,
            'template_name' => (string)$template->template_name,
            'template_version' => (int)$template->template_version,
            'is_default' => (int)$template->is_default,
            'status' => (int)$template->status,
            'sort' => (int)$template->sort,
            'update_time' => (int)$template->update_time,
        ];
    }

    protected static function formatDesignConfigForClient(array $design, string $type): array
    {
        $design = self::normalizeDesignConfig($design, $type);
        $design['background']['image_url'] = self::formatPublicImageUrl((string)$design['background']['image']);
        $qrcodeImage = self::getGlobalQrcodeImage();
        foreach ($design['layers'] as &$layer) {
            if (($layer['type'] ?? '') === 'qrcode') {
                $layer['src_url'] = self::formatPublicImageUrl($qrcodeImage);
            } elseif (($layer['type'] ?? '') === 'image') {
                $layer['src_url'] = self::formatPublicImageUrl((string)($layer['src'] ?? ''));
            } elseif (($layer['type'] ?? '') === 'text') {
                $layer['fillImageUrl'] = self::formatPublicImageUrl((string)($layer['fillImage'] ?? ''));
            } elseif (($layer['type'] ?? '') === 'repeater' && is_array($layer['card_layers'] ?? null)) {
                foreach ($layer['card_layers'] as &$cardLayer) {
                    if (($cardLayer['type'] ?? '') === 'text') {
                        $cardLayer['fillImageUrl'] = self::formatPublicImageUrl((string)($cardLayer['fillImage'] ?? ''));
                    }
                }
                unset($cardLayer);
            }
        }
        unset($layer);
        return $design;
    }

    protected static function formatTemplateSnapshot(array $templates): array
    {
        $result = [];
        foreach ($templates as $type => $template) {
            $result[$type] = [
                'template_id' => (int)($template['template_id'] ?? $template['id'] ?? 0),
                'template_name' => (string)($template['template_name'] ?? ''),
                'template_version' => (int)($template['template_version'] ?? 1),
                'design_version' => self::RENDER_SPEC_VERSION,
            ];
        }
        return $result;
    }

    protected static function formatReport(MonthlyReport $report): array
    {
        $data = $report->toArray();
        $data['addition_image_url'] = self::normalizeStoredFileUrl((string)($report->getData('addition_image_url') ?? ''));
        $data['ranking_image_url'] = self::normalizeStoredFileUrl((string)($report->getData('ranking_image_url') ?? ''));
        $data['top_image_url'] = self::normalizeStoredFileUrl((string)($report->getData('top_image_url') ?? ''));
        $data['addition_image_full_url'] = self::formatPublicImageUrl($data['addition_image_url']);
        $data['ranking_image_full_url'] = self::formatPublicImageUrl($data['ranking_image_url']);
        $data['top_image_full_url'] = self::formatPublicImageUrl($data['top_image_url']);
        return $data;
    }

    protected static function formatReportSummary(MonthlyReport $report): array
    {
        return [
            'id' => (int)$report->id,
            'report_month' => (string)$report->report_month,
            'version' => (int)$report->version,
            'is_current' => (int)$report->is_current,
            'addition_count' => (int)$report->addition_count,
            'executed_count' => (int)$report->executed_count,
            'top_count' => (int)$report->top_count,
            'addition_image_full_url' => self::formatPublicImageUrl((string)$report->addition_image_url),
            'ranking_image_full_url' => self::formatPublicImageUrl((string)$report->ranking_image_url),
            'top_image_full_url' => self::formatPublicImageUrl((string)$report->top_image_url),
            'snapshot_hash' => (string)$report->snapshot_hash,
            'confirm_admin_id' => (int)$report->confirm_admin_id,
            'confirm_time' => (int)$report->confirm_time,
            'create_time' => (int)$report->create_time,
        ];
    }

    protected static function validOrderStatuses(): array
    {
        return [
            Order::STATUS_PENDING_SERVICE,
            Order::STATUS_IN_SERVICE,
            Order::STATUS_COMPLETED,
            Order::STATUS_REVIEWED,
            Order::STATUS_PAUSED,
        ];
    }

    protected static function invalidPayStatuses(): array
    {
        return [
            Order::PAY_STATUS_PARTIAL_REFUND,
            Order::PAY_STATUS_FULL_REFUND,
        ];
    }

    protected static function defaultCopywriting(string $reportMonth): array
    {
        $label = self::formatReportMonthLabel($reportMonth);
        return [
            'addition_title' => 'ADDITION',
            'addition_subtitle' => $label . '新增婚礼档期',
            'ranking_title' => $label . '共计执行',
            'top_title' => 'THE MOST',
            'footer_note' => '感谢您的选择',
        ];
    }

    protected static function sanitizeCopywriting(array $copywriting): array
    {
        $allowed = ['addition_title', 'addition_subtitle', 'ranking_title', 'top_title', 'footer_note'];
        $result = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $copywriting)) {
                $result[$key] = mb_substr(trim((string)$copywriting[$key]), 0, 120, 'UTF-8');
            }
        }
        return $result;
    }

    protected static function resolveMonthRange(string $reportMonth): array
    {
        $startDate = $reportMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        return [
            $startDate,
            $endDate,
            strtotime($startDate . ' 00:00:00'),
            strtotime($endDate . ' 23:59:59'),
        ];
    }

    protected static function normalizeReportMonth(string $month): string
    {
        $month = trim($month);
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            return self::defaultReportMonth();
        }
        return date('Y-m', strtotime($month . '-01'));
    }

    protected static function defaultReportMonth(): string
    {
        return date('Y-m', strtotime('first day of last month'));
    }

    protected static function formatReportMonthLabel(string $reportMonth): string
    {
        $time = strtotime($reportMonth . '-01');
        return date('Y年n月', $time);
    }

    protected static function normalizeTemplateType(string $type): string
    {
        return in_array($type, MonthlyReportTemplate::types(), true) ? $type : MonthlyReportTemplate::TYPE_ADDITION;
    }

    protected static function normalizeIds($value): array
    {
        if (is_string($value)) {
            $value = array_filter(explode(',', $value));
        }
        if (!is_array($value)) {
            return [];
        }
        return array_values(array_unique(array_filter(array_map('intval', $value), static fn($id) => $id > 0)));
    }

    protected static function issue(string $code, string $message, bool $blocking, array $extra = []): array
    {
        return array_merge([
            'code' => $code,
            'message' => $message,
            'blocking' => $blocking ? 1 : 0,
        ], $extra);
    }

    protected static function hasBlockingIssues(array $issues): bool
    {
        foreach ($issues as $issue) {
            if ((int)($issue['blocking'] ?? 0) === 1) {
                return true;
            }
        }
        return false;
    }

    protected static function designContainsQrcode(array $design): bool
    {
        foreach (($design['layers'] ?? []) as $layer) {
            if (is_array($layer) && ($layer['type'] ?? '') === 'qrcode' && (int)($layer['visible'] ?? 1) === 1) {
                return true;
            }
        }
        return false;
    }

    protected static function getGlobalQrcodeImage(): string
    {
        $config = StaffScheduleConfirmLetterService::getGlobalQrcodeConfig();
        return self::normalizeStoredFileUrl((string)($config['schedule_qrcode_image'] ?? ''));
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

    protected static function formatCount(int $count): string
    {
        return str_pad((string)max(0, $count), 2, '0', STR_PAD_LEFT);
    }

    protected static function normalizeLayerId(string $id): string
    {
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id) ?: '';
        return mb_substr($id !== '' ? $id : uniqid('layer_', false), 0, 64, 'UTF-8');
    }

    protected static function normalizeColor(string $color, string $fallback): string
    {
        $color = trim($color);
        if ($color === '' && $fallback === '') {
            return '';
        }
        if (preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $color)) {
            return $color;
        }
        if (preg_match('/^rgba?\([0-9.,\s]+\)$/i', $color)) {
            return $color;
        }
        return $fallback;
    }

    protected static function normalizeOptionalColor(string $color): string
    {
        return self::normalizeColor($color, '');
    }

    protected static function clampInt(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    protected static function clampFloat(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    protected static function getStorageDefault(): string
    {
        return ConfigService::get('storage', 'default', 'local') ?: 'local';
    }

    protected static function buildTempAssetPath(string $fileName): string
    {
        $directory = rtrim(runtime_path(), '/\\') . DIRECTORY_SEPARATOR . self::ASSET_TEMP_DIR;
        self::ensureAssetDirectory($directory);
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName) ?: uniqid('monthly_report_', false) . '.jpg';
        return $directory . DIRECTORY_SEPARATOR . $safeName;
    }

    protected static function ensureAssetDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException(self::ERROR_ASSET_DIRECTORY);
        }
        if (!is_writable($directory)) {
            throw new \RuntimeException(self::ERROR_ASSET_DIRECTORY);
        }
    }

    protected static function isUsableLocalImageAsset(string $absolutePath): bool
    {
        clearstatcache(true, $absolutePath);
        return is_file($absolutePath)
            && is_readable($absolutePath)
            && (int)filesize($absolutePath) >= self::ASSET_MIN_VALID_BYTES;
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

    protected static function logAssetFailure(string $message, array $context = []): void
    {
        try {
            Log::write($message . '，上下文：' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            // 日志失败不应影响图片生成主流程。
        }
    }

    protected static function escapeAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    protected static function buildSnapshotHash(array $snapshot): string
    {
        return hash('sha256', self::RENDER_SPEC_VERSION . '|' . json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected static function demoStaffs(int $count, int $score = 12): array
    {
        $names = ['可玉', '鑫禹', '奕铭', '王帆', '朝阳', '王一平', '天元', '佳灏', '子德', '张宽', '王一', '祚然', '世雄', '彦许', '建南', '若涵', '佳孟', '刘超禹'];
        $english = ['KEYU', 'XINYU', 'YIMING', 'WANGFAN', 'ZHAOYANG', 'YIPING', 'TIANYUAN', 'JIAHAO', 'ZIDE', 'ZHANGKUAN', 'WANGYI', 'ZUORAN', 'SHIXIONG', 'YANXU', 'JIANNAN', 'JIAMENG', 'JIAMENG', 'CHAOYU'];
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'staff_id' => $i + 1,
                'staff_name' => $names[$i % count($names)],
                'english_name' => $english[$i % count($english)],
                'chinese_name' => $names[$i % count($names)],
                'photo_url' => '',
                'avatar_photo_url' => '',
                'half_body_photo_url' => '',
                'count_raw' => max(1, $score - ($i % 7)),
                'count' => self::formatCount(max(1, $score - ($i % 7))),
                'rank' => $i + 1,
                'material_complete' => 1,
                'ranking_material_complete' => 1,
                'top_material_complete' => 1,
                'material_sort' => 0,
            ];
        }
        return $rows;
    }
}
