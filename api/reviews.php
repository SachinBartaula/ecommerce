<?php
// Product Reviews API
// GET    ?product_id=ID              -> review summary + reviews + current user's review status
// POST   action=create/update        -> create or update current user's review
// POST   action=delete               -> delete current user's review

ob_start();
error_reporting(E_ALL);
ini_set("display_errors", "0");

require_once __DIR__ . "/../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_customer_session");
    session_start();
}

ob_clean();
header("Content-Type: application/json; charset=utf-8");

function respond(array $payload, int $status = 200): void {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function currentUserId(): int {
    return (int) ($_SESSION["user_id"] ?? 0);
}

function validateProduct(int $productId): bool {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT id FROM products WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = (bool) mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $exists;
}

function hasDeliveredPurchase(int $userId, int $productId): bool {
    global $conn;
    $sql = "SELECT oi.id
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            WHERE o.user_id = ?
              AND oi.product_id = ?
              AND o.status = 'delivered'
            LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, "ii", $userId, $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $found = (bool) mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $found;
}

function getReviewPayload(int $productId, int $userId = 0): array {
    global $conn;

    $summaryStmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS review_count, COALESCE(AVG(rating), 0) AS average_rating
         FROM reviews WHERE product_id = ?"
    );
    mysqli_stmt_bind_param($summaryStmt, "i", $productId);
    mysqli_stmt_execute($summaryStmt);
    $summaryResult = mysqli_stmt_get_result($summaryStmt);
    $summary = mysqli_fetch_assoc($summaryResult) ?: ["review_count" => 0, "average_rating" => 0];
    mysqli_stmt_close($summaryStmt);

    $reviews = [];
    $reviewStmt = mysqli_prepare(
        $conn,
        "SELECT r.id, r.rating, r.review, r.created_at, r.updated_at,
                u.id AS user_id, u.name AS user_name
         FROM reviews r
         INNER JOIN users u ON u.id = r.user_id
         WHERE r.product_id = ?
         ORDER BY r.created_at DESC"
    );
    mysqli_stmt_bind_param($reviewStmt, "i", $productId);
    mysqli_stmt_execute($reviewStmt);
    $reviewResult = mysqli_stmt_get_result($reviewStmt);

    while ($row = mysqli_fetch_assoc($reviewResult)) {
        $createdTimestamp = strtotime($row["created_at"] ?? "now");
        $updatedTimestamp = strtotime($row["updated_at"] ?? $row["created_at"] ?? "now");
        $reviews[] = [
            "id" => (int) $row["id"],
            "rating" => (int) $row["rating"],
            "review" => $row["review"],
            "user_name" => $row["user_name"],
            "verified_purchase" => true,
            "created_at" => date("M d, Y", $createdTimestamp),
            "updated" => $updatedTimestamp > $createdTimestamp,
            "is_owner" => $userId > 0 && (int) $row["user_id"] === $userId
        ];
    }
    mysqli_stmt_close($reviewStmt);

    $myReview = null;
    if ($userId > 0) {
        $myStmt = mysqli_prepare(
            $conn,
            "SELECT id, rating, review FROM reviews WHERE user_id = ? AND product_id = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($myStmt, "ii", $userId, $productId);
        mysqli_stmt_execute($myStmt);
        $myResult = mysqli_stmt_get_result($myStmt);
        $myReview = mysqli_fetch_assoc($myResult) ?: null;
        mysqli_stmt_close($myStmt);
        if ($myReview) {
            $myReview = [
                "id" => (int) $myReview["id"],
                "rating" => (int) $myReview["rating"],
                "review" => $myReview["review"]
            ];
        }
    }

    return [
        "review_count" => (int) $summary["review_count"],
        "average_rating" => round((float) $summary["average_rating"], 1),
        "reviews" => $reviews,
        "my_review" => $myReview,
        "can_review" => $userId > 0 && hasDeliveredPurchase($userId, $productId) && $myReview === null
    ];
}

$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($method === "GET") {
    $productId = filter_input(INPUT_GET, "product_id", FILTER_VALIDATE_INT);
    if (!$productId || $productId < 1) {
        respond(["success" => false, "message" => "Invalid product."] , 400);
    }

    if (!validateProduct((int) $productId)) {
        respond(["success" => false, "message" => "Product not found."], 404);
    }

    $userId = currentUserId();
    respond(["success" => true, "data" => getReviewPayload((int) $productId, $userId)]);
}

if ($method === "POST") {
    $userId = currentUserId();
    if ($userId < 1) {
        respond(["success" => false, "message" => "Please log in to manage reviews."], 401);
    }

    $action = strtolower(trim($_POST["action"] ?? ""));
    $productId = filter_var($_POST["product_id"] ?? "", FILTER_VALIDATE_INT);

    if (!$productId || $productId < 1 || !validateProduct((int) $productId)) {
        respond(["success" => false, "message" => "Invalid product."], 400);
    }

    if (!hasDeliveredPurchase($userId, (int) $productId)) {
        respond(["success" => false, "message" => "You can review this product after your order has been delivered."], 403);
    }

    if ($action === "create" || $action === "update") {
        $rating = filter_var($_POST["rating"] ?? "", FILTER_VALIDATE_INT);
        $review = trim($_POST["review"] ?? "");

        if ($rating === false || $rating < 1 || $rating > 5) {
            respond(["success" => false, "message" => "Please select a rating from 1 to 5 stars."], 422);
        }

        if ($review === "") {
            respond(["success" => false, "message" => "Please write a review.", "field" => "review"], 422);
        }

        if (mb_strlen($review) < 10) {
            respond(["success" => false, "message" => "Your review should be at least 10 characters.", "field" => "review"], 422);
        }

        if (mb_strlen($review) > 1000) {
            respond(["success" => false, "message" => "Your review cannot exceed 1000 characters.", "field" => "review"], 422);
        }

        $existingStmt = mysqli_prepare($conn, "SELECT id FROM reviews WHERE user_id = ? AND product_id = ? LIMIT 1");
        mysqli_stmt_bind_param($existingStmt, "ii", $userId, $productId);
        mysqli_stmt_execute($existingStmt);
        $existingResult = mysqli_stmt_get_result($existingStmt);
        $existing = mysqli_fetch_assoc($existingResult);
        mysqli_stmt_close($existingStmt);

        if ($action === "create" && $existing) {
            respond(["success" => false, "message" => "You have already reviewed this product. You can edit your existing review."], 409);
        }

        if ($action === "update") {
            if (!$existing) {
                respond(["success" => false, "message" => "Your review was not found."], 404);
            }

            $stmt = mysqli_prepare($conn, "UPDATE reviews SET rating = ?, review = ? WHERE id = ? AND user_id = ? AND product_id = ?");
            mysqli_stmt_bind_param($stmt, "isiii", $rating, $review, $existing["id"], $userId, $productId);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if (!$ok) {
                respond(["success" => false, "message" => "Unable to update your review."], 500);
            }

            respond([
                "success" => true,
                "message" => "Your review has been updated.",
                "data" => getReviewPayload((int) $productId, $userId)
            ]);
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO reviews (user_id, product_id, rating, review) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiis", $userId, $productId, $rating, $review);
        $ok = mysqli_stmt_execute($stmt);
        $errorNumber = mysqli_errno($conn);
        mysqli_stmt_close($stmt);

        if (!$ok) {
            if ($errorNumber === 1062) {
                respond(["success" => false, "message" => "You have already reviewed this product."], 409);
            }
            respond(["success" => false, "message" => "Unable to save your review."], 500);
        }

        respond([
            "success" => true,
            "message" => "Thank you! Your review has been submitted.",
            "data" => getReviewPayload((int) $productId, $userId)
        ]);
    }

    if ($action === "delete") {
        $reviewId = filter_var($_POST["review_id"] ?? "", FILTER_VALIDATE_INT);
        if (!$reviewId || $reviewId < 1) {
            respond(["success" => false, "message" => "Invalid review."], 400);
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM reviews WHERE id = ? AND user_id = ? AND product_id = ?");
        mysqli_stmt_bind_param($stmt, "iii", $reviewId, $userId, $productId);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($affected < 1) {
            respond(["success" => false, "message" => "Review not found or you do not have permission to delete it."], 404);
        }

        respond([
            "success" => true,
            "message" => "Your review has been deleted.",
            "data" => getReviewPayload((int) $productId, $userId)
        ]);
    }

    respond(["success" => false, "message" => "Invalid review action."], 400);
}

respond(["success" => false, "message" => "Method not allowed."], 405);
