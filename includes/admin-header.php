<?php

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_admin_session");
    session_start();
}

$adminSessionValid = (
    isset($_SESSION["user_id"]) && (
        ($_SESSION["user_role"] ?? "") === "admin" ||
        ($_SESSION["admin_role"] ?? "") === "admin"
    )
) || (
    isset($_SESSION["admin_id"]) && ($_SESSION["admin_role"] ?? "") === "admin"
);

if (($requireAdmin ?? true) && !$adminSessionValid) {
    header("Location: login.php");
    exit;
}

if ($adminSessionValid && empty($_SESSION["admin_id"])) {
    $_SESSION["admin_id"] = $_SESSION["user_id"];
    $_SESSION["admin_name"] = $_SESSION["user_name"] ?? "Admin";
    $_SESSION["admin_role"] = "admin";
}

$adminPage = basename($_SERVER["PHP_SELF"]);
$adminTitle = $pageTitle ?? "Admin";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($adminTitle); ?> - ShopEase Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .admin-header { background: #172033; }
        .admin-nav-link { color: #aeb9ca; transition: color .2s ease, background-color .2s ease; }
        .admin-nav-link:hover, .admin-nav-link.active { color: #fff; background: rgba(255, 255, 255, .1); }
        .admin-menu { max-height: 0; overflow: hidden; transition: max-height .25s ease; }
        .admin-menu.open { max-height: 15rem; }
        @media (prefers-reduced-motion: reduce) { .admin-menu { transition: none; } }
    </style>
</head>

<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="admin-header sticky top-0 z-50 border-b border-white/10 text-white shadow-lg">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="index.php" class="flex items-center gap-3 text-lg font-bold tracking-tight">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-sm">SE</span>
                ShopEase <span class="hidden text-sm font-medium text-slate-400 sm:inline">Admin</span>
            </a>

            <nav class="hidden items-center gap-1 md:flex">
                <a href="index.php" class="admin-nav-link rounded-lg px-4 py-2 text-sm font-semibold <?php echo $adminPage === "index.php" ? "active" : ""; ?>">Dashboard</a>
                <a href="products.php" class="admin-nav-link rounded-lg px-4 py-2 text-sm font-semibold <?php echo $adminPage === "products.php" ? "active" : ""; ?>">Products</a>
                <a href="inventory.php" class="admin-nav-link rounded-lg px-4 py-2 text-sm font-semibold <?php echo $adminPage === "inventory.php" ? "active" : ""; ?>">Inventory</a>
                <a href="categories.php" class="admin-nav-link rounded-lg px-4 py-2 text-sm font-semibold <?php echo $adminPage === "categories.php" ? "active" : ""; ?>">Categories</a>
                <a href="orders.php" class="admin-nav-link rounded-lg px-4 py-2 text-sm font-semibold <?php echo $adminPage === "orders.php" ? "active" : ""; ?>">Orders</a>
                <span class="mx-3 h-6 w-px bg-white/15"></span>
                <a href="../logout.php?role=admin" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-blue-100">Log out</a>
            </nav>

            <button id="admin-menu-button" type="button" class="rounded-lg border border-white/20 px-3 py-2 text-sm font-semibold md:hidden" aria-expanded="false" aria-controls="admin-menu">Menu</button>
        </div>

        <nav id="admin-menu" class="admin-menu border-t border-white/10 md:hidden">
            <div class="mx-auto flex max-w-7xl flex-col px-4 py-3 sm:px-6">
                <a href="index.php" class="admin-nav-link rounded-lg px-3 py-3 text-sm font-semibold <?php echo $adminPage === "index.php" ? "active" : ""; ?>">Dashboard</a>
                <a href="products.php" class="admin-nav-link rounded-lg px-3 py-3 text-sm font-semibold <?php echo $adminPage === "products.php" ? "active" : ""; ?>">Products</a>
                <a href="inventory.php" class="admin-nav-link rounded-lg px-3 py-3 text-sm font-semibold <?php echo $adminPage === "inventory.php" ? "active" : ""; ?>">Inventory</a>
                <a href="categories.php" class="admin-nav-link rounded-lg px-3 py-3 text-sm font-semibold <?php echo $adminPage === "categories.php" ? "active" : ""; ?>">Categories</a>
                <a href="orders.php" class="admin-nav-link rounded-lg px-3 py-3 text-sm font-semibold <?php echo $adminPage === "orders.php" ? "active" : ""; ?>">Orders</a>
                <a href="../products.php" class="admin-nav-link rounded-lg px-3 py-3 text-sm font-semibold">View storefront</a>
                <a href="../logout.php?role=admin" class="mt-2 rounded-lg px-3 py-3 text-sm font-semibold text-red-300 hover:bg-white/10">Log out</a>
            </div>
        </nav>
    </header>

    <script>
        const adminMenuButton = document.getElementById("admin-menu-button");
        const adminMenu = document.getElementById("admin-menu");

        if (adminMenuButton && adminMenu) {
            adminMenuButton.addEventListener("click", () => {
                const isOpen = adminMenu.classList.toggle("open");
                adminMenuButton.setAttribute("aria-expanded", String(isOpen));
            });
        }
    </script>