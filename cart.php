<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$pageTitle = "Your Cart";
require_once "includes/header.php";
?>

<section class="max-w-6xl mx-auto px-6 py-10">

    <h1 class="text-3xl font-bold text-gray-800 mb-8 reveal">
        Your Cart
    </h1>

    <div id="cartLoading" class="grid md:grid-cols-3 gap-8">
        <div class="md:col-span-2 space-y-4">
            <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="bg-white rounded-xl shadow p-4 flex gap-4 animate-pulse">
                    <div class="w-24 h-24 bg-gray-200 rounded-lg"></div>
                    <div class="flex-1 space-y-2 py-2">
                        <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                        <div class="h-3 bg-gray-200 rounded w-1/4"></div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
        <div class="bg-white rounded-xl shadow p-6 h-40 animate-pulse"></div>
    </div>

    <div id="cartContent" class="hidden grid md:grid-cols-3 gap-8">

        <!-- CART ITEMS -->
        <div class="md:col-span-2 space-y-4" id="cartItemsList"></div>

        <!-- ORDER SUMMARY -->
        <div class="reveal bg-white rounded-xl shadow p-6 h-fit sticky top-24">

            <h2 class="text-lg font-semibold text-gray-800 mb-4">Order Summary</h2>

            <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                <span>Items</span>
                <span id="summaryCount">0</span>
            </div>

            <div class="flex items-center justify-between text-base font-bold text-gray-800 border-t pt-4 mt-4">
                <span>Total</span>
                <span id="summaryTotal">$0.00</span>
            </div>

            <a
                id="checkoutBtn"
                href="checkout.php"
                class="mt-6 w-full bg-blue-600 text-white font-semibold px-4 py-3 rounded-lg hover:bg-blue-700 hover:scale-[1.02] transition-all duration-200 flex items-center justify-center shadow-lg">
                Proceed to Checkout
            </a>

            <a href="products.php" class="mt-3 block text-center text-sm text-blue-600 hover:underline">
                &larr; Continue Shopping
            </a>

        </div>

    </div>

    <!-- EMPTY STATE -->
    <div id="emptyCart" class="hidden bg-white rounded-xl shadow p-16 text-center animate-fade">
        <p class="text-5xl mb-4">🛒</p>
        <p class="text-gray-700 font-medium text-lg mb-2">Your cart is empty.</p>
        <p class="text-gray-400 text-sm mb-6">Looks like you haven't added anything yet.</p>
        <a href="products.php"
            class="inline-block bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-blue-700 hover:scale-105 transition-all duration-200">
            Browse Products
        </a>
    </div>

</section>

<script>
(function () {

    const cartLoading  = document.getElementById("cartLoading");
    const cartContent  = document.getElementById("cartContent");
    const emptyCart    = document.getElementById("emptyCart");
    const itemsList    = document.getElementById("cartItemsList");
    const summaryCount = document.getElementById("summaryCount");
    const summaryTotal = document.getElementById("summaryTotal");
    const checkoutBtn  = document.getElementById("checkoutBtn");

    function escapeHtml(str) {
        const div = document.createElement("div");
        div.textContent = str ?? "";
        return div.innerHTML;
    }

    function renderItem(item) {
        const lineTotal = (parseFloat(item.price) * item.quantity).toFixed(2);

        return `
            <div class="cart-row bg-white rounded-xl shadow p-4 flex gap-4 items-center transition-all duration-300" data-item-id="${item.id}">

                <div class="w-24 h-24 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                    ${item.image
                        ? `<img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" class="w-full h-full object-cover">`
                        : `<div class="w-full h-full flex items-center justify-center text-gray-400 text-xs">No Image</div>`
                    }
                </div>

                <div class="flex-1 min-w-0">
                    <h3 class="font-medium text-gray-800 truncate">${escapeHtml(item.name)}</h3>
                    <p class="text-blue-600 font-semibold mt-1">$${parseFloat(item.price).toFixed(2)}</p>

                    <div class="flex items-center gap-3 mt-3">
                        <div class="flex items-center border rounded-lg overflow-hidden">
                            <button type="button" class="qty-minus px-3 py-1 text-gray-600 hover:bg-gray-100 transition">&minus;</button>
                            <span class="qty-value px-4 py-1 text-sm">${item.quantity}</span>
                            <button type="button" class="qty-plus px-3 py-1 text-gray-600 hover:bg-gray-100 transition">&plus;</button>
                        </div>

                        <button type="button" class="remove-item text-red-500 text-sm hover:underline">
                            Remove
                        </button>
                    </div>
                </div>

                <div class="text-right font-semibold text-gray-800 line-total">
                    $${lineTotal}
                </div>

            </div>
        `;
    }

    function renderSummary(totalCount, totalAmount) {
        summaryCount.textContent = totalCount;
        summaryTotal.textContent = `$${parseFloat(totalAmount).toFixed(2)}`;
    }

    function attachRowEvents() {
        itemsList.querySelectorAll(".cart-row").forEach((row) => {
            const itemId = row.dataset.itemId;
            const qtyValue = row.querySelector(".qty-value");
            const minusBtn = row.querySelector(".qty-minus");
            const plusBtn = row.querySelector(".qty-plus");
            const removeBtn = row.querySelector(".remove-item");

            const updateQuantity = (newQty) => {
                const formData = new FormData();
                formData.append("action", "update");
                formData.append("item_id", itemId);
                formData.append("quantity", newQty);

                fetch("api/cart.php", { method: "POST", body: formData })
                    .then((r) => r.json())
                    .then(() => loadCart());
            };

            minusBtn.addEventListener("click", () => {
                const current = parseInt(qtyValue.textContent, 10);
                updateQuantity(Math.max(0, current - 1));
            });

            plusBtn.addEventListener("click", () => {
                const current = parseInt(qtyValue.textContent, 10);
                updateQuantity(current + 1);
            });

            removeBtn.addEventListener("click", () => {
                row.style.opacity = "0";
                row.style.transform = "translateX(20px)";

                const formData = new FormData();
                formData.append("action", "remove");
                formData.append("item_id", itemId);

                fetch("api/cart.php", { method: "POST", body: formData })
                    .then((r) => r.json())
                    .then(() => setTimeout(loadCart, 200));
            });
        });
    }

    function loadCart() {
        fetch("api/cart.php?action=list")
            .then((r) => r.json())
            .then((data) => {

                cartLoading.classList.add("hidden");

                if (!data.success || data.data.length === 0) {
                    cartContent.classList.add("hidden");
                    emptyCart.classList.remove("hidden");
                    if (window.updateCartBadge) window.updateCartBadge(0);
                    return;
                }

                emptyCart.classList.add("hidden");
                cartContent.classList.remove("hidden");

                itemsList.innerHTML = data.data.map(renderItem).join("");
                renderSummary(data.total_count, data.total_amount);
                attachRowEvents();

                const anyOutOfStock = data.data.some(item => parseInt(item.stock, 10) <= 0);
                if (anyOutOfStock) {
                    checkoutBtn.classList.add("opacity-50", "pointer-events-none");
                } else {
                    checkoutBtn.classList.remove("opacity-50", "pointer-events-none");
                }

                if (window.updateCartBadge) {
                    window.updateCartBadge(data.total_count);
                }
            })
            .catch(() => {
                cartLoading.classList.add("hidden");
                emptyCart.classList.remove("hidden");
            });
    }

    loadCart();

})();
</script>

<?php require_once "includes/footer.php"; ?>