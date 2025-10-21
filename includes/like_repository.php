<?php
require_once __DIR__ . '/db_class.php';

class LikeRepository {
    public function has(int $pictureId, int $userId): bool {
        $stmt = DB::get()->prepare('SELECT 1 FROM likes WHERE picture_id = ? AND profile_id = ?');
        $stmt->bind_param('ii', $pictureId, $userId);
        $stmt->execute();
        $exists = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $exists;
    }

    public function add(int $pictureId, int $userId): void {
        $stmt = DB::get()->prepare('INSERT INTO likes (picture_id, profile_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $pictureId, $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function remove(int $pictureId, int $userId): void {
        $stmt = DB::get()->prepare('DELETE FROM likes WHERE picture_id = ? AND profile_id = ?');
        $stmt->bind_param('ii', $pictureId, $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function toggle(int $pictureId, int $userId): bool {
        if ($this->has($pictureId, $userId)) {
            $this->remove($pictureId, $userId);
            return false;
        }
        $this->add($pictureId, $userId);
        return true;
    }
}
