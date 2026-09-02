<?php

require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_customer_session");
    session_start();
}

$orderId = filter_input(INPUT_GET, "order_id", FILTER_VALIDATE_INT);

if (isset($_SESSION["user_id"]) && $orderId) {
    $userId = (int) $_SESSION["user_id"];

    /*
     * eSewa cancellation must NOT leave a normal "pending" order behind.
     * The checkout temporarily reserves stock, so on cancellation we:
     *   1. lock the pending eSewa payment/order,
     *   2. return reserved quantities to product stock,
     *   3. delete the order (payment + order items cascade),
     *   4. leave the cart untouched so the customer can retry.
     *
     * Everything is done in one transaction.
     */
    mysqli_begin_transaction($conn);

    try {
        $lockSql = "SELECT payments.id AS payment_id, payments.status AS payment_status
                    FROM payments
                    JOIN orders ON orders.id = payments.order_id
                    WHERE orders.id = ?
                      AND orders.user_id = ?
                      AND orders.status = 'pending'
                      AND payments.payment_method = 'esewa'
                      AND payments.status = 'pending'
                    LIMIT 1
                    FOR UPDATE";
        $lockStmt = mysqli_prepare($conn, $lockSql);
        mysqli_stmt_bind_param($lockStmt, "ii", $orderId, $userId);
        mysqli_stmt_execute($lockStmt);
        $payment = mysqli_fetch_assoc(mysqli_stmt_get_result($lockStmt));
        mysqli_stmt_close($lockStmt);

        if ($payment) {
            $itemsSql = "SELECT product_id, quantity
                         FROM order_items
                         WHERE order_id = ?";
            $itemsStmt = mysqli_prepare($conn, $itemsSql);
            mysqli_stmt_bind_param($itemsStmt, "i", $orderId);
            mysqli_stmt_execute($itemsStmt);
            $itemsResult = mysqli_stmt_get_result($itemsStmt);

            while ($item = mysqli_fetch_assoc($itemsResult)) {
                if (!empty($item["product_id"])) {
                    $restoreSql = "UPDATE products SET stock = stock + ? WHERE id = ?";
                    $restoreStmt = mysqli_prepare($conn, $restoreSql);
                    mysqli_stmt_bind_param(
                        $restoreStmt,
                        "ii",
                        $item["quantity"],
                        $item["product_id"]
                    );
                    mysqli_stmt_execute($restoreStmt);
                    mysqli_stmt_close($restoreStmt);
                }
            }
            mysqli_stmt_close($itemsStmt);

            $deleteStmt = mysqli_prepare(
                $conn,
                "DELETE FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'"
            );
            mysqli_stmt_bind_param($deleteStmt, "ii", $orderId, $userId);
            mysqli_stmt_execute($deleteStmt);
            mysqli_stmt_close($deleteStmt);
        }

        mysqli_commit($conn);

        // The cart was never cleared for eSewa, so it is ready for retry.
        unset($_SESSION["esewa_pending_order_id"], $_SESSION["esewa_pending_cart_id"]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
    }
}

$pageTitle = "Payment Cancelled";
require_once "includes/header.php";
?>

<section class="max-w-lg mx-auto px-6 py-16">
    <div class="bg-white rounded-xl shadow p-12 text-center">
        <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-5">
            <span class="text-3xl text-amber-600">!</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Payment Not Completed</h1>
        <p class="text-gray-500 mb-6">
            Your eSewa payment was cancelled or didn't go through. No money was charged. You can try again below.
        </p>
        <a href="checkout.php" class="inline-block bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-blue-700 transition">
            Back to Checkout
        </a>
    </div>
</section>

<?php require_once "includes/footer.php"; ?>
