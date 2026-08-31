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
    <title><?php echo htmlspecialchars($adminTitle); ?> - MusicPasal Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700&family=Fredoka:wght@400;500;600;700&family=Fredoka+One&display=swap" rel="stylesheet">
    <style>
        body {
            background:
                radial-gradient(circle at 15% 0%, rgba(148, 163, 184, 0.25), transparent 45%),
                radial-gradient(circle at 85% 10%, rgba(100, 116, 139, 0.2), transparent 40%),
                #e2e5eb;
        }

        .admin-header {
            background: rgba(30, 35, 46, 0.55);
            backdrop-filter: blur(18px) saturate(140%);
            -webkit-backdrop-filter: blur(18px) saturate(140%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 8px 32px rgba(15, 23, 42, 0.18);
        }

        .admin-nav-link {
            color: #d1d5db;
            transition: color .2s ease, background-color .2s ease, box-shadow .2s ease;
        }

        .admin-nav-link:hover,
        .admin-nav-link.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(6px);
        }

        .admin-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height .25s ease;
            background: rgba(30, 35, 46, 0.6);
            backdrop-filter: blur(18px) saturate(140%);
            -webkit-backdrop-filter: blur(18px) saturate(140%);
        }

        .admin-menu.open { max-height: 20rem; }

        #admin-menu-button {
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(6px);
            transition: background-color .2s ease;
        }

        #admin-menu-button:hover { background: rgba(255, 255, 255, 0.14); }

        .store-name { font-family: 'Space Grotesk', sans-serif; font-weight: 700; letter-spacing: -0.025em; }
        .music-logo { font-family: 'Fredoka One', sans-serif; font-size: 0.875rem; font-weight: 700; letter-spacing: 0.025em; }

        .admin-logout-btn {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(6px);
        }
        .admin-logout-btn:hover { background: #fff; }

        @media (prefers-reduced-motion: reduce) {
            .admin-menu, .admin-nav-link, #admin-menu-button { transition: none; }
        }
    </style>
</head>

<body class="min-h-screen text-slate-900">
    <?php if (empty($hideAdminNav)): ?>
    <header class="admin-header sticky top-0 z-50 text-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 sm:py-4 lg:px-8">
            <a href="index.php" class="flex items-center gap-2 text-base font-bold tracking-tight sm:gap-3 sm:text-lg">
                <span class="store-name">MusicPasal</span> <span class="hidden text-sm font-medium text-slate-400 sm:inline">Admin</span>
            </a>

            <nav class="hidden items-center gap-1 md:flex lg:gap-1.5">
                <a href="index.php" class="admin-nav-link rounded-lg px-3 py-2 text-sm font-semibold lg:px-4 <?php echo $adminPage === "index.php" ? "active" : ""; ?>">Dashboard</a>
                <a href="inventory.php" class="admin-nav-link rounded-lg px-3 py-2 text-sm font-semibold lg:px-4 <?php echo $adminPage === "inventory.php" ? "active" : ""; ?>">Inventory</a>
                <a href="categories.php" class="admin-nav-link rounded-lg px-3 py-2 text-sm font-semibold lg:px-4 <?php echo $adminPage === "categories.php" ? "active" : ""; ?>">Categories</a>
                <a href="customers.php" class="admin-nav-link rounded-lg px-3 py-2 text-sm font-semibold lg:px-4 <?php echo $adminPage === "customers.php" ? "active" : ""; ?>">Customers</a>
                <a href="orders.php" class="admin-nav-link rounded-lg px-3 py-2 text-sm font-semibold lg:px-4 <?php echo $adminPage === "orders.php" ? "active" : ""; ?>">Orders</a>
                <span class="mx-2 h-6 w-px bg-white/15 lg:mx-3"></span>
                <a href="../logout.php?role=admin" class="admin-logout-btn rounded-lg px-4 py-2 text-sm font-semibold text-slate-900 transition">Log out</a>
            </nav>

            <button id="admin-menu-button" type="button" class="rounded-lg border border-white/20 px-3 py-2 text-sm font-semibold md:hidden" aria-expanded="false" aria-controls="admin-menu">Menu</button>
        </div>

        <nav id="admin-menu" class="admin-menu border-t border-white/10 md:hidden">
            <div class="mx-auto flex max-w-7xl flex-col px-4 py-3 sm:px-6">
                <a href="index.php" class="admin-nav-link rounded-lg px-3 py-3 text-sm font-semibold <?php echo $adminPage === "index.php" ? "active" : ""; ?>">Dashboard</a>
                <a href="products.php" class="admin-nav-link rounded-lg px-3 py-3 text-sm font-semibold <?php echo $adminPage === "products.php" ? "active" : ""; ?>">Products</a>
                <a href="inventory.php" class="admin-nav-link rounded-lg px-3 py-3 text-sm font-semibold <?php echo $adminPage === "inventory.php" ? "active" : ""; ?>">Inventory</a>
                <a href="categories.php" class="admin-nav-link rounded-lg px-3 py-3 text-sm font-semibold <?php echo $adminPage === "categories.php" ? "active" : ""; ?>">Categories</a>
                <a href="customers.php" class="admin-nav-link rounded-lg px-3 py-3 text-sm font-semibold <?php echo $adminPage === "customers.php" ? "active" : ""; ?>">Customers</a>
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
    <?php endif; ?>