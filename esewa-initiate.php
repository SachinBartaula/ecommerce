<?php

require_once "config/database.php";
require_once "config/esewa.php";

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_customer_session");
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId  = (int) $_SESSION["user_id"];
$orderId = filter_input(INPUT_GET, "order_id", FILTER_VALIDATE_INT);

if (!$orderId) {
    header("Location: checkout.php");
    exit;
}

// Load the order + its eSewa payment row, making sure it belongs to this
// user and is still awaiting payment. This stops someone from re-paying
// an already-paid order, or paying for someone else's order, just by
// guessing an order_id in the URL.
$sql = "SELECT orders.id, orders.total_amount,
               payments.id AS payment_id, payments.amount AS payment_amount, payments.status AS payment_status
        FROM orders
        JOIN payments ON payments.order_id = orders.id
        WHERE orders.id = ? AND orders.user_id = ? AND orders.status = 'pending' AND payments.payment_method = 'esewa'
        LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $orderId, $userId);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$order || $order["payment_status"] !== "pending") {
    header("Location: checkout.php");
    exit;
}

if (abs((float) $order["payment_amount"] - (float) $order["total_amount"]) > 0.01) {
    header("Location: checkout.php");
    exit;
}

// Build an absolute origin (scheme + host + subfolder) so success/failure
// callback URLs work whether the app is at the domain root or in a
// subdirectory (e.g. XAMPP's /ecommerce).
$documentRoot = realpath($_SERVER["DOCUMENT_ROOT"] ?? "");
$projectRoot  = realpath(__DIR__);
$basePath = "";

if ($documentRoot && $projectRoot) {
    $documentRoot = str_replace("\\", "/", $documentRoot);
    $projectRoot  = str_replace("\\", "/", $projectRoot);
    if (strpos($projectRoot, $documentRoot) === 0) {
        $basePath = substr($projectRoot, strlen($documentRoot));
    }
}

$scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$origin = $scheme . "://" . $_SERVER["HTTP_HOST"] . rtrim($basePath, "/");

// eSewa splits the charge into amount + tax + service/delivery charges.
// This project doesn't track those separately, so the whole order total
// goes into "amount" and the rest are 0.
$amount      = number_format((float) $order["total_amount"], 2, ".", "");
$taxAmount   = "0";
$serviceCharge  = "0";
$deliveryCharge = "0";
$totalAmount = $amount;

// transaction_uuid must be unique per payment ATTEMPT (a user might retry
// after a failed/cancelled payment), so combine the order id with a
// timestamp rather than reusing the same value every time.
$transactionUuid = $orderId . "-" . bin2hex(random_bytes(8));

// Remember the uuid we're about to send so esewa-verify.php can match
// eSewa's callback back to this exact payment row.
$updateSql  = "UPDATE payments SET transaction_id = ? WHERE id = ?";
$updateStmt = mysqli_prepare($conn, $updateSql);
mysqli_stmt_bind_param($updateStmt, "si", $transactionUuid, $order["payment_id"]);
mysqli_stmt_execute($updateStmt);
mysqli_stmt_close($updateStmt);

$signedFieldNames = "total_amount,transaction_uuid,product_code";

$fields = [
    "amount" => $amount,
    "tax_amount" => $taxAmount,
    "total_amount" => $totalAmount,
    "transaction_uuid" => $transactionUuid,
    "product_code" => ESEWA_PRODUCT_CODE,
    "product_service_charge" => $serviceCharge,
    "product_delivery_charge" => $deliveryCharge,
    "success_url" => $origin . "/esewa-verify.php",
    "failure_url" => $origin . "/esewa-failure.php?order_id=" . $orderId,
    "signed_field_names" => $signedFieldNames,
];

$fields["signature"] = esewaSign($fields, $signedFieldNames);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redirecting to eSewa...</title>
</head>
<body style="font-family: system-ui, sans-serif; display:flex; align-items:center; justify-content:center; height:100vh; margin:0; background:#f8fafc;">

    <div style="text-align:center;">
        <p style="color:#475569; font-size:1rem;">Redirecting you to eSewa to complete payment&hellip;</p>
        <p style="color:#94a3b8; font-size:.85rem;">Please do not close this window.</p>
    </div>

    <form id="esewaForm" action="<?php echo htmlspecialchars(ESEWA_FORM_URL); ?>" method="POST">
        <?php foreach ($fields as $name => $value): ?>
            <input type="hidden" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>">
        <?php endforeach; ?>
    </form>

    <script>
        document.getElementById("esewaForm").submit();
    </script>

</body>
</html>
