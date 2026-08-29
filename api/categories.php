<?php

require_once __DIR__ . "/../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json");

$requestMethod = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($requestMethod !== "GET" &&
    (!isset($_SESSION["user_id"]) || ($_SESSION["user_role"] ?? "customer") !== "admin")) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Admin access is required."]);
    exit;
}

if ($requestMethod === "DELETE") {
    $categoryId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

    if (!$categoryId) {
        echo json_encode(["success" => false, "message" => "A valid category ID is required."]);
        exit;
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM categories WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $categoryId);
    mysqli_stmt_execute($stmt);
    $deleted = mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    echo json_encode([
        "success" => $deleted,
        "message" => $deleted ? "Category deleted successfully." : "Category not found."
    ]);
    exit;
}

if ($requestMethod === "POST") {
    $action = $_POST["action"] ?? "create";
    $name = trim($_POST["name"] ?? "");
    $categoryId = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);

    $namePattern = "/^[A-Za-z][A-Za-z0-9\s&'().\/-]*$/";
    $repeatedSpecial = "/([&'().\/-])\\1+/";

    if ($name === "") {
        echo json_encode(["success" => false, "message" => "Category name is required."]);
        exit;
    }
    if (strlen($name) < 3) {
        echo json_encode(["success" => false, "message" => "Category name must be at least 3 characters."]);
        exit;
    }
    if (strlen($name) > 100) {
        echo json_encode(["success" => false, "message" => "Category name cannot exceed 100 characters."]);
        exit;
    }
    if (!preg_match($namePattern, $name)) {
        echo json_encode(["success" => false, "message" => "Category name must start with a letter and contain only valid characters."]);
        exit;
    }
    if (!preg_match("/[A-Za-z]/", $name)) {
        echo json_encode(["success" => false, "message" => "Category name must contain at least one letter."]);
        exit;
    }
    if (preg_match("/\s{2,}/", $name)) {
        echo json_encode(["success" => false, "message" => "Category name cannot contain multiple spaces."]);
        exit;
    }
    if (preg_match($repeatedSpecial, $name)) {
        echo json_encode(["success" => false, "message" => "Category name contains repeated special characters."]);
        exit;
    }

    $duplicateSql = "SELECT id FROM categories WHERE name = ?";
    if ($action === "update") {
        $duplicateSql .= " AND id <> ?";
    }
    $duplicateSql .= " LIMIT 1";
    $duplicateStmt = mysqli_prepare($conn, $duplicateSql);
    if ($action === "update") {
        mysqli_stmt_bind_param($duplicateStmt, "si", $name, $categoryId);
    } else {
        mysqli_stmt_bind_param($duplicateStmt, "s", $name);
    }
    mysqli_stmt_execute($duplicateStmt);
    mysqli_stmt_store_result($duplicateStmt);
    $duplicate = mysqli_stmt_num_rows($duplicateStmt) > 0;
    mysqli_stmt_close($duplicateStmt);

    if ($duplicate) {
        echo json_encode(["success" => false, "message" => "A category with this name already exists."]);
        exit;
    }

    if ($action === "update") {
        if (!$categoryId) {
            echo json_encode(["success" => false, "message" => "A valid category ID is required."]);
            exit;
        }

        $existsStmt = mysqli_prepare($conn, "SELECT id FROM categories WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($existsStmt, "i", $categoryId);
        mysqli_stmt_execute($existsStmt);
        mysqli_stmt_store_result($existsStmt);
        $categoryExists = mysqli_stmt_num_rows($existsStmt) > 0;
        mysqli_stmt_close($existsStmt);

        if (!$categoryExists) {
            echo json_encode(["success" => false, "message" => "Category not found."]);
            exit;
        }

        $stmt = mysqli_prepare($conn, "UPDATE categories SET name = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $name, $categoryId);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(["success" => $success, "message" => $success ? "Category updated successfully." : "Failed to update category."]);
        exit;
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO categories (name) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $name);
    $success = mysqli_stmt_execute($stmt);
    $newId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    echo json_encode(["success" => $success, "message" => $success ? "Category created successfully." : "Failed to create category.", "category_id" => $newId]);
    exit;
}

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