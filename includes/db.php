<?php
// mysqli with strict errors so issues are obvious
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db(): mysqli {
    $conn = new mysqli('localhost','root','', 'picturesque_1');
    $conn->set_charset('utf8mb4');
    return $conn;
}
