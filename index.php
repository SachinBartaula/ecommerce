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
            categories.name AS category,
            COALESCE(ROUND(AVG(reviews.rating), 1), 0) AS average_rating,
            COUNT(reviews.id) AS review_count
        FROM products
        LEFT JOIN categories
            ON products.category_id = categories.id
        LEFT JOIN reviews
            ON reviews.product_id = products.id
        GROUP BY
            products.id,
            products.name,
            products.price,
            products.image,
            products.stock,
            categories.name
        ORDER BY products.id DESC
        LIMIT 8";

$result = mysqli_query($conn, $sql);
$featuredProducts = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $featuredProducts[] = $row;
    }
}

// Images for the hero slider. These are pulled from Unsplash (free to use).
// Swap these URLs for your own hosted images any time.
$heroSlides = [
    [
        'image' => 'https://images.unsplash.com/photo-1501059104508-e158516511cd?q=80&w=1600&auto=format&fit=crop',
        'alt'   => 'Acoustic guitar close up',
    ],
    [
        'image' => 'https://images.unsplash.com/photo-1511379938547-c1f69419868d?q=80&w=1600&auto=format&fit=crop',
        'alt'   => 'Electric guitars on display',
    ],
    [
        'image' => 'https://images.unsplash.com/photo-1627928173110-9818c7fd7c43?q=80&w=1600&auto=format&fit=crop',
        'alt'   => 'Drum kit in a studio',
    ],
    [
        'image' => 'https://images.unsplash.com/photo-1641227169487-36d5922339af?q=80&w=1600&auto=format&fit=crop',
        'alt'   => 'Piano keys close up',
    ],
    [
        'image' => 'https://images.unsplash.com/photo-1618609377864-68609b857e90?q=80&w=1600&auto=format&fit=crop',
        'alt'   => 'DJ audio mixing equipment',
    ],
];
?>

<!-- ==========================================
     HERO (sliding images)
=========================================== -->
<section class="relative overflow-hidden bg-blue-700 text-white">

    <div class="hero-slider relative h-[520px] md:h-[600px] overflow-hidden" id="heroSlider">

        <div class="hero-track absolute inset-0 flex h-full" id="heroTrack">
            <?php
            // Duplicate the first slide at the end so the track can slide
            // seamlessly from the last image back to the first.
            $slidesForTrack = $heroSlides;
            if (!empty($heroSlides)) {
                $slidesForTrack[] = $heroSlides[0];
            }
            ?>
            <?php foreach ($slidesForTrack as $index => $slide): ?>
                <div class="hero-slide relative h-full w-full flex-shrink-0" data-index="<?php echo $index; ?>">
                    <img
                        src="<?php echo htmlspecialchars($slide['image']); ?>"
                        alt="<?php echo htmlspecialchars($slide['alt']); ?>"
                        class="h-full w-full object-cover"
                        loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>">
                    <div class="absolute inset-0 bg-gradient-to-t from-blue-900/80 via-blue-900/40 to-blue-900/20"></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- overlay content -->
        <div class="pointer-events-none relative z-10 flex h-full items-center">
            <div class="pointer-events-auto max-w-6xl mx-auto px-6 w-full">
                <div class="max-w-xl text-center md:text-left">

                    <span class="inline-flex items-center gap-2 rounded-full border border-blue-300/30 bg-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[.18em] text-blue-100">
                        <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                        Discover Music
                    </span>

                    <h1 class="text-4xl md:text-5xl font-black leading-tight mb-5 mt-5">
                        Your Sound Starts Here<br>at MusicPasal
                    </h1>

                    <p class="max-w-md mx-auto md:mx-0 text-blue-100 text-lg mb-9">
                        Find premium music instruments and audio equipment at unbeatable prices.
                    </p>

                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-3">
                        <a href="products.php"
                            class="inline-block bg-amber-400 text-blue-900 font-bold px-9 py-3.5 rounded-full hover:bg-amber-300 hover:scale-105 transition-all duration-200 shadow-xl">
                            Explore Catalog
                        </a>
                        <a href="products.php"
                            class="inline-flex items-center gap-2 border border-white/30 text-white font-semibold px-6 py-3.5 rounded-full hover:bg-white/10 transition-all duration-200">
                            🎧 New Arrivals
                        </a>
                    </div>

                </div>
            </div>
        </div>

        <!-- prev / next arrows -->
        <button type="button" class="hero-arrow hero-prev absolute left-4 top-1/2 z-20 -translate-y-1/2 flex h-11 w-11 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur hover:bg-white/25 transition-colors" aria-label="Previous slide">
            &#10094;
        </button>
        <button type="button" class="hero-arrow hero-next absolute right-4 top-1/2 z-20 -translate-y-1/2 flex h-11 w-11 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur hover:bg-white/25 transition-colors" aria-label="Next slide">
            &#10095;
        </button>

        <!-- dots -->
        <div class="hero-dots absolute bottom-6 left-1/2 z-20 flex -translate-x-1/2 gap-2">
            <?php foreach ($heroSlides as $index => $slide): ?>
                <button type="button"
                    class="hero-dot h-2.5 w-2.5 rounded-full bg-white/40 transition-all duration-200 <?php echo $index === 0 ? 'is-active' : ''; ?>"
                    data-index="<?php echo $index; ?>"
                    aria-label="Go to slide <?php echo $index + 1; ?>">
                </button>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<style>
    .hero-track {
        transition: transform 1.1s cubic-bezier(.65, 0, .35, 1);
        will-change: transform;
    }
    .hero-track.no-transition {
        transition: none;
    }
    .hero-dot.is-active {
        background: #fbbf24; /* amber-400 */
        width: 1.5rem;
        border-radius: 9999px;
    }
    .hero-arrow {
        font-size: 1rem;
        line-height: 1;
    }
    @media (prefers-reduced-motion: reduce) {
        .hero-track { transition: none; }
    }
</style>

<script>
(function () {
    var slider = document.getElementById('heroSlider');
    var track  = document.getElementById('heroTrack');
    if (!slider || !track) return;

    var realSlides = track.querySelectorAll('.hero-slide');
    var dots = slider.querySelectorAll('.hero-dot');
    var prevBtn = slider.querySelector('.hero-prev');
    var nextBtn = slider.querySelector('.hero-next');

    // realSlides includes one cloned slide at the end for a seamless loop.
    var total = realSlides.length; // includes clone
    var realTotal = total - 1;     // actual number of dots
    var current = 0;
    var intervalMs = 4500;
    var timer = null;

    function setPosition(withTransition) {
        if (!withTransition) {
            track.classList.add('no-transition');
        } else {
            track.classList.remove('no-transition');
        }
        track.style.transform = 'translateX(-' + (current * 100) + '%)';
    }

    function setActiveDot(index) {
        dots.forEach(function (dot) {
            dot.classList.toggle('is-active', parseInt(dot.getAttribute('data-index'), 10) === index);
        });
    }

    function goTo(index) {
        current = index;
        setActiveDot(current % realTotal);
        setPosition(true);
    }

    function next() {
        current++;
        setActiveDot(current % realTotal);
        setPosition(true);
    }

    function prev() {
        if (current === 0) {
            // jump instantly to the clone position, then slide back smoothly
            current = realTotal;
            setPosition(false);
            track.offsetHeight; // force reflow
            current = realTotal - 1;
            setActiveDot(current);
            setPosition(true);
            return;
        }
        current--;
        setActiveDot(current);
        setPosition(true);
    }

    // When we land on the cloned final slide, snap back to the real first
    // slide with no visible transition, so the loop feels continuous.
    track.addEventListener('transitionend', function () {
        if (current === realTotal) {
            current = 0;
            setPosition(false);
        }
    });

    function startAuto() {
        stopAuto();
        if (realTotal > 1) {
            timer = setInterval(next, intervalMs);
        }
    }

    function stopAuto() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    if (nextBtn) nextBtn.addEventListener('click', function () { next(); startAuto(); });
    if (prevBtn) prevBtn.addEventListener('click', function () { prev(); startAuto(); });

    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            goTo(parseInt(dot.getAttribute('data-index'), 10));
            startAuto();
        });
    });

    slider.addEventListener('mouseenter', stopAuto);
    slider.addEventListener('mouseleave', startAuto);

    window.addEventListener('resize', function () { setPosition(false); });

    setPosition(false);
    startAuto();
})();
</script>

<div class="relative z-10 -mt-7 max-w-5xl mx-auto px-6">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 rounded-2xl bg-white p-4">
        <div class="flex items-center gap-3 rounded-xl bg-blue-50 px-4 py-3">
            <span class="text-2xl">🚚</span>
            <div><p class="text-sm font-bold text-slate-800">Fast delivery</p><p class="text-xs text-slate-500">Right to your door</p></div>
        </div>
        <div class="flex items-center gap-3 rounded-xl bg-cyan-50 px-4 py-3">
            <span class="text-2xl">✨</span>
            <div><p class="text-sm font-bold text-slate-800">Premium quality</p><p class="text-xs text-slate-500">Authentic products</p></div>
        </div>
        <div class="flex items-center gap-3 rounded-xl bg-amber-50 px-4 py-3">
            <span class="text-2xl">🔒</span>
            <div><p class="text-sm font-bold text-slate-800">Secure checkout</p><p class="text-xs text-slate-500">Shop with confidence</p></div>
        </div>
    </div>
</div>


<!-- ==========================================
     FEATURED PRODUCTS
=========================================== -->
<section class="max-w-6xl mx-auto px-6 py-16">

    <div class="flex items-center justify-between mb-8 ">
        <div>
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
                    class="reveal group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-xl"
                    style="transition-delay: <?php echo min($index * 70, 350); ?>ms;">

                    <div class="p-3">
                        <div class="relative aspect-square overflow-hidden rounded-xl bg-blue-50">

                            <?php if ($index < 3): ?>
                                <span class="absolute left-3 top-3 z-10 rounded-full bg-blue-600 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-white">
                                    New
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($product['image'])): ?>
                                <img
                                    src="<?php echo htmlspecialchars($product['image']); ?>"
                                    alt="<?php echo htmlspecialchars($product['name']); ?>"
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">
                            <?php else: ?>
                                <div class="flex h-full w-full items-center justify-center text-sm font-bold text-blue-300">
                                    <span class="rounded-full bg-white/80 px-4 py-2">No Image</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 p-4">
                        <?php if (!empty($product['category'])): ?>
                            <span class="mb-2 inline-block rounded-full bg-blue-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-blue-600">
                                <?php echo htmlspecialchars($product['category']); ?>
                            </span>
                        <?php endif; ?>

                        <h3 class="truncate text-base font-bold text-slate-800">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h3>

                        <div class="mt-2 flex items-center gap-2" aria-label="<?php echo number_format((float) $product['average_rating'], 1); ?> out of 5 stars, <?php echo (int) $product['review_count']; ?> reviews">
                            <span class="text-sm tracking-wide text-amber-400">
                                <?php
                                $cardRating = (int) round((float) $product['average_rating']);
                                echo str_repeat('★', $cardRating) . str_repeat('☆', 5 - $cardRating);
                                ?>
                            </span>
                            <span class="text-xs font-semibold text-slate-500">
                                <?php echo number_format((float) $product['average_rating'], 1); ?>
                            </span>
                            <span class="text-xs text-slate-400">
                                (<?php echo (int) $product['review_count']; ?>)
                            </span>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-2">
                            <span class="text-lg font-black text-blue-700">
                                Rs. <?php echo number_format((float) $product['price'], 2); ?>
                            </span>

                            <?php if ((int) $product['stock'] <= 0): ?>
                                <span class="rounded-full bg-red-50 px-2 py-1 text-[10px] font-semibold text-red-600">Out of stock</span>
                            <?php else: ?>
                                <span class="rounded-full bg-cyan-50 px-2 py-1 text-[10px] font-semibold text-cyan-600">In stock</span>
                            <?php endif; ?>
                        </div>
                    </div>

                </a>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php require_once "includes/footer.php"; ?>