<?php

declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

/**
 * 清理日志中的会话凭据并强制全部会话重新登录。
 *
 * 背景：历史版本的 trigger_sql 跟随 APP_DEBUG，会把完整 SQL 写入 runtime 日志，
 * 其中包含 `WHERE token = '...'`，拿到日志即可冒用未过期会话。
 *
 * 该命令做两件事：
 *   1. 就地脱敏 runtime 日志中的 token（保留其余内容，便于继续排查）；
 *   2. 失效全部后台/前台会话（删表 + 清 token 缓存），使旧 token 立即不可用。
 *
 * 用法：
 *   php think purge_session_credentials --dry-run   # 只统计，不改动
 *   php think purge_session_credentials             # 执行
 *   php think purge_session_credentials --redact-pii # 同时脱敏手机号
 */
class PurgeSessionCredentials extends Command
{
    /** 日志中需要脱敏的 token 形态 */
    private const TOKEN_PATTERNS = [
        // SQL: WHERE `token` = 'abc123'
        '/(`token`\s*=\s*\')([0-9a-fA-F]{8,64})(\')/',
        // JSON: "token":"abc123"
        '/("token"\s*:\s*")([A-Za-z0-9_\-]{8,64})(")/',
        // 转义后的 JSON: \"token\":\"abc123\"
        '/(\\\\"token\\\\"\s*:\s*\\\\")([A-Za-z0-9_\-]{8,64})(\\\\")/',
        // Authorization: Bearer abc123
        '/(Bearer\s+)([A-Za-z0-9\-._~+\/]{16,})/i',
    ];

    private const MOBILE_PATTERN = '/\b(1[3-9]\d)(\d{4})(\d{4})\b/';

    protected function configure()
    {
        $this->setName('purge_session_credentials')
            ->addOption('dry-run', null, Option::VALUE_NONE, '只统计不修改')
            ->addOption('redact-pii', null, Option::VALUE_NONE, '同时脱敏日志中的手机号')
            ->setDescription('脱敏日志中的会话 token 并强制全部会话重新登录');
    }

    protected function execute(Input $input, Output $output)
    {
        $dryRun = (bool)$input->getOption('dry-run');
        $redactPii = (bool)$input->getOption('redact-pii');

        $output->writeln($dryRun ? '== 预演模式（不会修改任何数据）==' : '== 执行模式 ==');

        $this->redactLogs($output, $dryRun, $redactPii);
        $this->purgeSessions($output, $dryRun);
        $this->clearTokenCache($output, $dryRun);

        $output->writeln('');
        $output->writeln($dryRun ? '预演完成，未做任何改动。' : '处理完成：旧 token 已失效，所有端需重新登录。');

        return 0;
    }

    /**
     * @notes runtime 目录绝对路径
     */
    protected function runtimePath(): string
    {
        if ($this->app) {
            return rtrim($this->app->getRootPath(), '/\\') . DIRECTORY_SEPARATOR . 'runtime';
        }

        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'runtime';
    }

    /**
     * @notes 脱敏 runtime 日志
     */
    protected function redactLogs(Output $output, bool $dryRun, bool $redactPii): void
    {
        $root = $this->runtimePath();
        $files = $this->collectLogFiles($root);

        $totalHits = 0;
        $changedFiles = 0;

        foreach ($files as $file) {
            $content = (string)file_get_contents($file);
            $original = $content;
            $hits = 0;

            foreach (self::TOKEN_PATTERNS as $pattern) {
                $content = (string)preg_replace_callback(
                    $pattern,
                    static function (array $match) use (&$hits): string {
                        $hits++;
                        return $match[1] . '***REDACTED***' . ($match[3] ?? '');
                    },
                    $content
                );
            }

            if ($redactPii) {
                $content = (string)preg_replace_callback(
                    self::MOBILE_PATTERN,
                    static function (array $match) use (&$hits): string {
                        $hits++;
                        return $match[1] . '****' . $match[3];
                    },
                    $content
                );
            }

            if ($content === $original) {
                continue;
            }

            $changedFiles++;
            $totalHits += $hits;
            $relative = str_replace($root, 'runtime', str_replace('\\', '/', $file));

            if (!$dryRun) {
                file_put_contents($file, $content);
            }

            $output->writeln(sprintf('  %s  %s（%d 处）', $dryRun ? '待脱敏' : '已脱敏', $relative, $hits));
        }

        if ($changedFiles === 0) {
            $output->writeln('日志：未发现需要脱敏的凭据。');
            return;
        }

        $output->writeln(sprintf('日志：%d 个文件、%d 处凭据%s。', $changedFiles, $totalHits, $dryRun ? '待处理' : '已脱敏'));
    }

    /**
     * @notes 收集 runtime 下的日志文件
     * @return array<string>
     */
    protected function collectLogFiles(string $root): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            /** @var \SplFileInfo $fileInfo */
            if (!$fileInfo->isFile()) {
                continue;
            }
            $path = $fileInfo->getPathname();
            if (str_contains(str_replace('\\', '/', $path), '/log/') && str_ends_with($path, '.log')) {
                $files[] = $path;
            }
        }

        sort($files);

        return $files;
    }

    /**
     * @notes 删除全部会话记录
     */
    protected function purgeSessions(Output $output, bool $dryRun): void
    {
        $targets = [
            'admin_session' => '后台管理员会话',
            'user_session' => '前台用户会话',
        ];

        foreach ($targets as $table => $label) {
            try {
                $count = (int)Db::name($table)->count();
            } catch (\Throwable $e) {
                $output->writeln(sprintf('  %s：表不存在或不可读，跳过（%s）', $label, $e->getMessage()));
                continue;
            }

            if ($count === 0) {
                $output->writeln(sprintf('  %s：无记录。', $label));
                continue;
            }

            if (!$dryRun) {
                Db::name($table)->delete(true);
            }

            $output->writeln(sprintf('  %s：%s %d 条', $label, $dryRun ? '待删除' : '已删除', $count));
        }
    }

    /**
     * @notes 清理 token 缓存
     *
     * file 驱动下多应用各有一份缓存目录，控制台环境解析不到应用的缓存路径，
     * 因此直接扫描 runtime 下的 cache 目录，只删除命中 token 缓存特征的文件，
     * 避免连带清空配置缓存与权限缓存。
     */
    protected function clearTokenCache(Output $output, bool $dryRun): void
    {
        $driver = (string)config('cache.default', 'file');

        if ($driver !== 'file') {
            $this->clearTokenCacheByTag($output, $dryRun);
            return;
        }

        $runtime = $this->runtimePath();
        $cacheDirs = glob($runtime . '/*/cache', GLOB_ONLYDIR) ?: [];
        $cacheDirs[] = $runtime . '/cache';

        $removed = 0;
        $scanned = 0;

        foreach (array_unique($cacheDirs) as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $fileInfo) {
                /** @var \SplFileInfo $fileInfo */
                if (!$fileInfo->isFile()) {
                    continue;
                }
                $scanned++;

                $content = (string)@file_get_contents($fileInfo->getPathname());
                if (!$this->looksLikeTokenCache($content)) {
                    continue;
                }

                if (!$dryRun) {
                    @unlink($fileInfo->getPathname());
                }
                $removed++;
            }
        }

        $output->writeln(sprintf(
            '  缓存（file 驱动）：%s %d 个 token 缓存（扫描 %d）',
            $dryRun ? '待清理' : '已清理',
            $removed,
            $scanned
        ));
    }

    /**
     * @notes 判断缓存文件是否为 token 缓存
     *
     * 文件缓存内容为 serialize 后的数组，token 会话一定包含 `s:5:"token";s:` 片段；
     * 同时兼容 JSON 序列化与直接使用 key 前缀的实现。
     */
    protected function looksLikeTokenCache(string $content): bool
    {
        if ($content === '') {
            return false;
        }

        foreach (['s:5:"token";s:', '"token":"', 'token_admin_', 'token_user_'] as $marker) {
            if (str_contains($content, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @notes redis 等驱动下按标签清理 token 缓存
     */
    protected function clearTokenCacheByTag(Output $output, bool $dryRun): void
    {
        $caches = [
            \app\common\cache\AdminTokenCache::class,
            \app\common\cache\UserTokenCache::class,
        ];

        foreach ($caches as $class) {
            if (!$dryRun) {
                (new $class())->deleteTag();
            }
            $shortName = substr((string)strrchr($class, '\\'), 1) ?: $class;
            $output->writeln(sprintf('  缓存：%s %s', $dryRun ? '待清理' : '已清理', $shortName));
        }
    }
}
