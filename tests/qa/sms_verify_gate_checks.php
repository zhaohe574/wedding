<?php
/**
 * QA 资产 · 短信验证码「校验次数闸门」行为验证
 *
 * 不依赖 MySQL / ThinkPHP 容器，可直接在 CI 里跑：
 *   php tests/qa/sms_verify_gate_checks.php
 *
 * 原理：从 server/app/common/service/sms/SmsDriver.php 中【原样抽取】verify() 方法源码，
 * 注入到一个只替换了「数据源」的最小桩类里执行。被执行的判定逻辑就是线上那份代码，
 * 而不是测试里重写的一份逻辑 —— 源码一改，本测试立刻跟着变，不会出现「测试与实现各写一套」的假绿。
 *
 * 桩的实现要点：
 *  - SmsLogQuery::$next 模拟 findOrEmpty() 返回的「读快照」；
 *  - Db::$live        模拟数据库里那一行的「当前真实状态」；
 *  - DbQuery::update() 模拟带条件的原子 UPDATE（where + inc），条件不满足时返回 affected=0。
 * 把「快照」与「真实行」分开，才能真实模拟并发：
 * N 个请求都在同一时刻读到旧快照，但条件更新会按真实行逐个判定，超出上限的请求必然 affected=0。
 *
 * 退出码：0 全部通过；1 有失败场景；2 抽取失败（源码结构变了）。
 */

declare(strict_types=1);

// ---------------------------------------------------------------- 源码定位

$srcFile = dirname(__DIR__, 2) . '/server/app/common/service/sms/SmsDriver.php';
if (!is_file($srcFile)) {
    fwrite(STDERR, "找不到源码文件：{$srcFile}\n");
    exit(2);
}
$src = (string)file_get_contents($srcFile);

/**
 * 从源码中抽取一个方法的完整源码（含方法签名与配对的花括号体）。
 * 用花括号深度计数而非正则，避免方法体里出现嵌套数组/闭包时截断。
 */
function extractMethod(string $src, string $signature): string
{
    $start = strpos($src, $signature);
    if ($start === false) {
        fwrite(STDERR, "未找到方法签名：{$signature}\n");
        exit(2);
    }

    $braceStart = strpos($src, '{', $start);
    if ($braceStart === false) {
        fwrite(STDERR, "方法体起始花括号缺失：{$signature}\n");
        exit(2);
    }

    $depth = 0;
    $length = strlen($src);
    for ($i = $braceStart; $i < $length; $i++) {
        if ($src[$i] === '{') {
            $depth++;
        } elseif ($src[$i] === '}') {
            $depth--;
            if ($depth === 0) {
                return substr($src, $start, $i - $start + 1);
            }
        }
    }

    fwrite(STDERR, "方法体花括号未闭合：{$signature}\n");
    exit(2);
}

$verifyMethod = extractMethod($src, 'public function verify(');

if (!preg_match('/const\s+MAX_CHECK_NUM\s*=\s*(\d+)\s*;/', $src, $matched)) {
    fwrite(STDERR, "未找到 MAX_CHECK_NUM 常量\n");
    exit(2);
}
$maxCheckNum = (int)$matched[1];

// ---------------------------------------------------------------- 源码结构守卫
// 这些断言把「实现必须满足的约束」钉在测试里：任何人把原子条件更新改回「读-改-写」，测试立刻红。

$guardFailures = 0;
$guard = static function (string $label, bool $ok) use (&$guardFailures): void {
    printf("[%s] 源码守卫：%s\n", $ok ? 'PASS' : 'FAIL', $label);
    if (!$ok) {
        $guardFailures++;
    }
};

$gatePos = strpos($verifyMethod, 'MAX_CHECK_NUM');
$comparePos = strpos($verifyMethod, 'hash_equals');
$guard('闸门判定出现在验证码比对之前', $gatePos !== false && $comparePos !== false && $gatePos < $comparePos);
$guard('使用原子自增 inc(\'check_num\')', strpos($verifyMethod, "inc('check_num')") !== false);
$guard('自增带 check_num 上限条件', strpos($verifyMethod, "where('check_num', '<'") !== false);
$guard('不再使用「读属性 → +1 → save」写法', strpos($verifyMethod, '->save()') === false);
$guard('正确分支同时置位 is_verify', strpos($verifyMethod, "'is_verify' => YesNoEnum::YES") !== false);

echo "\n已从源码抽取 verify() 方法（" . substr_count($verifyMethod, "\n") . " 行），MAX_CHECK_NUM={$maxCheckNum}\n\n";

// ---------------------------------------------------------------- 最小桩

$stub = <<<'PHP'
<?php
namespace QAStub;

/** 枚举桩 */
class SmsEnum
{
    const SEND_SUCCESS = 1;
}

class NoticeEnum
{
    const SMS_SCENE = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
    const FIND_LOGIN_PASSWORD_CAPTCHA = 5;
}

class YesNoEnum
{
    const NO = 0;
    const YES = 1;
}

/** 数据行桩：可读写属性 + isEmpty()，模拟 ThinkPHP Model 实例 */
class SmsLogRow
{
    public bool $exists = true;
    public array $data = [];

    public function __construct(array $data, bool $exists = true)
    {
        $this->data = $data;
        $this->exists = $exists;
    }

    public function isEmpty(): bool
    {
        return !$this->exists;
    }

    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function save(): bool
    {
        return true;
    }
}

/** 查询构造器桩：模拟 SmsLog::where(...)->order(...)->findOrEmpty() */
class SmsLogQuery
{
    /** @var SmsLogRow|null 本次「读快照」返回的行 */
    public static ?SmsLogRow $next = null;

    public function order($field, $direction)
    {
        return $this;
    }

    public function findOrEmpty()
    {
        return self::$next ?? new SmsLogRow([], false);
    }
}

class SmsLog
{
    public static function where($where)
    {
        return new SmsLogQuery();
    }
}

/** 带条件的原子 UPDATE 桩：模拟 Db::name('sms_log')->where(...)->inc(...)->update(...) */
class DbQuery
{
    private array $wheres = [];
    private array $increments = [];

    public function __construct(private string $table)
    {
    }

    public function where($field, $op = null, $value = null)
    {
        // 对齐 ThinkPHP 的两种调用形式：where('a', 1) 与 where('a', '<', 1)
        if (func_num_args() === 2) {
            $value = $op;
            $op = '=';
        }
        $this->wheres[] = [$field, $op, $value];
        return $this;
    }

    public function whereNull($field)
    {
        $this->wheres[] = [$field, 'null', null];
        return $this;
    }

    public function inc($field, $step = 1)
    {
        $this->increments[$field] = $step;
        return $this;
    }

    /**
     * @return int affected rows：条件不满足时返回 0（真实 MySQL 行为）
     */
    public function update(array $data = []): int
    {
        $row = Db::$live;
        if (!$row instanceof SmsLogRow || !$row->exists) {
            return 0;
        }

        foreach ($this->wheres as [$field, $op, $value]) {
            $current = $row->data[$field] ?? null;

            $matched = match ($op) {
                '=' => self::looseEquals($current, $value),
                '<' => (float)$current < (float)$value,
                'null' => $current === null,
                default => false,
            };

            if (!$matched) {
                return 0;
            }
        }

        foreach ($this->increments as $field => $step) {
            $row->data[$field] = (int)($row->data[$field] ?? 0) + (int)$step;
        }

        foreach ($data as $field => $value) {
            if (array_key_exists($field, $this->increments)) {
                continue;
            }
            $row->data[$field] = $value;
        }

        return 1;
    }

    private static function looseEquals($current, $value): bool
    {
        if (is_numeric($current) && is_numeric($value)) {
            return (float)$current === (float)$value;
        }
        return (string)$current === (string)$value;
    }
}

/** Db 门面桩 */
class Db
{
    /** @var SmsLogRow|null 数据库里那一行的当前真实状态 */
    public static ?SmsLogRow $live = null;

    public static function name(string $table): DbQuery
    {
        return new DbQuery($table);
    }
}

/** 被测类：只提供 MAX_CHECK_NUM 常量与从源码抽取的 verify() */
class UnderTest
{
    const MAX_CHECK_NUM = __MAX__;
__METHOD__
}
PHP;

$stub = str_replace('__MAX__', (string)$maxCheckNum, $stub);
$stub = str_replace('__METHOD__', $verifyMethod, $stub);

$tmpFile = sys_get_temp_dir() . '/qa_sms_verify_stub_' . getmypid() . '.php';
if (file_put_contents($tmpFile, $stub) === false) {
    fwrite(STDERR, "桩文件写入失败：{$tmpFile}\n");
    exit(2);
}
require $tmpFile;

// ---------------------------------------------------------------- 场景执行器

$failures = 0;

/**
 * 执行一次 verify()：快照来自 $snapshotData，条件更新作用在 $liveData 上。
 *
 * @param array $snapshotData 本次请求「读到」的行数据
 * @param bool $exists 记录是否存在
 * @param string $inputCode 用户提交的验证码
 * @param array $liveData 数据库真实行（引用传入，会被 UPDATE 修改）
 * @return bool verify() 的返回值
 */
function attempt(array $snapshotData, bool $exists, string $inputCode, array &$liveData): bool
{
    \QAStub\SmsLogQuery::$next = new \QAStub\SmsLogRow($snapshotData, $exists);
    $liveRow = new \QAStub\SmsLogRow($liveData, true);
    \QAStub\Db::$live = $liveRow;

    $driver = new \QAStub\UnderTest();
    $result = $driver->verify('13800000000', $inputCode, 5);

    $liveData = $liveRow->data;

    return $result === true;
}

/**
 * @param string $name 场景名
 * @param callable $runner 返回 [bool $passed, string $detail]
 */
function scenario(string $name, callable $runner): void
{
    [$passed, $detail] = $runner();
    printf("[%s] %-46s %s\n", $passed ? 'PASS' : 'FAIL', $name, $detail);
    if (!$passed) {
        $GLOBALS['failures']++;
    }
}

$fresh = ['id' => 1, 'code' => '123456', 'check_num' => 0, 'is_verify' => 0, 'send_time' => time(), 'mobile' => '13800000000'];

// S1 正常路径
scenario('S1 正确验证码（check_num=0）', static function () use ($fresh) {
    $live = $fresh;
    $result = attempt($fresh, true, '123456', $live);
    $ok = $result === true && (int)$live['check_num'] === 1 && (int)$live['is_verify'] === 1;
    return [$ok, sprintf('verify=%s check_num=%s is_verify=%s', var_export($result, true), $live['check_num'], $live['is_verify'])];
});

// S2 错误验证码占用额度
scenario('S2 错误验证码（check_num=0）', static function () use ($fresh) {
    $live = $fresh;
    $result = attempt($fresh, true, '000000', $live);
    $ok = $result === false && (int)$live['check_num'] === 1 && (int)$live['is_verify'] === 0;
    return [$ok, sprintf('verify=%s check_num=%s is_verify=%s', var_export($result, true), $live['check_num'], $live['is_verify'])];
});

// S3 达上限 + 码正确 → 必须拒绝（证明闸门在比对之前）
scenario('S3 达上限 + 正确验证码（必须拒绝）', static function () {
    $snapshot = ['id' => 1, 'code' => '123456', 'check_num' => 5, 'is_verify' => 0, 'send_time' => time()];
    $live = $snapshot;
    $result = attempt($snapshot, true, '123456', $live);
    $ok = $result === false && (int)$live['check_num'] === 5 && (int)$live['is_verify'] === 0;
    return [$ok, sprintf('verify=%s check_num=%s is_verify=%s', var_export($result, true), $live['check_num'], $live['is_verify'])];
});

// S4 超上限 + 码正确 → 必须拒绝
scenario('S4 超上限 + 正确验证码（必须拒绝）', static function () {
    $snapshot = ['id' => 1, 'code' => '123456', 'check_num' => 99, 'is_verify' => 0, 'send_time' => time()];
    $live = $snapshot;
    $result = attempt($snapshot, true, '123456', $live);
    $ok = $result === false && (int)$live['check_num'] === 99;
    return [$ok, sprintf('verify=%s check_num=%s', var_export($result, true), $live['check_num'])];
});

// S5 上限内最后一次尝试 + 码正确 → 通过
scenario('S5 第5次尝试 + 正确验证码（应通过）', static function () {
    $snapshot = ['id' => 1, 'code' => '123456', 'check_num' => 4, 'is_verify' => 0, 'send_time' => time()];
    $live = $snapshot;
    $result = attempt($snapshot, true, '123456', $live);
    $ok = $result === true && (int)$live['check_num'] === 5 && (int)$live['is_verify'] === 1;
    return [$ok, sprintf('verify=%s check_num=%s is_verify=%s', var_export($result, true), $live['check_num'], $live['is_verify'])];
});

// S6 无记录
scenario('S6 无验证码记录', static function () {
    $live = [];
    $result = attempt([], false, '123456', $live);
    return [$result === false, sprintf('verify=%s', var_export($result, true))];
});

// S7 已过期（>5 分钟）
scenario('S7 验证码已过期', static function () {
    $snapshot = ['id' => 1, 'code' => '123456', 'check_num' => 0, 'is_verify' => 0, 'send_time' => time() - 301];
    $live = $snapshot;
    $result = attempt($snapshot, true, '123456', $live);
    $ok = $result === false && (int)$live['check_num'] === 0;
    return [$ok, sprintf('verify=%s check_num=%s', var_export($result, true), $live['check_num'])];
});

// S8 达上限后记录保留、不置位（防手机号探测）
scenario('S8 达上限后记录保留（不删不置位）', static function () {
    $snapshot = ['id' => 1, 'code' => '123456', 'check_num' => 5, 'is_verify' => 0, 'send_time' => time()];
    $live = $snapshot;
    $result = attempt($snapshot, true, '999999', $live);
    $ok = $result === false && (int)$live['is_verify'] === 0 && (int)$live['check_num'] === 5;
    return [$ok, sprintf('verify=%s check_num=%s is_verify=%s', var_export($result, true), $live['check_num'], $live['is_verify'])];
});

// S9 并发：快照通过闸门，但真实行已被并发请求推到上限 → 码对也不得置位
scenario('S9 码对但并发超限（不得置为已校验）', static function () {
    $snapshot = ['id' => 1, 'code' => '123456', 'check_num' => 4, 'is_verify' => 0, 'send_time' => time()];
    $live = ['id' => 1, 'code' => '123456', 'check_num' => 5, 'is_verify' => 0, 'send_time' => time()];
    $result = attempt($snapshot, true, '123456', $live);
    $ok = $result === false && (int)$live['is_verify'] === 0 && (int)$live['check_num'] === 5;
    return [$ok, sprintf('verify=%s check_num=%s is_verify=%s', var_export($result, true), $live['check_num'], $live['is_verify'])];
});

// S10 并发：6 个请求同时读到 check_num=0，条件更新保证真实行最多被推到 5
scenario('S10 6 并发错误码（不得突破 5 次上限）', static function () {
    $snapshot = ['id' => 1, 'code' => '123456', 'check_num' => 0, 'is_verify' => 0, 'send_time' => time()];
    $live = ['id' => 1, 'code' => '123456', 'check_num' => 0, 'is_verify' => 0, 'send_time' => time()];
    $passCount = 0;
    for ($i = 0; $i < 6; $i++) {
        // 每个请求都拿着同一份旧快照（模拟并发同时通过读闸门）
        if (attempt($snapshot, true, '000000', $live)) {
            $passCount++;
        }
    }
    $ok = $passCount === 0 && (int)$live['check_num'] === 5 && (int)$live['is_verify'] === 0;
    return [$ok, sprintf('通过数=%d check_num=%s（期望 5）', $passCount, $live['check_num'])];
});

@unlink($tmpFile);

echo "\n源码守卫失败数：{$guardFailures}，场景失败数：{$GLOBALS['failures']}\n";
exit(($guardFailures + $GLOBALS['failures']) > 0 ? 1 : 0);
