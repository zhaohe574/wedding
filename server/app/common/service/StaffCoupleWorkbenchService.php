<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作台新人档案、手记、跟进与 Checklist 服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\logic\BaseLogic;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\questionnaire\CoupleQuestionnaireAnswer;
use app\common\model\questionnaire\CoupleQuestionnaireTask;
use app\common\model\schedule\ManualSchedule;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffFollowupReminder;
use app\common\model\staff\StaffServiceNote;
use app\common\model\staff\StaffWeddingChecklist;

/**
 * 服务人员工作台 P3 新人档案与日常执行服务
 */
class StaffCoupleWorkbenchService extends BaseLogic
{
    /**
     * @notes 获取新人档案列表
     */
    public static function coupleLists(int $staffId, array $params = []): array
    {
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $today = date('Y-m-d');
        $filter = trim((string)($params['filter'] ?? 'all'));
        $keyword = trim((string)($params['keyword'] ?? ''));

        $orders = Order::with([
                'items' => function ($q) use ($staffId) {
                    $q->field('id, order_id, staff_id, package_name, service_date, item_status, item_type, quantity')
                        ->where('staff_id', $staffId)
                        ->whereIn('item_type', self::getStaffItemTypes())
                        ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                        ->order('id', 'asc');
                },
            ])
            ->whereNotIn('order_status', self::getExcludedOrderStatuses())
            ->whereIn('id', function ($subQuery) use ($staffId) {
                $subQuery->name('order_item')
                    ->where('staff_id', $staffId)
                    ->whereIn('item_type', self::getStaffItemTypes())
                    ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                    ->field('order_id');
            })
            ->order('id', 'desc')
            ->select();

        $orderIds = [];
        foreach ($orders as $orderModel) {
            $orderIds[] = (int)$orderModel->id;
        }

        $manuals = ManualSchedule::where('staff_id', $staffId)
            ->where('status', '<>', ManualSchedule::STATUS_CANCELLED)
            ->order('schedule_date', 'desc')
            ->order('id', 'desc')
            ->select();

        $manualIds = [];
        foreach ($manuals as $manualModel) {
            $manualIds[] = (int)$manualModel->id;
        }

        $questionnaireMap = self::loadQuestionnaireStatusMap($orderIds, $staffId);
        $noteSummaryMap = self::loadNoteSummaryMap($staffId);
        $followupSummaryMap = self::loadPendingFollowupSummaryMap($staffId, $today);
        $checklistSummaryMap = self::loadChecklistSummaryMap($staffId);

        $rawCards = [];
        foreach ($orders as $orderModel) {
            $oid = (int)$orderModel->id;
            $items = is_object($orderModel->items) && method_exists($orderModel->items, 'toArray')
                ? $orderModel->items->toArray()
                : (array)($orderModel->items ?? []);
            $firstItem = $items[0] ?? [];
            $serviceDate = trim((string)($firstItem['service_date'] ?? ($orderModel->service_date ?? '')));
            $packageName = trim((string)($firstItem['package_name'] ?? '婚礼服务套餐'));
            $customerName = trim((string)($orderModel->contact_name ?? '')) ?: '平台新人';
            $mobile = trim((string)($orderModel->contact_mobile ?? ''));
            $orderStatus = (int)$orderModel->order_status;
            $isCompleted = in_array($orderStatus, [Order::STATUS_COMPLETED, Order::STATUS_REVIEWED], true);
            $countdown = self::buildDateCountdown($serviceDate, $today, $isCompleted);

            $targetKey = self::buildTargetKey('order', $oid);
            $noteMeta = $noteSummaryMap[$targetKey] ?? ['count' => 0, 'pinned_preview' => ''];
            $followupMeta = $followupSummaryMap[$targetKey] ?? ['pending_count' => 0, 'overdue_count' => 0, 'next_remind_date' => ''];
            $checkMeta = $checklistSummaryMap[$targetKey] ?? ['done_count' => 0, 'total_count' => 0];
            $qMeta = $questionnaireMap[$oid] ?? ['task_id' => 0, 'status' => -1, 'status_desc' => '未生成'];

            $rawCards[] = [
                'target_key' => $targetKey,
                'source_type' => 'order',
                'source_label' => '平台订单',
                'target_id' => $oid,
                'order_id' => $oid,
                'manual_schedule_id' => 0,
                'order_sn' => (string)($orderModel->order_sn ?? ''),
                'order_status' => $orderStatus,
                'status_desc' => Order::getStatusText($orderStatus),
                'customer_name' => $customerName,
                'customer_mobile' => $mobile,
                'customer_mobile_masked' => self::maskMobile($mobile),
                'service_date' => $serviceDate,
                'countdown_days' => $countdown['days'],
                'countdown_text' => $countdown['text'],
                'is_completed' => $isCompleted,
                'is_upcoming' => !$isCompleted && $serviceDate !== '' && $serviceDate >= $today,
                'service_address' => trim((string)($orderModel->service_address ?? '')),
                'package_name' => $packageName,
                'questionnaire_status' => (int)$qMeta['status'],
                'questionnaire_status_desc' => (string)$qMeta['status_desc'],
                'checklist_done' => (int)$checkMeta['done_count'],
                'checklist_total' => (int)$checkMeta['total_count'],
                'note_count' => (int)$noteMeta['count'],
                'pinned_note_preview' => (string)$noteMeta['pinned_preview'],
                'pending_followup_count' => (int)$followupMeta['pending_count'],
                'overdue_followup_count' => (int)$followupMeta['overdue_count'],
                'next_remind_date' => (string)$followupMeta['next_remind_date'],
            ];
        }

        foreach ($manuals as $manualModel) {
            $mid = (int)$manualModel->id;
            $serviceDate = trim((string)($manualModel->schedule_date ?? ''));
            $customerName = trim((string)($manualModel->customer_name ?? '')) ?: '线下客户';
            $mobile = trim((string)($manualModel->customer_mobile ?? ''));
            $address = trim(implode(' ', array_filter([
                trim((string)($manualModel->region_name ?? '')),
                trim((string)($manualModel->service_address ?? '')),
            ])));
            $mStatus = (int)$manualModel->status;
            $isCompleted = $mStatus === ManualSchedule::STATUS_COMPLETED;
            $countdown = self::buildDateCountdown($serviceDate, $today, $isCompleted);

            $targetKey = self::buildTargetKey('manual', $mid);
            $noteMeta = $noteSummaryMap[$targetKey] ?? ['count' => 0, 'pinned_preview' => ''];
            $followupMeta = $followupSummaryMap[$targetKey] ?? ['pending_count' => 0, 'overdue_count' => 0, 'next_remind_date' => ''];
            $checkMeta = $checklistSummaryMap[$targetKey] ?? ['done_count' => 0, 'total_count' => 0];

            $rawCards[] = [
                'target_key' => $targetKey,
                'source_type' => 'manual',
                'source_label' => '线下档期',
                'target_id' => $mid,
                'order_id' => 0,
                'manual_schedule_id' => $mid,
                'order_sn' => '',
                'order_status' => $mStatus,
                'status_desc' => (string)$manualModel->status_desc,
                'customer_name' => $customerName,
                'customer_mobile' => $mobile,
                'customer_mobile_masked' => self::maskMobile($mobile),
                'service_date' => $serviceDate,
                'countdown_days' => $countdown['days'],
                'countdown_text' => $countdown['text'],
                'is_completed' => $isCompleted,
                'is_upcoming' => !$isCompleted && $serviceDate !== '' && $serviceDate >= $today,
                'service_address' => $address,
                'package_name' => trim((string)($manualModel->service_name ?? '线下预约服务')),
                'questionnaire_status' => -1,
                'questionnaire_status_desc' => '线下档期',
                'checklist_done' => (int)$checkMeta['done_count'],
                'checklist_total' => (int)$checkMeta['total_count'],
                'note_count' => (int)$noteMeta['count'],
                'pinned_note_preview' => (string)$noteMeta['pinned_preview'],
                'pending_followup_count' => (int)$followupMeta['pending_count'],
                'overdue_followup_count' => (int)$followupMeta['overdue_count'],
                'next_remind_date' => (string)$followupMeta['next_remind_date'],
            ];
        }

        // 先统计全量摘要，再过滤输出
        $summary = [
            'total_count' => count($rawCards),
            'upcoming_count' => 0,
            'pending_followup_count' => 0,
            'overdue_followup_count' => 0,
            'completed_count' => 0,
        ];
        foreach ($rawCards as $card) {
            if ($card['is_upcoming']) {
                $summary['upcoming_count']++;
            }
            if ($card['pending_followup_count'] > 0) {
                $summary['pending_followup_count']++;
            }
            if ($card['overdue_followup_count'] > 0) {
                $summary['overdue_followup_count']++;
            }
            if ($card['is_completed']) {
                $summary['completed_count']++;
            }
        }

        $filtered = array_values(array_filter($rawCards, static function (array $card) use ($filter, $keyword): bool {
            if ($filter === 'upcoming' && !$card['is_upcoming']) {
                return false;
            }
            if ($filter === 'followup' && (int)$card['pending_followup_count'] <= 0) {
                return false;
            }
            if ($filter === 'completed' && !$card['is_completed']) {
                return false;
            }
            if ($keyword !== '') {
                $haystack = implode(' ', [
                    $card['customer_name'],
                    $card['customer_mobile'],
                    $card['service_address'],
                    $card['package_name'],
                    $card['order_sn'],
                    $card['service_date'],
                ]);
                if (stripos($haystack, $keyword) === false) {
                    return false;
                }
            }
            return true;
        }));

        usort($filtered, static function (array $a, array $b) use ($today): int {
            $aFuture = !$a['is_completed'] && $a['service_date'] !== '' && $a['service_date'] >= $today ? 0 : 1;
            $bFuture = !$b['is_completed'] && $b['service_date'] !== '' && $b['service_date'] >= $today ? 0 : 1;
            if ($aFuture !== $bFuture) {
                return $aFuture <=> $bFuture;
            }
            if ($aFuture === 0) {
                return strcmp($a['service_date'], $b['service_date']) ?: ($b['target_id'] <=> $a['target_id']);
            }
            return strcmp($b['service_date'], $a['service_date']) ?: ($b['target_id'] <=> $a['target_id']);
        });

        return [
            'summary' => $summary,
            'lists' => $filtered,
        ];
    }

    /**
     * @notes 获取新人档案详情（档案+问卷+Checklist+手记+提醒）
     */
    public static function coupleDetail(int $staffId, string $sourceType, int $targetId): array
    {
        $owned = self::assertTargetOwned($staffId, $sourceType, $targetId);
        if ($owned === null) {
            return [];
        }

        $today = date('Y-m-d');
        if ($owned['source_type'] === 'order') {
            /** @var Order $order */
            $order = $owned['order'];
            $items = $owned['items'];
            $firstItem = $items[0] ?? [];
            $serviceDate = trim((string)($firstItem['service_date'] ?? ($order->service_date ?? '')));
            $packageName = trim((string)($firstItem['package_name'] ?? '婚礼服务套餐'));
            $orderStatus = (int)$order->order_status;
            $isCompleted = in_array($orderStatus, [Order::STATUS_COMPLETED, Order::STATUS_REVIEWED], true);
            $countdown = self::buildDateCountdown($serviceDate, $today, $isCompleted);
            $mobile = trim((string)($order->contact_mobile ?? ''));
            $remark = trim((string)($order->user_remark ?? ''));
            if ($remark === '') {
                $remark = trim((string)($order->admin_remark ?? ''));
            }

            $addonParts = [];
            foreach ($items as $it) {
                foreach ((array)($it['addons'] ?? []) as $addon) {
                    $qty = max(1, (int)($addon['quantity'] ?? 1));
                    $addonParts[] = (string)($addon['addon_name'] ?? '附加项') . '×' . $qty;
                }
            }

            $questionnaire = self::loadOrderQuestionnaireDetail((int)$order->id, $staffId);
            $canSendQuestionnaire = in_array($orderStatus, [
                Order::STATUS_PENDING_CONFIRM,
                Order::STATUS_PENDING_SERVICE,
                Order::STATUS_IN_SERVICE,
            ], true) && (($questionnaire['status'] ?? -1) !== CoupleQuestionnaireTask::STATUS_SUBMITTED);

            $profile = [
                'target_key' => self::buildTargetKey('order', (int)$order->id),
                'source_type' => 'order',
                'source_label' => '平台订单',
                'target_id' => (int)$order->id,
                'order_id' => (int)$order->id,
                'manual_schedule_id' => 0,
                'order_sn' => (string)($order->order_sn ?? ''),
                'order_status' => $orderStatus,
                'status_desc' => Order::getStatusText($orderStatus),
                'customer_name' => trim((string)($order->contact_name ?? '')) ?: '平台新人',
                'customer_mobile' => $mobile,
                'customer_mobile_masked' => self::maskMobile($mobile),
                'service_date' => $serviceDate,
                'countdown_days' => $countdown['days'],
                'countdown_text' => $countdown['text'],
                'service_address' => trim((string)($order->service_address ?? '')),
                'package_name' => $packageName,
                'addons_summary' => implode('、', $addonParts),
                'remark' => $remark,
                'teammates' => self::loadOrderTeammates((int)$order->id, $staffId),
                'can_send_questionnaire' => $canSendQuestionnaire,
                'questionnaire' => $questionnaire,
            ];
        } else {
            /** @var ManualSchedule $manual */
            $manual = $owned['manual'];
            $serviceDate = trim((string)($manual->schedule_date ?? ''));
            $mStatus = (int)$manual->status;
            $isCompleted = $mStatus === ManualSchedule::STATUS_COMPLETED;
            $countdown = self::buildDateCountdown($serviceDate, $today, $isCompleted);
            $mobile = trim((string)($manual->customer_mobile ?? ''));
            $address = trim(implode(' ', array_filter([
                trim((string)($manual->region_name ?? '')),
                trim((string)($manual->service_address ?? '')),
            ])));

            $profile = [
                'target_key' => self::buildTargetKey('manual', (int)$manual->id),
                'source_type' => 'manual',
                'source_label' => '线下档期',
                'target_id' => (int)$manual->id,
                'order_id' => 0,
                'manual_schedule_id' => (int)$manual->id,
                'order_sn' => '',
                'order_status' => $mStatus,
                'status_desc' => (string)$manual->status_desc,
                'customer_name' => trim((string)($manual->customer_name ?? '')) ?: '线下客户',
                'customer_mobile' => $mobile,
                'customer_mobile_masked' => self::maskMobile($mobile),
                'service_date' => $serviceDate,
                'countdown_days' => $countdown['days'],
                'countdown_text' => $countdown['text'],
                'service_address' => $address,
                'package_name' => trim((string)($manual->service_name ?? '线下预约服务')),
                'addons_summary' => '',
                'remark' => trim((string)($manual->remark ?? '')),
                'teammates' => [],
                'can_send_questionnaire' => false,
                'questionnaire' => null,
            ];
        }

        return [
            'profile' => $profile,
            'checklist' => self::buildChecklistBlock($staffId, $sourceType, $targetId),
            'notes' => self::loadTargetNotes($staffId, $sourceType, $targetId),
            'followups' => self::loadTargetFollowups($staffId, $sourceType, $targetId, $today),
            'note_categories' => StaffServiceNote::getCategoryOptions(),
            'remind_types' => StaffFollowupReminder::getRemindTypeOptions(),
        ];
    }

    /**
     * @notes 工作台向新人发送/补发问卷
     */
    public static function coupleSendQuestionnaire(int $staffId, int $orderId): array|false
    {
        if (self::assertTargetOwned($staffId, 'order', $orderId) === null) {
            return false;
        }

        try {
            return CoupleQuestionnaireService::manualSendTaskByOrder($orderId, $staffId);
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 保存服务手记
     */
    public static function serviceNoteSave(int $userId, int $staffId, array $params): array|false
    {
        $sourceType = trim((string)($params['source_type'] ?? 'order'));
        $targetId = (int)($params['target_id'] ?? 0);
        if (self::assertTargetOwned($staffId, $sourceType, $targetId) === null) {
            return false;
        }

        $category = trim((string)($params['category'] ?? StaffServiceNote::CATEGORY_COMMUNICATION));
        if (!array_key_exists($category, StaffServiceNote::CATEGORIES)) {
            self::setError('手记分类不合法');
            return false;
        }

        $content = trim((string)($params['content'] ?? ''));
        if ($content === '') {
            self::setError('请输入手记正文');
            return false;
        }

        if (!StaffContentGuardService::textGuard($userId, ['content' => $content], WechatSecurityService::SCENE_DYNAMIC)) {
            self::setError(StaffContentGuardService::getError() ?: '内容包含敏感词，请调整后再试');
            return false;
        }

        $images = StaffServiceNote::decodeImages($params['images'] ?? []);
        $isPinned = (int)($params['is_pinned'] ?? 0) === 1 ? 1 : 0;
        $now = time();
        $id = (int)($params['id'] ?? 0);

        if ($id > 0) {
            $note = StaffServiceNote::where('id', $id)
                ->where('staff_id', $staffId)
                ->find();
            if (!$note) {
                self::setError('服务手记不存在');
                return false;
            }
        } else {
            $note = new StaffServiceNote();
            $note->staff_id = $staffId;
            $note->source_type = $sourceType;
            $note->target_id = $targetId;
            $note->create_time = $now;
        }

        $note->category = $category;
        $note->content = $content;
        $note->images = json_encode($images, JSON_UNESCAPED_UNICODE);
        $note->is_pinned = $isPinned;
        $note->update_time = $now;
        $note->save();

        return self::formatNoteItem($note);
    }

    /**
     * @notes 切换服务手记置顶状态
     */
    public static function serviceNoteTogglePin(int $staffId, int $id): array|false
    {
        $note = StaffServiceNote::where('id', $id)
            ->where('staff_id', $staffId)
            ->find();
        if (!$note) {
            self::setError('服务手记不存在');
            return false;
        }

        $note->is_pinned = (int)$note->is_pinned === 1 ? 0 : 1;
        $note->update_time = time();
        $note->save();

        return self::formatNoteItem($note);
    }

    /**
     * @notes 删除服务手记
     */
    public static function serviceNoteDelete(int $staffId, int $id): bool
    {
        $note = StaffServiceNote::where('id', $id)
            ->where('staff_id', $staffId)
            ->find();
        if (!$note) {
            self::setError('服务手记不存在');
            return false;
        }

        $note->delete();
        return true;
    }

    /**
     * @notes 跟进提醒列表
     */
    public static function followupLists(int $staffId, array $params = []): array
    {
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $today = date('Y-m-d');
        $weekEnd = date('Y-m-d', strtotime($today . ' +6 days'));
        $scope = trim((string)($params['scope'] ?? 'pending'));
        $keyword = trim((string)($params['keyword'] ?? ''));

        $allModels = StaffFollowupReminder::where('staff_id', $staffId)
            ->order('status', 'asc')
            ->order('remind_date', 'asc')
            ->order('remind_time', 'asc')
            ->order('id', 'desc')
            ->select();

        $targetMetaMap = self::loadReminderTargetMetaMap($allModels, $staffId);

        $summary = [
            'pending_total' => 0,
            'overdue_count' => 0,
            'today_count' => 0,
            'week_count' => 0,
            'done_count' => 0,
        ];

        $lists = [];
        foreach ($allModels as $row) {
            $item = self::formatFollowupItem($row, $today, $targetMetaMap);
            $status = (int)$item['status'];
            $rDate = (string)$item['remind_date'];

            if ($status === StaffFollowupReminder::STATUS_PENDING) {
                $summary['pending_total']++;
                if ($rDate < $today) {
                    $summary['overdue_count']++;
                } elseif ($rDate === $today) {
                    $summary['today_count']++;
                }
                if ($rDate >= $today && $rDate <= $weekEnd) {
                    $summary['week_count']++;
                }
            } else {
                $summary['done_count']++;
            }

            if ($scope === 'pending' && $status !== StaffFollowupReminder::STATUS_PENDING) {
                continue;
            }
            if ($scope === 'overdue' && ($status !== StaffFollowupReminder::STATUS_PENDING || $rDate >= $today)) {
                continue;
            }
            if ($scope === 'today' && ($status !== StaffFollowupReminder::STATUS_PENDING || $rDate !== $today)) {
                continue;
            }
            if ($scope === 'week' && ($status !== StaffFollowupReminder::STATUS_PENDING || $rDate < $today || $rDate > $weekEnd)) {
                continue;
            }
            if ($scope === 'done' && $status === StaffFollowupReminder::STATUS_PENDING) {
                continue;
            }

            if ($keyword !== '') {
                $haystack = implode(' ', [
                    $item['title'],
                    $item['remark'],
                    $item['customer_name'],
                    $item['service_address'],
                    $item['package_name'],
                ]);
                if (stripos($haystack, $keyword) === false) {
                    continue;
                }
            }

            $lists[] = $item;
        }

        return [
            'today' => $today,
            'summary' => $summary,
            'remind_types' => StaffFollowupReminder::getRemindTypeOptions(),
            'recent_targets' => self::loadRecentFollowupTargets($staffId),
            'lists' => $lists,
        ];
    }

    /**
     * @notes 保存跟进提醒
     */
    public static function followupSave(int $userId, int $staffId, array $params): array|false
    {
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $sourceType = trim((string)($params['source_type'] ?? StaffFollowupReminder::SOURCE_GENERAL));
        $targetId = (int)($params['target_id'] ?? 0);
        if ($sourceType !== StaffFollowupReminder::SOURCE_GENERAL) {
            if (self::assertTargetOwned($staffId, $sourceType, $targetId) === null) {
                return false;
            }
        } else {
            $targetId = 0;
        }

        $remindType = trim((string)($params['remind_type'] ?? StaffFollowupReminder::TYPE_PRE_COMM));
        if (!array_key_exists($remindType, StaffFollowupReminder::REMIND_TYPES)) {
            self::setError('提醒类型不合法');
            return false;
        }

        $title = trim((string)($params['title'] ?? ''));
        if ($title === '') {
            self::setError('请输入提醒标题');
            return false;
        }

        $remindDate = trim((string)($params['remind_date'] ?? ''));
        $parsedDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $remindDate);
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $remindDate) {
            self::setError('请选择有效的提醒日期');
            return false;
        }

        $remindTime = trim((string)($params['remind_time'] ?? '10:00'));
        if (!preg_match('/^\d{2}:\d{2}$/', $remindTime)) {
            $remindTime = '10:00';
        }
        $remark = trim((string)($params['remark'] ?? ''));

        if (!StaffContentGuardService::textGuard($userId, ['title' => $title, 'remark' => $remark], WechatSecurityService::SCENE_DYNAMIC)) {
            self::setError(StaffContentGuardService::getError() ?: '内容包含敏感词，请修改后重试');
            return false;
        }

        $now = time();
        $id = (int)($params['id'] ?? 0);
        if ($id > 0) {
            $row = StaffFollowupReminder::where('id', $id)
                ->where('staff_id', $staffId)
                ->find();
            if (!$row) {
                self::setError('跟进提醒不存在');
                return false;
            }
        } else {
            $row = new StaffFollowupReminder();
            $row->staff_id = $staffId;
            $row->status = StaffFollowupReminder::STATUS_PENDING;
            $row->done_time = 0;
            $row->create_time = $now;
        }

        $row->source_type = $sourceType;
        $row->target_id = $targetId;
        $row->remind_type = $remindType;
        $row->title = $title;
        $row->remind_date = $remindDate;
        $row->remind_time = $remindTime;
        $row->remind_at = (int)(strtotime($remindDate . ' ' . $remindTime . ':00') ?: strtotime($remindDate));
        $row->remark = $remark;
        $row->update_time = $now;
        $row->save();

        $metaMap = self::loadReminderTargetMetaMap([$row], $staffId);
        return self::formatFollowupItem($row, date('Y-m-d'), $metaMap);
    }

    /**
     * @notes 处理跟进提醒状态（done/reopen/snooze/ignore）
     */
    public static function followupAction(int $staffId, int $id, string $action): array|false
    {
        $row = StaffFollowupReminder::where('id', $id)
            ->where('staff_id', $staffId)
            ->find();
        if (!$row) {
            self::setError('跟进提醒不存在');
            return false;
        }

        $now = time();
        $today = date('Y-m-d');
        switch ($action) {
            case 'done':
                $row->status = StaffFollowupReminder::STATUS_DONE;
                $row->done_time = $now;
                break;
            case 'reopen':
                $row->status = StaffFollowupReminder::STATUS_PENDING;
                $row->done_time = 0;
                break;
            case 'snooze':
                $baseDate = (string)$row->remind_date < $today ? $today : (string)$row->remind_date;
                $nextDate = date('Y-m-d', strtotime($baseDate . ' +1 day'));
                $timePart = preg_match('/^\d{2}:\d{2}$/', (string)$row->remind_time) ? (string)$row->remind_time : '10:00';
                $row->status = StaffFollowupReminder::STATUS_PENDING;
                $row->done_time = 0;
                $row->remind_date = $nextDate;
                $row->remind_at = (int)(strtotime($nextDate . ' ' . $timePart . ':00') ?: strtotime($nextDate));
                break;
            case 'ignore':
                $row->status = StaffFollowupReminder::STATUS_IGNORED;
                $row->done_time = $now;
                break;
            default:
                self::setError('不支持的操作类型');
                return false;
        }

        $row->update_time = $now;
        $row->save();

        $metaMap = self::loadReminderTargetMetaMap([$row], $staffId);
        return self::formatFollowupItem($row, $today, $metaMap);
    }

    /**
     * @notes 删除跟进提醒
     */
    public static function followupDelete(int $staffId, int $id): bool
    {
        $row = StaffFollowupReminder::where('id', $id)
            ->where('staff_id', $staffId)
            ->find();
        if (!$row) {
            self::setError('跟进提醒不存在');
            return false;
        }

        $row->delete();
        return true;
    }

    /**
     * @notes 一键初始化标准婚礼 Checklist（按标题幂等补齐）
     */
    public static function checklistInitPresets(int $staffId, string $sourceType, int $targetId): array|false
    {
        if (self::assertTargetOwned($staffId, $sourceType, $targetId) === null) {
            return false;
        }

        $existingTitles = StaffWeddingChecklist::where('staff_id', $staffId)
            ->where('source_type', $sourceType)
            ->where('target_id', $targetId)
            ->column('title');
        $existingMap = array_flip(array_map('strval', $existingTitles));

        $now = time();
        foreach (StaffWeddingChecklist::getDefaultChecklistTemplates() as $preset) {
            $title = (string)$preset['title'];
            if (isset($existingMap[$title])) {
                continue;
            }
            $row = new StaffWeddingChecklist();
            $row->staff_id = $staffId;
            $row->source_type = $sourceType;
            $row->target_id = $targetId;
            $row->stage = (string)$preset['stage'];
            $row->title = $title;
            $row->remark = (string)$preset['remark'];
            $row->is_done = 0;
            $row->done_time = 0;
            $row->sort = (int)$preset['sort'];
            $row->is_preset = 1;
            $row->create_time = $now;
            $row->update_time = $now;
            $row->save();
        }

        return self::buildChecklistBlock($staffId, $sourceType, $targetId);
    }

    /**
     * @notes 新增或编辑 Checklist 事项
     */
    public static function checklistSave(int $userId, int $staffId, array $params): array|false
    {
        $sourceType = trim((string)($params['source_type'] ?? 'order'));
        $targetId = (int)($params['target_id'] ?? 0);
        if (self::assertTargetOwned($staffId, $sourceType, $targetId) === null) {
            return false;
        }

        $stage = trim((string)($params['stage'] ?? StaffWeddingChecklist::STAGE_PRE_WEDDING));
        if (!array_key_exists($stage, StaffWeddingChecklist::STAGES)) {
            self::setError('筹备阶段参数不合法');
            return false;
        }

        $title = trim((string)($params['title'] ?? ''));
        if ($title === '') {
            self::setError('请输入检查项名称');
            return false;
        }
        $remark = trim((string)($params['remark'] ?? ''));

        if (!StaffContentGuardService::textGuard($userId, ['title' => $title, 'remark' => $remark], WechatSecurityService::SCENE_DYNAMIC)) {
            self::setError(StaffContentGuardService::getError() ?: '内容包含敏感词，请修改后重试');
            return false;
        }

        $now = time();
        $id = (int)($params['id'] ?? 0);
        if ($id > 0) {
            $row = StaffWeddingChecklist::where('id', $id)
                ->where('staff_id', $staffId)
                ->find();
            if (!$row) {
                self::setError('Checklist 事项不存在');
                return false;
            }
        } else {
            $row = new StaffWeddingChecklist();
            $row->staff_id = $staffId;
            $row->source_type = $sourceType;
            $row->target_id = $targetId;
            $row->is_done = 0;
            $row->done_time = 0;
            $row->is_preset = 0;
            $row->create_time = $now;
        }

        $row->stage = $stage;
        $row->title = $title;
        $row->remark = $remark;
        $row->sort = (int)($params['sort'] ?? ($row->sort ?? 0));
        $row->update_time = $now;
        $row->save();

        return self::buildChecklistBlock($staffId, $sourceType, $targetId);
    }

    /**
     * @notes 切换 Checklist 完成状态
     */
    public static function checklistToggle(int $staffId, int $id): array|false
    {
        $row = StaffWeddingChecklist::where('id', $id)
            ->where('staff_id', $staffId)
            ->find();
        if (!$row) {
            self::setError('Checklist 事项不存在');
            return false;
        }

        $nextDone = (int)$row->is_done === 1 ? 0 : 1;
        $now = time();
        $row->is_done = $nextDone;
        $row->done_time = $nextDone === 1 ? $now : 0;
        $row->update_time = $now;
        $row->save();

        return self::buildChecklistBlock($staffId, (string)$row->source_type, (int)$row->target_id);
    }

    /**
     * @notes 删除 Checklist 事项
     */
    public static function checklistDelete(int $staffId, int $id): bool
    {
        $row = StaffWeddingChecklist::where('id', $id)
            ->where('staff_id', $staffId)
            ->find();
        if (!$row) {
            self::setError('Checklist 事项不存在');
            return false;
        }

        $row->delete();
        return true;
    }

    /**
     * @notes 统计近 3 日内需跟进/已逾期提醒数（用于工作台角标）
     */
    public static function countPendingFollowups(int $staffId): int
    {
        if ($staffId <= 0) {
            return 0;
        }
        $deadline = date('Y-m-d', strtotime('+3 days'));
        return (int)StaffFollowupReminder::where('staff_id', $staffId)
            ->where('status', StaffFollowupReminder::STATUS_PENDING)
            ->where('remind_date', '<=', $deadline)
            ->count();
    }

    /**
     * @notes 统计有效新人档案总数（平台未取消订单数 + 线下未取消档期数）
     */
    public static function countActiveCouples(int $staffId): int
    {
        if ($staffId <= 0) {
            return 0;
        }

        $orderCount = (int)Order::whereNotIn('order_status', self::getExcludedOrderStatuses())
            ->whereIn('id', function ($subQuery) use ($staffId) {
                $subQuery->name('order_item')
                    ->where('staff_id', $staffId)
                    ->whereIn('item_type', self::getStaffItemTypes())
                    ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                    ->field('order_id');
            })
            ->count();

        $manualCount = (int)ManualSchedule::where('staff_id', $staffId)
            ->where('status', '<>', ManualSchedule::STATUS_CANCELLED)
            ->count();

        return $orderCount + $manualCount;
    }

    private static function assertTargetOwned(int $staffId, string $sourceType, int $targetId): ?array
    {
        if ($staffId <= 0 || $targetId <= 0) {
            self::setError('新人档案不存在或无权访问');
            return null;
        }

        if ($sourceType === 'order') {
            $order = Order::where('id', $targetId)
                ->whereNotIn('order_status', self::getExcludedOrderStatuses())
                ->whereIn('id', function ($subQuery) use ($staffId, $targetId) {
                    $subQuery->name('order_item')
                        ->where('order_id', $targetId)
                        ->where('staff_id', $staffId)
                        ->whereIn('item_type', self::getStaffItemTypes())
                        ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                        ->field('order_id');
                })
                ->find();
            if (!$order) {
                self::setError('新人档案不存在或无权访问');
                return null;
            }
            $items = OrderItem::where('order_id', $targetId)
                ->where('staff_id', $staffId)
                ->whereIn('item_type', self::getStaffItemTypes())
                ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                ->with(['addons' => function ($q) {
                    $q->field('id, order_item_id, addon_id, addon_name, price, quantity, subtotal');
                }])
                ->order('id', 'asc')
                ->select()
                ->toArray();

            return [
                'source_type' => 'order',
                'target_id' => $targetId,
                'order' => $order,
                'items' => $items,
            ];
        }

        if ($sourceType === 'manual') {
            $manual = ManualSchedule::where('id', $targetId)
                ->where('staff_id', $staffId)
                ->where('status', '<>', ManualSchedule::STATUS_CANCELLED)
                ->find();
            if (!$manual) {
                self::setError('新人档案不存在或无权访问');
                return null;
            }

            return [
                'source_type' => 'manual',
                'target_id' => $targetId,
                'manual' => $manual,
            ];
        }

        self::setError('新人档案类型错误');
        return null;
    }

    private static function getStaffItemTypes(): array
    {
        return [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF];
    }

    private static function getExcludedOrderStatuses(): array
    {
        return [
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
            Order::STATUS_USER_DELETED,
        ];
    }

    private static function buildTargetKey(string $sourceType, int $targetId): string
    {
        return $sourceType . '-' . $targetId;
    }

    private static function buildDateCountdown(string $serviceDate, string $today, bool $isCompleted): array
    {
        if ($serviceDate === '') {
            return ['days' => null, 'text' => '待定婚期'];
        }
        $targetTs = strtotime($serviceDate);
        $todayTs = strtotime($today);
        if (!$targetTs || !$todayTs) {
            return ['days' => null, 'text' => $serviceDate];
        }
        $diffDays = (int)round(($targetTs - $todayTs) / 86400);
        if ($isCompleted) {
            return ['days' => $diffDays, 'text' => '已履约'];
        }
        if ($diffDays === 0) {
            return ['days' => 0, 'text' => '今天服务'];
        }
        if ($diffDays === 1) {
            return ['days' => 1, 'text' => '明天服务'];
        }
        if ($diffDays > 1) {
            return ['days' => $diffDays, 'text' => "还有 {$diffDays} 天"];
        }
        $passed = abs($diffDays);
        return ['days' => $diffDays, 'text' => "已过 {$passed} 天"];
    }

    private static function loadQuestionnaireStatusMap(array $orderIds, int $staffId): array
    {
        if (empty($orderIds)) {
            return [];
        }
        $tasks = CoupleQuestionnaireTask::whereIn('order_id', $orderIds)
            ->where('staff_id', $staffId)
            ->order('id', 'desc')
            ->select();

        $map = [];
        foreach ($tasks as $task) {
            $oid = (int)$task->order_id;
            if (isset($map[$oid])) {
                continue;
            }
            $status = (int)$task->status;
            $desc = match ($status) {
                CoupleQuestionnaireTask::STATUS_SUBMITTED => '问卷已提交',
                CoupleQuestionnaireTask::STATUS_VIEWED => '新人已查看',
                CoupleQuestionnaireTask::STATUS_CANCELLED => '已取消',
                CoupleQuestionnaireTask::STATUS_EXPIRED => '已过期',
                default => '待填写',
            };
            $map[$oid] = [
                'task_id' => (int)$task->id,
                'status' => $status,
                'status_desc' => $desc,
            ];
        }
        return $map;
    }

    private static function loadOrderQuestionnaireDetail(int $orderId, int $staffId): ?array
    {
        $task = CoupleQuestionnaireTask::where('order_id', $orderId)
            ->where('staff_id', $staffId)
            ->order('id', 'desc')
            ->find();
        if (!$task) {
            return null;
        }

        $status = (int)$task->status;
        $statusDesc = match ($status) {
            CoupleQuestionnaireTask::STATUS_SUBMITTED => '已提交',
            CoupleQuestionnaireTask::STATUS_VIEWED => '新人已查看',
            CoupleQuestionnaireTask::STATUS_CANCELLED => '已取消',
            CoupleQuestionnaireTask::STATUS_EXPIRED => '已过期',
            default => '待填写',
        };

        $items = [];
        if ($status === CoupleQuestionnaireTask::STATUS_SUBMITTED) {
            $answerModel = CoupleQuestionnaireAnswer::where('task_id', (int)$task->id)
                ->order('id', 'desc')
                ->find();
            if ($answerModel) {
                $qSnapshots = is_array($answerModel->questions_snapshot) && !empty($answerModel->questions_snapshot)
                    ? $answerModel->questions_snapshot
                    : (is_array($task->questions_snapshot) ? $task->questions_snapshot : []);
                $catMap = [];
                foreach ($qSnapshots as $q) {
                    if (!is_array($q)) {
                        continue;
                    }
                    $cat = trim((string)($q['category'] ?? '')) ?: '问卷详情';
                    $qId = (string)($q['id'] ?? '');
                    $qBankId = (string)($q['bank_id'] ?? '');
                    if ($qId !== '') {
                        $catMap[$qId] = $cat;
                    }
                    if ($qBankId !== '' && !isset($catMap[$qBankId])) {
                        $catMap[$qBankId] = $cat;
                    }
                }

                $rawAnswers = is_array($answerModel->answers) ? $answerModel->answers : [];
                foreach ($rawAnswers as $idx => $ansRow) {
                    if (!is_array($ansRow)) {
                        continue;
                    }
                    $val = $ansRow['value'] ?? '';
                    $valText = is_array($val)
                        ? implode('、', array_filter(array_map('strval', $val)))
                        : trim((string)$val);
                    if ($valText === '') {
                        continue;
                    }
                    $key = (string)($ansRow['key'] ?? '');
                    $cat = $catMap[$key] ?? '问卷详情';
                    $title = (string)($ansRow['title'] ?? '');
                    $isTaboo = preg_match('/避讳|忌讳|禁忌|敏感|雷区/u', $cat . ' ' . $title . ' ' . $valText) === 1;
                    $items[] = [
                        'category' => $cat,
                        'title' => $title,
                        'answer' => $valText,
                        'is_taboo' => $isTaboo,
                        '_priority' => $isTaboo ? 0 : 1,
                        '_index' => (int)$idx,
                    ];
                }

                usort($items, static function (array $a, array $b): int {
                    return ($a['_priority'] <=> $b['_priority']) ?: ($a['_index'] <=> $b['_index']);
                });

                foreach ($items as &$it) {
                    unset($it['_priority'], $it['_index']);
                }
                unset($it);
            }
        }

        $subTs = (int)($task->getData('submitted_time') ?: $task->getData('submit_time') ?: 0);
        return [
            'task_id' => (int)$task->id,
            'status' => $status,
            'status_desc' => $statusDesc,
            'send_status' => (int)$task->send_status,
            'submitted_time_text' => $subTs > 0 ? date('Y-m-d H:i', $subTs) : '',
            'answer_count' => count($items),
            'answers' => $items,
        ];
    }

    private static function loadOrderTeammates(int $orderId, int $staffId): array
    {
        $rows = OrderItem::where('order_id', $orderId)
            ->where('staff_id', '>', 0)
            ->where('staff_id', '<>', $staffId)
            ->whereIn('item_type', self::getStaffItemTypes())
            ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->field('staff_id, staff_name, package_name, confirm_status')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        if (empty($rows)) {
            return [];
        }

        $otherIds = array_values(array_unique(array_column($rows, 'staff_id')));
        $staffRows = Staff::whereIn('id', $otherIds)
            ->field('id, name, category_id')
            ->append(['category_name'])
            ->select()
            ->toArray();
        $infoMap = [];
        foreach ($staffRows as $sr) {
            $infoMap[(int)$sr['id']] = [
                'name' => (string)($sr['name'] ?? ''),
                'category_name' => (string)($sr['category_name'] ?? ''),
            ];
        }

        $list = [];
        foreach ($rows as $row) {
            $sid = (int)($row['staff_id'] ?? 0);
            $info = $infoMap[$sid] ?? ['name' => '', 'category_name' => ''];
            $list[] = [
                'staff_id' => $sid,
                'staff_name' => trim((string)($row['staff_name'] ?? '')) ?: $info['name'],
                'category_name' => $info['category_name'] ?: '协作人员',
                'package_name' => (string)($row['package_name'] ?? ''),
                'confirm_status' => (int)($row['confirm_status'] ?? 0),
            ];
        }
        return $list;
    }

    private static function loadNoteSummaryMap(int $staffId): array
    {
        $rows = StaffServiceNote::where('staff_id', $staffId)
            ->order('is_pinned', 'desc')
            ->order('update_time', 'desc')
            ->order('id', 'desc')
            ->select();

        $map = [];
        foreach ($rows as $row) {
            $k = self::buildTargetKey((string)$row->source_type, (int)$row->target_id);
            if (!isset($map[$k])) {
                $map[$k] = ['count' => 0, 'pinned_preview' => ''];
            }
            $map[$k]['count']++;
            if ($map[$k]['pinned_preview'] === '' && (int)$row->is_pinned === 1) {
                $text = preg_replace('/\s+/', ' ', trim((string)$row->content)) ?? '';
                $map[$k]['pinned_preview'] = function_exists('mb_substr')
                    ? mb_substr($text, 0, 60)
                    : substr($text, 0, 60);
            }
        }
        return $map;
    }

    private static function loadPendingFollowupSummaryMap(int $staffId, string $today): array
    {
        $rows = StaffFollowupReminder::where('staff_id', $staffId)
            ->where('status', StaffFollowupReminder::STATUS_PENDING)
            ->where('source_type', '<>', StaffFollowupReminder::SOURCE_GENERAL)
            ->order('remind_date', 'asc')
            ->select();

        $map = [];
        foreach ($rows as $row) {
            $k = self::buildTargetKey((string)$row->source_type, (int)$row->target_id);
            if (!isset($map[$k])) {
                $map[$k] = ['pending_count' => 0, 'overdue_count' => 0, 'next_remind_date' => ''];
            }
            $map[$k]['pending_count']++;
            $rDate = (string)$row->remind_date;
            if ($rDate < $today) {
                $map[$k]['overdue_count']++;
            }
            if ($map[$k]['next_remind_date'] === '') {
                $map[$k]['next_remind_date'] = $rDate;
            }
        }
        return $map;
    }

    private static function loadChecklistSummaryMap(int $staffId): array
    {
        $rows = StaffWeddingChecklist::where('staff_id', $staffId)
            ->field('source_type, target_id, is_done')
            ->select();

        $map = [];
        foreach ($rows as $row) {
            $k = self::buildTargetKey((string)$row->source_type, (int)$row->target_id);
            if (!isset($map[$k])) {
                $map[$k] = ['done_count' => 0, 'total_count' => 0];
            }
            $map[$k]['total_count']++;
            if ((int)$row->is_done === 1) {
                $map[$k]['done_count']++;
            }
        }
        return $map;
    }

    private static function buildChecklistBlock(int $staffId, string $sourceType, int $targetId): array
    {
        $rows = StaffWeddingChecklist::where('staff_id', $staffId)
            ->where('source_type', $sourceType)
            ->where('target_id', $targetId)
            ->order('is_done', 'asc')
            ->order('sort', 'desc')
            ->order('id', 'asc')
            ->select();

        $items = [];
        $doneCount = 0;
        foreach ($rows as $row) {
            $isDone = (int)$row->is_done === 1;
            if ($isDone) {
                $doneCount++;
            }
            $stage = (string)$row->stage;
            $doneTs = (int)($row->getData('done_time') ?: 0);
            $items[] = [
                'id' => (int)$row->id,
                'stage' => $stage,
                'stage_label' => StaffWeddingChecklist::STAGES[$stage] ?? $stage,
                'title' => (string)$row->title,
                'remark' => (string)$row->remark,
                'is_done' => $isDone ? 1 : 0,
                'done_time_text' => $doneTs > 0 ? date('Y-m-d H:i', $doneTs) : '',
                'is_preset' => (int)$row->is_preset,
                'sort' => (int)$row->sort,
            ];
        }

        $totalCount = count($items);
        $percent = $totalCount > 0 ? (int)round(($doneCount / $totalCount) * 100) : 0;

        return [
            'stages' => StaffWeddingChecklist::getStageOptions(),
            'items' => $items,
            'done_count' => $doneCount,
            'total_count' => $totalCount,
            'progress_percent' => $percent,
        ];
    }

    private static function loadTargetNotes(int $staffId, string $sourceType, int $targetId): array
    {
        $rows = StaffServiceNote::where('staff_id', $staffId)
            ->where('source_type', $sourceType)
            ->where('target_id', $targetId)
            ->order('is_pinned', 'desc')
            ->order('update_time', 'desc')
            ->order('id', 'desc')
            ->select();

        $list = [];
        foreach ($rows as $row) {
            $list[] = self::formatNoteItem($row);
        }
        return $list;
    }

    private static function formatNoteItem(StaffServiceNote $note): array
    {
        $category = (string)$note->category;
        $rawImages = StaffServiceNote::decodeImages($note->getData('images') ?? $note->images);
        $imageItems = [];
        foreach ($rawImages as $path) {
            $imageItems[] = [
                'uri' => $path,
                'url' => FileService::getFileUrl($path),
            ];
        }
        $ts = (int)($note->getData('update_time') ?: $note->getData('create_time') ?: 0);

        return [
            'id' => (int)$note->id,
            'source_type' => (string)$note->source_type,
            'target_id' => (int)$note->target_id,
            'category' => $category,
            'category_label' => StaffServiceNote::CATEGORIES[$category] ?? $category,
            'content' => (string)$note->content,
            'images' => array_column($imageItems, 'url'),
            'image_items' => $imageItems,
            'is_pinned' => (int)$note->is_pinned === 1 ? 1 : 0,
            'update_time_text' => $ts > 0 ? date('Y-m-d H:i', $ts) : '',
        ];
    }

    private static function loadTargetFollowups(int $staffId, string $sourceType, int $targetId, string $today): array
    {
        $rows = StaffFollowupReminder::where('staff_id', $staffId)
            ->where('source_type', $sourceType)
            ->where('target_id', $targetId)
            ->order('status', 'asc')
            ->order('remind_date', 'asc')
            ->order('remind_time', 'asc')
            ->order('id', 'desc')
            ->select();

        $metaMap = self::loadReminderTargetMetaMap($rows, $staffId);
        $list = [];
        foreach ($rows as $row) {
            $list[] = self::formatFollowupItem($row, $today, $metaMap);
        }
        return $list;
    }

    private static function loadReminderTargetMetaMap(iterable $reminders, int $staffId): array
    {
        $orderIds = [];
        $manualIds = [];
        foreach ($reminders as $row) {
            $src = (string)$row->source_type;
            $tid = (int)$row->target_id;
            if ($tid <= 0) {
                continue;
            }
            if ($src === 'order') {
                $orderIds[] = $tid;
            } elseif ($src === 'manual') {
                $manualIds[] = $tid;
            }
        }
        $orderIds = array_values(array_unique($orderIds));
        $manualIds = array_values(array_unique($manualIds));

        $map = [];
        if (!empty($orderIds)) {
            $orders = Order::whereIn('id', $orderIds)
                ->with([
                    'items' => function ($q) use ($staffId) {
                        $q->field('id, order_id, staff_id, package_name, service_date')
                            ->where('staff_id', $staffId)
                            ->order('id', 'asc');
                    },
                ])
                ->select();
            foreach ($orders as $order) {
                $items = is_object($order->items) && method_exists($order->items, 'toArray')
                    ? $order->items->toArray()
                    : (array)($order->items ?? []);
                $firstItem = $items[0] ?? [];
                $mobile = trim((string)($order->contact_mobile ?? ''));
                $map[self::buildTargetKey('order', (int)$order->id)] = [
                    'customer_name' => trim((string)($order->contact_name ?? '')) ?: '平台新人',
                    'customer_mobile' => $mobile,
                    'customer_mobile_masked' => self::maskMobile($mobile),
                    'service_date' => trim((string)($firstItem['service_date'] ?? ($order->service_date ?? ''))),
                    'service_address' => trim((string)($order->service_address ?? '')),
                    'package_name' => trim((string)($firstItem['package_name'] ?? '婚礼服务')),
                ];
            }
        }

        if (!empty($manualIds)) {
            $manuals = ManualSchedule::whereIn('id', $manualIds)
                ->where('staff_id', $staffId)
                ->select();
            foreach ($manuals as $manual) {
                $mobile = trim((string)($manual->customer_mobile ?? ''));
                $addr = trim(implode(' ', array_filter([
                    trim((string)($manual->region_name ?? '')),
                    trim((string)($manual->service_address ?? '')),
                ])));
                $map[self::buildTargetKey('manual', (int)$manual->id)] = [
                    'customer_name' => trim((string)($manual->customer_name ?? '')) ?: '线下客户',
                    'customer_mobile' => $mobile,
                    'customer_mobile_masked' => self::maskMobile($mobile),
                    'service_date' => trim((string)($manual->schedule_date ?? '')),
                    'service_address' => $addr,
                    'package_name' => trim((string)($manual->service_name ?? '线下服务')),
                ];
            }
        }

        return $map;
    }

    private static function formatFollowupItem(StaffFollowupReminder $row, string $today, array $metaMap): array
    {
        $sourceType = (string)$row->source_type;
        $targetId = (int)$row->target_id;
        $metaKey = self::buildTargetKey($sourceType, $targetId);
        $targetMeta = $metaMap[$metaKey] ?? [
            'customer_name' => $sourceType === 'general' ? '通用工作事项' : '',
            'customer_mobile' => '',
            'customer_mobile_masked' => '',
            'service_date' => '',
            'service_address' => '',
            'package_name' => '',
        ];

        $remindType = (string)$row->remind_type;
        $remindDate = (string)$row->remind_date;
        $status = (int)$row->status;
        $isOverdue = $status === StaffFollowupReminder::STATUS_PENDING && $remindDate < $today;
        $isToday = $status === StaffFollowupReminder::STATUS_PENDING && $remindDate === $today;

        return [
            'id' => (int)$row->id,
            'source_type' => $sourceType,
            'target_id' => $targetId,
            'remind_type' => $remindType,
            'remind_type_label' => StaffFollowupReminder::REMIND_TYPES[$remindType] ?? $remindType,
            'title' => (string)$row->title,
            'remind_date' => $remindDate,
            'remind_time' => (string)$row->remind_time,
            'remark' => (string)$row->remark,
            'status' => $status,
            'status_desc' => $isOverdue ? '已逾期' : StaffFollowupReminder::getStatusDesc($status),
            'is_overdue' => $isOverdue,
            'is_today' => $isToday,
            'customer_name' => (string)$targetMeta['customer_name'],
            'customer_mobile' => (string)$targetMeta['customer_mobile'],
            'customer_mobile_masked' => (string)$targetMeta['customer_mobile_masked'],
            'service_date' => (string)$targetMeta['service_date'],
            'service_address' => (string)$targetMeta['service_address'],
            'package_name' => (string)$targetMeta['package_name'],
        ];
    }

    private static function loadRecentFollowupTargets(int $staffId): array
    {
        $targets = [];

        $orders = Order::whereNotIn('order_status', self::getExcludedOrderStatuses())
            ->whereIn('id', function ($subQuery) use ($staffId) {
                $subQuery->name('order_item')
                    ->where('staff_id', $staffId)
                    ->whereIn('item_type', self::getStaffItemTypes())
                    ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                    ->field('order_id');
            })
            ->with([
                'items' => function ($q) use ($staffId) {
                    $q->field('id, order_id, staff_id, package_name, service_date')
                        ->where('staff_id', $staffId)
                        ->order('id', 'asc');
                },
            ])
            ->order('id', 'desc')
            ->limit(8)
            ->select();

        foreach ($orders as $order) {
            $items = is_object($order->items) && method_exists($order->items, 'toArray')
                ? $order->items->toArray()
                : (array)($order->items ?? []);
            $firstItem = $items[0] ?? [];
            $serviceDate = trim((string)($firstItem['service_date'] ?? ($order->service_date ?? '')));
            $customerName = trim((string)($order->contact_name ?? '')) ?: '平台新人';
            $targets[] = [
                'target_key' => self::buildTargetKey('order', (int)$order->id),
                'source_type' => 'order',
                'target_id' => (int)$order->id,
                'title' => ($serviceDate !== '' ? $serviceDate . ' · ' : '') . $customerName,
                'service_date' => $serviceDate,
                'customer_name' => $customerName,
            ];
        }

        $manuals = ManualSchedule::where('staff_id', $staffId)
            ->where('status', '<>', ManualSchedule::STATUS_CANCELLED)
            ->order('schedule_date', 'desc')
            ->order('id', 'desc')
            ->limit(6)
            ->select();

        foreach ($manuals as $manual) {
            $serviceDate = trim((string)($manual->schedule_date ?? ''));
            $customerName = trim((string)($manual->customer_name ?? '')) ?: '线下客户';
            $targets[] = [
                'target_key' => self::buildTargetKey('manual', (int)$manual->id),
                'source_type' => 'manual',
                'target_id' => (int)$manual->id,
                'title' => ($serviceDate !== '' ? $serviceDate . ' · ' : '') . $customerName . '（线下）',
                'service_date' => $serviceDate,
                'customer_name' => $customerName,
            ];
        }

        return array_slice($targets, 0, 12);
    }

    private static function maskMobile(string $mobile): string
    {
        $mobile = trim($mobile);
        if ($mobile === '') {
            return '';
        }
        if (preg_match('/^(\d{3})\d{4}(\d{4})$/', $mobile)) {
            return (string)preg_replace('/^(\d{3})\d{4}(\d{4})$/', '$1****$2', $mobile);
        }
        $len = strlen($mobile);
        if ($len >= 7) {
            return substr($mobile, 0, 3) . '****' . substr($mobile, -4);
        }
        return $mobile;
    }
}
