<?php

if (!function_exists('enforcement_officer_signature_proxy_url')) {
  function enforcement_officer_signature_proxy_url($path)
  {
    $path = trim((string) $path);
    if ($path === '') {
      return '';
    }
    if (preg_match('#^https?://#i', $path)) {
      return $path;
    }

    $normalizedPath = str_replace('\\', '/', ltrim($path, '/'));
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $projectBase = '';
    if ($scriptName !== '') {
      $appPos = strpos($scriptName, '/app/');
      if ($appPos !== false) {
        $projectBase = substr($scriptName, 0, $appPos);
      } else {
        $projectBase = rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');
      }
    }

    return ($projectBase !== '' ? $projectBase : '/prototype')
      . '/app/modules/enforcement_officer/views/signature_image.php?path='
      . rawurlencode($normalizedPath);
  }
}
