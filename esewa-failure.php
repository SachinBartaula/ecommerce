<?php

require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_customer_session");
    session_start();
}

$orderId = filter_input(INPUT_GET, "order_id", FILTER_VALIDATE_INT);

if (isset($_SESSION["user_id"]) && $orderId) {
    $userId = (int) $_SESSION["user_id"];

    // Mark the pending eSewa payment as failed so the admin panel reflects
    // it and the customer is free to retry from checkout.
    $sql = "UPDATE payments
            JOIN orders ON orders.id = payments.order_id
            SET payments.status = 'failed'
            WHERE payments.order_id = ?
              AND orders.user_id = ?
              AND payments.payment_method = 'esewa'
              AND payments.status = 'pending'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $orderId, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
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
