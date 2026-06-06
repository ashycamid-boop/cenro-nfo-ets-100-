<?php
session_start();

$sidebarRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'Enforcement Officer';
