<?php
require_once __DIR__ . '/db_class.php';
require_once __DIR__ . '/base_repository.php';
class ProfileRepository extends BaseRepository
{
    public function getHeader(int $id): array {
        $sql = 'SELECT display_name, avatar_photo, role FROM profiles WHERE profile_id = ?';
        $stmt = DB::get()->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        return $row;
    }
    public function getById(int $id): array {
        $sql = 'SELECT profile_id, display_name, email, avatar_photo, cover_photo, role, created_at FROM profiles WHERE profile_id = ?';
        $stmt = DB::get()->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        return $row;
    }
    public function getLoginEmailAndRole(int $id): array {
        $sql = 'SELECT login_email, role FROM profiles WHERE profile_id = ?';
        $stmt = DB::get()->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        return $row;
    }
    public function searchUsersWithStats(?string $q = null): array {
        $baseSql = "
            SELECT
                pr.profile_id,
                pr.display_name,
                pr.login_email,
                pr.email,
                pr.avatar_photo,
                pr.role,
                pr.status,
                pr.created_at,
                (SELECT COUNT(*) FROM pictures p WHERE p.profile_id = pr.profile_id) AS posts
            FROM profiles pr
        ";
        if ($q !== null && $q !== '') {
            $sql = $baseSql . " WHERE pr.login_email LIKE ? OR pr.display_name LIKE ? ORDER BY pr.created_at DESC";
            $stmt = DB::get()->prepare($sql);
            $like = '%' . $q . '%';
            $stmt->bind_param('ss', $like, $like);
        } else {
            $sql = $baseSql . " ORDER BY pr.created_at DESC";
            $stmt = DB::get()->prepare($sql);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
    public function getRole(int $profileId): ?string {
        $stmt = DB::get()->prepare('SELECT role FROM profiles WHERE profile_id = ?');
        $stmt->bind_param('i', $profileId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row['role'] ?? null;
    }
    public function deleteById(int $profileId): void {
        $stmt = DB::get()->prepare('DELETE FROM profiles WHERE profile_id = ?');
        $stmt->bind_param('i', $profileId);
        $stmt->execute();
        $stmt->close();
    }
    public function getRoleAndStatus(int $profileId): ?array {
        $stmt = DB::get()->prepare('SELECT role, status FROM profiles WHERE profile_id = ?');
        $stmt->bind_param('i', $profileId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $row;
    }
    public function updateStatus(int $profileId, string $status): void {
        $stmt = DB::get()->prepare('UPDATE profiles SET status = ? WHERE profile_id = ?');
        $stmt->bind_param('si', $status, $profileId);
        $stmt->execute();
        $stmt->close();
    }
    public function findAuthByEmail(string $email): ?array {
        $stmt = DB::get()->prepare('
            SELECT profile_id, display_name, role, password_hash, status
            FROM profiles
            WHERE login_email = ?
            LIMIT 1
        ');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $row;
    }
    public function emailInUseByOther(string $email, int $excludeId): bool {
        $stmt = DB::get()->prepare('SELECT 1 FROM profiles WHERE email = ? AND profile_id <> ?');
        $stmt->bind_param('si', $email, $excludeId);
        $stmt->execute();
        $exists = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $exists;
    }
    public function updateProfileWithEmailAndAvatar(int $id, string $name, ?string $email, ?string $avatar): void {
        if ($avatar !== null && $email !== null) {
            $stmt = DB::get()->prepare('UPDATE profiles SET display_name=?, email=?, avatar_photo=? WHERE profile_id=?');
            $stmt->bind_param('sssi', $name, $email, $avatar, $id);
        } elseif ($avatar !== null && $email === null) {
            $stmt = DB::get()->prepare('UPDATE profiles SET display_name=?, email=NULL, avatar_photo=? WHERE profile_id=?');
            $stmt->bind_param('ssi', $name, $avatar, $id);
        } elseif ($avatar === null && $email !== null) {
            $stmt = DB::get()->prepare('UPDATE profiles SET display_name=?, email=? WHERE profile_id=?');
            $stmt->bind_param('ssi', $name, $email, $id);
        } else {
            $stmt = DB::get()->prepare('UPDATE profiles SET display_name=?, email=NULL WHERE profile_id=?');
            $stmt->bind_param('si', $name, $id);
        }
        $stmt->execute();
        $stmt->close();
    }
    public function loginEmailExists(string $email): bool {
        $stmt = DB::get()->prepare('SELECT 1 FROM profiles WHERE login_email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $exists = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $exists;
    }
    public function createUser(string $loginEmail, string $displayName, string $passwordHash): int {
        $stmt = DB::get()->prepare('
            INSERT INTO profiles (login_email, password_hash, display_name, role, status)
            VALUES (?, ?, ?, "user", "active")
        ');
        $stmt->bind_param('sss', $loginEmail, $passwordHash, $displayName);
        $stmt->execute();
        $id = DB::get()->insert_id;
        $stmt->close();
        return (int)$id;
    }
    public function setCoverPhoto(int $profileId, ?string $filename): void {
        if ($filename === null) {
            $stmt = DB::get()->prepare('UPDATE profiles SET cover_photo = NULL WHERE profile_id = ?');
            $stmt->bind_param('i', $profileId);
        } else {
            $stmt = DB::get()->prepare('UPDATE profiles SET cover_photo = ? WHERE profile_id = ?');
            $stmt->bind_param('si', $filename, $profileId);
        }
        $stmt->execute();
        $stmt->close();
    }
    public function getLoginEmailAndHash(int $profileId): ?array {
        $stmt = DB::get()->prepare('SELECT login_email, password_hash FROM profiles WHERE profile_id = ? LIMIT 1');
        $stmt->bind_param('i', $profileId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $row;
    }
    public function loginEmailInUseByOther(string $email, int $excludeId): bool {
        $stmt = DB::get()->prepare('SELECT 1 FROM profiles WHERE login_email = ? AND profile_id <> ? LIMIT 1');
        $stmt->bind_param('si', $email, $excludeId);
        $stmt->execute();
        $exists = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $exists;
    }
    public function updateLoginEmail(int $profileId, string $email): void {
        $stmt = DB::get()->prepare('UPDATE profiles SET login_email = ? WHERE profile_id = ?');
        $stmt->bind_param('si', $email, $profileId);
        $stmt->execute();
        $stmt->close();
    }
    public function updateLoginEmailAndPassword(int $profileId, string $email, string $passwordHash): void {
        $stmt = DB::get()->prepare('UPDATE profiles SET login_email = ?, password_hash = ? WHERE profile_id = ?');
        $stmt->bind_param('ssi', $email, $passwordHash, $profileId);
        $stmt->execute();
        $stmt->close();
    }
}