<?php

$pageTitle = "Products";
require_once "includes/header.php";
?>

<section class="max-w-6xl mx-auto px-6 py-10">

    <h1 class="text-3xl font-bold text-gray-800 mb-2 reveal">
        All Products
    </h1>
    <p class="text-gray-500 mb-8 reveal">
        Browse our full catalog &mdash; filter, search, and sort instantly.
    </p>

    <!-- ==========================================
         FILTER BAR
    =========================================== -->
    <div class="bg-white rounded-xl shadow p-5 mb-8 reveal">

        <div class="flex flex-col md:flex-row gap-4 md:items-center md:justify-between">

            <!-- Search -->
            <div class="relative flex-1 max-w-sm">
                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search products..."
                    class="w-full border rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
            </div>

            <!-- Sort -->
            <select
                id="sortSelect"
                class="border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                <option value="newest">Newest First</option>
                <option value="price_low">Price: Low to High</option>
                <option value="price_high">Price: High to Low</option>
                <option value="name_az">Name: A to Z</option>
            </select>

        </div>

        <!-- Category pills -->
        <div id="categoryPills" class="flex flex-wrap gap-2 mt-5">
            <button
                type="button"
                data-category="all"
                class="category-pill active-pill px-4 py-1.5 rounded-full text-sm font-medium border transition-all duration-200">
                All
            </button>
            <!-- more pills injected by JS -->
        </div>

    </div>

    <!-- Result count -->
    <p id="resultCount" class="text-sm text-gray-500 mb-4"></p>

    <!-- ==========================================
         PRODUCT GRID
    =========================================== -->
    <div id="productGrid" class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <!-- skeleton loaders while fetching -->
        <?php for ($i = 0; $i < 8; $i++): ?>
            <div class="bg-white rounded-xl shadow overflow-hidden animate-pulse">
                <div class="aspect-square bg-gray-200"></div>
                <div class="p-4 space-y-2">
                    <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                    <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                    <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Empty state (hidden by default) -->
    <div id="emptyState" class="hidden bg-white rounded-xl shadow p-12 text-center animate-fade">
        <p class="text-4xl mb-3">🔎</p>
        <p class="text-gray-600 font-medium">No products match your search.</p>
        <p class="text-gray-400 text-sm mt-1">Try a different keyword or category.</p>
    </div>

</section>

<style>
    .category-pill {
        border-color: #e5e7eb;
        color: #4b5563;
        background: #fff;
    }

    .category-pill:hover {
        border-color: #93c5fd;
        color: #2563eb;
    }

    .category-pill.active-pill {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }

    .product-card {
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08), 0 2px 8px rgba(15, 23, 42, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .product-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 32px rgba(37, 99, 235, 0.12), 0 6px 16px rgba(15, 23, 42, 0.08);
    }
</style>

<script>
(function () {

    const userLoggedIn = <?php echo isset($_SESSION["user_id"]) ? "true" : "false"; ?>;
    let allProducts = [];
    let activeCategory = "all";
    let searchTerm = "";
    let sortBy = "newest";

    const grid          = document.getElementById("productGrid");
    const emptyState     = document.getElementById("emptyState");
    const resultCount    = document.getElementById("resultCount");
    const searchInput    = document.getElementById("searchInput");
    const sortSelect      = document.getElementById("sortSelect");
    const categoryPillsEl = document.getElementById("categoryPills");

    function escapeHtml(str) {
        const div = document.createElement("div");
        div.textContent = str ?? "";
        return div.innerHTML;
    }

    function addProductToCart(productId, button) {
        if (!userLoggedIn) {
            window.location.href = "login.php";
            return;
        }

        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = "Adding...";

        const formData = new FormData();
        formData.append("action", "add");
        formData.append("product_id", productId);
        formData.append("quantity", 1);

        fetch("api/cart.php", { method: "POST", body: formData })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    button.textContent = "Added ✓";
                    if (window.updateCartBadge) {
                        window.updateCartBadge(data.total_count || 0);
                    }
                    setTimeout(() => {
                        button.textContent = originalText;
                        button.disabled = false;
                    }, 1200);
                    return;
                }

                button.textContent = originalText;
                button.disabled = false;
                alert(data.message || "Unable to add this item to the cart.");
            })
            .catch(() => {
                button.textContent = originalText;
                button.disabled = false;
                alert("Unable to add this item to the cart. Please try again.");
            });
    }

    function renderSkeleton() {
        grid.innerHTML = Array.from({ length: 8 }).map(() => `
            <div class="bg-white rounded-xl shadow overflow-hidden animate-pulse">
                <div class="aspect-square bg-gray-200"></div>
                <div class="p-4 space-y-2">
                    <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                    <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                    <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                </div>
            </div>
        `).join("");
    }

    function getFilteredProducts() {
        let list = [...allProducts];

        if (activeCategory !== "all") {
            list = list.filter(p => (p.category || "Uncategorized") === activeCategory);
        }

        if (searchTerm.trim() !== "") {
            const term = searchTerm.trim().toLowerCase();
            list = list.filter(p =>
                p.name.toLowerCase().includes(term) ||
                (p.description || "").toLowerCase().includes(term)
            );
        }

        switch (sortBy) {
            case "price_low":
                list.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
                break;
            case "price_high":
                list.sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
                break;
            case "name_az":
                list.sort((a, b) => a.name.localeCompare(b.name));
                break;
            default:
                list.sort((a, b) => b.id - a.id);
        }

        return list;
    }

    function renderProducts() {
        const list = getFilteredProducts();

        resultCount.textContent = `${list.length} product${list.length === 1 ? "" : "s"} found`;

        if (list.length === 0) {
            grid.innerHTML = "";
            emptyState.classList.remove("hidden");
            return;
        }

        emptyState.classList.add("hidden");

        grid.innerHTML = list.map((product, index) => {
            const outOfStock = parseInt(product.stock, 10) <= 0;
            const delay = Math.min(index * 50, 400);

            return `
                <div class="product-card card-hover animate-pop bg-white rounded-xl overflow-hidden group" style="animation-delay:${delay}ms">
                    <a href="product-details.php?id=${product.id}" class="block">
                        <div class="relative aspect-square bg-gray-100 overflow-hidden">
                            ${product.image
                                ? `<img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.name)}"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">`
                                : `<div class="w-full h-full flex items-center justify-center text-gray-400 text-sm">No Image</div>`
                            }
                            ${outOfStock
                                ? `<span class="absolute top-2 right-2 bg-red-500 text-white text-[10px] font-semibold px-2 py-1 rounded-full">OUT OF STOCK</span>`
                                : ""
                            }
                        </div>
                    </a>

                    <div class="p-4">
                        ${product.category
                            ? `<p class="text-xs text-gray-400 uppercase tracking-wide mb-1">${escapeHtml(product.category)}</p>`
                            : ""
                        }
                        <a href="product-details.php?id=${product.id}" class="block hover:text-blue-600 transition">
                            <h3 class="font-medium text-gray-800 truncate">${escapeHtml(product.name)}</h3>
                        </a>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-blue-600 font-bold">$${parseFloat(product.price).toFixed(2)}</span>
                        </div>
                    </div>

                    <div class="px-4 pb-4 space-y-2">
                        <button type="button" class="add-to-cart-btn w-full rounded-lg border border-blue-600 bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700" data-product-id="${product.id}">Add to Cart</button>
                        <button type="button" class="buy-now-btn w-full rounded-lg border border-blue-600 bg-white px-3 py-2 text-xs font-semibold text-blue-600 transition hover:bg-blue-50" data-product-id="${product.id}">Buy Now</button>
                    </div>
                </div>
            `;
        }).join("");
    }

    function renderCategoryPills(categories) {
        const pillsHtml = categories.map(cat => `
            <button
                type="button"
                data-category="${escapeHtml(cat.name)}"
                class="category-pill px-4 py-1.5 rounded-full text-sm font-medium border transition-all duration-200">
                ${escapeHtml(cat.name)}
            </button>
        `).join("");

        categoryPillsEl.insertAdjacentHTML("beforeend", pillsHtml);

        categoryPillsEl.querySelectorAll(".category-pill").forEach(btn => {
            btn.addEventListener("click", () => {
                categoryPillsEl.querySelectorAll(".category-pill").forEach(b => b.classList.remove("active-pill"));
                btn.classList.add("active-pill");
                activeCategory = btn.dataset.category;
                renderProducts();
            });
        });
    }

    // -------------------------
    // FETCH DATA
    // -------------------------

    renderSkeleton();

    Promise.all([
        fetch("api/products.php").then(r => r.json()),
        fetch("api/categories.php").then(r => r.json())
    ])
    .then(([productsRes, categoriesRes]) => {

        if (productsRes.success) {
            allProducts = productsRes.data;
        }

        if (categoriesRes.success) {
            renderCategoryPills(categoriesRes.data);
        }

        renderProducts();
    })
    .catch(() => {
        grid.innerHTML = "";
        emptyState.classList.remove("hidden");
        emptyState.querySelector("p.font-medium").textContent = "Something went wrong loading products.";
    });

    // -------------------------
    // EVENTS
    // -------------------------

    let searchDebounce;
    searchInput.addEventListener("input", (e) => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            searchTerm = e.target.value;
            renderProducts();
        }, 200);
    });

    sortSelect.addEventListener("change", (e) => {
        sortBy = e.target.value;
        renderProducts();
    });

    grid.addEventListener("click", (event) => {
        const addButton = event.target.closest(".add-to-cart-btn");
        if (addButton) {
            if (!addButton.dataset.productId) return;
            addProductToCart(addButton.dataset.productId, addButton);
            return;
        }

        const buyButton = event.target.closest(".buy-now-btn");
        if (buyButton) {
            if (!userLoggedIn) {
                window.location.href = "login.php";
                return;
            }

            window.location.href = `checkout.php?buy=${encodeURIComponent(buyButton.dataset.productId)}&qty=1`;
        }
    });

})();
</script>

<?php require_once "includes/footer.php"; ?>