<?php

require_once "config/database.php";
require_once "config/esewa.php";

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_customer_session");
    session_start();
}

$pageTitle  = "Payment Verification";
$verified   = false;
$errorMsg   = "";
$orderId    = null;
$orderTotal = 0;

$rawData = $_GET["data"] ?? "";

if ($rawData === "") {
    $errorMsg = "No payment response was received from eSewa.";
} else {
    $decoded = json_decode(base64_decode($rawData), true);

    if (!is_array($decoded) || !isset($decoded["signature"], $decoded["transaction_uuid"], $decoded["total_amount"], $decoded["status"])) {
        $errorMsg = "The payment response from eSewa was malformed.";
    } else {

        // 1) Verify eSewa's signature on the response itself. This proves
        //    the redirect wasn't forged or tampered with in the browser.
        $signedFieldNames  = $decoded["signed_field_names"] ?? "transaction_code,status,total_amount,transaction_uuid,product_code";
        $expectedSignature = esewaSign($decoded, $signedFieldNames);

        if (!hash_equals($expectedSignature, (string) $decoded["signature"])) {
            $errorMsg = "Payment signature could not be verified.";
        } elseif ($decoded["status"] !== "COMPLETE") {
            $errorMsg = "Payment was not completed (status: " . htmlspecialchars($decoded["status"]) . ").";
        } else {

            // 2) Match this callback to a payment row using the
            //    transaction_uuid we generated at initiate time.
            $transactionUuid = $decoded["transaction_uuid"];

            $currentUserId = isset($_SESSION["user_id"]) ? (int) $_SESSION["user_id"] : 0;

            $sql = "SELECT payments.id AS payment_id, payments.amount, payments.status AS payment_status,
                           orders.id AS order_id, orders.user_id, orders.total_amount, orders.status AS order_status
                    FROM payments
                    JOIN orders ON orders.id = payments.order_id
                    WHERE payments.transaction_id = ?
                      AND payments.payment_method = 'esewa'
                      AND orders.user_id = ?
                    LIMIT 1";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $transactionUuid, $currentUserId);
            mysqli_stmt_execute($stmt);
            $payment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            $amountDiff = $payment ? abs((float) $decoded["total_amount"] - (float) $payment["amount"]) : null;

            if (!$payment) {
                $errorMsg = "We couldn't match this payment to one of your orders.";
            } elseif ($amountDiff > 0.01) {
                $errorMsg = "The paid amount does not match the order total.";
            } elseif ($payment["payment_status"] === "paid") {
                // Already processed earlier (e.g. the user refreshed this
                // page) — just show the success state again.
                $verified   = true;
                $orderId    = (int) $payment["order_id"];
                $orderTotal = (float) $payment["total_amount"];
            } else {

                // 3) Defense in depth: don't just trust the redirect, ask
                //    eSewa directly whether this transaction is COMPLETE.
                $statusUrl = ESEWA_STATUS_URL . "?" . http_build_query([
                    "product_code" => ESEWA_PRODUCT_CODE,
                    "total_amount" => $decoded["total_amount"],
                    "transaction_uuid" => $transactionUuid,
                ]);

                $statusData = esewaHttpGet($statusUrl);

                if (!$statusData || ($statusData["status"] ?? "") !== "COMPLETE") {
                    $errorMsg = "eSewa could not confirm this payment as complete. If money was deducted, it will be auto-refunded by eSewa, or contact support with your order number.";
                } else {

                    $refId = $statusData["ref_id"] ?? $transactionUuid;

                    mysqli_begin_transaction($conn);
                    try {
                        // Keep the transaction UUID in our database. The same
                        // callback may be refreshed, and replacing it with ref_id
                        // would make the callback impossible to match on refresh.
                        $updatePayment = "UPDATE payments
                                          SET status = 'paid', paid_at = NOW()
                                          WHERE id = ? AND status = 'pending'";
                        $stmt2 = mysqli_prepare($conn, $updatePayment);
                        mysqli_stmt_bind_param($stmt2, "i", $payment["payment_id"]);
                        mysqli_stmt_execute($stmt2);
                        $paymentUpdated = mysqli_stmt_affected_rows($stmt2) === 1;
                        mysqli_stmt_close($stmt2);

                        $updateOrder = "UPDATE orders SET status = 'confirmed'
                                        WHERE id = ? AND user_id = ? AND status = 'pending'";
                        $stmt3 = mysqli_prepare($conn, $updateOrder);
                        mysqli_stmt_bind_param($stmt3, "ii", $payment["order_id"], $currentUserId);
                        mysqli_stmt_execute($stmt3);
                        $orderUpdated = mysqli_stmt_affected_rows($stmt3) === 1;
                        mysqli_stmt_close($stmt3);

                        if (!$paymentUpdated && $payment["payment_status"] !== "paid") {
                            throw new Exception("Payment record could not be finalized.");
                        }

                        if (!$orderUpdated && $payment["order_status"] !== "confirmed") {
                            throw new Exception("Order could not be finalized.");
                        }

                        // Clear only the cart that was used for this eSewa checkout.
                        // Direct-buy checkouts intentionally leave the user's cart alone.
                        $pendingCartId = (int) ($_SESSION["esewa_pending_cart_id"] ?? 0);
                        $pendingOrderId = (int) ($_SESSION["esewa_pending_order_id"] ?? 0);

                        if ($pendingCartId > 0 && $pendingOrderId === (int) $payment["order_id"]) {
                            $clearCart = "DELETE ci FROM cart_items ci
                                          INNER JOIN cart c ON c.id = ci.cart_id
                                          WHERE ci.cart_id = ? AND c.user_id = ?";
                            $cartStmt = mysqli_prepare($conn, $clearCart);
                            mysqli_stmt_bind_param($cartStmt, "ii", $pendingCartId, $currentUserId);
                            mysqli_stmt_execute($cartStmt);
                            mysqli_stmt_close($cartStmt);
                        }

                        unset($_SESSION["esewa_pending_order_id"], $_SESSION["esewa_pending_cart_id"]);

                        mysqli_commit($conn);

                        $verified   = true;
                        $orderId    = (int) $payment["order_id"];
                        $orderTotal = (float) $payment["total_amount"];
                    } catch (Exception $e) {
                        mysqli_rollback($conn);
                        $errorMsg = "Payment was confirmed by eSewa, but we couldn't finalize your order. Please contact support.";
                    }
                }
            }
        }
    }
}

require_once "includes/header.php";
?>

<section class="max-w-lg mx-auto px-6 py-16">

    <?php if ($verified): ?>
        <div class="bg-white rounded-xl shadow p-12 text-center animate-pop">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-5">
                <span class="text-3xl text-green-600">✓</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Payment Successful!</h1>
            <p class="text-gray-500 mb-1">Your eSewa payment was verified and your order is confirmed.</p>
            <p class="text-gray-500 mb-6">
                Order <span class="font-semibold text-gray-700">#<?php echo (int) $orderId; ?></span>
                &middot; Total
                <span class="font-semibold text-gray-700">Rs. <?php echo number_format($orderTotal, 2); ?></span>
            </p>
            <a href="products.php" class="inline-block bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-blue-700 hover:scale-105 transition-all duration-200">
                Continue Shopping
            </a>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", () => {
                if (window.updateCartBadge) window.updateCartBadge(0);
            });
        </script>

    <?php else: ?>
        <div class="bg-white rounded-xl shadow p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-5">
                <span class="text-3xl text-red-600">&times;</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Payment Verification Failed</h1>
            <p class="text-gray-500 mb-6"><?php echo htmlspecialchars($errorMsg); ?></p>
            <a href="checkout.php" class="inline-block bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                Back to Checkout
            </a>
        </div>
    <?php endif; ?>

</section>

<?php require_once "includes/footer.php"; ?>
