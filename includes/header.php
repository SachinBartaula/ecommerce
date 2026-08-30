<?php

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_customer_session");
    session_start();
}

$customerSessionId = $_SESSION["customer_id"] ?? $_SESSION["user_id"] ?? null;
$isLoggedIn = isset($customerSessionId);
$userName   = $_SESSION["customer_name"] ?? $_SESSION["user_name"] ?? "";
$userRole   = $_SESSION["customer_role"] ?? $_SESSION["user_role"] ?? "customer";

// Current script name, used to highlight the active nav link
$currentPage = basename($_SERVER["PHP_SELF"]);

// Build URLs from the application's directory so links also work when the
// project is hosted in a subdirectory (for example, /ecommerce in XAMPP).
$documentRoot = realpath($_SERVER["DOCUMENT_ROOT"] ?? "");
$projectRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . "..");
$basePath = "";

if ($documentRoot && $projectRoot) {
    $documentRoot = str_replace("\\", "/", $documentRoot);
    $projectRoot = str_replace("\\", "/", $projectRoot);

    if (strpos($projectRoot, $documentRoot) === 0) {
        $basePath = substr($projectRoot, strlen($documentRoot));
    }
}

$basePath = rtrim($basePath, "/");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " - MusicPasal" : "MusicPasal"; ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700&family=Fredoka:wght@400;500;600;700&family=Fredoka+One&display=swap" rel="stylesheet">

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
            background: #2563eb;
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
            transition: transform 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-2px);
        }

        .public-page {
            position: relative;
            overflow-x: hidden;
            background: #fafafa;
        }

        .public-page::before,
        .public-page::after {
            content: "";
            position: fixed;
            z-index: -1;
            width: 15rem;
            height: 15rem;
            border: 1.5rem solid rgba(37, 99, 235, .08);
            border-radius: 999px;
            pointer-events: none;
            animation: siteFloat 12s ease-in-out infinite;
        }

        .public-page::before {
            top: 18%;
            left: -7rem;
        }

        .public-page::after {
            right: -7rem;
            bottom: 10%;
            border-color: rgba(6, 182, 212, .08);
            animation-delay: -6s;
        }

        .public-page main {
            position: relative;
            z-index: 1;
            animation: siteEnter .45s ease-out both;
        }

        .music-brand {
            font-family: 'Fredoka One', sans-serif;
            letter-spacing: -0.5px;
        }

        .store-name {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .public-page main .bg-white,
        .public-page main [class*="rounded-2xl"].bg-white,
        .public-page main [class*="rounded-xl"].bg-white,
        .public-page main [class*="rounded-lg"].bg-white,
        .public-page main .featured-product-card,
        .public-page main .product-card,
        .public-page main .cart-row,
        .public-page main .reveal {
            border: 1px solid rgba(226, 232, 240, .7);
            box-shadow: 0 8px 18px rgba(15, 23, 42, .04);
        }

        @keyframes siteFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-22px) rotate(10deg); }
        }

        @keyframes siteEnter {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (prefers-reduced-motion: reduce) {
            .public-page::before,
            .public-page::after,
            .public-page main {
                animation: none;
            }
        }

    </style>
</head>

<body class="public-page min-h-screen flex flex-col">

    <!-- ==========================================
         NAVBAR
    =========================================== -->
    <header id="site-nav" class="bg-white/90 backdrop-blur sticky top-0 z-50 shadow-sm">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">

            <a href="<?php echo $basePath; ?>/index.php" class="text-xl font-bold text-blue-700 flex items-center gap-2 group">
                <span class="inline-block text-2xl transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-12">🎵</span>
                <span class="store-name">MusicPasal</span>
            </a>

            <!-- Desktop nav -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-gray-600">
                  <?php if ($isLoggedIn): ?>

                    <?php if ($userRole === 'admin'): ?>
                        <a href="<?php echo $basePath; ?>/admin/index.php" class="nav-link hover:text-blue-600">
                            Admin
                        </a>
                    <?php endif; ?>

                    <a href="<?php echo $basePath; ?>/profile.php"
                        class="nav-link text-gray-600 hover:text-blue-600 <?php echo $currentPage === 'profile.php' ? 'text-blue-600 active' : ''; ?>">
                        👤Hi, <?php echo htmlspecialchars($userName); ?>
                    </a>

                    

                <a href="<?php echo $basePath; ?>/index.php"
                    class="nav-link <?php echo $currentPage === 'index.php' ? 'text-blue-600 active' : 'hover:text-blue-600'; ?>">
                    Home
                </a>

                <a href="<?php echo $basePath; ?>/products.php"
                    class="nav-link <?php echo $currentPage === 'products.php' ? 'text-blue-600 active' : 'hover:text-blue-600'; ?>">
                    Products
                </a>

                <a href="<?php echo $basePath; ?>/cart.php"
                    class="nav-link relative <?php echo $currentPage === 'cart.php' ? 'text-blue-600 active' : 'hover:text-blue-600'; ?>">
                    🛒Cart
                    <span
                        id="cart-badge"
                        class="hidden absolute -top-2 -right-3 bg-blue-600 text-white text-[10px] font-bold w-4 h-4 rounded-full items-center justify-center">
                        0
                    </span>
                </a>

                    <a href="<?php echo $basePath; ?>/logout.php?role=customer"
                        class="bg-blue-700 text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition-transform duration-200 hover:scale-105">
                        Logout
                    </a>

                <?php else: ?>

                    <a href="<?php echo $basePath; ?>/login.php"
                        class="nav-link <?php echo $currentPage === 'login.php' ? 'text-blue-600 active' : 'hover:text-blue-600'; ?>">
                        Login
                    </a>

                    <a href="<?php echo $basePath; ?>/register.php"
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

                <a href="<?php echo $basePath; ?>/index.php"
                    class="py-3 border-b border-gray-100 <?php echo $currentPage === 'index.php' ? 'text-blue-600' : ''; ?>">
                    Home
                </a>

                <a href="<?php echo $basePath; ?>/products.php"
                    class="py-3 border-b border-gray-100 <?php echo $currentPage === 'products.php' ? 'text-blue-600' : ''; ?>">
                    Products
                </a>

                <a href="<?php echo $basePath; ?>/cart.php"
                    class="py-3 border-b border-gray-100 flex items-center justify-between <?php echo $currentPage === 'cart.php' ? 'text-blue-600' : ''; ?>">
                    <span>Cart</span>
                    <span
                        id="cart-badge-mobile"
                        class="hidden bg-blue-600 text-white text-xs font-bold w-5 h-5 rounded-full items-center justify-center">
                        0
                    </span>
                </a>

                <?php if ($isLoggedIn): ?>

                    <?php if ($userRole === 'admin'): ?>
                        <a href="<?php echo $basePath; ?>/admin/index.php" class="py-3 border-b border-gray-100">
                            Admin
                        </a>
                    <?php endif; ?>

                    <a href="<?php echo $basePath; ?>/profile.php" class="py-3 border-b border-gray-100 text-gray-600 <?php echo $currentPage === 'profile.php' ? 'text-blue-600' : ''; ?>">
                        Hi, <?php echo htmlspecialchars($userName); ?>
                    </a>

                    <a href="<?php echo $basePath; ?>/profile.php" class="py-3 border-b border-gray-100 <?php echo $currentPage === 'profile.php' ? 'text-blue-600' : ''; ?>">
                        Profile
                    </a>

                    <a href="<?php echo $basePath; ?>/logout.php?role=customer" class="py-3 text-red-600 font-semibold">
                        Logout
                    </a>

                <?php else: ?>

                    <a href="<?php echo $basePath; ?>/login.php"
                        class="py-3 border-b border-gray-100 <?php echo $currentPage === 'login.php' ? 'text-blue-600' : ''; ?>">
                        Login
                    </a>

                    <a href="<?php echo $basePath; ?>/register.php" class="py-3 text-blue-600 font-semibold">
                        Register
                    </a>

                <?php endif; ?>

            </div>
        </nav>
    </header>

    <main class="flex-1">