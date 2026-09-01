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
        background: #fafafa;
    }

    .product-image-panel {
        background: #eff6ff;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
    }

    /* Fixed-size image stage so every product photo — portrait, square,
       or landscape — displays consistently without stretching or
       randomly changing the height of the page. */
    .product-image-stage {
        height: 320px;
    }

    @media (min-width: 640px) {
        .product-image-stage {
            height: 420px;
        }
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
        background: #1d4ed8;
        box-shadow: 0 12px 24px rgba(37, 99, 235, 0.22);
    }

    .detail-cta-primary:hover {
        background: #1e40af;
    }

    .detail-cta-secondary {
        background: #ffffff;
        border: 1px solid #1d4ed8;
        color: #1e40af;
    }

    .reviews-section {
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid rgba(148, 163, 184, 0.2);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
    }

    .review-star {
        color: #f59e0b;
    }

    .review-star-button {
        transition: transform .15s ease, color .15s ease;
    }

    .review-star-button:hover {
        transform: scale(1.12);
    }

    .review-star-button.active {
        color: #f59e0b;
    }

    .review-input {
        background: #f8fafc;
        border-color: #cbd5e1;
        transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
    }

    .review-input:focus {
        background: #fff;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
        outline: none;
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
                <div class="product-image-stage flex w-full items-center justify-center overflow-hidden rounded-2xl bg-white">
                    <?php if (!empty($product["image"])): ?>
                        <img
                            id="productImage"
                            src="<?php echo htmlspecialchars($product["image"]); ?>"
                            alt="<?php echo htmlspecialchars($product["name"]); ?>"
                            class="h-full w-full object-contain transition-transform duration-500 hover:scale-105">
                    <?php else: ?>
                        <div class="flex h-full w-full flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-slate-300 bg-slate-50 text-slate-400">
                            <span class="text-4xl">🎵</span>
                            <span class="text-sm font-medium">No Image Available</span>
                        </div>
                    <?php endif; ?>
                </div>
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
                        Rs. <?php echo number_format((float) $product["price"], 2); ?>
                    </p>
                    <span class="rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-semibold text-cyan-700">Free shipping</span>
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

        <!-- Product Reviews -->
        <section id="reviewsSection" class="reviews-section mt-10 rounded-3xl p-6 sm:p-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Customer feedback</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Reviews & Ratings</h2>
                    <p class="mt-2 text-sm text-slate-500">See what customers think about this instrument.</p>
                </div>

                <div id="reviewSummary" class="rounded-2xl bg-slate-50 px-6 py-4 text-center lg:min-w-[210px]">
                    <div id="averageRating" class="text-3xl font-extrabold text-slate-900">0.0</div>
                    <div id="averageStars" class="mt-1 text-xl tracking-wide" aria-label="Average rating">★★★★★</div>
                    <div id="reviewCount" class="mt-1 text-xs font-medium text-slate-500">0 reviews</div>
                </div>
            </div>

            <?php if ($isLoggedIn): ?>
                <div id="reviewFormWrap" class="mt-8 hidden rounded-2xl border border-blue-100 bg-blue-50/50 p-5 sm:p-6">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h3 id="reviewFormTitle" class="text-lg font-bold text-slate-900">Write a review</h3>
                            <p id="reviewFormHint" class="text-sm text-slate-500">Your review helps other musicians choose the right instrument.</p>
                        </div>
                        <span class="text-xs font-medium text-slate-500">Verified purchase required</span>
                    </div>

                    <form id="reviewForm" class="mt-5" novalidate>
                        <input type="hidden" id="reviewProductId" value="<?php echo (int) $product['id']; ?>">
                        <input type="hidden" id="reviewId" value="">

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Your rating</label>
                            <div id="ratingStars" class="mt-2 flex items-center gap-1" role="radiogroup" aria-label="Choose a rating from 1 to 5 stars">
                                <button type="button" class="review-star-button text-3xl leading-none text-slate-300" data-rating="1" aria-label="1 star">★</button>
                                <button type="button" class="review-star-button text-3xl leading-none text-slate-300" data-rating="2" aria-label="2 stars">★</button>
                                <button type="button" class="review-star-button text-3xl leading-none text-slate-300" data-rating="3" aria-label="3 stars">★</button>
                                <button type="button" class="review-star-button text-3xl leading-none text-slate-300" data-rating="4" aria-label="4 stars">★</button>
                                <button type="button" class="review-star-button text-3xl leading-none text-slate-300" data-rating="5" aria-label="5 stars">★</button>
                            </div>
                            <p id="ratingError" class="mt-1 hidden text-sm text-red-600"></p>
                        </div>

                        <div class="mt-5">
                            <div class="flex items-center justify-between gap-3">
                                <label for="reviewText" class="block text-sm font-semibold text-slate-700">Your review</label>
                                <span id="reviewCounter" class="text-xs text-slate-400">0/1000</span>
                            </div>
                            <textarea id="reviewText" rows="5" maxlength="1000" class="review-input mt-2 w-full resize-y rounded-xl border px-4 py-3 text-sm text-slate-800" placeholder="Tell other musicians about the sound, quality, build, or your experience..."></textarea>
                            <p id="reviewError" class="mt-1 hidden text-sm text-red-600"></p>
                        </div>

                        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <button id="submitReviewBtn" type="submit" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                                Submit Review
                            </button>
                            <button id="cancelReviewBtn" type="button" class="hidden rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                Cancel Edit
                            </button>
                            <p id="reviewFeedback" class="hidden text-sm"></p>
                        </div>
                    </form>
                </div>

                <div id="reviewLoginMessage" class="mt-8 hidden rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600"></div>
            <?php else: ?>
                <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
                    <a href="login.php" class="font-semibold text-blue-600 hover:underline">Log in</a> to write a review after purchasing this product.
                </div>
            <?php endif; ?>

            <div id="reviewsList" class="mt-8 space-y-4">
                <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Loading reviews...</div>
            </div>
        </section>

    <?php endif; ?>

</section>

<?php if ($product): ?>
<script>
(function () {
    const productId = <?php echo (int) $product['id']; ?>;
    const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    const apiUrl = "api/reviews.php";

    const averageRating = document.getElementById("averageRating");
    const averageStars = document.getElementById("averageStars");
    const reviewCount = document.getElementById("reviewCount");
    const reviewsList = document.getElementById("reviewsList");
    const formWrap = document.getElementById("reviewFormWrap");
    const reviewForm = document.getElementById("reviewForm");
    const reviewId = document.getElementById("reviewId");
    const reviewText = document.getElementById("reviewText");
    const reviewCounter = document.getElementById("reviewCounter");
    const submitBtn = document.getElementById("submitReviewBtn");
    const cancelBtn = document.getElementById("cancelReviewBtn");
    const reviewFormTitle = document.getElementById("reviewFormTitle");
    const reviewFeedback = document.getElementById("reviewFeedback");
    const ratingError = document.getElementById("ratingError");
    const reviewError = document.getElementById("reviewError");
    const loginMessage = document.getElementById("reviewLoginMessage");
    const starButtons = document.querySelectorAll(".review-star-button");
    let selectedRating = 0;

    function escapeHtml(value) {
        const div = document.createElement("div");
        div.textContent = value ?? "";
        return div.innerHTML;
    }

    function stars(rating) {
        const rounded = Math.round(Number(rating) || 0);
        return Array.from({ length: 5 }, (_, i) => i < rounded ? "★" : "☆").join("");
    }

    function setStars(rating) {
        selectedRating = Number(rating) || 0;
        starButtons.forEach((button) => {
            const value = Number(button.dataset.rating);
            button.classList.toggle("active", value <= selectedRating);
            button.classList.toggle("text-slate-300", value > selectedRating);
        });
    }

    function showFieldError(element, message) {
        if (!element) return;
        element.textContent = message;
        element.classList.remove("hidden");
    }

    function clearErrors() {
        [ratingError, reviewError].forEach((element) => {
            if (element) {
                element.textContent = "";
                element.classList.add("hidden");
            }
        });
        if (reviewFeedback) {
            reviewFeedback.textContent = "";
            reviewFeedback.classList.add("hidden");
        }
    }

    function updateCounter() {
        if (reviewCounter && reviewText) {
            reviewCounter.textContent = `${reviewText.value.length}/1000`;
        }
    }

    function renderSummary(data) {
        const avg = Number(data.average_rating || 0).toFixed(1);
        const count = Number(data.review_count || 0);
        if (averageRating) averageRating.textContent = avg;
        if (averageStars) averageStars.textContent = stars(Number(data.average_rating || 0));
        if (reviewCount) reviewCount.textContent = `${count} review${count === 1 ? "" : "s"}`;
    }

    function renderReviews(data) {
        if (!reviewsList) return;
        const reviews = Array.isArray(data.reviews) ? data.reviews : [];

        if (!reviews.length) {
            reviewsList.innerHTML = `
                <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center">
                    <div class="text-3xl">⭐</div>
                    <p class="mt-2 font-semibold text-slate-700">No reviews yet</p>
                    <p class="mt-1 text-sm text-slate-500">Be the first verified customer to review this instrument.</p>
                </div>`;
            return;
        }

        reviewsList.innerHTML = reviews.map((item) => `
            <article class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-bold text-slate-900">${escapeHtml(item.user_name)}</h3>
                            ${item.verified_purchase ? '<span class="rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700">✓ Verified Purchase</span>' : ''}
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <span class="review-star text-lg tracking-wide">${stars(item.rating)}</span>
                            <span class="text-xs text-slate-400">${escapeHtml(item.created_at)}${item.updated ? " · edited" : ""}</span>
                        </div>
                    </div>
                    ${item.is_owner ? `
                        <div class="flex shrink-0 gap-2">
                            <button type="button" data-edit-review="${item.id}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</button>
                            <button type="button" data-delete-review="${item.id}" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button>
                        </div>` : ""}
                </div>
                <p class="mt-4 whitespace-pre-line break-words text-sm leading-7 text-slate-600">${escapeHtml(item.review)}</p>
            </article>
        `).join("");

        reviewsList.querySelectorAll("[data-edit-review]").forEach((button) => {
            button.addEventListener("click", () => {
                const item = reviews.find((review) => Number(review.id) === Number(button.dataset.editReview));
                if (item) startEdit(item);
            });
        });

        reviewsList.querySelectorAll("[data-delete-review]").forEach((button) => {
            button.addEventListener("click", () => deleteReview(button.dataset.deleteReview));
        });
    }

    function updateForm(data) {
        if (!isLoggedIn || !formWrap) return;

        const myReview = data.my_review;
        if (myReview) {
            formWrap.classList.add("hidden");
            return;
        }

        if (data.can_review) {
            formWrap.classList.remove("hidden");
            if (!reviewId.value) {
                reviewFormTitle.textContent = "Write a review";
                submitBtn.textContent = "Submit Review";
                cancelBtn.classList.add("hidden");
            }
            return;
        }

        formWrap.classList.add("hidden");
        if (loginMessage) {
            loginMessage.textContent = "You can write a review after your order for this product has been delivered.";
            loginMessage.classList.remove("hidden");
        }
    }

    function loadReviews() {
        fetch(`${apiUrl}?product_id=${encodeURIComponent(productId)}`, { credentials: "same-origin" })
            .then((response) => response.json())
            .then((result) => {
                if (!result.success) throw new Error(result.message || "Unable to load reviews.");
                renderSummary(result.data);
                renderReviews(result.data);
                updateForm(result.data);
            })
            .catch(() => {
                if (reviewsList) {
                    reviewsList.innerHTML = `<div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">Unable to load reviews right now. Please refresh the page.</div>`;
                }
            });
    }

    function startEdit(item) {
        if (!formWrap || !reviewForm) return;
        formWrap.classList.remove("hidden");
        reviewFormTitle.textContent = "Edit your review";
        submitBtn.textContent = "Update Review";
        cancelBtn.classList.remove("hidden");
        reviewId.value = item.id;
        reviewText.value = item.review || "";
        setStars(item.rating);
        clearErrors();
        updateCounter();
        formWrap.scrollIntoView({ behavior: "smooth", block: "center" });
        setTimeout(() => reviewText.focus(), 250);
    }

    function resetForm() {
        if (!reviewForm) return;
        reviewForm.reset();
        reviewId.value = "";
        setStars(0);
        reviewFormTitle.textContent = "Write a review";
        submitBtn.textContent = "Submit Review";
        cancelBtn.classList.add("hidden");
        updateCounter();
        clearErrors();
    }

    function deleteReview(id) {
        const formData = new FormData();
        formData.append("action", "delete");
        formData.append("product_id", productId);
        formData.append("review_id", id);

        fetch(apiUrl, { method: "POST", body: formData, credentials: "same-origin" })
            .then((response) => response.json())
            .then((result) => {
                if (!result.success) throw new Error(result.message || "Unable to delete review.");
                renderSummary(result.data);
                renderReviews(result.data);
                updateForm(result.data);
                resetForm();
            })
            .catch((error) => {
                if (reviewFeedback) {
                    reviewFeedback.textContent = error.message;
                    reviewFeedback.className = "text-sm text-red-600";
                    reviewFeedback.classList.remove("hidden");
                }
            });
    }

    starButtons.forEach((button) => {
        button.addEventListener("click", () => {
            setStars(button.dataset.rating);
            if (ratingError) ratingError.classList.add("hidden");
        });
    });

    if (reviewText) reviewText.addEventListener("input", updateCounter);
    if (cancelBtn) cancelBtn.addEventListener("click", resetForm);

    if (reviewForm) {
        reviewForm.addEventListener("submit", (event) => {
            event.preventDefault();
            clearErrors();

            let valid = true;
            const text = reviewText.value.trim();

            if (selectedRating < 1 || selectedRating > 5) {
                showFieldError(ratingError, "Please select a rating from 1 to 5 stars.");
                valid = false;
            }

            if (text.length < 10) {
                showFieldError(reviewError, "Your review should be at least 10 characters.");
                valid = false;
            } else if (text.length > 1000) {
                showFieldError(reviewError, "Your review cannot exceed 1000 characters.");
                valid = false;
            }

            if (!valid) return;

            const isEditing = Boolean(reviewId.value);
            const formData = new FormData();
            formData.append("action", isEditing ? "update" : "create");
            formData.append("product_id", productId);
            formData.append("rating", selectedRating);
            formData.append("review", text);
            if (isEditing) formData.append("review_id", reviewId.value);

            submitBtn.disabled = true;
            submitBtn.textContent = isEditing ? "Updating..." : "Submitting...";

            fetch(apiUrl, { method: "POST", body: formData, credentials: "same-origin" })
                .then((response) => response.json())
                .then((result) => {
                    if (!result.success) {
                        if (result.field === "review") showFieldError(reviewError, result.message);
                        else if (result.message && result.message.toLowerCase().includes("rating")) showFieldError(ratingError, result.message);
                        else throw new Error(result.message || "Unable to save review.");
                        return;
                    }

                    renderSummary(result.data);
                    renderReviews(result.data);
                    resetForm();
                    updateForm(result.data);

                    if (reviewFeedback) {
                        reviewFeedback.textContent = result.message;
                        reviewFeedback.className = "text-sm text-emerald-600";
                        reviewFeedback.classList.remove("hidden");
                    }
                })
                .catch((error) => {
                    if (reviewFeedback) {
                        reviewFeedback.textContent = error.message || "Something went wrong. Please try again.";
                        reviewFeedback.className = "text-sm text-red-600";
                        reviewFeedback.classList.remove("hidden");
                    }
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = reviewId.value ? "Update Review" : "Submit Review";
                });
        });
    }

    loadReviews();
})();
</script>
<?php endif; ?>

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