<?php
// +----------------------------------------------------------------------
// | 素材账号隔离轻量回归测试
// +----------------------------------------------------------------------
// | 说明：当前项目未配置 PHPUnit，本脚本通过源码契约扫描验证素材隔离关键入口。
// | 可直接执行：php tests/regression/MaterialScopeTest.php
// +----------------------------------------------------------------------

declare(strict_types=1);

final class MaterialScopeTest
{
    private int $assertions = 0;
    private string $root;

    public function __construct()
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function run(): void
    {
        $this->testAdminUploadWritesCurrentAdminId();
        $this->testMaterialListsUseVisibleScope();
        $this->testFileOperationsValidateVisibleScope();
        $this->testFrontendUploadVisibilityUsesStaffBinding();
        $this->testStaffRoleCannotSeeLegacyPublicMaterials();
        $this->testUploadAutoCreatesNamedCategory();
        $this->testUnGroupedFilterUsesCidZero();

        echo 'OK - ' . $this->assertions . " assertions\n";
    }

    private function testAdminUploadWritesCurrentAdminId(): void
    {
        $source = $this->read('app/adminapi/controller/UploadController.php');

        $this->assertContains('UploadService::image($cid, $this->adminId)', $source, '后台图片上传必须写入当前后台管理员ID');
        $this->assertContains('UploadService::video($cid, $this->adminId)', $source, '后台视频上传必须写入当前后台管理员ID');
        $this->assertContains('UploadService::file($cid, $this->adminId)', $source, '后台文件上传必须写入当前后台管理员ID');
        $this->assertSame(3, substr_count($source, 'FileLogic::resolveUploadCateId'), '后台上传必须先解析自动分组');
    }

    private function testMaterialListsUseVisibleScope(): void
    {
        $source = $this->read('app/adminapi/lists/file/FileLists.php');

        $this->assertSame(2, substr_count($source, 'FileLogic::applyVisibleScope'), '素材列表和数量统计都必须应用可见范围');
        $this->assertContains('$this->adminId', $source, '素材列表范围必须使用当前后台管理员ID');
        $this->assertContains('$this->adminInfo', $source, '素材列表范围必须使用当前后台管理员信息');
    }

    private function testFileOperationsValidateVisibleScope(): void
    {
        $source = $this->read('app/adminapi/logic/FileLogic.php');
        $controller = $this->read('app/adminapi/controller/FileController.php');

        $this->assertContains('function canAccessFileIds', $source, '文件操作必须具备统一素材权限校验入口');
        $this->assertContains('function assertCanAccessFiles', $source, '移动、重命名、删除必须复用素材权限校验');
        $this->assertContains('无权限操作部分素材', $source, '越权操作必须返回明确错误');
        $this->assertContains('该分组包含无权限操作的素材，无法删除', $source, '删除分组必须拦截包含不可操作素材的分组');
        $this->assertSame(4, substr_count($controller, '$this->adminId, $this->adminInfo'), '素材操作控制器必须传入当前后台账号上下文');
    }

    private function testFrontendUploadVisibilityUsesStaffBinding(): void
    {
        $source = $this->read('app/adminapi/logic/FileLogic.php');

        $this->assertContains('use app\\common\\model\\staff\\Staff;', $source, '前台素材可见范围必须复用服务人员绑定关系');
        $this->assertContains('Staff::where(\'admin_id\', $adminId)', $source, '必须通过 la_staff.admin_id 查找绑定前台用户');
        $this->assertContains("->column('user_id')", $source, '必须使用绑定的 la_staff.user_id 过滤前台上传素材');
        $this->assertContains('FileEnum::SOURCE_USER', $source, '前台上传素材必须单独按来源过滤');
        $this->assertContains('FileEnum::SOURCE_ADMIN', $source, '后台上传素材必须保留后台来源过滤');
    }

    private function testStaffRoleCannotSeeLegacyPublicMaterials(): void
    {
        $source = $this->read('app/adminapi/logic/FileLogic.php');

        $this->assertContains('use app\\common\\service\\StaffService;', $source, '素材范围必须能识别服务人员角色');
        $this->assertContains('function isStaffMaterialScope', $source, '素材范围必须具备服务人员账号统一判断入口');
        $this->assertContains('StaffService::isStaffRole($adminInfo) || self::hasStaffBindingByAdminId($adminId)', $source, '服务人员角色或服务人员绑定都必须使用更严格的后台素材范围');
        $this->assertContains('function hasStaffBindingByAdminId', $source, '素材范围不能只依赖角色缓存，必须识别 la_staff.admin_id 绑定');
        $this->assertContains('return $adminId > 0 ? [$adminId] : [];', $source, '服务人员后台素材范围不能包含历史公共 source_id=0 素材');
        $this->assertContains('whereRaw(\'1 = 0\')', $source, '无任何可见来源时必须返回空数据');
    }

    private function testUploadAutoCreatesNamedCategory(): void
    {
        $source = $this->read('app/adminapi/logic/FileLogic.php');

        $this->assertContains('function resolveUploadCateId', $source, '上传必须通过统一入口解析素材分组');
        $this->assertContains('function getUploadCateName', $source, '上传自动分组必须生成账号名称');
        $this->assertContains('function getStaffNameByAdminId', $source, '服务人员上传必须优先使用服务人员名称');
        $this->assertContains('function ensureUploadCate', $source, '上传自动分组必须不存在则创建');
        $this->assertContains('FileCate::create', $source, '上传自动分组必须写入文件分类表');
        $this->assertContains('mb_substr($name, 0, 32, \'UTF-8\')', $source, '自动分组名称必须限制在表字段长度内');
    }

    private function testUnGroupedFilterUsesCidZero(): void
    {
        $source = $this->read('app/adminapi/lists/file/FileLists.php');

        $this->assertContains("array_key_exists('cid', \$this->params)", $source, '素材分组筛选必须区分全部和未分组');
        $this->assertContains("\$this->params['cid'] !== ''", $source, 'cid=0 必须进入筛选逻辑');
        $this->assertContains('$cid = (int)$this->params[\'cid\'];', $source, '分组筛选必须按整数 cid 查询');
        $this->assertContains('if ($cid === 0)', $source, '未分组不能展开 pid=0 下的全部分组');
        $this->assertContains("\$where[] = ['cid', '=', 0];", $source, '未分组必须只查询 cid=0');
        $this->assertContains("\$where[] = ['cid', 'in', \$cateChild];", $source, '普通分组必须继续包含子分组素材');
    }

    private function read(string $relativePath): string
    {
        $path = $this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('无法读取文件：' . $relativePath);
        }
        return $contents;
    }

    private function assertContains(string $needle, string $haystack, string $message): void
    {
        $this->assertions++;
        if (!str_contains($haystack, $needle)) {
            throw new RuntimeException($message);
        }
    }

    private function assertSame(int $expected, int $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException($message . '，期望：' . $expected . '，实际：' . $actual);
        }
    }
}

(new MaterialScopeTest())->run();
