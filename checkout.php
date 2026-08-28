<?php

require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

$errors          = [];
$shippingAddress = "";
$paymentMethod   = "cod";
$orderPlaced     = false;
$orderId         = null;
$orderTotal      = 0;


function getCartId($conn, $userId) {

    $sql = "SELECT id FROM cart WHERE user_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row ? (int) $row["id"] : null;
}


// =====================================================
// HANDLE ORDER SUBMISSION
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $shippingAddress = trim($_POST["shipping_address"] ?? "");
    $paymentMethod   = in_array($_POST["payment_method"] ?? "", ["cod", "card"]) ? $_POST["payment_method"] : "cod";

    if ($shippingAddress === "") {
        $errors[] = "Shipping address is required.";
    } elseif (strlen($shippingAddress) < 10) {
        $errors[] = "Please enter a complete shipping address.";
    }

    if (empty($errors)) {

        $cartId = getCartId($conn, $userId);

        if (!$cartId) {
            $errors[] = "Your cart is empty.";
        } else {

            $itemsSql = "SELECT
                            cart_items.id AS cart_item_id,
                            cart_items.product_id,
                            cart_items.quantity,
                            products.price,
                            products.stock,
                            products.name
                         FROM cart_items
                         JOIN products ON cart_items.product_id = products.id
                         WHERE cart_items.cart_id = ?";

            $itemsStmt = mysqli_prepare($conn, $itemsSql);
            mysqli_stmt_bind_param($itemsStmt, "i", $cartId);
            mysqli_stmt_execute($itemsStmt);
            $itemsResult = mysqli_stmt_get_result($itemsStmt);

            $cartItems = [];
            while ($row = mysqli_fetch_assoc($itemsResult)) {
                $cartItems[] = $row;
            }
            mysqli_stmt_close($itemsStmt);

            if (empty($cartItems)) {
                $errors[] = "Your cart is empty.";
            } else {

                // Verify stock is still sufficient for every item
                foreach ($cartItems as $item) {
                    if ((int) $item["quantity"] > (int) $item["stock"]) {
                        $errors[] = "Not enough stock for \"" . $item["name"] . "\".";
                    }
                }
            }
        }
    }

    if (empty($errors)) {

        mysqli_begin_transaction($conn);

        try {

            $totalAmount = 0;
            foreach ($cartItems as $item) {
                $totalAmount += $item["price"] * $item["quantity"];
            }

            // Create the order
            $orderSql = "INSERT INTO orders (user_id, total_amount, status, shipping_address)
                         VALUES (?, ?, 'pending', ?)";
            $orderStmt = mysqli_prepare($conn, $orderSql);
            mysqli_stmt_bind_param($orderStmt, "ids", $userId, $totalAmount, $shippingAddress);
            mysqli_stmt_execute($orderStmt);
            $newOrderId = mysqli_insert_id($conn);
            mysqli_stmt_close($orderStmt);

            // Create order items + decrement stock
            foreach ($cartItems as $item) {

                $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price)
                            VALUES (?, ?, ?, ?)";
                $itemStmt = mysqli_prepare($conn, $itemSql);
                mysqli_stmt_bind_param(
                    $itemStmt,
                    "iiid",
                    $newOrderId,
                    $item["product_id"],
                    $item["quantity"],
                    $item["price"]
                );
                mysqli_stmt_execute($itemStmt);
                mysqli_stmt_close($itemStmt);

                $stockSql = "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?";
                $stockStmt = mysqli_prepare($conn, $stockSql);
                mysqli_stmt_bind_param(
                    $stockStmt,
                    "iii",
                    $item["quantity"],
                    $item["product_id"],
                    $item["quantity"]
                );
                mysqli_stmt_execute($stockStmt);

                if (mysqli_stmt_affected_rows($stockStmt) === 0) {
                    mysqli_stmt_close($stockStmt);
                    throw new Exception("Stock changed for \"" . $item["name"] . "\". Please review your cart.");
                }
                mysqli_stmt_close($stockStmt);
            }

            // Record payment
            $paymentStatus = $paymentMethod === "card" ? "paid" : "pending";
            $transactionId = $paymentMethod === "card" ? uniqid("txn_", true) : null;
            $paidAt = $paymentMethod === "card" ? date("Y-m-d H:i:s") : null;

            $paymentSql = "INSERT INTO payments (order_id, payment_method, transaction_id, amount, status, paid_at)
                           VALUES (?, ?, ?, ?, ?, ?)";
            $paymentStmt = mysqli_prepare($conn, $paymentSql);
            mysqli_stmt_bind_param(
                $paymentStmt,
                "issdss",
                $newOrderId,
                $paymentMethod,
                $transactionId,
                $totalAmount,
                $paymentStatus,
                $paidAt
            );
            mysqli_stmt_execute($paymentStmt);
            mysqli_stmt_close($paymentStmt);

            // Empty the cart
            $clearSql = "DELETE FROM cart_items WHERE cart_id = ?";
            $clearStmt = mysqli_prepare($conn, $clearSql);
            $cartIdForClear = getCartId($conn, $userId);
            mysqli_stmt_bind_param($clearStmt, "i", $cartIdForClear);
            mysqli_stmt_execute($clearStmt);
            mysqli_stmt_close($clearStmt);

            mysqli_commit($conn);

            $orderPlaced = true;
            $orderId     = $newOrderId;
            $orderTotal  = $totalAmount;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = "Checkout";
require_once "includes/header.php";
?>

<section class="max-w-6xl mx-auto px-6 py-10">

    <?php if ($orderPlaced): ?>

        <!-- ==========================================
             SUCCESS STATE
        =========================================== -->
        <div class="bg-white rounded-xl shadow p-12 text-center max-w-lg mx-auto animate-pop">

            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-5">
                <span class="text-3xl text-green-600">✓</span>
            </div>

            <h1 class="text-2xl font-bold text-gray-800 mb-2">Order Placed!</h1>
            <p class="text-gray-500 mb-1">Thank you for your purchase.</p>
            <p class="text-gray-500 mb-6">
                Order <span class="font-semibold text-gray-700">#<?php echo (int) $orderId; ?></span>
                &middot; Total
                <span class="font-semibold text-gray-700">$<?php echo number_format($orderTotal, 2); ?></span>
            </p>

            <a href="products.php"
                class="inline-block bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-blue-700 hover:scale-105 transition-all duration-200">
                Continue Shopping
            </a>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", () => {
                if (window.updateCartBadge) window.updateCartBadge(0);
            });
        </script>

    <?php else: ?>

        <h1 class="text-3xl font-bold text-gray-800 mb-8 reveal">
            Checkout
        </h1>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-6 max-w-2xl">
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="grid md:grid-cols-3 gap-8">

            <!-- SHIPPING + PAYMENT FORM -->
            <form method="POST" action="checkout.php" class="md:col-span-2 space-y-6">

                <div class="reveal bg-white rounded-xl shadow p-6">

                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Shipping Address</h2>

                    <textarea
                        name="shipping_address"
                        rows="4"
                        class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                        placeholder="Full name, street address, city, postal code, phone number"><?php echo htmlspecialchars($shippingAddress); ?></textarea>

                </div>

                <div class="reveal bg-white rounded-xl shadow p-6" style="transition-delay: 100ms;">

                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Payment Method</h2>

                    <div class="space-y-3">

                        <label class="flex items-center gap-3 border rounded-lg px-4 py-3 cursor-pointer hover:border-blue-400 transition">
                            <input type="radio" name="payment_method" value="cod"
                                <?php echo $paymentMethod === 'cod' ? 'checked' : ''; ?>>
                            <div>
                                <p class="font-medium text-gray-800">Cash on Delivery</p>
                                <p class="text-xs text-gray-500">Pay when your order arrives</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 border rounded-lg px-4 py-3 cursor-pointer hover:border-blue-400 transition">
                            <input type="radio" name="payment_method" value="card"
                                <?php echo $paymentMethod === 'card' ? 'checked' : ''; ?>>
                            <div>
                                <p class="font-medium text-gray-800">Credit / Debit Card</p>
                                <p class="text-xs text-gray-500">Demo payment &mdash; no real charge</p>
                            </div>
                        </label>

                    </div>

                </div>

                <button
                    type="submit"
                    id="placeOrderBtn"
                    class="w-full bg-blue-600 text-white font-semibold px-6 py-3.5 rounded-lg hover:bg-blue-700 hover:scale-[1.01] transition-all duration-200 shadow-lg reveal"
                    style="transition-delay: 150ms;">
                    Place Order
                </button>

            </form>

            <!-- ORDER SUMMARY (read-only, fetched live) -->
            <div class="reveal bg-white rounded-xl shadow p-6 h-fit sticky top-24" style="transition-delay: 200ms;">

                <h2 class="text-lg font-semibold text-gray-800 mb-4">Order Summary</h2>

                <div id="checkoutSummaryItems" class="space-y-3 mb-4 max-h-64 overflow-y-auto pr-1">
                    <div class="h-4 bg-gray-200 rounded animate-pulse"></div>
                </div>

                <div class="flex items-center justify-between text-base font-bold text-gray-800 border-t pt-4">
                    <span>Total</span>
                    <span id="checkoutSummaryTotal">$0.00</span>
                </div>

                <a href="cart.php" class="mt-4 block text-center text-sm text-blue-600 hover:underline">
                    Edit Cart
                </a>

            </div>

        </div>

    <?php endif; ?>

</section>

<?php if (!$orderPlaced): ?>
<script>
(function () {
    const summaryItems = document.getElementById("checkoutSummaryItems");
    const summaryTotal = document.getElementById("checkoutSummaryTotal");
    const placeOrderBtn = document.getElementById("placeOrderBtn");

    function escapeHtml(str) {
        const div = document.createElement("div");
        div.textContent = str ?? "";
        return div.innerHTML;
    }

    fetch("api/cart.php?action=list")
        .then((r) => r.json())
        .then((data) => {

            if (!data.success || data.data.length === 0) {
                summaryItems.innerHTML = `<p class="text-sm text-gray-500">Your cart is empty.</p>`;
                summaryTotal.textContent = "$0.00";
                if (placeOrderBtn) {
                    placeOrderBtn.disabled = true;
                    placeOrderBtn.classList.add("opacity-50", "cursor-not-allowed");
                }
                return;
            }

            summaryItems.innerHTML = data.data.map(item => `
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 truncate pr-2">${escapeHtml(item.name)} &times; ${item.quantity}</span>
                    <span class="text-gray-800 font-medium whitespace-nowrap">
                        $${(parseFloat(item.price) * item.quantity).toFixed(2)}
                    </span>
                </div>
            `).join("");

            summaryTotal.textContent = `$${parseFloat(data.total_amount).toFixed(2)}`;

            if (window.updateCartBadge) {
                window.updateCartBadge(data.total_count);
            }
        })
        .catch(() => {
            summaryItems.innerHTML = `<p class="text-sm text-red-500">Could not load your cart.</p>`;
        });
})();
</script>
<?php endif; ?>

<?php require_once "includes/footer.php"; ?>