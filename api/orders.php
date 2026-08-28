<?php

require_once __DIR__ . "/../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"]) || ($_SESSION["user_role"] ?? "customer") !== "admin") {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Admin access is required."]);
    exit;
}

$requestMethod = $_SERVER["REQUEST_METHOD"] ?? "GET";
$orderId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($requestMethod === "GET") {
    if ($orderId) {
        $orderStmt = mysqli_prepare($conn, "SELECT orders.id, orders.total_amount, orders.status, orders.shipping_address, orders.created_at, users.name AS customer, users.email, payments.payment_method, payments.transaction_id, payments.status AS payment_status FROM orders INNER JOIN users ON users.id = orders.user_id LEFT JOIN payments ON payments.order_id = orders.id WHERE orders.id = ? LIMIT 1");
        mysqli_stmt_bind_param($orderStmt, "i", $orderId);
        mysqli_stmt_execute($orderStmt);
        $orderResult = mysqli_stmt_get_result($orderStmt);
        $order = mysqli_fetch_assoc($orderResult);
        mysqli_stmt_close($orderStmt);

        if (!$order) {
            echo json_encode(["success" => false, "message" => "Order not found."]);
            exit;
        }

        $itemStmt = mysqli_prepare($conn, "SELECT order_items.quantity, order_items.price, products.name FROM order_items LEFT JOIN products ON products.id = order_items.product_id WHERE order_items.order_id = ? ORDER BY order_items.id ASC");
        mysqli_stmt_bind_param($itemStmt, "i", $orderId);
        mysqli_stmt_execute($itemStmt);
        $itemResult = mysqli_stmt_get_result($itemStmt);
        $order["items"] = [];
        while ($item = mysqli_fetch_assoc($itemResult)) {
            $order["items"][] = $item;
        }
        mysqli_stmt_close($itemStmt);

        echo json_encode(["success" => true, "data" => $order]);
        exit;
    }

    $status = $_GET["status"] ?? "all";
    $search = trim($_GET["search"] ?? "");
    $allowedStatuses = ["all", "pending", "confirmed", "shipped", "delivered", "cancelled"];
    if (!in_array($status, $allowedStatuses, true)) {
        $status = "all";
    }

    $sql = "SELECT orders.id, orders.total_amount, orders.status, orders.shipping_address, orders.created_at, users.name AS customer, users.email FROM orders INNER JOIN users ON users.id = orders.user_id WHERE 1=1";
    $types = "";
    $params = [];
    if ($status !== "all") {
        $sql .= " AND orders.status = ?";
        $types .= "s";
        $params[] = $status;
    }
    if ($search !== "") {
        $sql .= " AND (users.name LIKE ? OR users.email LIKE ? OR CAST(orders.id AS CHAR) LIKE ?)";
        $searchTerm = "%" . $search . "%";
        $types .= "sss";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    $sql .= " ORDER BY orders.created_at DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if ($types !== "") {
        $bindValues = [$types];
        foreach ($params as $key => &$param) {
            $bindValues[] = &$param;
        }
        call_user_func_array([$stmt, "bind_param"], $bindValues);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
    mysqli_stmt_close($stmt);
    echo json_encode(["success" => true, "data" => $orders]);
    exit;
}

if ($requestMethod === "POST" && ($_POST["action"] ?? "") === "update_status") {
    $orderId = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);
    $status = $_POST["status"] ?? "";
    $allowedStatuses = ["pending", "confirmed", "shipped", "delivered", "cancelled"];

    if (!$orderId || !in_array($status, $allowedStatuses, true)) {
        echo json_encode(["success" => false, "message" => "Invalid order status update."]);
        exit;
    }

    $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $status, $orderId);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(["success" => $success, "message" => $success ? "Order status updated." : "Unable to update order status."]);
    exit;
}

http_response_code(405);
echo json_encode(["success" => false, "message" => "Invalid request method."]);