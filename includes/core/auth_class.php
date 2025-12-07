<?php
require_once __DIR__ . '/db_class.php';

class Auth {
    public static function requireUserOrRedirect(string $redirect): int {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['profile_id'])) {
            header("Location: {$redirect}");
            exit;
        }
        return (int)$_SESSION['profile_id'];
    }

    public static function requireAdminOrRedirect(string $redirect = './index.php'): int {
        $id = self::requireUserOrRedirect('./auth/login.php');

        $stmt = DB::get()->prepare('SELECT role FROM profiles WHERE profile_id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        if (strtolower($row['role'] ?? '') !== 'admin') {
            header("Location: $redirect");
            exit;
        }
        return $id;
    }
}
