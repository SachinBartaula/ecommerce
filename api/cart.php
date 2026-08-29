<?php

require_once "../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_customer_session");
    session_start();
}

header("Content-Type: application/json");

$userId = $_SESSION["user_id"] ?? null;

if (!$userId) {
    echo json_encode([
        "success" => false,
        "message" => "You must be logged in.",
        "auth_required" => true
    ]);
    exit;
}


// -------------------------
// Get (or create) the user's cart id
// -------------------------

function getOrCreateCartId($conn, $userId) {

    $sql = "SELECT id FROM cart WHERE user_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row) {
        return (int) $row["id"];
    }

    $insertSql = "INSERT INTO cart (user_id) VALUES (?)";
    $insertStmt = mysqli_prepare($conn, $insertSql);
    mysqli_stmt_bind_param($insertStmt, "i", $userId);
    mysqli_stmt_execute($insertStmt);
    $newId = mysqli_insert_id($conn);
    mysqli_stmt_close($insertStmt);

    return (int) $newId;
}


$method = $_SERVER["REQUEST_METHOD"];
$action = $_GET["action"] ?? ($_POST["action"] ?? "");


// =====================================================
// GET CART ITEMS (also used for the navbar badge count)
// =====================================================

if ($method === "GET" && $action === "list") {

    $cartId = getOrCreateCartId($conn, $userId);

    $sql = "SELECT
                cart_items.id,
                cart_items.quantity,
                products.id AS product_id,
                products.name,
                products.price,
                products.image,
                products.stock
            FROM cart_items
            JOIN products ON cart_items.product_id = products.id
            WHERE cart_items.cart_id = ?
            ORDER BY cart_items.id DESC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $cartId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $items = [];
    $totalCount = 0;
    $totalAmount = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
        $totalCount += (int) $row["quantity"];
        $totalAmount += $row["price"] * $row["quantity"];
    }

    mysqli_stmt_close($stmt);

    echo json_encode([
        "success" => true,
        "data" => $items,
        "total_count" => $totalCount,
        "total_amount" => round($totalAmount, 2)
    ]);
    exit;
}


// =====================================================
// COUNT ONLY (lightweight, for navbar badge)
// =====================================================

if ($method === "GET" && $action === "count") {

    $cartId = getOrCreateCartId($conn, $userId);

    $sql = "SELECT COALESCE(SUM(quantity), 0) AS total
            FROM cart_items WHERE cart_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $cartId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    echo json_encode([
        "success" => true,
        "total_count" => (int) $row["total"]
    ]);
    exit;
}


// =====================================================
// ADD TO CART
// =====================================================

if ($method === "POST" && $action === "add") {

    $productId = (int) ($_POST["product_id"] ?? 0);
    $quantity  = (int) ($_POST["quantity"] ?? 1);

    if ($productId <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid product."]);
        exit;
    }

    if ($quantity <= 0) {
        $quantity = 1;
    }

    // Confirm product exists and has stock
    $checkSql = "SELECT stock FROM products WHERE id = ?";
    $checkStmt = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($checkStmt, "i", $productId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $product = mysqli_fetch_assoc($checkResult);
    mysqli_stmt_close($checkStmt);

    if (!$product) {
        echo json_encode(["success" => false, "message" => "Product not found."]);
        exit;
    }

    if ((int) $product["stock"] <= 0) {
        echo json_encode(["success" => false, "message" => "This product is out of stock."]);
        exit;
    }

    $cartId = getOrCreateCartId($conn, $userId);

    // Does this product already exist in the cart?
    $existsSql = "SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?";
    $existsStmt = mysqli_prepare($conn, $existsSql);
    mysqli_stmt_bind_param($existsStmt, "ii", $cartId, $productId);
    mysqli_stmt_execute($existsStmt);
    $existsResult = mysqli_stmt_get_result($existsStmt);
    $existingItem = mysqli_fetch_assoc($existsResult);
    mysqli_stmt_close($existsStmt);

    if ($existingItem) {

        $newQuantity = min((int) $existingItem["quantity"] + $quantity, (int) $product["stock"]);

        $updateSql = "UPDATE cart_items SET quantity = ? WHERE id = ?";
        $updateStmt = mysqli_prepare($conn, $updateSql);
        mysqli_stmt_bind_param($updateStmt, "ii", $newQuantity, $existingItem["id"]);
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);

    } else {

        $quantity = min($quantity, (int) $product["stock"]);

        $insertSql = "INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)";
        $insertStmt = mysqli_prepare($conn, $insertSql);
        mysqli_stmt_bind_param($insertStmt, "iii", $cartId, $productId, $quantity);
        mysqli_stmt_execute($insertStmt);
        mysqli_stmt_close($insertStmt);
    }

    // Return updated total count for the badge
    $countSql = "SELECT COALESCE(SUM(quantity), 0) AS total FROM cart_items WHERE cart_id = ?";
    $countStmt = mysqli_prepare($conn, $countSql);
    mysqli_stmt_bind_param($countStmt, "i", $cartId);
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $countRow = mysqli_fetch_assoc($countResult);
    mysqli_stmt_close($countStmt);

    echo json_encode([
        "success" => true,
        "message" => "Added to cart.",
        "total_count" => (int) $countRow["total"]
    ]);
    exit;
}


// =====================================================
// UPDATE QUANTITY
// =====================================================

if ($method === "POST" && $action === "update") {

    $itemId   = (int) ($_POST["item_id"] ?? 0);
    $quantity = (int) ($_POST["quantity"] ?? 0);

    if ($itemId <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid item."]);
        exit;
    }

    // Ensure this cart item actually belongs to the logged-in user
    $ownerSql = "SELECT cart_items.id, products.stock
                 FROM cart_items
                 JOIN cart ON cart_items.cart_id = cart.id
                 JOIN products ON cart_items.product_id = products.id
                 WHERE cart_items.id = ? AND cart.user_id = ?";
    $ownerStmt = mysqli_prepare($conn, $ownerSql);
    mysqli_stmt_bind_param($ownerStmt, "ii", $itemId, $userId);
    mysqli_stmt_execute($ownerStmt);
    $ownerResult = mysqli_stmt_get_result($ownerStmt);
    $ownedItem = mysqli_fetch_assoc($ownerResult);
    mysqli_stmt_close($ownerStmt);

    if (!$ownedItem) {
        echo json_encode(["success" => false, "message" => "Item not found."]);
        exit;
    }

    if ($quantity <= 0) {

        $deleteSql = "DELETE FROM cart_items WHERE id = ?";
        $deleteStmt = mysqli_prepare($conn, $deleteSql);
        mysqli_stmt_bind_param($deleteStmt, "i", $itemId);
        mysqli_stmt_execute($deleteStmt);
        mysqli_stmt_close($deleteStmt);

    } else {

        $quantity = min($quantity, (int) $ownedItem["stock"]);

        $updateSql = "UPDATE cart_items SET quantity = ? WHERE id = ?";
        $updateStmt = mysqli_prepare($conn, $updateSql);
        mysqli_stmt_bind_param($updateStmt, "ii", $quantity, $itemId);
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);
    }

    echo json_encode(["success" => true]);
    exit;
}


// =====================================================
// REMOVE ITEM
// =====================================================

if ($method === "POST" && $action === "remove") {

    $itemId = (int) ($_POST["item_id"] ?? 0);

    $sql = "DELETE cart_items FROM cart_items
            JOIN cart ON cart_items.cart_id = cart.id
            WHERE cart_items.id = ? AND cart.user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $itemId, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode(["success" => true]);
    exit;
}


// =====================================================
// INVALID REQUEST
// =====================================================

echo json_encode([
    "success" => false,
    "message" => "Invalid request."
]);