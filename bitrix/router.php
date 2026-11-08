<?php
// PHP development server only. Apache redirects are generated in .htaccess.
$root=rtrim($_SERVER['DOCUMENT_ROOT'],'/');
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH) ?? '/');
$manifest=json_decode(file_get_contents($root.'/local/migrations/catalog.json'),true);
$old=ltrim($path,'/');
if (isset($manifest['redirects'][$old])) {
    $query=parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY);
    header('Location: '.$manifest['redirects'][$old].($query?'?'.$query:''),true,301);return true;
}
// Let the server handle existing files/directories without exposing parent paths.
$file=realpath($root.$path);
if ($file && str_starts_with($file,$root.'/') && (is_file($file) || is_file($file.'/index.php'))) return false;
if ($path==='/') return false;
http_response_code(404);require $root.'/404/index.php';return true;
