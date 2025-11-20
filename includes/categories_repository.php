<?php
require_once __DIR__ . '/db_class.php';
require_once __DIR__ . '/base_repository.php';

class CategoriesRepository extends BaseRepository
{
    public function listActive(): array {
        $res = $this->db->query("
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
        $st = $this->db->prepare("SELECT * FROM categories WHERE category_id=? LIMIT 1");
        $st->bind_param('i', $id);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        return $row ?: null;
    }

    public function isActive(int $categoryId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM categories WHERE category_id = ? AND active = 1 LIMIT 1");
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $ok = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $ok;
    }
}
