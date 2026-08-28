<?php

require_once "../config/database.php";

header("Content-Type: application/json");

$sql = "SELECT id, name FROM categories ORDER BY name ASC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => mysqli_error($conn)
    ]);
    exit;
}

$categories = [];

while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $categories
]);