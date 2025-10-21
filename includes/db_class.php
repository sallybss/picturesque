<?php
class DB {
    private static ?mysqli $conn = null;

    public static function get(): mysqli {
        if (!self::$conn) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $c = new mysqli('localhost', 'root', '', 'picturesque_new');
            $c->set_charset('utf8mb4');
            self::$conn = $c;
        }
        return self::$conn;
    }
}
