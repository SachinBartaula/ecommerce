<?php
require_once "../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_name("shop_admin_session");
    session_start();
}

if (!isset($_SESSION["user_id"]) || ($_SESSION["user_role"] ?? "customer") !== "admin") {
    header("Location: login.php");
    exit;
}

$pageTitle = "Customers";
require_once "../includes/admin-header.php";

$sql = "SELECT id, name, email, role, created_at FROM users WHERE role = 'customer' ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
$customers = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $customers[] = $row;
    }
}
?>

<main class="min-h-[calc(100vh-5rem)] bg-slate-100 py-8">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mb-7">
            <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Users</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Customer Management</h1>
            <p class="mt-2 text-sm text-slate-500">View all registered customers in your store.</p>
        </div>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Joined</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500">No customers found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $customer): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 text-sm font-medium text-slate-900"><?php echo htmlspecialchars($customer["name"] ?? ""); ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($customer["email"] ?? ""); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            <?php echo htmlspecialchars($customer["role"] ?? "customer"); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600"><?php echo htmlspecialchars(date("M d, Y", strtotime($customer["created_at"]))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<?php require_once "../includes/footer.php"; ?>
