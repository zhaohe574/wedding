<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - Redis分布式锁服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use think\facade\Cache;

/**
 * Redis分布式锁服务
 * 用于档期预约防超卖场景
 * Class RedisLockService
 * @package app\common\service
 */
class RedisLockService
{
    /**
     * 锁前缀
     */
    const LOCK_PREFIX = 'schedule_lock:';

    /**
     * 默认锁超时时间（秒）
     */
    const DEFAULT_TIMEOUT = 10;

    /**
     * 默认重试次数
     */
    const DEFAULT_RETRY_COUNT = 3;

    /**
     * 默认重试间隔（毫秒）
     */
    const DEFAULT_RETRY_DELAY = 100;

    /**
     * @notes 获取锁
     * @param string $key 锁键名
     * @param int $timeout 锁超时时间（秒）
     * @param int $retryCount 重试次数
     * @param int $retryDelay 重试间隔（毫秒）
     * @return string|false 成功返回锁token，失败返回false
     */
    public static function acquire(
        string $key, 
        int $timeout = self::DEFAULT_TIMEOUT, 
        int $retryCount = self::DEFAULT_RETRY_COUNT, 
        int $retryDelay = self::DEFAULT_RETRY_DELAY
    ) {
        $lockKey = self::LOCK_PREFIX . $key;
        $token = self::generateToken();

        for ($i = 0; $i <= $retryCount; $i++) {
            // 使用SETNX + EXPIRE原子操作
            $result = self::setNx($lockKey, $token, $timeout);
            
            if ($result) {
                return $token;
            }

            // 未获取到锁，等待后重试
            if ($i < $retryCount) {
                usleep($retryDelay * 1000);
            }
        }

        return false;
    }

    /**
     * @notes 释放锁
     * @param string $key 锁键名
     * @param string $token 锁token
     * @return bool
     */
    public static function release(string $key, string $token): bool
    {
        $lockKey = self::LOCK_PREFIX . $key;

        // 使用Lua脚本保证原子性：只有token匹配才能删除
        $script = <<<LUA
if redis.call('get', KEYS[1]) == ARGV[1] then
    return redis.call('del', KEYS[1])
else
    return 0
end
LUA;

        try {
            $handler = self::getRedisHandler();
            if ($handler) {
                $result = self::evaluateTokenScript($handler, $script, $lockKey, [$token]);
                if ($result !== null) {
                    return $result === 1;
                }
            }
        } catch (\Throwable $e) {
            // 继续降级到缓存兜底
        }

        return self::releaseFallbackLock($lockKey, $token);
    }

    /**
     * @notes 续期锁
     * @param string $key 锁键名
     * @param string $token 锁token
     * @param int $timeout 新的超时时间
     * @return bool
     */
    public static function renew(string $key, string $token, int $timeout = self::DEFAULT_TIMEOUT): bool
    {
        $lockKey = self::LOCK_PREFIX . $key;

        // 使用Lua脚本保证原子性：只有token匹配才能续期
        $script = <<<LUA
if redis.call('get', KEYS[1]) == ARGV[1] then
    return redis.call('expire', KEYS[1], ARGV[2])
else
    return 0
end
LUA;

        try {
            $handler = self::getRedisHandler();
            if ($handler) {
                $result = self::evaluateTokenScript(
                    $handler,
                    $script,
                    $lockKey,
                    [$token, (string)$timeout]
                );
                if ($result !== null) {
                    return $result === 1;
                }
            }
        } catch (\Throwable $e) {
            // 继续降级到缓存兜底
        }

        return self::renewFallbackLock($lockKey, $token, $timeout);
    }

    /**
     * @notes 检查锁是否存在
     * @param string $key 锁键名
     * @return bool
     */
    public static function isLocked(string $key): bool
    {
        $lockKey = self::LOCK_PREFIX . $key;
        try {
            $handler = self::getRedisHandler();
            if ($handler && method_exists($handler, 'exists')) {
                return (bool)$handler->exists($lockKey);
            }
        } catch (\Throwable $e) {
            // 使用缓存兜底
        }

        return Cache::has($lockKey);
    }

    /**
     * @notes 获取锁的剩余时间
     * @param string $key 锁键名
     * @return int -2:不存在, -1:无过期时间, >0:剩余秒数
     */
    public static function getTtl(string $key): int
    {
        $lockKey = self::LOCK_PREFIX . $key;

        try {
            $handler = self::getRedisHandler();
            if ($handler && method_exists($handler, 'ttl')) {
                return (int)$handler->ttl($lockKey);
            }
        } catch (\Throwable $e) {
            // 使用缓存兜底
        }

        return Cache::has($lockKey) ? -1 : -2;
    }

    /**
     * @notes 获取档期锁键名
     * @param int $staffId 工作人员ID
     * @param string $date 日期
     * @param int $timeSlot 时间段
     * @return string
     */
    public static function getScheduleLockKey(int $staffId, string $date, int $timeSlot = 0): string
    {
        return "staff:{$staffId}:date:{$date}:slot:{$timeSlot}";
    }

    /**
     * @notes 锁定档期（带分布式锁）
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @param int $userId
     * @param callable $callback 获取锁后执行的回调
     * @return array [bool $success, string $message, mixed $data]
     */
    public static function lockScheduleWithRedis(
        int $staffId,
        string $date,
        int $timeSlot,
        int $userId,
        callable $callback,
        int $timeout = 5,
        int $retryCount = 3,
        int $retryDelay = 100
    ): array
    {
        $lockKey = self::getScheduleLockKey($staffId, $date, $timeSlot);

        // 尝试获取分布式锁
        $token = self::acquire($lockKey, $timeout, $retryCount, $retryDelay);

        if ($token === false) {
            return [false, '系统繁忙，请稍后重试', null];
        }

        try {
            // 执行业务逻辑
            $result = $callback();
            return $result;
        } catch (\Exception $e) {
            return [false, $e->getMessage(), null];
        } finally {
            // 释放锁
            self::release($lockKey, $token);
        }
    }

    /**
     * @notes 批量锁定档期
     * @param array $schedules [[staffId, date, timeSlot], ...]
     * @param int $userId
     * @param callable $callback
     * @return array
     */
    public static function batchLockSchedules(array $schedules, int $userId, callable $callback): array
    {
        $tokens = [];
        $lockKeys = [];

        // 尝试获取所有锁
        foreach ($schedules as $schedule) {
            $lockKey = self::getScheduleLockKey($schedule[0], $schedule[1], $schedule[2] ?? 0);
            $token = self::acquire($lockKey, 10, 2, 50);
            
            if ($token === false) {
                // 回滚已获取的锁
                foreach ($tokens as $key => $t) {
                    self::release($key, $t);
                }
                return [false, '部分档期正在被其他用户操作，请稍后重试', null];
            }
            
            $tokens[$lockKey] = $token;
            $lockKeys[] = $lockKey;
        }

        try {
            // 执行业务逻辑
            $result = $callback();
            return $result;
        } catch (\Exception $e) {
            return [false, $e->getMessage(), null];
        } finally {
            // 释放所有锁
            foreach ($tokens as $key => $token) {
                self::release($key, $token);
            }
        }
    }

    /**
     * @notes SETNX原子操作
     * @param string $key
     * @param string $value
     * @param int $timeout
     * @return bool
     */
    protected static function setNx(string $key, string $value, int $timeout): bool
    {
        try {
            $handler = self::getRedisHandler();
            if ($handler) {
                $result = self::setNxByHandler($handler, $key, $value, $timeout);
                if ($result !== null) {
                    return $result;
                }
            }
        } catch (\Throwable $e) {
            // 降级到Cache方式
        }

        // 降级处理：使用Cache（非原子操作，仅作为降级方案）
        if (!Cache::has($key)) {
            return Cache::set($key, $value, $timeout);
        }
        return false;
    }

    /**
     * @notes 生成唯一token
     * @return string
     */
    protected static function generateToken(): string
    {
        return md5(uniqid((string)mt_rand(), true) . microtime(true));
    }

    /**
     * @notes 获取 Redis handler
     * @return object|null
     */
    protected static function getRedisHandler(): ?object
    {
        try {
            $handler = Cache::store('redis')->handler();
            if (is_object($handler)) {
                return $handler;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * @notes 清理过期锁（定时任务使用）
     * @param string $pattern 匹配模式
     * @return int 清理数量
     */
    public static function cleanExpiredLocks(string $pattern = '*'): int
    {
        try {
            $handler = self::getRedisHandler();
            if (
                !$handler ||
                !method_exists($handler, 'keys') ||
                !method_exists($handler, 'ttl') ||
                !method_exists($handler, 'del')
            ) {
                return 0;
            }

            $keys = $handler->keys(self::LOCK_PREFIX . $pattern);
            $cleaned = 0;

            foreach ($keys as $key) {
                $ttl = (int)$handler->ttl($key);
                // TTL为-1表示没有设置过期时间，可能是异常锁
                if ($ttl == -1) {
                    $handler->del($key);
                    $cleaned++;
                }
            }

            return $cleaned;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * @notes 通过底层 handler 执行 SET NX EX
     * @return bool|null
     */
    protected static function setNxByHandler(object $handler, string $key, string $value, int $timeout): ?bool
    {
        if (!method_exists($handler, 'set')) {
            return null;
        }

        if ($handler instanceof \Redis) {
            $result = $handler->set($key, $value, ['nx', 'ex' => $timeout]);
            return $result === true;
        }

        $result = $handler->set($key, $value, 'EX', $timeout, 'NX');
        if (is_string($result)) {
            return strtoupper($result) === 'OK';
        }

        return $result === true;
    }

    /**
     * @notes 执行 token 校验脚本，兼容 phpredis 与 Predis 参数形式
     * @return int|null
     */
    protected static function evaluateTokenScript(
        object $handler,
        string $script,
        string $lockKey,
        array $arguments
    ): ?int {
        if (!method_exists($handler, 'eval')) {
            return null;
        }

        try {
            $result = $handler->eval($script, array_merge([$lockKey], $arguments), 1);
            if (is_numeric($result)) {
                return (int)$result;
            }
        } catch (\Throwable $e) {
            // 尝试兼容另一种参数顺序
        }

        try {
            $result = $handler->eval($script, 1, $lockKey, ...$arguments);
            if (is_numeric($result)) {
                return (int)$result;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * @notes 缓存兜底释放锁
     */
    protected static function releaseFallbackLock(string $lockKey, string $token): bool
    {
        $currentToken = Cache::get($lockKey);
        if (!is_string($currentToken) || $currentToken !== $token) {
            return false;
        }

        return Cache::delete($lockKey);
    }

    /**
     * @notes 缓存兜底续期锁
     */
    protected static function renewFallbackLock(string $lockKey, string $token, int $timeout): bool
    {
        $currentToken = Cache::get($lockKey);
        if (!is_string($currentToken) || $currentToken !== $token) {
            return false;
        }

        return Cache::set($lockKey, $token, $timeout);
    }
}
