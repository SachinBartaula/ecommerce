<?php

require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
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

<section class="max-w-6xl mx-auto px-6 py-10">

    <?php if (!$product): ?>

        <div class="bg-white rounded-xl shadow p-12 text-center animate-fade">
            <p class="text-4xl mb-3">📦</p>
            <p class="text-gray-700 font-medium text-lg">Product not found.</p>
            <a href="products.php" class="inline-block mt-4 text-blue-600 hover:underline">
                &larr; Back to Products
            </a>
        </div>

    <?php else: ?>

        <nav class="text-sm text-gray-500 mb-6 reveal">
            <a href="index.php" class="hover:text-blue-600">Home</a>
            <span class="mx-1">/</span>
            <a href="products.php" class="hover:text-blue-600">Products</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700"><?php echo htmlspecialchars($product["name"]); ?></span>
        </nav>

        <div class="grid md:grid-cols-2 gap-10">

            <!-- IMAGE -->
            <div class="reveal bg-white rounded-xl shadow overflow-hidden aspect-square">
                <?php if (!empty($product["image"])): ?>
                    <img
                        id="productImage"
                        src="<?php echo htmlspecialchars($product["image"]); ?>"
                        alt="<?php echo htmlspecialchars($product["name"]); ?>"
                        class="w-full h-full object-cover transition-transform duration-500 hover:scale-105">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                        No Image Available
                    </div>
                <?php endif; ?>
            </div>

            <!-- DETAILS -->
            <div class="reveal" style="transition-delay: 100ms;">

                <?php if (!empty($product["category"])): ?>
                    <p class="text-sm text-blue-600 font-medium uppercase tracking-wide mb-2">
                        <?php echo htmlspecialchars($product["category"]); ?>
                    </p>
                <?php endif; ?>

                <h1 class="text-3xl font-bold text-gray-800 mb-4">
                    <?php echo htmlspecialchars($product["name"]); ?>
                </h1>

                <p class="text-3xl font-bold text-blue-600 mb-6">
                    $<?php echo number_format((float) $product["price"], 2); ?>
                </p>

                <p class="text-gray-600 leading-relaxed mb-6">
                    <?php echo nl2br(htmlspecialchars($product["description"] ?: "No description available.")); ?>
                </p>

                <?php if ((int) $product["stock"] > 0): ?>

                    <p class="text-sm text-green-600 font-medium mb-6 flex items-center gap-1">
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        In Stock (<?php echo (int) $product["stock"]; ?> available)
                    </p>

                    <div class="flex items-center gap-4 mb-6">

                        <span class="text-sm font-medium text-gray-700">Quantity</span>

                        <div class="flex items-center border rounded-lg overflow-hidden">
                            <button
                                type="button"
                                id="qtyMinus"
                                class="px-4 py-2 text-gray-600 hover:bg-gray-100 transition">
                                &minus;
                            </button>
                            <input
                                type="number"
                                id="qtyInput"
                                value="1"
                                min="1"
                                max="<?php echo (int) $product['stock']; ?>"
                                class="w-14 text-center border-x py-2 focus:outline-none">
                            <button
                                type="button"
                                id="qtyPlus"
                                class="px-4 py-2 text-gray-600 hover:bg-gray-100 transition">
                                &plus;
                            </button>
                        </div>

                    </div>

                    <button
                        type="button"
                        id="addToCartBtn"
                        data-product-id="<?php echo (int) $product['id']; ?>"
                        class="w-full md:w-auto bg-blue-600 text-white font-semibold px-8 py-3 rounded-lg hover:bg-blue-700 hover:scale-105 transition-all duration-200 shadow-lg flex items-center justify-center gap-2">
                        <span id="addToCartLabel">Add to Cart</span>
                    </button>

                    <p id="cartFeedback" class="mt-3 text-sm hidden"></p>

                <?php else: ?>

                    <p class="text-sm text-red-600 font-medium mb-6 flex items-center gap-1">
                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                        Out of Stock
                    </p>

                    <button
                        type="button"
                        disabled
                        class="w-full md:w-auto bg-gray-300 text-gray-500 font-semibold px-8 py-3 rounded-lg cursor-not-allowed">
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
                    addBtn.classList.add("animate-pop");

                    feedback.textContent = "Added to your cart.";
                    feedback.className = "mt-3 text-sm text-green-600";
                    feedback.classList.remove("hidden");

                    if (window.updateCartBadge) {
                        window.updateCartBadge(data.total_count);
                    }

                    setTimeout(() => {
                        addLabel.textContent = "Add to Cart";
                        addBtn.classList.remove("animate-pop");
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
})();
</script>
<?php endif; ?>

<?php require_once "includes/footer.php"; ?>