<?php
require_once __DIR__ . '/flash.php';

function require_admin(mysqli $conn, int $me): void {
    $stmt = $conn->prepare("SELECT role FROM profiles WHERE profile_id = ?");
    $stmt->bind_param('i', $me);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $role = strtolower($row['role'] ?? '');

    if ($role !== 'admin') {
        set_flash('err', 'Admins only.');
        header('Location: ./index.php'); 
        exit;
    }
}
