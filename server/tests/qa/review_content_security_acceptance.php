<?php
// +----------------------------------------------------------------------
// | 评价内容安全验收脚本（真实调用微信 msg_sec_check）
// +----------------------------------------------------------------------
// | 用途：发版前证明“评价正文命中微信内容安全 → 落待审 → 后台可见”这条链路真的通了。
// | 前置：库里有已完成订单项、对应用户绑定了小程序 openid、mnp_setting 配置了 AppID/Secret。
// | 执行：php tests/qa/review_content_security_acceptance.php [order_item_id]
// +----------------------------------------------------------------------

declare(strict_types=1);

$root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;
require $root . 'vendor/autoload.php';

$app = new think\App($root);
$app->initialize();

use app\api\logic\ReviewLogic;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\review\Review;
use app\common\model\review\ReviewReply;

$orderItemId = (int)($argv[1] ?? 0);
if ($orderItemId <= 0) {
    $reviewedIds = Review::where('order_item_id', '>', 0)->column('order_item_id');
    $query = OrderItem::alias('item')
        ->join('order', 'order.id = item.order_id')
        ->where('order.order_status', Order::STATUS_COMPLETED)
        ->order('item.id', 'desc')
        ->field('item.id,item.order_id,item.staff_id,order.user_id');
    if (!empty($reviewedIds)) {
        $query->whereNotIn('item.id', $reviewedIds);
    }
    $candidate = $query->find();
    if (!$candidate) {
        fwrite(STDERR, "没有可用于测试的已完成订单项（订单需为已完成且未评价）\n");
        exit(1);
    }
    $orderItemId = (int)$candidate['id'];
    $userId = (int)$candidate['user_id'];
} else {
    $item = OrderItem::with(['order'])->find($orderItemId);
    $userId = (int)($item->order->user_id ?? 0);
}

echo "== 测试对象 ==\n";
echo "order_item_id = {$orderItemId}，user_id = {$userId}\n";

// 真实违规样本：微信 content_model 判定 risky / label=20006（金融诈骗类），
// 本地敏感词库为空表，所以这条样本不会被本地词库拦下，能完整验证微信这一层。
$content = '刷卡套现代还信用卡';

echo "\n== 1. 提交评价（正文命中微信内容安全）==\n";
echo "content = {$content}\n";

$result = ReviewLogic::publish([
    'user_id' => $userId,
    'order_item_id' => $orderItemId,
    'score' => 5,
    'content' => $content,
    'is_anonymous' => 0,
]);

if ($result === false) {
    echo "[FAIL] 发布失败：" . ReviewLogic::getError() . "\n";
    exit(1);
}

$reviewId = (int)$result['review_id'];
echo "review_id = {$reviewId}\n";

$review = Review::find($reviewId);
echo "\n== 2. 落库结果 ==\n";
echo "status = " . $review->status . "（" . Review::getStatusDesc((int)$review->status) . "）\n";
echo "security_remark = " . ($review->security_remark ?: '(空)') . "\n";
echo "content 已入库 = " . $review->content . "\n";

$pass = true;
if ((int)$review->status !== Review::STATUS_PENDING) {
    echo "[FAIL] 期望落待审，实际 status={$review->status}\n";
    $pass = false;
}
if ((string)$review->security_remark === '') {
    echo "[FAIL] 期望写入内容安全备注（微信命中原因），实际为空\n";
    $pass = false;
}

echo "\n== 3. 后台可见性（adminapi 评价详情 / 列表查询）==\n";
$adminDetail = app\adminapi\logic\review\ReviewLogic::detail($reviewId);
echo "detail.status_text = " . ($adminDetail['status_text'] ?? '(空)') . "\n";
echo "detail.security_remark = " . (($adminDetail['security_remark'] ?? '') ?: '(空)') . "\n";

$pendingTotal = Review::where('status', Review::STATUS_PENDING)->count();
echo "后台待审核评价总数 = {$pendingTotal}\n";

$lists = [];
try {
    $lists = (new \app\adminapi\lists\review\ReviewLists())->lists();
} catch (\Throwable $e) {
    echo "（列表实例化失败，改用同条件查询：" . $e->getMessage() . "）\n";
    $lists = Review::with(['user', 'staff', 'order'])
        ->where('status', Review::STATUS_PENDING)
        ->order('create_time', 'desc')
        ->limit(50)
        ->select()
        ->toArray();
}
$idInList = false;
foreach ($lists as $row) {
    if ((int)($row['id'] ?? 0) === $reviewId) {
        $idInList = true;
        break;
    }
}
echo "该评价出现在后台待审列表 = " . ($idInList ? '是' : '否') . "（列表返回 " . count($lists) . " 条）\n";
if (!$idInList) {
    $pass = false;
}

echo "\n== 4. 追评（同一口径：命中不拒绝，落待审）==\n";
$appended = ReviewLogic::append([
    'id' => $reviewId,
    'user_id' => $userId,
    'content' => $content,
]);
echo "append 返回 = " . ($appended ? 'true' : 'false：' . ReviewLogic::getError()) . "\n";
$reply = ReviewReply::where('review_id', $reviewId)->order('id', 'desc')->find();
if ($reply) {
    echo "reply_id = {$reply->id}，status = " . $reply->status . "（" . ReviewReply::getTypeDesc((int)$reply->reply_type) . "）\n";
    if ((int)$reply->status !== ReviewReply::STATUS_PENDING) {
        echo "[FAIL] 追评应落待审\n";
        $pass = false;
    }
} else {
    echo "[FAIL] 追评未入库\n";
    $pass = false;
}

echo "\n" . ($pass ? "OK - 评价内容安全链路验收通过" : "FAILED - 见上方失败项") . "\n";
echo "（测试数据：review_id={$reviewId}，如需清理：DELETE FROM la_review_reply WHERE review_id={$reviewId}; DELETE FROM la_review WHERE id={$reviewId};）\n";
exit($pass ? 0 : 1);
