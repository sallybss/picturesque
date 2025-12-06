<?php
class DB {
    private static ?mysqli $db = null;

    public static function get(): mysqli {
        if (!self::$db) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            $cfgFile = __DIR__ . '/env.php';
            if (!file_exists($cfgFile)) {

                $cfgFile = __DIR__ . '/env_sample.php';
            }

            $cfg = require $cfgFile;
            if (!is_array($cfg)) {
                throw new RuntimeException('env.php/env_sample.php must return an array');
            }

            self::$db = new mysqli(
                $cfg['host'] ?? 'localhost',
                $cfg['user'] ?? '',
                $cfg['pass'] ?? '',
                $cfg['name'] ?? '',
                $cfg['port'] ?? 3306
            );
            self::$db->set_charset('utf8mb4');
        }
        return self::$db;
    }
}
