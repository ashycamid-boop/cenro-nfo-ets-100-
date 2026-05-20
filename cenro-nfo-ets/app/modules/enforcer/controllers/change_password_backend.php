<?php
session_start();

if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'enforcer') {
    header('Location: ../../../../index.php');
    exit;
}
