<?php
// +----------------------------------------------------------------------
// | 素材账号隔离 + 公共素材库轻量回归测试
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
        $this->testAdminUploadResolvesScopeAndSourceId();
        $this->testMaterialListsUseVisibleScope();
        $this->testFileOperationsValidateVisibleScope();
        $this->testFrontendUploadVisibilityUsesStaffBinding();
        $this->testMaterialManagerContract();
        $this->testNonManagerCannotSeeLegacyPublicMaterials();
        $this->testPublicScopeQueriesAreSeparated();
        $this->testCateCrudRejectsCrossSpaceOperations();
        $this->testMoveSeparatesPublicAndPrivateMaterials();
        $this->testListsExposeOwnerMetadata();
        $this->testFileCateListsUsesCateScope();
        $this->testUploadAutoCreatesNamedCategory();
        $this->testUnGroupedFilterUsesCidZero();
        $this->testValidatorScopeRule();
        $this->testSqlAndMigrationContract();

        echo 'OK - ' . $this->assertions . " assertions\n";
    }

    private function testAdminUploadResolvesScopeAndSourceId(): void
    {
        $source = $this->read('app/adminapi/controller/UploadController.php');

        $this->assertContains('UploadService::image($cid, $sourceId)', $source, '后台图片上传必须写入解析后的上传者ID');
        $this->assertContains('UploadService::video($cid, $sourceId)', $source, '后台视频上传必须写入解析后的上传者ID');
        $this->assertContains('UploadService::file($cid, $sourceId)', $source, '后台文件上传必须写入解析后的上传者ID');
        $this->assertSame(3, substr_count($source, 'FileLogic::resolveUploadCateId'), '后台上传必须先解析自动分组');
        $this->assertSame(3, substr_count($source, 'FileLogic::resolveUploadSourceId'), '后台上传必须先解析上传者ID（公共素材固定为0）');
        $this->assertSame(3, substr_count($source, "post('scope', FileLogic::SCOPE_MINE)"), '后台上传必须读取素材空间范围参数');
    }

    private function testMaterialListsUseVisibleScope(): void
    {
        $source = $this->read('app/adminapi/lists/file/FileLists.php');

        $this->assertContains('implements ListsSearchInterface, ListsExtendInterface', $source, '素材列表必须实现扩展字段接口');
        $this->assertContains('function extend', $source, '素材列表必须返回前端所需扩展字段');
        $this->assertSame(2, substr_count($source, 'FileLogic::applyVisibleScope'), '素材列表和数量统计都必须应用可见范围');
        $this->assertContains('$this->adminId', $source, '素材列表范围必须使用当前后台管理员ID');
        $this->assertContains('$this->adminInfo', $source, '素材列表范围必须使用当前后台管理员信息');
        $this->assertContains("\$this->params['scope'] ?? FileLogic::SCOPE_MINE", $source, '素材列表必须读取素材空间范围参数');
        $this->assertContains("\$this->params['owner_admin_id'] ?? ''", $source, '素材列表必须读取归属账号筛选参数');
        $this->assertContains('source,source_id', $source, '素材列表必须补查归属来源字段');
    }

    private function testFileOperationsValidateVisibleScope(): void
    {
        $source = $this->read('app/adminapi/logic/FileLogic.php');
        $controller = $this->read('app/adminapi/controller/FileController.php');

        $this->assertContains('function canAccessFileIds', $source, '文件操作必须具备统一素材权限校验入口');
        $this->assertContains('function assertCanAccessFiles', $source, '移动、重命名、删除必须复用素材权限校验');
        $this->assertContains('function visibleFileIds', $source, '非管理员素材校验必须按本人素材范围匹配');
        $this->assertContains('self::applyVisibleScope($query, $adminId, $adminInfo, self::SCOPE_MINE);', $source, '非管理员素材校验必须显式使用个人空间范围');
        $this->assertContains('无权限操作部分素材', $source, '越权操作必须返回明确错误');
        $this->assertContains('该分组包含无权限操作的素材，无法删除', $source, '删除分组必须拦截包含不可操作素材的分组');
        $this->assertSame(6, substr_count($controller, '$this->adminId, $this->adminInfo'), '素材操作控制器必须传入当前后台账号上下文');
    }

    private function testFrontendUploadVisibilityUsesStaffBinding(): void
    {
        $source = $this->read('app/adminapi/logic/FileLogic.php');

        $this->assertContains('use app\\common\\model\\staff\\Staff;', $source, '前台素材可见范围必须复用服务人员绑定关系');
        $this->assertContains('function getBoundUserIdsByAdminId', $source, '必须保留服务人员绑定入口');
        $this->assertContains('Staff::where(\'admin_id\', $adminId)', $source, '必须通过 la_staff.admin_id 查找绑定前台用户');
        $this->assertContains("->column('user_id')", $source, '必须使用绑定的 la_staff.user_id 过滤前台上传素材');
        $this->assertContains('FileEnum::SOURCE_USER', $source, '前台上传素材必须单独按来源过滤');
        $this->assertContains('FileEnum::SOURCE_ADMIN', $source, '后台上传素材必须保留后台来源过滤');
    }

    private function testMaterialManagerContract(): void
    {
        $source = $this->read('app/adminapi/logic/FileLogic.php');

        $this->assertContains("public const SCOPE_MINE = 'mine';", $source, '素材空间必须定义个人范围常量');
        $this->assertContains("public const SCOPE_PUBLIC = 'public';", $source, '素材空间必须定义公共范围常量');
        $this->assertContains("public const SCOPE_ALL = 'all';", $source, '素材空间必须定义全部范围常量');
        $this->assertContains('function isMaterialManager', $source, '素材管理员判断必须有统一入口');
        $this->assertContains('function normalizeScope', $source, '素材空间范围必须有规范化入口');
        $this->assertContains('self::SCOPE_ALL && !self::isMaterialManager($adminId, $adminInfo)', $source, '非管理员请求全部素材必须强制降级为个人空间');
        $this->assertContains("SystemRole::where('name', '管理员')", $source, '素材管理员必须匹配「管理员」角色');
        $this->assertContains('AdminRole::where(\'admin_id\', $adminId)->column(\'role_id\')', $source, '缺少角色缓存时必须回查账号角色关联');
        $this->assertContains("'仅管理员可上传公共素材'", $source, '公共素材上传必须拦截非管理员');
        $this->assertContains("'仅管理员可管理公共素材分组'", $source, '公共素材分组管理必须拦截非管理员');
    }

    private function testNonManagerCannotSeeLegacyPublicMaterials(): void
    {
        $source = $this->read('app/adminapi/logic/FileLogic.php');

        $this->assertContains('whereRaw(\'1 = 0\')', $source, '无任何可见来源时必须返回空数据');
        $this->assertContains('return $adminId > 0 ? $adminId : 0;', $source, '个人上传必须写入当前后台账号ID');
        $this->assertContains("return \$query->where('source', FileEnum::SOURCE_ADMIN)\n                ->where('source_id', 0);", $source, '公共素材空间必须严格限定 source_id=0');
        $this->assertContains('// SCOPE_MINE：仅本人后台素材 + 绑定服务人员的前台素材，绝不混入公共素材（source_id=0）', $source, '个人素材空间必须显式排除公共素材');
    }

    private function testPublicScopeQueriesAreSeparated(): void
    {
        $source = $this->read('app/adminapi/logic/FileLogic.php');

        $this->assertSame(3, substr_count($source, "->where('is_public', 1)"), '公共分组查询与上传校验必须按 is_public=1 过滤');
        $this->assertContains("->where('is_public', 0)\n            ->where('admin_id', \$adminId)", $source, '个人分组查询必须限定本人个人分组');
        $this->assertContains("->where('is_public', 0)\n                    ->where('admin_id', \$targetAdminId)", $source, '全部视图按账号筛选时必须限定该账号个人分组');
        $this->assertContains('function applyCateVisibleScope', $source, '分组列表必须有独立可见范围入口');
        $this->assertContains('function resolveUploadSourceId', $source, '上传者ID解析必须有独立入口');
    }

    private function testCateCrudRejectsCrossSpaceOperations(): void
    {
        $source = $this->read('app/adminapi/logic/FileLogic.php');

        $this->assertContains('function canManageCate', $source, '分组管理必须有统一权限判断');
        $this->assertContains('(int)$cate->is_public !== 1 && (int)$cate->admin_id === $adminId', $source, '非管理员仅可操作自己的个人分组');
        $this->assertContains("'分组不存在'", $source, '分组编辑与删除必须校验分组存在');
        $this->assertSame(2, substr_count($source, "'无权限操作该分组'"), '分组编辑与删除必须拦截无权限分组');
        $this->assertContains("'上级分组无权使用或不存在'", $source, '新增分组必须校验父分组归属一致');
        $this->assertContains("'is_public' => \$isPublic,\n            'admin_id' => \$ownerAdminId,", $source, '新增分组必须写入公共标识与归属账号');
        $this->assertContains("'is_public' => \$isPublic,\n            'admin_id' => \$ownerAdminId,\n            'create_time' => time(),", $source, '上传自动分组必须写入公共标识与归属账号');
        $this->assertContains("->where('is_public', \$isPublic)\n            ->where('admin_id', \$ownerAdminId)", $source, '上传自动分组查询必须限定公共标识与归属账号');
    }

    private function testMoveSeparatesPublicAndPrivateMaterials(): void
    {
        $source = $this->read('app/adminapi/logic/FileLogic.php');

        $this->assertContains("'公共素材与个人素材不能同时移动'", $source, '移动必须拦截公共与个人素材混选');
        $this->assertSame(2, substr_count($source, "'公共素材与个人素材不能跨空间移动'"), '移动必须拦截公共与个人分组跨空间');
        $this->assertContains("'目标分组不存在'", $source, '移动必须校验目标分组存在');
        $this->assertSame(2, substr_count($source, "'无权限移动到该分组'"), '移动必须拦截无权限目标分组');
        $this->assertContains("(int)\$targetCate->admin_id !== \$adminId", $source, '非管理员只能移入自己的个人分组');
        $this->assertContains("!in_array((int)\$targetCate->admin_id, \$privateAdminOwnerIds, true)", $source, '管理员跨人移动只能移入素材归属账号的个人分组');
    }

    private function testListsExposeOwnerMetadata(): void
    {
        $fileLists = $this->read('app/adminapi/lists/file/FileLists.php');
        $logic = $this->read('app/adminapi/logic/FileLogic.php');

        $this->assertContains("'can_manage_all' => \$isManager", $fileLists, '扩展字段必须返回全部素材管理能力');
        $this->assertContains("'can_manage_public' => \$isManager", $fileLists, '扩展字段必须返回公共素材管理能力');
        $this->assertContains("'current_scope' =>", $fileLists, '扩展字段必须返回当前生效空间');
        $this->assertContains("'owner_options' => \$isManager ? FileLogic::getOwnerOptions() : []", $fileLists, '归属筛选项必须仅素材管理员可见');
        $this->assertContains('FileLogic::resolveOwnerMeta($lists)', $fileLists, '素材列表必须按批次解析归属元数据');
        $this->assertContains("\$item['is_public'] = \$meta['is_public'];", $fileLists, '素材列表必须返回公共标识');
        $this->assertContains("\$item['owner_name'] = \$meta['owner_name'];", $fileLists, '素材列表必须返回归属名称');
        $this->assertContains("\$item['can_manage'] = \$isManager || \$meta['is_public'] === false;", $fileLists, '素材列表必须区分可管理范围');
        $this->assertContains('function getOwnerOptions', $logic, '归属筛选项必须有统一入口');
        $this->assertContains("[['value' => '0', 'label' => '公共素材']", $logic, '归属筛选项必须包含公共素材');
        $this->assertContains('function resolveOwnerMeta', $logic, '归属元数据必须有统一解析入口');
        $this->assertContains("'前台用户#'", $logic, '前台上传素材必须可区分归属用户');
    }

    private function testFileCateListsUsesCateScope(): void
    {
        $source = $this->read('app/adminapi/lists/file/FileCateLists.php');

        $this->assertSame(2, substr_count($source, 'FileLogic::applyCateVisibleScope'), '分组列表和数量统计都必须应用分组可见范围');
        $this->assertContains('is_public,admin_id', $source, '分组列表必须补查公共标识与归属账号');
        $this->assertContains("\$this->params['scope'] ?? FileLogic::SCOPE_MINE", $source, '分组列表必须读取素材空间范围参数');
        $this->assertContains("\$this->params['owner_admin_id'] ?? ''", $source, '分组列表必须读取归属账号筛选参数');
        $this->assertContains("=== FileLogic::SCOPE_ALL\n            && \$ownerAdminId === ''", $source, '全部视图未筛选账号时必须标注分组归属');
        $this->assertContains("\$item['owner_name'] = \$isPublic", $source, '分组列表必须返回归属名称');
        $this->assertContains('$this->adminId', $source, '分组列表范围必须使用当前后台管理员ID');
        $this->assertContains('$this->adminInfo', $source, '分组列表范围必须使用当前后台管理员信息');
    }

    private function testUploadAutoCreatesNamedCategory(): void
    {
        $source = $this->read('app/adminapi/logic/FileLogic.php');

        $this->assertContains('function resolveUploadCateId', $source, '上传必须通过统一入口解析素材分组');
        $this->assertContains('function getUploadCateName', $source, '上传自动分组必须生成账号名称');
        $this->assertContains('function getStaffNameByAdminId', $source, '服务人员上传必须优先使用服务人员名称');
        $this->assertContains('function ensureUploadCate', $source, '上传自动分组必须不存在则创建');
        $this->assertContains('FileCate::create', $source, '上传自动分组必须写入文件分类表');
        $this->assertContains("mb_substr(\$name, 0, 32, 'UTF-8')", $source, '自动分组名称必须限制在表字段长度内');
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

    private function testValidatorScopeRule(): void
    {
        $source = $this->read('app/adminapi/validate/FileValidate.php');

        $this->assertContains("'scope' => 'in:mine,public,all'", $source, '素材空间范围参数必须校验合法值');
        $this->assertContains("'scope.in' => '素材范围参数不合法'", $source, '素材空间范围参数必须有错误提示');
        $this->assertContains("return \$this->only(['type', 'pid', 'name', 'scope']);", $source, '新增分组场景必须包含 scope 可选校验');
    }

    private function testSqlAndMigrationContract(): void
    {
        $sql = $this->read('public/install/db/like.sql');
        $migration = $this->read('database/migrations/20261123_material_scope_public.php');

        $this->assertContains("`is_public` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否公共分组[0=个人分组,1=公共分组]'", $sql, '安装基线必须包含 is_public 列');
        $this->assertContains("`admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '归属后台账号ID(0=公共)'", $sql, '安装基线必须包含 admin_id 列');
        $this->assertContains('KEY `idx_type_public_admin` (`type`,`is_public`,`admin_id`) USING BTREE', $sql, '安装基线必须包含公共归属联合索引');

        $this->assertContains('in_array(\'--apply\', $argv, true)', $migration, '迁移脚本必须默认 dry-run，显式 --apply 才落库');
        $this->assertContains('[只读预览]', $migration, '迁移脚本必须提示只读预览');
        $this->assertContains('idx_type_public_admin', $migration, '迁移脚本必须补齐公共归属联合索引');
        $this->assertContains('`{$prefix}staff`', $migration, '存量回填必须按服务人员名称匹配归属账号');
        $this->assertContains('`{$prefix}admin`', $migration, '存量回填必须按后台账号名称匹配归属账号');
        $this->assertContains('is_public = 0 AND admin_id = 0', $migration, '存量回填必须只处理未归属的默认分组');
        $this->assertContains('is_public=0,admin_id=', $migration, '存量回填输出必须区分个人分组');
        $this->assertContains('is_public=1,admin_id=0', $migration, '存量回填输出必须区分公共分组');
    }

    private function read(string $relativePath): string
    {
        $path = $this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('无法读取文件：' . $relativePath);
        }
        return str_replace(["\r\n", "\r"], "\n", $contents);
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
