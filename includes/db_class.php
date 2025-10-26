<?php
class DB {
    private static ?mysqli $conn = null;

    public static function get(): mysqli {
        if (!self::$conn) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            $cfg = require __DIR__ . '/env.php';

            $c = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], $cfg['port'] ?? 3306);
            $c->set_charset('utf8mb4');

            self::$conn = $c;
        }
        return self::$conn;
    }
}
