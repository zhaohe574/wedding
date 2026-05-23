<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 新人问卷服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\aftersale\AfterSaleTicket;
use app\common\model\notification\Notification;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\questionnaire\CoupleQuestionBank;
use app\common\model\questionnaire\CoupleQuestionnaire;
use app\common\model\questionnaire\CoupleQuestionnaireAnswer;
use app\common\model\questionnaire\CoupleQuestionnaireTask;
use app\common\model\questionnaire\CoupleQuestionnaireVersion;
use think\facade\Db;
use think\facade\Log;

/**
 * 新人问卷服务
 */
class CoupleQuestionnaireService
{
    private const DEFAULT_TITLE = '新人婚礼资料问卷';
    private const DEFAULT_DESCRIPTION = '请填写有助于仪式策划的信息，服务人员会据此完善婚礼流程与表达。';

    private const QUESTION_TYPES = ['text', 'textarea', 'single', 'multiple', 'rating'];
    private const QUESTION_CATEGORIES = [
        '新人基础信息',
        '职业与性格',
        '恋爱经过',
        '求婚故事',
        '家庭成员',
        '仪式风格',
        '特殊纪念',
        '避讳内容',
        '补充说明',
    ];

    /**
     * 获取基础题库。
     */
    public static function bankList(array $params = []): array
    {
        $query = CoupleQuestionBank::whereNull('delete_time');
        if (($params['status'] ?? '') !== '') {
            $query->where('status', (int)$params['status']);
        }
        if (!empty($params['category'])) {
            $query->where('category', trim((string)$params['category']));
        }

        return $query->order('category_sort', 'desc')
            ->order('sort', 'desc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 保存题库题目。
     */
    public static function saveBankQuestion(array $params): array
    {
        $question = self::normalizeQuestion([
            'bank_id' => (int)($params['id'] ?? 0),
            'category' => (string)($params['category'] ?? ''),
            'type' => (string)($params['type'] ?? 'textarea'),
            'title' => (string)($params['title'] ?? ''),
            'placeholder' => (string)($params['placeholder'] ?? ''),
            'options' => $params['options'] ?? [],
            'required' => (int)($params['required'] ?? 0),
            'sort' => (int)($params['sort'] ?? 0),
        ], false);

        $payload = [
            'category' => $question['category'],
            'category_sort' => (int)($params['category_sort'] ?? 0),
            'type' => $question['type'],
            'title' => $question['title'],
            'placeholder' => $question['placeholder'],
            'options' => self::encodeJsonArray($question['options']),
            'required' => (int)$question['required'],
            'sort' => (int)$question['sort'],
            'status' => (int)($params['status'] ?? CoupleQuestionBank::STATUS_ENABLED),
            'update_time' => time(),
        ];

        $id = (int)($params['id'] ?? 0);
        if ($id > 0) {
            CoupleQuestionBank::where('id', $id)->update($payload);
        } else {
            $payload['create_time'] = time();
            $model = CoupleQuestionBank::create($payload);
            $id = (int)$model->id;
        }

        return ['id' => $id];
    }

    /**
     * 删除题库题目。
     */
    public static function deleteBankQuestion(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        return CoupleQuestionBank::where('id', $id)->update([
            'delete_time' => time(),
            'update_time' => time(),
        ]) > 0;
    }

    /**
     * 获取服务人员问卷配置。
     */
    public static function getStaffConfig(int $staffId): array
    {
        $config = self::ensureStaffConfig($staffId);
        $latestVersion = self::getLatestVersion($staffId);

        $data = $config->toArray();
        $data['draft_questions'] = self::normalizeQuestions($data['draft_questions'] ?? []);
        $data['latest_version'] = $latestVersion ? $latestVersion->toArray() : null;
        $data['bank_questions'] = self::bankList(['status' => CoupleQuestionBank::STATUS_ENABLED]);
        $data['categories'] = self::QUESTION_CATEGORIES;
        $data['question_types'] = self::questionTypeOptions();
        $data['staff'] = Db::name('staff')
            ->where('id', $staffId)
            ->whereNull('delete_time')
            ->field('id,sn,name,mobile,status')
            ->find() ?: null;
        $data['pending_task_count'] = CoupleQuestionnaireTask::where('staff_id', $staffId)
            ->where('status', CoupleQuestionnaireTask::STATUS_PENDING)
            ->count();

        return $data;
    }

    /**
     * 管理员查看全部服务人员问卷配置。
     */
    public static function adminConfigList(array $params = []): array
    {
        [$page, $limit] = self::resolvePageParams($params);
        $query = Db::name('staff')
            ->alias('s')
            ->leftJoin('couple_questionnaire q', 'q.staff_id = s.id AND q.delete_time IS NULL')
            ->whereNull('s.delete_time');

        if (!empty($params['staff_id'])) {
            $query->where('s.id', (int)$params['staff_id']);
        }
        if (($params['status'] ?? '') !== '') {
            $query->where('q.status', (int)$params['status']);
        }
        if (($params['push_mode'] ?? '') !== '') {
            $query->where('q.push_mode', (int)$params['push_mode']);
        }
        if (($params['published'] ?? '') !== '') {
            if ((int)$params['published'] === 1) {
                $query->where('q.published_version_no', '>', 0);
            } else {
                $query->where(function ($query) {
                    $query->whereNull('q.id')->whereOr('q.published_version_no', '<=', 0);
                });
            }
        }
        if (!empty($params['keyword'])) {
            $keyword = trim((string)$params['keyword']);
            $query->whereLike('s.name|s.sn|s.mobile|q.title', '%' . $keyword . '%');
        }

        $total = (int)(clone $query)->count('s.id');
        $lists = $query
            ->field('s.id AS staff_id,s.sn AS staff_sn,s.name AS staff_name,s.mobile AS staff_mobile,s.status AS staff_status,'
                . 'q.id AS questionnaire_id,q.title,q.description,q.push_mode,q.status AS questionnaire_status,'
                . 'q.current_version_id,q.published_version_no,q.published_time,q.update_time')
            ->order('s.id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $staffIds = array_values(array_filter(array_map(static fn ($item): int => (int)($item['staff_id'] ?? 0), $lists)));
        $pendingCountMap = self::pendingTaskCountMap($staffIds);
        foreach ($lists as &$item) {
            $item = self::formatConfigListItem($item);
            $item['pending_task_count'] = (int)($pendingCountMap[(int)$item['staff_id']] ?? 0);
        }
        unset($item);

        return [
            'lists' => $lists,
            'total' => $total,
            'count' => $total,
            'page' => $page,
            'limit' => $limit,
            'page_no' => $page,
            'page_size' => $limit,
        ];
    }

    /**
     * 保存服务人员问卷草稿。
     */
    public static function saveStaffConfig(int $staffId, array $params): bool
    {
        $config = self::ensureStaffConfig($staffId);
        $questions = self::normalizeQuestions($params['questions'] ?? []);

        $config->save([
            'title' => self::limitText((string)($params['title'] ?? self::DEFAULT_TITLE), 120),
            'description' => self::limitText((string)($params['description'] ?? self::DEFAULT_DESCRIPTION), 500),
            'push_mode' => (int)($params['push_mode'] ?? CoupleQuestionnaire::PUSH_MODE_AUTO) === CoupleQuestionnaire::PUSH_MODE_MANUAL
                ? CoupleQuestionnaire::PUSH_MODE_MANUAL
                : CoupleQuestionnaire::PUSH_MODE_AUTO,
            'status' => (int)($params['status'] ?? CoupleQuestionnaire::STATUS_ENABLED) === CoupleQuestionnaire::STATUS_DISABLED
                ? CoupleQuestionnaire::STATUS_DISABLED
                : CoupleQuestionnaire::STATUS_ENABLED,
            'draft_questions' => $questions,
            'update_time' => time(),
        ]);

        return true;
    }

    /**
     * 发布服务人员问卷新版本。
     */
    public static function publishStaffVersion(int $staffId, array $params): array
    {
        return Db::transaction(function () use ($staffId, $params) {
            self::saveStaffConfig($staffId, $params);
            $config = self::ensureStaffConfig($staffId);
            $questions = self::normalizeQuestions($config->draft_questions);
            if (empty($questions)) {
                throw new \RuntimeException('请至少配置一道问卷题目');
            }

            $versionNo = (int)CoupleQuestionnaireVersion::where('questionnaire_id', (int)$config->id)->max('version_no') + 1;
            $version = CoupleQuestionnaireVersion::create([
                'questionnaire_id' => (int)$config->id,
                'staff_id' => $staffId,
                'version_no' => $versionNo,
                'title' => (string)$config->title,
                'description' => (string)$config->description,
                'questions' => $questions,
                'create_time' => time(),
            ]);

            $config->current_version_id = (int)$version->id;
            $config->published_version_no = $versionNo;
            $config->published_time = time();
            $config->update_time = time();
            $config->save();

            self::refreshPendingTasksForStaff($staffId);

            return ['version_id' => (int)$version->id, 'version_no' => $versionNo];
        });
    }

    /**
     * 获取服务人员问卷任务。
     */
    public static function staffTaskList(int $staffId, array $params = []): array
    {
        [$page, $limit] = self::resolvePageParams($params);
        $query = CoupleQuestionnaireTask::where('staff_id', $staffId);
        if (($params['status'] ?? '') !== '') {
            $query->where('status', (int)$params['status']);
        }
        if (($params['send_status'] ?? '') !== '') {
            $query->where('send_status', (int)$params['send_status']);
        }

        $total = (clone $query)->count();
        $lists = $query->with(['order', 'user'])
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item = self::formatTask($item);
        }

        return [
            'lists' => $lists,
            'total' => $total,
            'count' => $total,
            'page' => $page,
            'limit' => $limit,
            'page_no' => $page,
            'page_size' => $limit,
        ];
    }

    /**
     * 管理员查看全部问卷任务。
     */
    public static function adminTaskList(array $params = []): array
    {
        [$page, $limit] = self::resolvePageParams($params);
        $query = CoupleQuestionnaireTask::whereNull('delete_time');
        if (!empty($params['staff_id'])) {
            $query->where('staff_id', (int)$params['staff_id']);
        }
        if (($params['status'] ?? '') !== '') {
            $query->where('status', (int)$params['status']);
        }
        if (($params['send_status'] ?? '') !== '') {
            $query->where('send_status', (int)$params['send_status']);
        }
        if (!empty($params['keyword'])) {
            $keyword = trim((string)$params['keyword']);
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('task_sn|title_snapshot', '%' . $keyword . '%');
                if (ctype_digit($keyword)) {
                    $query->whereOr('order_id', (int)$keyword);
                }
            });
        }

        $total = (int)(clone $query)->count();
        $lists = $query->with(['order', 'user', 'staff'])
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item = self::formatTask($item);
        }
        unset($item);

        return [
            'lists' => $lists,
            'total' => $total,
            'count' => $total,
            'page' => $page,
            'limit' => $limit,
            'page_no' => $page,
            'page_size' => $limit,
        ];
    }

    /**
     * 用户端问卷任务列表。
     */
    public static function userTaskList(int $userId, array $params = []): array
    {
        [$page, $limit] = self::resolvePageParams($params);
        $query = CoupleQuestionnaireTask::where('user_id', $userId)
            ->where('send_status', CoupleQuestionnaireTask::SEND_STATUS_SENT);
        if (($params['status'] ?? '') !== '') {
            $query->where('status', (int)$params['status']);
        }
        if (!empty($params['order_id'])) {
            $query->where('order_id', (int)$params['order_id']);
        }

        $total = (clone $query)->count();
        $lists = $query->with(['order', 'staff'])
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item = self::formatTask($item);
        }

        return [
            'lists' => $lists,
            'total' => $total,
            'count' => $total,
            'page' => $page,
            'limit' => $limit,
            'page_no' => $page,
            'page_size' => $limit,
        ];
    }

    /**
     * 用户端问卷详情。
     */
    public static function userTaskDetail(int $taskId, int $userId): array
    {
        $task = CoupleQuestionnaireTask::with(['order', 'staff'])
            ->where('id', $taskId)
            ->where('user_id', $userId)
            ->find();
        if (!$task) {
            return [];
        }

        return self::buildTaskDetail($task);
    }

    /**
     * 服务人员端问卷详情。
     */
    public static function staffTaskDetail(int $taskId, int $staffId): array
    {
        $task = CoupleQuestionnaireTask::with(['order', 'user'])
            ->where('id', $taskId)
            ->where('staff_id', $staffId)
            ->find();
        if (!$task) {
            return [];
        }

        return self::buildTaskDetail($task);
    }

    /**
     * 管理员查看问卷任务详情。
     */
    public static function adminTaskDetail(int $taskId): array
    {
        $task = CoupleQuestionnaireTask::with(['order', 'user', 'staff'])
            ->where('id', $taskId)
            ->find();
        if (!$task) {
            return [];
        }

        return self::buildTaskDetail($task);
    }

    /**
     * 提交用户问卷。
     */
    public static function submitUserAnswers(int $taskId, int $userId, array $answers): bool
    {
        return Db::transaction(function () use ($taskId, $userId, $answers) {
            $task = CoupleQuestionnaireTask::where('id', $taskId)
                ->where('user_id', $userId)
                ->lock(true)
                ->find();
            if (!$task) {
                throw new \RuntimeException('问卷不存在');
            }
            if ((int)$task->status !== CoupleQuestionnaireTask::STATUS_PENDING) {
                throw new \RuntimeException('问卷已提交或不可填写');
            }

            $questions = self::resolveTaskQuestions($task);
            $cleanAnswers = self::normalizeAnswers($questions, $answers);

            CoupleQuestionnaireAnswer::create([
                'task_id' => (int)$task->id,
                'order_id' => (int)$task->order_id,
                'questionnaire_id' => (int)$task->questionnaire_id,
                'version_id' => (int)$task->version_id,
                'version_no' => (int)$task->version_no,
                'user_id' => $userId,
                'staff_id' => (int)$task->staff_id,
                'questions_snapshot' => $questions,
                'answers' => $cleanAnswers,
                'create_time' => time(),
            ]);

            $task->status = CoupleQuestionnaireTask::STATUS_SUBMITTED;
            $task->questions_snapshot = $questions;
            $task->submitted_time = time();
            $task->submit_time = time();
            $task->update_time = time();
            $task->save();

            return true;
        });
    }

    /**
     * 订单首笔支付后生成新人问卷任务。
     */
    public static function createTaskAfterOrderPendingService(int $orderId, bool $allowAutoPush = true): ?CoupleQuestionnaireTask
    {
        try {
            $task = Db::transaction(function () use ($orderId) {
                $order = Order::where('id', $orderId)->lock(true)->find();
                if (!$order || (int)$order->order_status !== Order::STATUS_PENDING_SERVICE) {
                    return null;
                }

                $exists = CoupleQuestionnaireTask::where('order_id', $orderId)->lock(true)->find();
                if ($exists) {
                    return $exists;
                }

                $mainItem = AfterSaleTicket::getMainOrderItem($orderId);
                $staffId = (int)($mainItem['staff_id'] ?? 0);
                if ($staffId <= 0) {
                    Log::info('新人问卷跳过：订单无主服务人员，order_id=' . $orderId);
                    return null;
                }

                $config = CoupleQuestionnaire::where('staff_id', $staffId)
                    ->where('status', CoupleQuestionnaire::STATUS_ENABLED)
                    ->find();
                if (!$config) {
                    Log::info('新人问卷跳过：服务人员未配置问卷，staff_id=' . $staffId);
                    return null;
                }

                $version = self::getLatestVersion($staffId);
                if (!$version || empty($version->questions)) {
                    Log::info('新人问卷跳过：服务人员无可用问卷版本，staff_id=' . $staffId);
                    return null;
                }

                return CoupleQuestionnaireTask::create([
                    'task_sn' => CoupleQuestionnaireTask::generateTaskSn(),
                    'order_id' => (int)$order->id,
                    'order_item_id' => (int)($mainItem['id'] ?? 0),
                    'user_id' => (int)$order->user_id,
                    'staff_id' => $staffId,
                    'questionnaire_id' => (int)$config->id,
                    'version_id' => (int)$version->id,
                    'version_no' => (int)$version->version_no,
                    'title_snapshot' => (string)$version->title,
                    'description_snapshot' => (string)$version->description,
                    'questions_snapshot' => [],
                    'status' => CoupleQuestionnaireTask::STATUS_PENDING,
                    'send_status' => CoupleQuestionnaireTask::SEND_STATUS_PENDING,
                    'push_mode' => (int)$config->push_mode,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            });

            if (
                $task
                && $allowAutoPush
                && (int)$task->push_mode === CoupleQuestionnaire::PUSH_MODE_AUTO
                && (int)$task->send_status === CoupleQuestionnaireTask::SEND_STATUS_PENDING
            ) {
                self::sendTaskNotice((int)$task->id, false);
            }

            return $task;
        } catch (\Throwable $e) {
            Log::error('新人问卷任务生成失败：' . $e->getMessage());
            return null;
        }
    }

    /**
     * 手动发送问卷。
     */
    public static function manualSendTask(int $taskId, int $staffId): bool
    {
        $task = CoupleQuestionnaireTask::where('id', $taskId)
            ->where('staff_id', $staffId)
            ->find();
        if (!$task) {
            throw new \RuntimeException('问卷任务不存在');
        }
        if ((int)$task->status !== CoupleQuestionnaireTask::STATUS_PENDING) {
            throw new \RuntimeException('当前问卷已提交或不可发送');
        }

        self::sendTaskNotice($taskId, true);
        return true;
    }

    /**
     * 管理员手动发送问卷。
     */
    public static function adminSendTask(int $taskId): bool
    {
        $task = CoupleQuestionnaireTask::where('id', $taskId)->find();
        if (!$task) {
            throw new \RuntimeException('问卷任务不存在');
        }
        if ((int)$task->status !== CoupleQuestionnaireTask::STATUS_PENDING) {
            throw new \RuntimeException('当前问卷已提交或不可发送');
        }

        self::sendTaskNotice($taskId, true);
        return true;
    }

    /**
     * 发送任务通知。
     */
    public static function sendTaskNotice(int $taskId, bool $force = false): void
    {
        $task = CoupleQuestionnaireTask::with(['order', 'staff'])->find($taskId);
        if (!$task || (int)$task->user_id <= 0) {
            return;
        }

        CoupleQuestionnaireTask::where('id', (int)$task->id)->update([
            'send_status' => CoupleQuestionnaireTask::SEND_STATUS_SENT,
            'send_time' => (int)($task->send_time ?? 0) > 0 ? (int)$task->send_time : time(),
            'last_send_time' => time(),
            'send_count' => Db::raw('send_count + 1'),
            'update_time' => time(),
        ]);

        $orderSn = (string)($task->order->order_sn ?? '');
        $staffName = (string)($task->staff->name ?? '服务人员');
        $title = '请填写新人问卷';
        $content = sprintf('订单%s的婚礼仪式资料问卷已准备好，请补充新人信息，方便%s完善仪式策划。', $orderSn, $staffName);

        StationNotificationService::sendUnique(
            (int)$task->user_id,
            Notification::TYPE_ORDER,
            $title,
            $content,
            StationNotificationService::TARGET_COUPLE_QUESTIONNAIRE,
            (int)$task->id
        );
    }

    private static function ensureStaffConfig(int $staffId): CoupleQuestionnaire
    {
        $config = CoupleQuestionnaire::where('staff_id', $staffId)->find();
        if ($config) {
            return $config;
        }

        return CoupleQuestionnaire::create([
            'staff_id' => $staffId,
            'title' => self::DEFAULT_TITLE,
            'description' => self::DEFAULT_DESCRIPTION,
            'push_mode' => CoupleQuestionnaire::PUSH_MODE_AUTO,
            'status' => CoupleQuestionnaire::STATUS_ENABLED,
            'draft_questions' => self::defaultQuestionsFromBank(),
            'current_version_id' => 0,
            'create_time' => time(),
            'update_time' => time(),
        ]);
    }

    private static function getLatestVersion(int $staffId): ?CoupleQuestionnaireVersion
    {
        return CoupleQuestionnaireVersion::where('staff_id', $staffId)
            ->order('id', 'desc')
            ->find();
    }

    private static function refreshPendingTasksForStaff(int $staffId): void
    {
        $version = self::getLatestVersion($staffId);
        if (!$version) {
            return;
        }

        CoupleQuestionnaireTask::where('staff_id', $staffId)
            ->where('status', CoupleQuestionnaireTask::STATUS_PENDING)
            ->update([
                'version_id' => (int)$version->id,
                'version_no' => (int)$version->version_no,
                'title_snapshot' => (string)$version->title,
                'description_snapshot' => (string)$version->description,
                'questions_snapshot' => self::encodeJsonArray([]),
                'update_time' => time(),
            ]);
    }

    private static function buildTaskDetail(CoupleQuestionnaireTask $task): array
    {
        $data = self::formatTask($task->toArray());
        $data['questions'] = self::resolveTaskQuestions($task);
        $answer = CoupleQuestionnaireAnswer::where('task_id', (int)$task->id)
            ->order('id', 'desc')
            ->find();
        $data['answer'] = $answer ? $answer->toArray() : null;
        return $data;
    }

    private static function formatTask(array $item): array
    {
        $status = (int)($item['status'] ?? CoupleQuestionnaireTask::STATUS_PENDING);
        $sendStatus = (int)($item['send_status'] ?? CoupleQuestionnaireTask::SEND_STATUS_PENDING);
        $item['status_desc'] = match ($status) {
            CoupleQuestionnaireTask::STATUS_SUBMITTED => '已填写',
            CoupleQuestionnaireTask::STATUS_CANCELLED => '已取消',
            default => '待填写',
        };
        $item['send_status_desc'] = $sendStatus === CoupleQuestionnaireTask::SEND_STATUS_SENT ? '已推送' : '待推送';
        $item['push_mode_desc'] = (int)($item['push_mode'] ?? CoupleQuestionnaire::PUSH_MODE_AUTO) === CoupleQuestionnaire::PUSH_MODE_MANUAL
            ? '手动触发'
            : '自动推送';
        return $item;
    }

    private static function resolveTaskQuestions(CoupleQuestionnaireTask $task): array
    {
        if ((int)$task->status === CoupleQuestionnaireTask::STATUS_SUBMITTED && !empty($task->questions_snapshot)) {
            return self::normalizeQuestions($task->questions_snapshot);
        }

        $version = self::getLatestVersion((int)$task->staff_id)
            ?: CoupleQuestionnaireVersion::where('id', (int)$task->version_id)->find();
        if ($version && (int)$task->status === CoupleQuestionnaireTask::STATUS_PENDING && (int)$task->version_id !== (int)$version->id) {
            $task->version_id = (int)$version->id;
            $task->version_no = (int)$version->version_no;
            $task->title_snapshot = (string)$version->title;
            $task->description_snapshot = (string)$version->description;
            $task->questions_snapshot = [];
            $task->update_time = time();
            $task->save();
        }

        return $version ? self::normalizeQuestions($version->questions) : [];
    }

    private static function resolvePageParams(array $params): array
    {
        $page = (int)($params['page'] ?? $params['page_no'] ?? 1);
        $limit = (int)($params['limit'] ?? $params['page_size'] ?? $params['size'] ?? 10);
        return [max($page, 1), min(max($limit, 1), 50)];
    }

    private static function pendingTaskCountMap(array $staffIds): array
    {
        $staffIds = array_values(array_unique(array_filter(array_map('intval', $staffIds))));
        if (empty($staffIds)) {
            return [];
        }

        $rows = Db::name('couple_questionnaire_task')
            ->whereIn('staff_id', $staffIds)
            ->where('status', CoupleQuestionnaireTask::STATUS_PENDING)
            ->whereNull('delete_time')
            ->field('staff_id, COUNT(*) AS pending_count')
            ->group('staff_id')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['staff_id']] = (int)$row['pending_count'];
        }
        return $map;
    }

    private static function formatConfigListItem(array $item): array
    {
        $questionnaireId = (int)($item['questionnaire_id'] ?? 0);
        $pushMode = $questionnaireId > 0
            ? (int)($item['push_mode'] ?? CoupleQuestionnaire::PUSH_MODE_AUTO)
            : CoupleQuestionnaire::PUSH_MODE_AUTO;
        $status = $questionnaireId > 0
            ? (int)($item['questionnaire_status'] ?? CoupleQuestionnaire::STATUS_ENABLED)
            : CoupleQuestionnaire::STATUS_ENABLED;
        $versionNo = (int)($item['published_version_no'] ?? 0);

        $item['questionnaire_id'] = $questionnaireId;
        $item['has_config'] = $questionnaireId > 0;
        $item['title'] = trim((string)($item['title'] ?? '')) ?: self::DEFAULT_TITLE;
        $item['description'] = trim((string)($item['description'] ?? '')) ?: self::DEFAULT_DESCRIPTION;
        $item['push_mode'] = $pushMode;
        $item['push_mode_desc'] = $pushMode === CoupleQuestionnaire::PUSH_MODE_MANUAL ? '手动触发' : '自动推送';
        $item['status'] = $status;
        $item['status_desc'] = $status === CoupleQuestionnaire::STATUS_ENABLED ? '启用' : '停用';
        $item['published'] = $versionNo > 0;
        $item['published_version_no'] = $versionNo;
        $item['published_time'] = self::formatTimeValue($item['published_time'] ?? 0);
        $item['update_time'] = self::formatTimeValue($item['update_time'] ?? 0);
        $item['staff'] = [
            'id' => (int)($item['staff_id'] ?? 0),
            'sn' => (string)($item['staff_sn'] ?? ''),
            'name' => (string)($item['staff_name'] ?? ''),
            'mobile' => (string)($item['staff_mobile'] ?? ''),
            'status' => (int)($item['staff_status'] ?? 0),
        ];
        return $item;
    }

    private static function formatTimeValue($value): string
    {
        if (is_numeric($value) && (int)$value > 0) {
            return date('Y-m-d H:i:s', (int)$value);
        }
        return is_string($value) ? $value : '';
    }

    private static function questionTypeOptions(): array
    {
        return [
            ['value' => 'text', 'label' => '短文本'],
            ['value' => 'textarea', 'label' => '文本'],
            ['value' => 'single', 'label' => '单选'],
            ['value' => 'multiple', 'label' => '多选'],
            ['value' => 'rating', 'label' => '评分'],
        ];
    }

    private static function defaultQuestionsFromBank(): array
    {
        $bank = self::bankList(['status' => CoupleQuestionBank::STATUS_ENABLED]);
        return array_slice(array_map(static function (array $item): array {
            return [
                'bank_id' => (int)($item['id'] ?? 0),
                'category' => (string)($item['category'] ?? ''),
                'type' => (string)($item['type'] ?? 'textarea'),
                'title' => (string)($item['title'] ?? ''),
                'placeholder' => (string)($item['placeholder'] ?? ''),
                'options' => $item['options'] ?? [],
                'required' => (int)($item['required'] ?? 0),
                'sort' => (int)($item['sort'] ?? 0),
            ];
        }, $bank), 0, 8);
    }

    private static function normalizeQuestions($questions): array
    {
        if (!is_array($questions)) {
            return [];
        }

        $normalized = [];
        foreach ($questions as $index => $question) {
            if (!is_array($question)) {
                continue;
            }
            $normalized[] = self::normalizeQuestion($question, true, $index);
        }

        usort($normalized, static function (array $a, array $b): int {
            return ((int)$b['sort'] <=> (int)$a['sort']) ?: ((int)$a['id'] <=> (int)$b['id']);
        });

        return array_values($normalized);
    }

    private static function normalizeQuestion(array $question, bool $allowGeneratedId = true, int $index = 0): array
    {
        $type = strtolower(trim((string)($question['type'] ?? $question['question_type'] ?? 'textarea')));
        if (!in_array($type, self::QUESTION_TYPES, true)) {
            $type = 'textarea';
        }

        $title = self::limitText((string)($question['title'] ?? $question['question'] ?? ''), 120);
        if ($title === '') {
            throw new \RuntimeException('题目标题不能为空');
        }

        $options = $question['options'] ?? [];
        if (!is_array($options)) {
            $options = [];
        }
        $options = array_values(array_filter(array_map(static function ($option): string {
            if (is_array($option)) {
                $option = $option['label'] ?? $option['text'] ?? $option['value'] ?? '';
            }
            return self::limitText((string)$option, 60);
        }, $options), static fn (string $option): bool => $option !== ''));

        if (in_array($type, ['single', 'multiple'], true) && count($options) < 2) {
            throw new \RuntimeException('单选或多选题至少需要两个选项');
        }

        return [
            'id' => (int)($question['id'] ?? 0) > 0
                ? (int)$question['id']
                : ($allowGeneratedId ? $index + 1 : 0),
            'bank_id' => (int)($question['bank_id'] ?? 0),
            'category' => self::limitText((string)($question['category'] ?? '补充说明'), 50),
            'type' => $type,
            'title' => $title,
            'placeholder' => self::limitText((string)($question['placeholder'] ?? ''), 120),
            'options' => $options,
            'required' => (int)($question['required'] ?? 0) ? 1 : 0,
            'sort' => (int)($question['sort'] ?? 0),
        ];
    }

    private static function normalizeAnswers(array $questions, array $answers): array
    {
        $answerMap = [];
        foreach ($answers as $answer) {
            if (!is_array($answer)) {
                continue;
            }
            $key = (string)($answer['key'] ?? $answer['id'] ?? '');
            if ($key !== '') {
                $answerMap[$key] = $answer['value'] ?? '';
            }
        }

        $result = [];
        foreach ($questions as $question) {
            $key = (string)($question['id'] ?? '');
            $value = $answerMap[$key] ?? $answerMap[(string)($question['bank_id'] ?? '')] ?? '';
            $type = (string)($question['type'] ?? 'textarea');

            if ($type === 'multiple') {
                $value = is_array($value)
                    ? array_values(array_filter(array_map('strval', $value)))
                    : [];
            } elseif ($type === 'rating') {
                $value = max(1, min(5, (int)$value));
            } else {
                $value = self::limitText(is_array($value) ? implode('，', $value) : (string)$value, 2000);
            }

            if ((int)($question['required'] ?? 0) === 1) {
                $empty = is_array($value) ? empty($value) : trim((string)$value) === '';
                if ($empty) {
                    throw new \RuntimeException('请填写必填项：' . (string)$question['title']);
                }
            }

            $result[] = [
                'key' => $key,
                'title' => (string)$question['title'],
                'type' => $type,
                'value' => $value,
            ];
        }

        return $result;
    }

    private static function limitText(string $value, int $length): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return mb_strlen($value, 'UTF-8') > $length
            ? mb_substr($value, 0, $length, 'UTF-8')
            : $value;
    }

    private static function encodeJsonArray(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '[]';
    }
}
