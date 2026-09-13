<?php

require_once __DIR__ . "/../config/database.php";

/**
 * Safely validate and store an uploaded product image.
 *
 * Never trusts the client-supplied MIME type or original filename/extension
 * (both are attacker-controlled). Instead it inspects the actual file bytes
 * with getimagesize() and always writes a fixed, server-chosen extension,
 * which prevents disguised PHP/HTML files from being stored as "images"
 * (a classic path to remote code execution via file upload).
 *
 * @return array{ok:bool, path?:string, error?:string}
 */
function handleProductImageUpload(array $file): array
{
    $allowedExtensionsByType = [
        IMAGETYPE_JPEG => "jpg",
        IMAGETYPE_PNG  => "png",
        IMAGETYPE_WEBP => "webp",
    ];

    if ($file["size"] > 2 * 1024 * 1024) {
        return ["ok" => false, "error" => "Use an image smaller than 2MB."];
    }

    // getimagesize() reads the actual file header, so a renamed .php file
    // with a spoofed Content-Type will fail here even though the client
    // claimed it was a JPEG.
    $imageInfo = @getimagesize($file["tmp_name"]);
    if ($imageInfo === false || !isset($allowedExtensionsByType[$imageInfo[2]])) {
        return ["ok" => false, "error" => "Use a real JPG, PNG or WEBP image."];
    }

    $extension = $allowedExtensionsByType[$imageInfo[2]];
    $uploadDirectory = __DIR__ . "/../assets/images/products/";
    if (!is_dir($uploadDirectory)) {
        mkdir($uploadDirectory, 0755, true);
    }

    $fileName = uniqid("product_", true) . "." . $extension;
    if (!move_uploaded_file($file["tmp_name"], $uploadDirectory . $fileName)) {
        return ["ok" => false, "error" => "Failed to upload image."];
    }

    return ["ok" => true, "path" => "assets/images/products/" . $fileName];
}

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_admin_session");
    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "secure" => isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "httponly" => true,
        "samesite" => "Lax",
    ]);
    session_start();
}

header("Content-Type: application/json");
$requestMethod = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($requestMethod !== "GET" && (!isset($_SESSION["user_id"]) || ($_SESSION["user_role"] ?? "customer") !== "admin")) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Admin access is required."
    ]);
    exit;
}

if ($requestMethod === "DELETE") {
    $productId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

    if (!$productId) {
        echo json_encode(["success" => false, "message" => "A valid product ID is required."]);
        exit;
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) === 0) {
        mysqli_stmt_close($stmt);
        echo json_encode(["success" => false, "message" => "Product not found."]);
        exit;
    }

    mysqli_stmt_close($stmt);
    echo json_encode(["success" => true, "message" => "Product deleted successfully."]);
    exit;
}

if ($requestMethod === "POST" && ($_POST["action"] ?? "") === "update") {
    $productId = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);
    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = $_POST["price"] ?? "";
    $stock = $_POST["stock"] ?? "";
    $categoryId = filter_var($_POST["category_id"] ?? "", FILTER_VALIDATE_INT);
    $imageUrl = trim($_POST["image_url"] ?? "");

    if (!$productId || $name === "" || strlen($name) < 3 || !is_numeric($price) || $price <= 0 || $stock === "" || !is_numeric($stock) || $stock < 0 || !$categoryId || $description === "" || strlen($description) < 10) {
        echo json_encode(["success" => false, "message" => "Please provide valid product details."]);
        exit;
    }

    $currentStmt = mysqli_prepare($conn, "SELECT image FROM products WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($currentStmt, "i", $productId);
    mysqli_stmt_execute($currentStmt);
    $currentResult = mysqli_stmt_get_result($currentStmt);
    $currentProduct = mysqli_fetch_assoc($currentResult);
    mysqli_stmt_close($currentStmt);

    if (!$currentProduct) {
        echo json_encode(["success" => false, "message" => "Product not found."]);
        exit;
    }

    $image = $currentProduct["image"] ?? "";
    if (isset($_FILES["image_file"]) && $_FILES["image_file"]["error"] === UPLOAD_ERR_OK) {
        $uploadResult = handleProductImageUpload($_FILES["image_file"]);

        if (!$uploadResult["ok"]) {
            echo json_encode(["success" => false, "message" => $uploadResult["error"]]);
            exit;
        }

        $image = $uploadResult["path"];
    } elseif ($imageUrl !== "") {
        $image = $imageUrl;
    }

    $stmt = mysqli_prepare($conn, "UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "issdisi", $categoryId, $name, $description, $price, $stock, $image, $productId);
    $updated = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode([
        "success" => $updated,
        "message" => $updated ? "Product updated successfully." : "Failed to update product."
    ]);
    exit;
}


// =====================================================
// GET PRODUCTS
// =====================================================

if ($requestMethod === "GET") {

    $sql = "SELECT 
                products.id,
                products.name,
                products.description,
                products.price,
                products.stock,
                products.image,
                categories.name AS category,
                COALESCE(ROUND(AVG(reviews.rating), 1), 0) AS average_rating,
                COUNT(reviews.id) AS review_count
            FROM products
            LEFT JOIN categories
                ON products.category_id = categories.id
            LEFT JOIN reviews
                ON reviews.product_id = products.id
            GROUP BY
                products.id,
                products.name,
                products.description,
                products.price,
                products.stock,
                products.image,
                categories.name
            ORDER BY products.id DESC";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        echo json_encode([
            "success" => false,
            "message" => mysqli_error($conn)
        ]);
        exit;
    }

    $products = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }

    echo json_encode([
        "success" => true,
        "data" => $products
    ]);

    exit;
}


// =====================================================
// ADD PRODUCT
// =====================================================

if ($requestMethod === "POST") {

    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = $_POST["price"] ?? "";
    $stock = $_POST["stock"] ?? "";
    $category_id = $_POST["category_id"] ?? "";
    $image_url = trim($_POST["image_url"] ?? "");


    // -------------------------
    // SERVER-SIDE VALIDATION
    // -------------------------

    if ($name === "") {
        echo json_encode([
            "success" => false,
            "message" => "Product name is required."
        ]);
        exit;
    }

    if (strlen($name) < 3) {
        echo json_encode([
            "success" => false,
            "message" => "Product name must be at least 3 characters."
        ]);
        exit;
    }

    if (!preg_match('/^[A-Za-z][A-Za-z0-9\s&\'\(\)\.\/-]*$/', $name)) {
        echo json_encode([
            "success" => false,
            "message" => "Product name must start with a letter and use valid characters only."
        ]);
        exit;
    }

    if (!is_numeric($price) || $price <= 0) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid price."
        ]);
        exit;
    }

    if (!is_numeric($stock) || $stock < 0) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid stock."
        ]);
        exit;
    }

    if ($category_id === "") {
        echo json_encode([
            "success" => false,
            "message" => "Category is required."
        ]);
        exit;
    }

    if ($description === "" || strlen($description) < 10) {
        echo json_encode([
            "success" => false,
            "message" => "Description must be at least 10 characters."
        ]);
        exit;
    }


    // -------------------------
    // IMAGE
    // -------------------------

    $image = "";


    // Uploaded image
    if (isset($_FILES["image_file"]) &&
        $_FILES["image_file"]["error"] === UPLOAD_ERR_OK) {

        $uploadResult = handleProductImageUpload($_FILES["image_file"]);

        if (!$uploadResult["ok"]) {
            echo json_encode([
                "success" => false,
                "message" => $uploadResult["error"]
            ]);
            exit;
        }

        $image = $uploadResult["path"];
    }


    // Image URL
    elseif ($image_url !== "") {

        $image = $image_url;
    }

    else {
        echo json_encode([
            "success" => false,
            "message" => "Please provide an image URL or upload an image."
        ]);
        exit;
    }


    // -------------------------
    // INSERT PRODUCT
    // -------------------------

    $sql = "INSERT INTO products
            (category_id, name, description, price, stock, image)
            VALUES (?, ?, ?, ?, ?, ?)";


    $stmt = mysqli_prepare($conn, $sql);


    if (!$stmt) {

        echo json_encode([
            "success" => false,
            "message" => "Database error."
        ]);

        exit;
    }


    mysqli_stmt_bind_param(
        $stmt,
        "issdis",
        $category_id,
        $name,
        $description,
        $price,
        $stock,
        $image
    );


    if (mysqli_stmt_execute($stmt)) {

        echo json_encode([
            "success" => true,
            "message" => "Product added successfully.",
            "product_id" => mysqli_insert_id($conn)
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Failed to add product."
        ]);
    }


    mysqli_stmt_close($stmt);

    exit;
}


// =====================================================
// INVALID REQUEST
// =====================================================

echo json_encode([
    "success" => false,
    "message" => "Invalid request method."
]);