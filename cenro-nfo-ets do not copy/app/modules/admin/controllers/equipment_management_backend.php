<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
  header('Location: ' . app_url('index.php'));
  exit;
}

$sidebarRole = 'Administrator';
