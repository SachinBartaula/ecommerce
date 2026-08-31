<?php
// Buffer all output so stray notices/warnings can never corrupt the JSON response.
ob_start();
error_reporting(E_ALL);
ini_set("display_errors", "0"); // don't print errors into the response body

require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_customer_session");
    session_start();
}

// Discard anything that may have leaked out (notices, whitespace, BOM, etc.)
ob_clean();
header("Content-Type: application/json");

function respond($payload, $httpCode = 200) {
    // Clean any buffered output one more time before sending the real payload
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($httpCode);
    echo json_encode($payload);
    exit;
}

if (!isset($_SESSION["user_id"])) {
    respond(["error" => "Not authenticated."], 401);
}

$userId = (int) $_SESSION["user_id"];

try {
    // ------------------------------------------------------------
    // Assumes tables:
    //   orders (id, user_id, total_amount, status, created_at, ...)
    //   order_items (id, order_id, product_id, quantity, price)
    //   products (id, name, image, ...)
    // Adjust column/table names below if your schema differs.
    // ------------------------------------------------------------

    $orders = [];

    $orderSql = "SELECT id, total_amount, status, created_at
             FROM orders
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT 5";
    $orderStmt = mysqli_prepare($conn, $orderSql);

    if (!$orderStmt) {
        throw new Exception("Prepare failed (orders): " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($orderStmt, "i", $userId);
    mysqli_stmt_execute($orderStmt);
    $orderResult = mysqli_stmt_get_result($orderStmt);

    while ($orderRow = mysqli_fetch_assoc($orderResult)) {
        $orderRow["items"] = [];
        $orders[$orderRow["id"]] = $orderRow;
    }
    mysqli_stmt_close($orderStmt);

    if (!empty($orders)) {
        $orderIds = array_keys($orders);
        $placeholders = implode(",", array_fill(0, count($orderIds), "?"));
        $types = str_repeat("i", count($orderIds));

        $itemSql = "SELECT order_items.order_id,
                           order_items.quantity,
                           order_items.price,
                           products.name AS product_name,
                           products.image AS product_image
                    FROM order_items
                    LEFT JOIN products ON products.id = order_items.product_id
                    WHERE order_items.order_id IN ($placeholders)";
        $itemStmt = mysqli_prepare($conn, $itemSql);

        if (!$itemStmt) {
            throw new Exception("Prepare failed (order_items): " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($itemStmt, $types, ...$orderIds);
        mysqli_stmt_execute($itemStmt);
        $itemResult = mysqli_stmt_get_result($itemStmt);

        while ($itemRow = mysqli_fetch_assoc($itemResult)) {
            $orders[$itemRow["order_id"]]["items"][] = [
                "name"     => $itemRow["product_name"] ?? "Product no longer available",
                "image"    => $itemRow["product_image"] ?? null,
                "quantity" => (int) $itemRow["quantity"],
                "price"    => (float) $itemRow["price"],
                "subtotal" => (float) $itemRow["quantity"] * (float) $itemRow["price"],
            ];
        }
        mysqli_stmt_close($itemStmt);
    }

    // Shape the final payload: pre-format the display-ready fields so
    // the frontend just renders, it doesn't reformat currency/dates.
    $payload = [];
    foreach ($orders as $order) {
        $payload[] = [
            "id"          => (int) $order["id"],
            "orderNumber" => "#" . str_pad((string) $order["id"], 5, "0", STR_PAD_LEFT),
            "date"        => date("M d, Y", strtotime($order["created_at"])),
            "status"      => strtolower($order["status"] ?? "pending"),
            "total"       => number_format((float) $order["total_amount"], 2),
            "itemCount"   => count($order["items"]),
            "items"       => array_map(function ($item) {
                return [
                    "name"     => $item["name"],
                    "image"    => $item["image"],
                    "quantity" => $item["quantity"],
                    "price"    => number_format($item["price"], 2),
                    "subtotal" => number_format($item["subtotal"], 2),
                ];
            }, $order["items"]),
        ];
    }

    respond(["orders" => $payload]);

} catch (Throwable $e) {
    // Log the real error server-side; never leak it into the JSON body.
    error_log("order-history-data.php error: " . $e->getMessage());
    respond(["error" => "Unable to load orders."], 500);
}