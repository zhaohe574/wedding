<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单确认函字体管理服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

class OrderConfirmLetterFontService
{
    public const CONFIG_KEY_FONT_CONFIG = 'font_config';
    public const DEFAULT_SANS_FILE = 'NotoSansSC-VF.ttf';
    public const DEFAULT_SERIF_FILE = 'NotoSerifSC-wght.ttf';
    public const CUSTOM_DIR_NAME = 'custom';
    protected const FONT_DIR = 'app/common/resource/fonts';
    protected const MAX_FONT_SIZE = 30 * 1024 * 1024;
    protected const ALLOWED_EXTENSIONS = ['ttf', 'otf'];

    public static function getConfig(): array
    {
        $config = ConfigService::get(
            OrderConfirmLetterService::CONFIG_GROUP,
            self::CONFIG_KEY_FONT_CONFIG,
            []
        );
        $config = is_array($config) ? $config : [];
        return self::normalizeConfig($config);
    }

    public static function setConfig(array $params): array
    {
        $sansFile = self::normalizeFontFile((string) ($params['sans_file'] ?? ''));
        $serifFile = self::normalizeFontFile((string) ($params['serif_file'] ?? ''));
        if ($sansFile === '') {
            throw new \RuntimeException('正文字体参数不合法');
        }
        if ($serifFile === '') {
            throw new \RuntimeException('衬线字体参数不合法');
        }

        $config = self::normalizeConfig($params);
        self::assertFontUsable($config['sans_file'], '正文字体不存在或不可读');
        self::assertFontUsable($config['serif_file'], '衬线字体不存在或不可读');
        $renderResult = self::testChineseRender(
            self::resolveFontPath($config['sans_file']),
            self::resolveFontPath($config['serif_file'])
        );
        if (empty($renderResult['ok'])) {
            throw new \RuntimeException((string) ($renderResult['message'] ?? '中文渲染检测失败'));
        }
        ConfigService::set(OrderConfirmLetterService::CONFIG_GROUP, self::CONFIG_KEY_FONT_CONFIG, $config);
        return $config;
    }

    public static function lists(): array
    {
        $config = self::getConfig();
        $fonts = [];
        foreach (self::scanFontFiles(self::getFontDirectory(), false) as $font) {
            $fonts[] = self::formatFontItem($font, $config);
        }

        $customDirectory = self::getCustomFontDirectory();
        if (is_dir($customDirectory)) {
            foreach (self::scanFontFiles($customDirectory, true) as $font) {
                $fonts[] = self::formatFontItem($font, $config);
            }
        }

        usort($fonts, static function (array $left, array $right) {
            if ((int) $left['is_builtin'] !== (int) $right['is_builtin']) {
                return (int) $right['is_builtin'] <=> (int) $left['is_builtin'];
            }
            return strcmp((string) $left['name'], (string) $right['name']);
        });

        return [
            'config' => $config,
            'fonts' => $fonts,
            'limits' => [
                'max_size' => self::MAX_FONT_SIZE,
                'allowed_extensions' => self::ALLOWED_EXTENSIONS,
            ],
            'diagnostics' => self::diagnostics($config),
        ];
    }

    public static function upload(): array
    {
        $file = request()->file('file');
        if (!$file) {
            throw new \RuntimeException('请选择字体文件');
        }
        if (method_exists($file, 'isValid') && !$file->isValid()) {
            throw new \RuntimeException('字体文件上传失败');
        }

        $originalName = (string) $file->getOriginalName();
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \RuntimeException('仅支持上传 ttf、otf 字体文件');
        }

        $size = (int) $file->getSize();
        if ($size <= 0 || $size > self::MAX_FONT_SIZE) {
            throw new \RuntimeException('字体文件大小不能超过30MB');
        }

        $realPath = (string) $file->getRealPath();
        if ($realPath === '' || !is_file($realPath)) {
            throw new \RuntimeException('字体文件读取失败');
        }

        $targetDir = self::getCustomFontDirectory();
        self::ensureDirectory($targetDir);
        $fileName = self::buildSafeFileName($originalName, $extension);
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($targetPath)) {
            throw new \RuntimeException('同名字体文件已存在，请重命名后再上传');
        }

        if (!@copy($realPath, $targetPath)) {
            throw new \RuntimeException('字体文件保存失败');
        }
        @chmod($targetPath, 0644);

        return self::formatFontItem([
            'file' => self::CUSTOM_DIR_NAME . '/' . $fileName,
            'path' => $targetPath,
            'builtin' => false,
        ], self::getConfig());
    }

    public static function delete(string $file): void
    {
        $file = self::normalizeFontFile($file);
        if ($file === '') {
            throw new \RuntimeException('请选择要删除的字体');
        }
        if (!str_starts_with($file, self::CUSTOM_DIR_NAME . '/')) {
            throw new \RuntimeException('内置字体不允许删除');
        }

        $config = self::getConfig();
        if ($config['sans_file'] === $file || $config['serif_file'] === $file) {
            throw new \RuntimeException('当前正在使用的字体不允许删除');
        }

        $path = self::resolveFontPath($file);
        if ($path === '' || !is_file($path)) {
            throw new \RuntimeException('字体文件不存在');
        }
        if (!@unlink($path)) {
            throw new \RuntimeException('字体文件删除失败');
        }
    }

    public static function diagnostics(?array $config = null): array
    {
        $config = $config ? self::normalizeConfig($config) : self::getConfig();
        $sans = self::inspectFont($config['sans_file']);
        $serif = self::inspectFont($config['serif_file']);
        return [
            'imagick_loaded' => extension_loaded('imagick') && class_exists(\Imagick::class),
            'query_fonts_available' => class_exists(\Imagick::class) && method_exists(\Imagick::class, 'queryFonts'),
            'font_directory' => self::getFontDirectory(),
            'sans' => $sans,
            'serif' => $serif,
            'render_test' => self::testChineseRender($sans['path'], $serif['path']),
        ];
    }

    public static function getActiveFontOptions(): array
    {
        $config = self::getConfig();
        return [
            'sans_family' => 'OrderConfirmLetterSans',
            'serif_family' => 'OrderConfirmLetterSerif',
            'sans_path' => self::resolveFontPath($config['sans_file']),
            'serif_path' => self::resolveFontPath($config['serif_file']),
            'sans_file' => $config['sans_file'],
            'serif_file' => $config['serif_file'],
        ];
    }

    public static function getActiveFontSignature(array $fontOptions = []): array
    {
        $fontOptions = $fontOptions ?: self::getActiveFontOptions();
        $signature = [
            'sans_file' => (string) ($fontOptions['sans_file'] ?? ''),
            'serif_file' => (string) ($fontOptions['serif_file'] ?? ''),
            'sans_size' => self::resolveFontFileSize((string) ($fontOptions['sans_path'] ?? '')),
            'serif_size' => self::resolveFontFileSize((string) ($fontOptions['serif_path'] ?? '')),
            'sans_mtime' => self::resolveFontFileMtime((string) ($fontOptions['sans_path'] ?? '')),
            'serif_mtime' => self::resolveFontFileMtime((string) ($fontOptions['serif_path'] ?? '')),
        ];
        $signature['hash'] = hash(
            'sha256',
            json_encode($signature, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: ''
        );
        return $signature;
    }

    public static function assertActiveFontsRenderable(array $fontOptions = []): void
    {
        $fontOptions = $fontOptions ?: self::getActiveFontOptions();
        $sansPath = (string) ($fontOptions['sans_path'] ?? '');
        $serifPath = (string) ($fontOptions['serif_path'] ?? '');
        $result = self::testChineseRender($sansPath, $serifPath);
        if (empty($result['ok'])) {
            throw new \RuntimeException((string) ($result['message'] ?? '中文渲染检测失败'));
        }
    }

    public static function getFontDirectory(): string
    {
        return rtrim((string) root_path(), '/\\')
            . DIRECTORY_SEPARATOR
            . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, self::FONT_DIR);
    }

    protected static function normalizeConfig(array $config): array
    {
        $sansFile = self::normalizeFontFile((string) ($config['sans_file'] ?? ''));
        $serifFile = self::normalizeFontFile((string) ($config['serif_file'] ?? ''));
        return [
            'sans_file' => $sansFile !== '' ? $sansFile : self::DEFAULT_SANS_FILE,
            'serif_file' => $serifFile !== '' ? $serifFile : self::DEFAULT_SERIF_FILE,
        ];
    }

    protected static function scanFontFiles(string $directory, bool $custom): array
    {
        if (!is_dir($directory)) {
            return [];
        }
        $fonts = [];
        foreach (scandir($directory) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $path = $directory . DIRECTORY_SEPARATOR . $name;
            if (!is_file($path)) {
                continue;
            }
            $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                continue;
            }
            $fonts[] = [
                'file' => $custom ? self::CUSTOM_DIR_NAME . '/' . $name : $name,
                'path' => $path,
                'builtin' => !$custom,
            ];
        }
        return $fonts;
    }

    protected static function formatFontItem(array $font, array $config): array
    {
        $path = (string) ($font['path'] ?? '');
        $file = self::normalizeFontFile((string) ($font['file'] ?? ''));
        return [
            'file' => $file,
            'name' => basename($file),
            'size' => is_file($path) ? filesize($path) : 0,
            'size_text' => self::formatSize(is_file($path) ? (int) filesize($path) : 0),
            'is_builtin' => !empty($font['builtin']) ? 1 : 0,
            'is_current_sans' => $config['sans_file'] === $file ? 1 : 0,
            'is_current_serif' => $config['serif_file'] === $file ? 1 : 0,
            'readable' => is_readable($path) ? 1 : 0,
            'imagick_query_matched' => self::queryFontMatched($file) ? 1 : 0,
            'path' => $path,
        ];
    }

    protected static function inspectFont(string $file): array
    {
        $path = self::resolveFontPath($file);
        return [
            'file' => $file,
            'path' => $path,
            'exists' => $path !== '' && is_file($path) ? 1 : 0,
            'readable' => $path !== '' && is_readable($path) ? 1 : 0,
            'size' => $path !== '' && is_file($path) ? filesize($path) : 0,
            'imagick_query_matched' => self::queryFontMatched($file) ? 1 : 0,
        ];
    }

    protected static function testChineseRender(string $sansPath, string $serifPath): array
    {
        if (!extension_loaded('imagick') || !class_exists(\Imagick::class)) {
            return ['ok' => 0, 'message' => 'Imagick 未启用'];
        }
        if ($sansPath === '' || !is_file($sansPath) || !is_readable($sansPath)) {
            return ['ok' => 0, 'message' => '正文字体文件不可用'];
        }
        if ($serifPath === '' || !is_file($serifPath) || !is_readable($serifPath)) {
            return ['ok' => 0, 'message' => '衬线字体文件不可用'];
        }

        try {
            self::assertChineseRenderByFont($sansPath);
            self::assertChineseRenderByFont($serifPath);
        } catch (\Throwable $e) {
            return ['ok' => 0, 'message' => '中文渲染检测失败：' . $e->getMessage()];
        }

        return ['ok' => 1, 'message' => '中文测试图可生成，确认函将使用应用内字体文件'];
    }

    protected static function assertChineseRenderByFont(string $fontPath): void
    {
        $width = 360;
        $height = 120;
        $image = new \Imagick();
        $draw = new \ImagickDraw();

        try {
            $image->newImage($width, $height, new \ImagickPixel('white'));
            $image->setImageFormat('png');
            $draw->setFont($fontPath);
            $draw->setFontSize(36);
            $draw->setFillColor(new \ImagickPixel('black'));
            $image->annotateImage($draw, 20, 72, 0, '中文渲染测试');

            $pixels = $image->exportImagePixels(0, 0, $width, $height, 'RGB', \Imagick::PIXEL_CHAR);
            $darkPixels = 0;
            $total = count($pixels);
            for ($index = 0; $index + 2 < $total; $index += 3) {
                if ((int) $pixels[$index] < 245 || (int) $pixels[$index + 1] < 245 || (int) $pixels[$index + 2] < 245) {
                    $darkPixels++;
                    if ($darkPixels > 20) {
                        return;
                    }
                }
            }
            throw new \RuntimeException('测试图片没有绘制出中文像素');
        } finally {
            $draw->clear();
            $draw->destroy();
            $image->clear();
            $image->destroy();
        }
    }

    protected static function queryFontMatched(string $file): bool
    {
        if (!class_exists(\Imagick::class) || !method_exists(\Imagick::class, 'queryFonts')) {
            return false;
        }
        $baseName = pathinfo($file, PATHINFO_FILENAME);
        $keywords = array_values(array_filter(preg_split('/[-_\s]+/', $baseName) ?: []));
        if (empty($keywords)) {
            return false;
        }
        foreach ($keywords as $keyword) {
            if (strlen($keyword) < 3) {
                continue;
            }
            try {
                if (!empty(\Imagick::queryFonts('*' . $keyword . '*'))) {
                    return true;
                }
            } catch (\Throwable $e) {
                return false;
            }
        }
        return false;
    }

    protected static function assertFontUsable(string $file, string $message): void
    {
        $path = self::resolveFontPath($file);
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            throw new \RuntimeException($message);
        }
    }

    protected static function resolveFontFileSize(string $path): int
    {
        return $path !== '' && is_file($path) ? (int) filesize($path) : 0;
    }

    protected static function resolveFontFileMtime(string $path): int
    {
        return $path !== '' && is_file($path) ? (int) filemtime($path) : 0;
    }

    protected static function getCustomFontDirectory(): string
    {
        return self::getFontDirectory() . DIRECTORY_SEPARATOR . self::CUSTOM_DIR_NAME;
    }

    protected static function resolveFontPath(string $file): string
    {
        $file = self::normalizeFontFile($file);
        if ($file === '') {
            return '';
        }
        return self::getFontDirectory() . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file);
    }

    protected static function normalizeFontFile(string $file): string
    {
        $file = str_replace('\\', '/', trim($file));
        $file = ltrim($file, '/');
        if ($file === '' || str_contains($file, '..')) {
            return '';
        }
        $segments = array_values(array_filter(explode('/', $file), static fn($segment) => $segment !== ''));
        if (empty($segments) || count($segments) > 2) {
            return '';
        }
        if (count($segments) === 2 && $segments[0] !== self::CUSTOM_DIR_NAME) {
            return '';
        }
        $name = end($segments);
        $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return '';
        }
        return implode('/', $segments);
    }

    protected static function buildSafeFileName(string $originalName, string $extension): string
    {
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $baseName = preg_replace('/[^a-zA-Z0-9_\-\x{4e00}-\x{9fa5}]/u', '-', $baseName);
        $baseName = trim((string) $baseName, '-_');
        if ($baseName === '') {
            $baseName = 'font';
        }
        $baseName = function_exists('mb_substr')
            ? mb_substr($baseName, 0, 80, 'UTF-8')
            : substr($baseName, 0, 80);
        return $baseName . '.' . $extension;
    }

    protected static function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            if (!is_writable($directory)) {
                throw new \RuntimeException('字体目录不可写');
            }
            return;
        }
        if (!@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('字体目录创建失败');
        }
        @chmod($directory, 0775);
    }

    protected static function formatSize(int $size): string
    {
        if ($size >= 1024 * 1024) {
            return round($size / 1024 / 1024, 2) . ' MB';
        }
        if ($size >= 1024) {
            return round($size / 1024, 2) . ' KB';
        }
        return $size . ' B';
    }
}
