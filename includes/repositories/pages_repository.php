<?php
require_once __DIR__ . '/../core/db_class.php';
require_once __DIR__ . '/base_repository.php';

class PagesRepository extends BaseRepository
{
    public function getBySlug(string $slug): ?array
    {
        $sql = "SELECT page_id, slug, title, content, image_path
                FROM pages
                WHERE slug = ?
                LIMIT 1";

        $st = $this->db->prepare($sql);
        $st->bind_param('s', $slug);
        $st->execute();
        $row = $st->get_result()->fetch_assoc() ?: null;
        $st->close();

        return $row;
    }

    public function getAbout(): ?array
    {
        return $this->getBySlug('about');
    }

    public function upsert(
        string $slug,
        string $title,
        string $content,
        ?string $imagePath,
        ?int $updatedBy
    ): void {
        $sql = "
          INSERT INTO pages (slug, title, content, image_path, updated_by, updated_at)
          VALUES (?, ?, ?, ?, ?, NOW())
          ON DUPLICATE KEY UPDATE
            title      = VALUES(title),
            content    = VALUES(content),
            image_path = VALUES(image_path),
            updated_by = VALUES(updated_by),
            updated_at = NOW()
        ";

        $st = $this->db->prepare($sql);
        $st->bind_param('ssssi', $slug, $title, $content, $imagePath, $updatedBy);
        $st->execute();
        $st->close();
    }

    public function saveAbout(
        string $title,
        string $content,
        ?string $imagePath,
        int $updatedBy
    ): void {
        $this->upsert('about', $title, $content, $imagePath, $updatedBy);
    }


    public function upsertAbout(
        string $title,
        string $content,
        ?string $imagePath,
        int $updatedBy
    ): void {
        $this->saveAbout($title, $content, $imagePath, $updatedBy);
    }

    public function updateAbout(
        int $pageId,
        string $title,
        string $content,
        ?string $imagePath,
        int $userId
    ): void {

        $sql = "
          UPDATE pages
             SET title = ?, content = ?, image_path = ?, updated_by = ?, updated_at = NOW()
           WHERE page_id = ? AND slug = 'about'
        ";
        $st = $this->db->prepare($sql);
        $st->bind_param('sssii', $title, $content, $imagePath, $userId, $pageId);
        $st->execute();
        $st->close();
    }

    public function insertAbout(
        string $title,
        string $content,
        ?string $imagePath,
        int $userId
    ): void {
        $this->upsert('about', $title, $content, $imagePath, $userId);
    }
}
