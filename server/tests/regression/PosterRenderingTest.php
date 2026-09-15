<?php
declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/topthink/framework/src/helper.php';

use app\common\service\OrderConfirmLetterFontService;
use app\common\service\StaffScheduleConfirmLetterRenderer;

$app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
$read = new ReflectionMethod(OrderConfirmLetterFontService::class, 'readFontFamily');
$read->setAccessible(true);
$family = $read->invoke(null, __DIR__ . '/../../app/common/resource/fonts/NotoSansSC-VF.ttf');
if ($family !== 'Noto Sans SC') { throw new RuntimeException('中文字体名称读取错误'); }
$lines = StaffScheduleConfirmLetterRenderer::wrapTextByWidth("中文测试\n第二行", 60, 20, 4);
if ($lines !== ['中文测', '试', '第二行']) { throw new RuntimeException('中文宽度或换行错误'); }
try {
    StaffScheduleConfirmLetterRenderer::wrapTextByWidth('中文测试', 20, 20, 1);
    throw new LogicException('文字溢出未被拒绝');
} catch (RuntimeException $error) {
    if (!str_contains($error->getMessage(), '高度')) { throw $error; }
}
OrderConfirmLetterFontService::configureSvgFonts();
$defaultDesign = new ReflectionMethod(\app\common\service\MonthlyReportService::class, 'defaultDesignConfig');
$defaultDesign->setAccessible(true);
foreach (['addition', 'ranking', 'top'] as $type) {
    \app\common\service\MonthlyReportRenderer::render([
        'design_config' => $defaultDesign->invoke(null, $type),
        'variables' => ['addition_title' => 'ADDITION', 'addition_subtitle' => '2026年9月新增婚礼档期',
            'addition_count' => '204', 'executed_count' => '233', 'top_title' => 'THE MOST',
            'top_count' => '23', 'footer_note' => '感谢您的选择', 'ranking_title' => '2026年9月共计执行',
            'top_staff_names' => '测试人员、测试人员', 'report_month_label' => '2026年9月', 'report_year' => '2026'],
    ], ['font_options' => ['font_family' => $family]]);
}
$image = new Imagick();
$path = sys_get_temp_dir() . '/wedding-poster-check.png';
try {
    $image->readImageBlob('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="180"><rect width="400" height="180" fill="white"/><text x="20" y="70" font-size="36" font-family="' . $family . '">中文海报测试</text><g opacity="0.5" transform="rotate(12 200 120)"><text x="20" y="130" font-size="30" fill="red" font-family="' . $family . '">图层旋转透明度</text></g><rect x="20" y="40" width="20" height="30" fill="blue"/></svg>');
    $color = $image->getImagePixelColor(25, 50)->getColor();
    if ($color['b'] < 240 || $color['r'] > 10) { throw new RuntimeException('导出图层顺序错误'); }
    $image->setImageFormat('png');
    if (!$image->writeImage($path)) { throw new RuntimeException('海报文件写入失败'); }
    echo 'OK - 字体、中文换行、溢出、图层顺序' . PHP_EOL;
} finally {
    $image->clear();
    if (!getenv('WEDDING_KEEP_POSTER_PREVIEW') && is_file($path)) { unlink($path); }
}
