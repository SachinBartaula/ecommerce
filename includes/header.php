<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION["user_id"]);
$userName   = $_SESSION["user_name"] ?? "";
$userRole   = $_SESSION["user_role"] ?? "customer";

// Current script name, used to highlight the active nav link
$currentPage = basename($_SERVER["PHP_SELF"]);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " - ShopEase" : "ShopEase"; ?></title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* ============ ANIMATION UTILITIES ============ */

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        @keyframes floatBlob {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50%      { transform: translate(20px, -25px) scale(1.08); }
        }

        @keyframes shimmer {
            0%   { background-position: -400px 0; }
            100% { background-position: 400px 0; }
        }

        @keyframes pop {
            0%   { transform: scale(0.9); opacity: 0; }
            60%  { transform: scale(1.03); opacity: 1; }
            100% { transform: scale(1); }
        }

        .animate-fade-up {
            opacity: 0;
            animation: fadeInUp 0.7s ease-out forwards;
        }

        .animate-fade {
            opacity: 0;
            animation: fadeIn 0.6s ease-out forwards;
        }

        .animate-blob {
            animation: floatBlob 8s ease-in-out infinite;
        }

        .animate-pop {
            animation: pop 0.35s ease-out;
        }

        .shimmer-badge {
            background: linear-gradient(90deg, #2563eb 0%, #60a5fa 50%, #2563eb 100%);
            background-size: 800px 100%;
            animation: shimmer 2.5s linear infinite;
        }

        /* Scroll-reveal: hidden until JS adds .is-visible */
        .reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
        }

        .reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        #site-nav {
            transition: box-shadow 0.25s ease, background-color 0.25s ease;
        }

        .nav-link {
            position: relative;
        }

        .nav-link::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: -4px;
            width: 0;
            height: 2px;
            background: #2563eb;
            transition: width 0.25s ease;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }

        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 30px -12px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">

    <!-- ==========================================
         NAVBAR
    =========================================== -->
    <header id="site-nav" class="bg-white/90 backdrop-blur sticky top-0 z-50 shadow-sm">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">

            <a href="/index.php" class="text-xl font-bold text-gray-800 flex items-center gap-1 group">
                <span class="inline-block transition-transform duration-300 group-hover:rotate-12">🛍️</span>
                ShopEase
            </a>

            <!-- Desktop nav -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-gray-600">

                <a href="/index.php"
                    class="nav-link <?php echo $currentPage === 'index.php' ? 'text-blue-600 active' : 'hover:text-blue-600'; ?>">
                    Home
                </a>

                <a href="/products.php"
                    class="nav-link <?php echo $currentPage === 'products.php' ? 'text-blue-600 active' : 'hover:text-blue-600'; ?>">
                    Products
                </a>

                <a href="/cart.php"
                    class="nav-link <?php echo $currentPage === 'cart.php' ? 'text-blue-600 active' : 'hover:text-blue-600'; ?>">
                    Cart
                </a>

                <?php if ($isLoggedIn): ?>

                    <?php if ($userRole === 'admin'): ?>
                        <a href="/admin/products.php" class="nav-link hover:text-blue-600">
                            Admin
                        </a>
                    <?php endif; ?>

                    <span class="text-gray-400">
                        Hi, <?php echo htmlspecialchars($userName); ?>
                    </span>

                    <a href="/logout.php"
                        class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition-transform duration-200 hover:scale-105">
                        Logout
                    </a>

                <?php else: ?>

                    <a href="/login.php"
                        class="nav-link <?php echo $currentPage === 'login.php' ? 'text-blue-600 active' : 'hover:text-blue-600'; ?>">
                        Login
                    </a>

                    <a href="/register.php"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-transform duration-200 hover:scale-105">
                        Register
                    </a>

                <?php endif; ?>

            </nav>

            <!-- Mobile hamburger button -->
            <button
                id="mobile-menu-btn"
                class="md:hidden flex flex-col justify-center items-center gap-1.5 w-9 h-9"
                aria-label="Toggle menu"
                aria-expanded="false">
                <span class="hamburger-line block w-6 h-0.5 bg-gray-800 transition-all duration-300"></span>
                <span class="hamburger-line block w-6 h-0.5 bg-gray-800 transition-all duration-300"></span>
                <span class="hamburger-line block w-6 h-0.5 bg-gray-800 transition-all duration-300"></span>
            </button>

        </div>

        <!-- Mobile nav panel -->
        <nav
            id="mobile-menu"
            class="md:hidden max-h-0 overflow-hidden transition-all duration-300 ease-in-out bg-white border-t">
            <div class="flex flex-col px-6 py-2 text-sm font-medium text-gray-600">

                <a href="/index.php"
                    class="py-3 border-b border-gray-100 <?php echo $currentPage === 'index.php' ? 'text-blue-600' : ''; ?>">
                    Home
                </a>

                <a href="/products.php"
                    class="py-3 border-b border-gray-100 <?php echo $currentPage === 'products.php' ? 'text-blue-600' : ''; ?>">
                    Products
                </a>

                <a href="/cart.php"
                    class="py-3 border-b border-gray-100 <?php echo $currentPage === 'cart.php' ? 'text-blue-600' : ''; ?>">
                    Cart
                </a>

                <?php if ($isLoggedIn): ?>

                    <?php if ($userRole === 'admin'): ?>
                        <a href="/admin/products.php" class="py-3 border-b border-gray-100">
                            Admin
                        </a>
                    <?php endif; ?>

                    <span class="py-3 border-b border-gray-100 text-gray-400">
                        Hi, <?php echo htmlspecialchars($userName); ?>
                    </span>

                    <a href="/logout.php" class="py-3 text-red-600 font-semibold">
                        Logout
                    </a>

                <?php else: ?>

                    <a href="/login.php"
                        class="py-3 border-b border-gray-100 <?php echo $currentPage === 'login.php' ? 'text-blue-600' : ''; ?>">
                        Login
                    </a>

                    <a href="/register.php" class="py-3 text-blue-600 font-semibold">
                        Register
                    </a>

                <?php endif; ?>

            </div>
        </nav>
    </header>

    <main class="flex-1">