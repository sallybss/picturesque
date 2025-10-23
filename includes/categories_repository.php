<?php
require_once __DIR__ . '/db_class.php';

class CategoriesRepository {
    public function listActive(): array {
        $res = DB::get()->query("
            SELECT category_id,
                   category_name AS name,
                   slug
            FROM categories
            WHERE active = 1
            ORDER BY category_name
        ");
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(int $id): ?array {
        $st = DB::get()->prepare("SELECT * FROM categories WHERE category_id=? LIMIT 1");
        $st->bind_param('i', $id); $st->execute();
        $row = $st->get_result()->fetch_assoc(); $st->close();
        return $row ?: null;
    }
}
