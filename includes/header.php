<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_customer_session");
    session_start();
}
$customerSessionId = $_SESSION["customer_id"] ?? $_SESSION["user_id"] ?? null;
$isLoggedIn = isset($customerSessionId);
$userName = $_SESSION["customer_name"] ?? $_SESSION["user_name"] ?? "";
$userRole = $_SESSION["customer_role"] ?? $_SESSION["user_role"] ?? "customer";
$currentPage = basename($_SERVER["PHP_SELF"]);

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
@keyframes fadeInUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes floatBlob{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(20px,-25px) scale(1.08)}}
@keyframes shimmer{0%{background-position:-400px 0}100%{background-position:400px 0}}
@keyframes pop{0%{transform:scale(.9);opacity:0}60%{transform:scale(1.03);opacity:1}100%{transform:scale(1)}}
.animate-fade-up{opacity:0;animation:fadeInUp .7s ease-out forwards}.animate-fade{opacity:0;animation:fadeIn .6s ease-out forwards}.animate-blob{animation:floatBlob 8s ease-in-out infinite}.animate-pop{animation:pop .35s ease-out}.shimmer-badge{background:#2563eb;background-size:800px 100%;animation:shimmer 2.5s linear infinite}
.reveal{opacity:0;transform:translateY(20px);transition:opacity .6s ease-out,transform .6s ease-out}.reveal.is-visible{opacity:1;transform:translateY(0)}
#site-nav{transition:box-shadow .25s ease,background-color .25s ease}.nav-link{position:relative}.nav-link::after{content:"";position:absolute;left:0;bottom:-4px;width:0;height:2px;background:#2563eb;transition:width .25s ease}.nav-link:hover::after,.nav-link.active::after{width:100%}
.card-hover{transition:transform .3s ease}.card-hover:hover{transform:translateY(-2px)}
.public-page{position:relative;overflow-x:hidden;background:#fafafa}.public-page main{position:relative;z-index:1;animation:siteEnter .45s ease-out both}
.store-name{font-family:'Space Grotesk',sans-serif;font-weight:700;letter-spacing:-.025em}
@keyframes siteEnter{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
</style>
</head>
<body class="public-page min-h-screen flex flex-col">

<header id="site-nav" class="bg-white/90 backdrop-blur sticky top-0 z-50 shadow-sm">
<div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
<a href="<?php echo $basePath; ?>/index.php" class="text-xl font-bold text-blue-700 flex items-center gap-2 group">
<span class="inline-block text-2xl transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-12">🎵</span>
<span class="store-name">MusicPasal</span>
</a>

<nav class="hidden md:flex items-center gap-6 text-sm font-medium text-gray-600">
<?php if ($isLoggedIn): ?>

<?php if ($userRole === "admin"): ?>
<a href="<?php echo $basePath; ?>/admin/index.php" class="nav-link hover:text-blue-600">Admin</a>
<?php endif; ?>

<a href="<?php echo $basePath; ?>/index.php" class="nav-link <?php echo $currentPage==="index.php"?"text-blue-600 active":"hover:text-blue-600"; ?>">Home</a>
<a href="<?php echo $basePath; ?>/products.php" class="nav-link <?php echo $currentPage==="products.php"?"text-blue-600 active":"hover:text-blue-600"; ?>">Products</a>

<a href="<?php echo $basePath; ?>/cart.php" class="nav-link relative <?php echo $currentPage==="cart.php"?"text-blue-600 active":"hover:text-blue-600"; ?>">
🛒 Cart
<span id="cart-badge" class="hidden absolute -top-2 -right-3 bg-blue-600 text-white text-[10px] font-bold w-4 h-4 rounded-full items-center justify-center">0</span>
</a>

<!-- User dropdown: Profile, Settings and Order History only -->
<div class="relative" id="user-dropdown-wrapper">
<button type="button" id="user-dropdown-btn" class="flex items-center gap-1.5 text-gray-600 hover:text-blue-600 transition focus:outline-none" aria-expanded="false" aria-haspopup="true">
<span>Hi, <?php echo htmlspecialchars($userName); ?></span>
<svg id="user-dropdown-arrow" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
</svg>
</button>

<div id="user-dropdown-menu" class="hidden absolute right-0 mt-3 w-52 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-[60]" role="menu">
<a href="<?php echo $basePath; ?>/profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition" role="menuitem">👤 <span>Profile</span></a>
<a href="<?php echo $basePath; ?>/settings.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition" role="menuitem">⚙️ <span>Settings</span></a>
<a href="<?php echo $basePath; ?>/order-history.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition" role="menuitem">📦 <span>Order History</span></a>
</div>
</div>

<!-- Logout stays separate -->
<a href="<?php echo $basePath; ?>/logout.php?role=customer" class="bg-blue-700 text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition-transform duration-200 hover:scale-105">Logout</a>

<?php else: ?>

<a href="<?php echo $basePath; ?>/about.php" class="nav-link <?php echo $currentPage==="about.php"?"text-blue-600 active":"hover:text-blue-600"; ?>">About</a>
<a href="<?php echo $basePath; ?>/login.php" class="nav-link <?php echo $currentPage==="login.php"?"text-blue-600 active":"hover:text-blue-600"; ?>">Login</a>
<a href="<?php echo $basePath; ?>/register.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-transform duration-200 hover:scale-105">Register</a>

<?php endif; ?>
</nav>

<button id="mobile-menu-btn" class="md:hidden flex flex-col justify-center items-center gap-1.5 w-9 h-9" aria-label="Toggle menu" aria-expanded="false">
<span class="hamburger-line block w-6 h-0.5 bg-gray-800 transition-all duration-300"></span>
<span class="hamburger-line block w-6 h-0.5 bg-gray-800 transition-all duration-300"></span>
<span class="hamburger-line block w-6 h-0.5 bg-gray-800 transition-all duration-300"></span>
</button>
</div>

<nav id="mobile-menu" class="md:hidden max-h-0 overflow-hidden transition-all duration-300 ease-in-out bg-white border-t">
<div class="flex flex-col px-6 py-2 text-sm font-medium text-gray-600">
<a href="<?php echo $basePath; ?>/index.php" class="py-3 border-b border-gray-100">Home</a>
<a href="<?php echo $basePath; ?>/products.php" class="py-3 border-b border-gray-100">Products</a>
<a href="<?php echo $basePath; ?>/about.php" class="py-3 border-b border-gray-100">About</a>
<a href="<?php echo $basePath; ?>/cart.php" class="py-3 border-b border-gray-100 flex items-center justify-between">
<span>Cart</span><span id="cart-badge-mobile" class="hidden bg-blue-600 text-white text-xs font-bold w-5 h-5 rounded-full items-center justify-center">0</span>
</a>

<?php if ($isLoggedIn): ?>
<?php if ($userRole === "admin"): ?>
<a href="<?php echo $basePath; ?>/admin/index.php" class="py-3 border-b border-gray-100">Admin</a>
<?php endif; ?>

<div class="py-3 border-b border-gray-100">
<div class="font-semibold text-gray-800 mb-2">Hi, <?php echo htmlspecialchars($userName); ?></div>
<div class="ml-3 flex flex-col">
<a href="<?php echo $basePath; ?>/profile.php" class="py-2">👤 Profile</a>
<a href="<?php echo $basePath; ?>/settings.php" class="py-2">⚙️ Settings</a>
<a href="<?php echo $basePath; ?>/order-history.php" class="py-2">📦 Order History</a>
</div>
</div>

<a href="<?php echo $basePath; ?>/logout.php?role=customer" class="py-3 text-red-600 font-semibold">Logout</a>
<?php else: ?>
<a href="<?php echo $basePath; ?>/login.php" class="py-3 border-b border-gray-100">Login</a>
<a href="<?php echo $basePath; ?>/register.php" class="py-3 text-blue-600 font-semibold">Register</a>
<?php endif; ?>
</div>
</nav>
</header>

<main class="flex-1">

<script>
document.addEventListener("DOMContentLoaded", function () {
    const btn = document.getElementById("user-dropdown-btn");
    const menu = document.getElementById("user-dropdown-menu");
    const arrow = document.getElementById("user-dropdown-arrow");
    const wrapper = document.getElementById("user-dropdown-wrapper");

    if (btn && menu) {
        btn.addEventListener("click", function(e) {
            e.stopPropagation();
            const open = !menu.classList.contains("hidden");
            menu.classList.toggle("hidden", open);
            btn.setAttribute("aria-expanded", String(!open));
            if (arrow) arrow.classList.toggle("rotate-180", !open);
        });

        document.addEventListener("click", function(e) {
            if (wrapper && !wrapper.contains(e.target)) {
                menu.classList.add("hidden");
                btn.setAttribute("aria-expanded", "false");
                if (arrow) arrow.classList.remove("rotate-180");
            }
        });

        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") {
                menu.classList.add("hidden");
                btn.setAttribute("aria-expanded", "false");
                if (arrow) arrow.classList.remove("rotate-180");
            }
        });
    }

    const mobileBtn = document.getElementById("mobile-menu-btn");
    const mobileMenu = document.getElementById("mobile-menu");

    if (mobileBtn && mobileMenu) {
        mobileBtn.addEventListener("click", function() {
            const open = mobileMenu.style.maxHeight && mobileMenu.style.maxHeight !== "0px";
            mobileMenu.style.maxHeight = open ? "0px" : mobileMenu.scrollHeight + "px";
            mobileBtn.setAttribute("aria-expanded", String(!open));
        });
    }

    const siteNav = document.getElementById("site-nav");
    if (siteNav) {
        window.addEventListener("scroll", function() {
            siteNav.classList.toggle("shadow-md", window.scrollY > 5);
        });
    }

    const reveals = document.querySelectorAll(".reveal");
    if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver(function(entries, obs) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    obs.unobserve(entry.target);
                }
            });
        }, {threshold: 0.08});
        reveals.forEach(function(el) { observer.observe(el); });
    } else {
        reveals.forEach(function(el) { el.classList.add("is-visible"); });
    }
});
</script>
