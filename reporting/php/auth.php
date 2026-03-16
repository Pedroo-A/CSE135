<?php
session_start();

if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    header('Location: login.php');
    exit();
}


function hasAccess($section) {
    $role = $_SESSION['role'] ?? '';
    
    //super admins are gods
    if ($role === 'super_admin') return true;

    // analysts
    if ($role === 'analyst') {
        $raw = $_SESSION['allowed_sections'] ?? '';
        $allowed = array_map('trim', explode(',', $raw));
        
        return in_array($section, $allowed) || in_array('all', $allowed);
    }

    //viewers
    return false;
}