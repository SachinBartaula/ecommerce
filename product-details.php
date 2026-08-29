<?php

require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_customer_session");
    session_start();
}

$isLoggedIn = isset($_SESSION["user_id"]);

$productId = (int) ($_GET["id"] ?? 0);

$product = null;

if ($productId > 0) {

    $sql = "SELECT
                products.id,
                products.name,
                products.description,
                products.price,
                products.stock,
                products.image,
                categories.name AS category
            FROM products
            LEFT JOIN categories ON products.category_id = categories.id
            WHERE products.id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

$pageTitle = $product ? $product["name"] : "Product Not Found";
require_once "includes/header.php";
?>

<style>
    .product-detail-shell {
        background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
    }

    .product-image-panel {
        background: linear-gradient(135deg, #f8fafc 0%, #eef6ff 100%);
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
    }

    .product-info-card {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(148, 163, 184, 0.2);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
    }

    .detail-qty-button {
        transition: all 0.2s ease;
    }

    .detail-qty-button:hover {
        background: #f1f5f9;
    }

    .detail-cta-primary {
        background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
        box-shadow: 0 12px 24px rgba(37, 99, 235, 0.22);
    }

    .detail-cta-secondary {
        background: #ffffff;
        border: 1px solid #2563eb;
        color: #1d4ed8;
    }
</style>

<section class="product-detail-shell mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">

    <?php if (!$product): ?>

        <div class="rounded-2xl bg-white p-12 text-center shadow-lg ring-1 ring-slate-200">
            <p class="mb-3 text-4xl">📦</p>
            <p class="text-lg font-medium text-slate-700">Product not found.</p>
            <a href="products.php" class="mt-4 inline-block text-blue-600 hover:underline">
                &larr; Back to Products
            </a>
        </div>

    <?php else: ?>

        <nav class="mb-7 text-sm text-slate-500">
            <div class="flex flex-wrap items-center gap-2">
                <a href="index.php" class="transition hover:text-blue-600">Home</a>
                <span>/</span>
                <a href="products.php" class="transition hover:text-blue-600">Products</a>
                <span>/</span>
                <span class="text-slate-700"><?php echo htmlspecialchars($product["name"]); ?></span>
            </div>
        </nav>

        <div class="grid gap-8 lg:grid-cols-2">

            <div class="product-image-panel reveal overflow-hidden rounded-3xl bg-white p-4">
                <?php if (!empty($product["image"])): ?>
                    <img
                        id="productImage"
                        src="<?php echo htmlspecialchars($product["image"]); ?>"
                        alt="<?php echo htmlspecialchars($product["name"]); ?>"
                        class="h-full min-h-[420px] w-full rounded-2xl object-cover transition-transform duration-500 hover:scale-[1.02]">
                <?php else: ?>
                    <div class="flex h-[420px] w-full items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 text-slate-400">
                        No Image Available
                    </div>
                <?php endif; ?>
            </div>

            <div class="product-info-card reveal rounded-3xl p-6 sm:p-8" style="transition-delay: 100ms;">
                <?php if (!empty($product["category"])): ?>
                    <p class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">
                        <?php echo htmlspecialchars($product["category"]); ?>
                    </p>
                <?php endif; ?>

                <h1 class="mb-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                    <?php echo htmlspecialchars($product["name"]); ?>
                </h1>

                <div class="mb-6 flex items-center gap-3">
                    <p class="text-3xl font-extrabold text-blue-600">
                        $<?php echo number_format((float) $product["price"], 2); ?>
                    </p>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Free shipping</span>
                </div>

                <div class="mb-6 rounded-2xl bg-slate-50 p-4">
                    <p class="text-sm font-medium text-slate-600">Description</p>
                    <p class="mt-2 leading-7 text-slate-600">
                        <?php echo nl2br(htmlspecialchars($product["description"] ?: "No description available.")); ?>
                    </p>
                </div>

                <?php if ((int) $product["stock"] > 0): ?>
                    <div class="mb-6 flex items-center gap-2 text-sm font-medium text-emerald-600">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        In stock · <?php echo (int) $product["stock"]; ?> available
                    </div>

                    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <span class="text-sm font-medium text-slate-700">Quantity</span>

                        <div class="flex items-center overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm">
                            <button type="button" id="qtyMinus" class="detail-qty-button h-11 w-11 text-xl font-medium text-slate-600">&minus;</button>
                            <input
                                type="number"
                                id="qtyInput"
                                value="1"
                                min="1"
                                max="<?php echo (int) $product['stock']; ?>"
                                class="h-11 w-16 border-x border-slate-300 bg-white text-center text-sm font-semibold text-slate-800 outline-none focus:ring-0">
                            <button type="button" id="qtyPlus" class="detail-qty-button h-11 w-11 text-xl font-medium text-slate-600">&plus;</button>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button
                            type="button"
                            id="addToCartBtn"
                            data-product-id="<?php echo (int) $product['id']; ?>"
                            class="detail-cta-primary flex w-full items-center justify-center rounded-xl px-6 py-3 text-sm font-semibold text-white transition hover:brightness-105 sm:w-auto">
                            <span id="addToCartLabel">Add to Cart</span>
                        </button>

                        <button
                            type="button"
                            id="buyNowBtn"
                            data-product-id="<?php echo (int) $product['id']; ?>"
                            class="detail-cta-secondary flex w-full items-center justify-center rounded-xl px-6 py-3 text-sm font-semibold transition hover:bg-blue-50 sm:w-auto">
                            Buy Now
                        </button>
                    </div>

                    <p id="cartFeedback" class="mt-3 hidden text-sm"></p>

                <?php else: ?>
                    <div class="mb-6 flex items-center gap-2 text-sm font-medium text-red-600">
                        <span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>
                        Out of stock
                    </div>

                    <button
                        type="button"
                        disabled
                        class="w-full rounded-xl bg-slate-200 px-6 py-3 text-sm font-semibold text-slate-500 sm:w-auto">
                        Out of Stock
                    </button>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>

</section>

<?php if ($product && (int) $product["stock"] > 0): ?>
<script>
(function () {
    const qtyInput   = document.getElementById("qtyInput");
    const qtyMinus   = document.getElementById("qtyMinus");
    const qtyPlus    = document.getElementById("qtyPlus");
    const addBtn     = document.getElementById("addToCartBtn");
    const buyNowBtn  = document.getElementById("buyNowBtn");
    const addLabel   = document.getElementById("addToCartLabel");
    const feedback   = document.getElementById("cartFeedback");
    const maxStock   = <?php echo (int) $product["stock"]; ?>;
    const isLoggedIn = <?php echo $isLoggedIn ? "true" : "false"; ?>;

    qtyMinus.addEventListener("click", () => {
        const val = Math.max(1, parseInt(qtyInput.value || 1, 10) - 1);
        qtyInput.value = val;
    });

    qtyPlus.addEventListener("click", () => {
        const val = Math.min(maxStock, parseInt(qtyInput.value || 1, 10) + 1);
        qtyInput.value = val;
    });

    qtyInput.addEventListener("change", () => {
        let val = parseInt(qtyInput.value || 1, 10);
        if (isNaN(val) || val < 1) val = 1;
        if (val > maxStock) val = maxStock;
        qtyInput.value = val;
    });

    addBtn.addEventListener("click", () => {
        if (!isLoggedIn) {
            window.location.href = "login.php";
            return;
        }

        const quantity = parseInt(qtyInput.value || 1, 10);

        addBtn.disabled = true;
        addLabel.textContent = "Adding...";

        const formData = new FormData();
        formData.append("action", "add");
        formData.append("product_id", addBtn.dataset.productId);
        formData.append("quantity", quantity);

        fetch("api/cart.php", { method: "POST", body: formData })
            .then((r) => r.json())
            .then((data) => {
                addBtn.disabled = false;

                if (data.success) {
                    addLabel.textContent = "Added ✓";
                    feedback.textContent = "Added to your cart.";
                    feedback.className = "mt-3 text-sm text-green-600";
                    feedback.classList.remove("hidden");

                    if (window.updateCartBadge) {
                        window.updateCartBadge(data.total_count);
                    }

                    setTimeout(() => {
                        addLabel.textContent = "Add to Cart";
                    }, 1500);
                } else {
                    addLabel.textContent = "Add to Cart";
                    feedback.textContent = data.message || "Something went wrong.";
                    feedback.className = "mt-3 text-sm text-red-600";
                    feedback.classList.remove("hidden");
                }
            })
            .catch(() => {
                addBtn.disabled = false;
                addLabel.textContent = "Add to Cart";
                feedback.textContent = "Network error. Please try again.";
                feedback.className = "mt-3 text-sm text-red-600";
                feedback.classList.remove("hidden");
            });
    });

    buyNowBtn.addEventListener("click", () => {
        if (!isLoggedIn) {
            window.location.href = "login.php";
            return;
        }

        const quantity = parseInt(qtyInput.value || 1, 10);
        window.location.href = `checkout.php?buy=${encodeURIComponent(addBtn.dataset.productId)}&qty=${encodeURIComponent(quantity)}`;
    });
})();
</script>
<?php endif; ?>

<?php require_once "includes/footer.php"; ?>