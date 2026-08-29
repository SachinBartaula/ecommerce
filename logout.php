<?php

$role = $_GET["role"] ?? "customer";
$sessionName = $role === "admin" ? "shop_admin_session" : "shop_customer_session";
$redirectTo = $role === "admin" ? "admin/login.php" : "login.php";

session_name($sessionName);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header("Location: " . $redirectTo);
exit;