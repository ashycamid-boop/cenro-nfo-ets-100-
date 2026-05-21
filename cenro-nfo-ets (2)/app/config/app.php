<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/cenro-nfo-ets/');
}

function app_url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}
