<?php
require_once __DIR__ . '/db_class.php';

class ContactRepository {
    public function create(?int $profileId, string $name, string $email, string $company, string $subject, string $message, ?string $ip): void {
        $sql = "
          INSERT INTO contact_messages (profile_id, name, email, company, subject, message, ip)
          VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = DB::get()->prepare($sql);
        $stmt->bind_param('issssss', $profileId, $name, $email, $company, $subject, $message, $ip);
        $stmt->execute();
        $stmt->close();
    }
}
