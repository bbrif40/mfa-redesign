<?php
// Router for PHP's local development server: php -S 127.0.0.1:8080 -t htdocs router.php
$root = realpath($_SERVER['DOCUMENT_ROOT']);
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$file = realpath($root . ($path === '/' ? '/index.php' : $path));
if (!$file || !str_starts_with($file, $root . DIRECTORY_SEPARATOR) || !is_file($file)) {
    http_response_code(404); exit('Not found');
}
if (str_starts_with($file, $root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR)
    || in_array(basename($file), ['config.php', 'config.example.php'], true)) {
    http_response_code(403); exit('Forbidden');
}
return false;
