<?php
session_start();

function checkAdminAccess() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../public/login.php");
        exit();
    }
} 