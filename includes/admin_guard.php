<?php
function require_admin(mysqli $conn, int $me) {
  $stmt = $conn->prepare("SELECT role FROM profiles WHERE profile_id=?");
  $stmt->bind_param('i', $me);
  $stmt->execute();
  $role = $stmt->get_result()->fetch_column();
  $stmt->close();

  if ($role !== 'admin') {
    http_response_code(403);
    echo "Forbidden: Admins only.";
    exit;
  }
}
