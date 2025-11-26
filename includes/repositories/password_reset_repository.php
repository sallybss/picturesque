<?php

class PasswordResetRepository
{
    public function createToken(int $userId, string $token, int $ttlSeconds = 3600): void
    {
        $mysqli = DB::get();
        $tokenHash = hash('sha256', $token);
        $expiresAt = (new DateTimeImmutable("+{$ttlSeconds} seconds"))->format('Y-m-d H:i:s');

        // Delete old tokens for this user
        $stmt = $mysqli->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        // Insert new token
        $stmt = $mysqli->prepare("
            INSERT INTO password_resets (user_id, token_hash, expires_at)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('iss', $userId, $tokenHash, $expiresAt);
        $stmt->execute();
        $stmt->close();
    }

    public function findValidByToken(string $token): ?array
    {
        $mysqli = DB::get();
        $tokenHash = hash('sha256', $token);
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $stmt = $mysqli->prepare("
            SELECT *
            FROM password_resets
            WHERE token_hash = ?
              AND expires_at > ?
              AND used_at IS NULL
            LIMIT 1
        ");
        $stmt->bind_param('ss', $tokenHash, $now);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc() ?: null;
        $stmt->close();

        return $row;
    }

    public function markUsed(int $id): void
    {
        $mysqli = DB::get();
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $stmt = $mysqli->prepare("
            UPDATE password_resets
            SET used_at = ?
            WHERE id = ?
        ");
        $stmt->bind_param('si', $now, $id);
        $stmt->execute();
        $stmt->close();
    }
}