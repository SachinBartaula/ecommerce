<?php

require_once __DIR__ . "/../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_admin_session");
    session_start();
}

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"]) || ($_SESSION["user_role"] ?? "customer") !== "admin") {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Admin access is required."]);
    exit;
}

$requestMethod = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($requestMethod === "GET") {
    $sql = "SELECT products.id, products.name, products.stock, products.image, categories.name AS category FROM products LEFT JOIN categories ON categories.id = products.category_id ORDER BY products.stock ASC, products.name ASC";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
        exit;
    }

    $inventory = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $inventory[] = $row;
    }

    echo json_encode(["success" => true, "data" => $inventory]);
    exit;
}

if ($requestMethod === "POST") {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    $mode = $_POST["mode"] ?? "set";
    $quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT);

    if (!$id || !in_array($mode, ["set", "add", "remove"], true) || $quantity === false) {
        echo json_encode(["success" => false, "message" => "Invalid stock update request."]);
        exit;
    }

    $currentStmt = mysqli_prepare($conn, "SELECT stock FROM products WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($currentStmt, "i", $id);
    mysqli_stmt_execute($currentStmt);
    $currentResult = mysqli_stmt_get_result($currentStmt);
    $currentProduct = mysqli_fetch_assoc($currentResult);
    mysqli_stmt_close($currentStmt);

    if (!$currentProduct) {
        echo json_encode(["success" => false, "message" => "Product not found."]);
        exit;
    }

    $currentStock = (int) ($currentProduct["stock"] ?? 0);

    if ($mode === "set") {
        $newStock = max(0, $quantity);
    } elseif ($mode === "add") {
        $newStock = max(0, $currentStock + $quantity);
    } else {
        $newStock = max(0, $currentStock - $quantity);
    }

    $updateStmt = mysqli_prepare($conn, "UPDATE products SET stock = ? WHERE id = ?");
    mysqli_stmt_bind_param($updateStmt, "ii", $newStock, $id);
    $success = mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);

    echo json_encode([
        "success" => $success,
        "message" => $success ? "Stock updated successfully." : "Unable to update stock."
    ]);
    exit;
}

http_response_code(405);
echo json_encode(["success" => false, "message" => "Invalid request method."]);
