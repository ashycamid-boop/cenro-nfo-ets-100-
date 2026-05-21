<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'property_custodian') {
  header('Location: ' . app_url('index.php'));
  exit;
}
