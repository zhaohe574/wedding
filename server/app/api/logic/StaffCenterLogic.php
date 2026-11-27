<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员中心逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\enum\notice\NoticeEnum;
use app\common\logic\BaseLogic;
use app\common\model\auth\Admin;
use app\common\model\dynamic\Dynamic;
use app\common\model\financial\StaffSettlement;
use app\common\model\notification\Notification;
use app\common\model\order\Order;
use app\common\model\order\OrderChange;
use app\common\model\order\OrderLog;
use app\common\model\order\OrderItem;
use app\common\model\order\Payment;
use app\common\model\package\PackageBooking;
use app\common\model\questionnaire\CoupleQuestionnaireAnswer;
use app\common\model\questionnaire\CoupleQuestionnaireTask;
use app\common\model\schedule\Schedule;
use app\common\model\schedule\ManualSchedule;
use app\common\model\schedule\Waitlist;
use app\common\model\service\ServiceAddon;
use app\common\model\service\ServiceCategory;
use app\common\model\service\ServicePackage;
use app\common\model\service\ServicePackageAddon;
use app\common\model\service\ServicePackageRegionPrice;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffBanner;
use app\common\model\staff\StaffCertificate;
use app\common\model\staff\StaffCopyTemplate;
use app\common\model\staff\StaffScheduleConfirmLetter;
use app\common\model\staff\StaffWork;
use app\common\service\OrderNotificationService;
use app\common\service\PackageRegionPriceService;
use app\common\service\MonthlyReportService;
use app\common\service\ScheduleOccupancyService;
use app\common\service\StaffContentGuardService;
use app\common\service\StaffCoupleWorkbenchService;
use app\common\service\StaffPriceService;
use app\common\service\StaffScheduleConfirmLetterService;
use app\common\service\StaffService;
use app\common\service\StaffSettlementService;
use app\common\service\StaffTagReviewService;
use app\common\service\StaffTeamService;
use app\common\service\StaffTeamWorkbenchService;
use app\common\service\WechatSecurityService;
use app\common\service\sms\SmsDriver;
use think\facade\Db;

/**
 * 服务人员中心逻辑
 * Class StaffCenterLogic
 * @package app\api\logic
 */
class StaffCenterLogic extends BaseLogic
{
    private static ?string $staffCertificateStatusField = null;
    private static ?array $staffCertificateFields = null;

    /**
     * @notes 获取服务人员ID
     */
    public static function getStaffId(int $userId): int
    {
        return StaffService::getStaffIdByUserId($userId);
    }

    /**
     * @notes 获取个人资料
     */
    public static function profile(int $userId): array
    {
        $staff = Staff::where('user_id', $userId)->find();
        if (!$staff) {
            self::setError('未绑定服务人员');
            return [];
        }

        $data = $staff->toArray();
        // 工作台资料下发裁剪：admin_id 为服务人员归属管理员的内部字段，不随个人资料接口下发
        //（保留 mobile_full，由前端脱敏展示）
        unset($data['admin_id']);
        $fullMobile = $staff->getData('mobile_full') ?: $staff->getData('mobile');
        $data['mobile'] = $fullMobile;
        $data['mobile_full'] = $fullMobile;
        $displayPrice = StaffPriceService::getDisplayPriceByStaffId((int)$staff->id);
        $data['price'] = $displayPrice['price'];
        $data['has_price'] = $displayPrice['has_price'];
        $data['price_text'] = $displayPrice['price_text'];
        $data['monthly_report_material'] = MonthlyReportService::materialByStaffId((int)$staff->id);

        // 统计数据
        $data['orderCount'] = (int) self::buildStaffFollowableOrderBaseQuery((int) $staff->id)->count();

        $data['workCount'] = StaffWork::where('staff_id', $staff->id)
            ->where('delete_time', null)
            ->count();

        $data['packageCount'] = ServicePackage::where('staff_id', $staff->id)
            ->where('delete_time', null)
            ->count();

        $data['addonCount'] = ServiceAddon::where('staff_id', $staff->id)
            ->whereNull('delete_time')
            ->count();

        $data['scheduleCount'] = Schedule::where('staff_id', $staff->id)->count();
        $data['todoCount'] = self::getTodoCount((int) $staff->id);
        $data = array_merge($data, StaffTagReviewService::getProfileTagState((int) $staff->id));

        return $data;
    }

    /**
     * @notes 获取工作台首页数据
     */
    public static function dashboard(int $userId): array
    {
        $profile = self::profile($userId);
        if (empty($profile)) {
            return [];
        }

        $staffId = (int) ($profile['id'] ?? 0);
        $unreadMessageCount = Notification::getUnreadCount($userId);
        $pendingConfirmOrders = self::getTodoCount($staffId);
        $todayServiceCount = self::getTodayServiceCount($staffId);
        $upcomingScheduleCount = self::getUpcomingScheduleCount($staffId);
        $settlementCount = (int) StaffSettlement::where('staff_id', $staffId)->count();
        $pendingSettlementCount = self::getPendingSettlementCount($staffId);
        $pendingWaitlistCount = self::getPendingWaitlistCount($staffId);
        $teamLeaderInfo = StaffTeamService::getLeaderTeam($staffId);
        $teamActiveInfo = $teamLeaderInfo ?: StaffTeamService::getActiveTeamByStaffId($staffId);
        $hasTeam = !empty($teamActiveInfo['team_id']);
        $isTeamLeader = !empty($teamLeaderInfo['team_id']);
        $teamName = (string)($teamActiveInfo['team_name'] ?? '');
        $coupleCount = StaffCoupleWorkbenchService::countActiveCouples($staffId);
        $teamMemberCount = StaffTeamWorkbenchService::countMyTeamMembers($staffId);
        $pendingFollowupCount = StaffCoupleWorkbenchService::countPendingFollowups($staffId);

        return [
            'profile' => [
                'name' => $profile['name'] ?? '',
                'avatar' => $profile['avatar'] ?? '',
                'status' => (int) ($profile['status'] ?? 0),
                'status_desc' => $profile['status_desc'] ?? '',
                'audit_status' => (int) ($profile['audit_status'] ?? 0),
                'audit_status_desc' => $profile['audit_status_desc'] ?? '',
                'mobile' => $profile['mobile_full'] ?? ($profile['mobile'] ?? ''),
                'price_text' => $profile['price_text'] ?? '',
                'has_price' => (bool) ($profile['has_price'] ?? false),
                'category_name' => $profile['category_name'] ?? '',
                'rating' => $profile['rating'] ?? 0,
                'experience_years' => $profile['experience_years'] ?? 0,
                'has_team' => $hasTeam,
                'is_team_leader' => $isTeamLeader,
                'team_name' => $teamName,
            ],
            'overview' => [
                'order_count' => (int) ($profile['orderCount'] ?? 0),
                'work_count' => (int) ($profile['workCount'] ?? 0),
                'package_count' => (int) ($profile['packageCount'] ?? 0),
                'addon_count' => (int) ($profile['addonCount'] ?? 0),
                'schedule_count' => (int) ($profile['scheduleCount'] ?? 0),
                'settlement_count' => $settlementCount,
                'couple_count' => $coupleCount,
                'team_member_count' => $teamMemberCount,
            ],
            'todo' => [
                'pending_confirm_orders' => $pendingConfirmOrders,
                'today_service_count' => $todayServiceCount,
                'upcoming_7d_schedule_count' => $upcomingScheduleCount,
                'pending_settlement_count' => $pendingSettlementCount,
                'pending_waitlist_count' => $pendingWaitlistCount,
                'pending_followup_count' => $pendingFollowupCount,
                'unread_message_count' => $unreadMessageCount,
                'total' => $pendingConfirmOrders + $todayServiceCount + $upcomingScheduleCount + $unreadMessageCount,
            ],
            'recent_orders' => self::getRecentOrders($staffId),
        ];
    }

    /**
     * @notes 获取服务人员可操作待办摘要（供 v2/me 聚合使用，口径与 dashboard 的 todo 一致）
     * 仅统计 5 项可操作待办，不含 upcoming_7d_schedule_count / unread_message_count。
     */
    public static function getTodoSummary(int $userId): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            return [
                'pending_confirm_orders' => 0,
                'today_service_count' => 0,
                'pending_settlement_count' => 0,
                'pending_waitlist_count' => 0,
                'pending_followup_count' => 0,
                'total' => 0,
            ];
        }

        $pendingConfirmOrders = self::getTodoCount($staffId);
        $todayServiceCount = self::getTodayServiceCount($staffId);
        $pendingSettlementCount = self::getPendingSettlementCount($staffId);
        $pendingWaitlistCount = self::getPendingWaitlistCount($staffId);
        $pendingFollowupCount = StaffCoupleWorkbenchService::countPendingFollowups($staffId);

        return [
            'pending_confirm_orders' => $pendingConfirmOrders,
            'today_service_count' => $todayServiceCount,
            'pending_settlement_count' => $pendingSettlementCount,
            'pending_waitlist_count' => $pendingWaitlistCount,
            'pending_followup_count' => $pendingFollowupCount,
            'total' => $pendingConfirmOrders + $todayServiceCount + $pendingSettlementCount
                + $pendingWaitlistCount + $pendingFollowupCount,
        ];
    }

    /**
     * @notes 获取订单状态统计
     */
    public static function orderStats(int $userId): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $query = self::buildStaffRelatedOrderBaseQuery($staffId);
        $statuses = [
            'pending_confirm' => Order::STATUS_PENDING_CONFIRM,
            'pending_pay' => Order::STATUS_PENDING_PAY,
            'paid' => Order::STATUS_PAID,
            'in_service' => Order::STATUS_IN_SERVICE,
            'completed' => Order::STATUS_COMPLETED,
            'reviewed' => Order::STATUS_REVIEWED,
            'cancelled' => Order::STATUS_CANCELLED,
            'paused' => Order::STATUS_PAUSED,
            'refunding' => Order::STATUS_REFUNDING,
            'refunded' => Order::STATUS_REFUNDED,
        ];

        $result = [
            'all' => (int) (clone $query)->count(),
        ];

        foreach ($statuses as $key => $status) {
            if ($status === Order::STATUS_PENDING_CONFIRM) {
                $result[$key] = (int) (clone self::buildPendingConfirmOrderBaseQuery($staffId))
                    ->count();
                continue;
            }

            $result[$key] = (int) (clone $query)
                ->where('order_status', $status)
                ->count();
        }

        return $result;
    }

    /**
     * @notes 获取服务人员待办数量
     */
    private static function getTodoCount(int $staffId): int
    {
        return (int) (clone self::buildPendingConfirmOrderBaseQuery($staffId))
            ->count();
    }

    /**
     * @notes 获取今日服务数量
     */
    private static function getTodayServiceCount(int $staffId): int
    {
        $today = date('Y-m-d');

        $platformCount = (int) Order::whereNotIn('order_status', [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED])
            ->whereIn('id', function ($subQuery) use ($staffId, $today) {
                self::applyStaffOrderIdSubQuery($subQuery, $staffId);
                $subQuery->where('service_date', $today)
                    ->where('item_status', '<>', OrderItem::STATUS_CANCELLED);
            })
            ->count();
        return $platformCount + (int)ManualSchedule::where('staff_id', $staffId)
            ->where('schedule_date', $today)->where('status', '<>', ManualSchedule::STATUS_CANCELLED)->count();
    }

    /**
     * @notes 获取未来7日安排数量
     */
    private static function getUpcomingScheduleCount(int $staffId): int
    {
        $startDate = date('Y-m-d', strtotime('+1 day'));
        $endDate = date('Y-m-d', strtotime('+7 day'));

        return (int) Schedule::where('staff_id', $staffId)
            ->whereBetween('schedule_date', [$startDate, $endDate])
            // LOCKED 是用户下单过程中的临时锁，未付款不占用档期，不计入未来安排
            ->whereIn('status', [Schedule::STATUS_BOOKED, Schedule::STATUS_RESERVED])
            ->count();
    }

    /**
     * @notes 获取待处理结算数量（待确认收款或待补平台）
     */
    private static function getPendingSettlementCount(int $staffId): int
    {
        return (int) StaffSettlement::where('staff_id', $staffId)
            ->where(function ($query) {
                $query->where('status', StaffSettlement::STATUS_TRANSFER_PROCESSING)
                    ->whereOr(function ($subQuery) {
                        $subQuery->where('status', '<>', StaffSettlement::STATUS_CANCELLED)
                            ->whereRaw('staff_due_platform_amount > staff_due_collected_amount');
                    });
            })
            ->count();
    }

    /**
     * @notes 获取待通知候补数量
     */
    private static function getPendingWaitlistCount(int $staffId): int
    {
        $now = time();
        return (int) Waitlist::where('staff_id', $staffId)
            ->where('notify_status', Waitlist::NOTIFY_STATUS_PENDING)
            ->where('schedule_date', '>=', date('Y-m-d'))
            ->where(function ($query) use ($now) {
                $query->where('expire_time', 0)
                    ->whereOr('expire_time', '>', $now);
            })
            ->count();
    }

    /**
     * @notes 获取最近订单
     */
    private static function getRecentOrders(int $staffId): array
    {
        $orderTable = (new Order())->getTable();

        $orders = self::buildStaffRelatedOrderBaseQuery($staffId)
            ->with([
                'items' => function ($query) use ($staffId) {
                    $query->field('id, order_id, staff_id, package_name, service_date, confirm_status, item_status, item_type')
                        ->where('staff_id', $staffId)
                        ->order('id', 'asc');
                },
            ])
            ->field([
                $orderTable . '.id' => 'id',
                $orderTable . '.order_sn',
                $orderTable . '.order_status',
                $orderTable . '.pay_status',
                $orderTable . '.confirm_deadline_time',
                $orderTable . '.pay_amount',
                $orderTable . '.service_address',
                $orderTable . '.contact_name',
                $orderTable . '.contact_mobile',
                $orderTable . '.create_time',
            ])
            ->order($orderTable . '.id', 'desc')
            ->limit(5)
            ->select()
            ->toArray();

        $result = [];
        foreach ($orders as $order) {
            $result[] = self::formatRecentOrder($order);
        }

        return $result;
    }

    /**
     * @notes 格式化最近订单
     */
    private static function formatRecentOrder(array $order): array
    {
        $items = $order['items'] ?? [];
        $displayItems = array_values(array_filter($items, [self::class, 'isStaffDisplayOrderItem']));
        $serviceDates = array_values(array_filter(array_map(function ($item) {
            return (string) ($item['service_date'] ?? '');
        }, $displayItems)));
        sort($serviceDates);

        $packageNames = array_values(array_filter(array_unique(array_map(function ($item) {
            return (string) ($item['package_name'] ?? '');
        }, $displayItems))));
        $orderStatus = (int) ($order['order_status'] ?? -1);
        $pendingConfirmCount = $orderStatus === Order::STATUS_PENDING_CONFIRM
            ? count(array_filter($displayItems, function ($item) {
                return (int) ($item['confirm_status'] ?? 0) === 0
                    && (int) ($item['item_status'] ?? OrderItem::STATUS_PENDING) !== OrderItem::STATUS_CANCELLED;
            }))
            : 0;

        return array_merge([
            'id' => (int) ($order['id'] ?? 0),
            'order_sn' => $order['order_sn'] ?? '',
            'service_date' => $serviceDates[0] ?? '',
            'contact_name' => $order['contact_name'] ?? '',
            'contact_mobile' => $order['contact_mobile'] ?? '',
            'service_address' => $order['service_address'] ?? '',
            'order_status' => (int) ($order['order_status'] ?? 0),
            'order_status_desc' => self::getStatusDesc((int) ($order['order_status'] ?? 0)),
            'pay_amount' => round((float) ($order['pay_amount'] ?? 0), 2),
            'item_count' => count($displayItems),
            'package_names' => $packageNames,
            'pending_confirm_count' => $pendingConfirmCount,
        ], Order::buildPayTimeoutSummaryFromState(
            (int) ($order['order_status'] ?? Order::STATUS_PENDING_PAY),
            (int) ($order['pay_deadline_time'] ?? 0),
            $order
        ), Order::buildConfirmTimeoutSummaryFromState(
            (int) ($order['order_status'] ?? Order::STATUS_PENDING_CONFIRM),
            (int) ($order['confirm_deadline_time'] ?? 0)
        ));
    }

    /**
     * @notes 构造服务人员订单基础查询
     */
    private static function buildStaffOrderQuery(int $staffId)
    {
        $orderTable = (new Order())->getTable();

        return OrderItem::alias('oi')
            ->join($orderTable . ' o', 'o.id = oi.order_id')
            ->where('oi.staff_id', $staffId)
            ->whereIn('oi.item_type', self::getStaffStatItemTypes())
            ->whereNull('o.delete_time');
    }

    /**
     * @notes 构造当前服务人员本人待确认订单查询
     */
    private static function buildPendingConfirmOrderQuery(int $staffId)
    {
        return self::buildStaffOrderQuery($staffId)
            ->where('oi.confirm_status', 0)
            ->where('oi.item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->where('o.order_status', Order::STATUS_PENDING_CONFIRM);
    }

    /**
     * @notes 构造服务人员相关订单基础查询
     */
    private static function buildStaffRelatedOrderBaseQuery(int $staffId)
    {
        return Order::whereIn('id', function ($subQuery) use ($staffId) {
            self::applyStaffOrderIdSubQuery($subQuery, $staffId);
        });
    }

    /**
     * @notes 构造服务人员可跟进订单基础查询
     */
    private static function buildStaffFollowableOrderBaseQuery(int $staffId)
    {
        return self::buildStaffRelatedOrderBaseQuery($staffId)
            ->whereNotIn('order_status', [
                Order::STATUS_CANCELLED,
                Order::STATUS_REFUNDED,
                Order::STATUS_USER_DELETED,
            ]);
    }

    /**
     * @notes 构造当前服务人员本人待确认订单基础查询
     */
    private static function buildPendingConfirmOrderBaseQuery(int $staffId)
    {
        return Order::where('order_status', Order::STATUS_PENDING_CONFIRM)
            ->whereIn('id', function ($subQuery) use ($staffId) {
                self::applyPendingConfirmOrderIdSubQuery($subQuery, $staffId);
            });
    }

    /**
     * @notes 服务人员订单统计只统计主服务与关联人员项
     */
    private static function getStaffStatItemTypes(): array
    {
        return [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF];
    }

    /**
     * @notes 当前服务人员相关订单ID子查询
     */
    private static function applyStaffOrderIdSubQuery($subQuery, int $staffId): void
    {
        $subQuery->name('order_item')
            ->where('staff_id', $staffId)
            ->whereIn('item_type', self::getStaffStatItemTypes())
            ->field('order_id');
    }

    /**
     * @notes 当前服务人员本人待确认订单ID子查询
     */
    private static function applyPendingConfirmOrderIdSubQuery($subQuery, int $staffId): void
    {
        self::applyStaffOrderIdSubQuery($subQuery, $staffId);
        $subQuery
            ->where('confirm_status', 0)
            ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->field('order_id');
    }

    /**
     * @notes 订单卡片与最近订单展示仅统计主服务与关联人员项
     */
    private static function isStaffDisplayOrderItem(array $item): bool
    {
        return in_array(
            (int) ($item['item_type'] ?? OrderItem::TYPE_SERVICE),
            self::getStaffStatItemTypes(),
            true
        );
    }

    /**
     * @notes 更新个人资料
     */
    public static function updateProfile(int $userId, array $params): array|bool
    {
        $staff = Staff::where('user_id', $userId)->find();
        if (!$staff) {
            self::setError('未绑定服务人员');
            return false;
        }

        // 内容安全闸门：只检测本次提交的字段，未提交字段沿用已过审旧值
        $guardTexts = [];
        foreach (['name', 'list_intro', 'profile', 'service_desc'] as $guardField) {
            if (array_key_exists($guardField, $params)) {
                $guardTexts[$guardField] = (string)($params[$guardField] ?? '');
            }
        }
        if (!StaffContentGuardService::textGuard($userId, $guardTexts, WechatSecurityService::SCENE_STAFF_WORK)) {
            self::setError(StaffContentGuardService::getError());
            return false;
        }

        // 手机号变更校验：新手机号与当前绑定手机号不同时，必须携带短信验证码
        // （场景 CHANGE_MOBILE_CAPTCHA，与用户端 bindMobile 同一模式，发送入口 /api/sms/sendCode）。
        // SmsDriver::verify 通过后会将该验证码原子置为已核销（is_verify=YES），天然防重放。
        // 手机号未变更时忽略 code，直接保存其余资料。
        $rawMobile = $staff->getData('mobile_full') ?: $staff->getData('mobile');
        $newMobile = trim((string)($params['mobile'] ?? ''));
        if ($newMobile !== '' && $newMobile !== (string)$rawMobile) {
            $code = trim((string)($params['code'] ?? ''));
            if ($code === '') {
                self::setError('变更手机号需提供短信验证码');
                return false;
            }
            if (!(new SmsDriver())->verify($newMobile, $code, NoticeEnum::CHANGE_MOBILE_CAPTCHA)) {
                self::setError('验证码错误或已过期');
                return false;
            }
        }

        Db::startTrans();
        try {
            $oldCategoryId = (int)$staff->category_id;
            $newCategoryId = (int)($params['category_id'] ?? $oldCategoryId);

            // 严格局部合并：只写本次提交的字段，未提交字段保持原值，
            // 避免资料页与展示页局部保存互相覆盖；avatar 未提交时不读 getter 默认图。
            $oldName = trim((string)($staff->getData('name') ?? ''));
            $newName = $oldName;
            $update = ['update_time' => time()];
            if (array_key_exists('name', $params)) {
                $submittedName = trim((string)$params['name']);
                if ($submittedName !== '') {
                    $update['name'] = $submittedName;
                    $newName = $submittedName;
                }
            }
            if (array_key_exists('avatar', $params)) {
                $update['avatar'] = (string)($params['avatar'] ?? '');
            }
            if (array_key_exists('mobile', $params)) {
                $mobile = trim((string)($params['mobile'] ?? ''));
                $update['mobile'] = $mobile;
                $update['mobile_full'] = $mobile;
            }
            if (array_key_exists('experience_years', $params)) {
                $update['experience_years'] = (int)($params['experience_years'] ?? 0);
            }
            if (array_key_exists('list_intro', $params)) {
                $update['list_intro'] = trim((string)($params['list_intro'] ?? ''));
            }
            if (array_key_exists('profile', $params)) {
                $update['profile'] = (string)($params['profile'] ?? '');
            }
            if (array_key_exists('service_desc', $params)) {
                $update['service_desc'] = (string)($params['service_desc'] ?? '');
            }
            if (array_key_exists('long_detail', $params)) {
                $update['long_detail'] = (string)($params['long_detail'] ?? '');
            }
            $update['category_id'] = $newCategoryId;

            $staff->save($update);

            // 姓名是后台账号展示名，改名后必须同步，避免工作台与后台两处不一致
            if ($newName !== $oldName && (int)$staff->admin_id > 0) {
                Admin::update(['id' => (int)$staff->admin_id, 'name' => $newName]);
            }

            $tagResult = [
                'action' => 'applied',
                'message' => '标签未变化',
            ];
            if (array_key_exists('tag_ids', $params)) {
                $tagResult = StaffTagReviewService::handleSelfTagUpdate(
                    (int) $staff->id,
                    $newCategoryId,
                    is_array($params['tag_ids']) ? $params['tag_ids'] : [],
                    [
                        'source' => \app\common\model\staff\StaffTagApply::SOURCE_UNIAPP,
                        'submit_user_id' => $userId,
                    ]
                );
            }

            if ($newCategoryId > 0 && $newCategoryId !== $oldCategoryId) {
                self::syncOwnedPackageCategory((int)$staff->id, $newCategoryId);
                self::syncOwnedAddonCategory((int)$staff->id, $newCategoryId);
            }

            if (
                array_key_exists('monthly_report_material', $params)
                && is_array($params['monthly_report_material'])
            ) {
                $materialParams = self::normalizeMonthlyReportMaterialParams(
                    $params['monthly_report_material']
                );
                if ($materialParams !== null) {
                    MonthlyReportService::materialSaveForStaff((int)$staff->id, $materialParams);
                }
            }

            Db::commit();
            return [
                'tag_action' => $tagResult['action'] ?? 'applied',
                'tag_message' => $tagResult['message'] ?? '',
            ];
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 归一化服务人员中心提交的月报素材
     */
    private static function normalizeMonthlyReportMaterialParams(array $material): ?array
    {
        $payload = [
            'status' => 1,
        ];
        $id = max(0, (int)($material['id'] ?? 0));
        if ($id > 0) {
            $payload['id'] = $id;
        }

        $hasEditableField = false;
        $hasNonEmptyValue = false;
        foreach (['avatar_photo', 'half_body_photo', 'english_name'] as $field) {
            if (!array_key_exists($field, $material)) {
                continue;
            }
            $value = trim((string)$material[$field]);
            $payload[$field] = $value;
            $hasEditableField = true;
            if ($value !== '') {
                $hasNonEmptyValue = true;
            }
        }

        if (!$hasEditableField) {
            return null;
        }
        if ($id <= 0 && !$hasNonEmptyValue) {
            return null;
        }

        return $payload;
    }

    /**
     * @notes 证书列表
     */
    public static function certificateLists(int $userId, array $params = []): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $statusField = self::getStaffCertificateStatusField();
        $query = StaffCertificate::where('staff_id', $staffId)
            ->order('id', 'desc');

        if (isset($params['verify_status']) && $params['verify_status'] !== '') {
            $query->where($statusField, (int) $params['verify_status']);
        }
        if (!empty($params['name'])) {
            $query->whereLike('name', '%' . trim((string) $params['name']) . '%');
        }
        if (!empty($params['sn'])) {
            $query->whereLike(
                self::getStaffCertificateNumberField(),
                '%' . trim((string) $params['sn']) . '%'
            );
        }

        $pageSize = (int) ($params['page_size'] ?? 10);
        if ($pageSize <= 0) {
            $pageSize = 10;
        }

        $result = $query->paginate($pageSize)->toArray();
        $result['data'] = array_map(
            fn($item) => self::formatStaffCertificateItem($item),
            is_array($result['data'] ?? null) ? $result['data'] : []
        );
        $result['summary'] = [
            'total' => (int) StaffCertificate::where('staff_id', $staffId)->count(),
            'pending_count' => (int) StaffCertificate::where('staff_id', $staffId)->where($statusField, StaffCertificate::VERIFY_PENDING)->count(),
            'approved_count' => (int) StaffCertificate::where('staff_id', $staffId)->where($statusField, StaffCertificate::VERIFY_PASS)->count(),
            'rejected_count' => (int) StaffCertificate::where('staff_id', $staffId)->where($statusField, StaffCertificate::VERIFY_REJECT)->count(),
        ];

        return $result;
    }

    /**
     * @notes 证书详情
     */
    public static function certificateDetail(int $userId, int $id): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $certificate = StaffCertificate::where('id', $id)->where('staff_id', $staffId)->find();
        if (!$certificate) {
            self::setError('证书不存在');
            return [];
        }

        return self::formatStaffCertificateItem($certificate->toArray(), $certificate);
    }

    /**
     * @notes 添加证书
     */
    public static function certificateAdd(int $userId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        // 内容安全闸门：本地敏感词禁止级命中直接拒绝；微信检测命中不阻断（落库待审，转人工复核）
        if (!StaffContentGuardService::textGuard($userId, [
            'name' => (string)($params['name'] ?? ''),
            'type' => (string)($params['type'] ?? ''),
            'sn' => (string)($params['sn'] ?? ''),
            'issue_org' => (string)($params['issue_org'] ?? ''),
        ], WechatSecurityService::SCENE_STAFF_CERT)) {
            self::setError(StaffContentGuardService::getError());
            return false;
        }

        $issueDate = self::normalizeNullableField($params, 'issue_date');
        $expireDate = self::normalizeNullableField($params, 'expire_date');

        $createData = [
            'staff_id' => $staffId,
            'name' => trim((string) $params['name']),
            'image' => trim((string) ($params['image'] ?? '')),
            'issue_org' => trim((string) ($params['issue_org'] ?? '')),
            'issue_date' => $issueDate['value'],
            'expire_date' => $expireDate['value'],
            'create_time' => time(),
            'update_time' => time(),
        ];
        if (self::hasStaffCertificateField('type')) {
            $createData['type'] = trim((string) ($params['type'] ?? ''));
        }
        if (self::hasStaffCertificateField('reject_reason')) {
            $createData['reject_reason'] = '';
        }
        self::syncStaffCertificateNumberPayload(
            $createData,
            trim((string) ($params['sn'] ?? ''))
        );
        self::syncStaffCertificateStatusPayload($createData, StaffCertificate::VERIFY_PENDING);

        StaffCertificate::create($createData);

        return true;
    }

    /**
     * @notes 编辑证书
     */
    public static function certificateEdit(int $userId, int $id, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $certificate = StaffCertificate::where('id', $id)->where('staff_id', $staffId)->find();
        if (!$certificate) {
            self::setError('证书不存在');
            return false;
        }

        // 内容安全闸门：仅检测本次提交的非空字段，未提交字段沿用已过审旧值
        if (!StaffContentGuardService::textGuard($userId, [
            'name' => (string)($params['name'] ?? ''),
            'type' => (string)($params['type'] ?? ''),
            'sn' => (string)($params['sn'] ?? ''),
            'issue_org' => (string)($params['issue_org'] ?? ''),
        ], WechatSecurityService::SCENE_STAFF_CERT)) {
            self::setError(StaffContentGuardService::getError());
            return false;
        }

        $issueDate = self::normalizeNullableField($params, 'issue_date');
        $expireDate = self::normalizeNullableField($params, 'expire_date');
        $certificateData = $certificate->getData();

        $saveData = [
            'name' => trim((string) ($params['name'] ?? ($certificateData['name'] ?? ''))),
            'image' => trim((string) ($params['image'] ?? ($certificateData['image'] ?? ''))),
            'issue_org' => trim((string) ($params['issue_org'] ?? ($certificateData['issue_org'] ?? ''))),
            'update_time' => time(),
        ];
        if (self::hasStaffCertificateField('type')) {
            $saveData['type'] = trim((string) ($params['type'] ?? ($certificateData['type'] ?? '')));
        }
        if (self::hasStaffCertificateField('reject_reason')) {
            $saveData['reject_reason'] = '';
        }
        self::syncStaffCertificateNumberPayload(
            $saveData,
            trim((string) (
                $params['sn']
                    ?? ($certificateData['sn'] ?? $certificateData['certificate_no'] ?? '')
            ))
        );
        self::syncStaffCertificateStatusPayload($saveData, StaffCertificate::VERIFY_PENDING);

        if ($issueDate['exists']) {
            $saveData['issue_date'] = $issueDate['value'];
        }
        if ($expireDate['exists']) {
            $saveData['expire_date'] = $expireDate['value'];
        }

        $certificate->save($saveData);

        return true;
    }

    /**
     * @notes 删除证书
     */
    public static function certificateDelete(int $userId, int $id): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $certificate = StaffCertificate::where('id', $id)->where('staff_id', $staffId)->find();
        if (!$certificate) {
            self::setError('证书不存在');
            return false;
        }

        $certificate->delete();
        return true;
    }

    /**
     * @notes 作品列表
     */
    public static function workLists(int $userId, array $params): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $query = StaffWork::where('staff_id', $staffId)
            ->order('sort', 'desc')
            ->order('id', 'desc');

        if (isset($params['is_show']) && $params['is_show'] !== '') {
            $query->where('is_show', (int) $params['is_show']);
        }

        if (isset($params['audit_status']) && $params['audit_status'] !== '') {
            $query->where('audit_status', (int) $params['audit_status']);
        } elseif (isset($params['is_show']) && $params['is_show'] !== '') {
            $query->where('audit_status', StaffWork::AUDIT_PASS);
        } else {
            $query->where('audit_status', '<>', StaffWork::AUDIT_REJECT);
        }

        $pageSize = (int) ($params['page_size'] ?? 10);
        if ($pageSize <= 0) {
            $pageSize = 10;
        }

        $result = $query->paginate($pageSize)->toArray();
        $result['summary'] = [
            'total' => (int) StaffWork::where('staff_id', $staffId)
                ->where('audit_status', '<>', StaffWork::AUDIT_REJECT)
                ->count(),
            'visible_count' => (int) StaffWork::where('staff_id', $staffId)
                ->where('audit_status', StaffWork::AUDIT_PASS)
                ->where('is_show', 1)
                ->count(),
            'hidden_count' => (int) StaffWork::where('staff_id', $staffId)
                ->where('audit_status', StaffWork::AUDIT_PASS)
                ->where('is_show', 0)
                ->count(),
            'pending_audit_count' => (int) StaffWork::where('staff_id', $staffId)->where('audit_status', StaffWork::AUDIT_PENDING)->count(),
            'rejected_count' => (int) StaffWork::where('staff_id', $staffId)->where('audit_status', StaffWork::AUDIT_REJECT)->count(),
        ];

        return $result;
    }

    /**
     * @notes 作品详情
     */
    public static function workDetail(int $userId, int $id): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $work = StaffWork::where('id', $id)->where('staff_id', $staffId)->find();
        if (!$work) {
            self::setError('作品不存在');
            return [];
        }

        return $work->toArray();
    }

    /**
     * @notes 添加作品
     */
    public static function workAdd(int $userId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        // 内容安全闸门：本地敏感词禁止级命中直接拒绝；微信检测命中不阻断（落库待审，转人工复核）
        if (!StaffContentGuardService::textGuard($userId, [
            'title' => (string)($params['title'] ?? ''),
            'description' => (string)($params['description'] ?? ''),
            'location' => (string)($params['location'] ?? ''),
        ], WechatSecurityService::SCENE_STAFF_WORK)) {
            self::setError(StaffContentGuardService::getError());
            return false;
        }

        $shootDate = self::normalizeNullableField($params, 'shoot_date');

        StaffWork::create([
            'staff_id' => $staffId,
            'title' => $params['title'],
            'cover' => $params['cover'] ?? '',
            'images' => $params['images'] ?? [],
            'video' => $params['video'] ?? '',
            'description' => $params['description'] ?? '',
            'shoot_date' => $shootDate['value'],
            'location' => $params['location'] ?? '',
            'sort' => $params['sort'] ?? 0,
            'is_show' => $params['is_show'] ?? 1,
            'audit_status' => StaffWork::AUDIT_PENDING,
            'create_time' => time(),
            'update_time' => time(),
        ]);

        return true;
    }

    /**
     * @notes 编辑作品
     */
    public static function workEdit(int $userId, int $id, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $work = StaffWork::where('id', $id)->where('staff_id', $staffId)->find();
        if (!$work) {
            self::setError('作品不存在');
            return false;
        }

        // 内容安全闸门：仅检测本次提交的非空字段，未提交字段沿用已过审旧值
        if (!StaffContentGuardService::textGuard($userId, [
            'title' => (string)($params['title'] ?? ''),
            'description' => (string)($params['description'] ?? ''),
            'location' => (string)($params['location'] ?? ''),
        ], WechatSecurityService::SCENE_STAFF_WORK)) {
            self::setError(StaffContentGuardService::getError());
            return false;
        }

        $shootDate = self::normalizeNullableField($params, 'shoot_date');

        $saveData = [
            'title' => $params['title'] ?? $work->title,
            'cover' => $params['cover'] ?? $work->cover,
            'images' => $params['images'] ?? $work->images,
            'video' => $params['video'] ?? $work->video,
            'description' => $params['description'] ?? $work->description,
            'location' => $params['location'] ?? $work->location,
            'sort' => $params['sort'] ?? $work->sort,
            'is_show' => $params['is_show'] ?? $work->is_show,
            'audit_status' => StaffWork::AUDIT_PENDING,
            'update_time' => time(),
        ];

        if ($shootDate['exists']) {
            $saveData['shoot_date'] = $shootDate['value'];
        }

        $work->save($saveData);

        return true;
    }

    /**
     * @notes 将可选字段中的空字符串统一归一化为null
     */
    private static function normalizeNullableField(array $params, string $field): array
    {
        if (!array_key_exists($field, $params)) {
            return ['exists' => false, 'value' => null];
        }

        $value = $params[$field];
        if ($value === null) {
            return ['exists' => true, 'value' => null];
        }

        if (is_string($value) && trim($value) === '') {
            return ['exists' => true, 'value' => null];
        }

        return ['exists' => true, 'value' => $value];
    }

    /**
     * @notes 统一格式化证书数据
     */
    private static function formatStaffCertificateItem(
        array $item,
        ?StaffCertificate $certificate = null
    ): array {
        $certificate = $certificate ?? new StaffCertificate();
        $certificate->data($item, true);

        $item['type'] = trim((string) ($item['type'] ?? ''));
        $item['sn'] = trim((string) ($item['sn'] ?? $item['certificate_no'] ?? ''));
        $item['reject_reason'] = trim((string) ($item['reject_reason'] ?? ''));
        $item['verify_status'] = (int) (
            $item['verify_status']
                ?? $item['audit_status']
                ?? StaffCertificate::VERIFY_PENDING
        );
        $item['verify_status_desc'] = $certificate->getAttr('verify_status_desc');
        $item['is_expired'] = $certificate->getAttr('is_expired');
        $item['image'] = $certificate->getAttr('image');

        return $item;
    }

    /**
     * @notes 获取证书字段集合
     */
    private static function getStaffCertificateFields(): array
    {
        if (self::$staffCertificateFields !== null) {
            return self::$staffCertificateFields;
        }

        $fields = Db::name('staff_certificate')->getFields();
        self::$staffCertificateFields = is_array($fields) ? $fields : [];

        return self::$staffCertificateFields;
    }

    /**
     * @notes 判断证书字段是否存在
     */
    private static function hasStaffCertificateField(string $field): bool
    {
        return isset(self::getStaffCertificateFields()[$field]);
    }

    /**
     * @notes 获取证书状态字段名（兼容 verify_status / audit_status）
     */
    private static function getStaffCertificateStatusField(): string
    {
        if (self::$staffCertificateStatusField !== null) {
            return self::$staffCertificateStatusField;
        }

        self::$staffCertificateStatusField = self::hasStaffCertificateField('verify_status')
            ? 'verify_status'
            : 'audit_status';

        return self::$staffCertificateStatusField;
    }

    /**
     * @notes 获取证书编号字段名（兼容 sn / certificate_no）
     */
    private static function getStaffCertificateNumberField(): string
    {
        return self::hasStaffCertificateField('sn') ? 'sn' : 'certificate_no';
    }

    /**
     * @notes 同步写入证书状态字段
     */
    private static function syncStaffCertificateStatusPayload(array &$payload, int $status): void
    {
        if (self::hasStaffCertificateField('verify_status')) {
            $payload['verify_status'] = $status;
        }
        if (self::hasStaffCertificateField('audit_status')) {
            $payload['audit_status'] = $status;
        }
    }

    /**
     * @notes 同步写入证书编号字段
     */
    private static function syncStaffCertificateNumberPayload(array &$payload, string $number): void
    {
        if (self::hasStaffCertificateField('sn')) {
            $payload['sn'] = $number;
        }
        if (self::hasStaffCertificateField('certificate_no')) {
            $payload['certificate_no'] = $number;
        }
    }

    /**
     * @notes 删除作品
     */
    public static function workDelete(int $userId, int $id): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $work = StaffWork::where('id', $id)->where('staff_id', $staffId)->find();
        if (!$work) {
            self::setError('作品不存在');
            return false;
        }

        $work->delete();
        return true;
    }

    /**
     * @notes 套餐列表
     */
    public static function packageLists(int $userId, array $params = []): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $query = ServicePackage::where('staff_id', $staffId)
            ->where('delete_time', null)
            ->field('id, staff_id, category_id, name, price, original_price, description, image, duration, sort, is_show, is_recommend')
            ->order('sort', 'desc')
            ->order('id', 'desc');

        if (isset($params['is_show']) && $params['is_show'] !== '') {
            $query->where('is_show', (int) $params['is_show']);
        }
        if (isset($params['is_recommend']) && $params['is_recommend'] !== '') {
            $query->where('is_recommend', (int) $params['is_recommend']);
        }

        $pageSize = (int) ($params['page_size'] ?? 10);
        if ($pageSize <= 0) {
            $pageSize = 10;
        }

        $result = $query->paginate($pageSize)->toArray();
        $rows = is_array($result['data'] ?? null) ? $result['data'] : [];
        $categoryName = self::resolveStaffCategoryName($staffId);
        foreach ($rows as &$row) {
            $row['category_name'] = $categoryName;
            if (!empty($row['image'])) {
                $row['image'] = \app\common\service\FileService::getFileUrl((string) $row['image']);
            }
        }
        unset($row);
        $rows = PackageRegionPriceService::attachRegionPrices($rows);
        $result['data'] = ServicePackageAddon::attachAddonIds($rows);
        return $result;
    }

    /**
     * @notes 套餐详情
     */
    public static function packageDetail(int $userId, int $packageId): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $package = ServicePackage::where('id', $packageId)
            ->where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->append(['category_name'])
            ->find();
        if (!$package) {
            self::setError('套餐不存在');
            return [];
        }

        $data = $package->toArray();
        if (!empty($data['image'])) {
            $data['image'] = \app\common\service\FileService::getFileUrl((string) $data['image']);
        }
        $list = PackageRegionPriceService::attachRegionPrices([$data]);
        $list = ServicePackageAddon::attachAddonIds($list);
        return $list[0] ?? $data;
    }

    /**
     * @notes 添加套餐
     */
    public static function packageAdd(int $userId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        try {
            Db::transaction(function () use ($staffId, $params) {
                $package = ServicePackage::create(self::buildPackagePayload($staffId, $params));
                PackageRegionPriceService::syncPackageRegionPrices(
                    (int) $package->id,
                    $staffId,
                    is_array($params['region_prices'] ?? null) ? $params['region_prices'] : []
                );
                ServicePackageAddon::syncPackageAddons(
                    (int) $package->id,
                    $staffId,
                    $params['addon_ids'] ?? []
                );
            });
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 更新套餐
     */
    public static function packageUpdate(int $userId, int $packageId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $package = ServicePackage::where('id', $packageId)
            ->where('staff_id', $staffId)
            ->find();
        if (!$package) {
            self::setError('套餐不存在');
            return false;
        }

        try {
            Db::transaction(function () use ($package, $staffId, $params) {
                $package->save(self::buildPackagePayload($staffId, $params, false, $package));
                if (array_key_exists('region_prices', $params) && is_array($params['region_prices'])) {
                    PackageRegionPriceService::syncPackageRegionPrices(
                        (int) $package->id,
                        $staffId,
                        $params['region_prices']
                    );
                }
                if (array_key_exists('addon_ids', $params)) {
                    ServicePackageAddon::syncPackageAddons(
                        (int) $package->id,
                        $staffId,
                        $params['addon_ids']
                    );
                }
            });
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 移除套餐
     */
    public static function packageRemove(int $userId, int $packageId): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $package = ServicePackage::where('id', $packageId)
            ->where('staff_id', $staffId)
            ->find();
        if (!$package) {
            self::setError('套餐不存在');
            return false;
        }

        $bookingCount = PackageBooking::where('package_id', $packageId)
            ->whereIn('status', [PackageBooking::STATUS_TEMP_LOCK, PackageBooking::STATUS_CONFIRMED])
            ->count();
        if ($bookingCount > 0) {
            self::setError('该套餐存在有效预订，无法删除');
            return false;
        }

        try {
            Db::transaction(function () use ($package, $packageId) {
                ServicePackageAddon::clearByPackageId($packageId);
                ServicePackageRegionPrice::where('package_id', $packageId)->delete();
                $package->delete();
            });
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 附加服务列表
     */
    public static function addonLists(int $userId, array $params = []): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $query = ServiceAddon::where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->field('id, staff_id, category_id, name, price, original_price, description, image, sort, is_show')
            ->order('sort', 'desc')
            ->order('id', 'desc');

        if (isset($params['is_show']) && $params['is_show'] !== '') {
            $query->where('is_show', (int) $params['is_show']);
        }

        $pageSize = (int) ($params['page_size'] ?? 10);
        if ($pageSize <= 0) {
            $pageSize = 10;
        }

        $result = $query->paginate($pageSize)->toArray();
        $rows = is_array($result['data'] ?? null) ? $result['data'] : [];
        $categoryName = self::resolveStaffCategoryName($staffId);
        foreach ($rows as &$row) {
            $row['category_name'] = $categoryName;
            if (!empty($row['image'])) {
                $row['image'] = \app\common\service\FileService::getFileUrl((string) $row['image']);
            }
        }
        unset($row);
        $result['data'] = ServicePackageAddon::attachPackageIds($rows);
        return $result;
    }

    /**
     * @notes 附加服务详情
     */
    public static function addonDetail(int $userId, int $addonId): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $addon = ServiceAddon::where('id', $addonId)
            ->where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->append(['category_name', 'package_ids'])
            ->find();
        if (!$addon) {
            self::setError('附加服务不存在');
            return [];
        }

        $data = $addon->toArray();
        if (!empty($data['image'])) {
            $data['image'] = \app\common\service\FileService::getFileUrl((string) $data['image']);
        }
        return $data;
    }

    /**
     * @notes 添加附加服务
     */
    public static function addonAdd(int $userId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        try {
            Db::transaction(function () use ($staffId, $params) {
                $addon = ServiceAddon::create(self::buildAddonPayload($staffId, $params));
                if (array_key_exists('package_ids', $params)) {
                    ServicePackageAddon::syncAddonPackages(
                        (int) $addon->id,
                        $staffId,
                        $params['package_ids']
                    );
                }
            });
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 更新附加服务
     */
    public static function addonUpdate(int $userId, int $addonId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $addon = ServiceAddon::where('id', $addonId)
            ->where('staff_id', $staffId)
            ->find();
        if (!$addon) {
            self::setError('附加服务不存在');
            return false;
        }

        try {
            Db::transaction(function () use ($addon, $staffId, $params) {
                $addon->save(self::buildAddonPayload($staffId, $params, false, $addon));
                if (array_key_exists('package_ids', $params)) {
                    ServicePackageAddon::syncAddonPackages(
                        (int) $addon->id,
                        $staffId,
                        $params['package_ids']
                    );
                }
            });
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除附加服务
     */
    public static function addonRemove(int $userId, int $addonId): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $addon = ServiceAddon::where('id', $addonId)
            ->where('staff_id', $staffId)
            ->find();
        if (!$addon) {
            self::setError('附加服务不存在');
            return false;
        }

        try {
            Db::transaction(function () use ($addon, $addonId) {
                ServicePackageAddon::clearByAddonId($addonId);
                $addon->delete();
            });
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 月度档期
     */
    public static function scheduleMonth(int $userId, int $year, int $month): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $schedules = Schedule::getMonthSchedule($staffId, $year, $month);
        $pendingServiceOrders = self::getSchedulePendingServiceOrders($staffId, $year, $month);
        $manualSchedules = ManualSchedule::where('staff_id', $staffId)
            ->whereBetween('schedule_date', [sprintf('%04d-%02d-01', $year, $month), date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $month)))])
            ->order('schedule_date', 'asc')->order('id', 'asc')->select()
            ->map(static fn(ManualSchedule $item) => $item->toDetail())->toArray();

        return [
            'year' => $year,
            'month' => $month,
            'schedules' => $schedules,
            'month_summary' => self::buildScheduleMonthSummary($year, $month, $schedules, $pendingServiceOrders),
            'pending_service_orders' => $pendingServiceOrders,
            'manual_schedules' => $manualSchedules,
            'pending_waitlist_count' => self::getPendingWaitlistCount($staffId),
        ];
    }

    private static function claimManualDate(int $staffId, string $date, int $manualId): Schedule
    {
        $schedule = Schedule::where('staff_id', $staffId)->where('schedule_date', $date)
            ->where('time_slot', Schedule::TIME_SLOT_ALL)->lock(true)->find();
        if ($schedule && ((int)$schedule->status !== Schedule::STATUS_AVAILABLE
            || (int)$schedule->order_id > 0 || (int)$schedule->manual_schedule_id > 0)) {
            throw new \RuntimeException('该日期已有订单、锁定、预留或不可用安排');
        }
        $payload = [
            'status' => Schedule::STATUS_RESERVED, 'order_id' => 0,
            'manual_schedule_id' => $manualId, 'lock_type' => Schedule::LOCK_TYPE_INTERNAL,
            'lock_reason' => 'manual_schedule:' . $manualId, 'lock_user_id' => 0,
            'lock_expire_time' => 0, 'remark' => '', 'update_time' => time(),
            'version' => $schedule ? (int)$schedule->version + 1 : 1,
        ];
        if ($schedule) {
            $changed = Schedule::where('id', $schedule->id)->where('version', $schedule->version)
                ->where('status', Schedule::STATUS_AVAILABLE)->where('order_id', 0)
                ->where('manual_schedule_id', 0)->update($payload);
            if (!$changed) {
                throw new \RuntimeException('该日期已被占用，请刷新后重试');
            }
            return Schedule::find((int)$schedule->id);
        }
        return Schedule::create(array_merge($payload, [
            'staff_id' => $staffId, 'schedule_date' => $date,
            'time_slot' => Schedule::TIME_SLOT_ALL, 'create_time' => time(),
        ]));
    }

    private static function manualPayload(array $params): array
    {
        $date = (string)($params['date'] ?? '');
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date || $date < date('Y-m-d')) {
            throw new \RuntimeException('请选择今天或未来的有效日期');
        }
        $payload = ['schedule_date' => $date];
        foreach (['service_name' => 100, 'customer_name' => 50, 'customer_mobile' => 20,
            'region_name' => 100, 'service_address' => 255, 'remark' => 255] as $field => $limit) {
            $value = trim((string)($params[$field] ?? ''));
            if (mb_strlen($value) > $limit) {
                throw new \RuntimeException('填写内容超过允许长度');
            }
            $payload[$field] = $value;
        }
        if ($payload['service_name'] === '') {
            throw new \RuntimeException('请输入服务名称');
        }
        return $payload;
    }

    private static function notifyManualRelease(int $staffId, string $date): void
    {
        if ($date < date('Y-m-d')) {
            return;
        }
        try {
            \app\common\model\schedule\Waitlist::notifyWaitlistUsers($staffId, $date, Schedule::TIME_SLOT_ALL);
        } catch (\Throwable $e) {
            \think\facade\Log::error('线下档期释放后候补通知失败：' . $e->getMessage());
        }
    }

    public static function manualScheduleAdd(int $userId, array $params): array|false
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        $requestId = trim((string)($params['request_id'] ?? ''));
        try {
            if ($requestId === '' || strlen($requestId) > 64) {
                throw new \RuntimeException('请求编号无效，请重新打开添加档期');
            }
            // 重试返回首次写入结果，即使服务日已过去也不重复创建。
            $existing = ManualSchedule::where('staff_id', $staffId)->where('request_id', $requestId)->find();
            if ($existing) {
                return $existing->toDetail();
            }
            $payload = self::manualPayload($params);
            return Db::transaction(function () use ($staffId, $requestId, $payload) {
                $manual = ManualSchedule::create(array_merge($payload, [
                    'staff_id' => $staffId, 'status' => ManualSchedule::STATUS_PENDING,
                    'source_month' => date('Y-m'), 'request_id' => $requestId, 'version' => 1,
                    'create_time' => time(), 'update_time' => time(),
                ]));
                $schedule = self::claimManualDate($staffId, $payload['schedule_date'], (int)$manual->id);
                $manual->save(['schedule_id' => (int)$schedule->id]);
                return $manual->toDetail();
            });
        } catch (\Throwable $e) {
            $existing = $requestId !== '' ? ManualSchedule::where('staff_id', $staffId)->where('request_id', $requestId)->find() : null;
            if ($existing) {
                return $existing->toDetail();
            }
            self::setError(str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), '1213')
                ? '该日期已被占用，请刷新后重试' : $e->getMessage());
            return false;
        }
    }

    public static function manualScheduleDetail(int $userId, int $id): array|false
    {
        $staffId = self::getStaffId($userId);
        $item = $staffId > 0 ? ManualSchedule::where('id', $id)->where('staff_id', $staffId)->find() : null;
        if (!$item) {
            self::setError('线下档期不存在或无权限查看');
            return false;
        }
        return $item->toDetail();
    }

    public static function manualScheduleEdit(int $userId, array $params): array|false
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        $releasedDate = '';
        try {
            $payload = self::manualPayload($params);
            $result = Db::transaction(function () use ($staffId, $params, $payload, &$releasedDate) {
                $item = ManualSchedule::where('id', (int)$params['manual_schedule_id'])
                    ->where('staff_id', $staffId)->lock(true)->find();
                if (!$item) {
                    throw new \RuntimeException('线下档期不存在');
                }
                if ((int)$item->version !== (int)($params['version'] ?? 0)) {
                    throw new \RuntimeException('档期已更新，请刷新后重试');
                }
                if ((int)$item->status !== ManualSchedule::STATUS_PENDING || (string)$item->schedule_date < date('Y-m-d')) {
                    throw new \RuntimeException('历史日期或当前状态不可修改');
                }
                $old = Schedule::where('id', (int)$item->schedule_id)->lock(true)->find();
                if (!$old || (int)$old->staff_id !== $staffId || (int)$old->manual_schedule_id !== (int)$item->id
                    || (int)$old->status !== Schedule::STATUS_RESERVED || (int)$old->order_id > 0) {
                    throw new \RuntimeException('档期占用归属已变化，请刷新后重试');
                }
                if ($payload['schedule_date'] !== (string)$item->schedule_date) {
                    $new = self::claimManualDate($staffId, $payload['schedule_date'], (int)$item->id);
                    if (!Schedule::releaseManual((int)$old->id, (int)$item->id, (int)$old->version)) {
                        throw new \RuntimeException('原档期已更新，请刷新后重试');
                    }
                    $releasedDate = (string)$item->schedule_date;
                    $item->schedule_id = (int)$new->id;
                }
                $item->save(array_merge($payload, ['version' => (int)$item->version + 1, 'update_time' => time()]));
                StaffScheduleConfirmLetterService::markOutdatedByManualId((int)$item->id);
                return $item->toDetail();
            });
            if ($releasedDate !== '') {
                self::notifyManualRelease($staffId, $releasedDate);
            }
            return $result;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function manualScheduleCancel(int $userId, array $params): bool
    {
        return self::manualScheduleAction($userId, $params, ManualSchedule::STATUS_CANCELLED);
    }

    public static function manualScheduleComplete(int $userId, array $params): bool
    {
        return self::manualScheduleAction($userId, $params, ManualSchedule::STATUS_COMPLETED);
    }

    private static function manualScheduleAction(int $userId, array $params, int $status): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        $releasedDate = '';
        try {
            Db::transaction(function () use ($staffId, $params, $status, &$releasedDate) {
                $item = ManualSchedule::where('id', (int)$params['manual_schedule_id'])
                    ->where('staff_id', $staffId)->lock(true)->find();
                if (!$item) {
                    throw new \RuntimeException('线下档期不存在');
                }
                if ((int)$item->status === $status) {
                    return;
                }
                if ((int)$item->status !== ManualSchedule::STATUS_PENDING) {
                    throw new \RuntimeException('当前档期状态不可执行该操作');
                }
                if ((int)$item->version !== (int)($params['version'] ?? 0)) {
                    throw new \RuntimeException('档期已更新，请刷新后重试');
                }
                if ($status === ManualSchedule::STATUS_COMPLETED && (string)$item->schedule_date > date('Y-m-d')) {
                    throw new \RuntimeException('服务日期未到，暂不能完成');
                }
                $schedule = Schedule::where('id', (int)$item->schedule_id)->lock(true)->find();
                if (!$schedule || (int)$schedule->staff_id !== $staffId || (int)$schedule->manual_schedule_id !== (int)$item->id
                    || (int)$schedule->status !== Schedule::STATUS_RESERVED || (int)$schedule->order_id > 0) {
                    throw new \RuntimeException('档期占用归属已变化，请刷新后重试');
                }
                if ($status === ManualSchedule::STATUS_CANCELLED) {
                    if (!Schedule::releaseManual((int)$schedule->id, (int)$item->id, (int)$schedule->version)) {
                        throw new \RuntimeException('档期占用已变化，请刷新后重试');
                    }
                    $releasedDate = (string)$item->schedule_date;
                    StaffScheduleConfirmLetterService::markOutdatedByManualId((int)$item->id);
                }
                // 已完成仍保留当天占用，避免同日再次接单。
                $item->save([
                    'status' => $status, 'version' => (int)$item->version + 1, 'update_time' => time(),
                    $status === ManualSchedule::STATUS_COMPLETED ? 'complete_time' : 'cancel_time' => time(),
                ]);
            });
            if ($releasedDate !== '') {
                self::notifyManualRelease($staffId, $releasedDate);
            }
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取档期页待履约订单摘要
     * @param int $staffId
     * @param int $year
     * @param int $month
     * @return array
     */
    private static function getSchedulePendingServiceOrders(int $staffId, int $year, int $month): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $rows = OrderItem::alias('oi')
            ->leftJoin('order o', 'o.id = oi.order_id')
            ->field([
                'oi.order_id',
                'oi.package_name',
                'oi.service_date',
                'o.order_sn',
                'o.order_status',
                'o.contact_name',
                'o.contact_mobile',
                'o.service_address',
            ])
            ->where('oi.staff_id', $staffId)
            ->whereIn('oi.item_type', self::getStaffStatItemTypes())
            ->where('oi.item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->whereBetween('oi.service_date', [$startDate, $endDate])
            ->where('o.order_status', Order::STATUS_PENDING_SERVICE)
            ->whereNull('o.delete_time')
            ->order('oi.service_date', 'asc')
            ->order('oi.order_id', 'asc')
            ->select()
            ->toArray();

        $grouped = [];
        foreach ($rows as $row) {
            $groupKey = (string)$row['service_date'] . '#' . (int)$row['order_id'];
            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'order_id' => (int)$row['order_id'],
                    'order_sn' => (string)($row['order_sn'] ?? ''),
                    'service_date' => (string)($row['service_date'] ?? ''),
                    'contact_name' => (string)($row['contact_name'] ?? ''),
                    'contact_mobile' => (string)($row['contact_mobile'] ?? ''),
                    'service_address' => (string)($row['service_address'] ?? ''),
                    'package_names' => [],
                    'item_count' => 0,
                    'order_status' => (int)($row['order_status'] ?? Order::STATUS_PENDING_SERVICE),
                    'order_status_desc' => self::getStatusDesc((int)($row['order_status'] ?? Order::STATUS_PENDING_SERVICE)),
                    'can_staff_start' => (int)($row['order_status'] ?? Order::STATUS_PENDING_SERVICE) === Order::STATUS_PENDING_SERVICE ? 1 : 0,
                ];
            }

            $packageName = trim((string)($row['package_name'] ?? ''));
            if ($packageName !== '' && !in_array($packageName, $grouped[$groupKey]['package_names'], true)) {
                $grouped[$groupKey]['package_names'][] = $packageName;
            }
            $grouped[$groupKey]['item_count']++;
        }

        $result = [];
        foreach ($grouped as $item) {
            $packageNames = $item['package_names'];
            $item['package_summary'] = empty($packageNames)
                ? '待确认服务内容'
                : implode('、', array_slice($packageNames, 0, 2))
                    . (count($packageNames) > 2 ? ' 等 ' . count($packageNames) . ' 项' : '');
            unset($item['package_names']);
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @notes 构建档期页月度汇总
     * @param int $year
     * @param int $month
     * @param array $schedules
     * @param array $pendingServiceOrders
     * @return array
     */
    private static function buildScheduleMonthSummary(
        int $year,
        int $month,
        array $schedules,
        array $pendingServiceOrders
    ): array {
        $today = date('Y-m-d');
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        $pendingServiceDates = array_flip(array_column($pendingServiceOrders, 'service_date'));

        $availableDays = 0;
        $occupiedDays = 0;
        $unavailableDays = 0;

        $currentDate = $startDate;
        while ($currentDate <= $endDate) {
            if ($currentDate < $today) {
                $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
                continue;
            }

            $status = (int)($schedules[$currentDate][Schedule::TIME_SLOT_ALL]['status'] ?? Schedule::STATUS_AVAILABLE);
            if (isset($pendingServiceDates[$currentDate])) {
                $occupiedDays++;
            } elseif ($status === Schedule::STATUS_UNAVAILABLE) {
                $unavailableDays++;
            } elseif (in_array($status, [Schedule::STATUS_BOOKED, Schedule::STATUS_RESERVED], true)) {
                // LOCKED 为下单中的临时锁，未付款不占用档期，不计入已预约
                $occupiedDays++;
            } else {
                $availableDays++;
            }

            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        return [
            'available_days' => $availableDays,
            'occupied_days' => $occupiedDays,
            'unavailable_days' => $unavailableDays,
            'pending_service_count' => count($pendingServiceOrders),
        ];
    }

    /**
     * @notes 设置档期状态
     */
    public static function scheduleSetStatus(int $userId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        $date = (string)$params['date'];
        $status = (int)$params['status'];
        $version = (int)($params['version'] ?? -1);
        if ($staffId <= 0 || $date < date('Y-m-d') || !in_array($status, [0, 1], true) || $version < 0) {
            self::setError('档期参数无效，请刷新后重试');
            return false;
        }
        try {
            Db::transaction(function () use ($staffId, $date, $status, $version, $params) {
                $schedule = Schedule::where('staff_id', $staffId)->where('schedule_date', $date)
                    ->where('time_slot', Schedule::TIME_SLOT_ALL)->lock(true)->find();
                $payload = ['status' => $status, 'remark' => trim((string)($params['remark'] ?? '')),
                    'version' => $version + 1, 'update_time' => time()];
                if (!$schedule) {
                    if ($version !== 0) {
                        throw new \RuntimeException('档期已变化，请刷新后重试');
                    }
                    Schedule::create(array_merge($payload, ['staff_id' => $staffId, 'schedule_date' => $date,
                        'time_slot' => Schedule::TIME_SLOT_ALL, 'create_time' => time()]));
                } else {
                    $changed = Schedule::where('id', $schedule->id)->where('version', $version)
                        ->whereIn('status', [Schedule::STATUS_AVAILABLE, Schedule::STATUS_UNAVAILABLE])
                        ->where('order_id', 0)->where('manual_schedule_id', 0)->update($payload);
                    if (!$changed) {
                        throw new \RuntimeException('档期已变化或存在占用，请刷新后重试');
                    }
                }
            });
            if ($status === Schedule::STATUS_AVAILABLE) {
                self::notifyManualRelease($staffId, $date);
            }
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 全年已预约档期
     */
    public static function scheduleBookedYear(int $userId, array $params): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }
        $year = (int)($params['year'] ?? date('Y'));
        $pageSize = max(1, min(50, (int)($params['page_size'] ?? 30)));
        $page = max(1, (int)($params['page_no'] ?? 1));
        $query = Schedule::alias('s')
            ->leftJoin('order o', 'o.id = s.order_id')
            ->leftJoin('manual_schedule m', 'm.id = s.manual_schedule_id AND m.staff_id = s.staff_id')
            ->where('s.staff_id', $staffId)->where('s.time_slot', Schedule::TIME_SLOT_ALL)
            ->whereBetween('s.schedule_date', [sprintf('%04d-01-01', $year), sprintf('%04d-12-31', $year)])
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->where('s.status', Schedule::STATUS_BOOKED)->where('s.order_id', '>', 0)
                        ->whereNotIn('o.order_status', [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED, Order::STATUS_USER_DELETED])
                        ->whereNull('o.delete_time');
                })->whereOr(function ($q) {
                    $q->where('s.status', Schedule::STATUS_RESERVED)
                        ->where('s.manual_schedule_id', '>', 0)->where('s.order_id', 0)
                        ->whereIn('m.status', [ManualSchedule::STATUS_PENDING, ManualSchedule::STATUS_COMPLETED]);
                });
            });
        // 全年标记仅传日期，详细条目严格分页。
        $dates = (clone $query)->order('s.schedule_date', 'asc')->column('s.schedule_date');
        $total = count($dates);
        $rows = (clone $query)->field('s.id,s.order_id,s.manual_schedule_id,s.schedule_date AS service_date,m.status AS manual_status')
            ->order('s.schedule_date', 'asc')->order('s.id', 'asc')
            ->page($page, $pageSize)->select()->toArray();
        foreach ($rows as &$row) {
            $row['source'] = (int)$row['manual_schedule_id'] > 0 ? 'manual' : 'platform';
        }
        unset($row);
        return ['year' => $year, 'data' => $rows, 'total' => $total, 'current_page' => $page,
            'last_page' => max(1, (int)ceil($total / $pageSize)), 'per_page' => $pageSize,
            'date_markers' => $dates];
    }

    /**
     * @notes 订单列表
     */
    public static function orderLists(int $userId, array $params): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $status = isset($params['status']) && $params['status'] !== ''
            ? (int) $params['status']
            : null;
        $pageSize = (int) ($params['page_size'] ?? 10);
        if ($pageSize <= 0) {
            $pageSize = 10;
        }

        if ($status === Order::STATUS_PENDING_CONFIRM) {
            $query = Order::where('order_status', Order::STATUS_PENDING_CONFIRM)
                ->whereIn('id', function ($subQuery) use ($staffId) {
                    self::applyPendingConfirmOrderIdSubQuery($subQuery, $staffId);
                });
        } else {
            $query = self::buildStaffRelatedOrderBaseQuery($staffId);
        }

        if ($status !== null && $status !== Order::STATUS_PENDING_CONFIRM) {
            $query->where('order_status', $status);
        }

        if (!empty($params['keyword'])) {
            $query->where('order_sn|contact_name|contact_mobile', 'like', '%' . $params['keyword'] . '%');
        }

        $list = $query->with([
                'items' => function ($q) use ($staffId) {
                        $q->field('id, order_id, staff_id, staff_name, package_name, package_description, service_date, item_status, confirm_status, schedule_id, item_type, item_meta, price, quantity, subtotal')
                        ->where('staff_id', $staffId)
                        ->with(['staff' => function ($staffQuery) {
                            $staffQuery->field('id, name, avatar');
                        }, 'addons' => function ($addonQuery) {
                            $addonQuery->field('id, order_item_id, addon_id, addon_name, price, quantity, subtotal');
                        }]);
                },
            ])
            ->order('id', 'desc')
            ->paginate($pageSize)
            ->toArray();

        foreach ($list['data'] as &$item) {
            $item = self::applyStaffOrderAmounts($item);
            $item['order_status_desc'] = self::getStatusDesc((int) $item['order_status']);
            $item['pay_status_desc'] = self::getPayStatusDesc((int) $item['pay_status']);
            $canManageWholeOrder = Order::isWholeOrderOwnedByStaff((int)($item['id'] ?? 0), $staffId);
            $item['can_staff_confirm'] = self::canStaffConfirmOrder((int)($item['order_status'] ?? -1), (array)($item['items'] ?? []));
            $item['can_staff_start'] = (int)($item['order_status'] ?? -1) === Order::STATUS_PENDING_SERVICE
                && $canManageWholeOrder
                ? 1
                : 0;
            $item['can_staff_complete'] = (int)($item['order_status'] ?? -1) === Order::STATUS_IN_SERVICE
                && $canManageWholeOrder
                && Order::canStaffCompleteService();
            $item = array_merge($item, Order::buildPayTimeoutSummaryFromState(
                (int) ($item['order_status'] ?? Order::STATUS_PENDING_PAY),
                (int) ($item['pay_deadline_time'] ?? 0),
                $item
            ));
            $item = array_merge($item, Order::buildConfirmTimeoutSummaryFromState(
                (int) ($item['order_status'] ?? Order::STATUS_PENDING_CONFIRM),
                (int) ($item['confirm_deadline_time'] ?? 0)
            ));
        }
        unset($item);

        return $list;
    }

    /**
     * @notes 订单详情
     */
    public static function orderDetail(int $userId, int $orderId): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $order = Order::with([
                'items' => function ($q) use ($staffId) {
                        $q->field('id, order_id, staff_id, staff_name, package_name, package_description, service_date, item_status, confirm_status, schedule_id, item_type, item_meta, price, quantity, subtotal')
                        ->where('staff_id', $staffId)
                        ->with(['staff' => function ($staffQuery) {
                            $staffQuery->field('id, name, avatar');
                        }, 'addons' => function ($addonQuery) {
                            $addonQuery->field('id, order_item_id, addon_id, addon_name, price, quantity, subtotal');
                        }]);
                },
            ])
            ->hasWhere('items', ['staff_id' => $staffId])
            ->find($orderId);

        if (!$order) {
            self::setError('订单不存在');
            return [];
        }

        $data = $order->toArray();
        $data = self::applyStaffOrderAmounts($data);
        $data['order_status_desc'] = self::getStatusDesc((int) $order->order_status);
        $data['pay_status_desc'] = self::getPayStatusDesc((int) $order->pay_status);
        $data['pay_type_desc'] = self::getPayTypeDesc((int) $order->pay_type);
        $data = array_merge($data, Order::buildPaymentSummaryFromState($data));
        $data['receipt_pending'] = \app\common\service\OrderReceiptService::pending((int)$order->id);
        $data = array_merge($data, Order::getPayTimeoutSummary($order));
        $data = array_merge($data, Order::getConfirmTimeoutSummary($order));
        $canManageWholeOrder = Order::isWholeOrderOwnedByStaff((int)$order->id, $staffId);
        $data['is_whole_order'] = $canManageWholeOrder;

        // 本单轻量全量项：用于判定当前服务人员的角色（主服务 / 协作）与协作项是否仍生效
        $allOrderItems = OrderItem::where('order_id', (int)$order->id)
            ->field('id, staff_id, item_type, item_status')
            ->select()
            ->toArray();
        $staffRole = self::resolveStaffOrderRole($allOrderItems, $staffId);
        $hasActiveCollaboratorItem = false;
        foreach ($allOrderItems as $allOrderItem) {
            if (
                (int)($allOrderItem['staff_id'] ?? 0) === $staffId
                && (int)($allOrderItem['item_type'] ?? 0) === OrderItem::TYPE_RELATED_STAFF
                && (int)($allOrderItem['item_status'] ?? 0) !== OrderItem::STATUS_CANCELLED
            ) {
                $hasActiveCollaboratorItem = true;
                break;
            }
        }
        $data['has_active_collaborator_item'] = $hasActiveCollaboratorItem;
        $data['staff_role'] = $staffRole;

        // 分期金额明细：统一基于 applyStaffOrderAmounts 后的服务人员视角金额计算，
        // 全款模式把应付全款视作尾款口径，保证 deposit/balance 与 pay/paid/unpaid 自洽。
        $depositAmount = round((float)($data['deposit_amount'] ?? 0), 2);
        $payAmount = round((float)($data['pay_amount'] ?? 0), 2);
        $staffPaidAmount = round((float)($data['paid_amount'] ?? 0), 2);
        $depositPaidAmount = $depositAmount > 0
            ? round(min($staffPaidAmount, $depositAmount), 2)
            : 0.0;
        $depositUnpaidAmount = round(max(0, $depositAmount - $depositPaidAmount), 2);
        if ($depositAmount > 0) {
            $balanceAmount = round((float)($data['balance_amount'] ?? 0), 2);
            $balancePaidAmount = round(min(max($staffPaidAmount - $depositPaidAmount, 0), $balanceAmount), 2);
        } else {
            $balanceAmount = $payAmount;
            $balancePaidAmount = round(min($staffPaidAmount, $payAmount), 2);
        }
        $balanceUnpaidAmount = round(max(0, $balanceAmount - $balancePaidAmount), 2);
        $data['balance_amount'] = $balanceAmount;
        $data['deposit_paid_amount'] = $depositPaidAmount;
        $data['deposit_unpaid_amount'] = $depositUnpaidAmount;
        $data['balance_paid_amount'] = $balancePaidAmount;
        $data['balance_unpaid_amount'] = $balanceUnpaidAmount;

        // 支付流水：仅整单归属该服务人员时下发，避免拼单场景泄露其他服务人员收款明细。
        $data['payments'] = [];
        if ($canManageWholeOrder) {
            $paymentRows = Payment::where('order_id', (int)$order->id)
                ->where('pay_status', Payment::STATUS_PAID)
                ->order('pay_time', 'asc')
                ->order('id', 'asc')
                ->select();
            foreach ($paymentRows as $payment) {
                $payTime = (int)($payment->pay_time ?? 0);
                $data['payments'][] = [
                    'id' => (int)$payment->id,
                    'payment_sn' => (string)($payment->payment_sn ?? ''),
                    'pay_type' => (int)($payment->pay_type ?? 0),
                    'pay_type_desc' => (string)($payment['pay_type_desc'] ?? ''),
                    'pay_way' => (int)($payment->pay_way ?? 0),
                    'pay_way_desc' => (string)($payment['pay_way_desc'] ?? ''),
                    'pay_amount' => round((float)($payment->pay_amount ?? 0), 2),
                    'pay_time' => $payTime,
                    'pay_time_text' => $payTime > 0 ? date('Y-m-d H:i:s', $payTime) : '',
                    'remark' => (string)($payment->remark ?? ''),
                ];
            }
        }

        $cancelTime = (int)($order->cancel_time ?? 0);
        $data['cancel_reason'] = (string)($order->cancel_reason ?? '');
        $data['cancel_time'] = $cancelTime;
        $data['cancel_time_text'] = $cancelTime > 0 ? date('Y-m-d H:i:s', $cancelTime) : '';

        // 协作人员本人协作项已取消且整单未取消时，回显其取消原因与时间
        if (
            $staffRole === 'collaborator'
            && !$hasActiveCollaboratorItem
            && (int)$order->order_status !== Order::STATUS_CANCELLED
        ) {
            $cancelledCollaboratorItems = OrderItem::where('order_id', (int)$order->id)
                ->where('staff_id', $staffId)
                ->where('item_type', OrderItem::TYPE_RELATED_STAFF)
                ->where('item_status', OrderItem::STATUS_CANCELLED)
                ->order('update_time', 'desc')
                ->select();
            foreach ($cancelledCollaboratorItems as $cancelledCollaboratorItem) {
                $cancelledMeta = $cancelledCollaboratorItem->item_meta;
                if (!is_array($cancelledMeta)) {
                    $cancelledMeta = json_decode((string)$cancelledMeta, true) ?: [];
                }
                $metaCancelTime = (int)($cancelledMeta['cancel_time'] ?? 0);
                $metaCancelReason = trim((string)($cancelledMeta['cancel_reason'] ?? ''));
                if ($metaCancelTime <= 0 && $metaCancelReason === '') {
                    continue;
                }
                $data['cancel_reason'] = $metaCancelReason;
                $data['cancel_time'] = $metaCancelTime;
                $data['cancel_time_text'] = $metaCancelTime > 0 ? date('Y-m-d H:i:s', $metaCancelTime) : '';
                $data['collaborator_cancelled'] = true;
                break;
            }
        }

        $data = array_merge($data, self::resolveStaffCancelCapability($order, $data, $canManageWholeOrder, $staffRole));

        $data['source_desc'] = (int)$order->source === Order::SOURCE_STAFF ? '服务人员录入' : ((int)$order->source === Order::SOURCE_ADMIN ? '后台' : '小程序');
        $data['receipt_requests'] = $canManageWholeOrder ? \app\common\service\OrderReceiptService::history((int)$order->id) : [];
        $data['can_submit_receipt'] = $canManageWholeOrder && (int)$order->order_status === Order::STATUS_PENDING_PAY
            && !$order->isOfflineVoucherPending();
        $data['receipt_phases'] = [];
        if ($data['can_submit_receipt']) {
            foreach ([1 => '定金', 2 => '尾款', 3 => '全款'] as $phase => $label) {
                try {
                    $amount = \app\common\service\OrderReceiptService::phaseAmount($order, $phase);
                    $data['receipt_phases'][] = ['value' => $phase, 'label' => $label, 'amount' => $amount];
                } catch (\Throwable $e) { }
            }
        }
        $data['can_staff_confirm'] = self::canStaffConfirmOrder((int)$order->order_status, (array)($data['items'] ?? []));
        $data['can_staff_start'] = (int)$order->order_status === Order::STATUS_PENDING_SERVICE
            && $canManageWholeOrder
            ? 1
            : 0;
        $data['can_staff_complete'] = (int)$order->order_status === Order::STATUS_IN_SERVICE
            && $canManageWholeOrder
            && Order::canStaffCompleteService();
        $data['changes'] = self::getOrderChangesForStaff((int)$order->id);
        $data['has_pending_change'] = OrderChange::hasPendingChange((int)$order->id) ? 1 : 0;

        return $data;
    }

    private static function canStaffConfirmOrder(int $orderStatus, array $staffItems): int
    {
        if ($orderStatus !== Order::STATUS_PENDING_CONFIRM) {
            return 0;
        }
        foreach ($staffItems as $orderItem) {
            if ((int)($orderItem['confirm_status'] ?? 0) === 0) {
                return 1;
            }
        }
        return 0;
    }

    /**
     * @notes 确认订单
     */
    public static function orderConfirm(int $userId, int $orderId): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $shouldNotifyUser = false;

        $result = Db::transaction(function () use ($userId, $staffId, $orderId, &$shouldNotifyUser) {
            // 加锁查询，防止并发确认
            $order = Order::with(['items'])
                ->where('id', $orderId)
                ->lock(true)
                ->find();

            if (!$order) {
                self::setError('订单不存在');
                return false;
            }

            if (is_array($order->items ?? null)) {
                $allItems = $order->items;
            } elseif (is_object($order->items ?? null) && method_exists($order->items, 'toArray')) {
                $allItems = $order->items->toArray();
            } else {
                $allItems = [];
            }
            $staffItems = array_values(array_filter($allItems, function ($item) use ($staffId) {
                return (int) ($item['staff_id'] ?? 0) === $staffId
                    && (int) ($item['item_status'] ?? 0) !== OrderItem::STATUS_CANCELLED;
            }));
            if (empty($staffItems)) {
                self::setError('无权确认该订单');
                return false;
            }

            if ((int) $order->order_status !== Order::STATUS_PENDING_CONFIRM) {
                self::setError('当前订单状态不可确认');
                return false;
            }

            $pendingItems = array_values(array_filter($staffItems, function ($item) {
                return (int) ($item['confirm_status'] ?? 0) === 0;
            }));
            if (empty($pendingItems)) {
                self::setError('已确认，无需重复操作');
                return false;
            }

            // 确认前校验：该服务人员当日不能已有其他已付款订单
            try {
                ScheduleOccupancyService::assertOrderItemsFree($orderId, '确认订单', false, $staffId);
            } catch (\app\common\exception\ScheduleOccupancyException $e) {
                self::setError($e->getMessage());
                return false;
            }

            // 标记订单项确认，首笔收款成功后再统一锁定档期
            foreach ($pendingItems as $item) {
                OrderItem::where('id', $item['id'])->update([
                    'confirm_status' => 1,
                    'update_time' => time(),
                ]);
            }

            // 全部订单项均已确认则推进订单状态
            $remain = OrderItem::where('order_id', $order->id)
                ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                ->where('confirm_status', 0)
                ->count();

            if ($remain === 0) {
                $beforeStatus = (int) $order->order_status;
                $order->order_status = Order::STATUS_PENDING_PAY;
                $order->confirm_deadline_time = 0;
                $order->update_time = time();
                $order->save();
                Order::syncPendingPayDeadline($order);
                $shouldNotifyUser = true;

                OrderLog::addLog(
                    $order->id,
                    OrderLog::OPERATOR_USER,
                    $userId,
                    'confirm',
                    $beforeStatus,
                    Order::STATUS_PENDING_PAY,
                    '全部服务人员已确认，进入待支付'
                );
            } else {
                OrderLog::addLog(
                    $order->id,
                    OrderLog::OPERATOR_USER,
                    $userId,
                    'confirm_item',
                    (int) $order->order_status,
                    (int) $order->order_status,
                    '服务人员确认订单项（剩余' . $remain . '项待确认）'
                );
            }

            return true;
        });

        if ($result && $shouldNotifyUser) {
            OrderNotificationService::notifyUserOnOrderConfirmed($orderId);
        }

        return $result;
    }

    /**
     * @notes 完成服务
     */
    public static function orderComplete(int $userId, int $orderId): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $order = Order::with(['items'])->find($orderId);
        if (!$order) {
            self::setError('订单不存在');
            return false;
        }

        if (!Order::isWholeOrderOwnedByStaff((int)$order->id, $staffId)) {
            self::setError('共享订单不支持当前整单履约操作');
            return false;
        }

        [$success, $message] = Order::completeOrder($orderId, $userId, OrderLog::OPERATOR_USER, 'staff');
        if (!$success) {
            self::setError($message);
            return false;
        }

        return true;
    }

    /**
     * @notes 开始履约
     */
    public static function orderStartService(int $userId, int $orderId): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $order = Order::with(['items'])->find($orderId);
        if (!$order) {
            self::setError('订单不存在');
            return false;
        }

        if (!Order::isWholeOrderOwnedByStaff((int)$order->id, $staffId)) {
            self::setError('共享订单不支持当前整单履约操作');
            return false;
        }

        [$success, $message] = Order::startService(
            $orderId,
            $userId,
            OrderLog::OPERATOR_USER,
            '服务人员开始履约'
        );
        if (!$success) {
            self::setError($message);
            return false;
        }

        return true;
    }

    /**
     * @notes 判定服务人员在本单中的角色：main 主服务 / collaborator 协作 / '' 无
     * @param array $allItems 订单全部订单项（需含 staff_id / item_type / item_status）
     * @param int $staffId
     * @return string
     */
    public static function resolveStaffOrderRole(array $allItems, int $staffId): string
    {
        if ($staffId <= 0 || empty($allItems)) {
            return '';
        }

        $staffItems = array_values(array_filter($allItems, static function ($item) use ($staffId) {
            return (int)($item['staff_id'] ?? 0) === $staffId
                && (int)($item['item_status'] ?? 0) !== OrderItem::STATUS_CANCELLED;
        }));
        if (empty($staffItems)) {
            // 本人协作项已全部取消时回退统计全部项，仍能识别原角色
            $staffItems = array_values(array_filter($allItems, static function ($item) use ($staffId) {
                return (int)($item['staff_id'] ?? 0) === $staffId;
            }));
        }
        if (empty($staffItems)) {
            return '';
        }

        foreach ($staffItems as $staffItem) {
            if ((int)($staffItem['item_type'] ?? 0) === OrderItem::TYPE_SERVICE) {
                return 'main';
            }
        }
        foreach ($staffItems as $staffItem) {
            if ((int)($staffItem['item_type'] ?? 0) === OrderItem::TYPE_RELATED_STAFF) {
                return 'collaborator';
            }
        }

        return '';
    }

    /**
     * @notes 判断服务人员是否具备取消/拒绝接单能力
     * @param Order $order
     * @param array $detailData 需包含 receipt_pending / payments / has_active_collaborator_item（可选）
     * @param bool $canManageWholeOrder
     * @param string|null $staffRole main / collaborator；为空时按旧三参逻辑推导
     * @return array{can_staff_cancel: bool, staff_cancel_label: string, staff_cancel_disabled_reason: string, staff_role: string, staff_cancel_scope: string, staff_cancel_notice: string}
     */
    public static function resolveStaffCancelCapability(Order $order, array $detailData, bool $canManageWholeOrder, ?string $staffRole = null): array
    {
        $resolvedRole = $staffRole !== null ? $staffRole : ($canManageWholeOrder ? 'main' : 'none');
        $status = (int)($order->order_status ?? Order::STATUS_PENDING_CONFIRM);
        $label = $resolvedRole === 'collaborator'
            ? ($status === Order::STATUS_PENDING_CONFIRM ? '拒绝协作' : '取消协作')
            : ($status === Order::STATUS_PENDING_CONFIRM ? '拒绝接单' : '取消订单');
        $disabled = static function (string $reason) use ($label, $resolvedRole): array {
            return [
                'can_staff_cancel' => false,
                'staff_cancel_label' => $label,
                'staff_cancel_disabled_reason' => $reason,
                'staff_role' => $resolvedRole,
                'staff_cancel_scope' => 'none',
                'staff_cancel_notice' => '',
            ];
        };

        if (!in_array($resolvedRole, ['main', 'collaborator'], true)) {
            return $disabled('当前订单暂不支持由您直接取消');
        }

        if (!in_array($status, [Order::STATUS_PENDING_CONFIRM, Order::STATUS_PENDING_PAY], true)) {
            $reason = match ($status) {
                Order::STATUS_CANCELLED => '订单已取消，无法重复操作',
                Order::STATUS_COMPLETED, Order::STATUS_REVIEWED => '订单已完成，如需处理请联系管理员',
                Order::STATUS_PENDING_SERVICE, Order::STATUS_IN_SERVICE => '订单已进入服务阶段，如需取消请联系管理员协调',
                Order::STATUS_REFUNDED, Order::STATUS_REFUNDING => '订单退款处理中或已退款，无法取消',
                Order::STATUS_USER_DELETED => '订单已删除，无法取消',
                default => '当前订单状态不支持取消',
            };
            return $disabled($reason);
        }

        if (round((float)($order->paid_amount ?? 0), 2) > 0.01 || !empty($detailData['payments'])) {
            return $disabled('买家已支付款项，如需取消请联系管理员处理退款');
        }

        if ((int)($order->complete_time ?? 0) > 0) {
            return $disabled('服务已完成，尾款应收不可取消，请通过售后处理');
        }

        if (!empty($detailData['receipt_pending'])) {
            return $disabled('存在待审核的线下付款凭证，暂无法取消订单');
        }

        if (
            $resolvedRole === 'collaborator'
            && array_key_exists('has_active_collaborator_item', $detailData)
            && empty($detailData['has_active_collaborator_item'])
        ) {
            return $disabled('您的协作服务已取消，无需重复操作');
        }

        if ($resolvedRole === 'main') {
            return [
                'can_staff_cancel' => true,
                'staff_cancel_label' => $label,
                'staff_cancel_disabled_reason' => '',
                'staff_role' => 'main',
                'staff_cancel_scope' => 'whole',
                'staff_cancel_notice' => $canManageWholeOrder
                    ? ''
                    : '您是本单主服务人员，取消后其他协作服务将同步取消，档期立即释放并通知客户与协作人员',
            ];
        }

        return [
            'can_staff_cancel' => true,
            'staff_cancel_label' => $label,
            'staff_cancel_disabled_reason' => '',
            'staff_role' => 'collaborator',
            'staff_cancel_scope' => 'item',
            'staff_cancel_notice' => '取消后仅释放您本人档期并移除您的协作服务，不影响主服务；系统将推送通知用户重新挑选',
        ];
    }

    /**
     * @notes 服务人员取消订单 / 拒绝接单
     */
    public static function orderCancel(int $userId, int $orderId, string $reason): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $reason = trim($reason);
        $reasonLength = mb_strlen($reason, 'UTF-8');
        if ($reasonLength <= 0) {
            self::setError('请填写取消原因');
            return false;
        }
        if ($reasonLength < 2) {
            self::setError('取消原因至少2个字符');
            return false;
        }
        if ($reasonLength > 200) {
            self::setError('取消原因不能超过200个字符');
            return false;
        }

        $order = Order::with(['items'])->find($orderId);
        if (!$order) {
            self::setError('订单不存在');
            return false;
        }

        if (is_array($order->items ?? null)) {
            $allItems = $order->items;
        } elseif (is_object($order->items ?? null) && method_exists($order->items, 'toArray')) {
            $allItems = $order->items->toArray();
        } else {
            $allItems = [];
        }
        $staffItems = array_values(array_filter($allItems, function ($item) use ($staffId) {
            return (int)($item['staff_id'] ?? 0) === $staffId
                && (int)($item['item_status'] ?? 0) !== OrderItem::STATUS_CANCELLED;
        }));
        if (empty($staffItems)) {
            self::setError('无权取消该订单');
            return false;
        }

        $staffRole = self::resolveStaffOrderRole($allItems, $staffId);
        $hasActiveCollaboratorItem = false;
        foreach ($staffItems as $staffItem) {
            if ((int)($staffItem['item_type'] ?? 0) === OrderItem::TYPE_RELATED_STAFF) {
                $hasActiveCollaboratorItem = true;
                break;
            }
        }

        $canManageWholeOrder = Order::isWholeOrderOwnedByStaff((int)$order->id, $staffId);
        $precheckData = [
            'receipt_pending' => \app\common\service\OrderReceiptService::pending((int)$order->id),
            'payments' => Payment::where('order_id', (int)$order->id)
                ->where('pay_status', Payment::STATUS_PAID)
                ->count() > 0 ? [1] : [],
            'has_active_collaborator_item' => $hasActiveCollaboratorItem,
        ];
        $capability = self::resolveStaffCancelCapability($order, $precheckData, $canManageWholeOrder, $staffRole);
        if (!$capability['can_staff_cancel']) {
            self::setError($capability['staff_cancel_disabled_reason']);
            return false;
        }

        $guardCallback = function (Order $lockedOrder) use ($staffId): array {
            $lockedAllItems = OrderItem::where('order_id', (int)$lockedOrder->id)
                ->field('id, staff_id, item_type, item_status')
                ->select()
                ->toArray();
            $lockedStaffRole = self::resolveStaffOrderRole($lockedAllItems, $staffId);
            $lockedCanManage = Order::isWholeOrderOwnedByStaff((int)$lockedOrder->id, $staffId);
            $lockedPaidPayment = Payment::where('order_id', (int)$lockedOrder->id)
                ->where('pay_status', Payment::STATUS_PAID)
                ->count() > 0;
            $lockedHasActiveCollaboratorItem = false;
            foreach ($lockedAllItems as $lockedItem) {
                if (
                    (int)($lockedItem['staff_id'] ?? 0) === $staffId
                    && (int)($lockedItem['item_type'] ?? 0) === OrderItem::TYPE_RELATED_STAFF
                    && (int)($lockedItem['item_status'] ?? 0) !== OrderItem::STATUS_CANCELLED
                ) {
                    $lockedHasActiveCollaboratorItem = true;
                    break;
                }
            }
            $lockedData = [
                'receipt_pending' => \app\common\service\OrderReceiptService::pending((int)$lockedOrder->id),
                'payments' => $lockedPaidPayment ? [1] : [],
                'has_active_collaborator_item' => $lockedHasActiveCollaboratorItem,
            ];
            $lockedCapability = self::resolveStaffCancelCapability($lockedOrder, $lockedData, $lockedCanManage, $lockedStaffRole);

            return [$lockedCapability['can_staff_cancel'], $lockedCapability['staff_cancel_disabled_reason']];
        };

        if ($staffRole === 'collaborator') {
            [$success, $message] = Order::cancelCollaboratorItems(
                $orderId,
                $staffId,
                $userId,
                OrderLog::OPERATOR_STAFF,
                $reason,
                $guardCallback
            );
        } else {
            [$success, $message] = Order::cancelOrder(
                $orderId,
                $userId,
                OrderLog::OPERATOR_STAFF,
                $reason,
                $guardCallback
            );
        }

        if (!$success) {
            self::setError($message);
            return false;
        }

        return true;
    }

    /**
     * @notes 动态列表
     */
    public static function dynamicLists(int $userId, array $params): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $query = Dynamic::where('user_type', Dynamic::USER_TYPE_STAFF)
            ->where('staff_id', $staffId)
            ->order('create_time', 'desc');

        $summaryQuery = Dynamic::where('user_type', Dynamic::USER_TYPE_STAFF)
            ->where('staff_id', $staffId);

        $dynamicType = (int) ($params['dynamic_type'] ?? 0);
        if ($dynamicType > 0) {
            $query->where('dynamic_type', $dynamicType);
        }

        $status = $params['status'] ?? null;
        if ($status !== null && $status !== '' && in_array((int) $status, [
            Dynamic::STATUS_PENDING,
            Dynamic::STATUS_PUBLISHED,
            Dynamic::STATUS_OFFLINE,
            Dynamic::STATUS_REJECTED,
        ], true)) {
            $query->where('status', (int) $status);
        }

        $pageSize = (int) ($params['page_size'] ?? 10);
        if ($pageSize <= 0) {
            $pageSize = 10;
        }

        $result = $query->paginate($pageSize)->toArray();
        foreach (($result['data'] ?? []) as $index => $item) {
            $result['data'][$index] = self::formatStaffDynamicItem($item);
        }
        $result['summary'] = [
            'total' => (int) $summaryQuery->count(),
            'published_count' => (int) Dynamic::where('user_type', Dynamic::USER_TYPE_STAFF)
                ->where('staff_id', $staffId)
                ->where('status', Dynamic::STATUS_PUBLISHED)
                ->count(),
            'pending_count' => (int) Dynamic::where('user_type', Dynamic::USER_TYPE_STAFF)
                ->where('staff_id', $staffId)
                ->where('status', Dynamic::STATUS_PENDING)
                ->count(),
            'offline_count' => (int) Dynamic::where('user_type', Dynamic::USER_TYPE_STAFF)
                ->where('staff_id', $staffId)
                ->where('status', Dynamic::STATUS_OFFLINE)
                ->count(),
            'rejected_count' => (int) Dynamic::where('user_type', Dynamic::USER_TYPE_STAFF)
                ->where('staff_id', $staffId)
                ->where('status', Dynamic::STATUS_REJECTED)
                ->count(),
        ];

        return $result;
    }

    /**
     * @notes 动态详情
     */
    public static function dynamicDetail(int $userId, int $id): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $dynamic = Dynamic::where('id', $id)
            ->where('staff_id', $staffId)
            ->where('user_type', Dynamic::USER_TYPE_STAFF)
            ->append(['tags_arr', 'dynamic_type_desc', 'status_desc', 'allow_comment_desc'])
            ->find();
        if (!$dynamic) {
            self::setError('动态不存在');
            return [];
        }

        return self::formatStaffDynamicItem($dynamic->toArray());
    }

    /**
     * @notes 发布动态
     */
    public static function dynamicAdd(int $userId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        // 内容安全闸门：本地敏感词禁止级命中直接拒绝；微信检测命中不阻断（落库待审，转人工复核）
        $contentText = is_array($params['content'] ?? null)
            ? json_encode($params['content'], JSON_UNESCAPED_UNICODE)
            : (string)($params['content'] ?? '');
        $tagsText = is_array($params['tags'] ?? null)
            ? implode(',', array_map('strval', $params['tags']))
            : (string)($params['tags'] ?? '');
        if (!StaffContentGuardService::textGuard($userId, [
            'title' => (string)($params['title'] ?? ''),
            'content' => $contentText,
            'tags' => $tagsText,
            'location' => (string)($params['location'] ?? ''),
        ], WechatSecurityService::SCENE_DYNAMIC)) {
            self::setError(StaffContentGuardService::getError());
            return false;
        }

        [$success, $message] = Dynamic::publish($staffId, Dynamic::USER_TYPE_STAFF, [
            'staff_id' => $staffId,
            'dynamic_type' => $params['dynamic_type'] ?? Dynamic::TYPE_IMAGE_TEXT,
            'title' => $params['title'] ?? '',
            'content' => $params['content'],
            'images' => $params['images'] ?? [],
            'video_url' => $params['video_url'] ?? '',
            'video_cover' => $params['video_cover'] ?? '',
            'location' => $params['location'] ?? '',
            'latitude' => $params['latitude'] ?? 0,
            'longitude' => $params['longitude'] ?? 0,
            'tags' => $params['tags'] ?? '',
            'allow_comment' => $params['allow_comment'] ?? 1,
            'order_id' => $params['order_id'] ?? 0,
        ]);

        if (!$success) {
            self::setError($message);
            return false;
        }
        return true;
    }

    /**
     * @notes 编辑动态
     */
    public static function dynamicEdit(int $userId, int $id, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $dynamic = Dynamic::where('id', $id)
            ->where('staff_id', $staffId)
            ->where('user_type', Dynamic::USER_TYPE_STAFF)
            ->find();
        if (!$dynamic) {
            self::setError('动态不存在');
            return false;
        }

        // 内容安全闸门：仅检测本次提交的非空字段，未提交字段沿用已过审旧值
        $contentText = is_array($params['content'] ?? null)
            ? json_encode($params['content'], JSON_UNESCAPED_UNICODE)
            : (string)($params['content'] ?? '');
        $tagsText = is_array($params['tags'] ?? null)
            ? implode(',', array_map('strval', $params['tags']))
            : (string)($params['tags'] ?? '');
        if (!StaffContentGuardService::textGuard($userId, [
            'title' => (string)($params['title'] ?? ''),
            'content' => $contentText,
            'tags' => $tagsText,
            'location' => (string)($params['location'] ?? ''),
        ], WechatSecurityService::SCENE_DYNAMIC)) {
            self::setError(StaffContentGuardService::getError());
            return false;
        }

        $dynamic->dynamic_type = $params['dynamic_type'] ?? $dynamic->dynamic_type;
        $dynamic->title = $params['title'] ?? $dynamic->title;
        $dynamic->content = $params['content'] ?? $dynamic->content;
        $dynamic->images = $params['images'] ?? $dynamic->images;
        $dynamic->video_url = $params['video_url'] ?? $dynamic->video_url;
        $dynamic->video_cover = $params['video_cover'] ?? $dynamic->video_cover;
        $dynamic->location = $params['location'] ?? $dynamic->location;
        $dynamic->latitude = $params['latitude'] ?? $dynamic->latitude;
        $dynamic->longitude = $params['longitude'] ?? $dynamic->longitude;
        $dynamic->tags = is_array($params['tags'] ?? '') ? implode(',', $params['tags']) : ($params['tags'] ?? $dynamic->tags);
        $dynamic->allow_comment = $params['allow_comment'] ?? $dynamic->allow_comment;
        $dynamic->status = Dynamic::STATUS_PENDING;
        $dynamic->update_time = time();
        $dynamic->save();

        return true;
    }

    /**
     * @notes 删除动态
     */
    public static function dynamicDelete(int $userId, int $id): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $dynamic = Dynamic::where('id', $id)
            ->where('staff_id', $staffId)
            ->where('user_type', Dynamic::USER_TYPE_STAFF)
            ->find();
        if (!$dynamic) {
            self::setError('动态不存在');
            return false;
        }

        $dynamic->delete();
        return true;
    }

    /**
     * @notes 仅保留当前服务人员相关金额
     */
    protected static function applyStaffOrderAmounts(array $order): array
    {
        $items = $order['items'] ?? [];
        $staffServiceAmount = 0.0;
        $staffAddonAmount = 0.0;
        $hasActiveItem = false;
        foreach ($items as $item) {
            if ((int)($item['item_status'] ?? 0) !== OrderItem::STATUS_CANCELLED) {
                $hasActiveItem = true;
                break;
            }
        }
        foreach ($items as $index => $item) {
            if ($hasActiveItem && (int)($item['item_status'] ?? 0) === OrderItem::STATUS_CANCELLED) {
                continue;
            }
            $subtotal = isset($item['subtotal']) ? (float) $item['subtotal'] : 0.0;
            if ($subtotal <= 0) {
                $price = (float) ($item['price'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 1);
                $subtotal = $price * max($quantity, 1);
            }
            $addonAmount = 0.0;
            foreach (($item['addons'] ?? []) as $addon) {
                $addonAmount += (float)($addon['subtotal'] ?? $addon['price'] ?? 0);
            }
            $addonAmount = round($addonAmount, 2);
            $items[$index]['addon_amount'] = $addonAmount;
            if ((int)($item['item_type'] ?? OrderItem::TYPE_SERVICE) === OrderItem::TYPE_SERVICE) {
                $staffServiceAmount += $subtotal;
            } else {
                $staffAddonAmount += $subtotal;
            }
            $staffAddonAmount += $addonAmount;
        }
        $order['items'] = $items;
        $staffServiceAmount = round($staffServiceAmount, 2);
        $staffAddonAmount = round($staffAddonAmount, 2);
        $staffTotal = round($staffServiceAmount + $staffAddonAmount, 2);

        $orderTotal = (float) ($order['total_amount'] ?? 0);
        $originPayAmount = (float) ($order['pay_amount'] ?? 0);
        $originDepositAmount = (float) ($order['deposit_amount'] ?? 0);
        $discountTotal = (float) ($order['discount_amount'] ?? 0);
        $paidTotal = (float) ($order['paid_amount'] ?? 0);

        if ($orderTotal > 0 && $staffTotal > 0) {
            $ratio = $staffTotal / $orderTotal;
            $staffDiscount = round($discountTotal * $ratio, 2);
        } else {
            $staffDiscount = 0.0;
        }

        $staffPay = round(max($staffTotal - $staffDiscount, 0), 2);
        $staffPaid = 0.0;
        if ($originPayAmount > 0 && $staffPay > 0 && $paidTotal > 0) {
            $staffPaid = round($paidTotal * ($staffPay / $originPayAmount), 2);
            if ($staffPaid > $staffPay) {
                $staffPaid = $staffPay;
            }
        }

        $staffDeposit = 0.0;
        $staffBalance = 0.0;
        if ($originDepositAmount > 0 && $originPayAmount > 0 && $staffPay > 0) {
            $staffDeposit = round($originDepositAmount * ($staffPay / $originPayAmount), 2);
            if ($staffDeposit > $staffPay) {
                $staffDeposit = $staffPay;
            }
            $staffBalance = round(max($staffPay - $staffDeposit, 0), 2);
        }

        $depositPaid = 0;
        $balancePaid = 0;
        $amountTolerance = 0.01;
        if ($staffDeposit > 0) {
            if ((int)($order['deposit_paid'] ?? 0) === 1 && $staffPaid + $amountTolerance >= $staffDeposit) {
                $depositPaid = 1;
            }
            if ((int)($order['balance_paid'] ?? 0) === 1 && $staffPaid + $amountTolerance >= $staffPay) {
                $balancePaid = 1;
            }
        } elseif ($staffPay > 0 && (int)($order['balance_paid'] ?? 0) === 1 && $staffPaid + $amountTolerance >= $staffPay) {
            $balancePaid = 1;
        }

        $order['service_amount'] = $staffServiceAmount;
        $order['addon_amount'] = $staffAddonAmount;
        $order['total_amount'] = $staffTotal;
        $order['discount_amount'] = $staffDiscount;
        $order['pay_amount'] = $staffPay;
        $order['paid_amount'] = $staffPaid;
        $order['unpaid_amount'] = round(max($staffPay - $staffPaid, 0), 2);
        $order['deposit_amount'] = $staffDeposit;
        $order['balance_amount'] = $staffBalance;
        $order['deposit_paid'] = $depositPaid;
        $order['balance_paid'] = $balancePaid;
        if ($staffPay <= 0) {
            $order['pay_status'] = Order::PAY_STATUS_UNPAID;
        } elseif ($staffPaid + $amountTolerance >= $staffPay) {
            $order['pay_status'] = Order::PAY_STATUS_PAID;
        }

        return $order;
    }

    /**
     * @notes 获取订单状态描述
     */
    protected static function getStatusDesc(int $status): string
    {
        return Order::getStatusText($status);
    }

    /**
     * @notes 获取支付状态描述
     */
    protected static function getPayStatusDesc(int $status): string
    {
        return Order::getPayStatusText($status);
    }

    /**
     * @notes 获取支付方式描述
     */
    protected static function getPayTypeDesc(int $type): string
    {
        return Order::getPayWayText($type);
    }

    /**
     * @notes 统一服务人员动态状态反馈
     * @param array $item
     * @return array
     */
    protected static function formatStaffDynamicItem(array $item): array
    {
        $status = (int)($item['status'] ?? Dynamic::STATUS_PENDING);
        $remark = trim((string)($item['audit_remark'] ?? ''));

        $item['status_desc'] = self::getDynamicStatusDesc($status);
        $item['dynamic_type_desc'] = $item['dynamic_type_desc'] ?? self::getDynamicTypeDesc((int)($item['dynamic_type'] ?? 0));
        $item['allow_comment_desc'] = ((int)($item['allow_comment'] ?? 1) === 1) ? '允许评论' : '禁止评论';
        $item['status_reason'] = self::getDynamicStatusReason($status, $remark);
        $item['status_hint'] = self::getDynamicStatusHint($status);
        $item['handled_time'] = (int)($item['audit_time'] ?? 0);

        return $item;
    }

    /**
     * @notes 获取动态状态描述
     * @param int $status
     * @return string
     */
    protected static function getDynamicStatusDesc(int $status): string
    {
        return match ($status) {
            Dynamic::STATUS_PENDING => '待审核',
            Dynamic::STATUS_PUBLISHED => '已发布',
            Dynamic::STATUS_OFFLINE => '已下架',
            Dynamic::STATUS_REJECTED => '已拒绝',
            default => '未知',
        };
    }

    /**
     * @notes 获取动态类型描述
     * @param int $type
     * @return string
     */
    protected static function getDynamicTypeDesc(int $type): string
    {
        return match ($type) {
            Dynamic::TYPE_IMAGE_TEXT => '图文',
            Dynamic::TYPE_VIDEO => '视频',
            Dynamic::TYPE_CASE => '案例',
            Dynamic::TYPE_ACTIVITY => '活动',
            default => '动态',
        };
    }

    /**
     * @notes 获取动态状态原因
     * @param int $status
     * @param string $remark
     * @return string
     */
    protected static function getDynamicStatusReason(int $status, string $remark): string
    {
        if (in_array($status, [Dynamic::STATUS_OFFLINE, Dynamic::STATUS_REJECTED], true)) {
            return $remark ?: '暂无补充说明';
        }

        if ($status === Dynamic::STATUS_PENDING) {
            return '内容已提交，等待后台审核。';
        }

        if ($status === Dynamic::STATUS_PUBLISHED) {
            return '当前内容已对用户可见。';
        }

        return '';
    }

    /**
     * @notes 获取动态状态操作提示
     * @param int $status
     * @return string
     */
    protected static function getDynamicStatusHint(int $status): string
    {
        return match ($status) {
            Dynamic::STATUS_PENDING => '审核通过后会自动展示到用户端。',
            Dynamic::STATUS_PUBLISHED => '可继续编辑内容；如被下架会在这里同步处理原因。',
            Dynamic::STATUS_OFFLINE => '修改内容后可重新提交审核。',
            Dynamic::STATUS_REJECTED => '请根据原因调整内容后重新提交审核。',
            default => '',
        };
    }

    /**
     * @notes 读取人员当前服务分类名称
     * @param int $staffId
     * @return string
     */
    protected static function resolveStaffCategoryName(int $staffId): string
    {
        if ($staffId <= 0) {
            return '';
        }

        $staff = Staff::where('id', $staffId)->field('id, category_id')->find();
        if (!$staff) {
            return '';
        }

        $categoryId = (int)($staff->category_id ?? 0);
        if ($categoryId <= 0) {
            return '';
        }

        $category = ServiceCategory::where('id', $categoryId)->field('id, name')->find();
        return $category ? (string)$category->name : '';
    }

    /**
     * @notes 构造套餐写入载荷
     * @param int $staffId
     * @param array $params
     * @param bool $withCreateFields
     * @param ServicePackage|null $existing
     * @return array
     */
    protected static function buildPackagePayload(
        int $staffId,
        array $params,
        bool $withCreateFields = true,
        ?ServicePackage $existing = null
    ): array {
        $image = trim((string)($params['image'] ?? ''));
        $payload = [
            'staff_id' => $staffId,
            'category_id' => self::resolveStaffCategoryId($staffId),
            'name' => array_key_exists('name', $params)
                ? (string)$params['name']
                : (string)($existing->name ?? ''),
            'price' => array_key_exists('price', $params)
                ? round((float)$params['price'], 2)
                : round((float)($existing->price ?? 0), 2),
            'original_price' => array_key_exists('original_price', $params)
                ? round((float)$params['original_price'], 2)
                : round((float)($existing->original_price ?? 0), 2),
            'description' => array_key_exists('description', $params)
                ? (string)$params['description']
                : (string)($existing->description ?? ''),
            'image' => array_key_exists('image', $params)
                ? ($image !== '' ? \app\common\service\FileService::setFileUrl($image) : '')
                : ($existing ? (string)$existing->getData('image') : ''),
            'sort' => array_key_exists('sort', $params)
                ? (int)$params['sort']
                : (int)($existing->sort ?? 0),
            'is_show' => array_key_exists('is_show', $params)
                ? (int)$params['is_show']
                : (int)($existing->is_show ?? 1),
            'is_recommend' => array_key_exists('is_recommend', $params)
                ? (int)$params['is_recommend']
                : (int)($existing->is_recommend ?? 0),
            'duration' => array_key_exists('duration', $params)
                ? (int)$params['duration']
                : (int)($existing->duration ?? 0),
            'update_time' => time(),
        ];

        if ($withCreateFields) {
            $payload['create_time'] = time();
        }

        return $payload;
    }

    /**
     * @notes 构造附加服务写入载荷
     * @param int $staffId
     * @param array $params
     * @param bool $withCreateFields
     * @param ServiceAddon|null $existing
     * @return array
     */
    protected static function buildAddonPayload(
        int $staffId,
        array $params,
        bool $withCreateFields = true,
        ?ServiceAddon $existing = null
    ): array {
        $image = trim((string)($params['image'] ?? ''));
        $payload = [
            'staff_id' => $staffId,
            'category_id' => self::resolveStaffCategoryId($staffId),
            'name' => array_key_exists('name', $params)
                ? (string)$params['name']
                : (string)($existing->name ?? ''),
            'price' => array_key_exists('price', $params)
                ? round((float)$params['price'], 2)
                : round((float)($existing->price ?? 0), 2),
            'original_price' => array_key_exists('original_price', $params)
                ? round((float)$params['original_price'], 2)
                : round((float)($existing->original_price ?? 0), 2),
            'description' => array_key_exists('description', $params)
                ? (string)$params['description']
                : (string)($existing->description ?? ''),
            'image' => array_key_exists('image', $params)
                ? ($image !== '' ? \app\common\service\FileService::setFileUrl($image) : '')
                : ($existing ? (string)$existing->getData('image') : ''),
            'sort' => array_key_exists('sort', $params)
                ? (int)$params['sort']
                : (int)($existing->sort ?? 0),
            'is_show' => array_key_exists('is_show', $params)
                ? (int)$params['is_show']
                : (int)($existing->is_show ?? 1),
            'update_time' => time(),
        ];

        if ($withCreateFields) {
            $payload['create_time'] = time();
        }

        return $payload;
    }

    /**
     * @notes 同步人员名下套餐分类
     * @param int $staffId
     * @param int $categoryId
     * @return void
     */
    protected static function syncOwnedPackageCategory(int $staffId, int $categoryId): void
    {
        if ($staffId <= 0 || $categoryId <= 0) {
            return;
        }

        ServicePackage::where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->update([
                'category_id' => $categoryId,
                'update_time' => time(),
            ]);
    }

    /**
     * @notes 同步人员名下附加服务分类
     * @param int $staffId
     * @param int $categoryId
     * @return void
     */
    protected static function syncOwnedAddonCategory(int $staffId, int $categoryId): void
    {
        if ($staffId <= 0 || $categoryId <= 0) {
            return;
        }

        ServiceAddon::where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->update([
                'category_id' => $categoryId,
                'update_time' => time(),
            ]);
    }

    /**
     * @notes 读取所属人员当前服务分类
     * @param int $staffId
     * @return int
     */
    protected static function resolveStaffCategoryId(int $staffId): int
    {
        $staff = Staff::find($staffId);
        if (!$staff) {
            throw new \Exception('所属人员不存在');
        }

        $categoryId = (int)($staff->category_id ?? 0);
        if ($categoryId <= 0) {
            throw new \Exception('请先设置服务分类');
        }

        return $categoryId;
    }

    public static function orderConfirmLetterGenerate(int $userId, int $orderId, int $configId = 0, int $manualId = 0)
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        try {
            return StaffScheduleConfirmLetterService::generate($orderId, $staffId, 'staff', $staffId, $configId, $manualId);
        } catch (\Throwable $e) {
            self::setError(StaffScheduleConfirmLetterService::normalizeErrorMessage($e->getMessage()));
            return false;
        }
    }

    private static function getScheduleConfirmLetterColorPresets(): array
    {
        return [
            ['label' => '玄墨黑', 'value' => '#191713'],
            ['label' => '沉香棕', 'value' => '#2B2118'],
            ['label' => '绛红金', 'value' => '#4A1E24'],
            ['label' => '暖象牙', 'value' => '#F5EFE4'],
        ];
    }

    public static function scheduleConfirmLetterConfig(int $userId, int $configId = 0)
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        try {
            $config = StaffScheduleConfirmLetterService::getConfig($staffId, $configId);
            $config['versions'] = StaffScheduleConfirmLetterService::listConfigs($staffId, false);
            $config['color_presets'] = self::getScheduleConfirmLetterColorPresets();
            return $config;
        } catch (\Throwable $e) {
            self::setError(StaffScheduleConfirmLetterService::normalizeErrorMessage($e->getMessage()));
            return false;
        }
    }

    public static function scheduleConfirmLetterSaveConfig(int $userId, array $params)
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        try {
            $result = StaffScheduleConfirmLetterService::saveLiteConfig($staffId, (int)($params['config_id'] ?? 0), $params);
            if (is_array($result)) {
                $result['color_presets'] = self::getScheduleConfirmLetterColorPresets();
            }
            return $result;
        } catch (\Throwable $e) {
            self::setError(StaffScheduleConfirmLetterService::normalizeErrorMessage($e->getMessage()));
            return false;
        }
    }

    public static function scheduleConfirmLetterPreview(int $userId, array $params = [])
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        unset($params['design_config']);
        try {
            return StaffScheduleConfirmLetterService::previewConfig($staffId, $params);
        } catch (\Throwable $e) {
            self::setError(StaffScheduleConfirmLetterService::normalizeErrorMessage($e->getMessage()));
            return false;
        }
    }

    public static function orderConfirmLetterSaveAssets(int $userId, array $params)
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        try {
            $result = StaffScheduleConfirmLetterService::regenerateAssets(
                (int) $params['letter_id'],
                (string) ($params['snapshot_hash'] ?? ''),
                $staffId,
                true
            );
            return [
                'letter_id' => (int) ($result['letter_id'] ?? $params['letter_id']),
                'assets_saved' => true,
            ];
        } catch (\Throwable $e) {
            self::setError(StaffScheduleConfirmLetterService::normalizeErrorMessage($e->getMessage()));
            return false;
        }
    }

    public static function orderConfirmLetterPush(int $userId, int $letterId)
    {
        self::setError('档期确认函用于服务人员保存图片后自行分享，不支持推送客户');
        return false;
    }

    public static function orderConfirmLetterDetail(int $userId, int $letterId): ?array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return null;
        }
        try {
            return StaffScheduleConfirmLetterService::detail($letterId, $staffId);
        } catch (\Throwable $e) {
            self::setError(StaffScheduleConfirmLetterService::normalizeErrorMessage($e->getMessage()));
            return null;
        }
    }

    public static function orderConfirmLetterHistory(int $userId, int $orderId, int $manualId = 0)
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        try {
            return StaffScheduleConfirmLetterService::history($orderId, $staffId, $manualId);
        } catch (\Throwable $e) {
            self::setError(StaffScheduleConfirmLetterService::normalizeErrorMessage($e->getMessage()));
            return false;
        }
    }

    /**
     * @notes 结算列表
     */
    public static function settlementLists(int $userId, array $params): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $pageNo = max(1, (int)($params['page_no'] ?? 1));
        $pageSize = max(1, min((int)($params['page_size'] ?? 15), 50));
        $query = StaffSettlement::with(['order', 'orderItem', 'transfers'])
            ->where('staff_id', $staffId);
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int)$params['status']);
        }

        $count = (clone $query)->count();
        $list = $query->order('id', 'desc')
            ->page($pageNo, $pageSize)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item = self::formatSettlementItem($item);
        }

        return [
            'lists' => $list,
            'count' => $count,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];
    }

    /**
     * @notes 转账确认参数
     */
    public static function settlementReceive(int $userId, int $settlementId): array|bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $result = (new StaffSettlementService())->getConfirmPayload($settlementId, $staffId);
        if ($result === false) {
            self::setError('结算记录不存在或无权限');
            return false;
        }
        if (empty($result['package']) && empty($result['package_info'])) {
            self::setError('当前结算暂无可确认转账');
            return false;
        }
        return $result;
    }

    /**
     * @notes 同步本人转账状态
     */
    public static function settlementSync(int $userId, int $settlementId): array|bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $settlement = StaffSettlement::where('id', $settlementId)
            ->where('staff_id', $staffId)
            ->find();
        if (!$settlement) {
            self::setError('结算记录不存在或无权限');
            return false;
        }

        return (new StaffSettlementService())->syncTransferStatus($settlementId, 20);
    }

    /**
     * @notes 格式化结算项
     */
    protected static function formatSettlementItem(array $item): array
    {
        $transfers = $item['transfers'] ?? [];
        $summary = StaffSettlementService::buildTransferSummary($transfers);

        $item['status_text'] = StaffSettlement::getStatusDesc((int)$item['status']);
        $item['settle_way_text'] = StaffSettlement::getSettleWayDesc((int)$item['settle_way']);
        $item['platform_commission_amount'] = round((float)($item['platform_amount'] ?? $item['company_amount'] ?? 0), 2);
        $item['platform_paid_share_amount'] = round((float)($item['platform_paid_share_amount'] ?? 0), 2);
        $item['staff_due_platform_amount'] = round((float)($item['staff_due_platform_amount'] ?? 0), 2);
        $item['staff_due_collected_amount'] = round((float)($item['staff_due_collected_amount'] ?? 0), 2);
        $item['staff_due_left_amount'] = round(max($item['staff_due_platform_amount'] - $item['staff_due_collected_amount'], 0), 2);
        $item['staff_due_collect_status_text'] = StaffSettlement::getDueCollectStatusDesc((int)($item['staff_due_collect_status'] ?? 0));
        $item['transfer_summary'] = $summary;
        $item['can_receive'] = $summary['package_info'] !== ''
            && (int)$item['status'] === StaffSettlement::STATUS_TRANSFER_PROCESSING;
        $item['can_repay_platform'] = $item['staff_due_left_amount'] > 0
            && (int)$item['status'] !== StaffSettlement::STATUS_CANCELLED;
        return $item;
    }

    /**
     * @notes 获取订单变更/改期记录（工作台只读展示）
     */
    private static function getOrderChangesForStaff(int $orderId): array
    {
        if ($orderId <= 0) {
            return [];
        }

        $changes = OrderChange::where('order_id', $orderId)
            ->order('id', 'desc')
            ->select();

        $list = [];
        foreach ($changes as $change) {
            $list[] = [
                'id' => (int)$change->id,
                'change_sn' => (string)($change->change_sn ?? ''),
                'change_type' => (int)($change->change_type ?? 0),
                'change_type_desc' => (string)$change->change_type_desc,
                'change_status' => (int)($change->change_status ?? 0),
                'change_status_desc' => (string)$change->change_status_desc,
                'old_service_date' => (string)($change->old_service_date ?? ''),
                'new_service_date' => (string)($change->new_service_date ?? ''),
                'apply_reason' => (string)($change->apply_reason ?? ''),
                'audit_remark' => (string)($change->audit_remark ?? ''),
                'reject_reason' => (string)($change->reject_reason ?? ''),
                'create_time' => self::formatTimestampText($change->getData('create_time') ?: $change->create_time),
                'audit_time' => self::formatTimestampText($change->getData('audit_time') ?: $change->audit_time),
                'execute_time' => self::formatTimestampText($change->getData('execute_time') ?: $change->execute_time),
            ];
        }

        return $list;
    }

    /**
     * @notes 服务人员候补队列
     */
    public static function waitlistLists(int $userId, array $params): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $pageNo = max(1, (int)($params['page_no'] ?? 1));
        $pageSize = max(1, min((int)($params['page_size'] ?? 15), 50));

        $query = Waitlist::where('staff_id', $staffId);
        if (isset($params['notify_status']) && $params['notify_status'] !== '') {
            $query->where('notify_status', (int)$params['notify_status']);
        }

        $count = (clone $query)->count();
        $rows = (clone $query)
            ->with([
                'user' => function ($q) {
                    $q->field('id, nickname, avatar, mobile');
                },
                'package' => function ($q) {
                    $q->field('id, name');
                },
            ])
            ->order('notify_status', 'asc')
            ->order('schedule_date', 'asc')
            ->order('create_time', 'asc')
            ->order('id', 'asc')
            ->page($pageNo, $pageSize)
            ->select();

        $lists = [];
        foreach ($rows as $row) {
            $status = (int)($row->notify_status ?? 0);
            $canNotify = false;
            $notifyBlockReason = '';
            if ($status === Waitlist::NOTIFY_STATUS_PENDING) {
                [$canNotify, $notifyBlockReason] = Waitlist::canNotifyWaitlist($row);
            }

            $item = $row->toArray();
            $rawMobile = (string)($item['user']['mobile'] ?? '');
            $maskedMobile = self::maskMobileNumber($rawMobile);
            $nickname = trim((string)($item['user']['nickname'] ?? ''));
            $customerName = $nickname !== '' ? $nickname : ($maskedMobile !== '' ? $maskedMobile : '客户');

            if (isset($item['user']) && is_array($item['user'])) {
                unset($item['user']['mobile']);
            }

            $lists[] = [
                'id' => (int)($item['id'] ?? 0),
                'schedule_date' => (string)($item['schedule_date'] ?? ''),
                'package_id' => (int)($item['package_id'] ?? 0),
                'package_name' => (string)($item['package']['name'] ?? '意向服务预约'),
                'batch_no' => (string)($item['batch_no'] ?? ''),
                'customer_name' => $customerName,
                'customer_mobile' => $maskedMobile,
                'notify_status' => $status,
                'notify_status_desc' => (string)($item['notify_status_desc'] ?? ''),
                'notify_time_text' => self::formatTimestampText($row->getData('notify_time') ?: ($item['notify_time'] ?? 0)),
                'expire_time_text' => self::formatTimestampText($row->getData('expire_time') ?: ($item['expire_time'] ?? 0)),
                'create_time_text' => self::formatTimestampText($row->getData('create_time') ?: ($item['create_time'] ?? 0)),
                'remark' => (string)($item['remark'] ?? ''),
                'can_notify' => $canNotify,
                'notify_block_reason' => $canNotify ? '' : $notifyBlockReason,
            ];
        }

        return [
            'lists' => $lists,
            'count' => $count,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];
    }

    /**
     * @notes 通知候补客户预约（严格校验档期已释放且按 FIFO 顺序通知）
     */
    public static function waitlistNotify(int $userId, int $waitlistId): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $notifiedWaitlist = null;
        Db::startTrans();
        try {
            $waitlist = Waitlist::where('id', $waitlistId)
                ->where('staff_id', $staffId)
                ->with([
                    'staff' => function ($q) {
                        $q->field('id, name');
                    },
                    'package' => function ($q) {
                        $q->field('id, name');
                    },
                ])
                ->lock(true)
                ->find();

            if (!$waitlist) {
                Db::rollback();
                self::setError('候补记录不存在或无权限');
                return false;
            }

            [$canNotify, $message] = Waitlist::canNotifyWaitlist($waitlist);
            if (!$canNotify) {
                Db::rollback();
                self::setError($message);
                return false;
            }

            if (!Waitlist::markWaitlistAsNotified($waitlist)) {
                Db::rollback();
                self::setError('通知状态更新失败');
                return false;
            }

            $notifiedWaitlist = $waitlist;
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError('通知失败：' . $e->getMessage());
            return false;
        }

        try {
            Waitlist::dispatchReleaseNotifications($notifiedWaitlist);
        } catch (\Throwable $e) {
            \think\facade\Log::error('服务人员释放候补通知发送失败：' . $e->getMessage());
        }

        return true;
    }

    private static function formatTimestampText(mixed $value): string
    {
        if (is_numeric($value)) {
            $ts = (int)$value;
            return $ts > 0 ? date('Y-m-d H:i', $ts) : '';
        }
        $str = trim((string)$value);
        if ($str === '' || $str === '0') {
            return '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/', $str)) {
            return substr($str, 0, 16);
        }
        return $str;
    }

    private static function maskMobileNumber(string $mobile): string
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

    /**
     * @notes 服务日作战面板
     */
    public static function serviceDayPanel(int $userId, array $params = []): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $today = date('Y-m-d');
        $rawDate = trim((string)($params['date'] ?? ''));
        $parsed = $rawDate !== '' ? \DateTimeImmutable::createFromFormat('!Y-m-d', $rawDate) : false;
        $targetDate = ($parsed && $parsed->format('Y-m-d') === $rawDate) ? $rawDate : $today;
        $rangeDays = max(1, min(30, (int)($params['range_days'] ?? 14)));
        $endDate = date('Y-m-d', strtotime($today . ' +' . $rangeDays . ' days'));

        $weekNames = ['日', '一', '二', '三', '四', '五', '六'];
        $weekdayLabel = '周' . ($weekNames[(int)date('w', strtotime($targetDate))] ?? '');

        $excludedOrderStatuses = [
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
            Order::STATUS_USER_DELETED,
        ];

        $dateStats = [];
        $platformDateRows = OrderItem::alias('oi')
            ->join('order o', 'o.id = oi.order_id')
            ->where('oi.staff_id', $staffId)
            ->whereIn('oi.item_type', self::getStaffStatItemTypes())
            ->where('oi.item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->whereBetween('oi.service_date', [$today, $endDate])
            ->whereNotIn('o.order_status', $excludedOrderStatuses)
            ->whereNull('o.delete_time')
            ->field('oi.service_date AS d, COUNT(DISTINCT oi.order_id) AS cnt')
            ->group('oi.service_date')
            ->select()
            ->toArray();

        foreach ($platformDateRows as $row) {
            $d = (string)($row['d'] ?? '');
            if ($d === '') {
                continue;
            }
            if (!isset($dateStats[$d])) {
                $dateStats[$d] = ['platform_count' => 0, 'manual_count' => 0];
            }
            $dateStats[$d]['platform_count'] += (int)($row['cnt'] ?? 0);
        }

        $manualDateRows = ManualSchedule::where('staff_id', $staffId)
            ->where('status', '<>', ManualSchedule::STATUS_CANCELLED)
            ->whereBetween('schedule_date', [$today, $endDate])
            ->field('schedule_date AS d, COUNT(*) AS cnt')
            ->group('schedule_date')
            ->select()
            ->toArray();

        foreach ($manualDateRows as $row) {
            $d = (string)($row['d'] ?? '');
            if ($d === '') {
                continue;
            }
            if (!isset($dateStats[$d])) {
                $dateStats[$d] = ['platform_count' => 0, 'manual_count' => 0];
            }
            $dateStats[$d]['manual_count'] += (int)($row['cnt'] ?? 0);
        }

        ksort($dateStats);
        $upcomingDates = [];
        foreach (array_slice($dateStats, 0, 14, true) as $d => $counts) {
            $pCount = (int)$counts['platform_count'];
            $mCount = (int)$counts['manual_count'];
            $upcomingDates[] = [
                'date' => $d,
                'platform_count' => $pCount,
                'manual_count' => $mCount,
                'total_count' => $pCount + $mCount,
                'is_today' => $d === $today,
            ];
        }

        $ordersCollection = Order::with([
                'items' => function ($q) use ($staffId, $targetDate) {
                    $q->field('id, order_id, staff_id, staff_name, package_name, package_description, service_date, item_status, confirm_status, item_type, quantity, price, subtotal')
                        ->where('staff_id', $staffId)
                        ->whereIn('item_type', self::getStaffStatItemTypes())
                        ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                        ->where('service_date', $targetDate)
                        ->with(['addons' => function ($addonQuery) {
                            $addonQuery->field('id, order_item_id, addon_id, addon_name, price, quantity, subtotal');
                        }])
                        ->order('id', 'asc');
                },
            ])
            ->whereNotIn('order_status', $excludedOrderStatuses)
            ->whereIn('id', function ($subQuery) use ($staffId, $targetDate) {
                self::applyStaffOrderIdSubQuery($subQuery, $staffId);
                $subQuery->where('service_date', $targetDate)
                    ->where('item_status', '<>', OrderItem::STATUS_CANCELLED);
            })
            ->order('id', 'asc')
            ->select();

        $orderIds = [];
        foreach ($ordersCollection as $orderModel) {
            $orderIds[] = (int)$orderModel->id;
        }

        $teammatesMap = self::loadServiceDayTeammatesMap($orderIds, $staffId);
        $questionnaireMap = self::loadServiceDayQuestionnaireMap($orderIds, $staffId);
        $orderLetterMap = self::loadConfirmLetterMapByOrder($orderIds, $staffId);

        $pendingConfirmCount = 0;
        $pendingServiceCount = 0;
        $inServiceCount = 0;
        $completedCount = 0;

        $orders = [];
        foreach ($ordersCollection as $orderModel) {
            $oid = (int)$orderModel->id;
            $status = (int)$orderModel->order_status;
            if ($status === Order::STATUS_PENDING_CONFIRM) {
                $pendingConfirmCount++;
            } elseif ($status === Order::STATUS_PENDING_SERVICE) {
                $pendingServiceCount++;
            } elseif ($status === Order::STATUS_IN_SERVICE) {
                $inServiceCount++;
            } elseif (in_array($status, [Order::STATUS_COMPLETED, Order::STATUS_REVIEWED], true)) {
                $completedCount++;
            }

            $rawArr = $orderModel->toArray();
            $staffItems = array_values((array)($rawArr['items'] ?? []));
            $canManageWholeOrder = Order::isWholeOrderOwnedByStaff($oid, $staffId);
            $paymentSummary = Order::getPaymentSummary($orderModel);
            $startTs = (int)($orderModel->getData('start_service_time') ?: 0);
            $remark = trim((string)($orderModel->user_remark ?? ''));
            if ($remark === '') {
                $remark = trim((string)($orderModel->admin_remark ?? ''));
            }

            $formattedItems = [];
            foreach ($staffItems as $item) {
                $addons = [];
                foreach ((array)($item['addons'] ?? []) as $addon) {
                    $addons[] = [
                        'id' => (int)($addon['id'] ?? 0),
                        'name' => (string)($addon['addon_name'] ?? '附加项'),
                        'quantity' => max(1, (int)($addon['quantity'] ?? 1)),
                    ];
                }
                $formattedItems[] = [
                    'id' => (int)($item['id'] ?? 0),
                    'package_name' => (string)($item['package_name'] ?? '服务项'),
                    'package_description' => (string)($item['package_description'] ?? ''),
                    'quantity' => max(1, (int)($item['quantity'] ?? 1)),
                    'confirm_status' => (int)($item['confirm_status'] ?? 0),
                    'addons' => $addons,
                ];
            }

            $orders[] = [
                'order_id' => $oid,
                'order_sn' => (string)($orderModel->order_sn ?? ''),
                'order_status' => $status,
                'order_status_desc' => self::getStatusDesc($status),
                'pay_status_desc' => self::getPayStatusDesc((int)$orderModel->pay_status),
                'current_pay_stage_desc' => (string)($paymentSummary['current_pay_stage_desc'] ?? ''),
                'service_date' => $targetDate,
                'contact_name' => (string)($orderModel->contact_name ?? ''),
                'contact_mobile' => (string)($orderModel->contact_mobile ?? ''),
                'contact_mobile_masked' => self::maskMobileNumber((string)($orderModel->contact_mobile ?? '')),
                'service_address' => (string)($orderModel->service_address ?? ''),
                'remark' => $remark,
                'start_service_time_text' => $startTs > 0 ? date('Y-m-d H:i', $startTs) : '',
                'has_pending_change' => OrderChange::hasPendingChange($oid) ? 1 : 0,
                'can_staff_confirm' => self::canStaffConfirmOrder($status, $staffItems),
                'can_staff_start' => ($status === Order::STATUS_PENDING_SERVICE && $canManageWholeOrder) ? 1 : 0,
                'can_staff_complete' => ($status === Order::STATUS_IN_SERVICE && $canManageWholeOrder && Order::canStaffCompleteService()) ? 1 : 0,
                'items' => $formattedItems,
                'teammates' => $teammatesMap[$oid] ?? [],
                'questionnaire' => $questionnaireMap[$oid] ?? null,
                'confirm_letter' => $orderLetterMap[$oid] ?? null,
            ];
        }

        $manualModels = ManualSchedule::where('staff_id', $staffId)
            ->where('schedule_date', $targetDate)
            ->where('status', '<>', ManualSchedule::STATUS_CANCELLED)
            ->order('id', 'asc')
            ->select();

        $manualIds = [];
        foreach ($manualModels as $manualModel) {
            $manualIds[] = (int)$manualModel->id;
        }
        $manualLetterMap = self::loadConfirmLetterMapByManual($manualIds, $staffId);

        $manualSchedules = [];
        foreach ($manualModels as $manualModel) {
            $detail = $manualModel->toDetail();
            $mid = (int)$manualModel->id;
            $mStatus = (int)$manualModel->status;
            if ($mStatus === ManualSchedule::STATUS_PENDING) {
                $pendingServiceCount++;
            } elseif ($mStatus === ManualSchedule::STATUS_COMPLETED) {
                $completedCount++;
            }
            $detail['customer_mobile_masked'] = self::maskMobileNumber((string)($detail['customer_mobile'] ?? ''));
            $detail['confirm_letter'] = $manualLetterMap[$mid] ?? null;
            $manualSchedules[] = $detail;
        }

        return [
            'date' => $targetDate,
            'today' => $today,
            'weekday_label' => $weekdayLabel,
            'upcoming_dates' => $upcomingDates,
            'summary' => [
                'total_count' => count($orders) + count($manualSchedules),
                'platform_count' => count($orders),
                'manual_count' => count($manualSchedules),
                'pending_confirm_count' => $pendingConfirmCount,
                'pending_service_count' => $pendingServiceCount,
                'in_service_count' => $inServiceCount,
                'completed_count' => $completedCount,
            ],
            'orders' => $orders,
            'manual_schedules' => $manualSchedules,
        ];
    }

    /**
     * @notes 获取服务人员电子名片数据
     */
    public static function businessCard(int $userId): array
    {
        $staff = Staff::where('user_id', $userId)->find();
        if (!$staff) {
            self::setError('未绑定服务人员');
            return [];
        }

        $staffId = (int)$staff->id;
        $row = Staff::injectServiceStatsToRow($staff->toArray());
        $displayPrice = StaffPriceService::getDisplayPriceByStaffId($staffId);

        $banner = StaffBanner::where('staff_id', $staffId)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->find();
        $cover = '';
        if ($banner) {
            $cover = (int)$banner->type === StaffBanner::TYPE_VIDEO
                ? ((string)($banner->cover_url ?? '') ?: (string)($banner->file_url ?? ''))
                : (string)($banner->file_url ?? '');
        }
        if ($cover === '') {
            $cover = (string)($row['avatar'] ?? '');
        }

        $tagIds = StaffTagReviewService::getEffectiveTagIds($staffId);
        $tags = StaffTagReviewService::getTagNames($tagIds);
        $rawMobile = (string)($staff->getData('mobile_full') ?: $staff->getData('mobile'));

        $workModels = StaffWork::where('staff_id', $staffId)
            ->where('audit_status', StaffWork::AUDIT_PASS)
            ->where('is_show', 1)
            ->whereNull('delete_time')
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->limit(4)
            ->select();
        $works = [];
        foreach ($workModels as $workModel) {
            $works[] = [
                'id' => (int)$workModel->id,
                'title' => (string)($workModel->title ?? ''),
                'cover' => (string)($workModel->cover ?? ''),
            ];
        }

        $packageModels = ServicePackage::where('staff_id', $staffId)
            ->where('is_show', 1)
            ->whereNull('delete_time')
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->limit(3)
            ->select();
        $packages = [];
        foreach ($packageModels as $pkgModel) {
            $duration = (int)($pkgModel->duration ?? 0);
            $packages[] = [
                'id' => (int)$pkgModel->id,
                'name' => (string)($pkgModel->name ?? ''),
                'price' => round((float)($pkgModel->price ?? 0), 2),
                'duration_desc' => $duration > 0 ? $duration . ' 小时' : '',
            ];
        }

        $today = date('Y-m-d');
        $end = date('Y-m-d', strtotime($today . ' +13 days'));
        $scheduleRows = Schedule::where('staff_id', $staffId)
            ->where('time_slot', Schedule::TIME_SLOT_ALL)
            ->whereBetween('schedule_date', [$today, $end])
            ->column('status', 'schedule_date');

        $weekNames = ['日', '一', '二', '三', '四', '五', '六'];
        $occupiedStatuses = [
            Schedule::STATUS_BOOKED,
            Schedule::STATUS_RESERVED,
            Schedule::STATUS_UNAVAILABLE,
        ];
        $availability = [];
        $availableDays14d = 0;
        for ($i = 0; $i < 14; $i++) {
            $ts = strtotime($today . ' +' . $i . ' days');
            $d = date('Y-m-d', $ts);
            $status = isset($scheduleRows[$d]) ? (int)$scheduleRows[$d] : Schedule::STATUS_AVAILABLE;
            $available = !in_array($status, $occupiedStatuses, true);
            if ($available) {
                $availableDays14d++;
            }
            $availability[] = [
                'date' => $d,
                'md_label' => date('m.d', $ts),
                'weekday' => '周' . ($weekNames[(int)date('w', $ts)] ?? ''),
                'available' => $available,
            ];
        }

        $qrcodeConfig = StaffScheduleConfirmLetterService::getGlobalQrcodeConfig();

        return [
            'staff' => [
                'id' => $staffId,
                'name' => (string)($row['name'] ?? ''),
                'avatar' => (string)($row['avatar'] ?? ''),
                'cover' => $cover,
                'category_name' => (string)($row['category_name'] ?? ''),
                'experience_years' => (int)($row['experience_years'] ?? 0),
                'rating' => round((float)($row['rating'] ?? 0), 1),
                'order_count' => (int)($row['order_count'] ?? 0),
                'price_text' => (string)($displayPrice['price_text'] ?? ''),
                'has_price' => (bool)($displayPrice['has_price'] ?? false),
                'city' => '',
                'tags' => array_values($tags),
                'intro' => trim((string)($row['profile'] ?? '')),
                'service_desc' => trim((string)($row['service_desc'] ?? '')),
                'mobile_masked' => self::maskMobileNumber($rawMobile),
            ],
            'works' => $works,
            'packages' => $packages,
            'upcoming_availability' => $availability,
            'available_days_14d' => $availableDays14d,
            'qrcode_image_url' => (string)($qrcodeConfig['schedule_qrcode_image_url'] ?? ''),
            'share_path' => '/packages/pkg-catalog/pages/staff-detail/staff-detail?id=' . $staffId,
        ];
    }

    /**
     * @notes 文案模板库列表
     */
    public static function copyTemplateLists(int $userId, array $params = []): array
    {
        $staff = Staff::where('user_id', $userId)->find();
        if (!$staff) {
            self::setError('未绑定服务人员');
            return [];
        }

        $staffId = (int)$staff->id;
        $scene = trim((string)($params['scene'] ?? ''));

        StaffCopyTemplate::ensureSystemTemplatesInitialized();

        $presetQuery = StaffCopyTemplate::where('staff_id', 0)
            ->where('status', StaffCopyTemplate::STATUS_ENABLED)
            ->order('sort', 'desc')
            ->order('id', 'asc');
        if ($scene !== '') {
            $presetQuery->where('scene', $scene);
        }

        $presets = [];
        foreach ($presetQuery->select() as $row) {
            $presets[] = self::formatSystemCopyTemplateItem($row);
        }

        $customQuery = StaffCopyTemplate::where('staff_id', $staffId)
            ->where('status', StaffCopyTemplate::STATUS_ENABLED)
            ->order('sort', 'desc')
            ->order('id', 'desc');
        if ($scene !== '') {
            $customQuery->where('scene', $scene);
        }

        $customs = [];
        foreach ($customQuery->select() as $row) {
            $customs[] = self::formatCopyTemplateItem($row);
        }

        $displayPrice = StaffPriceService::getDisplayPriceByStaffId($staffId);

        return [
            'scenes' => StaffCopyTemplate::getSceneOptions(),
            'presets' => $presets,
            'customs' => $customs,
            'staff_context' => [
                'staff_name' => (string)($staff->name ?? ''),
                'category_name' => (string)($staff->category_name ?? '婚礼服务'),
                'price_text' => (string)($displayPrice['price_text'] ?? '面议'),
            ],
            'recent_targets' => self::loadCopyTemplateRecentTargets($staffId),
        ];
    }

    /**
     * @notes 保存自定义文案模板
     */
    public static function copyTemplateSave(int $userId, array $params): array|false
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $id = (int)($params['id'] ?? 0);
        $scene = trim((string)($params['scene'] ?? ''));
        $title = trim((string)($params['title'] ?? ''));
        $content = trim((string)($params['content'] ?? ''));
        $sort = max(0, min(9999, (int)($params['sort'] ?? 0)));

        if (!isset(StaffCopyTemplate::SCENES[$scene])) {
            self::setError('话术场景参数错误');
            return false;
        }
        if ($title === '' || $content === '') {
            self::setError('请填写模板标题与正文');
            return false;
        }

        if (!StaffContentGuardService::textGuard($userId, [
            'title' => $title,
            'content' => $content,
        ], WechatSecurityService::SCENE_DYNAMIC)) {
            self::setError(StaffContentGuardService::getError());
            return false;
        }

        $now = time();
        if ($id > 0) {
            $template = StaffCopyTemplate::where('id', $id)
                ->where('staff_id', $staffId)
                ->find();
            if (!$template) {
                self::setError('模板不存在或无权限修改');
                return false;
            }
            $template->save([
                'scene' => $scene,
                'title' => $title,
                'content' => $content,
                'sort' => $sort,
                'update_time' => $now,
            ]);
        } else {
            $template = StaffCopyTemplate::create([
                'staff_id' => $staffId,
                'scene' => $scene,
                'title' => $title,
                'content' => $content,
                'sort' => $sort,
                'use_count' => 0,
                'status' => StaffCopyTemplate::STATUS_ENABLED,
                'create_time' => $now,
                'update_time' => $now,
            ]);
        }

        return self::formatCopyTemplateItem($template);
    }

    /**
     * @notes 删除自定义文案模板
     */
    public static function copyTemplateDelete(int $userId, int $id): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $template = StaffCopyTemplate::where('id', $id)
            ->where('staff_id', $staffId)
            ->find();
        if (!$template) {
            self::setError('模板不存在或无权限删除');
            return false;
        }

        $template->delete();
        return true;
    }

    /**
     * @notes 记录文案模板使用次数
     */
    public static function copyTemplateUse(int $userId, int $id): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $template = StaffCopyTemplate::where('id', $id)
            ->where(function ($query) use ($staffId) {
                $query->where('staff_id', $staffId)
                    ->whereOr(function ($sub) {
                        $sub->where('staff_id', 0)
                            ->where('status', StaffCopyTemplate::STATUS_ENABLED);
                    });
            })
            ->find();
        if (!$template) {
            self::setError('模板不存在或无权限操作');
            return false;
        }

        StaffCopyTemplate::where('id', (int)$template->id)
            ->inc('use_count', 1)
            ->update(['update_time' => time()]);

        return true;
    }

    /**
     * @notes 新人档案列表
     */
    public static function coupleLists(int $userId, array $params = []): array
    {
        self::setError('');
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }
        return StaffCoupleWorkbenchService::coupleLists($staffId, $params);
    }

    /**
     * @notes 新人档案详情
     */
    public static function coupleDetail(int $userId, string $sourceType, int $targetId): array
    {
        self::setError('');
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }
        return StaffCoupleWorkbenchService::coupleDetail($staffId, $sourceType, $targetId);
    }

    /**
     * @notes 发送或补发新人问卷
     */
    public static function coupleSendQuestionnaire(int $userId, int $orderId): array|false
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        return StaffCoupleWorkbenchService::coupleSendQuestionnaire($staffId, $orderId);
    }

    /**
     * @notes 保存服务手记
     */
    public static function serviceNoteSave(int $userId, array $params): array|false
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        return StaffCoupleWorkbenchService::serviceNoteSave($userId, $staffId, $params);
    }

    /**
     * @notes 置顶/取消置顶服务手记
     */
    public static function serviceNoteTogglePin(int $userId, int $id): array|false
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        return StaffCoupleWorkbenchService::serviceNoteTogglePin($staffId, $id);
    }

    /**
     * @notes 删除服务手记
     */
    public static function serviceNoteDelete(int $userId, int $id): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        return StaffCoupleWorkbenchService::serviceNoteDelete($staffId, $id);
    }

    /**
     * @notes 跟进提醒列表
     */
    public static function followupLists(int $userId, array $params = []): array
    {
        self::setError('');
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }
        return StaffCoupleWorkbenchService::followupLists($staffId, $params);
    }

    /**
     * @notes 保存跟进提醒
     */
    public static function followupSave(int $userId, array $params): array|false
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        return StaffCoupleWorkbenchService::followupSave($userId, $staffId, $params);
    }

    /**
     * @notes 更新跟进提醒状态
     */
    public static function followupAction(int $userId, int $id, string $action): array|false
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        return StaffCoupleWorkbenchService::followupAction($staffId, $id, $action);
    }

    /**
     * @notes 删除跟进提醒
     */
    public static function followupDelete(int $userId, int $id): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        return StaffCoupleWorkbenchService::followupDelete($staffId, $id);
    }

    /**
     * @notes 一键初始化标准婚礼 Checklist
     */
    public static function checklistInitPresets(int $userId, string $sourceType, int $targetId): array|false
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        return StaffCoupleWorkbenchService::checklistInitPresets($staffId, $sourceType, $targetId);
    }

    /**
     * @notes 保存婚礼 Checklist 事项
     */
    public static function checklistSave(int $userId, array $params): array|false
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        return StaffCoupleWorkbenchService::checklistSave($userId, $staffId, $params);
    }

    /**
     * @notes 切换婚礼 Checklist 完成状态
     */
    public static function checklistToggle(int $userId, int $id): array|false
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        return StaffCoupleWorkbenchService::checklistToggle($staffId, $id);
    }

    /**
     * @notes 删除婚礼 Checklist 事项
     */
    public static function checklistDelete(int $userId, int $id): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        return StaffCoupleWorkbenchService::checklistDelete($staffId, $id);
    }

    /**
     * @notes 我的团队概览与近14天档期热力视图
     */
    public static function teamOverview(int $userId): array
    {
        self::setError('');
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }
        return StaffTeamWorkbenchService::teamOverview($staffId);
    }

    /**
     * @notes 队长更新团队信息
     */
    public static function teamUpdateInfo(int $userId, array $params): array|false
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        return StaffTeamWorkbenchService::teamUpdateInfo($userId, $staffId, $params);
    }

    /**
     * @notes 搜索可添加进团队的候选服务人员
     */
    public static function teamMemberCandidates(int $userId, string $keyword = ''): array|false
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        return StaffTeamWorkbenchService::teamMemberCandidates($staffId, $keyword);
    }

    /**
     * @notes 队长添加成员至团队
     */
    public static function teamMemberAdd(int $userId, int $targetStaffId): array|false
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        return StaffTeamWorkbenchService::teamMemberAdd($staffId, $targetStaffId);
    }

    /**
     * @notes 队长启停团队成员状态
     */
    public static function teamMemberToggleStatus(int $userId, int $memberId, int $status): array|false
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        return StaffTeamWorkbenchService::teamMemberToggleStatus($staffId, $memberId, $status);
    }

    /**
     * @notes 队长移除团队成员
     */
    public static function teamMemberRemove(int $userId, int $memberId): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        return StaffTeamWorkbenchService::teamMemberRemove($staffId, $memberId);
    }

    private static function loadServiceDayTeammatesMap(array $orderIds, int $staffId): array
    {
        if (empty($orderIds)) {
            return [];
        }

        $rows = OrderItem::whereIn('order_id', $orderIds)
            ->where('staff_id', '>', 0)
            ->where('staff_id', '<>', $staffId)
            ->whereIn('item_type', self::getStaffStatItemTypes())
            ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->field('order_id, staff_id, staff_name, package_name, confirm_status')
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
        $staffInfoMap = [];
        foreach ($staffRows as $sr) {
            $staffInfoMap[(int)$sr['id']] = [
                'name' => (string)($sr['name'] ?? ''),
                'category_name' => (string)($sr['category_name'] ?? ''),
            ];
        }

        $map = [];
        foreach ($rows as $row) {
            $oid = (int)($row['order_id'] ?? 0);
            $sid = (int)($row['staff_id'] ?? 0);
            $info = $staffInfoMap[$sid] ?? ['name' => '', 'category_name' => ''];
            $map[$oid][] = [
                'staff_id' => $sid,
                'staff_name' => trim((string)($row['staff_name'] ?? '')) ?: $info['name'],
                'category_name' => $info['category_name'] ?: '协作人员',
                'package_name' => (string)($row['package_name'] ?? ''),
                'confirm_status' => (int)($row['confirm_status'] ?? 0),
            ];
        }
        return $map;
    }

    private static function loadServiceDayQuestionnaireMap(array $orderIds, int $staffId): array
    {
        if (empty($orderIds)) {
            return [];
        }

        $tasks = CoupleQuestionnaireTask::whereIn('order_id', $orderIds)
            ->where('staff_id', $staffId)
            ->order('id', 'desc')
            ->select();

        $taskByOrder = [];
        $submittedTaskIds = [];
        foreach ($tasks as $task) {
            $oid = (int)$task->order_id;
            if (isset($taskByOrder[$oid])) {
                continue;
            }
            $taskByOrder[$oid] = $task;
            if ((int)$task->status === CoupleQuestionnaireTask::STATUS_SUBMITTED) {
                $submittedTaskIds[] = (int)$task->id;
            }
        }

        $answerMap = [];
        if (!empty($submittedTaskIds)) {
            $answers = CoupleQuestionnaireAnswer::whereIn('task_id', $submittedTaskIds)
                ->order('id', 'desc')
                ->select();
            foreach ($answers as $ans) {
                $tid = (int)$ans->task_id;
                if (!isset($answerMap[$tid])) {
                    $answerMap[$tid] = $ans;
                }
            }
        }

        $priorityMap = [
            '避讳内容' => 0,
            '仪式风格' => 1,
            '新人基础信息' => 2,
        ];

        $result = [];
        foreach ($taskByOrder as $oid => $task) {
            $status = (int)$task->status;
            $statusDesc = match ($status) {
                CoupleQuestionnaireTask::STATUS_SUBMITTED => '已填写',
                CoupleQuestionnaireTask::STATUS_CANCELLED => '已取消',
                CoupleQuestionnaireTask::STATUS_VIEWED => '已查看',
                CoupleQuestionnaireTask::STATUS_EXPIRED => '已过期',
                default => '待填写',
            };
            $subTs = (int)($task->getData('submitted_time') ?: $task->getData('submit_time') ?: 0);

            $highlights = [];
            $answerModel = $answerMap[(int)$task->id] ?? null;
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
                $items = [];
                foreach ($rawAnswers as $idx => $ansRow) {
                    if (!is_array($ansRow)) {
                        continue;
                    }
                    $val = $ansRow['value'] ?? '';
                    $valText = is_array($val) ? implode('、', array_filter(array_map('strval', $val))) : trim((string)$val);
                    if ($valText === '') {
                        continue;
                    }
                    $k = (string)($ansRow['key'] ?? '');
                    $cat = $catMap[$k] ?? '问卷详情';
                    $items[] = [
                        'category' => $cat,
                        'title' => (string)($ansRow['title'] ?? ''),
                        'answer' => $valText,
                        '_priority' => $priorityMap[$cat] ?? 3,
                        '_index' => (int)$idx,
                    ];
                }

                usort($items, static function (array $a, array $b): int {
                    return ($a['_priority'] <=> $b['_priority']) ?: ($a['_index'] <=> $b['_index']);
                });

                foreach (array_slice($items, 0, 8) as $it) {
                    $highlights[] = [
                        'category' => $it['category'],
                        'title' => $it['title'],
                        'answer' => $it['answer'],
                    ];
                }
            }

            $result[$oid] = [
                'task_id' => (int)$task->id,
                'status' => $status,
                'status_desc' => $statusDesc,
                'send_status' => (int)$task->send_status,
                'submitted_time_text' => $subTs > 0 ? date('Y-m-d H:i', $subTs) : '',
                'answer_count' => count($highlights),
                'highlights' => $highlights,
            ];
        }

        return $result;
    }

    private static function loadConfirmLetterMapByOrder(array $orderIds, int $staffId): array
    {
        if (empty($orderIds)) {
            return [];
        }

        $letters = StaffScheduleConfirmLetter::whereIn('order_id', $orderIds)
            ->where('staff_id', $staffId)
            ->where('is_outdated', StaffScheduleConfirmLetter::STATUS_ACTIVE)
            ->order('id', 'desc')
            ->select();

        $map = [];
        foreach ($letters as $letter) {
            $oid = (int)$letter->order_id;
            if (isset($map[$oid])) {
                continue;
            }
            $map[$oid] = self::formatConfirmLetterBrief($letter);
        }
        return $map;
    }

    private static function loadConfirmLetterMapByManual(array $manualIds, int $staffId): array
    {
        if (empty($manualIds)) {
            return [];
        }

        $letters = StaffScheduleConfirmLetter::whereIn('manual_schedule_id', $manualIds)
            ->where('staff_id', $staffId)
            ->where('is_outdated', StaffScheduleConfirmLetter::STATUS_ACTIVE)
            ->order('id', 'desc')
            ->select();

        $map = [];
        foreach ($letters as $letter) {
            $mid = (int)$letter->manual_schedule_id;
            if (isset($map[$mid])) {
                continue;
            }
            $map[$mid] = self::formatConfirmLetterBrief($letter);
        }
        return $map;
    }

    private static function formatConfirmLetterBrief(StaffScheduleConfirmLetter $letter): array
    {
        $fullPath = trim((string)($letter->full_image_url ?? ''));
        $thumbPath = trim((string)($letter->thumb_image_url ?? ''));
        return [
            'letter_id' => (int)$letter->id,
            'version' => (int)$letter->version,
            'full_image_url' => $fullPath !== '' ? \app\common\service\FileService::getFileUrl($fullPath) : '',
            'thumb_image_url' => $thumbPath !== '' ? \app\common\service\FileService::getFileUrl($thumbPath) : '',
        ];
    }

    private static function formatCopyTemplateItem(StaffCopyTemplate $row): array
    {
        $scene = (string)$row->scene;
        return [
            'id' => (int)$row->id,
            'is_system' => false,
            'scene' => $scene,
            'scene_label' => StaffCopyTemplate::SCENES[$scene] ?? $scene,
            'title' => (string)$row->title,
            'content' => (string)$row->content,
            'sort' => (int)$row->sort,
            'use_count' => (int)$row->use_count,
            'update_time_text' => self::formatTimestampText($row->getData('update_time') ?: $row->update_time),
        ];
    }

    private static function formatSystemCopyTemplateItem(StaffCopyTemplate $row): array
    {
        $scene = (string)$row->scene;
        return [
            'id' => (int)$row->id,
            'preset_key' => 'system_' . (int)$row->id,
            'is_system' => true,
            'scene' => $scene,
            'scene_label' => StaffCopyTemplate::SCENES[$scene] ?? $scene,
            'title' => (string)$row->title,
            'content' => (string)$row->content,
            'sort' => (int)$row->sort,
            'use_count' => (int)$row->use_count,
            'update_time_text' => self::formatTimestampText($row->getData('update_time') ?: $row->update_time),
        ];
    }

    private static function loadCopyTemplateRecentTargets(int $staffId): array
    {
        $targets = [];

        $orders = self::buildStaffFollowableOrderBaseQuery($staffId)
            ->with([
                'items' => function ($q) use ($staffId) {
                    $q->field('id, order_id, staff_id, package_name, service_date, item_status, item_type')
                        ->where('staff_id', $staffId)
                        ->whereIn('item_type', self::getStaffStatItemTypes())
                        ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                        ->order('id', 'asc');
                },
            ])
            ->order('id', 'desc')
            ->limit(6)
            ->select();

        foreach ($orders as $orderModel) {
            $items = is_object($orderModel->items) && method_exists($orderModel->items, 'toArray')
                ? $orderModel->items->toArray()
                : (array)($orderModel->items ?? []);
            $firstItem = $items[0] ?? [];
            $serviceDate = (string)($firstItem['service_date'] ?? ($orderModel->service_date ?? ''));
            $packageName = (string)($firstItem['package_name'] ?? '婚礼服务');
            $customerName = trim((string)($orderModel->contact_name ?? '')) ?: '新人客户';
            $targets[] = [
                'target_key' => 'order-' . (int)$orderModel->id,
                'source' => 'platform',
                'order_id' => (int)$orderModel->id,
                'manual_schedule_id' => 0,
                'title' => ($serviceDate !== '' ? $serviceDate . ' · ' : '') . $customerName,
                'customer_name' => $customerName,
                'service_date' => $serviceDate,
                'service_address' => (string)($orderModel->service_address ?? ''),
                'package_name' => $packageName,
            ];
        }

        $manuals = ManualSchedule::where('staff_id', $staffId)
            ->where('status', '<>', ManualSchedule::STATUS_CANCELLED)
            ->order('schedule_date', 'desc')
            ->order('id', 'desc')
            ->limit(4)
            ->select();

        foreach ($manuals as $manualModel) {
            $customerName = trim((string)($manualModel->customer_name ?? '')) ?: '线下客户';
            $serviceDate = (string)($manualModel->schedule_date ?? '');
            $addr = trim(implode(' ', array_filter([
                (string)($manualModel->region_name ?? ''),
                (string)($manualModel->service_address ?? ''),
            ])));
            $targets[] = [
                'target_key' => 'manual-' . (int)$manualModel->id,
                'source' => 'manual',
                'order_id' => 0,
                'manual_schedule_id' => (int)$manualModel->id,
                'title' => ($serviceDate !== '' ? $serviceDate . ' · ' : '') . $customerName . '（线下）',
                'customer_name' => $customerName,
                'service_date' => $serviceDate,
                'service_address' => $addr,
                'package_name' => (string)($manualModel->service_name ?? '线下服务'),
            ];
        }

        return array_slice($targets, 0, 10);
    }
}
