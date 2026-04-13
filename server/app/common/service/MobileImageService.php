<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * 移动端图片优化服务
 * 目标：降低小程序详情页对超大图片的下载压力，避免资源加载超时。
 */
class MobileImageService
{
    private const MAX_UPLOAD_SIZE = 20971520; // 20MB
    private const OPTIMIZE_SIZE_THRESHOLD = 2097152; // 2MB
    private const SOURCE_WIDTH_THRESHOLD = 1600;
    private const TARGET_MAX_WIDTH = 1440;
    private const TARGET_QUALITY = 82;
    private const CACHE_DIR = 'uploads/mobile-cache';
    private const SUPPORTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * @notes 校验上传图片大小
     * @param array $fileInfo
     * @return void
     */
    public static function guardUploadSize(array $fileInfo): void
    {
        $size = (int) ($fileInfo['size'] ?? 0);
        if ($size > self::MAX_UPLOAD_SIZE) {
            throw new \RuntimeException('图片大小不能超过20MB，请压缩后重新上传');
        }
    }

    /**
     * @notes 准备上传文件
     * @param array $fileInfo
     * @return array{path:string,cleanup:bool}
     */
    public static function prepareUploadFile(array $fileInfo): array
    {
        $sourcePath = (string) ($fileInfo['realPath'] ?? '');
        if (!self::shouldOptimize($sourcePath, $fileInfo)) {
            return [
                'path' => $sourcePath,
                'cleanup' => false,
            ];
        }

        $targetPath = self::buildTempTargetPath();
        try {
            self::optimizeToTarget($sourcePath, $targetPath);
            if (!is_file($targetPath) || filesize($targetPath) === 0) {
                @unlink($targetPath);
                return [
                    'path' => $sourcePath,
                    'cleanup' => false,
                ];
            }

            $sourceSize = (int) ($fileInfo['size'] ?? filesize($sourcePath));
            $sourceWidth = self::getImageWidth($sourcePath);
            $targetWidth = self::getImageWidth($targetPath);
            if (filesize($targetPath) >= $sourceSize && $sourceWidth <= 0) {
                @unlink($targetPath);
                return [
                    'path' => $sourcePath,
                    'cleanup' => false,
                ];
            }

            if (filesize($targetPath) >= $sourceSize && $targetWidth >= $sourceWidth) {
                @unlink($targetPath);
                return [
                    'path' => $sourcePath,
                    'cleanup' => false,
                ];
            }

            return [
                'path' => $targetPath,
                'cleanup' => true,
            ];
        } catch (\Throwable $e) {
            @unlink($targetPath);
            return [
                'path' => $sourcePath,
                'cleanup' => false,
            ];
        }
    }

    /**
     * @notes 获取移动端展示图片地址
     * @param string $uri
     * @return string
     */
    public static function toMobileDisplayUrl(string $uri): string
    {
        $uri = trim($uri);
        if ($uri === '' || !self::isLocalStorage() || self::isCacheUri($uri)) {
            return $uri;
        }

        $relativeUri = self::normalizeRelativeUri($uri);
        if ($relativeUri === '') {
            return $uri;
        }

        $ext = strtolower(pathinfo($relativeUri, PATHINFO_EXTENSION));
        if (!self::isOptimizableExtension($ext)) {
            return $uri;
        }

        $sourcePath = self::toPublicPath($relativeUri);
        if (!self::shouldOptimize($sourcePath)) {
            return $uri;
        }

        $cacheRelativeUri = self::buildCacheRelativeUri($relativeUri, $sourcePath);
        $cachePath = self::toPublicPath($cacheRelativeUri);
        if (!is_file($cachePath) || filesize($cachePath) === 0) {
            try {
                self::ensureDirectory(dirname($cachePath));
                self::optimizeToTarget($sourcePath, $cachePath);
                if (!is_file($cachePath) || filesize($cachePath) === 0) {
                    @unlink($cachePath);
                    return $uri;
                }
            } catch (\Throwable $e) {
                @unlink($cachePath);
                return $uri;
            }
        }

        return FileService::getFileUrl($cacheRelativeUri);
    }

    /**
     * @notes 是否需要优化
     * @param string $sourcePath
     * @param array $fileInfo
     * @return bool
     */
    private static function shouldOptimize(string $sourcePath, array $fileInfo = []): bool
    {
        if ($sourcePath === '' || !is_file($sourcePath)) {
            return false;
        }

        $ext = strtolower((string) ($fileInfo['ext'] ?? pathinfo($sourcePath, PATHINFO_EXTENSION)));
        if (!self::isOptimizableExtension($ext)) {
            return false;
        }

        $size = (int) ($fileInfo['size'] ?? filesize($sourcePath));
        $width = self::getImageWidth($sourcePath);
        return $size > self::OPTIMIZE_SIZE_THRESHOLD || $width > self::SOURCE_WIDTH_THRESHOLD;
    }

    /**
     * @notes 是否支持优化
     * @param string $ext
     * @return bool
     */
    private static function isOptimizableExtension(string $ext): bool
    {
        return in_array(strtolower($ext), self::SUPPORTED_EXTENSIONS, true);
    }

    /**
     * @notes 是否本地存储
     * @return bool
     */
    private static function isLocalStorage(): bool
    {
        return ConfigService::get('storage', 'default', 'local') === 'local';
    }

    /**
     * @notes 是否已经是移动端缓存图
     * @param string $uri
     * @return bool
     */
    private static function isCacheUri(string $uri): bool
    {
        return str_contains(str_replace('\\', '/', $uri), self::CACHE_DIR . '/');
    }

    /**
     * @notes 规范化相对路径
     * @param string $uri
     * @return string
     */
    private static function normalizeRelativeUri(string $uri): string
    {
        if (preg_match('/^https?:\/\//i', $uri)) {
            $path = (string) parse_url($uri, PHP_URL_PATH);
            return ltrim($path, '/\\');
        }

        return ltrim($uri, '/\\');
    }

    /**
     * @notes 相对路径转 public 文件路径
     * @param string $relativeUri
     * @return string
     */
    private static function toPublicPath(string $relativeUri): string
    {
        $relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relativeUri, '/\\'));
        return rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR . $relativePath;
    }

    /**
     * @notes 构造缓存图相对路径
     * @param string $relativeUri
     * @param string $sourcePath
     * @return string
     */
    private static function buildCacheRelativeUri(string $relativeUri, string $sourcePath): string
    {
        $hash = md5(
            implode('|', [
                $relativeUri,
                (string) filesize($sourcePath),
                (string) filemtime($sourcePath),
                (string) self::TARGET_MAX_WIDTH,
                (string) self::TARGET_QUALITY,
            ])
        );

        return self::CACHE_DIR . '/' . substr($hash, 0, 2) . '/' . $hash . '.webp';
    }

    /**
     * @notes 构造临时文件路径
     * @return string
     */
    private static function buildTempTargetPath(): string
    {
        return rtrim(sys_get_temp_dir(), '/\\')
            . DIRECTORY_SEPARATOR
            . uniqid('wm_mobile_', true)
            . '.webp';
    }

    /**
     * @notes 获取图片宽度
     * @param string $path
     * @return int
     */
    private static function getImageWidth(string $path): int
    {
        $imageInfo = @getimagesize($path);
        return (int) ($imageInfo[0] ?? 0);
    }

    /**
     * @notes 优化到目标文件
     * @param string $sourcePath
     * @param string $targetPath
     * @return void
     */
    private static function optimizeToTarget(string $sourcePath, string $targetPath): void
    {
        self::ensureDirectory(dirname($targetPath));

        if (extension_loaded('imagick')) {
            try {
                self::optimizeByImagick($sourcePath, $targetPath);
                return;
            } catch (\Throwable $e) {
                @unlink($targetPath);
            }
        }

        self::optimizeByGd($sourcePath, $targetPath);
    }

    /**
     * @notes Imagick 优化
     * @param string $sourcePath
     * @param string $targetPath
     * @return void
     */
    private static function optimizeByImagick(string $sourcePath, string $targetPath): void
    {
        $imagick = new \Imagick();
        $imagick->readImage($sourcePath);
        if ($imagick->getNumberImages() > 1) {
            throw new \RuntimeException('暂不处理动图优化');
        }

        if (method_exists($imagick, 'autoOrient')) {
            $imagick->autoOrient();
        }

        $width = $imagick->getImageWidth();
        if ($width > self::TARGET_MAX_WIDTH) {
            $imagick->resizeImage(self::TARGET_MAX_WIDTH, 0, \Imagick::FILTER_LANCZOS, 1, true);
        }

        $imagick->stripImage();
        $imagick->setImageBackgroundColor(new \ImagickPixel('transparent'));
        $imagick->setImageFormat('webp');
        $imagick->setImageCompressionQuality(self::TARGET_QUALITY);
        $imagick->setOption('webp:method', '6');
        $imagick->setOption('webp:alpha-quality', '100');
        $imagick->writeImage($targetPath);
        $imagick->clear();
        $imagick->destroy();
    }

    /**
     * @notes GD 优化
     * @param string $sourcePath
     * @param string $targetPath
     * @return void
     */
    private static function optimizeByGd(string $sourcePath, string $targetPath): void
    {
        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            throw new \RuntimeException('无法读取图片信息');
        }

        [$sourceWidth, $sourceHeight] = $imageInfo;
        $mime = strtolower((string) ($imageInfo['mime'] ?? ''));
        $sourceImage = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if (!$sourceImage) {
            throw new \RuntimeException('当前环境不支持该图片格式优化');
        }

        $targetWidth = $sourceWidth > self::TARGET_MAX_WIDTH ? self::TARGET_MAX_WIDTH : $sourceWidth;
        $targetHeight = $sourceWidth > 0
            ? (int) round(($targetWidth / $sourceWidth) * $sourceHeight)
            : $sourceHeight;

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);

        imagecopyresampled(
            $canvas,
            $sourceImage,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        if (!function_exists('imagewebp') || !imagewebp($canvas, $targetPath, self::TARGET_QUALITY)) {
            imagedestroy($canvas);
            imagedestroy($sourceImage);
            throw new \RuntimeException('生成 webp 图片失败');
        }

        imagedestroy($canvas);
        imagedestroy($sourceImage);
    }

    /**
     * @notes 确保目录存在
     * @param string $directory
     * @return void
     */
    private static function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('创建图片缓存目录失败');
        }
    }
}
