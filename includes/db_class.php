<?php
class DB {
  private static $db;

  public static function get(): mysqli {
    if (!self::$db) {
      $cfg = null;
      if (file_exists(__DIR__ . '/config.local.php')) {
        $cfg = require __DIR__ . '/config.local.php';
      } elseif (file_exists(__DIR__ . '/config.server.php')) {
        $cfg = require __DIR__ . '/config.server.php';
      } else {
        die('No DB config found.');
      }

      mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
      self::$db = new mysqli(
        $cfg['db_host'],
        $cfg['db_user'],
        $cfg['db_pass'],
        $cfg['db_name'],
        $cfg['db_port'] ?? 3306
      );
      self::$db->set_charset('utf8mb4');
    }
    return self::$db;
  }
}
