<?php
/* SEDOTPRESS ENGINE
 * Version : 0.2.0
 * Source  : https://github.com/sukualam/sedotpress
 * News    : http://sedot.space
 * License : MIT
 * Requires: PHP 8.0+
 */

if (session_status() === PHP_SESSION_NONE) {
	session_start([
		'cookie_httponly' => true,
		'cookie_samesite' => 'Lax',
		'use_strict_mode' => true,
	]);
}

define('SEDOT_VER', 'v0.2.0');
define('ROOT_DIR', __DIR__);
define('CONFIG_FILE', ROOT_DIR . '/config.json');

function sp_default_config(): array
{
	return [
		'site_title'           => 'Sedotpress',
		'site_desc'            => 'my sedotpress blog',
		'site_url'             => '',
		'timezone'             => 'Asia/Jakarta',
		'language'             => 'id_ID',
		'theme'                => 'sedot_bootstrap',
		'posts_per_page'       => 5,
		'gravatar_email'       => 'example@example.com',
		'admin_user'           => 'admin',
		'admin_pass'           => 'root',
		'comment_auto_approve' => false,
		'debug'                => false,
	];
}

function sp_save_config(array $config): bool
{
	$clean = array_intersect_key($config, sp_default_config());
	$json  = json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	if ($json === false) {
		return false;
	}
	return @file_put_contents(CONFIG_FILE, $json . "\n", LOCK_EX) !== false;
}

function sp_load_config(): array
{
	$defaults = sp_default_config();
	if (!is_file(CONFIG_FILE)) {
		sp_save_config($defaults);
		return $defaults;
	}
	$raw  = (string)@file_get_contents(CONFIG_FILE);
	$data = json_decode($raw, true);
	if (!is_array($data)) {
		return $defaults;
	}
	return array_replace($defaults, array_intersect_key($data, $defaults));
}

$CONFIG = sp_load_config();

function sp_config(string $key, $default = null)
{
	global $CONFIG;
	return isset($CONFIG[$key]) ? $CONFIG[$key] : $default;
}

if (!empty($CONFIG['debug'])) {
	error_reporting(E_ALL);
	ini_set('display_errors', '1');
	ini_set('log_errors', '1');
} else {
	error_reporting(E_ALL);
	ini_set('display_errors', '0');
	ini_set('log_errors', '1');
}

define('SITE_TITLE', (string)sp_config('site_title', 'Sedotpress'));
define('SITE_DESC', (string)sp_config('site_desc', 'my sedotpress blog'));
define('GRAV_EMAIL', (string)sp_config('gravatar_email', 'example@example.com'));
define('ADMIN_NICKNM', (string)sp_config('admin_user', 'admin'));
define('ADMIN_PASSWD', (string)sp_config('admin_pass', 'root'));
define('POSTS_PER_PAGE', max(1, (int)sp_config('posts_per_page', 5)));
define('RESERVED_SLUGS', [
	'page', 'tag', 'search', 'timeline', 'backstage', 'rss', 'rss.xml',
	'sitemap.xml', 'robots.txt', 'feed', 'comment', 'index.php',
]);

$spTheme = preg_replace('/[^a-z0-9_\-]/i', '', (string)sp_config('theme', 'sedot_bootstrap'));
if ($spTheme === '' || !is_dir(ROOT_DIR . '/sp_theme/' . $spTheme)) {
	$spTheme = 'sedot_bootstrap';
}
define('THEME', $spTheme);

$spTz = (string)sp_config('timezone', 'Asia/Jakarta');
if (!in_array($spTz, DateTimeZone::listIdentifiers(), true)) {
	$spTz = 'Asia/Jakarta';
}
date_default_timezone_set($spTz);

$spLang = (string)sp_config('language', 'id_ID');
@setlocale(LC_ALL, $spLang, $spLang . '.utf8', $spLang . '.UTF-8', $spLang . '.UTF8', 'id_ID', 'en_US');

function sp_detect_site_url(): string
{
	$https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
		|| (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
	$scheme = $https ? 'https' : 'http';
	$host   = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
	if (!preg_match('/^[a-z0-9\.\-:\[\]]+$/i', $host)) {
		$host = 'localhost';
	}
	$script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/'));
	$dir    = str_replace('\\', '/', dirname($script));
	if ($dir === '/' || $dir === '.' || $dir === '\\') {
		$dir = '';
	}
	return $scheme . '://' . $host . $dir;
}

$spSiteUrl = trim((string)sp_config('site_url', ''));
define('SITE_URL', $spSiteUrl !== '' ? rtrim($spSiteUrl, '/') : sp_detect_site_url());

function sp_path(string $rel): string
{
	$rel = str_replace('\\', '/', $rel);
	return ROOT_DIR . '/' . ltrim($rel, '/');
}

function sp_substr($string, $start, $length = null): string
{
	return function_exists('mb_substr') ? mb_substr((string)$string, $start, $length) : substr((string)$string, $start, $length);
}

function sp_strlen($string): int
{
	return function_exists('mb_strlen') ? mb_strlen((string)$string) : strlen((string)$string);
}

function sp_strpos($haystack, $needle)
{
	return function_exists('mb_strpos') ? mb_strpos((string)$haystack, (string)$needle) : strpos((string)$haystack, (string)$needle);
}

function sp_strtolower($string): string
{
	return function_exists('mb_strtolower') ? mb_strtolower((string)$string) : strtolower((string)$string);
}

function sp_strrpos($haystack, $needle)
{
	return function_exists('mb_strrpos') ? mb_strrpos((string)$haystack, (string)$needle) : strrpos((string)$haystack, (string)$needle);
}

function sp_request_segments(): array
{
	$uri  = (string)($_SERVER['REQUEST_URI'] ?? '/');
	$path = parse_url($uri, PHP_URL_PATH);
	if (!is_string($path) || $path === '') {
		$path = '/';
	}
	$basePath = rtrim((string)parse_url(SITE_URL, PHP_URL_PATH), '/');
	if ($basePath !== '' && strpos($path, $basePath) === 0) {
		$path = substr($path, strlen($basePath));
	}
	$path = trim(rawurldecode($path), '/');
	if ($path === '') {
		return [];
	}
	return array_map('strtolower', explode('/', $path));
}

function sp_csrf_field(): string
{
	return sprintf(
		'<input type="hidden" name="sp_csrf" value="%s">',
		sedot::e(sedot::csrfToken())
	);
}

### Sedotpress Class ###
class sedot
{
	const MODE_DATE      = 0;
	const MODE_TITLE     = 1;
	const MODE_PERMALINK = 2;
	const MODE_TAG       = 3;

	protected static $navTotal = 0;
	protected static $navPage  = 1;
	public $postPart          = [];
	public $primaryHTML       = [];

	function __construct()
	{
		$this->ensureStorage();
	}

	protected static function ensureStorage(): void
	{
		$dirs = ['sp_post', 'sp_static', 'sp_static/index', 'sp_static/xml', 'sp_static/comment', 'sp_static/misc', 'sp_theme'];
		foreach ($dirs as $dir) {
			if (!is_dir(sp_path($dir))) {
				@mkdir(sp_path($dir), 0755, true);
			}
		}
		$seed = [
			'sp_static/index/post.json'      => '[]',
			'sp_static/index/draft.json'     => '[]',
			'sp_static/index/tag.json'       => '{}',
			'sp_static/index/archive.json'   => '{}',
			'sp_static/index/totalpost.json' => '0',
			'sp_static/index/filesize.json'  => '0',
			'sp_static/index/index-0.json'   => '[]',
			'sp_static/misc/blacklist.json'  => '[]',
		];
		$areas = self::themeConfig()['area'];
		if ($areas) {
			$seed['sp_static/misc/_widget-' . THEME . '.json'] = self::toJson(array_fill_keys($areas, []));
		}
		foreach ($seed as $rel => $content) {
			if (!is_file(sp_path($rel))) {
				@file_put_contents(sp_path($rel), $content);
			}
		}
	}

	public static function e($value): string
	{
		return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}

	public static function csrfToken(): string
	{
		if (empty($_SESSION['sp_csrf']) || !is_string($_SESSION['sp_csrf'])) {
			$_SESSION['sp_csrf'] = bin2hex(random_bytes(32));
		}
		return $_SESSION['sp_csrf'];
	}

	public static function checkCsrf($token): bool
	{
		return is_string($token) && $token !== ''
			&& !empty($_SESSION['sp_csrf'])
			&& is_string($_SESSION['sp_csrf'])
			&& hash_equals($_SESSION['sp_csrf'], $token);
	}

	public static function _url($x = '/'): string
	{
		return rtrim(SITE_URL, '/') . '/' . trim((string)$x, '/');
	}

	public static function navi($total, $page): void
	{
		self::$navTotal = max(0, (int)$total);
		self::$navPage  = max(1, (int)$page);
	}

	public static function navTotal(): int
	{
		return self::$navTotal;
	}

	public static function navPage(): int
	{
		return self::$navPage;
	}

	public static function pageCount($total): int
	{
		return max(1, (int)ceil((int)$total / POSTS_PER_PAGE));
	}

	public static function currentMethod($class): array
	{
		$all    = get_class_methods($class) ?: [];
		$parent = get_parent_class($class);
		if (!$parent) {
			return $all;
		}
		return array_values(array_diff($all, get_class_methods($parent) ?: []));
	}

	public static function widgetMethods(): array
	{
		if (!class_exists('widget')) {
			return [];
		}
		$methods = [];
		foreach (get_class_methods('widget') as $name) {
			if (strpos($name, '__') === 0) {
				continue;
			}
			$ref = new ReflectionMethod('widget', $name);
			if ($ref->getDeclaringClass()->getName() === 'widget' && $ref->isPublic()) {
				$methods[] = $name;
			}
		}
		return $methods;
	}

	public static function removeExistingFile($path): bool
	{
		return is_file($path) && @unlink($path);
	}

	public static function toJson($str): string
	{
		$json = json_encode(
			$str,
			JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
		);
		return $json === false ? 'null' : $json;
	}

	public function createWidget($widgetId): void
	{
		$this->primaryHTML[$widgetId] = [];
	}

	public function addWidget($widgetId, $str, $order = 'after'): void
	{
		if (!isset($this->primaryHTML[$widgetId]) || !is_array($this->primaryHTML[$widgetId])) {
			$this->primaryHTML[$widgetId] = [];
		}
		if ($order === 'after') {
			$this->primaryHTML[$widgetId][] = $str;
		} else {
			array_unshift($this->primaryHTML[$widgetId], $str);
		}
	}

	public function widgetSection($opt = '1'): void
	{
		$widgets = $this->primaryHTML[$opt] ?? [];
		if (!is_array($widgets)) {
			return;
		}
		foreach ($widgets as $widget) {
			echo $widget;
		}
	}

	public static function down($a, $x): array
	{
		$a = is_array($a) ? $a : [];
		if (count($a) - 1 > $x && isset($a[$x]) && isset($a[$x + 1])) {
			$b = array_slice($a, 0, $x, true);
			$b[] = $a[$x + 1];
			$b[] = $a[$x];
			$b += array_slice($a, $x + 2, count($a), true);
			return $b;
		}
		return $a;
	}

	public static function up($a, $x): array
	{
		$a = is_array($a) ? $a : [];
		if ($x > 0 && $x < count($a) && isset($a[$x]) && isset($a[$x - 1])) {
			$b = array_slice($a, 0, ($x - 1), true);
			$b[] = $a[$x];
			$b[] = $a[$x - 1];
			$b += array_slice($a, ($x + 1), count($a), true);
			return $b;
		}
		return $a;
	}

	public static function themeConfig(): array
	{
		$file = sp_path('sp_theme/' . THEME . '/config.ini');
		$conf = is_file($file) ? @parse_ini_file($file) : false;
		if (!is_array($conf) || empty($conf['area']) || !is_array($conf['area'])) {
			return ['area' => []];
		}
		return $conf;
	}

	public function enableWidget(): void
	{
		$widgetContainer = '<div class="widget widget-%1$s-%2$s col-12 mb-3">%3$s%4$s</div>';
		$widgetTitle     = '<h2 class="widget-title h5">%s</h2>';
		$templateWidget  = self::loadJson('misc', '_widget-' . THEME);
		$getConfig       = self::themeConfig();
		foreach ($getConfig['area'] as $widgetArea) {
			$this->createWidget($widgetArea);
			$items = $templateWidget[$widgetArea] ?? [];
			if (!is_array($items)) {
				continue;
			}
			foreach ($items as $widgetKey => $content) {
				if (!is_array($content)) {
					continue;
				}
				$title = (string)($content['title'] ?? '');
				$title = $title !== '' ? sprintf($widgetTitle, $title) : '';
				$type  = (string)($content['type'] ?? '');
				if ($type === 'html') {
					$code = (string)($content['code'] ?? '');
				} elseif (in_array($type, self::widgetMethods(), true)) {
					$widgetObj = new widget();
					$code      = (string)$widgetObj->$type();
				} else {
					$code = '';
				}
				$this->addWidget($widgetArea, sprintf($widgetContainer, $widgetArea, $widgetKey, $title, $code));
			}
		}
	}

	public static function toFile($path, $data, $truncate = false): bool
	{
		$dir = dirname($path);
		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}
		$flags = $truncate ? 0 : FILE_APPEND;
		return @file_put_contents($path, $data, $flags | LOCK_EX) !== false;
	}

	public static function loadFile($path, $line = false): string
	{
		if (!is_file($path) || !is_readable($path)) {
			return '';
		}
		if ($line) {
			$handle = @fopen($path, 'r');
			if (!$handle) {
				return '';
			}
			$data = fgets($handle);
			fclose($handle);
			return $data === false ? '' : $data;
		}
		$data = @file_get_contents($path);
		return $data === false ? '' : $data;
	}

	public static function loadPost($postnum): ?array
	{
		$id = self::sanitizePostId($postnum);
		if ($id === '') {
			return null;
		}
		$file = sp_path('sp_post/post' . $id);
		if (!is_file($file)) {
			return null;
		}
		$data = json_decode((string)@file_get_contents($file), true);
		if (!is_array($data)) {
			return null;
		}
		$data['id'] = $id;
		return $data;
	}

	public static function sanitizePostId($raw): string
	{
		$raw = (string)$raw;
		return preg_match('/^\d{1,12}$/', $raw) ? $raw : '';
	}

	public static function postTags($post): array
	{
		$tag = is_array($post) ? ($post['tag'] ?? '') : '';
		$list = is_array($tag) ? $tag : explode(',', (string)$tag);
		$list = array_map('trim', $list);
		$list = array_values(array_filter($list, static function ($t) {
			return (string)$t !== '';
		}));
		return $list ?: ['uncategorized'];
	}

	public static function formatPost($filename): ?array
	{
		$post = self::loadPost($filename);
		if ($post === null) {
			return null;
		}
		$post['tag'] = self::postTags($post);
		return $post;
	}

	public static function endKey($array, $increment = false, $trim = 'post'): int
	{
		if (!is_array($array) || !$array) {
			$key = 0;
		} else {
			end($array);
			$raw = (string)key($array);
			$raw = preg_replace('/^' . preg_quote((string)$trim, '/') . '/', '', $raw);
			$key = ($raw === '' || !is_numeric($raw)) ? 0 : (int)$raw;
		}
		if ($increment) {
			$key += 1;
		}
		return $key;
	}

	public static function assignPostId($prefix = null): string
	{
		$postList = self::loadJson('index', 'post');
		$first    = isset($postList[0]) && is_array($postList[0]) ? $postList[0] : [];
		$newKey   = self::endKey(array_reverse($first, true), true);
		if ($newKey < 1) {
			$newKey = 1;
		}
		if ($prefix === null) {
			$prefix = 'post';
		}
		while (is_file(sp_path(sprintf('sp_post/%s%s', $prefix, $newKey)))) {
			$newKey += 1;
		}
		return $prefix . $newKey;
	}

	public static function getImg($string)
	{
		preg_match('/src="([^"]*)"/', (string)$string, $matches);
		if (!isset($matches[1]) || $matches[1] === '') {
			return false;
		}
		return $matches[1];
	}

	public static function plainString($text): string
	{
		// Copyright (c) 2008, David R. Nadeau, NadeauSoftware.com.
		$text = preg_replace(
			array(
				'@<head[^>]*?>.*?</head>@siu',
				'@<style[^>]*?>.*?</style>@siu',
				'@<script[^>]*?.*?</script>@siu',
				'@<object[^>]*?.*?</object>@siu',
				'@<embed[^>]*?.*?</embed>@siu',
				'@<applet[^>]*?.*?</applet>@siu',
				'@<noframes[^>]*?.*?</noframes>@siu',
				'@<noscript[^>]*?.*?</noscript>@siu',
				'@<noembed[^>]*?.*?</noembed>@siu',
				'@<((br)|(hr))@iu',
				'@</?((address)|(blockquote)|(center)|(del))@iu',
				'@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
				'@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
				'@</?((table)|(th)|(td)|(caption))@iu',
				'@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
				'@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
				'@</?((frameset)|(frame)|(iframe))@iu',
			),
			array(
				' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
				"\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
				"\n\$0", "\n\$0",
			),
			(string)$text
		);
		return strip_tags((string)$text);
	}

	public static function getExcerpt($string, $length): string
	{
		$text = preg_replace('/\s+/u', ' ', trim(self::plainString((string)$string)));
		$text = (string)$text;
		if ($text === '') {
			return '';
		}
		if (sp_strlen($text) <= $length) {
			return $text;
		}
		$excerpt = sp_substr($text, 0, $length);
		$cut     = sp_strrpos($excerpt, ' ');
		if ($cut !== false && $cut > 0) {
			$excerpt = sp_substr($excerpt, 0, $cut);
		}
		return rtrim($excerpt, '.,;:!?') . '…';
	}

	public static function splitMeta($str): array
	{
		return [
			'date'      => $str[0] ?? '',
			'title'     => $str[1] ?? '',
			'permalink' => $str[2] ?? '',
			'tag'       => isset($str[3]) && is_array($str[3]) ? $str[3] : self::postTags(['tag' => $str[3] ?? '']),
		];
	}

	public static function create_index($itemPerIndex = 0): void
	{
		if ((int)$itemPerIndex < 1) {
			$itemPerIndex = POSTS_PER_PAGE;
		}
		$dir      = sp_path('sp_post');
		$scandir  = is_dir($dir) ? (array)scandir($dir) : [];
		$scandir  = array_values(array_filter($scandir, static function ($file) {
			return preg_match('/^post\d{1,12}$/', $file) === 1;
		}));
		natsort($scandir);
		$reverseOrder = array_reverse($scandir);

		$container      = [];
		$draftContainer = [];
		$tags           = [];
		$archiveDate    = [];
		$countAllPost   = 0;
		$countFileSize  = 0;
		$xmlItems       = [];
		$rssItems       = [];

		foreach ($reverseOrder as $file) {
			$id = substr($file, 4);
			$countFileSize += (int)@filesize($dir . '/' . $file);
			$dec = self::loadPost($id);
			if ($dec === null) {
				continue;
			}
			$splitTag  = self::postTags($dec);
			$meta      = [(string)($dec['date'] ?? ''), (string)($dec['title'] ?? ''), (string)($dec['permalink'] ?? ''), $splitTag];
			$timestamp = strtotime((string)($dec['date'] ?? ''));
			if (($dec['status'] ?? 'publish') === 'publish') {
				$container[$id] = $meta;
				$countAllPost   += 1;
				$loc            = self::_url((string)($dec['permalink'] ?? ''));
				$xmlItems[]     = sprintf(
					"\n\t<url><loc>%s</loc><lastmod>%s</lastmod></url>",
					self::xmlEscape($loc),
					$timestamp ? date('c', $timestamp) : ''
				);
				if (count($rssItems) < $itemPerIndex * 2) {
					$rssItems[] = sprintf(
						"\n\t<item>\n\t<title>%s</title>\n\t<link>%s</link>\n\t<guid>%s</guid>\n\t<pubDate>%s</pubDate>\n\t<description>%s</description>\n\t</item>",
						self::xmlEscape((string)($dec['title'] ?? '')),
						self::xmlEscape($loc),
						self::xmlEscape($loc),
						$timestamp ? date(DATE_RSS, $timestamp) : '',
						self::xmlEscape(sp_substr(self::getExcerpt((string)($dec['entry'] ?? ''), 300), 0, 300))
					);
				}
			} else {
				$draftContainer[$id] = $meta;
			}
			if ($timestamp) {
				$parts = explode(' ', date('Y m d', $timestamp));
				$archiveDate[$parts[0]][$parts[1]][$parts[2]] = ($archiveDate[$parts[0]][$parts[1]][$parts[2]] ?? 0) + 1;
			}
			foreach ($splitTag as $tag) {
				$tags[] = $tag;
			}
		}

		$chunk      = $container ? array_chunk($container, (int)$itemPerIndex, true) : [];
		$chunkDraft = $draftContainer ? array_chunk($draftContainer, (int)$itemPerIndex, true) : [];

		$sitemapContainer = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
			. '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">%1$s' . "\n\t"
			. '<url><loc>%2$s</loc></url>' . "\n" . '</urlset>';
		$sitemapItems = implode('', $xmlItems);
		$xml          = sprintf($sitemapContainer, $sitemapItems, self::xmlEscape(rtrim(SITE_URL, '/') . '/'));

		$rssContainer = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
			. '<rss version="2.0">' . "\n" . '<channel>' . "\n"
			. '<title>%1$s</title>' . "\n" . '<link>%2$s</link>' . "\n"
			. '<description>%3$s</description>' . "\n" . '<lastBuildDate>%4$s</lastBuildDate>%5$s' . "\n"
			. '</channel>' . "\n" . '</rss>';
		$rss = sprintf(
			$rssContainer,
			self::xmlEscape(SITE_TITLE),
			self::xmlEscape(rtrim(SITE_URL, '/')),
			self::xmlEscape(SITE_DESC),
			date(DATE_RSS),
			implode('', $rssItems)
		);

		$counttag = $tags ? array_count_values($tags) : [];
		$reverseYear = $archiveDate ? array_reverse($archiveDate, true) : [];

		foreach ((array)glob(sp_path('sp_static/index/index-*.json')) as $old) {
			@unlink($old);
		}
		foreach ($chunk as $chunkKey => $chunkValue) {
			self::toFile(sp_path('sp_static/index/index-' . $chunkKey . '.json'), self::toJson($chunkValue), true);
		}
		self::toFile(sp_path('sp_static/index/totalpost.json'), (string)$countAllPost, true);
		self::toFile(sp_path('sp_static/index/archive.json'), self::toJson($reverseYear), true);
		self::toFile(sp_path('sp_static/index/draft.json'), self::toJson($chunkDraft), true);
		self::toFile(sp_path('sp_static/index/post.json'), self::toJson($chunk), true);
		self::toFile(sp_path('sp_static/index/tag.json'), self::toJson($counttag), true);
		self::toFile(sp_path('sp_static/index/filesize.json'), (string)$countFileSize, true);
		self::toFile(sp_path('sp_static/xml/rss.xml'), $rss, true);
		self::toFile(sp_path('sp_static/xml/sitemap.xml'), $xml, true);
	}

	public static function xmlEscape($value): string
	{
		$value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string)$value);
		return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
	}

	public static function loadJson($type = null, $name = null): array
	{
		if ($type === null || $name === null) {
			return [];
		}
		$str  = self::loadFile(sp_path("sp_static/{$type}/{$name}.json"));
		$data = $str === '' ? null : json_decode($str, true);
		return is_array($data) ? $data : [];
	}

	public static function ipBlackList($specify = null): void
	{
		$ip = $specify === null ? (string)($_SERVER['REMOTE_ADDR'] ?? '') : (string)$specify;
		if ($ip === '') {
			return;
		}
		$blackList = self::loadJson('misc', 'blacklist');
		$blackList[] = $ip;
		$blackList = array_values(array_unique($blackList));
		self::toFile(sp_path('sp_static/misc/blacklist.json'), self::toJson($blackList), true);
	}

	public static function isBlacklisted($specify = null): bool
	{
		$ip = $specify === null ? (string)($_SERVER['REMOTE_ADDR'] ?? '') : (string)$specify;
		if ($ip === '') {
			return false;
		}
		return in_array($ip, self::loadJson('misc', 'blacklist'), true);
	}

	public static function totalPost(): int
	{
		return (int)self::loadFile(sp_path('sp_static/index/totalpost.json'));
	}

	public static function search($mode, $query): array
	{
		$index = self::loadJson('index', 'post');
		$query = trim((string)$query);
		if ($query === '') {
			return [];
		}
		$terms = preg_split('/[\s+]+/', $query) ?: [];
		$terms = array_values(array_filter($terms, static function ($t) {
			return (string)$t !== '';
		}));
		if (!$terms) {
			return [];
		}
		$found = [];
		foreach ($index as $chunk) {
			if (!is_array($chunk)) {
				continue;
			}
			foreach ($chunk as $key => $val) {
				if (!is_array($val)) {
					continue;
				}
				$match = false;
				switch ((int)$mode) {
					case self::MODE_DATE:
						$dateParts = preg_split('/\s+/', (string)date('Y m d', strtotime((string)($val[0] ?? '')))) ?: [];
						$want      = array_slice($terms, 0, 3);
						$match     = (bool)$want;
						foreach ($want as $i => $w) {
							if (!isset($dateParts[$i]) || $dateParts[$i] !== $w) {
								$match = false;
								break;
							}
						}
						break;
					case self::MODE_TITLE:
						$title = sp_strtolower((string)($val[1] ?? ''));
						$match = true;
						foreach ($terms as $term) {
							if (sp_strpos($title, sp_strtolower($term)) === false) {
								$match = false;
								break;
							}
						}
						break;
					case self::MODE_PERMALINK:
						if (sp_strtolower((string)($val[2] ?? '')) === sp_strtolower($query)) {
							return [$key => $val];
						}
						break;
					case self::MODE_TAG:
						$tags = array_map('sp_strtolower', self::postTags(['tag' => $val[3] ?? '']));
						$match = in_array(sp_strtolower($query), $tags, true);
						if (!$match) {
							foreach ($terms as $term) {
								if (in_array(sp_strtolower($term), $tags, true)) {
									$match = true;
									break;
								}
							}
						}
						break;
				}
				if ($match) {
					$found[$key] = $val;
				}
			}
		}
		return $found;
	}

	public static function index_page($post_array, $read_meta = false): array
	{
		$group = [];
		if (!is_array($post_array)) {
			return $group;
		}
		foreach ($post_array as $postid => $metadata) {
			$dec = self::loadPost($postid);
			if ($dec === null) {
				continue;
			}
			if ($read_meta) {
				$postmeta = [
					'date'      => (string)($dec['date'] ?? ''),
					'title'     => (string)($dec['title'] ?? ''),
					'permalink' => (string)($dec['permalink'] ?? ''),
					'tag'       => self::postTags($dec),
				];
			} else {
				$postmeta = self::splitMeta($metadata);
			}
			$thumbImg          = self::getImg((string)($dec['entry'] ?? ''));
			$group[$postid]    = [
				'id'      => (string)$postid,
				'image'   => $thumbImg,
				'tag'     => $postmeta['tag'],
				'title'   => $postmeta['title'],
				'url'     => self::_url($postmeta['permalink']),
				'date'    => $postmeta['date'],
				'entry'   => (string)($dec['entry'] ?? ''),
				'permalink' => $postmeta['permalink'],
			];
		}
		return $group;
	}

	public static function gravatar($email, $size = 48): string
	{
		$email = trim(sp_strtolower((string)$email));
		$hash  = md5($email !== '' ? $email : sp_strtolower(GRAV_EMAIL));
		return 'https://www.gravatar.com/avatar/' . $hash . '?s=' . (int)$size . '&d=identicon';
	}

	public static function validAdmin($user, $pass): bool
	{
		$user = (string)$user;
		$pass = (string)$pass;
		if (!hash_equals(ADMIN_NICKNM, $user)) {
			password_verify($pass, '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG');
			return false;
		}
		$stored = ADMIN_PASSWD;
		if (preg_match('/^\$2[ayb]\$/', $stored)) {
			return password_verify($pass, $stored);
		}
		return hash_equals($stored, $pass);
	}

	public static function commentFile($postId): string
	{
		return sp_path('sp_static/comment/' . self::sanitizePostId($postId) . '.json');
	}

	public static function getComments($postId): array
	{
		$file = self::commentFile($postId);
		if (!is_file($file)) {
			return [];
		}
		$list = json_decode((string)@file_get_contents($file), true);
		return is_array($list) ? $list : [];
	}

	public static function saveComments($postId, array $list): bool
	{
		$file = self::commentFile($postId);
		if ($file === sp_path('sp_static/comment/.json')) {
			return false;
		}
		return self::toFile($file, self::toJson(array_values($list)), true);
	}

	public static function approvedComments($postId): array
	{
		return array_values(array_filter(self::getComments($postId), static function ($c) {
			return is_array($c) && ($c['status'] ?? '') === 'approved';
		}));
	}

	public static function countCommentsByStatus($status): int
	{
		$count = 0;
		foreach ((array)glob(sp_path('sp_static/comment/*.json')) as $file) {
			$list = json_decode((string)@file_get_contents($file), true);
			if (!is_array($list)) {
				continue;
			}
			foreach ($list as $comment) {
				if (is_array($comment) && ($comment['status'] ?? '') === $status) {
					$count += 1;
				}
			}
		}
		return $count;
	}

	public static function allComments(): array
	{
		$all = [];
		foreach ((array)glob(sp_path('sp_static/comment/*.json')) as $file) {
			$postId = basename($file, '.json');
			$list   = json_decode((string)@file_get_contents($file), true);
			if (!is_array($list)) {
				continue;
			}
			foreach ($list as $index => $comment) {
				if (!is_array($comment)) {
					continue;
				}
				$comment['post_id'] = $postId;
				$comment['index']   = $index;
				$all[]              = $comment;
			}
		}
		return $all;
	}

	public static function moderateComment($postId, $index, $action): bool
	{
		$postId = self::sanitizePostId($postId);
		$index  = (int)$index;
		$list   = self::getComments($postId);
		if ($postId === '' || !isset($list[$index])) {
			return false;
		}
		if ($action === 'approve') {
			$list[$index]['status'] = 'approved';
			return self::saveComments($postId, $list);
		}
		if ($action === 'spam') {
			$list[$index]['status'] = 'spam';
			$saved = self::saveComments($postId, $list);
			if (!empty($list[$index]['ip'])) {
				self::ipBlackList($list[$index]['ip']);
			}
			return $saved;
		}
		if ($action === 'delete') {
			unset($list[$index]);
			return self::saveComments($postId, $list);
		}
		return false;
	}

	public static function submitComment($postId, array $input): array
	{
		$postId = self::sanitizePostId($postId);
		if ($postId === '' || self::loadPost($postId) === null) {
			return ['ok' => false, 'error' => 'Post not found.'];
		}
		if (!self::checkCsrf($input['sp_csrf'] ?? '')) {
			return ['ok' => false, 'error' => 'Security token invalid, please reload the page.'];
		}
		if (trim((string)($input['sp_trap'] ?? '')) !== '') {
			return ['ok' => true, 'error' => ''];
		}
		$ts = (int)($input['sp_ts'] ?? 0);
		if ($ts > 0 && time() - $ts > 86400) {
			return ['ok' => false, 'error' => 'This form has expired, please reload the page.'];
		}
		if (self::isBlacklisted()) {
			return ['ok' => false, 'error' => 'Your IP address is blocked.'];
		}
		if (isset($_SESSION['sp_comment_last']) && time() - (int)$_SESSION['sp_comment_last'] < 5) {
			return ['ok' => false, 'error' => 'You are commenting too quickly, please wait a moment.'];
		}
		$name    = trim((string)($input['cname'] ?? ''));
		$email   = trim((string)($input['cemail'] ?? ''));
		$url     = trim((string)($input['curl'] ?? ''));
		$content = trim((string)($input['cbody'] ?? ''));
		if ($name === '' || sp_strlen($name) > 50) {
			return ['ok' => false, 'error' => 'Name is required (max 50 characters).'];
		}
		if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return ['ok' => false, 'error' => 'Invalid email address.'];
		}
		if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
			return ['ok' => false, 'error' => 'Invalid website URL.'];
		}
		if (sp_strlen($content) < 3 || sp_strlen($content) > 5000) {
			return ['ok' => false, 'error' => 'Comment must be between 3 and 5000 characters.'];
		}
		$list         = self::getComments($postId);
		$list[]       = [
			'name'    => $name,
			'email'   => $email,
			'url'     => $url,
			'content' => $content,
			'date'    => date('c'),
			'ip'      => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
			'status'  => !empty(sp_config('comment_auto_approve')) ? 'approved' : 'pending',
		];
		if (!self::saveComments($postId, $list)) {
			return ['ok' => false, 'error' => 'Could not save the comment, check data folder permissions.'];
		}
		$_SESSION['sp_comment_last'] = time();
		return ['ok' => true, 'error' => ''];
	}
}

include sp_path('sp_theme/' . THEME . '/widget.php');

### START LISTENING ###
$post = new sedot();

$getRequest = sp_request_segments();
$route      = $getRequest[0] ?? '';

$_FRONT_PAGE   = false;
$_SINGLE_POST  = false;
$_PAGE_404     = false;
$_SEARCH_MODE  = false;
$searchQuery   = '';
$entries       = [];
$entry         = null;
$comments      = [];
$commentFlash  = null;

$trimPage = $getRequest;
foreach ($trimPage as $segKey => $segValue) {
	if ($segValue === 'page') {
		$trimPage = array_slice($trimPage, 0, $segKey);
		break;
	}
}
$actualUrl = rtrim(SITE_URL, '/');
if ($trimPage) {
	$actualUrl .= '/' . implode('/', $trimPage);
}
define('ACTUAL_URL', $actualUrl);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (($_POST['sp_action'] ?? '') === 'comment')) {
	$commentPostId = sedot::sanitizePostId($_POST['post_id'] ?? '');
	$commentTarget = $commentPostId !== '' ? sedot::loadPost($commentPostId) : null;
	if ($commentTarget === null) {
		http_response_code(404);
		exit('Post not found');
	}
	$commentResult = sedot::submitComment($commentPostId, $_POST);
	$_SESSION['sp_flash'] = $commentResult['ok']
		? ['type' => 'success', 'text' => 'Thank you, your comment has been received' . (sp_config('comment_auto_approve') ? '.' : ' and is awaiting moderation.')]
		: ['type' => 'danger', 'text' => $commentResult['error']];
	header('Location: ' . sedot::_url((string)$commentTarget['permalink'] . '#comments'));
	exit;
}

if ($route === '') {
	$_FRONT_PAGE = true;
	$pointer     = sedot::loadJson('index', 'index-0');
	$entries     = sedot::index_page($pointer, false);
	$post->navi(sedot::pageCount(sedot::totalPost()), 1);
} elseif ($route === 'page') {
	$_FRONT_PAGE = true;
	$pagenum     = ctype_digit((string)($getRequest[1] ?? '')) ? max(1, (int)$getRequest[1]) : 1;
	$pointer     = sedot::loadJson('index', 'index-' . ($pagenum - 1));
	if (!$pointer && $pagenum > 1) {
		$_FRONT_PAGE = false;
		$_PAGE_404   = true;
		http_response_code(404);
	}
	$entries = sedot::index_page($pointer, false);
	$post->navi(sedot::pageCount(sedot::totalPost()), $pagenum);
} elseif ($route === 'rss' || $route === 'rss.xml') {
	header('Content-Type: application/rss+xml; charset=UTF-8');
	echo sedot::loadFile(sp_path('sp_static/xml/rss.xml'));
	exit;
} elseif ($route === 'sitemap.xml') {
	header('Content-Type: application/xml; charset=UTF-8');
	echo sedot::loadFile(sp_path('sp_static/xml/sitemap.xml'));
	exit;
} elseif ($route === 'robots.txt') {
	header('Content-Type: text/plain; charset=UTF-8');
	echo "User-agent: *\nAllow: /\nDisallow: /backstage\nSitemap: " . sedot::_url('sitemap.xml') . "\n";
	exit;
} elseif ($route === 'search' || $route === 'tag' || $route === 'timeline') {
	$_FRONT_PAGE = true;
	$_SEARCH_MODE = true;
	$pageIdx     = array_search('page', $getRequest, true);
	$pagenum     = 1;
	if ($pageIdx !== false) {
		$nextSeg  = $getRequest[$pageIdx + 1] ?? '';
		$pagenum  = ctype_digit((string)$nextSeg) ? max(1, (int)$nextSeg) : 1;
		$termSegs = array_slice($getRequest, 1, $pageIdx - 1);
	} else {
		$termSegs = array_slice($getRequest, 1);
	}
	if ($route === 'search' && $termSegs === []) {
		$searchQuery = sp_strtolower(trim((string)($_GET['q'] ?? '')));
	} else {
		$searchQuery = implode(' ', $termSegs);
	}
	$mode = $route === 'search' ? sedot::MODE_TITLE : ($route === 'tag' ? sedot::MODE_TAG : sedot::MODE_DATE);
	$results = sedot::search($mode, $searchQuery);
	$pages   = $results ? array_chunk($results, POSTS_PER_PAGE, true) : [[]];
	if ($pagenum > count($pages)) {
		$_FRONT_PAGE = false;
		$_PAGE_404   = true;
		http_response_code(404);
		$entries = [];
	} else {
		$entries = sedot::index_page($pages[$pagenum - 1] ?? [], true);
	}
	$post->navi(count($pages), $pagenum);
} elseif ($route === 'backstage') {
	$authUser = (string)($_SERVER['PHP_AUTH_USER'] ?? '');
	$authPass = (string)($_SERVER['PHP_AUTH_PW'] ?? '');
	if (sedot::isBlacklisted()) {
		http_response_code(403);
		die('IP Address Blocked');
	}
	if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && ($getRequest[1] ?? '') === 'logout') {
		$_SESSION['ATTEMPT'] = 0;
		header('WWW-Authenticate: Basic realm="sedotpress"');
		header('Cache-Control: no-store');
		http_response_code(401);
		die('Logged out. Please sign in again.');
	}
	if (!sedot::validAdmin($authUser, $authPass)) {
		$_SESSION['ATTEMPT'] = (int)($_SESSION['ATTEMPT'] ?? 0) + 1;
		if ($_SESSION['ATTEMPT'] >= 10) {
			sedot::ipBlackList();
			$_SESSION['ATTEMPT'] = 0;
			http_response_code(403);
			die('Max attempt reached, your IP has been blocked.');
		}
		header('WWW-Authenticate: Basic realm="sedotpress"');
		header('Cache-Control: no-store');
		http_response_code(401);
		die('Not authorized');
	}
	$_SESSION['ATTEMPT'] = 0;
	require 'admin.php';
	exit;
} else {
	$found  = sedot::search(sedot::MODE_PERMALINK, $route);
	$id     = $found ? (string)array_key_first($found) : '';
	$entry  = $id !== '' ? sedot::formatPost($id) : null;
	if ($entry === null) {
		$_PAGE_404 = true;
		http_response_code(404);
	} else {
		$_SINGLE_POST = true;
		$comments     = sedot::approvedComments($id);
		$commentFlash = $_SESSION['sp_flash'] ?? null;
		unset($_SESSION['sp_flash']);
	}
}

require sp_path('sp_theme/' . THEME . '/template.php');
