<?php
declare(strict_types=1);

/**
 * 隐私政策正文补齐（小程序发版审核阻断项）。
 *
 * 现状核查：privacy_content 已有 10 个章节 / 约 2300 字，但缺三处审核会挑的点：
 *   1. 只有「生效日期」，没有「更新日期」
 *   2. 全文没有「相册（图片）」和 chooseImage 的表述，而小程序实际调了 chooseImage
 *   3. 手机号只写了「授权」，没写清 getPhoneNumber 这个实际调用的能力
 *
 * 本脚本只做这三处增量修改，其它正文一字不动，重复执行不会重复插入。
 *
 * 用法：
 *   php database/migrations/20261108_privacy_policy_refresh.php           # 只预览
 *   php database/migrations/20261108_privacy_policy_refresh.php --apply   # 落库
 *
 * 回滚：执行前把原值备份到 database/migrations/.backup/privacy_content_<时间>.html
 */

use think\facade\Db;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
$app->initialize();

set_exception_handler(static function (Throwable $e): void {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
});

$apply = in_array('--apply', $argv, true);
$db = Db::connect();

$row = $db->name('config')->where(['type' => 'agreement', 'name' => 'privacy_content'])->find();
if (!$row) {
    fwrite(STDERR, "未找到 agreement.privacy_content，请先在后台保存一次隐私政策\n");
    exit(1);
}

$original = (string)$row['value'];
$content = $original;
$changes = [];

// 1. 更新日期
if (strpos($content, '更新日期') === false) {
    $anchor = '生效日期：<strong>2026年05月02日</strong></p>';
    $replacement = $anchor . '<p style="text-indent: 2em;">更新日期：<strong>2026年11月08日</strong></p>';
    if (strpos($content, $anchor) !== false) {
        $content = str_replace($anchor, $replacement, $content);
        $changes[] = '补充「更新日期：2026年11月08日」';
    } else {
        $changes[] = '[警告] 未匹配到生效日期锚点，跳过更新日期';
    }
} else {
    $changes[] = '[skip] 已有更新日期';
}

// 2. 手机号：写明 getPhoneNumber
$oldMobile = '当您使用手机号登录或绑定手机号时，我们会收集您的手机号码和验证码校验结果。';
$newMobile = '当您使用手机号快捷登录（微信手机号授权组件 getPhoneNumber）或主动绑定手机号时，我们会收集您的手机号码和短信验证码校验结果。';
if (strpos($content, 'getPhoneNumber') === false && strpos($content, $oldMobile) !== false) {
    $content = str_replace($oldMobile, $newMobile, $content);
    $changes[] = '补充手机号授权组件 getPhoneNumber 说明';
} else {
    $changes[] = '[skip] 手机号说明已包含 getPhoneNumber 或锚点不匹配';
}

// 3. 相册（图片）：新增第 8 条收集项
if (strpos($content, '相册（图片）') === false) {
    $anchor = '用途为系统安全、故障排查、风险防控和服务优化。';
    $paragraph = '<p style="text-indent: 2em;">8. 相册（图片）信息：当您发表评价、追评，或提交售后工单、投诉时主动选择图片，我们会调用微信小程序的相册/拍照选择能力（chooseImage），仅收集您主动选中的图片文件。我们不会在您未选择的情况下读取您的相册，也不会从图片中提取地理位置等额外信息。用途为展示评价图文、核实售后凭证、处理投诉与售后。</p>';
    if (strpos($content, $anchor) !== false) {
        // 第 7 条段落以该句结尾，新段落插在其后
        $pos = strpos($content, $anchor);
        $insertAt = $pos + strlen($anchor);
        // 跳过紧接着的 </p>
        if (substr($content, $insertAt, 4) === '</p>') {
            $insertAt += 4;
        }
        $content = substr($content, 0, $insertAt) . $paragraph . substr($content, $insertAt);
        $changes[] = '新增「8. 相册（图片）信息」收集项（chooseImage）';
    } else {
        $changes[] = '[警告] 未匹配到第 7 条锚点，跳过相册条款';
    }
} else {
    $changes[] = '[skip] 已有相册（图片）条款';
}

// 4. 第三方服务说明里补 chooseImage / getPhoneNumber 口径
$oldThird = '1. 微信开放能力：用于微信登录、手机号授权、隐私授权弹窗等；';
$newThird = '1. 微信开放能力：用于微信登录、手机号授权（getPhoneNumber）、相册选择（chooseImage）、隐私授权弹窗等；';
if (strpos($content, $oldThird) !== false) {
    $content = str_replace($oldThird, $newThird, $content);
    $changes[] = '第三方服务说明补充 getPhoneNumber / chooseImage';
} else {
    $changes[] = '[skip] 第三方服务说明已更新或锚点不匹配';
}

// 5. 第三方清单里补“共享”口径（PIPL 要求写明是否共享、共享对象）
$oldShare = '为实现小程序功能，我们可能接入以下第三方或基础服务：';
$newShare = '我们不会出售您的个人信息。仅在实现以下功能所必需、且限于最小必要范围时，我们才会与下列第三方服务方共享相关信息；除此之外，我们不会向任何第三方共享您的个人信息：';
if (strpos($content, $oldShare) !== false) {
    $content = str_replace($oldShare, $newShare, $content);
    $changes[] = '第三方服务说明补充「不出售、最小必要共享」口径';
} else {
    $changes[] = '[skip] 第三方共享口径已存在或锚点不匹配';
}

echo "当前长度：" . mb_strlen(strip_tags($original)) . " 字（纯文本）/" . strlen($original) . " 字节（HTML）\n";
echo "调整后长度：" . mb_strlen(strip_tags($content)) . " 字（纯文本）/" . strlen($content) . " 字节（HTML）\n\n";
foreach ($changes as $line) {
    echo "  - {$line}\n";
}

if ($content === $original) {
    echo "\n没有需要执行的变更\n";
    exit(0);
}

if (!$apply) {
    echo "\n（预览模式，未落库。确认无误后加 --apply 执行）\n";
    exit(0);
}

// 备份原值
$backupDir = __DIR__ . '/.backup';
if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true)) {
    throw new RuntimeException('备份目录创建失败：' . $backupDir);
}
$backupFile = $backupDir . '/privacy_content_' . date('YmdHis') . '.html';
file_put_contents($backupFile, $original);
echo "\n原值已备份：{$backupFile}\n";

\app\common\service\ConfigService::set('agreement', 'privacy_content', $content);
echo "已写入 agreement.privacy_content，并清理配置缓存\n";
