<?php
require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_customer_session");
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$errors = [];
$successMessage = "";

if (empty($_SESSION["settings_csrf"])) {
    $_SESSION["settings_csrf"] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION["settings_csrf"];

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function redirectWithMessage(string $status, string $message): void
{
    header("Location: settings.php?status=" . urlencode($status) . "&message=" . urlencode($message));
    exit;
}

$sql = "SELECT id, name, email, role, created_at FROM users WHERE id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $postedToken = $_POST["csrf_token"] ?? "";

    if (!hash_equals($csrfToken, $postedToken)) {
        $errors[] = "Your session has expired. Please refresh the page and try again.";
    } elseif ($action === "update_profile") {
        $name = trim($_POST["name"] ?? "");

        if ($name === "") {
            $errors[] = "Name is required.";
        } elseif (strlen($name) < 2) {
            $errors[] = "Name must be at least 2 characters long.";
        } elseif (strlen($name) > 100) {
            $errors[] = "Name cannot be longer than 100 characters.";
        } elseif (!preg_match("/^[A-Za-z][A-Za-z\s'.-]*$/", $name)) {
            $errors[] = "Name can contain letters, spaces, apostrophes, periods and hyphens only.";
        }

        if (empty($errors)) {
            $updateSql = "UPDATE users SET name = ? WHERE id = ?";
            $updateStmt = mysqli_prepare($conn, $updateSql);
            mysqli_stmt_bind_param($updateStmt, "si", $name, $userId);

            if (mysqli_stmt_execute($updateStmt)) {
                mysqli_stmt_close($updateStmt);
                $_SESSION["user_name"] = $name;
                $_SESSION["customer_name"] = $name;
                redirectWithMessage("success", "Your name was updated successfully.");
            }

            mysqli_stmt_close($updateStmt);
            $errors[] = "Unable to update your name. Please try again.";
        }
    } elseif ($action === "change_password") {
        $currentPassword = $_POST["current_password"] ?? "";
        $newPassword = $_POST["new_password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";

        if ($currentPassword === "") {
            $errors[] = "Current password is required.";
        }

        if ($newPassword === "") {
            $errors[] = "New password is required.";
        } elseif (strlen($newPassword) < 8) {
            $errors[] = "New password must be at least 8 characters long.";
        } elseif (strlen($newPassword) > 72) {
            $errors[] = "New password cannot be longer than 72 characters.";
        } elseif (!preg_match('/[A-Z]/', $newPassword) ||
                  !preg_match('/[a-z]/', $newPassword) ||
                  !preg_match('/[0-9]/', $newPassword) ||
                  !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
            $errors[] = "Password must contain uppercase, lowercase, number and special character.";
        }

        if ($confirmPassword === "") {
            $errors[] = "Please confirm your new password.";
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = "New password and confirmation password do not match.";
        }

        if ($currentPassword !== "" && $newPassword !== "" && hash_equals($currentPassword, $newPassword)) {
            $errors[] = "New password must be different from your current password.";
        }

        if (empty($errors)) {
            $passwordSql = "SELECT password FROM users WHERE id = ? LIMIT 1";
            $passwordStmt = mysqli_prepare($conn, $passwordSql);
            mysqli_stmt_bind_param($passwordStmt, "i", $userId);
            mysqli_stmt_execute($passwordStmt);
            $passwordResult = mysqli_stmt_get_result($passwordStmt);
            $passwordRow = mysqli_fetch_assoc($passwordResult);
            mysqli_stmt_close($passwordStmt);

            if (!$passwordRow || !password_verify($currentPassword, $passwordRow["password"])) {
                $errors[] = "Current password is incorrect.";
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatePasswordSql = "UPDATE users SET password = ? WHERE id = ?";
                $updatePasswordStmt = mysqli_prepare($conn, $updatePasswordSql);
                mysqli_stmt_bind_param($updatePasswordStmt, "si", $hashedPassword, $userId);

                if (mysqli_stmt_execute($updatePasswordStmt)) {
                    mysqli_stmt_close($updatePasswordStmt);
                    redirectWithMessage("success", "Password changed successfully.");
                }

                mysqli_stmt_close($updatePasswordStmt);
                $errors[] = "Unable to change your password. Please try again.";
            }
        }
    } else {
        $errors[] = "Invalid settings request.";
    }

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result) ?: $user;
    mysqli_stmt_close($stmt);
}

if (isset($_GET["message"])) {
    $message = trim(urldecode($_GET["message"]));
    if (($_GET["status"] ?? "") === "success") {
        $successMessage = $message;
    } elseif ($message !== "") {
        $errors[] = $message;
    }
}

$pageTitle = "Settings";
require_once "includes/header.php";
?>

<style>
    .settings-card { animation: fadeInUp .5s ease-out both; }
    .settings-input { transition: border-color .2s ease, box-shadow .2s ease, background .2s ease; }
    .settings-input:focus { background: #fff; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); outline: none; }
    .settings-error { min-height: 1.25rem; }
</style>

<main class="min-h-[calc(100vh-5rem)] bg-slate-50 py-10">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Account</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Settings</h1>
                <p class="mt-2 text-slate-500">Update your personal information and password.</p>
            </div>
            <a href="profile.php" class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">← Back to Profile</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                <ul class="list-disc space-y-1 pl-5">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($successMessage !== ""): ?>
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
                <?php echo e($successMessage); ?>
            </div>
        <?php endif; ?>

        <div class="grid gap-8 lg:grid-cols-2">
            <section class="settings-card rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Personal Information</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Update Name</h2>
                    <p class="mt-2 text-sm text-slate-500">Your email address cannot be changed here.</p>
                </div>

                <form id="profileSettingsForm" method="POST" action="settings.php" novalidate>
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

                    <div class="mb-5">
                        <label for="name" class="mb-2 block text-sm font-medium text-slate-700">Full Name</label>
                        <input type="text" id="name" name="name" maxlength="100" value="<?php echo e($user["name"]); ?>" autocomplete="name" class="settings-input w-full rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-slate-900" required>
                        <p id="nameError" class="settings-error mt-1 text-xs text-red-600"></p>
                    </div>

                    <div class="mb-5">
                        <label class="mb-2 block text-sm font-medium text-slate-700">Email Address</label>
                        <input type="email" value="<?php echo e($user["email"]); ?>" class="w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-4 py-3 text-slate-500" disabled>
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white transition hover:bg-blue-700">Save Name</button>
                </form>
            </section>

            <section class="settings-card rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Security</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Change Password</h2>
                    <p class="mt-2 text-sm text-slate-500">Use a strong password that you do not reuse elsewhere.</p>
                </div>

                <form id="passwordSettingsForm" method="POST" action="settings.php" novalidate>
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

                    <div class="mb-5">
                        <label for="current_password" class="mb-2 block text-sm font-medium text-slate-700">Current Password</label>
                        <input type="password" id="current_password" name="current_password" autocomplete="current-password" class="settings-input w-full rounded-lg border border-slate-300 bg-slate-50 px-4 py-3" required>
                        <p id="currentPasswordError" class="settings-error mt-1 text-xs text-red-600"></p>
                    </div>

                    <div class="mb-5">
                        <label for="new_password" class="mb-2 block text-sm font-medium text-slate-700">New Password</label>
                        <input type="password" id="new_password" name="new_password" maxlength="72" autocomplete="new-password" class="settings-input w-full rounded-lg border border-slate-300 bg-slate-50 px-4 py-3" required>
                        <p class="mt-2 text-xs text-slate-500">8–72 characters with uppercase, lowercase, number and special character.</p>
                        <p id="newPasswordError" class="settings-error mt-1 text-xs text-red-600"></p>
                    </div>

                    <div class="mb-6">
                        <label for="confirm_password" class="mb-2 block text-sm font-medium text-slate-700">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" maxlength="72" autocomplete="new-password" class="settings-input w-full rounded-lg border border-slate-300 bg-slate-50 px-4 py-3" required>
                        <p id="confirmPasswordError" class="settings-error mt-1 text-xs text-red-600"></p>
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white transition hover:bg-slate-800">Change Password</button>
                </form>
            </section>
        </div>
    </div>
</main>

<script>
(() => {
    const profileForm = document.getElementById("profileSettingsForm");
    const passwordForm = document.getElementById("passwordSettingsForm");
    const nameInput = document.getElementById("name");

    const setError = (id, message) => {
        const element = document.getElementById(id);
        if (element) element.textContent = message;
    };

    const clearErrors = (ids) => ids.forEach(id => setError(id, ""));

    const validateName = () => {
        const value = nameInput.value.trim();
        if (!value) return "Name is required.";
        if (value.length < 2) return "Name must be at least 2 characters long.";
        if (value.length > 100) return "Name cannot be longer than 100 characters.";
        if (!/^[A-Za-z][A-Za-z\s'.-]*$/.test(value)) return "Name can contain letters, spaces, apostrophes, periods and hyphens only.";
        return "";
    };

    const validatePassword = (value) => {
        if (!value) return "New password is required.";
        if (value.length < 8) return "New password must be at least 8 characters long.";
        if (value.length > 72) return "New password cannot be longer than 72 characters.";
        if (!/[A-Z]/.test(value)) return "Add at least one uppercase letter.";
        if (!/[a-z]/.test(value)) return "Add at least one lowercase letter.";
        if (!/[0-9]/.test(value)) return "Add at least one number.";
        if (!/[^A-Za-z0-9]/.test(value)) return "Add at least one special character.";
        return "";
    };

    if (profileForm) {
        profileForm.addEventListener("submit", (event) => {
            clearErrors(["nameError"]);
            const error = validateName();
            if (error) {
                setError("nameError", error);
                event.preventDefault();
            }
        });
    }

    if (nameInput) {
        nameInput.addEventListener("input", () => setError("nameError", ""));
    }

    if (passwordForm) {
        passwordForm.addEventListener("submit", (event) => {
            clearErrors(["currentPasswordError", "newPasswordError", "confirmPasswordError"]);

            const current = document.getElementById("current_password").value;
            const newPassword = document.getElementById("new_password").value;
            const confirm = document.getElementById("confirm_password").value;
            let valid = true;

            if (!current) {
                setError("currentPasswordError", "Current password is required.");
                valid = false;
            }

            const passwordError = validatePassword(newPassword);
            if (passwordError) {
                setError("newPasswordError", passwordError);
                valid = false;
            } else if (current && current === newPassword) {
                setError("newPasswordError", "New password must be different from your current password.");
                valid = false;
            }

            if (!confirm) {
                setError("confirmPasswordError", "Please confirm your new password.");
                valid = false;
            } else if (newPassword !== confirm) {
                setError("confirmPasswordError", "Passwords do not match.");
                valid = false;
            }

            if (!valid) event.preventDefault();
        });
    }

    ["current_password", "new_password", "confirm_password"].forEach(id => {
        const input = document.getElementById(id);
        if (input) input.addEventListener("input", () => {
            const errorMap = {
                current_password: "currentPasswordError",
                new_password: "newPasswordError",
                confirm_password: "confirmPasswordError"
            };
            setError(errorMap[id], "");
        });
    });
})();
</script>

<?php require_once "includes/footer.php"; ?>
