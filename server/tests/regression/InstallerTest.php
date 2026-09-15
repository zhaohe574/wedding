<?php
declare(strict_types=1);

define('INSTALL_ROOT', dirname(__DIR__, 2) . '/public/install');
require INSTALL_ROOT . '/model.php';
require INSTALL_ROOT . '/YxEnv.php';
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/topthink/framework/src/helper.php';

$port = (int)getenv('WEDDING_TEST_MYSQL_PORT');
if ($port <= 0 || $port === 3306) {
    throw new RuntimeException('必须指定独立本机数据库端口 WEDDING_TEST_MYSQL_PORT，禁止使用 3306');
}
$password = (string)getenv('WEDDING_TEST_MYSQL_PASSWORD');
$pdo = new PDO('mysql:host=127.0.0.1;port=' . $port . ';charset=utf8mb4', 'root', $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$database = 'wedding_install_' . getmypid() . '_' . time();
$envPath = tempnam(sys_get_temp_dir(), 'wedding-env-');
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void {
    $checks++;
    if (!$ok) { throw new RuntimeException($message); }
};
try {
    $params = ['host' => '127.0.0.1', 'port' => $port, 'user' => 'root', 'password' => $password,
        'name' => $database, 'prefix' => 'qa_', 'admin_user' => "测试'管理员", 'admin_password' => 'Test!Install$2026'];
    $installer = new installModel();
    $result = $installer->checkConfig($database, $params);
    $check($result->result === 'ok', '安装失败：' . ($result->error ?? '未知错误'));
    $pdo->exec('USE `' . $database . '`');
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $check(count($tables) === substr_count(file_get_contents(INSTALL_ROOT . '/db/like.sql'), 'CREATE TABLE'), '安装表数量不匹配');
    $check(count(array_filter($tables, static fn ($table) => !str_starts_with($table, 'qa_'))) === 0, '自定义前缀未生效');
    $admin = $pdo->query('SELECT account, password FROM qa_admin WHERE id=1')->fetch(PDO::FETCH_ASSOC);
    $check($admin['account'] === $params['admin_user'], '特殊字符管理员账号未正确保存');
    $check(strlen($uniqueSalt) === 32 && $admin['password'] === $installer->createPassword($params['admin_password'], $uniqueSalt), '随机密码盐与管理员密码不匹配');
    $check($installer->checkConfig($database, $params)->result === 'fail', '安装器必须拒绝覆盖非空数据库');
    $check((int)$pdo->query('SELECT COUNT(*) FROM qa_payment')->fetchColumn() === 0, '安装不得创建历史支付种子');
    $env = new YxEnv();
    $env->load(dirname(__DIR__, 2) . '/.example.env');
    foreach (['abc"def\\ghi$XYZ', '${PATH};#"\\', 'true', 'false', 'null', ''] as $value) {
        $env->putEnv($envPath, array_merge($params, ['password' => $value]));
        $read = parse_ini_file($envPath, true, INI_SCANNER_RAW);
        $check($read['DATABASE']['PASSWORD'] === $value, '配置密码转义回读不一致');
        $check($read['PROJECT']['UNIQUE_IDENTIFICATION'] === $uniqueSalt, '环境配置未保存本次安装密码盐');
        $app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
        $app->env->load($envPath);
        $config = require __DIR__ . '/../../config/database.php';
        $check($config['connections']['mysql']['password'] === $value, '框架数据库配置密码回读不一致');
    }
    try {
        $env->putEnv($envPath, array_merge($params, ['password' => "bad\nvalue"]));
        $check(false, '配置值必须拒绝换行注入');
    } catch (RuntimeException $e) {
        $check(str_contains($e->getMessage(), '换行'), '配置注入校验错误');
    }
    echo 'OK - 安装器与配置回读：' . $checks . " 项\n";
} finally {
    $pdo->exec('DROP DATABASE IF EXISTS `' . $database . '`');
    if (is_file($envPath)) { unlink($envPath); }
}
