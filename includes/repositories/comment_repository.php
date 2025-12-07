<?php
require_once __DIR__ . '/../core/db_class.php';
require_once __DIR__ . '/base_repository.php';

class CommentRepository extends BaseRepository 
{
    public function listForPictureWithAuthors(int $pictureId): array {
        $sql = "
            SELECT
              c.comment_id,
              c.picture_id,
              c.profile_id,
              c.parent_comment_id AS parent_id,
              c.comment_content,
              c.created_at,
              p.display_name,
              p.avatar_photo
            FROM comments c
            JOIN profiles p ON p.profile_id = c.profile_id
            WHERE c.picture_id = ?
            ORDER BY c.created_at ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $pictureId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function pictureExists(int $pictureId): bool {
        $st = $this->db->prepare("SELECT 1 FROM pictures WHERE picture_id = ? LIMIT 1");
        $st->bind_param('i', $pictureId);
        $st->execute();
        $ok = (bool)$st->get_result()->fetch_row();
        $st->close();
        return $ok;
    }

    public function parentExists(int $parentId, int $pictureId): bool {
        $st = $this->db->prepare(
            "SELECT 1 FROM comments WHERE comment_id = ? AND picture_id = ?"
        );
        $st->bind_param('ii', $parentId, $pictureId);
        $st->execute();
        $ok = (bool)$st->get_result()->fetch_row();
        $st->close();
        return $ok;
    }

    public function add(
        int $pictureId, 
        int $userId, 
        string $content, 
        ?int $parentId = null
    ): void 
    {
        if ($parentId === null) {
            $sql = "
                INSERT INTO comments (picture_id, profile_id, comment_content, created_at)
                VALUES (?, ?, ?, NOW())
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iis', $pictureId, $userId, $content);
        } else {
            $sql = "
                INSERT INTO comments (picture_id, profile_id, parent_comment_id, comment_content, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iiis', $pictureId, $userId, $parentId, $content);
        }

        $stmt->execute();
        $stmt->close();
    }

    public function countRecentForUser(int $profileId, int $minutes = 1): int
    {
        if ($minutes < 1)  { $minutes = 1; }
        if ($minutes > 60) { $minutes = 60; }

        $sql = "
            SELECT COUNT(*) AS c
            FROM comments
            WHERE profile_id = ?
              AND created_at >= (NOW() - INTERVAL ? MINUTE)
        ";

        $mysqli = DB::get();
        $stmt   = $mysqli->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("prepare failed: " . $mysqli->error);
        }
        
        $stmt->bind_param('ii', $profileId, $minutes);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: ['c' => 0];
        $stmt->close();

        return (int)$row['c'];
    }

   public function delete(int $commentId): void
{
    $db = DB::get();
    $db->begin_transaction();

    try {
        $st = $db->prepare("DELETE FROM comments WHERE parent_comment_id = ?");
        $st->bind_param('i', $commentId);
        $st->execute();
        $st->close();

        $st = $db->prepare("DELETE FROM comments WHERE comment_id = ?");
        $st->bind_param('i', $commentId);
        $st->execute();
        $st->close();

        $db->commit();
    } catch (\mysqli_sql_exception $e) {
        $db->rollback();
        throw $e;
    }
}

}