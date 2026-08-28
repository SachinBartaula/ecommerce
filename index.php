<?php

require_once "config/database.php";

$pageTitle = "Home";
require_once "includes/header.php";


$sql = "SELECT
            products.id,
            products.name,
            products.price,
            products.image,
            products.stock,
            categories.name AS category
        FROM products
        LEFT JOIN categories
        ON products.category_id = categories.id
        ORDER BY products.id DESC
        LIMIT 8";

$result = mysqli_query($conn, $sql);
$featuredProducts = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $featuredProducts[] = $row;
    }
}
?>

<!-- ==========================================
     HERO
=========================================== -->
<section class="relative overflow-hidden bg-gradient-to-r from-blue-600 to-blue-500 text-white">

    <!-- floating decorative blobs -->
    <div class="pointer-events-none absolute -top-10 -left-10 w-72 h-72 bg-white/10 rounded-full blur-2xl animate-blob"></div>
    <div class="pointer-events-none absolute bottom-0 right-0 w-96 h-96 bg-fuchsia-400/20 rounded-full blur-2xl animate-blob" style="animation-delay: -3s;"></div>
    <div class="pointer-events-none absolute top-12 right-[12%] h-24 w-24 rotate-45 rounded-3xl border border-white/20"></div>

    <div class="relative max-w-6xl mx-auto px-6 py-24 md:py-28 text-center">

        <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[.18em] text-blue-100 animate-fade-up">
            <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
             Just for you
        </span>

        <h1 class="text-4xl md:text-6xl font-black leading-tight mb-5 animate-fade-up" style="animation-delay: 0.05s;">
            Everything You Need,<br>All in One Place
        </h1>

        <p class="max-w-xl mx-auto text-blue-100 text-lg mb-9 animate-fade-up" style="animation-delay: 0.2s;">
            Quality products at prices you'll love.
        </p>

        <a href="products.php"
            class="inline-block bg-white text-blue-700 font-bold px-9 py-3.5 rounded-full hover:bg-blue-50 hover:scale-105 transition-all duration-200 animate-fade-up shadow-xl"
            style="animation-delay: 0.35s;">
            Shop Now
        </a>

    </div>
</section>

<div class="relative z-10 -mt-7 max-w-5xl mx-auto px-6">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 rounded-2xl bg-white p-4 shadow-xl ring-1 ring-slate-100">
        <div class="flex items-center gap-3 rounded-xl bg-blue-50 px-4 py-3">
            <span class="text-2xl">🚚</span>
            <div><p class="text-sm font-bold text-slate-800">Fast delivery</p><p class="text-xs text-slate-500">Right to your door</p></div>
        </div>
        <div class="flex items-center gap-3 rounded-xl bg-fuchsia-50 px-4 py-3">
            <span class="text-2xl">✨</span>
            <div><p class="text-sm font-bold text-slate-800">Quality picks</p><p class="text-xs text-slate-500">Made to impress</p></div>
        </div>
        <div class="flex items-center gap-3 rounded-xl bg-emerald-50 px-4 py-3">
            <span class="text-2xl">🔒</span>
            <div><p class="text-sm font-bold text-slate-800">Secure checkout</p><p class="text-xs text-slate-500">Shop with confidence</p></div>
        </div>
    </div>
</div>


<!-- ==========================================
     FEATURED PRODUCTS
=========================================== -->
<section class="max-w-6xl mx-auto px-6 py-16">

    <div class="flex items-center justify-between mb-8 reveal">
        <div>
            <p class="text-xs font-extrabold uppercase tracking-[.18em] text-fuchsia-600 mb-1">Handpicked collection</p>
            <h2 class="text-2xl md:text-3xl font-black text-gray-800">Featured Products</h2>
        </div>
        <a href="products.php" class="rounded-full bg-blue-50 px-4 py-2 text-blue-700 text-sm font-bold hover:bg-blue-100 transition-colors">
            View all &rarr;
        </a>
    </div>

    <?php if (empty($featuredProducts)): ?>

        <div class="bg-white rounded-xl shadow p-10 text-center text-gray-500 reveal">
            No products available yet. Check back soon!
        </div>

    <?php else: ?>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">

            <?php foreach ($featuredProducts as $index => $product): ?>

                <a href="product-details.php?id=<?php echo (int) $product['id']; ?>"
                    class="reveal card-hover bg-white rounded-2xl shadow-md ring-1 ring-slate-100 overflow-hidden group"
                    style="transition-delay: <?php echo min($index * 70, 350); ?>ms;">

                    <div class="relative aspect-square bg-gradient-to-br from-blue-50 via-white to-fuchsia-50 overflow-hidden">

                        <?php if ($index < 3): ?>
                            <span class="absolute top-3 left-3 z-10 text-[10px] font-bold text-white px-3 py-1.5 rounded-full shimmer-badge shadow-md">
                                NEW
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($product['image'])): ?>
                            <img
                                src="<?php echo htmlspecialchars($product['image']); ?>"
                                alt="<?php echo htmlspecialchars($product['name']); ?>"
                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-blue-300 text-sm font-bold">
                                <span class="rounded-full bg-white/80 px-4 py-2">No Image</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-5">

                        <?php if (!empty($product['category'])): ?>
                            <p class="text-[10px] text-fuchsia-600 font-bold uppercase tracking-[.16em] mb-1">
                                <?php echo htmlspecialchars($product['category']); ?>
                            </p>
                        <?php endif; ?>

                        <h3 class="font-bold text-gray-800 truncate">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h3>

                        <div class="flex items-center justify-between mt-2">
                            <span class="text-blue-700 font-black text-lg">
                                $<?php echo number_format((float) $product['price'], 2); ?>
                            </span>

                            <?php if ((int) $product['stock'] <= 0): ?>
                                <span class="text-xs text-red-500 font-medium">Out of stock</span>
                            <?php endif; ?>
                        </div>

                    </div>

                </a>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php require_once "includes/footer.php"; ?>