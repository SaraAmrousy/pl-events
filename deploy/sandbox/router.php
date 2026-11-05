<?php
// php -S router for the sandbox (pretty permalinks).
$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$file = __DIR__ . '/../../../wp-sandbox/site' . $path;
if ( $path !== '/' && file_exists( $_SERVER['DOCUMENT_ROOT'] . $path ) && ! is_dir( $_SERVER['DOCUMENT_ROOT'] . $path ) ) { return false; }
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
