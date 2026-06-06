<?php
session_start();

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../../../../index.php');
    exit;
}
