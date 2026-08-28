<?php

require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in? No need to register again.
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$errors = [];
$name   = "";
$email  = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name            = trim($_POST["name"] ?? "");
    $email           = trim($_POST["email"] ?? "");
    $password        = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";


    // -------------------------
    // VALIDATION
    // -------------------------

    if ($name === "") {
        $errors[] = "Name is required.";
    } elseif (strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters.";
    }

    if ($email === "") {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if ($password === "") {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }


    // Check for duplicate email
    if (empty($errors)) {

        $checkSql = "SELECT id FROM users WHERE email = ?";
        $checkStmt = mysqli_prepare($conn, $checkSql);
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);

        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            $errors[] = "An account with this email already exists.";
        }

        mysqli_stmt_close($checkStmt);
    }


    // -------------------------
    // INSERT USER
    // -------------------------

    if (empty($errors)) {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (name, email, password, role)
                VALUES (?, ?, ?, 'customer')";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashedPassword);

        if (mysqli_stmt_execute($stmt)) {

            // Log the user in immediately after registering
            $_SESSION["user_id"]   = mysqli_insert_id($conn);
            $_SESSION["user_name"] = $name;
            $_SESSION["user_role"] = "customer";

            mysqli_stmt_close($stmt);

            header("Location: index.php");
            exit;

        } else {
            $errors[] = "Something went wrong. Please try again.";
        }

        mysqli_stmt_close($stmt);
    }
}

$pageTitle = "Register";
require_once "includes/header.php";
?>

<style>
    .auth-page {
        position: relative;
        overflow: hidden;
        background: #f8fafc;
    }

    .auth-page::before,
    .auth-page::after {
        content: "";
        position: absolute;
        width: 11rem;
        height: 11rem;
        border: 1.5rem solid rgba(37, 99, 235, .07);
        border-radius: 999px;
        pointer-events: none;
        animation: authFloat 8s ease-in-out infinite;
    }

    .auth-page::before { top: 8%; left: -4rem; }
    .auth-page::after {
        right: -3rem;
        bottom: 8%;
        border-color: rgba(14, 165, 233, .08);
        animation-delay: -4s;
    }

    .auth-card {
        animation: fadeInUp .55s ease-out both;
        border: 1px solid #e2e8f0;
        border-top: 4px solid #2563eb;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .08);
    }

    .auth-card form > div { animation: fadeInUp .45s ease-out both; }
    .auth-card form > div:nth-child(2) { animation-delay: .08s; }
    .auth-card form > div:nth-child(3) { animation-delay: .16s; }
    .auth-card form > div:nth-child(4) { animation-delay: .24s; }

    .auth-input {
        background: #f8fafc;
        border-color: #cbd5e1;
        transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
    }

    .auth-input:focus {
        background: #fff;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
    }

    .auth-button { transition: background-color .2s ease, transform .2s ease; }
    .auth-button:hover { transform: translateY(-1px); }

    @keyframes authFloat {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-16px) rotate(8deg); }
    }

    @media (prefers-reduced-motion: reduce) {
        .auth-page::before, .auth-page::after, .auth-card,
        .auth-card form > div { animation: none; }
    }
</style>

<div class="auth-page min-h-[calc(100vh-5rem)] flex items-center justify-center px-6 py-12">

    <div class="auth-card w-full max-w-md bg-white rounded-xl p-8">

        <div class="mb-7">
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 mb-2">Get started</p>
            <h1 class="text-2xl font-bold text-gray-800">Create an Account</h1>
            <p class="text-sm text-gray-500 mt-2">Join ShopEase and start exploring.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-6">
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="space-y-5">

            <div>
                <label for="name" class="block text-sm font-medium mb-2">Full Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?php echo htmlspecialchars($name); ?>"
                    class="auth-input w-full border rounded-lg px-4 py-3 focus:outline-none"
                    placeholder="Full Name">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium mb-2">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($email); ?>"
                    class="auth-input w-full border rounded-lg px-4 py-3 focus:outline-none"
                    placeholder="Your Email">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium mb-2">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="auth-input w-full border rounded-lg px-4 py-3 focus:outline-none"
                    placeholder="At least 6 characters">
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-medium mb-2">Confirm Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    class="auth-input w-full border rounded-lg px-4 py-3 focus:outline-none"
                    placeholder="Re-enter your password">
            </div>

            <button
                type="submit"
                class="auth-button w-full bg-blue-600 text-white font-medium rounded-lg px-4 py-3 hover:bg-blue-700">
                Create Account
            </button>

        </form>

        <p class="text-sm text-gray-500 mt-6 text-center">
            Already have an account?
            <a href="login.php" class="text-blue-600 hover:underline">Log in</a>
        </p>

    </div>

</div>

<?php require_once "includes/footer.php"; ?>