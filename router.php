<?php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) ? $path : '/';
if (strpos($path, '..') !== false) {
	http_response_code(404);
	exit('Not Found');
}
if (preg_match('#^/(?:sp_post|sp_static)/#', $path) || ltrim($path, '/') === 'config.json') {
	http_response_code(404);
	exit('Not Found');
}
$local = __DIR__ . rawurldecode($path);
if ($path !== '/' && is_file($local)) {
	return false;
}
require __DIR__ . '/index.php';
