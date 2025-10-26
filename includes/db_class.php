<?php
class DB {
    private static ?mysqli $db = null;

    public static function get(): mysqli {
        if (!self::$db) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            $cfgFile = __DIR__ . '/env.php';
            if (!file_exists($cfgFile)) {
                $cfgFile = __DIR__ . '/env.sample.php'; 
            }
            $cfg = require $cfgFile;

            self::$db = new mysqli(
                $cfg['host'],
                $cfg['user'],
                $cfg['pass'],
                $cfg['name'],
                $cfg['port'] ?? 3306
            );
            self::$db->set_charset('utf8mb4');
        }
        return self::$db;
    }
}
