<?php

require_once __DIR__ . "/../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_admin_session");
    session_start();
}

if (
    isset($_SESSION["user_id"]) && (
        ($_SESSION["user_role"] ?? "") === "admin" ||
        ($_SESSION["admin_role"] ?? "") === "admin"
    )
) {
    header("Location: index.php");
    exit;
}

$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Enter your admin email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid email address.";
    } else {
        $sql = "SELECT id, name, password, role FROM users WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
        } else {
            $user = null;
        }

        if (!$user || $user["role"] !== "admin" || !password_verify($password, $user["password"])) {
            $error = "Invalid admin email or password.";
        } else {
            session_regenerate_id(true);

            unset($_SESSION["customer_id"], $_SESSION["customer_name"], $_SESSION["customer_role"]);

            $_SESSION["user_id"]   = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["user_role"] = "admin";

            $_SESSION["admin_id"]   = $user["id"];
            $_SESSION["admin_name"] = $user["name"];
            $_SESSION["admin_role"] = "admin";

            header("Location: index.php");
            exit;
        }
    }
}

$pageTitle = "Admin Login";
$requireAdmin = false;
require_once __DIR__ . "/../includes/admin-header.php";
?>

<style>
    .admin-login-page { background: #eef3f8; }
    .admin-login-card { border: 1px solid #dce4ee; border-top: 4px solid #172033; box-shadow: 0 18px 40px rgba(23, 32, 51, .1); animation: fadeInUp .55s ease-out both; }
    .admin-login-input { background: #f8fafc; border-color: #cbd5e1; transition: border-color .2s ease, box-shadow .2s ease, background .2s ease; }
    .admin-login-input:focus { background: #fff; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
    .admin-login-button { transition: background-color .2s ease, transform .2s ease; }
    .admin-login-button:hover { transform: translateY(-1px); }
    @media (prefers-reduced-motion: reduce) { .admin-login-card { animation: none; } }
</style>

<main class="admin-login-page min-h-[calc(100vh-5rem)] flex items-center justify-center px-6 py-12">
    <section class="admin-login-card w-full max-w-md rounded-xl bg-white p-8">
        <div class="mb-7">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-blue-600">Private workspace</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Admin sign in</h1>
            <p class="mt-2 text-sm text-slate-500">Access your ShopEase control room.</p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="space-y-5">
            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Admin email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="admin-login-input w-full rounded-lg border px-4 py-3 outline-none" placeholder="admin@example.com" autocomplete="email" required>
            </div>
            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                <input type="password" id="password" name="password" class="admin-login-input w-full rounded-lg border px-4 py-3 outline-none" placeholder="Your password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="admin-login-button w-full rounded-lg bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-blue-700">Sign in to dashboard</button>
        </form>

        <div class="mt-7 flex items-center justify-between border-t border-slate-100 pt-5 text-sm">
            <a href="../login.php" class="font-medium text-slate-500 hover:text-blue-600">Customer login</a>
            <a href="../index.php" class="font-medium text-slate-500 hover:text-blue-600">Back to store</a>
        </div>
    </section>
</main>

<?php require_once "../includes/footer.php"; ?>