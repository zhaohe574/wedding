<?php
// +----------------------------------------------------------------------
// | 服务人员资料信息链路回归测试（批次1）
// +----------------------------------------------------------------------
// | 覆盖：
// | 1) la_staff 存在 list_intro 列；
// | 2) 服务人员角色的后台账号可通过 AuthMiddleware 访问资料页 / 服务展示页依赖接口；
// | 3) 管理端与小程序端验证器的 list_intro 36/37 字符边界；
// | 4) 局部保存不覆盖其他字段、avatar 不回写默认图、name 同步 la_admin.name、mobile 双列同步；
// | 5) 小程序列表返回 list_intro 且 keyword 可命中。
// |
// | 执行：php server/tests/regression/StaffProfileChainTest.php
// | 说明：测试数据全部临时创建，finally 中物理删除，不污染数据库。
// +----------------------------------------------------------------------

declare(strict_types=1);

use app\adminapi\http\middleware\AuthMiddleware;
use app\adminapi\logic\auth\AuthLogic;
use app\adminapi\logic\staff\StaffLogic as AdminStaffLogic;
use app\adminapi\validate\staff\StaffValidate;
use app\api\lists\staff\StaffLists as ApiStaffLists;
use app\api\logic\StaffCenterLogic;
use app\api\validate\StaffCenterValidate;
use app\common\model\auth\Admin;
use app\common\model\staff\Staff;
use app\common\service\PasswordService;
use app\common\service\StaffService;
use think\facade\Db;

require __DIR__ . '/../../vendor/autoload.php';

$app = new \think\App(dirname(__DIR__, 2));
$app->initialize();

final class StaffProfileChainTest
{
    private int $assertions = 0;

    /** @var int[] */
    private array $userIds = [];
    /** @var int[] */
    private array $adminIds = [];
    /** @var int[] */
    private array $staffIds = [];

    public function run(): void
    {
        $token = 'qa' . date('YmdHis') . random_int(1000, 9999);

        try {
            $this->testColumnExists();

            $fixtures = $this->createFixtures($token);
            $this->testValidators($fixtures);
            $this->testSelfServicePermission($fixtures);
            $this->testProfileMerge($fixtures);
            $this->testApiList($fixtures, $token);
        } finally {
            $this->cleanup();
        }

        echo 'OK - ' . $this->assertions . " assertions\n";
    }

    // 1) 数据库列
    private function testColumnExists(): void
    {
        $prefix = (string)config('database.connections.mysql.prefix', 'la_');
        $columns = Db::query("SHOW COLUMNS FROM `{$prefix}staff` LIKE 'list_intro'");
        $this->assertTrue(!empty($columns), 'la_staff 必须存在 list_intro 列');
    }

    // 3) 验证器边界：36 个汉字通过，37 个汉字失败
    private function testValidators(array $fixtures): void
    {
        $pass36 = str_repeat('简', 36);
        $fail37 = str_repeat('简', 37);
        $staffId = (int)$fixtures['staff_id'];

        $validate = new StaffValidate();
        $this->assertTrue(
            $validate->scene('myProfile')->check(['id' => $staffId, 'name' => '测试姓名', 'list_intro' => $pass36]),
            'StaffValidate myProfile 场景 36 个汉字必须通过：' . (string)$validate->getError()
        );

        $validate = new StaffValidate();
        $this->assertFalse(
            $validate->scene('myProfile')->check(['id' => $staffId, 'name' => '测试姓名', 'list_intro' => $fail37]),
            'StaffValidate myProfile 场景 37 个汉字必须失败'
        );
        $this->assertContains('列表页简介最多36个字符', (string)$validate->getError(), 'StaffValidate 必须返回 list_intro 中文提示');

        $validate = new StaffCenterValidate();
        $this->assertTrue(
            $validate->scene('profile')->check(['name' => '张三', 'category_id' => 1, 'list_intro' => $pass36]),
            'StaffCenterValidate profile 场景 36 个汉字必须通过：' . (string)$validate->getError()
        );

        $validate = new StaffCenterValidate();
        $this->assertFalse(
            $validate->scene('profile')->check(['name' => '张三', 'category_id' => 1, 'list_intro' => $fail37]),
            'StaffCenterValidate profile 场景 37 个汉字必须失败'
        );
        $this->assertContains('列表页简介最多36个字符', (string)$validate->getError(), 'StaffCenterValidate 必须返回 list_intro 中文提示');
    }

    // 2) 服务人员角色的自助接口放行与依赖权限
    private function testSelfServicePermission(array $fixtures): void
    {
        $adminInfo = $fixtures['admin_info'];
        $allowedUris = [
            ['ops.category', 'tree', 'ops.category/tree'],
            ['ops.category', 'all', 'ops.category/all'],
            ['ops.styleTag', 'all', 'ops.styleTag/all'],
            ['ops.styleTag', 'typeOptions', 'ops.styleTag/typeOptions'],
            ['ops.staff', 'myProfile', 'ops.staff/myProfile'],
            ['ops.staff', 'myProfileUpdate', 'ops.staff/myProfileUpdate'],
            ['ops.staff', 'myProfileBannerList', 'ops.staff/myProfileBannerList'],
            ['ops.staff', 'myProfilePackageConfig', 'ops.staff/myProfilePackageConfig'],
        ];

        foreach ($allowedUris as [$controller, $action, $uri]) {
            $this->assertSame('allowed', $this->runMiddleware($adminInfo, $controller, $action), $uri . ' 必须对服务人员放行');
        }

        $denied = $this->runMiddleware($adminInfo, 'ops.staff', 'notExistAction');
        $this->assertTrue($denied instanceof \think\response\Json, '未登记接口必须返回失败响应');
        $payload = $denied instanceof \think\response\Json ? $denied->getData() : [];
        $this->assertSame(0, (int)($payload['code'] ?? -1), '未登记接口必须被拒绝');
        $this->assertContains('权限未登记', (string)($payload['msg'] ?? ''), '未登记接口必须提示权限未登记');

        $permissions = AuthLogic::getAuthByAdminId((int)$fixtures['admin_id']);
        foreach ([
            'ops.category/tree',
            'ops.category/all',
            'ops.styleTag/all',
            'ops.styleTag/typeOptions',
            'ops.staff/myProfileBannerList',
        ] as $uri) {
            $this->assertTrue(in_array($uri, $permissions, true), 'getAuthByAdminId 必须展开依赖权限：' . $uri);
        }
    }

    // 4) 局部保存、头像、姓名与手机号同步
    private function testProfileMerge(array $fixtures): void
    {
        $staffId = (int)$fixtures['staff_id'];
        $userId = (int)$fixtures['user_id'];
        $adminId = (int)$fixtures['admin_id'];

        $before = Staff::find($staffId);
        $this->assertTrue($before !== null, '临时服务人员必须存在');
        $avatar = (string)$before->getData('avatar');
        $longDetail = (string)$before->getData('long_detail');

        $result = AdminStaffLogic::updateSelfProfile($staffId, ['list_intro' => '后台短简介']);
        $this->assertTrue(is_array($result), '后台 updateSelfProfile 保存 list_intro 必须成功：' . AdminStaffLogic::getError());

        $row = Staff::find($staffId);
        $this->assertSame('后台短简介', (string)$row->getData('list_intro'), '后台局部保存必须写入 list_intro');
        $this->assertSame($longDetail, (string)$row->getData('long_detail'), '后台局部保存不得清空 long_detail');
        $this->assertSame('旧服务说明', (string)$row->getData('service_desc'), '后台局部保存不得覆盖 service_desc');
        $this->assertSame('旧个人简介', (string)$row->getData('profile'), '后台局部保存不得覆盖 profile');
        $this->assertSame($avatar, (string)$row->getData('avatar'), '后台局部保存不得把默认头像回写数据库');

        $result = AdminStaffLogic::updateSelfProfile($staffId, ['name' => '后台新名字']);
        $this->assertTrue(is_array($result), '后台 updateSelfProfile 改名必须成功：' . AdminStaffLogic::getError());
        $this->assertSame('后台新名字', (string)Staff::find($staffId)->getData('name'), '后台改名必须写入 la_staff.name');
        $this->assertSame('后台新名字', (string)Admin::find($adminId)->name, '后台改名必须同步 la_admin.name');

        $mobile = '139' . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $result = AdminStaffLogic::updateSelfProfile($staffId, ['mobile' => $mobile]);
        $this->assertTrue(is_array($result), '后台 updateSelfProfile 修改手机号必须成功：' . AdminStaffLogic::getError());
        $row = Staff::find($staffId);
        $this->assertSame($mobile, (string)$row->getData('mobile'), '后台改手机号必须同步 mobile');
        $this->assertSame($mobile, (string)$row->getData('mobile_full'), '后台改手机号必须同步 mobile_full');

        $intro = '前台短简介';
        $result = StaffCenterLogic::updateProfile($userId, [
            'name' => '前台新名字',
            'list_intro' => $intro,
            'category_id' => 0,
        ]);
        $this->assertTrue(is_array($result), '小程序 updateProfile 必须成功：' . StaffCenterLogic::getError());

        $row = Staff::find($staffId);
        $this->assertSame('前台新名字', (string)$row->getData('name'), '小程序改名必须写入 la_staff.name');
        $this->assertSame('前台新名字', (string)Admin::find($adminId)->name, '小程序改名必须同步 la_admin.name');
        $this->assertSame($intro, (string)$row->getData('list_intro'), '小程序保存必须写入 list_intro');
        $this->assertSame($mobile, (string)$row->getData('mobile'), '小程序局部保存不得覆盖 mobile');
        $this->assertSame($mobile, (string)$row->getData('mobile_full'), '小程序局部保存不得覆盖 mobile_full');
        $this->assertSame($longDetail, (string)$row->getData('long_detail'), '小程序局部保存不得清空 long_detail');
        $this->assertSame('旧服务说明', (string)$row->getData('service_desc'), '小程序局部保存不得覆盖 service_desc');
        $this->assertSame($avatar, (string)$row->getData('avatar'), '小程序局部保存不得把默认头像回写数据库');
    }

    // 5) 小程序列表返回 list_intro 且 keyword 可命中
    private function testApiList(array $fixtures, string $token): void
    {
        $staffId = (int)$fixtures['staff_id'];
        $intro = '唯一关键词' . $token;

        $staff = Staff::find($staffId);
        $this->assertTrue($staff !== null, '临时服务人员必须存在');
        $staff->save(['list_intro' => $intro]);

        request()->setMethod('GET')->withGet([
            'page_type' => 1,
            'page_no' => 1,
            'page_size' => 100,
            'keyword' => $token,
        ]);

        $lists = new ApiStaffLists();
        $rows = $lists->lists();

        $found = null;
        foreach ($rows as $row) {
            if ((int)($row['id'] ?? 0) === $staffId) {
                $found = $row;
                break;
            }
        }

        $this->assertTrue($found !== null, 'keyword 必须能搜出 list_intro 匹配的服务人员');
        $this->assertTrue(is_array($found) && array_key_exists('list_intro', $found), '小程序列表必须返回 list_intro 字段');
        $this->assertSame($intro, (string)($found['list_intro'] ?? ''), '小程序列表 list_intro 必须与库内一致');
        $this->assertSame(1, $lists->count(), 'keyword 命中 list_intro 时 count 必须为 1');
    }

    /**
     * @return array{user_id:int, admin_id:int, staff_id:int, admin_info:array}
     */
    private function createFixtures(string $token): array
    {
        $now = time();

        do {
            $sn = random_int(100000000, 999999999);
        } while (Db::name('user')->where('sn', $sn)->count() > 0);
        do {
            $mobile = '13' . str_pad((string)random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (Db::name('user')->where('mobile', $mobile)->count() > 0);

        $userId = (int)Db::name('user')->insertGetId([
            'sn' => $sn,
            'account' => 'qa_' . $token,
            'mobile' => $mobile,
            'nickname' => '回归测试用户',
            'create_time' => $now,
            'update_time' => $now,
        ]);
        $this->userIds[] = $userId;

        $adminId = (int)Db::name('admin')->insertGetId([
            'root' => 0,
            'name' => '回归测试人员',
            'avatar' => '',
            'account' => 'qa_admin_' . $token,
            'password' => PasswordService::hash('123456'),
            'multipoint_login' => 1,
            'force_password_reset' => 0,
            'disable' => 0,
            'user_id' => $userId,
            'create_time' => $now,
            'update_time' => $now,
        ]);
        $this->adminIds[] = $adminId;

        $roleId = StaffService::getStaffRoleId();
        if ($roleId <= 0) {
            throw new RuntimeException('缺少「服务人员」角色，无法构造测试数据');
        }
        Db::name('admin_role')->insert([
            'admin_id' => $adminId,
            'role_id' => $roleId,
        ]);

        $staffId = (int)Db::name('staff')->insertGetId([
            'user_id' => $userId,
            'admin_id' => $adminId,
            'sn' => Staff::generateSn(),
            'name' => '回归测试人员',
            'avatar' => 'uploads/regression/avatar.png',
            'mobile' => $mobile,
            'mobile_full' => $mobile,
            'category_id' => 0,
            'experience_years' => 3,
            'list_intro' => '旧短简介',
            'profile' => '旧个人简介',
            'service_desc' => '旧服务说明',
            'long_detail' => '{"blocks":[{"type":"text"}]}',
            'rating' => 5,
            'sort' => 0,
            'is_recommend' => 0,
            'status' => 1,
            'audit_status' => 1,
            'create_time' => $now,
            'update_time' => $now,
        ]);
        $this->staffIds[] = $staffId;

        return [
            'user_id' => $userId,
            'admin_id' => $adminId,
            'staff_id' => $staffId,
            'admin_info' => [
                'admin_id' => $adminId,
                'root' => 0,
                'role_id' => [$roleId],
                'login_ip' => request()->ip(),
                'force_password_reset' => 0,
            ],
        ];
    }

    /**
     * 用最小请求对象直接跑 AuthMiddleware；放行时闭包返回 allowed。
     *
     * @return mixed
     */
    private function runMiddleware(array $adminInfo, string $controller, string $action)
    {
        $request = new \think\Request();
        $request->controllerObject = new class {
            public function isNotNeedLogin(): bool
            {
                return false;
            }
        };
        $request->adminInfo = $adminInfo;
        $request->setController($controller);
        $request->setAction($action);

        return (new AuthMiddleware())->handle($request, static fn() => 'allowed');
    }

    private function cleanup(): void
    {
        try {
            if ($this->staffIds) {
                Db::name('staff')->whereIn('id', $this->staffIds)->delete();
            }
            if ($this->adminIds) {
                Db::name('admin_role')->whereIn('admin_id', $this->adminIds)->delete();
                Db::name('admin')->whereIn('id', $this->adminIds)->delete();
            }
            if ($this->userIds) {
                Db::name('user')->whereIn('id', $this->userIds)->delete();
            }
        } catch (Throwable $e) {
            fwrite(STDERR, '临时数据清理失败：' . $e->getMessage() . "\n");
        }
    }

    private function assertContains(string $needle, string $haystack, string $message): void
    {
        $this->assertions++;
        if (!str_contains($haystack, $needle)) {
            throw new RuntimeException($message . '，实际：' . $haystack);
        }
    }

    private function assertSame($expected, $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException($message . '，期望：' . var_export($expected, true) . '，实际：' . var_export($actual, true));
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
    (new StaffProfileChainTest())->run();
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL - ' . $e->getMessage() . "\n");
    exit(1);
}
