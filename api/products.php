<?php

require_once "../config/database.php";

header("Content-Type: application/json");


// =====================================================
// GET PRODUCTS
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $sql = "SELECT 
                products.id,
                products.name,
                products.description,
                products.price,
                products.stock,
                products.image,
                categories.name AS category
            FROM products
            LEFT JOIN categories
            ON products.category_id = categories.id
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {

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


    // -------------------------
    // IMAGE
    // -------------------------

    $image = "";


    // Uploaded image
    if (isset($_FILES["image_file"]) &&
        $_FILES["image_file"]["error"] === UPLOAD_ERR_OK) {

        $file = $_FILES["image_file"];

        $allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        if (!in_array($file["type"], $allowedTypes)) {

            echo json_encode([
                "success" => false,
                "message" => "Only JPG, PNG and WEBP images are allowed."
            ]);

            exit;
        }


        // Maximum 2MB
        if ($file["size"] > 2 * 1024 * 1024) {

            echo json_encode([
                "success" => false,
                "message" => "Image size must be less than 2MB."
            ]);

            exit;
        }


        $uploadDirectory = "../assets/images/products/";


        // Create directory if it doesn't exist
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0755, true);
        }


        $extension = pathinfo(
            $file["name"],
            PATHINFO_EXTENSION
        );


        $fileName = uniqid("product_", true) . "." . $extension;

        $uploadPath = $uploadDirectory . $fileName;


        if (!move_uploaded_file(
            $file["tmp_name"],
            $uploadPath
        )) {

            echo json_encode([
                "success" => false,
                "message" => "Failed to upload image."
            ]);

            exit;
        }


        $image = "assets/images/products/" . $fileName;
    }


    // Image URL
    elseif ($image_url !== "") {

        $image = $image_url;
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