<?php
require_once __DIR__ . '/db_class.php';

class PagesRepository {
    public function getAbout(): array {
        $stmt = DB::get()->prepare("SELECT page_id, title, content, image_path FROM pages WHERE slug='about' LIMIT 1");
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        return $row;
    }

    public function getBySlug(string $slug): array {
        $st = DB::get()->prepare("SELECT * FROM pages WHERE slug=? LIMIT 1");
        $st->bind_param('s', $slug);
        $st->execute();
        $row = $st->get_result()->fetch_assoc() ?: [];
        $st->close();
        return $row;
    }

    public function upsert(string $slug, string $title, string $content, ?string $imagePath, ?int $userId): void {
        $sql = "
          INSERT INTO pages (slug, title, content, image_path, updated_by)
          VALUES (?, ?, ?, ?, ?)
          ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            content = VALUES(content),
            image_path = VALUES(image_path),
            updated_by = VALUES(updated_by),
            updated_at = NOW()
        ";
        $st = DB::get()->prepare($sql);
        $uid = $userId ?? null;
        $st->bind_param('ssssi', $slug, $title, $content, $imagePath, $uid);
        $st->execute();
        $st->close();
    }

    public function updateAbout(int $pageId, string $title, string $content, ?string $imagePath, int $userId): void {
        $stmt = DB::get()->prepare("UPDATE pages SET title=?, content=?, image_path=?, updated_by=?, updated_at=NOW() WHERE page_id=?");
        $stmt->bind_param('sssii', $title, $content, $imagePath, $userId, $pageId);
        $stmt->execute();
        $stmt->close();
    }

    public function insertAbout(string $title, string $content, ?string $imagePath, int $userId): void {
        $slug = 'about';
        $stmt = DB::get()->prepare("INSERT INTO pages (slug, title, content, image_path, updated_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssi', $slug, $title, $content, $imagePath, $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function upsertAbout(string $title, string $content, ?string $imagePath, int $updatedBy): void {
    if ($imagePath !== null && $imagePath !== '') {
        $sql = "
          INSERT INTO pages (slug, title, content, image_path, updated_by)
          VALUES ('about', ?, ?, ?, ?)
          ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            content = VALUES(content),
            image_path = VALUES(image_path),
            updated_by = VALUES(updated_by)
        ";
        $stmt = DB::get()->prepare($sql);
        $stmt->bind_param('sssi', $title, $content, $imagePath, $updatedBy);
    } else {
        $sql = "
          INSERT INTO pages (slug, title, content, updated_by)
          VALUES ('about', ?, ?, ?)
          ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            content = VALUES(content),
            updated_by = VALUES(updated_by)
        ";
        $stmt = DB::get()->prepare($sql);
        $stmt->bind_param('ssi', $title, $content, $updatedBy);
    }
    $stmt->execute();
    $stmt->close();
}

}
