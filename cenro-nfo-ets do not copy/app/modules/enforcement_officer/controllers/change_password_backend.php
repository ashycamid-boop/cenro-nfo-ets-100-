<?php
session_start();

if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'enforcement_officer') {
    header('Location: ../../../../index.php');
    exit;
}
