<?php
require_once __DIR__ . '/db_class.php';
require_once __DIR__ . '/base_repository.php';

class SearchRepository extends BaseRepository
{
    public function peopleByDisplayNameLike(string $like, int $limit = 20): array {
        $sql = '
            SELECT profile_id, display_name, avatar_photo
            FROM profiles
            WHERE display_name LIKE ?
            ORDER BY display_name
            LIMIT ?
        ';
        $stmt = DB::get()->prepare($sql);
        $stmt->bind_param('si', $like, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}
