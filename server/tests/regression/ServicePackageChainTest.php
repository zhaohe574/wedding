<?php
// +----------------------------------------------------------------------
// | 服务套餐与预约附加项链路回归测试（20261126PkgHub）
// +----------------------------------------------------------------------
// | 覆盖：
// | 1) 套餐新增/编辑：duration=0、地区价、附加项绑定；未传 region_prices 的局部编辑不清空地区价；
// |    跨人员变更时清理旧附加项绑定并迁移地区价归属；
// | 2) 附加项新增/编辑与人员端/小程序端双向同步 package_ids；局部改状态不清空 original_price 与 image；
// | 3) 存在有效预订时，后台套餐删除 / 人员端删除 / 小程序端删除均被拦截且返回中文错误；
// | 4) 推荐套餐在首条无当前区县价时回退命中下一条有地区价的上架套餐。
// |
// | 执行：php server/tests/regression/ServicePackageChainTest.php
// | 说明：测试夹具全部临时创建，finally 中物理删除，不污染数据库。
// +----------------------------------------------------------------------

declare(strict_types=1);

use app\adminapi\logic\service\AddonLogic;
use app\adminapi\logic\service\PackageLogic;
use app\adminapi\logic\staff\StaffLogic;
use app\api\logic\StaffCenterLogic;
use app\common\model\package\PackageBooking;
use app\common\model\service\ServicePackageAddon;
use app\common\model\service\ServicePackageRegionPrice;
use app\common\service\BookingFlowService;
use app\common\service\PasswordService;
use app\common\service\StaffService;
use app\common\model\staff\Staff;
use think\facade\Db;

require __DIR__ . '/../../vendor/autoload.php';

$app = new \think\App(dirname(__DIR__, 2));
$app->initialize();

final class ServicePackageChainTest
{
    private int $assertions = 0;

    /** @var int[] */
    private array $userIds = [];
    /** @var int[] */
    private array $adminIds = [];
    /** @var int[] */
    private array $staffIds = [];
    /** @var int[] */
    private array $categoryIds = [];
    /** @var int[] */
    private array $cityPoolIds = [];
    /** @var int[] */
    private array $packageIds = [];
    /** @var int[] */
    private array $addonIds = [];
    /** @var int[] */
    private array $bookingIds = [];

    private string $token = '';

    public function run(): void
    {
        $this->token = 'pkg' . date('YmdHis') . random_int(1000, 9999);

        try {
            $fixtures = $this->createFixtures();
            $this->testPackageLogic($fixtures);
            $this->testAddonSync($fixtures);
            $this->testBookingGuards($fixtures);
            $this->testRecommendedPackageFallback($fixtures);
        } finally {
            $this->cleanup();
        }

        echo 'OK - ' . $this->assertions . " assertions\n";
    }

    // 1) 套餐新增/编辑：duration=0、地区价、附加项、跨人员迁移
    private function testPackageLogic(array $fixtures): void
    {
        $staffA = (int)$fixtures['staff_a'];
        $staffB = (int)$fixtures['staff_b'];

        $addonA = $this->createAddon($staffA, '附加项A' . $this->token, 20.0);
        $addonB = $this->createAddon($staffB, '附加项B' . $this->token, 30.0);

        $regionPrices = $this->districtRegionPrice(128.5);

        $result = PackageLogic::add([
            'staff_id' => $staffA,
            'name' => '回归套餐' . $this->token,
            'price' => 100,
            'original_price' => 150,
            'duration' => 0,
            'region_prices' => $regionPrices,
            'addon_ids' => [$addonA],
        ]);
        $this->assertTrue($result, 'PackageLogic::add 必须成功：' . PackageLogic::getError());

        $packageId = (int)Db::name('service_package')
            ->where('staff_id', $staffA)
            ->where('name', '回归套餐' . $this->token)
            ->value('id');
        $this->trackPackage($packageId);
        $this->assertTrue($packageId > 0, '必须创建出套餐记录');

        $row = Db::name('service_package')->where('id', $packageId)->find();
        $this->assertSame(0, (int)$row['duration'], 'duration=0（不限/全天）必须原样保存');
        $this->assertSame(1, (int)Db::name('service_package_region_price')->where('package_id', $packageId)->count(), '新增套餐必须写入地区价');
        $this->assertSame([$addonA], ServicePackageAddon::getAddonIds($packageId), '新增套餐必须绑定所选附加项');

        // 未传 region_prices / addon_ids 的局部编辑：不得误清空
        $result = PackageLogic::edit([
            'id' => $packageId,
            'staff_id' => $staffA,
            'name' => '回归套餐改名' . $this->token,
            'price' => 110,
        ]);
        $this->assertTrue($result, 'PackageLogic::edit 局部编辑必须成功：' . PackageLogic::getError());
        $this->assertSame(1, (int)Db::name('service_package_region_price')->where('package_id', $packageId)->count(), '未传地区价时不得清空已有地区价');
        $this->assertSame([$addonA], ServicePackageAddon::getAddonIds($packageId), '未传 addon_ids 时不得清空已有绑定');

        // 跨人员变更且未传 region_prices / addon_ids：迁移地区价归属、清理旧人员附加项
        $result = PackageLogic::edit([
            'id' => $packageId,
            'staff_id' => $staffB,
            'name' => '回归套餐换人' . $this->token,
            'price' => 110,
        ]);
        $this->assertTrue($result, 'PackageLogic::edit 跨人员编辑必须成功：' . PackageLogic::getError());
        $this->assertSame([], ServicePackageAddon::getAddonIds($packageId), '跨人员变更必须清理旧附加项绑定');
        $regionRow = Db::name('service_package_region_price')->where('package_id', $packageId)->find();
        $this->assertTrue($regionRow !== null, '跨人员变更不得误删已有地区价');
        $this->assertSame($staffB, (int)$regionRow['staff_id'], '跨人员变更必须把已有地区价归属迁移到新人员');

        // 换人后重新选择附加项
        $result = PackageLogic::edit([
            'id' => $packageId,
            'staff_id' => $staffB,
            'name' => '回归套餐换人' . $this->token,
            'addon_ids' => [$addonB],
        ]);
        $this->assertTrue($result, 'PackageLogic::edit 重新绑定附加项必须成功：' . PackageLogic::getError());
        $this->assertSame([$addonB], ServicePackageAddon::getAddonIds($packageId), '换人后必须按新人员的附加项重建绑定');
    }

    // 2) 附加项双向同步与局部更新不覆盖
    private function testAddonSync(array $fixtures): void
    {
        $staffA = (int)$fixtures['staff_a'];
        $userId = (int)$fixtures['user_id'];

        $packageA = $this->createPackage($staffA, '附加项联动套餐A' . $this->token);
        $packageA2 = $this->createPackage($staffA, '附加项联动套餐A2' . $this->token);

        // AddonLogic::add 支持 package_ids
        $result = AddonLogic::add([
            'staff_id' => $staffA,
            'name' => '后台附加项' . $this->token,
            'price' => 11,
            'original_price' => 22,
            'image' => 'uploads/regression/admin-addon.png',
            'package_ids' => [$packageA],
        ]);
        $this->assertTrue($result, 'AddonLogic::add 必须成功：' . AddonLogic::getError());
        $addonId = (int)Db::name('service_addon')
            ->where('staff_id', $staffA)
            ->where('name', '后台附加项' . $this->token)
            ->value('id');
        $this->trackAddon($addonId);
        $this->assertSame([$packageA], ServicePackageAddon::getPackageIds($addonId), 'AddonLogic::add 必须同步 package_ids');

        $detail = AddonLogic::detail($addonId);
        $this->assertSame([$packageA], $detail['package_ids'] ?? null, 'AddonLogic::detail 必须返回 package_ids');

        // 局部改上下架：original_price / image / package_ids 均不得被覆盖
        $result = AddonLogic::edit([
            'id' => $addonId,
            'staff_id' => $staffA,
            'is_show' => 0,
        ]);
        $this->assertTrue($result, 'AddonLogic::edit 局部状态编辑必须成功：' . AddonLogic::getError());
        $row = Db::name('service_addon')->where('id', $addonId)->find();
        $this->assertSame(0, (int)$row['is_show'], '局部状态编辑必须写入 is_show');
        $this->assertSame('22.00', (string)$row['original_price'], '局部状态编辑不得清空 original_price');
        $this->assertSame('uploads/regression/admin-addon.png', (string)$row['image'], '局部状态编辑不得清空 image');
        $this->assertSame([$packageA], ServicePackageAddon::getPackageIds($addonId), '局部状态编辑不得清空 package_ids');

        // StaffLogic::createStaffAddon 支持 package_ids
        $result = StaffLogic::createStaffAddon($staffA, [
            'name' => '人员端附加项' . $this->token,
            'price' => 33,
            'original_price' => 44,
            'image' => 'uploads/regression/staff-addon.png',
            'package_ids' => [$packageA],
        ]);
        $this->assertTrue($result, 'StaffLogic::createStaffAddon 必须成功：' . StaffLogic::getError());
        $staffAddonId = (int)Db::name('service_addon')
            ->where('staff_id', $staffA)
            ->where('name', '人员端附加项' . $this->token)
            ->value('id');
        $this->trackAddon($staffAddonId);
        $this->assertSame([$packageA], ServicePackageAddon::getPackageIds($staffAddonId), 'StaffLogic::createStaffAddon 必须同步 package_ids');

        // StaffLogic::updateStaffAddon 局部状态：不得清空 original_price / image
        $result = StaffLogic::updateStaffAddon($staffA, $staffAddonId, ['is_show' => 0]);
        $this->assertTrue($result, 'StaffLogic::updateStaffAddon 局部状态编辑必须成功：' . StaffLogic::getError());
        $row = Db::name('service_addon')->where('id', $staffAddonId)->find();
        $this->assertSame(0, (int)$row['is_show'], '人员端局部状态编辑必须写入 is_show');
        $this->assertSame('44.00', (string)$row['original_price'], '人员端局部状态编辑不得清空 original_price');
        $this->assertSame('uploads/regression/staff-addon.png', (string)$row['image'], '人员端局部状态编辑不得清空 image');
        $this->assertSame([$packageA], ServicePackageAddon::getPackageIds($staffAddonId), '人员端局部状态编辑不得清空 package_ids');

        // StaffLogic::updateStaffAddon 换绑套餐
        $result = StaffLogic::updateStaffAddon($staffA, $staffAddonId, ['original_price' => 66, 'package_ids' => [$packageA2]]);
        $this->assertTrue($result, 'StaffLogic::updateStaffAddon 换绑套餐必须成功：' . StaffLogic::getError());
        $this->assertSame([$packageA2], ServicePackageAddon::getPackageIds($staffAddonId), 'StaffLogic::updateStaffAddon 必须同步新的 package_ids');
        $this->assertSame('66.00', (string)Db::name('service_addon')->where('id', $staffAddonId)->value('original_price'), '显式传入 original_price 时必须更新');

        // 小程序端新增/更新：双向同步 package_ids，局部更新保留原值
        $result = StaffCenterLogic::addonAdd($userId, [
            'name' => '小程序附加项' . $this->token,
            'price' => 55,
            'original_price' => 77,
            'image' => 'uploads/regression/center-addon.png',
            'package_ids' => [$packageA2],
        ]);
        $this->assertTrue($result, 'StaffCenterLogic::addonAdd 必须成功：' . StaffCenterLogic::getError());
        $centerAddonId = (int)Db::name('service_addon')
            ->where('staff_id', $staffA)
            ->where('name', '小程序附加项' . $this->token)
            ->value('id');
        $this->trackAddon($centerAddonId);
        $this->assertSame([$packageA2], ServicePackageAddon::getPackageIds($centerAddonId), 'StaffCenterLogic::addonAdd 必须同步 package_ids');

        $result = StaffCenterLogic::addonUpdate($userId, $centerAddonId, ['is_show' => 0]);
        $this->assertTrue($result, 'StaffCenterLogic::addonUpdate 局部更新必须成功：' . StaffCenterLogic::getError());
        $row = Db::name('service_addon')->where('id', $centerAddonId)->find();
        $this->assertSame(0, (int)$row['is_show'], '小程序端局部更新必须写入 is_show');
        $this->assertSame('77.00', (string)$row['original_price'], '小程序端局部更新不得清空 original_price');
        $this->assertSame('uploads/regression/center-addon.png', (string)$row['image'], '小程序端局部更新不得清空 image');
        $this->assertSame([$packageA2], ServicePackageAddon::getPackageIds($centerAddonId), '小程序端局部更新不得清空 package_ids');

        // 列表接口 package_ids 走有效套餐过滤，不产生脏绑定
        $list = StaffCenterLogic::addonLists($userId, ['page_size' => 100]);
        $found = null;
        foreach ((array)($list['data'] ?? []) as $item) {
            if ((int)($item['id'] ?? 0) === $centerAddonId) {
                $found = $item;
                break;
            }
        }
        $this->assertTrue($found !== null, '小程序附加项列表必须包含新增记录');
        $this->assertSame([$packageA2], $found['package_ids'] ?? null, '小程序附加项列表的 package_ids 必须为有效套餐');
    }

    // 3) 有效预订拦截删除
    private function testBookingGuards(array $fixtures): void
    {
        $staffA = (int)$fixtures['staff_a'];
        $userId = (int)$fixtures['user_id'];

        $packageId = $this->createPackage($staffA, '预订拦截套餐' . $this->token);
        $now = time();
        $bookingIds = Db::name('package_booking')->insertAll([
            [
                'package_id' => $packageId,
                'staff_id' => $staffA,
                'booking_date' => date('Y-m-d', strtotime('+30 days')),
                'time_slot' => 0,
                'status' => PackageBooking::STATUS_TEMP_LOCK,
                'version' => 1,
                'create_time' => $now,
            ],
            [
                'package_id' => $packageId,
                'staff_id' => $staffA,
                'booking_date' => date('Y-m-d', strtotime('+31 days')),
                'time_slot' => 0,
                'status' => PackageBooking::STATUS_CONFIRMED,
                'version' => 1,
                'create_time' => $now,
            ],
        ]);
        $this->assertTrue((int)$bookingIds > 0, '预订夹具必须写入');

        $result = PackageLogic::delete(['id' => $packageId]);
        $this->assertFalse($result, '存在有效预订时 PackageLogic::delete 必须失败');
        $this->assertContains('预订', PackageLogic::getError(), 'PackageLogic::delete 必须返回预订拦截提示');

        $result = StaffLogic::deleteStaffPackage($staffA, $packageId);
        $this->assertFalse($result, '存在有效预订时 StaffLogic::deleteStaffPackage 必须失败');
        $this->assertTrue(trim((string)StaffLogic::getError()) !== '', 'StaffLogic::deleteStaffPackage 必须返回非空错误信息');
        $this->assertContains('预订', StaffLogic::getError(), 'StaffLogic::deleteStaffPackage 必须透传中文预订拦截提示');

        $result = StaffCenterLogic::packageRemove($userId, $packageId);
        $this->assertFalse($result, '存在有效预订时 StaffCenterLogic::packageRemove 必须失败');
        $this->assertSame('该套餐存在有效预订，无法删除', StaffCenterLogic::getError(), '小程序端必须返回预订拦截中文提示');

        Db::name('package_booking')->where('package_id', $packageId)->delete();
        $this->assertSame(0, (int)Db::name('package_booking')->where('package_id', $packageId)->count(), '预订夹具必须可清理');
    }

    // 4) 推荐套餐按区县价回退
    private function testRecommendedPackageFallback(array $fixtures): void
    {
        $staffC = (int)$fixtures['staff_c'];

        $topPackageId = $this->createPackage($staffC, '推荐位套餐' . $this->token, [
            'is_recommend' => 1,
            'sort' => 100,
            'price' => 500,
            'region_prices' => $this->districtRegionPrice(999.0, '130104', '桥西区'),
        ]);
        $secondPackageId = $this->createPackage($staffC, '回退套餐' . $this->token, [
            'is_recommend' => 0,
            'sort' => 50,
            'price' => 300,
            'region_prices' => $this->districtRegionPrice(318.0),
        ]);

        $method = new \ReflectionMethod(BookingFlowService::class, 'getRecommendedPackage');
        $method->setAccessible(true);

        $resolved = $method->invoke(null, $staffC, $this->regionContext());
        $this->assertSame($secondPackageId, (int)($resolved['id'] ?? 0), '首条套餐无当前区县价时必须回退到下一条有价套餐');
        $this->assertSame(318.0, (float)($resolved['price'] ?? 0), '回退套餐必须使用命中区县的价格');

        $base = $method->invoke(null, $staffC, []);
        $this->assertSame($topPackageId, (int)($base['id'] ?? 0), '无地区上下文时必须取排序第一的套餐');
        $this->assertSame(500.0, (float)($base['price'] ?? 0), '无地区上下文时必须使用基础价');
    }

    // ------------------------------------------------------------------ fixtures

    /**
     * @return array{user_id:int, staff_a:int, staff_b:int, staff_c:int}
     */
    private function createFixtures(): array
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
            'account' => 'pkg_' . $this->token,
            'mobile' => $mobile,
            'nickname' => '套餐链路测试用户',
            'create_time' => $now,
            'update_time' => $now,
        ]);
        $this->userIds[] = $userId;

        $adminId = (int)Db::name('admin')->insertGetId([
            'root' => 0,
            'name' => '套餐链路测试人员',
            'avatar' => '',
            'account' => 'pkg_admin_' . $this->token,
            'password' => PasswordService::hash('123456'),
            'multipoint_login' => 1,
            'force_password_reset' => 0,
            'disable' => 0,
            'user_id' => $userId,
            'create_time' => $now,
            'update_time' => $now,
        ]);
        $this->adminIds[] = $adminId;

        $categoryId = (int)Db::name('service_category')->insertGetId([
            'name' => '套餐链路分类' . $this->token,
            'pid' => 0,
            'sort' => 0,
            'is_show' => 1,
            'create_time' => $now,
            'update_time' => $now,
        ]);
        $this->categoryIds[] = $categoryId;

        $this->cityPoolIds[] = (int)Db::name('service_city_pool')->insertGetId([
            'province_code' => '130000',
            'province_name' => '河北省',
            'city_code' => '130100',
            'city_name' => '石家庄市',
            'sort' => 0,
            'status' => 1,
            'create_time' => $now,
            'update_time' => $now,
        ]);

        $staffA = $this->createStaff($categoryId, $userId, $adminId, '套餐链路人员A');
        $staffB = $this->createStaff($categoryId, 0, 0, '套餐链路人员B');
        $staffC = $this->createStaff($categoryId, 0, 0, '套餐链路人员C');

        return [
            'user_id' => $userId,
            'staff_a' => $staffA,
            'staff_b' => $staffB,
            'staff_c' => $staffC,
        ];
    }

    private function createStaff(int $categoryId, int $userId, int $adminId, string $name): int
    {
        $now = time();
        do {
            $mobile = '13' . str_pad((string)random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (Db::name('staff')->where('mobile', $mobile)->count() > 0);

        $staffId = (int)Db::name('staff')->insertGetId([
            'user_id' => $userId,
            'admin_id' => $adminId,
            'sn' => Staff::generateSn(),
            'name' => $name . $this->token,
            'avatar' => 'uploads/regression/staff.png',
            'mobile' => $mobile,
            'mobile_full' => $mobile,
            'category_id' => $categoryId,
            'list_intro' => '套餐链路测试',
            'rating' => 5,
            'sort' => 0,
            'is_recommend' => 0,
            'status' => 1,
            'audit_status' => 1,
            'create_time' => $now,
            'update_time' => $now,
        ]);
        $this->staffIds[] = $staffId;

        return $staffId;
    }

    private function createAddon(int $staffId, string $name, float $price): int
    {
        $result = AddonLogic::add([
            'staff_id' => $staffId,
            'name' => $name,
            'price' => $price,
        ]);
        $this->assertTrue($result, '附加项夹具必须创建成功：' . AddonLogic::getError());

        $addonId = (int)Db::name('service_addon')
            ->where('staff_id', $staffId)
            ->where('name', $name)
            ->value('id');
        $this->trackAddon($addonId);

        return $addonId;
    }

    private function createPackage(int $staffId, string $name, array $extra = []): int
    {
        $result = PackageLogic::add(array_merge([
            'staff_id' => $staffId,
            'name' => $name,
            'price' => 100,
            'duration' => 0,
        ], $extra));
        $this->assertTrue($result, '套餐夹具必须创建成功：' . PackageLogic::getError());

        $packageId = (int)Db::name('service_package')
            ->where('staff_id', $staffId)
            ->where('name', $name)
            ->value('id');
        $this->trackPackage($packageId);

        return $packageId;
    }

    /**
     * @param float $price
     * @param string $districtCode
     * @param string $districtName
     * @return array<int, array<string, mixed>>
     */
    private function districtRegionPrice(float $price, string $districtCode = '130102', string $districtName = '长安区'): array
    {
        return [[
            'region_level' => ServicePackageRegionPrice::LEVEL_DISTRICT,
            'province_code' => '130000',
            'province_name' => '河北省',
            'city_code' => '130100',
            'city_name' => '石家庄市',
            'district_code' => $districtCode,
            'district_name' => $districtName,
            'price' => $price,
        ]];
    }

    private function regionContext(): array
    {
        return [
            'province_code' => '130000',
            'province_name' => '河北省',
            'city_code' => '130100',
            'city_name' => '石家庄市',
            'district_code' => '130102',
            'district_name' => '长安区',
        ];
    }

    private function trackPackage(int $packageId): void
    {
        if ($packageId > 0) {
            $this->packageIds[] = $packageId;
        }
    }

    private function trackAddon(int $addonId): void
    {
        if ($addonId > 0) {
            $this->addonIds[] = $addonId;
        }
    }

    private function cleanup(): void
    {
        try {
            if ($this->packageIds) {
                Db::name('package_booking')->whereIn('package_id', $this->packageIds)->delete();
                Db::name('service_package_addon')->whereIn('package_id', $this->packageIds)->delete();
                Db::name('service_package_region_price')->whereIn('package_id', $this->packageIds)->delete();
                Db::name('service_package')->whereIn('id', $this->packageIds)->delete();
            }
            if ($this->addonIds) {
                Db::name('service_package_addon')->whereIn('addon_id', $this->addonIds)->delete();
                Db::name('service_addon')->whereIn('id', $this->addonIds)->delete();
            }
            if ($this->cityPoolIds) {
                Db::name('service_city_pool')->whereIn('id', $this->cityPoolIds)->delete();
            }
            if ($this->staffIds) {
                Db::name('staff')->whereIn('id', $this->staffIds)->delete();
            }
            if ($this->adminIds) {
                Db::name('admin')->whereIn('id', $this->adminIds)->delete();
            }
            if ($this->userIds) {
                Db::name('user')->whereIn('id', $this->userIds)->delete();
            }
            if ($this->categoryIds) {
                Db::name('service_category')->whereIn('id', $this->categoryIds)->delete();
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
    (new ServicePackageChainTest())->run();
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL - ' . $e->getMessage() . "\n");
    exit(1);
}
