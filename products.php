<?php

$pageTitle = "Products";
require_once "includes/header.php";
?>

<section class="max-w-6xl mx-auto px-6 py-10">

    <h1 class="text-3xl font-bold text-gray-800 mb-2 ">
        All Products
    </h1>
    <p class="text-gray-500 mb-8 ">
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

    .product-image-actions {
        position: absolute;
        right: 0.75rem;
        bottom: 0.75rem;
        display: flex;
        gap: 0.5rem;
        opacity: 1;
        transform: translateY(0);
        transition: all 0.2s ease;
    }

    .product-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.7rem;
        height: 2.7rem;
        border-radius: 9999px;
        border: 1px solid rgba(255, 255, 255, 0.9);
        background: rgba(255, 255, 255, 0.94);
        color: #1d4ed8;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
        transition: all 0.2s ease;
    }

    .product-action-btn:hover {
        background: #ffffff;
        transform: translateY(-1px);
    }

    .product-action-btn.is-loading {
        opacity: 0.8;
    }

    .product-action-btn svg {
        width: 1.15rem;
        height: 1.15rem;
    }

    .product-action-btn.buy-now-btn {
        color: #0f172a;
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

        const originalHtml = button.innerHTML;
        const loadingSvg = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2v4"></path>
                <path d="M12 18v4"></path>
                <path d="M4.93 4.93l2.83 2.83"></path>
                <path d="M16.24 16.24l2.83 2.83"></path>
                <path d="M2 12h4"></path>
                <path d="M18 12h4"></path>
                <path d="M4.93 19.07l2.83-2.83"></path>
                <path d="M16.24 7.76l2.83-2.83"></path>
            </svg>
        `;

        const successSvg = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12.5l4.2 4.2L19 2.5"></path>
            </svg>
        `;

        button.disabled = true;
        button.classList.add("is-loading");
        button.setAttribute("aria-label", "Adding to cart");
        button.innerHTML = loadingSvg;

        const formData = new FormData();
        formData.append("action", "add");
        formData.append("product_id", productId);
        formData.append("quantity", 1);

        fetch("api/cart.php", { method: "POST", body: formData })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    button.innerHTML = successSvg;
                    if (window.updateCartBadge) {
                        window.updateCartBadge(data.total_count || 0);
                    }
                    setTimeout(() => {
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                        button.classList.remove("is-loading");
                        button.setAttribute("aria-label", "Add to cart");
                    }, 1000);
                    return;
                }

                button.innerHTML = originalHtml;
                button.disabled = false;
                button.classList.remove("is-loading");
                button.setAttribute("aria-label", "Add to cart");
                alert(data.message || "Unable to add this item to the cart.");
            })
            .catch(() => {
                button.innerHTML = originalHtml;
                button.disabled = false;
                button.classList.remove("is-loading");
                button.setAttribute("aria-label", "Add to cart");
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
                    <a href="product-details.php?id=${product.id}" class="block p-3">
                        <div class="relative aspect-square overflow-hidden rounded-lg bg-gray-100">
                            ${product.image
                                ? `<img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.name)}"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">`
                                : `<div class="w-full h-full flex items-center justify-center text-gray-400 text-sm">No Image</div>`
                            }

                            <div class="product-image-actions">
                                <button type="button" class="product-action-btn add-to-cart-btn" data-product-id="${product.id}" aria-label="Add to cart" title="Add to cart">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="9" cy="19" r="1.6"></circle>
                                        <circle cx="17" cy="19" r="1.6"></circle>
                                        <path d="M3 4h2l2.8 10.2a1 1 0 0 0 1 .8H17a1 1 0 0 0 1-.8L20 7H7"></path>
                                    </svg>
                                </button>
                                <button type="button" class="product-action-btn buy-now-btn" data-product-id="${product.id}" aria-label="Buy now" title="Buy now">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 2v20"></path>
                                        <path d="M17 7l-5-5-5 5"></path>
                                        <path d="M7 17l5 5 5-5"></path>
                                    </svg>
                                </button>
                            </div>

                            ${outOfStock
                                ? `<span class="absolute top-2 right-2 bg-red-500 text-white text-[10px] font-semibold px-2 py-1 rounded-full">OUT OF STOCK</span>`
                                : ""
                            }
                        </div>
                    </a>

                    <div class="border-t border-slate-200 p-4">
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
            event.preventDefault();
            event.stopPropagation();
            if (!addButton.dataset.productId) return;
            addProductToCart(addButton.dataset.productId, addButton);
            return;
        }

        const buyButton = event.target.closest(".buy-now-btn");
        if (buyButton) {
            event.preventDefault();
            event.stopPropagation();
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