<?php
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$faviconBaseUrl = '';

if ($scriptName !== '') {
    $appPos = strpos($scriptName, '/app/');

    if ($appPos !== false) {
        $faviconBaseUrl = substr($scriptName, 0, $appPos);
    } else {
        $faviconBaseUrl = rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');
    }
}

$faviconHref = ($faviconBaseUrl !== '' ? $faviconBaseUrl : '') . '/public/assets/images/denr-logo.png';
$faviconHref = preg_replace('#(?<!:)/{2,}#', '/', $faviconHref);
?>
  <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($faviconHref, ENT_QUOTES, 'UTF-8'); ?>">
  <link rel="shortcut icon" type="image/png" href="<?php echo htmlspecialchars($faviconHref, ENT_QUOTES, 'UTF-8'); ?>">
  <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($faviconHref, ENT_QUOTES, 'UTF-8'); ?>">
