<?php
require_once __DIR__ . '/db_class.php';

class PictureRepository {
    public function feed(int $viewerId, ?string $q = null): array {
        if ($q === null || $q === '') {
            $sql = "
                SELECT
                  p.picture_id AS pic_id,
                  p.profile_id AS pic_profile_id,
                  p.picture_title AS pic_title,
                  p.picture_description AS pic_desc,
                  p.picture_url AS pic_url,
                  p.created_at AS pic_created_at,
                  pr.display_name AS author_name,
                  pr.avatar_photo AS author_avatar,
                  COALESCE(l.cnt, 0) AS like_count,
                  COALESCE(c.cnt, 0) AS comment_count,
                  CASE WHEN ml.like_id IS NULL THEN 0 ELSE 1 END AS liked_by_me
                FROM pictures p
                JOIN profiles pr ON pr.profile_id = p.profile_id
                LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM likes GROUP BY picture_id) l ON l.picture_id = p.picture_id
                LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM comments GROUP BY picture_id) c ON c.picture_id = p.picture_id
                LEFT JOIN likes ml ON ml.picture_id = p.picture_id AND ml.profile_id = ?
                ORDER BY p.created_at DESC
            ";
            $stmt = DB::get()->prepare($sql);
            $stmt->bind_param('i', $viewerId);
        } else {
            $like = '%' . $q . '%';
            $likeUser = '%' . ltrim($q, '@') . '%';
            $sql = "
                SELECT
                  p.picture_id AS pic_id,
                  p.profile_id AS pic_profile_id,
                  p.picture_title AS pic_title,
                  p.picture_description AS pic_desc,
                  p.picture_url AS pic_url,
                  p.created_at AS pic_created_at,
                  pr.display_name AS author_name,
                  pr.avatar_photo AS author_avatar,
                  COALESCE(l.cnt, 0) AS like_count,
                  COALESCE(c.cnt, 0) AS comment_count,
                  CASE WHEN ml.like_id IS NULL THEN 0 ELSE 1 END AS liked_by_me
                FROM pictures p
                JOIN profiles pr ON pr.profile_id = p.profile_id
                LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM likes GROUP BY picture_id) l ON l.picture_id = p.picture_id
                LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM comments GROUP BY picture_id) c ON c.picture_id = p.picture_id
                LEFT JOIN likes ml ON ml.picture_id = p.picture_id AND ml.profile_id = ?
                WHERE (p.picture_title LIKE ? OR p.picture_description LIKE ? OR pr.display_name LIKE ?)
                ORDER BY p.created_at DESC
            ";
            $stmt = DB::get()->prepare($sql);
            $stmt->bind_param('isss', $viewerId, $like, $like, $likeUser);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function getOneWithCountsAndAuthor(int $pictureId): ?array {
        $sql = "
          SELECT
            p.picture_id, p.profile_id, p.picture_title, p.picture_description, p.picture_url, p.created_at,
            pr.display_name,
            COALESCE(l.cnt,0) AS like_count,
            COALESCE(c.cnt,0) AS comment_count
          FROM pictures p
          JOIN profiles pr ON pr.profile_id = p.profile_id
          LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM likes GROUP BY picture_id) l ON l.picture_id = p.picture_id
          LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM comments GROUP BY picture_id) c ON c.picture_id = p.picture_id
          WHERE p.picture_id = ?
          LIMIT 1
        ";
        $stmt = DB::get()->prepare($sql);
        $stmt->bind_param('i', $pictureId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $row;
    }

    public function countByProfile(int $profileId): int {
        $stmt = DB::get()->prepare("SELECT COUNT(*) AS cnt FROM pictures WHERE profile_id = ?");
        $stmt->bind_param('i', $profileId);
        $stmt->execute();
        $cnt = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
        $stmt->close();
        return $cnt;
    }

    public function likesCountForProfilePictures(int $profileId): int {
        $sql = "SELECT COUNT(*) AS cnt FROM likes l JOIN pictures p ON p.picture_id = l.picture_id WHERE p.profile_id = ?";
        $stmt = DB::get()->prepare($sql);
        $stmt->bind_param('i', $profileId);
        $stmt->execute();
        $cnt = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
        $stmt->close();
        return $cnt;
    }

    public function commentsCountForProfilePictures(int $profileId): int {
        $sql = "SELECT COUNT(*) AS cnt FROM comments c JOIN pictures p ON p.picture_id = c.picture_id WHERE p.profile_id = ?";
        $stmt = DB::get()->prepare($sql);
        $stmt->bind_param('i', $profileId);
        $stmt->execute();
        $cnt = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
        $stmt->close();
        return $cnt;
    }

    public function listByProfile(int $profileId): array {
        $sql = "
          SELECT
            p.picture_id, p.picture_title, p.picture_description, p.picture_url, p.created_at,
            COALESCE(l.cnt,0) AS like_count,
            COALESCE(c.cnt,0) AS comment_count
          FROM pictures p
          LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM likes GROUP BY picture_id) l ON l.picture_id = p.picture_id
          LEFT JOIN (SELECT picture_id, COUNT(*) cnt FROM comments GROUP BY picture_id) c ON c.picture_id = p.picture_id
          WHERE p.profile_id = ?
          ORDER BY p.created_at DESC
        ";
        $stmt = DB::get()->prepare($sql);
        $stmt->bind_param('i', $profileId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function getEditableByOwner(int $pictureId, int $ownerId): ?array {
    $sql = "
      SELECT picture_id, picture_title, picture_description, picture_url
      FROM pictures
      WHERE picture_id = ? AND profile_id = ?
      LIMIT 1
    ";
    $stmt = DB::get()->prepare($sql);
    $stmt->bind_param('ii', $pictureId, $ownerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row;
}

    public function getOwnerAndUrl(int $pictureId): ?array {
    $sql = "SELECT profile_id AS owner_id, picture_url FROM pictures WHERE picture_id = ? LIMIT 1";
    $stmt = DB::get()->prepare($sql);
    $stmt->bind_param('i', $pictureId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row;
}

public function deleteById(int $pictureId): void {
    $stmt = DB::get()->prepare("DELETE FROM pictures WHERE picture_id = ?");
    $stmt->bind_param('i', $pictureId);
    $stmt->execute();
    $stmt->close();
}

public function getUrlIfOwned(int $pictureId, int $ownerId): ?string {
    $stmt = DB::get()->prepare("SELECT picture_url FROM pictures WHERE picture_id = ? AND profile_id = ? LIMIT 1");
    $stmt->bind_param('ii', $pictureId, $ownerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row['picture_url'] ?? null;
}

public function deleteCascadeOwned(int $pictureId, int $ownerId): void {
    $db = DB::get();
    $db->begin_transaction();
    try {
        $d1 = $db->prepare("DELETE FROM comments WHERE picture_id = ?");
        $d1->bind_param('i', $pictureId); $d1->execute(); $d1->close();

        $d2 = $db->prepare("DELETE FROM likes WHERE picture_id = ?");
        $d2->bind_param('i', $pictureId); $d2->execute(); $d2->close();

        $d3 = $db->prepare("DELETE FROM pictures WHERE picture_id = ? AND profile_id = ?");
        $d3->bind_param('ii', $pictureId, $ownerId); $d3->execute(); $d3->close();

        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

public function create(int $profileId, string $title, string $desc, string $filename): int {
    $stmt = DB::get()->prepare("
        INSERT INTO pictures (profile_id, picture_title, picture_description, picture_url)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param('isss', $profileId, $title, $desc, $filename);
    $stmt->execute();
    $id = DB::get()->insert_id;
    $stmt->close();
    return (int)$id;
}

public function updateOwned(int $pictureId, int $ownerId, string $title, string $desc, ?string $filename): void {
    if ($filename === null) {
        $stmt = DB::get()->prepare('UPDATE pictures SET picture_title = ?, picture_description = ? WHERE picture_id = ? AND profile_id = ?');
        $stmt->bind_param('ssii', $title, $desc, $pictureId, $ownerId);
    } else {
        $stmt = DB::get()->prepare('UPDATE pictures SET picture_title = ?, picture_description = ?, picture_url = ? WHERE picture_id = ? AND profile_id = ?');
        $stmt->bind_param('sssii', $title, $desc, $filename, $pictureId, $ownerId);
    }
    $stmt->execute();
    $stmt->close();
}

public function clearImage(int $pictureId, int $ownerId): void {
    $stmt = DB::get()->prepare('UPDATE pictures SET picture_url = NULL WHERE picture_id = ? AND profile_id = ?');
    $stmt->bind_param('ii', $pictureId, $ownerId);
    $stmt->execute();
    $stmt->close();
}



}
