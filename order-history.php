<?php
require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_customer_session");
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

// ------------------------------------------------------------
// Filters & pagination
// ------------------------------------------------------------
$validStatuses = ["pending", "confirmed", "shipped", "delivered", "cancelled"];
$statusFilter = strtolower(trim($_GET["status"] ?? "all"));
if ($statusFilter !== "all" && !in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = "all";
}

$perPage = 10;
$page = max(1, (int) ($_GET["page"] ?? 1));
$offset = ($page - 1) * $perPage;

// ------------------------------------------------------------
// Count total orders (for pagination) respecting the filter
// ------------------------------------------------------------
if ($statusFilter === "all") {
    $countSql = "SELECT COUNT(*) AS total FROM orders WHERE user_id = ?";
    $countStmt = mysqli_prepare($conn, $countSql);
    mysqli_stmt_bind_param($countStmt, "i", $userId);
} else {
    $countSql = "SELECT COUNT(*) AS total FROM orders WHERE user_id = ? AND status = ?";
    $countStmt = mysqli_prepare($conn, $countSql);
    mysqli_stmt_bind_param($countStmt, "is", $userId, $statusFilter);
}
mysqli_stmt_execute($countStmt);
$totalOrders = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))["total"] ?? 0);
mysqli_stmt_close($countStmt);

$totalPages = max(1, (int) ceil($totalOrders / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// ------------------------------------------------------------
// Also grab a status-count summary for the filter tabs
// ------------------------------------------------------------
$statusCounts = ["all" => 0];
foreach ($validStatuses as $s) {
    $statusCounts[$s] = 0;
}
$summarySql = "SELECT status, COUNT(*) AS cnt FROM orders WHERE user_id = ? GROUP BY status";
$summaryStmt = mysqli_prepare($conn, $summarySql);
mysqli_stmt_bind_param($summaryStmt, "i", $userId);
mysqli_stmt_execute($summaryStmt);
$summaryResult = mysqli_stmt_get_result($summaryStmt);
while ($row = mysqli_fetch_assoc($summaryResult)) {
    $key = strtolower($row["status"]);
    if (isset($statusCounts[$key])) {
        $statusCounts[$key] = (int) $row["cnt"];
    }
    $statusCounts["all"] += (int) $row["cnt"];
}
mysqli_stmt_close($summaryStmt);

// ------------------------------------------------------------
// Fetch the page of orders
// ------------------------------------------------------------
$orders = [];

if ($statusFilter === "all") {
    $orderSql = "SELECT id, total_amount, status, shipping_address, created_at
                 FROM orders
                 WHERE user_id = ?
                 ORDER BY created_at DESC
                 LIMIT ? OFFSET ?";
    $orderStmt = mysqli_prepare($conn, $orderSql);
    mysqli_stmt_bind_param($orderStmt, "iii", $userId, $perPage, $offset);
} else {
    $orderSql = "SELECT id, total_amount, status, shipping_address, created_at
                 FROM orders
                 WHERE user_id = ? AND status = ?
                 ORDER BY created_at DESC
                 LIMIT ? OFFSET ?";
    $orderStmt = mysqli_prepare($conn, $orderSql);
    mysqli_stmt_bind_param($orderStmt, "isii", $userId, $statusFilter, $perPage, $offset);
}
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

$statusStyles = [
    "pending"   => "bg-amber-100 text-amber-700",
    "confirmed" => "bg-blue-100 text-blue-700",
    "shipped"   => "bg-cyan-100 text-cyan-700",
    "delivered" => "bg-emerald-100 text-emerald-700",
    "cancelled" => "bg-red-100 text-red-700",
];

$statusTabs = [
    "all"       => "All Orders",
    "pending"   => "Pending",
    "confirmed" => "Confirmed",
    "shipped"   => "Shipped",
    "delivered" => "Delivered",
    "cancelled" => "Cancelled",
];

function buildQuery($status, $page) {
    $params = [];
    if ($status !== "all") {
        $params["status"] = $status;
    }
    if ($page > 1) {
        $params["page"] = $page;
    }
    $query = http_build_query($params);
    return $query === "" ? "order-history.php" : "order-history.php?" . $query;
}

$pageTitle = "Order History";
require_once "includes/header.php";
?>

<main class="min-h-[calc(100vh-5rem)] py-10">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Account</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Order History</h1>
                <p class="mt-1 text-sm text-slate-500">
                    <?php echo $totalOrders; ?> order<?php echo $totalOrders === 1 ? "" : "s"; ?> total
                </p>
            </div>

            <a href="profile.php" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                &larr; Back to Profile
            </a>
        </div>

        <!-- Status filter tabs -->
        <div class="mb-6 flex flex-wrap gap-2">
            <?php foreach ($statusTabs as $key => $label): ?>
                <a href="<?php echo buildQuery($key, 1); ?>"
                   class="rounded-full px-4 py-2 text-sm font-semibold transition
                          <?php echo $statusFilter === $key
                              ? "bg-blue-600 text-white"
                              : "bg-white text-slate-600 border border-slate-300 hover:bg-slate-50"; ?>">
                    <?php echo htmlspecialchars($label); ?>
                    <span class="ml-1 <?php echo $statusFilter === $key ? "text-blue-100" : "text-slate-400"; ?>">
                        (<?php echo (int) ($statusCounts[$key] ?? 0); ?>)
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($orders)): ?>
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                <p class="text-4xl">📦</p>
                <p class="mt-4 text-lg font-semibold text-slate-700">
                    <?php echo $statusFilter === "all" ? "You haven't placed any orders yet." : "No " . htmlspecialchars($statusFilter) . " orders."; ?>
                </p>
                <a href="products.php" class="mt-4 inline-block font-semibold text-blue-600 hover:underline">
                    Start shopping &rarr;
                </a>
            </div>
        <?php else: ?>

            <div class="space-y-4">
                <?php foreach ($orders as $order):
                    $statusKey = strtolower($order["status"] ?? "pending");
                    $statusClass = $statusStyles[$statusKey] ?? "bg-slate-100 text-slate-700";
                    $statusLabel = ucfirst($statusKey);
                    $orderNumber = "#" . str_pad((string) $order["id"], 5, "0", STR_PAD_LEFT);
                    $itemCount = count($order["items"]);
                ?>
                    <details class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm open:shadow-md">
                        <summary class="flex cursor-pointer list-none flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-base font-bold text-slate-900">Order <?php echo htmlspecialchars($orderNumber); ?></span>
                                <span class="text-sm text-slate-500"><?php echo htmlspecialchars(date("M d, Y \a\t g:i A", strtotime($order["created_at"]))); ?></span>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($statusLabel); ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-slate-500"><?php echo $itemCount; ?> item<?php echo $itemCount === 1 ? "" : "s"; ?></span>
                                <span class="text-lg font-black text-blue-700">Rs. <?php echo number_format((float) $order["total_amount"], 2); ?></span>
                                <span class="text-slate-400 transition-transform group-open:rotate-180">&#9662;</span>
                            </div>
                        </summary>

                        <div class="border-t border-slate-200 bg-slate-50 p-5">
                            <?php if (!empty($order["shipping_address"])): ?>
                                <p class="mb-4 text-sm text-slate-600">
                                    <span class="font-semibold text-slate-700">Shipping to:</span>
                                    <?php echo htmlspecialchars($order["shipping_address"]); ?>
                                </p>
                            <?php endif; ?>

                            <?php if (empty($order["items"])): ?>
                                <p class="text-sm text-slate-500">No item details available for this order.</p>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($order["items"] as $item): ?>
                                        <div class="flex items-center gap-4 rounded-xl bg-white p-3">
                                            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-blue-50">
                                                <?php if (!empty($item["image"])): ?>
                                                    <img src="<?php echo htmlspecialchars($item["image"]); ?>" alt="<?php echo htmlspecialchars($item["name"]); ?>" class="h-full w-full object-cover">
                                                <?php else: ?>
                                                    <div class="flex h-full w-full items-center justify-center text-[10px] font-bold text-blue-300">No image</div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-semibold text-slate-800"><?php echo htmlspecialchars($item["name"]); ?></p>
                                                <p class="text-xs text-slate-500">Qty: <?php echo (int) $item["quantity"]; ?> &times; Rs. <?php echo number_format($item["price"], 2); ?></p>
                                            </div>
                                            <div class="shrink-0 text-sm font-bold text-slate-800">Rs. <?php echo number_format($item["subtotal"], 2); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-8 flex items-center justify-center gap-2">
                    <a href="<?php echo buildQuery($statusFilter, max(1, $page - 1)); ?>"
                       class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 <?php echo $page <= 1 ? "pointer-events-none opacity-40" : ""; ?>">
                        &larr; Prev
                    </a>

                    <span class="px-3 text-sm font-medium text-slate-500">
                        Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                    </span>

                    <a href="<?php echo buildQuery($statusFilter, min($totalPages, $page + 1)); ?>"
                       class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 <?php echo $page >= $totalPages ? "pointer-events-none opacity-40" : ""; ?>">
                        Next &rarr;
                    </a>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</main>

<?php require_once "includes/footer.php"; ?>