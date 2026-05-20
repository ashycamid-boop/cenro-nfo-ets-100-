<?php

if (!function_exists('cenro_qr_scheme')) {
    function cenro_qr_scheme() {
        $forwardedProto = trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwardedProto !== '') {
            $proto = strtolower(trim(explode(',', $forwardedProto)[0]));
            if (in_array($proto, ['http', 'https'], true)) {
                return $proto;
            }
        }

        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    }
}

if (!function_exists('cenro_qr_host')) {
    function cenro_qr_host() {
        $configuredHost = trim((string) getenv('CENRO_QR_HOST'));
        if ($configuredHost !== '') {
            return $configuredHost;
        }

        $forwardedHost = trim((string)($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''));
        $rawHost = $forwardedHost !== ''
            ? trim(explode(',', $forwardedHost)[0])
            : ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $hostOnly = $rawHost;
        $port = '';

        if (strpos($rawHost, ':') !== false && substr_count($rawHost, ':') === 1) {
            [$hostOnly, $port] = explode(':', $rawHost, 2);
        }

        $isLocalHost = in_array(strtolower($hostOnly), ['localhost', '127.0.0.1', '::1'], true);
        if ($isLocalHost) {
            $candidates = [
                $_SERVER['SERVER_ADDR'] ?? '',
                cenro_detect_gateway_ipv4(),
                gethostbyname(gethostname()),
            ];

            foreach ($candidates as $candidate) {
                $candidate = trim((string)$candidate);
                if ($candidate !== '' && !in_array($candidate, ['localhost', '127.0.0.1', '::1'], true)) {
                    $hostOnly = $candidate;
                    break;
                }
            }
        }

        if ($port !== '' && !in_array($port, ['80', '443'], true)) {
            return $hostOnly . ':' . $port;
        }

        return $hostOnly;
    }
}

if (!function_exists('cenro_detect_gateway_ipv4')) {
    function cenro_detect_gateway_ipv4() {
        if (stripos(PHP_OS_FAMILY, 'Windows') === false || !function_exists('shell_exec')) {
            return '';
        }

        $output = @shell_exec('ipconfig');
        if (!is_string($output) || trim($output) === '') {
            return '';
        }

        $blocks = preg_split('/\R\s*\R/', $output) ?: [];
        foreach ($blocks as $block) {
            if (stripos($block, 'IPv4 Address') === false || stripos($block, 'Default Gateway') === false) {
                continue;
            }

            if (!preg_match('/IPv4 Address[^\:]*:\s*([0-9.]+)/i', $block, $ipMatch)) {
                continue;
            }

            if (!preg_match('/Default Gateway[^\:]*:\s*([0-9.]+)/i', $block, $gatewayMatch)) {
                continue;
            }

            $ip = trim($ipMatch[1]);
            $gateway = trim($gatewayMatch[1]);
            if ($ip !== '' && $gateway !== '' && strpos($ip, '127.') !== 0 && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ip;
            }
        }

        return '';
    }
}

if (!function_exists('cenro_qr_origin')) {
    function cenro_qr_origin() {
        return cenro_qr_scheme() . '://' . cenro_qr_host();
    }
}

if (!function_exists('cenro_project_base_path')) {
    function cenro_project_base_path() {
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $appPos = strpos($requestPath, '/app/');
        if ($appPos !== false) {
            return rtrim(substr($requestPath, 0, $appPos), '/');
        }

        return '';
    }
}

if (!function_exists('cenro_project_url')) {
    function cenro_project_url($relativePath) {
        $relativePath = ltrim((string)$relativePath, '/');
        $basePath = cenro_project_base_path();
        return cenro_qr_origin() . ($basePath !== '' ? $basePath : '') . '/' . $relativePath;
    }
}

if (!function_exists('cenro_current_dir_url')) {
    function cenro_current_dir_url($relativePath) {
        $relativePath = ltrim((string)$relativePath, '/');
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $dir = rtrim(str_replace('\\', '/', dirname($requestPath)), '/');
        return cenro_qr_origin() . ($dir !== '' ? $dir : '') . '/' . $relativePath;
    }
}
