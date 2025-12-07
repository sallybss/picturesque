<?php
require_once __DIR__ . '/../core/db_class.php';
require_once __DIR__ . '/base_repository.php';

class CategoriesRepository extends BaseRepository
{
    public function listActive(): array
    {
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

    public function getById(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM categories WHERE category_id=? LIMIT 1");
        $st->bind_param('i', $id);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        return $row ?: null;
    }

    public function isActive(int $categoryId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM categories WHERE category_id = ? AND active = 1 LIMIT 1"
        );
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $ok = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $ok;
    }

    public function create(string $name, string $slug): bool
    {
        try {
            $st = $this->db->prepare(
                "INSERT INTO categories (category_name, slug, active) VALUES (?, ?, 1)"
            );
            $st->bind_param('ss', $name, $slug);
            $st->execute();
            $st->close();
            return true;
        } catch (\mysqli_sql_exception $e) {
            return false;
        }
    }

    public function toggleActive(int $id): void
    {
        $st = $this->db->prepare(
            "UPDATE categories SET active = 1 - active WHERE category_id = ?"
        );
        $st->bind_param('i', $id);
        $st->execute();
        $st->close();
    }

    public function countAll(): int
    {
        $res = $this->db->query("SELECT COUNT(*) AS cnt FROM categories");
        $row = $res->fetch_assoc();
        return (int)($row['cnt'] ?? 0);
    }

    public function listWithPicCount(): array
    {
        $sql = "
            SELECT c.category_id,
                   c.category_name,
                   c.slug,
                   c.active,
                   COUNT(p.picture_id) AS pic_count
            FROM categories c
            LEFT JOIN pictures p ON p.category_id = c.category_id
            GROUP BY c.category_id, c.category_name, c.slug, c.active
            ORDER BY c.active DESC, c.category_name
        ";
        $res = $this->db->query($sql);
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function delete(int $id): bool
    {       
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM pictures WHERE category_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $cnt = (int)($res['cnt'] ?? 0);
        if ($cnt > 0) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $ok = ($stmt->affected_rows === 1);
        $stmt->close();

        return $ok;
    }
}
