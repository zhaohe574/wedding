<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 快捷工具代理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\ops;

use app\adminapi\controller\BaseAdminController;
use think\facade\Cache;

class QuickToolController extends BaseAdminController
{
    private const MAX_HTML_BYTES = 1024 * 1024 * 2;
    private const TICKET_TTL = 120;
    private const TICKET_CACHE_PREFIX = 'quick_tool_ticket_';
    private const API_TICKET_TTL = 900;
    private const API_TOKEN_CACHE_PREFIX = 'quick_tool_api_token_';
    private const REDPANDA_ASSET_CACHE_DIR = 'redpanda-assets';
    private const REDPANDA_ASSET_CACHE_TTL = 604800;

    public array $notNeedLogin = ['page', 'api', 'redpanda', 'redpandaAsset'];

    private const TOOLS = [
        'human_split' => 'https://tool.browser.qq.com/img_human_split.html',
        'compress' => 'https://tool.browser.qq.com/tupianyasuo.html',
        'convert' => 'https://tool.browser.qq.com/imgconvert.html',
        'image_edit' => 'https://tool.browser.qq.com/img_edit_canvas.html',
    ];

    private const API_PATHS = [
        '/api/addToolPV',
        '/api/consumeCount',
        '/api/get_tool_list',
        '/api/getcoscredential',
        '/api/getEquityCardInfo',
        '/api/getEquityConsumeCount',
        '/api/getEquityInfo',
        '/api/getEquitySceneText',
        '/api/getLoginInfo',
        '/api/getToken',
        '/api/getUserInfor',
        '/api/imgcompress',
        '/api/logout',
        '/api/parseFile',
        '/api/pdfconvert/createPdfTask',
        '/api/pdfconvert/queryConsumeCount',
        '/api/pdfconvert/queryPdfTask',
        '/api/pdfconvert/upload2cos',
    ];

    public function ticket()
    {
        $tool = (string)$this->request->get('tool', 'human_split');
        if (!isset(self::TOOLS[$tool])) {
            return $this->fail('工具参数无效');
        }

        $ticket = self::createTicket();
        Cache::set(self::TICKET_CACHE_PREFIX . $ticket, [
            'admin_id' => (int)$this->adminId,
            'tool' => $tool,
            'api_token' => self::createTicket(),
        ], self::TICKET_TTL);

        return $this->success('获取成功', [
            'ticket' => $ticket,
            'expire_in' => self::TICKET_TTL,
        ]);
    }

    public function page()
    {
        $tool = (string)$this->request->get('tool', 'human_split');
        if (!isset(self::TOOLS[$tool])) {
            return $this->htmlError('工具参数无效');
        }
        $payload = $this->consumePageTicket($tool);
        if (empty($payload)) {
            return $this->htmlError('工具访问凭证已失效，请刷新后重试');
        }

        try {
            $html = self::fetchRemoteHtml(self::TOOLS[$tool]);
            if ($html === '') {
                return $this->htmlError('工具加载失败，请稍后重试');
            }

            return response(self::rewriteHtml($html, (string)$payload['api_token']))->header([
                'Content-Type' => 'text/html; charset=utf-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Referrer-Policy' => 'no-referrer',
                'X-Frame-Options' => 'SAMEORIGIN',
            ]);
        } catch (\Throwable $e) {
            return $this->htmlError('工具加载失败，请稍后重试');
        }
    }

    public function api()
    {
        $apiToken = (string)$this->request->get('api_token', '');
        if (!preg_match('/^[a-f0-9]{32,64}$/i', $apiToken) || !Cache::get(self::API_TOKEN_CACHE_PREFIX . $apiToken)) {
            return response('工具访问凭证已失效')->code(403)->header([
                'Content-Type' => 'text/plain; charset=utf-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'X-Frame-Options' => 'SAMEORIGIN',
            ]);
        }

        $path = '/' . ltrim((string)$this->request->get('path', ''), '/');
        if (!in_array($path, self::API_PATHS, true)) {
            return response('工具接口不允许访问')->code(403)->header([
                'Content-Type' => 'text/plain; charset=utf-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'X-Frame-Options' => 'SAMEORIGIN',
            ]);
        }

        return self::proxyToolApi($path);
    }

    public function redpanda()
    {
        return self::redpandaPageResponse();
    }

    public static function redpandaPageResponse()
    {
        $url = 'https://www.redpandacompress.com/zh/';

        try {
            $html = self::fetchRedpandaHtml($url);
            if ($html === '') {
                return self::htmlErrorResponse('视频压缩工具加载失败，请稍后重试');
            }

            return response(self::rewriteRedpandaHtml($html))->header([
                'Content-Type' => 'text/html; charset=utf-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Referrer-Policy' => 'no-referrer',
                'X-Frame-Options' => 'SAMEORIGIN',
            ]);
        } catch (\Throwable $e) {
            return self::htmlErrorResponse('视频压缩工具加载失败，请稍后重试');
        }
    }

    public function redpandaAsset()
    {
        $path = '/' . ltrim((string)$this->request->get('path', ''), '/');
        return self::redpandaAssetResponse($path);
    }

    public static function redpandaAssetResponse(string $path)
    {
        $path = '/' . ltrim($path, '/');
        if (!self::isAllowedRedpandaAssetPath($path)) {
            return response('资源不允许访问')->code(403)->header([
                'Content-Type' => 'text/plain; charset=utf-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'X-Frame-Options' => 'SAMEORIGIN',
            ]);
        }

        return self::proxyRedpandaAsset($path);
    }

    private function htmlError(string $message)
    {
        return self::htmlErrorResponse($message);
    }

    private static function htmlErrorResponse(string $message)
    {
        $safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return response('<!doctype html><html><head><meta charset="utf-8"><style>body{margin:0;font:14px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#606266;background:#f5f7fa}.state{display:flex;min-height:360px;align-items:center;justify-content:center}.box{padding:24px 28px;border:1px solid #ebeef5;border-radius:8px;background:#fff;color:#909399}</style></head><body><div class="state"><div class="box">' . $safeMessage . '</div></div></body></html>')->header([
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Referrer-Policy' => 'no-referrer',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    private static function createTicket(): string
    {
        try {
            return bin2hex(random_bytes(24));
        } catch (\Throwable $e) {
            return md5(uniqid('quick_tool_', true) . microtime(true));
        }
    }

    private function consumePageTicket(string $tool): array
    {
        $ticket = (string)$this->request->get('ticket', '');
        if (!preg_match('/^[a-f0-9]{32,64}$/i', $ticket)) {
            return [];
        }

        $cacheKey = self::TICKET_CACHE_PREFIX . $ticket;
        $payload = Cache::get($cacheKey);
        if (!is_array($payload) || ($payload['tool'] ?? '') !== $tool) {
            return [];
        }

        Cache::delete($cacheKey);
        Cache::set(self::API_TOKEN_CACHE_PREFIX . (string)$payload['api_token'], [
            'admin_id' => (int)($payload['admin_id'] ?? 0),
            'tool' => $tool,
        ], self::API_TICKET_TTL);

        return $payload;
    }

    private static function fetchRemoteHtml(string $url): string
    {
        if (!extension_loaded('curl') || !function_exists('curl_init') || !self::isAllowedUrl($url)) {
            return '';
        }

        return self::fetchRemoteHtmlWithRedirects($url, 0);
    }

    private static function fetchRedpandaHtml(string $url): string
    {
        if (!extension_loaded('curl') || !function_exists('curl_init') || !self::isAllowedRedpandaUrl($url)) {
            return '';
        }

        $buffer = '';
        $curl = curl_init($url);
        if ($curl === false) {
            return '';
        }

        curl_setopt_array($curl, [
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'GelinsheRedpandaFrame/1.0',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$buffer): int {
                $buffer .= $chunk;
                if (strlen($buffer) > self::MAX_HTML_BYTES) {
                    return 0;
                }
                return strlen($chunk);
            },
        ]);
        if (defined('CURLOPT_PROTOCOLS')) {
            curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        }
        self::applyRedpandaCurlCaFile($curl);

        $result = curl_exec($curl);
        $httpCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $contentType = (string)curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        curl_close($curl);

        if ($result === false || $httpCode < 200 || $httpCode >= 300 || $buffer === '' || strlen($buffer) > self::MAX_HTML_BYTES) {
            return '';
        }

        if ($contentType !== '' && stripos($contentType, 'text/html') === false) {
            return '';
        }

        return $buffer;
    }

    private static function fetchRemoteHtmlWithRedirects(string $url, int $redirectCount): string
    {
        if ($redirectCount > 3 || !self::isAllowedUrl($url)) {
            return '';
        }

        $buffer = '';
        $location = '';
        $curl = curl_init($url);
        if ($curl === false) {
            return '';
        }

        curl_setopt_array($curl, [
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'GelinsheQuickToolProxy/1.0',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
            CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use (&$location): int {
                if (stripos($headerLine, 'Location:') === 0) {
                    $location = trim(substr($headerLine, 9));
                }
                return strlen($headerLine);
            },
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$buffer): int {
                $buffer .= $chunk;
                if (strlen($buffer) > self::MAX_HTML_BYTES) {
                    return 0;
                }
                return strlen($chunk);
            },
        ]);
        if (defined('CURLOPT_PROTOCOLS')) {
            curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        }
        if (defined('CURLOPT_REDIR_PROTOCOLS')) {
            curl_setopt($curl, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS);
        }

        $result = curl_exec($curl);
        $httpCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $contentType = (string)curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        curl_close($curl);

        if ($httpCode >= 300 && $httpCode < 400 && $location !== '') {
            $redirectUrl = self::buildRedirectUrl($url, $location);
            return $redirectUrl !== ''
                ? self::fetchRemoteHtmlWithRedirects($redirectUrl, $redirectCount + 1)
                : '';
        }

        if ($result === false || $httpCode < 200 || $httpCode >= 300 || $buffer === '' || strlen($buffer) > self::MAX_HTML_BYTES) {
            return '';
        }

        if ($contentType !== '' && stripos($contentType, 'text/html') === false) {
            return '';
        }

        return $buffer;
    }

    private static function rewriteHtml(string $html, string $apiToken): string
    {
        $html = self::rewriteRelativeUrls($html);
        $style = '<base href="https://tool.browser.qq.com/"><meta name="referrer" content="no-referrer">' . self::injectedStyle();
        $script = self::injectedScript($apiToken);

        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $style . '</head>', $html, 1) . $script;
        }

        return $style . $html . $script;
    }

    private static function rewriteRedpandaHtml(string $html): string
    {
        $html = self::rewriteRedpandaUrls($html);
        $html = self::removeRedpandaThirdPartyScripts($html);
        $style = '<meta name="referrer" content="no-referrer">' . self::redpandaInjectedStyle();
        $script = self::redpandaInjectedScript();

        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $style . '</head>', $html, 1) . $script;
        }

        return $style . $html . $script;
    }

    private static function rewriteRedpandaUrls(string $html): string
    {
        $assetBase = self::redpandaAssetProxyBase();
        $html = preg_replace_callback('/\s(href|src)=([\'"])(\/(?!\/)[^\'"]*)\2/i', static function (array $matches) use ($assetBase) {
            $path = $matches[3];
            if (!self::isAllowedRedpandaAssetPath($path)) {
                return $matches[0];
            }
            return ' ' . $matches[1] . '=' . $matches[2] . $assetBase . rawurlencode($path) . $matches[2];
        }, $html);

        return (string)preg_replace_callback('/\s(href|src)=([\'"])(https:\/\/www\.redpandacompress\.com\/[^\'"]*)\2/i', static function (array $matches) use ($assetBase) {
            $path = (string)(parse_url($matches[3], PHP_URL_PATH) ?: '');
            if (!self::isAllowedRedpandaAssetPath($path)) {
                return $matches[0];
            }
            return ' ' . $matches[1] . '=' . $matches[2] . $assetBase . rawurlencode($path) . $matches[2];
        }, $html);
    }

    private static function removeRedpandaThirdPartyScripts(string $html): string
    {
        $patterns = [
            '/<script\b[^>]+src=["\']https:\/\/pagead2\.googlesyndication\.com\/[^"\']*["\'][^>]*>\s*<\/script>/i',
            '/<script\b[^>]+src=["\']https:\/\/www\.googletagmanager\.com\/[^"\']*["\'][^>]*>\s*<\/script>/i',
            '/<script>\s*if\s*\(\s*navigator\.userAgent\s*!==\s*["\']ReactSnap["\']\s*\)\s*\{.*?googletagmanager\.com\/gtag\/js.*?\}\s*<\/script>/is',
        ];

        return (string)preg_replace($patterns, '', $html);
    }

    private static function proxyRedpandaAsset(string $path)
    {
        if (!extension_loaded('curl') || !function_exists('curl_init')) {
            return response('curl 扩展不可用')->code(500)->header(['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $cachePath = self::redpandaAssetCachePath($path);
        if ($cachePath !== '' && is_file($cachePath) && self::isFreshRedpandaAssetCache($cachePath)) {
            $buffer = (string)file_get_contents($cachePath);
            if ($buffer !== '') {
                $contentType = self::redpandaAssetContentType($path);
                return response($buffer)->code(200)->header(self::redpandaAssetResponseHeaders($contentType, $path, true));
            }
        }

        $remote = self::fetchRedpandaAssetFromRemote($path);
        $buffer = (string)$remote['buffer'];
        $httpCode = (int)$remote['http_code'];
        $responseContentType = (string)$remote['content_type'];

        if (!$remote['ok']) {
            if ($cachePath !== '' && is_file($cachePath)) {
                $buffer = (string)file_get_contents($cachePath);
                if ($buffer !== '') {
                    $contentType = self::redpandaAssetContentType($path);
                    return response($buffer)->code(200)->header(self::redpandaAssetResponseHeaders($contentType, $path, true, true));
                }
            }

            return response('资源请求失败')->code(502)->header(['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (self::shouldRewriteRedpandaAssetContent($responseContentType, $path)) {
            $buffer = self::rewriteRedpandaAssetContent($buffer);
        }

        $contentType = self::redpandaAssetContentType($path, $responseContentType);
        if ($httpCode >= 200 && $httpCode < 300 && $buffer !== '' && self::isCacheableRedpandaAsset($path)) {
            self::writeRedpandaAssetCache($cachePath, $buffer);
        }

        return response($buffer)->code($httpCode)->header(self::redpandaAssetResponseHeaders($contentType, $path, false));
    }

    private static function fetchRedpandaAssetFromRemote(string $path): array
    {
        $url = 'https://www.redpandacompress.com' . $path;
        $last = [
            'ok' => false,
            'http_code' => 0,
            'content_type' => '',
            'buffer' => '',
        ];

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $buffer = '';
            $responseContentType = '';
            $curl = curl_init($url);
            if ($curl === false) {
                continue;
            }

            curl_setopt_array($curl, [
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'GelinsheRedpandaFrame/1.0',
                CURLOPT_HTTPHEADER => [
                    'Accept: */*',
                    'Referer: https://www.redpandacompress.com/zh/',
                ],
                CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use (&$responseContentType): int {
                    if (stripos($headerLine, 'Content-Type:') === 0) {
                        $responseContentType = trim(substr($headerLine, 13));
                    }
                    return strlen($headerLine);
                },
                CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$buffer): int {
                    $buffer .= $chunk;
                    return strlen($chunk);
                },
            ]);
            if (defined('CURLOPT_PROTOCOLS')) {
                curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
            }
            self::applyRedpandaCurlCaFile($curl);

            $result = curl_exec($curl);
            $httpCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            curl_close($curl);

            $last = [
                'ok' => $result !== false && $httpCode >= 200 && $httpCode < 300 && $buffer !== '',
                'http_code' => $httpCode,
                'content_type' => $responseContentType,
                'buffer' => $buffer,
            ];
            if ($last['ok']) {
                return $last;
            }

            usleep(200000);
        }

        return $last;
    }

    private static function redpandaAssetResponseHeaders(string $contentType, string $path, bool $cacheHit, bool $stale = false): array
    {
        $headers = [
            'Content-Type' => $contentType,
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-RedPanda-Cache' => $stale ? 'STALE' : ($cacheHit ? 'HIT' : 'MISS'),
        ];

        if (self::isCacheableRedpandaAsset($path)) {
            $headers['Cache-Control'] = 'public, max-age=' . self::REDPANDA_ASSET_CACHE_TTL . ', immutable';
        } else {
            $headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0';
        }

        return $headers;
    }

    private static function redpandaAssetContentType(string $path, string $remoteContentType = ''): string
    {
        $path = strtolower($path);
        if (str_ends_with($path, '.js')) {
            return 'text/javascript; charset=utf-8';
        }
        if (str_ends_with($path, '.css')) {
            return 'text/css; charset=utf-8';
        }
        if (str_ends_with($path, '.wasm')) {
            return 'application/wasm';
        }
        if (str_ends_with($path, '.svg')) {
            return 'image/svg+xml';
        }
        if (str_ends_with($path, '.png')) {
            return 'image/png';
        }
        if (str_ends_with($path, '.jpg') || str_ends_with($path, '.jpeg')) {
            return 'image/jpeg';
        }
        if (str_ends_with($path, '.webp')) {
            return 'image/webp';
        }
        if (str_ends_with($path, '.ico')) {
            return 'image/x-icon';
        }
        if (str_ends_with($path, '.json') || str_ends_with($path, '.webmanifest')) {
            return 'application/json; charset=utf-8';
        }

        return $remoteContentType !== '' ? $remoteContentType : 'application/octet-stream';
    }

    private static function isCacheableRedpandaAsset(string $path): bool
    {
        return preg_match('/\.(?:js|css|wasm|svg|png|jpg|jpeg|webp|ico|json|webmanifest)$/i', $path) === 1;
    }

    private static function redpandaAssetCachePath(string $path): string
    {
        if (!self::isCacheableRedpandaAsset($path)) {
            return '';
        }

        $dir = runtime_path() . self::REDPANDA_ASSET_CACHE_DIR . DIRECTORY_SEPARATOR;
        return $dir . sha1($path) . self::redpandaAssetCacheExtension($path);
    }

    private static function redpandaAssetCacheExtension(string $path): string
    {
        $extension = pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION);
        return $extension !== '' ? '.' . strtolower($extension) : '.cache';
    }

    private static function isFreshRedpandaAssetCache(string $cachePath): bool
    {
        $mtime = filemtime($cachePath);
        return $mtime !== false && $mtime + self::REDPANDA_ASSET_CACHE_TTL > time();
    }

    private static function writeRedpandaAssetCache(string $cachePath, string $buffer): void
    {
        if ($cachePath === '') {
            return;
        }

        $dir = dirname($cachePath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }

        $tmpPath = $cachePath . '.' . uniqid('tmp_', true);
        if (file_put_contents($tmpPath, $buffer, LOCK_EX) === false) {
            return;
        }

        @rename($tmpPath, $cachePath);
        if (is_file($tmpPath)) {
            @unlink($tmpPath);
        }
    }

    private static function shouldRewriteRedpandaAssetContent(string $contentType, string $path): bool
    {
        $path = strtolower($path);
        $contentType = strtolower($contentType);

        return str_ends_with($path, '.js')
            || str_ends_with($path, '.css')
            || str_contains($contentType, 'javascript')
            || str_contains($contentType, 'text/css');
    }

    private static function applyRedpandaCurlCaFile($curl): void
    {
        $caFile = root_path() . 'vendor' . DIRECTORY_SEPARATOR . 'rmccue' . DIRECTORY_SEPARATOR
            . 'requests' . DIRECTORY_SEPARATOR . 'certificates' . DIRECTORY_SEPARATOR . 'cacert.pem';
        if (is_file($caFile)) {
            curl_setopt($curl, CURLOPT_CAINFO, $caFile);
        }
    }

    private static function rewriteRedpandaAssetContent(string $content): string
    {
        $assetBase = self::redpandaAssetProxyBase();
        $content = str_replace(
            'C=function(e){return`/`+e}',
            'C=function(e){return e&&e[0]==="/" ? e : `/`+e}',
            $content
        );
        $content = (string)preg_replace(
            '/function J\(a\)\{return l\.locateFile\?l\.locateFile\(a,\s*S\):S\+a\}/',
            'function J(a){return a&&(/^(?:https?:)?\\/\\//.test(a)||a[0]==="/")?a:(l.locateFile?l.locateFile(a,S):S+a)}',
            $content,
            1
        );
        $content = str_replace(
            'q=document.getElementById(`root`);q.hasChildNodes()?(0,se.hydrateRoot)(q,(0,O.jsx)(Ke,{})):(0,se.createRoot)(q).render((0,O.jsx)(Ke,{})),E();',
            'q=document.getElementById(`root`);q&&(q.textContent="",(0,se.createRoot)(q).render((0,O.jsx)(Ke,{}))),E();',
            $content
        );

        $content = (string)preg_replace_callback(
            '/(?P<prefix>["\'`(])(?P<path>\/(?!\/)[A-Za-z0-9._~\-\/]+\.(?:svg|png|jpg|jpeg|webp|ico|json|css|js|wasm|txt|xml|webmanifest))(?:[?#][^"\'`)\s]*)?/i',
            static function (array $matches) use ($assetBase): string {
                $path = $matches['path'];
                if (!self::isAllowedRedpandaAssetPath($path)) {
                    return $matches[0];
                }

                return $matches['prefix'] . $assetBase . rawurlencode($path);
            },
            $content
        );

        $content = (string)preg_replace_callback(
            '/(?P<prefix>["\'`(])https:\/\/www\.redpandacompress\.com(?P<path>\/[A-Za-z0-9._~\-\/]+\.(?:svg|png|jpg|jpeg|webp|ico|json|css|js|wasm|txt|xml|webmanifest))(?:[?#][^"\'`)\s]*)?/i',
            static function (array $matches) use ($assetBase): string {
                $path = $matches['path'];
                if (!self::isAllowedRedpandaAssetPath($path)) {
                    return $matches[0];
                }

                return $matches['prefix'] . $assetBase . rawurlencode($path);
            },
            $content
        );

        $content = (string)preg_replace_callback(
            '/(?P<prefix>["\'`])\.\/(?P<file>[A-Za-z0-9._~\-]+\.(?:css|js|wasm))(?:[?#][^"\'`\s]*)?/i',
            static function (array $matches) use ($assetBase): string {
                $path = '/assets/' . $matches['file'];
                if (!self::isAllowedRedpandaAssetPath($path)) {
                    return $matches[0];
                }

                return $matches['prefix'] . $assetBase . rawurlencode($path);
            },
            $content
        );

        $content = (string)preg_replace_callback(
            '/(?P<prefix>["\'`(])(?P<path>assets\/[A-Za-z0-9._~\-\/]+\.(?:css|js|wasm))(?:[?#][^"\'`)\s]*)?/i',
            static function (array $matches) use ($assetBase): string {
                $path = '/' . $matches['path'];
                if (!self::isAllowedRedpandaAssetPath($path)) {
                    return $matches[0];
                }

                return $matches['prefix'] . $assetBase . rawurlencode($path);
            },
            $content
        );

        return (string)preg_replace_callback(
            '/(?P<prefix>["\'`])(?P<file>[A-Za-z0-9._~\-]+\.(?:wasm))(?:[?#][^"\'`\s]*)?/i',
            static function (array $matches) use ($assetBase): string {
                $path = '/' . $matches['file'];
                if (!self::isAllowedRedpandaAssetPath($path)) {
                    return $matches[0];
                }

                return $matches['prefix'] . $assetBase . rawurlencode($path);
            },
            $content
        );
    }

    private static function redpandaAssetProxyBase(): string
    {
        return '/adminapi/ops.quickTool/redpandaAsset?_v=20260701_proxy3&path=';
    }

    private static function redpandaInjectedStyle(): string
    {
        return <<<'HTML'
<style id="gelinshe-redpanda-frame-style">
html,
body,
#root,
.ant-layout,
.container {
    min-width: 0 !important;
    width: 100% !important;
    margin: 0 !important;
    overflow-x: hidden !important;
    background: #f5f7fa !important;
}
header,
footer,
.feature-banner,
.feature-block,
.description-session,
.social-share,
.terms,
.gray,
.desktop-only,
.header-container,
.logo,
.logo_words,
.hamburger-btn {
    display: none !important;
}
.ant-layout-content {
    width: 100% !important;
    max-width: none !important;
    min-width: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    background: transparent !important;
}
body.gelinshe-redpanda-ready .content {
    width: min(90%, 1040px) !important;
    max-width: 1040px !important;
    min-height: 100vh !important;
    margin: 0 auto !important;
    padding: 20px 0 !important;
    background: #f5f5f5 !important;
}
body.gelinshe-redpanda-ready .content > *:not(.main-block) {
    display: none !important;
}
body.gelinshe-redpanda-ready .main-block {
    display: block !important;
    width: 100% !important;
    min-width: 0 !important;
    margin: 0 auto !important;
    padding: 0 !important;
    box-sizing: border-box !important;
}
</style>
HTML;
    }

    private static function redpandaInjectedScript(): string
    {
        return <<<'HTML'
<script>
(function () {
    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () {};
    window.adsbygoogle = window.adsbygoogle || { push: function () {} };
    function isolateMainBlock() {
        var mainBlock = document.querySelector('.main-block');
        if (!mainBlock) {
            return;
        }
        document.body.classList.add('gelinshe-redpanda-ready');
    }
    window.addEventListener('load', isolateMainBlock);
    setTimeout(isolateMainBlock, 100);
    setTimeout(isolateMainBlock, 600);
    setTimeout(isolateMainBlock, 1500);
})();
</script>
HTML;
    }

    private static function rewriteRelativeUrls(string $html): string
    {
        $base = 'https://tool.browser.qq.com';
        $html = preg_replace_callback('/\s(href|src)=([\'"])(\/(?!\/)[^\'"]*)\2/i', static function (array $matches) use ($base) {
            return ' ' . $matches[1] . '=' . $matches[2] . $base . $matches[3] . $matches[2];
        }, $html);

        return (string)preg_replace_callback('/\s(href|src)=([\'"])(\/\/[^\'"]*)\2/i', static function (array $matches) {
            return ' ' . $matches[1] . '=' . $matches[2] . 'https:' . $matches[3] . $matches[2];
        }, $html);
    }

    private static function injectedStyle(): string
    {
        return <<<'HTML'
<style id="gelinshe-quick-tool-style">
html,
body {
    min-width: 0 !important;
    width: 100% !important;
    margin: 0 !important;
    overflow-x: hidden !important;
    background: #f5f7fa !important;
}
.left-nav,
.left-nav-placeholder,
.top-content,
.nav-button-wrap,
.tool-usage-container,
.tool-useguide-modal,
.recommend-title,
.recommend-container,
.footer-placeholder-pc,
.footer-pc,
.share-btn {
    display: none !important;
}
.main-content {
    width: 100% !important;
    min-width: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
}
.main-content main,
.tool-container,
.tool-content-container,
.pc-content {
    width: 100% !important;
    max-width: none !important;
    min-width: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    background: transparent !important;
}
.tool-content-container {
    display: block !important;
}
#app {
    width: 100% !important;
    min-height: 620px !important;
    margin: 0 !important;
}
</style>
HTML;
    }

    private static function injectedScript(string $apiToken): string
    {
        $encodedApiToken = json_encode($apiToken, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return <<<HTML
<script>
(function () {
    var apiToken = {$encodedApiToken};
    function rewriteToolApiUrl(input) {
        if (typeof input !== 'string' || input.indexOf('/api/') !== 0) {
            return input;
        }
        return '/adminapi/ops.quickTool/api?api_token=' + encodeURIComponent(apiToken) + '&path=' + encodeURIComponent(input);
    }
    if (window.fetch) {
        var nativeFetch = window.fetch.bind(window);
        window.fetch = function (input, init) {
            if (typeof input === 'string') {
                input = rewriteToolApiUrl(input);
            } else if (input && input.url) {
                var rewrittenUrl = rewriteToolApiUrl(input.url);
                if (rewrittenUrl !== input.url) {
                    input = new Request(rewrittenUrl, input);
                }
            }
            return nativeFetch(input, init);
        };
    }
    if (window.XMLHttpRequest) {
        var nativeOpen = window.XMLHttpRequest.prototype.open;
        window.XMLHttpRequest.prototype.open = function (method, url) {
            arguments[1] = rewriteToolApiUrl(url);
            return nativeOpen.apply(this, arguments);
        };
    }
    function hideShell() {
        var selectors = [
            '.left-nav',
            '.left-nav-placeholder',
            '.top-content',
            '.nav-button-wrap',
            '.tool-usage-container',
            '.tool-useguide-modal',
            '.recommend-title',
            '.recommend-container',
            '.footer-placeholder-pc',
            '.footer-pc',
            '.share-btn'
        ];
        selectors.forEach(function (selector) {
            document.querySelectorAll(selector).forEach(function (node) {
                node.style.display = 'none';
            });
        });
        document.documentElement.style.minWidth = '0';
        document.body.style.minWidth = '0';
    }
    hideShell();
    window.addEventListener('load', hideShell);
    setTimeout(hideShell, 300);
    setTimeout(hideShell, 1200);
})();
</script>
HTML;
    }

    private static function proxyToolApi(string $path)
    {
        if (!extension_loaded('curl') || !function_exists('curl_init')) {
            return response('curl 扩展不可用')->code(500)->header(['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $query = $_GET ?? [];
        unset($query['api_token'], $query['path']);
        $url = 'https://tool.browser.qq.com' . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $body = file_get_contents('php://input');
        $method = strtoupper((string)request()->method());
        $contentType = (string)request()->header('content-type', '');

        $buffer = '';
        $responseContentType = '';
        $curl = curl_init($url);
        if ($curl === false) {
            return response('工具接口请求失败')->code(500)->header(['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $headers = [
            'Accept: */*',
            'Origin: https://tool.browser.qq.com',
            'Referer: https://tool.browser.qq.com/',
        ];
        if ($contentType !== '') {
            $headers[] = 'Content-Type: ' . $contentType;
        }

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => in_array($method, ['GET', 'POST'], true) ? $method : 'GET',
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_USERAGENT => (string)request()->header('user-agent', 'GelinsheQuickToolProxy/1.0'),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use (&$responseContentType): int {
                if (stripos($headerLine, 'Content-Type:') === 0) {
                    $responseContentType = trim(substr($headerLine, 13));
                }
                return strlen($headerLine);
            },
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$buffer): int {
                $buffer .= $chunk;
                return strlen($chunk);
            },
        ]);
        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body === false ? '' : $body);
        }
        if (defined('CURLOPT_PROTOCOLS')) {
            curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        }
        self::applyRedpandaCurlCaFile($curl);

        $result = curl_exec($curl);
        $httpCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if ($result === false || $httpCode < 200 || $httpCode >= 500) {
            return response('工具接口请求失败')->code(502)->header(['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return response($buffer)->code($httpCode)->header([
            'Content-Type' => $responseContentType !== '' ? $responseContentType : 'application/octet-stream',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    private static function isAllowedUrl(string $url): bool
    {
        $parts = parse_url($url);
        return is_array($parts)
            && strtolower((string)($parts['scheme'] ?? '')) === 'https'
            && strtolower((string)($parts['host'] ?? '')) === 'tool.browser.qq.com';
    }

    private static function isAllowedRedpandaUrl(string $url): bool
    {
        $parts = parse_url($url);
        return is_array($parts)
            && strtolower((string)($parts['scheme'] ?? '')) === 'https'
            && strtolower((string)($parts['host'] ?? '')) === 'www.redpandacompress.com'
            && (string)($parts['path'] ?? '/') === '/zh/';
    }

    private static function isAllowedRedpandaAssetPath(string $path): bool
    {
        $path = '/' . ltrim($path, '/');
        if (str_contains($path, '..')) {
            return false;
        }

        if (preg_match('/^\/assets\/[A-Za-z0-9._~\-\/]+$/', $path) === 1) {
            return true;
        }

        return preg_match('/^\/[A-Za-z0-9._~\-]+\\.(?:svg|png|jpg|jpeg|webp|ico|json|css|js|wasm|txt|xml|webmanifest)$/i', $path) === 1;
    }

    private static function buildRedirectUrl(string $baseUrl, string $location): string
    {
        $location = trim($location);
        if ($location === '') {
            return '';
        }
        if (preg_match('/^https:\/\//i', $location) === 1) {
            return self::isAllowedUrl($location) ? $location : '';
        }
        if (str_starts_with($location, '//')) {
            $url = 'https:' . $location;
            return self::isAllowedUrl($url) ? $url : '';
        }
        if (str_starts_with($location, '/')) {
            $parts = parse_url($baseUrl);
            $url = 'https://' . ($parts['host'] ?? 'tool.browser.qq.com') . $location;
            return self::isAllowedUrl($url) ? $url : '';
        }

        $url = rtrim(dirname($baseUrl), '/') . '/' . ltrim($location, '/');
        return self::isAllowedUrl($url) ? $url : '';
    }
}
