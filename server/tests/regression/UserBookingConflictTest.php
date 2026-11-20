<?php
// +----------------------------------------------------------------------
// | 用户重复预约校验回归测试
// +----------------------------------------------------------------------
// | 规则：同一用户在同一自然日，不能对同一服务人员创建重叠/重复的预约。
// | 执行：php tests/regression/UserBookingConflictTest.php
// +----------------------------------------------------------------------

declare(strict_types=1);

use app\common\exception\UserBookingConflictException;
use app\common\model\order\Order;
use app\common\model\schedule\Schedule;
use app\common\service\UserBookingConflictService;

require __DIR__ . '/../../vendor/autoload.php';

final class UserBookingConflictTest
{
    private int $assertions = 0;

    public function run(): void
    {
        $this->testStatusScope();
        $this->testTimeSlotOverlap();
        $this->testConflictMessage();
        $this->testServiceUsesNamedLocks();
        $this->testCreateEntrypointsGuarded();
        $this->testModifyEntrypointsGuarded();
        $this->testLockCoversCommit();

        echo 'OK - ' . $this->assertions . " assertions\n";
    }

    private function testStatusScope(): void
    {
        foreach ([
            Order::STATUS_PENDING_CONFIRM,
            Order::STATUS_PENDING_PAY,
            Order::STATUS_PENDING_SERVICE,
            Order::STATUS_IN_SERVICE,
            Order::STATUS_COMPLETED,
            Order::STATUS_REVIEWED,
            Order::STATUS_PAUSED,
            Order::STATUS_REFUNDING,
        ] as $status) {
            $this->assertTrue(
                UserBookingConflictService::isConflictStatus($status),
                '已提交且未释放的订单状态必须参与重复预约判定：' . $status
            );
        }

        foreach ([
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
            Order::STATUS_USER_DELETED,
        ] as $status) {
            $this->assertFalse(
                UserBookingConflictService::isConflictStatus($status),
                '已取消/已退款/已删除订单不得阻止再次预约：' . $status
            );
        }
    }

    private function testTimeSlotOverlap(): void
    {
        $this->assertTrue(
            UserBookingConflictService::timeSlotOverlaps(Schedule::TIME_SLOT_ALL, Schedule::TIME_SLOT_MORNING),
            '全天与早礼必须重叠'
        );
        $this->assertTrue(
            UserBookingConflictService::timeSlotOverlaps(Schedule::TIME_SLOT_ALL, Schedule::TIME_SLOT_ALL),
            '全天与全天必须重叠'
        );
        $this->assertTrue(
            UserBookingConflictService::timeSlotOverlaps(Schedule::TIME_SLOT_MORNING, Schedule::TIME_SLOT_MORNING),
            '同一具体时段必须重叠'
        );
        $this->assertFalse(
            UserBookingConflictService::timeSlotOverlaps(Schedule::TIME_SLOT_MORNING, Schedule::TIME_SLOT_AFTERNOON),
            '不同具体时段不重叠'
        );
    }

    private function testConflictMessage(): void
    {
        $duplicate = UserBookingConflictException::duplicate('2026-12-01', 'SN20261201001', '待确认');
        $this->assertContains('2026-12-01', $duplicate->getMessage(), '冲突提示必须包含日期');
        $this->assertContains('SN20261201001', $duplicate->getMessage(), '冲突提示必须包含原订单号');
        $this->assertContains('待确认', $duplicate->getMessage(), '冲突提示必须包含原订单状态');
        $this->assertContains('不能重复预约', $duplicate->getMessage(), '冲突提示必须说明不能重复预约');
        $this->assertContains('取消原订单', $duplicate->getMessage(), '冲突提示必须给出处理方式');

        $sameSlot = UserBookingConflictException::duplicate('2026-12-01', 'SN20261201001', '待服务', true);
        $this->assertContains('同一时段', $sameSlot->getMessage(), '具体时段冲突必须说明同一时段');

        $payload = UserBookingConflictException::duplicatedInPayload('张三', '2026-12-01');
        $this->assertContains('同一订单不能重复选择', $payload->getMessage(), '同一请求内重复选择必须单独提示');

        $this->assertContains('请勿重复操作', UserBookingConflictException::submitting()->getMessage(), '并发未拿到锁必须有稳定提示');
    }

    private function testServiceUsesNamedLocks(): void
    {
        $service = $this->readSource('app/common/service/UserBookingConflictService.php');
        $this->assertContains('GET_LOCK', $service, '并发互斥必须使用数据库命名锁');
        $this->assertContains('RELEASE_LOCK', $service, '命名锁必须显式释放');
        $this->assertContains('ub:d:', $service, '日期维度锁名必须稳定');
        $this->assertContains('ub:o:', $service, '订单维度锁名必须稳定');
        $this->assertContains('CONFLICT_ORDER_STATUSES', $service, '冲突状态范围必须显式声明');
        $this->assertContains('timeSlotOverlaps', $service, '时间重叠判定必须收敛在服务内');
        $this->assertContains('lockUserScope', $service, '必须提供用户维度当前读锁');
        $this->assertContains('->lock(true)', $service, '用户维度锁必须使用 FOR UPDATE 当前读');
    }

    private function testCreateEntrypointsGuarded(): void
    {
        $apiOrder = $this->readSource('app/api/logic/OrderLogic.php');
        $createBody = $this->extractFunctionBody($apiOrder, 'createOrder');
        $this->assertContains('UserBookingConflictService::acquireForItems', $createBody, '小程序下单必须先获取用户日期锁');
        $this->assertContains('Order::createOrder', $createBody, '小程序下单必须走订单创建入口');

        $order = $this->readSource('app/common/model/order/Order.php');
        $this->assertContains('UserBookingConflictService::lockUserScope($userId)', $order, '订单创建必须在事务内做用户维度当前读');
        $this->assertContains('UserBookingConflictService::assertItemsBookable($userId, $selectedItems', $order, '订单创建必须校验重复预约');

        $manual = $this->readSource('app/common/service/ManualOrderService.php');
        $this->assertContains('UserBookingConflictService::lockUserScope($userId)', $manual, '后台/服务人员录单必须做用户维度当前读');
        $this->assertContains('UserBookingConflictService::acquireForItems', $manual, '后台/服务人员录单必须获取用户日期锁');
        $this->assertContains('UserBookingConflictService::assertItemsBookable', $manual, '后台/服务人员录单必须校验重复预约');

        $staffManual = $this->readSource('app/common/service/StaffManualOrderService.php');
        $this->assertContains('UserBookingConflictService::acquireForItems', $staffManual, '服务人员录单的外层事务必须持有用户日期锁');

        $adminOrder = $this->readSource('app/adminapi/logic/order/OrderLogic.php');
        $addBody = $this->extractFunctionBody($adminOrder, 'add');
        $this->assertContains('UserBookingConflictService::acquireForItems', $addBody, '后台建单必须获取用户日期锁');
        $this->assertContains('UserBookingConflictService::assertItemsBookable', $addBody, '后台建单必须校验重复预约');
    }

    private function testModifyEntrypointsGuarded(): void
    {
        $change = $this->readSource('app/common/model/order/OrderChange.php');
        $this->assertContains('UserBookingConflictService::assertBookable', $change, '改期/加项必须校验重复预约');
        $this->assertContains('assertNoDuplicateStaffInItems', $change, '改期必须拦截同一订单内同一人员重复');
        $this->assertContains('assertNoIntraOrderDuplicate', $change, '加项必须拦截订单内已有同日同人员');
        $this->assertContains('acquireChangeGuardLocks', $change, '变更执行必须持有用户日期锁');
        $this->assertContains('UserBookingConflictService::dateLockName', $change, '变更锁必须按用户+人员+日期生成');
        $this->assertContains('lockUserScope', $change, '改期/加项事务内必须做用户维度当前读');

        $applyDateBody = $this->extractFunctionBody($change, 'applyDateChange');
        $this->assertContains('assertBookable', $applyDateBody, '改期申请阶段必须校验重复预约');

        $applyAddBody = $this->extractFunctionBody($change, 'applyAddItem');
        $this->assertContains('assertBookable', $applyAddBody, '加项申请阶段必须校验重复预约');

        $executeDateBody = $this->extractFunctionBody($change, 'executeDateChange');
        $this->assertContains('assertBookable', $executeDateBody, '改期执行阶段必须再次校验重复预约');

        $executeAddBody = $this->extractFunctionBody($change, 'executeAddItem');
        $this->assertContains('assertBookable', $executeAddBody, '加项执行阶段必须再次校验重复预约');

        $pause = $this->readSource('app/common/model/order/OrderPause.php');
        $resumeBody = $this->extractFunctionBody($pause, 'resumeOrder');
        $this->assertContains('assertBookable', $resumeBody, '暂停恢复改期必须校验重复预约');
        $this->assertContains('acquireLocks', $resumeBody, '暂停恢复必须持有用户日期锁');
        $this->assertContains('lockUserScope', $resumeBody, '暂停恢复事务内必须做用户维度当前读');
    }

    private function testLockCoversCommit(): void
    {
        $apiOrder = $this->readSource('app/api/logic/OrderLogic.php');
        $createBody = $this->extractFunctionBody($apiOrder, 'createOrder');
        $acquirePos = strpos($createBody, 'UserBookingConflictService::acquireForItems');
        $commitPos = strpos($createBody, 'Db::commit()');
        $releasePos = strpos($createBody, 'UserBookingConflictService::releaseLocks($guardLocks)');
        $this->assertTrue(
            $acquirePos !== false && $commitPos !== false && $releasePos !== false
                && $acquirePos < $commitPos && $commitPos < $releasePos,
            '用户日期锁必须在事务提交后才释放'
        );

        $staffManual = $this->readSource('app/common/service/StaffManualOrderService.php');
        $this->assertContains('finally', $staffManual, '服务人员录单必须在 finally 中释放外层锁');
    }

    private function readSource(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('无法读取文件：' . $path);
        }
        return $contents;
    }

    private function extractFunctionBody(string $source, string $functionName): string
    {
        $position = strpos($source, 'function ' . $functionName . '(');
        if ($position === false) {
            throw new RuntimeException('无法定位函数：' . $functionName);
        }

        $braceStart = strpos($source, '{', $position);
        if ($braceStart === false) {
            throw new RuntimeException('无法定位函数体：' . $functionName);
        }

        $depth = 0;
        $length = strlen($source);
        for ($i = $braceStart; $i < $length; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($source, $braceStart, $i - $braceStart + 1);
                }
            }
        }

        throw new RuntimeException('函数体未闭合：' . $functionName);
    }

    private function assertContains(string $needle, string $haystack, string $message): void
    {
        $this->assertions++;
        if (!str_contains($haystack, $needle)) {
            throw new RuntimeException($message . '，缺少片段：' . $needle);
        }
    }

    private function assertTrue(bool $condition, string $message): void
    {
        $this->assertions++;
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    private function assertFalse(bool $condition, string $message): void
    {
        $this->assertions++;
        if ($condition) {
            throw new RuntimeException($message);
        }
    }
}

try {
    (new UserBookingConflictTest())->run();
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL - ' . $e->getMessage() . "\n");
    exit(1);
}
