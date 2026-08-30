<?php

require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_customer_session");
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
$directBuyProduct = null;
$directBuyQty     = 1;
$savedAddresses   = [];

$addressSql = "SELECT id, label, full_name, phone, address, city, postal_code, is_default FROM shipping_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC";
$addressStmt = mysqli_prepare($conn, $addressSql);
mysqli_stmt_bind_param($addressStmt, "i", $userId);
mysqli_stmt_execute($addressStmt);
$addressResult = mysqli_stmt_get_result($addressStmt);
while ($row = mysqli_fetch_assoc($addressResult)) {
    $savedAddresses[] = $row;
}
mysqli_stmt_close($addressStmt);

if ($shippingAddress === "" && !empty($savedAddresses)) {
    $defaultAddress = null;

    foreach ($savedAddresses as $saved) {
        if (!empty($saved["is_default"])) {
            $defaultAddress = $saved;
            break;
        }
    }

    if ($defaultAddress === null) {
        $defaultAddress = $savedAddresses[0];
    }

    $addressText = trim(
        ($defaultAddress["full_name"] ?? "") . ", " .
        ($defaultAddress["address"] ?? "") . ", " .
        ($defaultAddress["city"] ?? "") . ", " .
        ($defaultAddress["postal_code"] ?? "") . ", " .
        ($defaultAddress["phone"] ?? "")
    );

    if ($addressText !== "") {
        $shippingAddress = $addressText;
    }
}

$buyProductId = filter_input(INPUT_GET, "buy", FILTER_VALIDATE_INT);
if ($buyProductId) {
    $buySql = "SELECT id, name, price, stock FROM products WHERE id = ? LIMIT 1";
    $buyStmt = mysqli_prepare($conn, $buySql);
    mysqli_stmt_bind_param($buyStmt, "i", $buyProductId);
    mysqli_stmt_execute($buyStmt);
    $buyResult = mysqli_stmt_get_result($buyStmt);
    $directBuyProduct = mysqli_fetch_assoc($buyResult);
    mysqli_stmt_close($buyStmt);

    if ($directBuyProduct) {
        $directBuyQty = (int) ($_GET["qty"] ?? 1);
        if ($directBuyQty < 1) {
            $directBuyQty = 1;
        }
        if ($directBuyQty > (int) $directBuyProduct["stock"]) {
            $directBuyQty = (int) $directBuyProduct["stock"];
        }
    }
}


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
    $paymentMethod   = in_array($_POST["payment_method"] ?? "", ["cod", "card", "esewa", "khalti", "imepay"]) ? $_POST["payment_method"] : "cod";

    if ($shippingAddress === "") {
        $errors[] = "Shipping address is required.";
    } elseif (strlen($shippingAddress) < 10) {
        $errors[] = "Please enter a complete shipping address.";
    }

    if (empty($errors)) {

        $directBuyProductId = filter_input(INPUT_POST, "direct_buy_product_id", FILTER_VALIDATE_INT);
        if ($directBuyProductId) {
            $directBuySql = "SELECT id, name, price, stock FROM products WHERE id = ? LIMIT 1";
            $directBuyStmt = mysqli_prepare($conn, $directBuySql);
            mysqli_stmt_bind_param($directBuyStmt, "i", $directBuyProductId);
            mysqli_stmt_execute($directBuyStmt);
            $directBuyResult = mysqli_stmt_get_result($directBuyStmt);
            $directBuyProduct = mysqli_fetch_assoc($directBuyResult);
            mysqli_stmt_close($directBuyStmt);

            $directBuyQty = (int) ($_POST["direct_buy_quantity"] ?? 1);
            if ($directBuyQty < 1) {
                $directBuyQty = 1;
            }
            if ($directBuyQty > (int) ($directBuyProduct["stock"] ?? 0)) {
                $directBuyQty = (int) ($directBuyProduct["stock"] ?? 0);
            }

            if (!$directBuyProduct || (int) $directBuyProduct["stock"] <= 0) {
                $errors[] = "This product is not available for direct purchase.";
            } else {
                $cartItems = [[
                    "product_id" => (int) $directBuyProduct["id"],
                    "quantity" => $directBuyQty,
                    "price" => (float) $directBuyProduct["price"],
                    "stock" => (int) $directBuyProduct["stock"],
                    "name" => $directBuyProduct["name"]
                ]];
            }
        } else {
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

            $isPaidOnline = in_array($paymentMethod, ["card", "khalti", "imepay"], true);
            $paymentStatus = $isPaidOnline ? "paid" : "pending";
            $transactionId = $isPaidOnline ? uniqid("txn_", true) : null;
            $paidAt = $isPaidOnline ? date("Y-m-d H:i:s") : null;

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

            // Empty the cart only for normal checkout flow
            if (!$directBuyProductId) {
                $clearSql = "DELETE FROM cart_items WHERE cart_id = ?";
                $clearStmt = mysqli_prepare($conn, $clearSql);
                $cartIdForClear = getCartId($conn, $userId);
                mysqli_stmt_bind_param($clearStmt, "i", $cartIdForClear);
                mysqli_stmt_execute($clearStmt);
                mysqli_stmt_close($clearStmt);
            }

            mysqli_commit($conn);

            // eSewa: send the customer to eSewa's checkout page instead of
            if ($paymentMethod === "esewa") {
                header("Location: esewa-initiate.php?order_id=" . $newOrderId);
                exit;
            }

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

                    <?php if (!empty($savedAddresses)): ?>
                        <div class="mb-4">
                            <label for="saved-address-select" class="mb-2 block text-sm font-medium text-gray-700">Saved addresses</label>
                            <select id="saved-address-select" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                                <option value="new">Use a new address</option>
                                <?php foreach ($savedAddresses as $savedAddress): ?>
                                    <?php $savedText = trim(($savedAddress["full_name"] ?? "") . ", " . ($savedAddress["address"] ?? "") . ", " . ($savedAddress["city"] ?? "") . ", " . ($savedAddress["postal_code"] ?? "") . ", " . ($savedAddress["phone"] ?? "")); ?>
                                    <option value="<?php echo (int) $savedAddress["id"]; ?>" data-address="<?php echo htmlspecialchars($savedText, ENT_QUOTES); ?>">
                                        <?php echo htmlspecialchars(($savedAddress["label"] ?: "Address") . " - " . $savedAddress["full_name"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <textarea
                        id="shipping_address"
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
                            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-blue-50 text-lg">💵</span>
                            <div>
                                <p class="font-medium text-gray-800">Cash on Delivery</p>
                                <p class="text-xs text-gray-500">Pay when your order arrives</p>
                            </div>
                        </label>


                        <label class="flex items-center gap-3 border rounded-lg px-4 py-3 cursor-pointer hover:border-blue-400 transition">
                            <input type="radio" name="payment_method" value="esewa"
                                <?php echo $paymentMethod === 'esewa' ? 'checked' : ''; ?>>
                            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-blue-50 text-lg">📱</span>
                            <div>
                                <p class="font-medium text-gray-800">eSewa</p>
                                <p class="text-xs text-gray-500">Popular Nepal online payment</p>
                            </div>
                        </label>
<!-- 
                        <label class="flex items-center gap-3 border rounded-lg px-4 py-3 cursor-pointer hover:border-blue-400 transition">
                            <input type="radio" name="payment_method" value="khalti"
                                <?php echo $paymentMethod === 'khalti' ? 'checked' : ''; ?>>
                            <div>
                                <p class="font-medium text-gray-800">Khalti</p>
                                <p class="text-xs text-gray-500">Nepal digital wallet</p>
                            </div>
                        </label> -->

                        

                    </div>

                </div>

                <?php if ($directBuyProduct): ?>
                    <input type="hidden" name="direct_buy_product_id" value="<?php echo (int) $directBuyProduct["id"]; ?>">
                    <input type="hidden" name="direct_buy_quantity" value="<?php echo (int) $directBuyQty; ?>">
                <?php endif; ?>

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

    const addressSelect = document.getElementById("saved-address-select");
    const shippingAddressField = document.getElementById("shipping_address");

    if (addressSelect && shippingAddressField) {
        addressSelect.addEventListener("change", function () {
            const selectedOption = this.options[this.selectedIndex];
            if (!selectedOption || selectedOption.value === "new") {
                shippingAddressField.value = "";
                return;
            }

            shippingAddressField.value = selectedOption.dataset.address || "";
        });
    }

    const directBuyProduct = <?php echo json_encode($directBuyProduct ? [
        "id" => (int) $directBuyProduct["id"],
        "name" => $directBuyProduct["name"],
        "price" => (float) $directBuyProduct["price"],
        "quantity" => (int) $directBuyQty
    ] : null); ?>;

    if (directBuyProduct) {
        summaryItems.innerHTML = `
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 truncate pr-2">${escapeHtml(directBuyProduct.name)} &times; ${directBuyProduct.quantity}</span>
                <span class="text-gray-800 font-medium whitespace-nowrap">
                    $${(parseFloat(directBuyProduct.price) * directBuyProduct.quantity).toFixed(2)}
                </span>
            </div>
        `;
        summaryTotal.textContent = `$${(parseFloat(directBuyProduct.price) * directBuyProduct.quantity).toFixed(2)}`;
        return;
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