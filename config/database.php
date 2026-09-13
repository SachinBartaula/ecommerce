<?php

// -----------------------------------------------------------------------
// Database credentials come from environment variables in production so
// they are never hardcoded or committed to the project. On InfinityFree
// (or any host without env var support in the control panel) you can
// instead create a config/db-credentials.php file OUTSIDE your web root
// (or blocked by .htaccess) that defines these constants, and require it
// here — just never commit real credentials into the project itself.
// -----------------------------------------------------------------------

$host     = getenv("DB_HOST") ?: "localhost";
$username = getenv("DB_USER") ?: "root";
$password = getenv("DB_PASSWORD") ?: "";
$database = getenv("DB_NAME") ?: "ecommerce";

$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database
);

if (!$conn) {
    // Never echo mysqli_connect_error() in production — it can leak
    // hostnames/usernames to anyone who hits a broken page.
    error_log("Database connection failed: " . mysqli_connect_error());
    http_response_code(500);
    die("We're having trouble connecting right now. Please try again shortly.");
}

mysqli_set_charset($conn, "utf8mb4");
