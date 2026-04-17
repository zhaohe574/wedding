<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务回访模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\aftersale;

use app\common\model\BaseModel;
use app\common\model\order\Order;
use app\common\model\staff\Staff;
use app\common\model\user\User;
use think\facade\Db;

/**
 * 服务回访模型
 * Class ServiceCallback
 * @package app\common\model\aftersale
 */
class ServiceCallback extends BaseModel
{
    protected $name = 'service_callback';

    // 回访类型
    const TYPE_BEFORE = 1;      // 服务前
    const TYPE_DURING = 2;      // 服务中
    const TYPE_AFTER = 3;       // 服务后

    // 回访方式
    const METHOD_PHONE = 1;         // 电话
    const METHOD_SMS = 2;           // 短信
    const METHOD_WECHAT = 3;        // 微信
    const METHOD_QUESTIONNAIRE = 4; // 小程序问卷

    // 回访状态
    const STATUS_PENDING = 0;       // 待回访
    const STATUS_COMPLETED = 1;     // 已回访
    const STATUS_UNREACHABLE = 2;   // 无法联系
    const STATUS_CANCELLED = 3;     // 已取消

    // 问题状态
    const PROBLEM_UNHANDLED = 0;    // 未处理
    const PROBLEM_HANDLED = 1;      // 已处理
    const PROBLEM_ESCALATED = 2;    // 已升级

    /**
     * @notes 关联订单
     * @return \think\model\relation\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * @notes 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->field('id,nickname,avatar,mobile');
    }

    /**
     * @notes 关联服务人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id')
            ->field('id,name,avatar,mobile');
    }

    /**
     * @notes 关联回访人
     * @return \think\model\relation\BelongsTo
     */
    public function admin()
    {
        return $this->belongsTo('app\common\model\auth\Admin', 'admin_id', 'id')
            ->field('id,name,avatar');
    }

    /**
     * @notes 回访类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getTypeDescAttr($value, $data): string
    {
        $map = [
            self::TYPE_BEFORE => '服务前',
            self::TYPE_DURING => '服务中',
            self::TYPE_AFTER => '服务后',
        ];
        return $map[$data['type']] ?? '未知';
    }

    /**
     * @notes 回访方式描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getMethodDescAttr($value, $data): string
    {
        $map = [
            self::METHOD_PHONE => '电话',
            self::METHOD_SMS => '短信',
            self::METHOD_WECHAT => '微信',
            self::METHOD_QUESTIONNAIRE => '小程序问卷',
        ];
        return $map[$data['method']] ?? '未知';
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusDescAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待回访',
            self::STATUS_COMPLETED => '已回访',
            self::STATUS_UNREACHABLE => '无法联系',
            self::STATUS_CANCELLED => '已取消',
        ];
        return $map[$data['status']] ?? '未知';
    }

    /**
     * @notes 生成回访编号
     * @return string
     */
    public static function generateCallbackSn(): string
    {
        return 'CB' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @notes 创建回访任务
     * @param array $data
     * @return array [bool $success, string $message, ServiceCallback|null $callback]
     */
    public static function createCallback(array $data): array
    {
        $order = self::resolveCallbackOrder((int)($data['order_id'] ?? 0), (int)($data['user_id'] ?? 0));
        if (!$order) {
            return [false, '关联订单不存在', null];
        }

        $method = (int)($data['method'] ?? self::METHOD_PHONE);
        $type = (int)($data['type'] ?? self::TYPE_AFTER);
        $staffId = self::resolveCallbackStaffId($order, (int)($data['staff_id'] ?? 0));
        if ($staffId < 0) {
            return [false, '回访服务人员与订单不匹配', null];
        }

        if ($method === self::METHOD_QUESTIONNAIRE) {
            $hasPending = self::where('order_id', (int)$order->id)
                ->where('user_id', (int)$order->user_id)
                ->where('type', $type)
                ->where('method', self::METHOD_QUESTIONNAIRE)
                ->where('status', self::STATUS_PENDING)
                ->find();
            if ($hasPending) {
                return [false, '当前订单已存在待填写问卷', null];
            }
        }

        Db::startTrans();
        try {
            $planTime = (int)($data['plan_time'] ?? 0);
            if ($planTime <= 0) {
                $planTime = time() + 86400;
            }

            $callback = self::create([
                'callback_sn' => self::generateCallbackSn(),
                'order_id' => (int)$order->id,
                'user_id' => (int)$order->user_id,
                'staff_id' => $staffId,
                'type' => $type,
                'method' => $method,
                'status' => self::STATUS_PENDING,
                'plan_time' => $planTime,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            Db::commit();
            return [true, '回访任务创建成功', $callback];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '创建失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 自动创建服务后回访任务
     * @param int $orderId
     * @return bool
     */
    public static function autoCreateAfterServiceCallback(int $orderId): bool
    {
        try {
            $exists = self::where('order_id', $orderId)
                ->where('type', self::TYPE_AFTER)
                ->where('method', self::METHOD_QUESTIONNAIRE)
                ->find();
            if ($exists) {
                return true;
            }

            $order = Order::with(['user', 'items.staff'])->find($orderId);
            if (!$order) {
                return false;
            }

            // 服务完成后7天创建回访任务
            $planTime = time() + 7 * 86400;

            // 获取服务人员ID（取第一个）
            $staffId = 0;
            if (!empty($order->items) && !empty($order->items[0]->staff_id)) {
                $staffId = $order->items[0]->staff_id;
            }

            self::create([
                'callback_sn' => self::generateCallbackSn(),
                'order_id' => $orderId,
                'user_id' => $order->user_id,
                'staff_id' => $staffId,
                'type' => self::TYPE_AFTER,
                'method' => self::METHOD_QUESTIONNAIRE,
                'status' => self::STATUS_PENDING,
                'plan_time' => $planTime,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @notes 完成回访
     * @param int $callbackId
     * @param int $adminId
     * @param array $callbackData
     * @return array [bool $success, string $message]
     */
    public static function completeCallback(int $callbackId, int $adminId, array $callbackData): array
    {
        Db::startTrans();
        try {
            $callback = self::lock(true)->find($callbackId);
            if (!$callback) {
                Db::rollback();
                return [false, '回访任务不存在'];
            }

            if ($callback->status != self::STATUS_PENDING) {
                Db::rollback();
                return [false, '当前状态不可操作'];
            }

            $scoreOverall = self::sanitizeScore((int)($callbackData['score_overall'] ?? $callbackData['score'] ?? 0));
            $hasProblem = !empty($callbackData['has_problem']) ? 1 : 0;
            $callback->status = self::STATUS_COMPLETED;
            $callback->actual_time = time();
            $callback->admin_id = $adminId;
            $callback->duration = max(0, (int)($callbackData['duration'] ?? 0));
            $callback->score = self::sanitizeScore((int)($callbackData['score'] ?? $scoreOverall));
            $callback->score_service = self::sanitizeScore((int)($callbackData['score_service'] ?? $scoreOverall));
            $callback->score_professional = self::sanitizeScore((int)($callbackData['score_professional'] ?? $scoreOverall));
            $callback->score_punctual = self::sanitizeScore((int)($callbackData['score_punctual'] ?? $scoreOverall));
            $callback->score_overall = $scoreOverall;
            $callback->content = self::trimText((string)($callbackData['content'] ?? ''), 1000);
            $callback->summary = self::trimText((string)($callbackData['summary'] ?? ''), 1000);
            $callback->has_problem = $hasProblem;
            $callback->remark = self::trimText((string)($callbackData['remark'] ?? ''), 500);
            $callback->update_time = time();

            // 如果有问题
            if ($hasProblem) {
                $callback->problem_type = self::trimText((string)($callbackData['problem_type'] ?? ''), 100);
                $callback->problem_desc = self::trimText((string)($callbackData['problem_desc'] ?? ''), 1000);
                $callback->problem_status = self::PROBLEM_UNHANDLED;
            } else {
                $callback->problem_type = '';
                $callback->problem_desc = '';
                $callback->problem_status = self::PROBLEM_UNHANDLED;
            }

            $callback->save();

            Db::commit();
            return [true, '回访完成'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '操作失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 标记无法联系
     * @param int $callbackId
     * @param int $adminId
     * @return array [bool $success, string $message]
     */
    public static function markUnreachable(int $callbackId, int $adminId): array
    {
        try {
            $callback = self::find($callbackId);
            if (!$callback) {
                return [false, '回访任务不存在'];
            }

            if ($callback->status != self::STATUS_PENDING) {
                return [false, '当前状态不可操作'];
            }

            // 最多重试3次
            if ($callback->retry_count >= 3) {
                $callback->status = self::STATUS_UNREACHABLE;
            } else {
                $callback->retry_count = $callback->retry_count + 1;
                $callback->next_retry_time = time() + 24 * 3600;  // 下次重试时间：1天后
            }

            $callback->admin_id = $adminId;
            $callback->update_time = time();
            $callback->save();

            return [true, '已标记'];
        } catch (\Exception $e) {
            return [false, '操作失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 问题升级为工单
     * @param int $callbackId
     * @param int $adminId
     * @return array [bool $success, string $message, int $ticketId]
     */
    public static function escalateProblem(int $callbackId, int $adminId): array
    {
        Db::startTrans();
        try {
            $callback = self::lock(true)->find($callbackId);
            if (!$callback) {
                Db::rollback();
                return [false, '回访任务不存在', 0];
            }

            if (!$callback->has_problem) {
                Db::rollback();
                return [false, '该回访无问题记录', 0];
            }

            if ($callback->problem_status == self::PROBLEM_ESCALATED) {
                Db::rollback();
                return [false, '问题已升级', 0];
            }

            // 创建工单
            $ticketResult = AfterSaleTicket::createTicket([
                'order_id' => $callback->order_id,
                'user_id' => $callback->user_id,
                'type' => AfterSaleTicket::TYPE_AFTER_SALE,
                'priority' => AfterSaleTicket::PRIORITY_HIGH,
                'title' => '回访问题升级：' . $callback->problem_type,
                'content' => $callback->problem_desc,
                'source' => AfterSaleTicket::SOURCE_ADMIN,
            ]);

            if (!$ticketResult[0]) {
                Db::rollback();
                return [false, '创建工单失败：' . $ticketResult[1], 0];
            }

            $callback->problem_status = self::PROBLEM_ESCALATED;
            $callback->ticket_id = $ticketResult[2]->id;
            $callback->update_time = time();
            $callback->save();

            Db::commit();
            return [true, '问题已升级为工单', $ticketResult[2]->id];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '升级失败：' . $e->getMessage(), 0];
        }
    }

    /**
     * @notes 用户提交问卷答案
     * @param int $callbackId
     * @param int $userId
     * @param array $answers
     * @return array [bool $success, string $message]
     */
    public static function submitQuestionnaire(int $callbackId, int $userId, array $answers): array
    {
        Db::startTrans();
        try {
            $callback = self::lock(true)->find($callbackId);
            if (!$callback) {
                Db::rollback();
                return [false, '回访任务不存在'];
            }

            if ($callback->user_id != $userId) {
                Db::rollback();
                return [false, '无权操作'];
            }

            if ((int)$callback->method !== self::METHOD_QUESTIONNAIRE) {
                Db::rollback();
                return [false, '当前回访不支持问卷提交'];
            }

            if ($callback->status != self::STATUS_PENDING) {
                Db::rollback();
                return [false, '当前状态不可提交'];
            }

            $questionnaire = self::getActiveQuestionnaireByType((int)$callback->type);
            $questionnaireId = (int)($answers['questionnaire_id'] ?? 0);
            if ($questionnaire) {
                if ($questionnaireId > 0 && $questionnaireId !== (int)$questionnaire['id']) {
                    Db::rollback();
                    return [false, '问卷已更新，请刷新后重试'];
                }
                $questionnaireId = (int)$questionnaire['id'];
            } elseif ($questionnaireId > 0) {
                Db::rollback();
                return [false, '问卷配置不存在'];
            }

            $allowedQuestions = is_array($questionnaire['questions'] ?? null) ? $questionnaire['questions'] : [];
            $sanitizedAnswers = self::sanitizeQuestionnaireAnswers($answers['answers'] ?? [], $allowedQuestions);
            $scoreOverall = self::sanitizeScore((int)($answers['score_overall'] ?? $answers['score'] ?? 0));

            // 解析评分
            $callback->status = self::STATUS_COMPLETED;
            $callback->actual_time = time();
            $callback->score = self::sanitizeScore((int)($answers['score'] ?? $scoreOverall));
            $callback->score_service = self::sanitizeScore((int)($answers['score_service'] ?? $scoreOverall));
            $callback->score_professional = self::sanitizeScore((int)($answers['score_professional'] ?? $scoreOverall));
            $callback->score_punctual = self::sanitizeScore((int)($answers['score_punctual'] ?? $scoreOverall));
            $callback->score_overall = $scoreOverall;
            $callback->content = self::trimText((string)($answers['feedback'] ?? ''), 1000);
            $callback->update_time = time();
            $callback->save();

            // 保存问卷答案
            if ($questionnaireId > 0) {
                Db::name('callback_answer')->insert([
                    'callback_id' => $callbackId,
                    'questionnaire_id' => $questionnaireId,
                    'user_id' => $userId,
                    'answers' => json_encode($sanitizedAnswers, JSON_UNESCAPED_UNICODE),
                    'create_time' => time(),
                ]);
            }

            Db::commit();
            return [true, '提交成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '提交失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 获取统计数据
     * @return array
     */
    public static function getStatistics(): array
    {
        $today = strtotime(date('Y-m-d'));
        $todayEnd = $today + 86400;

        // 计算平均满意度
        $avgScore = self::where('status', self::STATUS_COMPLETED)
            ->where('score', '>', 0)
            ->avg('score') ?: 0;

        return [
            'total' => self::count(),
            'pending' => self::where('status', self::STATUS_PENDING)->count(),
            'completed' => self::where('status', self::STATUS_COMPLETED)->count(),
            'today_plan' => self::where('plan_time', '>=', $today)
                ->where('plan_time', '<', $todayEnd)
                ->where('status', self::STATUS_PENDING)
                ->count(),
            'has_problem' => self::where('has_problem', 1)->where('problem_status', self::PROBLEM_UNHANDLED)->count(),
            'avg_score' => round($avgScore, 2),
        ];
    }

    /**
     * @notes 解析回访订单
     */
    protected static function resolveCallbackOrder(int $orderId, int $userId): ?Order
    {
        if ($orderId <= 0 || $userId <= 0) {
            return null;
        }

        return Order::with(['items'])
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->find();
    }

    /**
     * @notes 校验回访服务人员
     */
    protected static function resolveCallbackStaffId(Order $order, int $staffId = 0): int
    {
        $staffIds = [];
        foreach ($order->items ?? [] as $item) {
            $itemStaffId = (int)($item->staff_id ?? 0);
            if ($itemStaffId > 0) {
                $staffIds[] = $itemStaffId;
            }
        }
        $staffIds = array_values(array_unique($staffIds));

        if ($staffId > 0) {
            return in_array($staffId, $staffIds, true) ? $staffId : -1;
        }

        return $staffIds[0] ?? 0;
    }

    /**
     * @notes 获取当前启用问卷
     */
    protected static function getActiveQuestionnaireByType(int $type): ?array
    {
        $questionnaire = Db::name('callback_questionnaire')
            ->where('type', $type)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->find();

        if (!$questionnaire) {
            return null;
        }

        $questionnaire['questions'] = json_decode((string)($questionnaire['questions'] ?? '[]'), true) ?: [];
        return $questionnaire;
    }

    /**
     * @notes 清洗问卷答案
     */
    protected static function sanitizeQuestionnaireAnswers($answers, array $allowedQuestions): array
    {
        if (!is_array($answers)) {
            return [];
        }

        $allowedMap = [];
        foreach ($allowedQuestions as $index => $question) {
            $key = (string)($question['id'] ?? $question['key'] ?? $index);
            $allowedMap[$key] = self::trimText((string)($question['title'] ?? $question['question'] ?? ''), 200);
        }

        $sanitized = [];
        foreach ($answers as $answer) {
            if (!is_array($answer)) {
                continue;
            }

            $key = trim((string)($answer['key'] ?? ''));
            if ($key === '' || (!empty($allowedMap) && !array_key_exists($key, $allowedMap))) {
                continue;
            }

            $value = $answer['value'] ?? '';
            if (is_array($value)) {
                $value = array_values(array_filter(array_map(static function ($item) {
                    return self::trimText((string)$item, 100);
                }, $value)));
            } else {
                $value = self::trimText((string)$value, 1000);
            }

            $sanitized[] = [
                'key' => $key,
                'title' => $allowedMap[$key] ?? self::trimText((string)($answer['title'] ?? ''), 200),
                'value' => $value,
            ];
        }

        return $sanitized;
    }

    /**
     * @notes 规范评分
     */
    protected static function sanitizeScore(int $score): int
    {
        return max(0, min(5, $score));
    }

    /**
     * @notes 截断文本
     */
    protected static function trimText(string $value, int $maxLength): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return mb_strlen($value, 'UTF-8') > $maxLength
            ? mb_substr($value, 0, $maxLength, 'UTF-8')
            : $value;
    }
}
