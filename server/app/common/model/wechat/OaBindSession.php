<?php

declare(strict_types=1);

namespace app\common\model\wechat;

use app\common\model\BaseModel;

/**
 * 公众号绑定会话。
 */
class OaBindSession extends BaseModel
{
    protected $name = 'wechat_oa_bind_session';

    public const STATUS_PENDING = 0;
    public const STATUS_CONSUMED = 1;
    public const STATUS_EXPIRED = 2;

    public static function createSession(int $userId, int $expireSeconds = 900): self
    {
        $now = time();
        return self::create([
            'token' => bin2hex(random_bytes(20)),
            'binding_code' => strtoupper(bin2hex(random_bytes(5))),
            'user_id' => $userId,
            'status' => self::STATUS_PENDING,
            'expires_time' => $now + max(60, $expireSeconds),
            'create_time' => $now,
            'update_time' => $now,
        ]);
    }

    public static function findPendingByToken(string $token): ?self
    {
        $session = self::where('token', trim($token))
            ->where('status', self::STATUS_PENDING)
            ->find();

        if (!$session) {
            return null;
        }

        if ((int) $session->expires_time < time()) {
            $session->status = self::STATUS_EXPIRED;
            $session->update_time = time();
            $session->save();
            return null;
        }

        return $session;
    }

    public function consume(): bool
    {
        $this->status = self::STATUS_CONSUMED;
        $this->consumed_time = time();
        $this->update_time = time();
        return $this->save() !== false;
    }
}
