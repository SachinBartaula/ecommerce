<?php

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_admin_session");
    session_start();
}

$adminSessionValid = isset($_SESSION["user_id"]) && (
    ($_SESSION["user_role"] ?? "") === "admin" ||
    ($_SESSION["admin_role"] ?? "") === "admin"
);

if (!$adminSessionValid) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

function adminCount($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int) ($row["total"] ?? 0);
}

function formatPaymentMethod($method) {
    $value = strtolower(trim((string) $method));

    if ($value === "cod") {
        return "Cash on Delivery";
    }

    if ($value === "card" || $value === "online_payment" || $value === "onlinepayment" || $value === "esewa" || $value === "khalti" || $value === "imepay") {
        return "Online Payment";
    }

    return "Not recorded";
}

$productCount = adminCount($conn, "SELECT COUNT(*) AS total FROM products");
$customerCount = adminCount($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'customer'");
$orderCount = adminCount($conn, "SELECT COUNT(*) AS total FROM orders");
$lowStockCount = adminCount($conn, "SELECT COUNT(*) AS total FROM products WHERE stock <= 5");

$revenueResult = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders WHERE status <> 'cancelled'");
$revenueRow = $revenueResult ? mysqli_fetch_assoc($revenueResult) : ["total" => 0];
$revenue = (float) ($revenueRow["total"] ?? 0);

$statusResult = mysqli_query($conn, "SELECT status, COUNT(*) AS total FROM orders GROUP BY status ORDER BY total DESC");
$orderStatuses = [];
if ($statusResult) {
    while ($row = mysqli_fetch_assoc($statusResult)) {
        $orderStatuses[] = $row;
    }
}

$paymentStatusResult = mysqli_query($conn, "SELECT payments.status, COUNT(*) AS total FROM payments GROUP BY payments.status ORDER BY total DESC");
$paymentStatuses = [];
if ($paymentStatusResult) {
    while ($row = mysqli_fetch_assoc($paymentStatusResult)) {
        $paymentStatuses[] = $row;
    }
}

$recentOrders = [];
$recentResult = mysqli_query($conn, "SELECT orders.id, orders.total_amount, orders.status, orders.created_at, users.name AS customer, payments.payment_method, payments.status AS payment_status FROM orders INNER JOIN users ON users.id = orders.user_id LEFT JOIN payments ON payments.order_id = orders.id ORDER BY orders.created_at DESC LIMIT 6");
if ($recentResult) {
    while ($row = mysqli_fetch_assoc($recentResult)) {
        $recentOrders[] = $row;
    }
}

$lowStockProducts = [];
$lowStockResult = mysqli_query($conn, "SELECT name, stock FROM products WHERE stock <= 5 ORDER BY stock ASC, name ASC LIMIT 5");
if ($lowStockResult) {
    while ($row = mysqli_fetch_assoc($lowStockResult)) {
        $lowStockProducts[] = $row;
    }
}

$pageTitle = "Admin Dashboard";
require_once "../includes/admin-header.php";
?>

<style>
    .admin-shell { background: #f4f7fb; }
    .admin-sidebar { background: #172033; }
    .admin-card { border: 1px solid #e5eaf1; box-shadow: 0 8px 22px rgba(23, 32, 51, .05); }
    .admin-stat { animation: fadeInUp .5s ease-out both; }
    .admin-stat:nth-child(2) { animation-delay: .06s; }
    .admin-stat:nth-child(3) { animation-delay: .12s; }
    .admin-stat:nth-child(4) { animation-delay: .18s; }
    .status-pill { border-radius: 999px; padding: .25rem .6rem; font-size: .72rem; font-weight: 700; text-transform: capitalize; }
    .status-pending { background: #fff7ed; color: #c2410c; }
    .status-confirmed { background: #eff6ff; color: #1d4ed8; }
    .status-shipped { background: #f5f3ff; color: #6d28d9; }
    .status-delivered { background: #ecfdf5; color: #047857; }
    .status-cancelled { background: #fef2f2; color: #b91c1c; }
    @media (prefers-reduced-motion: reduce) { .admin-stat { animation: none; } }
</style>

<main class="admin-shell min-h-[calc(100vh-5rem)] py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            <aside class="admin-sidebar rounded-2xl p-5 text-white lg:w-60 lg:shrink-0 lg:self-start">
                <p class="text-xs uppercase tracking-[.18em] text-slate-400">Control room</p>
                <h1 class="mt-2 text-2xl font-bold">Admin</h1>
                <nav class="mt-8 space-y-2 text-sm">
                    <a href="index.php" class="flex items-center rounded-lg bg-blue-600 px-3 py-3 font-semibold">Dashboard</a>
                    <a href="products.php" class="flex items-center rounded-lg px-3 py-3 text-slate-300 transition hover:bg-white/10 hover:text-white">Products</a>
                    <a href="inventory.php" class="flex items-center rounded-lg px-3 py-3 text-slate-300 transition hover:bg-white/10 hover:text-white">Inventory</a>
                    <a href="orders.php" class="flex items-center rounded-lg px-3 py-3 text-slate-300 transition hover:bg-white/10 hover:text-white">Orders</a>
                    <a href="../products.php" class="flex items-center rounded-lg px-3 py-3 text-slate-300 transition hover:bg-white/10 hover:text-white">View storefront</a>
                </nav>
            </aside>

            <section class="min-w-0 flex-1">
                <div class="mb-7 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Overview</p>
                        <h2 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Good morning, <?php echo htmlspecialchars($_SESSION["user_name"] ?? "Admin"); ?></h2>
                        <p class="mt-2 text-sm text-slate-500">Here is what is happening across your store.</p>
                    </div>
                    <a href="products.php" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">Add a product <span class="ml-2 text-lg leading-none">+</span></a>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="admin-card admin-stat rounded-xl bg-white p-5"><p class="text-sm text-slate-500">Total revenue</p><p class="mt-2 text-2xl font-bold text-slate-900">$<?php echo number_format($revenue, 2); ?></p><p class="mt-3 text-xs font-semibold text-emerald-600">Excluding cancelled orders</p></div>
                    <div class="admin-card admin-stat rounded-xl bg-white p-5"><p class="text-sm text-slate-500">Orders</p><p class="mt-2 text-2xl font-bold text-slate-900"><?php echo number_format($orderCount); ?></p><p class="mt-3 text-xs font-semibold text-blue-600">All-time orders</p></div>
                    <div class="admin-card admin-stat rounded-xl bg-white p-5"><p class="text-sm text-slate-500">Customers</p><p class="mt-2 text-2xl font-bold text-slate-900"><?php echo number_format($customerCount); ?></p><p class="mt-3 text-xs font-semibold text-slate-500"><?php echo number_format($productCount); ?> products listed</p></div>
                    <div class="admin-card admin-stat rounded-xl bg-white p-5"><p class="text-sm text-slate-500">Low stock</p><p class="mt-2 text-2xl font-bold <?php echo $lowStockCount ? "text-amber-600" : "text-slate-900"; ?>"><?php echo number_format($lowStockCount); ?></p><p class="mt-3 text-xs font-semibold text-amber-600">Products at 5 units or less</p></div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
                    <div class="admin-card rounded-xl bg-white p-6 xl:col-span-2">
                        <div class="flex items-center justify-between"><div><h3 class="text-lg font-bold text-slate-900">Recent orders</h3><p class="mt-1 text-sm text-slate-500">The latest customer activity.</p></div><span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-500">Live</span></div>
                        <?php if (!$recentOrders): ?>
                            <p class="mt-8 rounded-lg bg-slate-50 p-5 text-center text-sm text-slate-500">No orders have been placed yet.</p>
                        <?php else: ?>
                            <div class="mt-5 overflow-hidden"><table class="w-full table-fixed text-left text-xs sm:text-sm"><thead class="border-b border-slate-100 text-[10px] uppercase tracking-wide text-slate-400 sm:text-xs"><tr><th class="w-[12%] pb-3 font-semibold">Order</th><th class="w-[20%] pb-3 font-semibold">Customer</th><th class="w-[26%] pb-3 font-semibold">Payment</th><th class="w-[15%] pb-3 font-semibold">Date</th><th class="w-[13%] pb-3 text-right font-semibold">Total</th><th class="w-[14%] pb-3 text-right font-semibold">Status</th></tr></thead><tbody class="divide-y divide-slate-100"><?php foreach ($recentOrders as $order): ?><tr><td class="py-3 font-semibold text-slate-700">#<?php echo (int) $order["id"]; ?></td><td class="py-3 truncate pr-2 text-slate-600"><?php echo htmlspecialchars($order["customer"]); ?></td><td class="py-3 pr-2"><div class="space-y-1"><p class="truncate text-slate-600"><?php echo htmlspecialchars(formatPaymentMethod($order["payment_method"] ?? "")); ?></p><span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600"><?php echo htmlspecialchars(strtolower((string) ($order["payment_status"] ?? "pending"))); ?></span></div></td><td class="py-3 text-slate-500"><?php echo date("M j, Y", strtotime($order["created_at"])); ?></td><td class="py-3 text-right font-semibold text-slate-700">$<?php echo number_format((float) $order["total_amount"], 2); ?></td><td class="py-3 text-right"><span class="status-pill status-<?php echo htmlspecialchars($order["status"]); ?>"><?php echo htmlspecialchars($order["status"]); ?></span></td></tr><?php endforeach; ?></tbody></table></div>
                        <?php endif; ?>
                    </div>

                    <div class="admin-card rounded-xl bg-white p-6"><h3 class="text-lg font-bold text-slate-900">Order status</h3><p class="mt-1 text-sm text-slate-500">Current distribution.</p><div class="mt-5 space-y-4"><?php if (!$orderStatuses): ?><p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">Nothing to summarize yet.</p><?php else: ?><?php foreach ($orderStatuses as $status): ?><div><div class="mb-1 flex justify-between text-sm"><span class="capitalize text-slate-600"><?php echo htmlspecialchars($status["status"]); ?></span><strong class="text-slate-800"><?php echo (int) $status["total"]; ?></strong></div><div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-500" style="width: <?php echo $orderCount ? min(100, ((int) $status["total"] / $orderCount) * 100) : 0; ?>%"></div></div></div><?php endforeach; ?><?php endif; ?></div></div>
                </div>

                <div class="admin-card mt-6 rounded-xl bg-white p-6"><h3 class="text-lg font-bold text-slate-900">Payment status</h3><p class="mt-1 text-sm text-slate-500">Current payment collection state.</p><div class="mt-5 space-y-4"><?php if (!$paymentStatuses): ?><p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No payment records yet.</p><?php else: ?><?php foreach ($paymentStatuses as $status): ?><div><div class="mb-1 flex justify-between text-sm"><span class="capitalize text-slate-600"><?php echo htmlspecialchars($status["status"] ?? "pending"); ?></span><strong class="text-slate-800"><?php echo (int) $status["total"]; ?></strong></div><div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width: <?php echo $orderCount ? min(100, ((int) $status["total"] / $orderCount) * 100) : 0; ?>%"></div></div></div><?php endforeach; ?><?php endif; ?></div></div>

                <div class="admin-card mt-6 rounded-xl bg-white p-6"><div class="flex items-center justify-between"><div><h3 class="text-lg font-bold text-slate-900">Inventory attention</h3><p class="mt-1 text-sm text-slate-500">Restock these products soon.</p></div><a href="products.php" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Manage products &rarr;</a></div><?php if (!$lowStockProducts): ?><p class="mt-5 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-700">All products have healthy stock levels.</p><?php else: ?><div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5"><?php foreach ($lowStockProducts as $product): ?><div class="rounded-lg border border-slate-100 bg-slate-50 p-4"><p class="truncate text-sm font-semibold text-slate-700"><?php echo htmlspecialchars($product["name"]); ?></p><p class="mt-2 text-xs font-bold <?php echo (int) $product["stock"] === 0 ? "text-red-600" : "text-amber-600"; ?>"><?php echo (int) $product["stock"]; ?> in stock</p></div><?php endforeach; ?></div><?php endif; ?></div>
            </section>
        </div>
    </div>
</main>

<?php require_once "../includes/footer.php"; ?>