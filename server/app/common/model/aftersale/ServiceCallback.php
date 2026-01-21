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
        Db::startTrans();
        try {
            $callback = self::create([
                'callback_sn' => self::generateCallbackSn(),
                'order_id' => $data['order_id'],
                'user_id' => $data['user_id'],
                'staff_id' => $data['staff_id'] ?? 0,
                'type' => $data['type'] ?? self::TYPE_AFTER,
                'method' => $data['method'] ?? self::METHOD_PHONE,
                'status' => self::STATUS_PENDING,
                'plan_time' => $data['plan_time'] ?? time() + 86400,  // 默认次日
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
            $callback = self::find($callbackId);
            if (!$callback) {
                return [false, '回访任务不存在'];
            }

            if ($callback->status != self::STATUS_PENDING) {
                return [false, '当前状态不可操作'];
            }

            $callback->status = self::STATUS_COMPLETED;
            $callback->actual_time = time();
            $callback->admin_id = $adminId;
            $callback->duration = $callbackData['duration'] ?? 0;
            $callback->score = $callbackData['score'] ?? 0;
            $callback->score_service = $callbackData['score_service'] ?? 0;
            $callback->score_professional = $callbackData['score_professional'] ?? 0;
            $callback->score_punctual = $callbackData['score_punctual'] ?? 0;
            $callback->score_overall = $callbackData['score_overall'] ?? 0;
            $callback->content = $callbackData['content'] ?? '';
            $callback->summary = $callbackData['summary'] ?? '';
            $callback->has_problem = $callbackData['has_problem'] ?? 0;
            $callback->remark = $callbackData['remark'] ?? '';
            $callback->update_time = time();

            // 如果有问题
            if (!empty($callbackData['has_problem'])) {
                $callback->problem_type = $callbackData['problem_type'] ?? '';
                $callback->problem_desc = $callbackData['problem_desc'] ?? '';
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
            $callback = self::find($callbackId);
            if (!$callback) {
                return [false, '回访任务不存在', 0];
            }

            if (!$callback->has_problem) {
                return [false, '该回访无问题记录', 0];
            }

            if ($callback->problem_status == self::PROBLEM_ESCALATED) {
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
            $callback = self::find($callbackId);
            if (!$callback) {
                return [false, '回访任务不存在'];
            }

            if ($callback->user_id != $userId) {
                return [false, '无权操作'];
            }

            if ($callback->status != self::STATUS_PENDING) {
                return [false, '当前状态不可提交'];
            }

            // 解析评分
            $callback->status = self::STATUS_COMPLETED;
            $callback->actual_time = time();
            $callback->score = $answers['score'] ?? 0;
            $callback->score_service = $answers['score_service'] ?? 0;
            $callback->score_professional = $answers['score_professional'] ?? 0;
            $callback->score_punctual = $answers['score_punctual'] ?? 0;
            $callback->score_overall = $answers['score_overall'] ?? 0;
            $callback->content = $answers['feedback'] ?? '';
            $callback->update_time = time();
            $callback->save();

            // 保存问卷答案
            if (!empty($answers['questionnaire_id'])) {
                Db::name('callback_answer')->insert([
                    'callback_id' => $callbackId,
                    'questionnaire_id' => $answers['questionnaire_id'],
                    'user_id' => $userId,
                    'answers' => json_encode($answers['answers'] ?? [], JSON_UNESCAPED_UNICODE),
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
}
