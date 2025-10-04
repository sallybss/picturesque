<?php
// mysqli with strict errors so issues are obvious
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db(): mysqli {
    // change user/password if yours differ
    $conn = new mysqli('localhost', 'root', '', 'picturesque');
    $conn->set_charset('utf8mb4');
    return $conn;
}