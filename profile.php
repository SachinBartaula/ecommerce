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

$userId = (int) ($_SESSION["user_id"] ?? 0);
$userName = $_SESSION["user_name"] ?? "";
$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "save_address") {
        $addressId = (int) ($_POST["address_id"] ?? 0);
        $label = trim($_POST["label"] ?? "Home");
        $fullName = trim($_POST["full_name"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $address = trim($_POST["address"] ?? "");
        $city = trim($_POST["city"] ?? "");
        $postalCode = trim($_POST["postal_code"] ?? "");
        $isDefault = isset($_POST["is_default"]) ? 1 : 0;

        $label = $label === "" ? "Home" : $label;

        if ($fullName === "") {
            $errorMessage = "Full name is required.";
        } elseif (strlen($fullName) < 2) {
            $errorMessage = "Full name must be at least 2 characters long.";
        } elseif (!preg_match('/^[A-Za-z][A-Za-z\s\'.-]{1,49}$/', $fullName)) {
            $errorMessage = "Full name must start with a letter and use valid characters only.";
        } elseif ($phone === "") {
            $errorMessage = "Phone number is required.";
        } elseif (!preg_match('/^[0-9+()\-\s]{7,20}$/', $phone)) {
            $errorMessage = "Phone number format is invalid.";
        } elseif ($address === "") {
            $errorMessage = "Street address is required.";
        } elseif (strlen($address) < 5) {
            $errorMessage = "Street address is too short.";
        } elseif ($city === "") {
            $errorMessage = "City is required.";
        } elseif (strlen($city) < 2) {
            $errorMessage = "City name is too short.";
        } elseif ($postalCode !== "" && !preg_match('/^[A-Za-z0-9\- ]{3,20}$/', $postalCode)) {
            $errorMessage = "Postal code format is invalid.";
        }

        if ($errorMessage === "") {
            if ($isDefault) {
                $defaultSql = "UPDATE shipping_addresses SET is_default = 0 WHERE user_id = ?";
                $defaultStmt = mysqli_prepare($conn, $defaultSql);
                mysqli_stmt_bind_param($defaultStmt, "i", $userId);
                mysqli_stmt_execute($defaultStmt);
                mysqli_stmt_close($defaultStmt);
            }

            if ($addressId > 0) {
                $sql = "UPDATE shipping_addresses SET label = ?, full_name = ?, phone = ?, address = ?, city = ?, postal_code = ?, is_default = ? WHERE id = ? AND user_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssssssiii", $label, $fullName, $phone, $address, $city, $postalCode, $isDefault, $addressId, $userId);
            } else {
                $sql = "INSERT INTO shipping_addresses (user_id, label, full_name, phone, address, city, postal_code, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "issssssi", $userId, $label, $fullName, $phone, $address, $city, $postalCode, $isDefault);
            }

            if ($stmt && mysqli_stmt_execute($stmt)) {
                $successMessage = $addressId > 0 ? "Shipping address updated successfully." : "Shipping address added successfully.";
            } else {
                $errorMessage = "Unable to save shipping address.";
            }

            if ($stmt) {
                mysqli_stmt_close($stmt);
            }
        }
    }

    if ($action === "delete_address") {
        $addressId = (int) ($_POST["address_id"] ?? 0);
        if ($addressId > 0) {
            $deleteSql = "DELETE FROM shipping_addresses WHERE id = ? AND user_id = ?";
            $deleteStmt = mysqli_prepare($conn, $deleteSql);
            mysqli_stmt_bind_param($deleteStmt, "ii", $addressId, $userId);
            if (mysqli_stmt_execute($deleteStmt)) {
                $successMessage = "Shipping address deleted.";

                $checkSql = "SELECT id FROM shipping_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC LIMIT 1";
                $checkStmt = mysqli_prepare($conn, $checkSql);
                mysqli_stmt_bind_param($checkStmt, "i", $userId);
                mysqli_stmt_execute($checkStmt);
                $defaultResult = mysqli_stmt_get_result($checkStmt);
                $defaultRow = mysqli_fetch_assoc($defaultResult);
                mysqli_stmt_close($checkStmt);

                if ($defaultRow && $defaultRow["id"] != $addressId) {
                    $resetSql = "UPDATE shipping_addresses SET is_default = 1 WHERE id = ? AND user_id = ?";
                    $resetStmt = mysqli_prepare($conn, $resetSql);
                    mysqli_stmt_bind_param($resetStmt, "ii", $defaultRow["id"], $userId);
                    mysqli_stmt_execute($resetStmt);
                    mysqli_stmt_close($resetStmt);
                }
            } else {
                $errorMessage = "Unable to delete shipping address.";
            }
            mysqli_stmt_close($deleteStmt);
        }
    }

    if ($errorMessage !== "" || $successMessage !== "") {
        $query = [
            "status" => $errorMessage !== "" ? "error" : "success",
            "message" => urlencode($errorMessage !== "" ? $errorMessage : $successMessage),
        ];
        header("Location: profile.php?" . http_build_query($query));
        exit;
    }
}

if (isset($_GET["message"])) {
    $flashMessage = urldecode($_GET["message"] ?? "");
    $flashStatus = $_GET["status"] ?? "success";

    if ($flashStatus === "success") {
        $successMessage = $flashMessage;
    } else {
        $errorMessage = $flashMessage;
    }
}

$sql = "SELECT id, name, email, role, created_at FROM users WHERE id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$addressSql = "SELECT id, label, full_name, phone, address, city, postal_code, is_default FROM shipping_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC";
$addressStmt = mysqli_prepare($conn, $addressSql);
mysqli_stmt_bind_param($addressStmt, "i", $userId);
mysqli_stmt_execute($addressStmt);
$addressResult = mysqli_stmt_get_result($addressStmt);
$addresses = [];
while ($row = mysqli_fetch_assoc($addressResult)) {
    $addresses[] = $row;
}
mysqli_stmt_close($addressStmt);

$pageTitle = "Profile";
require_once "includes/header.php";
?>

<main class="min-h-[calc(100vh-5rem)] py-10">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Account</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">My Profile</h1>
            </div>

            <button type="button" id="open-order-history-btn"
                class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white transition hover:bg-blue-700">
                See Order History
            </button>
        </div>

        <?php if ($successMessage !== ""): ?>
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ""): ?>
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <div class="grid min-w-0 gap-8 lg:grid-cols-[1.2fr,1.8fr]">
            <section class="min-w-0 overflow-hidden rounded-2xl bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-2xl font-bold text-blue-700">
                            <?php echo strtoupper(substr($user["name"], 0, 1)); ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h2 class="truncate text-2xl font-bold text-slate-900"><?php echo htmlspecialchars($user["name"]); ?></h2>
                            <p class="break-all text-sm text-slate-500"><?php echo htmlspecialchars($user["email"]); ?></p>
                        </div>
                    </div>

                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700">
                        <?php echo ucfirst(htmlspecialchars($user["role"])); ?>
                    </span>
                </div>

                <div class="mt-8 grid gap-6 md:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Full Name</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900"><?php echo htmlspecialchars($user["name"]); ?></p>
                    </div>

                    <div class="min-w-0 rounded-xl bg-slate-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Email Address</p>
                        <p class="mt-2 break-all text-lg font-semibold text-slate-900"><?php echo htmlspecialchars($user["email"]); ?></p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Account Type</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900"><?php echo ucfirst(htmlspecialchars($user["role"])); ?></p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Member Since</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900"><?php echo htmlspecialchars(date("M d, Y", strtotime($user["created_at"]))); ?></p>
                    </div>
                </div>
            </section>

            <section class="min-w-0 overflow-hidden rounded-2xl bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-6 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Delivery</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Shipping Addresses</h2>
                    </div>
                </div>

                <form id="shipping-address-form" method="POST" action="profile.php" class="w-full min-w-0 space-y-4 rounded-xl bg-slate-50 p-5">
                    <input type="hidden" name="action" value="save_address">
                    <input type="hidden" name="address_id" value="0">

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Label</label>
                            <input type="text" name="label" value="Home" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Home / Office">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Full Name</label>
                            <input type="text" name="full_name" required class="field-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Your name">
                            <p class="field-error mt-1 hidden text-xs text-red-600"></p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Phone Number</label>
                            <input type="text" name="phone" required class="field-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="98XXXXXXXX">
                            <p class="field-error mt-1 hidden text-xs text-red-600"></p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Postal Code</label>
                            <input type="text" name="postal_code" class="field-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="44600">
                            <p class="field-error mt-1 hidden text-xs text-red-600"></p>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Street Address</label>
                        <textarea name="address" required rows="3" class="field-input w-full resize-none rounded-lg border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Street address, ward, area"></textarea>
                        <p class="field-error mt-1 hidden text-xs text-red-600"></p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">City</label>
                        <input type="text" name="city" required class="field-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Kathmandu">
                        <p class="field-error mt-1 hidden text-xs text-red-600"></p>
                    </div>

                    <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" name="is_default" value="1">
                        Set as default shipping address
                    </label>

                    <button type="submit" class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white transition hover:bg-blue-700">
                        Save Address
                    </button>
                </form>

                <div class="mt-8 space-y-4">
                    <?php if (empty($addresses)): ?>
                        <p class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                            No shipping addresses saved yet.
                        </p>
                    <?php else: ?>
                        <?php foreach ($addresses as $address): ?>
                            <div class="min-w-0 rounded-xl bg-slate-50 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0 flex-1 break-words">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-lg font-semibold text-slate-900"><?php echo htmlspecialchars($address["label"] ?: "Home"); ?></p>
                                            <?php if (!empty($address["is_default"])): ?>
                                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">Default</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mt-2 break-words text-sm text-slate-700"><?php echo htmlspecialchars($address["full_name"]); ?></p>
                                        <p class="break-words text-sm text-slate-700"><?php echo htmlspecialchars($address["phone"]); ?></p>
                                        <p class="break-words text-sm text-slate-700"><?php echo htmlspecialchars($address["address"]); ?></p>
                                        <p class="break-words text-sm text-slate-700"><?php echo htmlspecialchars($address["city"] . (!empty($address["postal_code"]) ? ', ' . $address["postal_code"] : '')); ?></p>
                                    </div>

                                    <div class="flex shrink-0 gap-2 sm:flex-col">
                                        <button type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 edit-address-btn" data-id="<?php echo (int) $address["id"]; ?>" data-label="<?php echo htmlspecialchars($address["label"] ?: "Home"); ?>" data-name="<?php echo htmlspecialchars($address["full_name"]); ?>" data-phone="<?php echo htmlspecialchars($address["phone"]); ?>" data-address="<?php echo htmlspecialchars($address["address"]); ?>" data-city="<?php echo htmlspecialchars($address["city"]); ?>" data-postal="<?php echo htmlspecialchars($address["postal_code"]); ?>" data-default="<?php echo (int) $address["is_default"]; ?>">
                                            Edit
                                        </button>
                                        <form method="POST" action="profile.php" onsubmit="return confirm('Delete this shipping address?');">
                                            <input type="hidden" name="action" value="delete_address">
                                            <input type="hidden" name="address_id" value="<?php echo (int) $address["id"]; ?>">
                                            <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

    </div>
</main>

<!-- ==========================================
     ORDER HISTORY MODAL
=========================================== -->
<div id="order-history-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div id="order-history-backdrop" class="absolute inset-0 bg-slate-900/80"></div>

    <div class="relative flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">

        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h3 class="text-lg font-bold text-slate-900">Order History</h3>
            <button type="button" id="close-order-history-btn" class="rounded-full p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Close">
                &#10005;
            </button>
        </div>

        <div id="order-history-body" class="min-w-0 flex-1 overflow-y-auto p-6">
            <!-- Populated by JS on first open -->
        </div>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const openBtn = document.getElementById("open-order-history-btn");
        const closeBtn = document.getElementById("close-order-history-btn");
        const modal = document.getElementById("order-history-modal");
        const backdrop = document.getElementById("order-history-backdrop");
        const body = document.getElementById("order-history-body");

        if (!openBtn || !modal || !body) return;

        // Safety net: clear any leftover inert attributes in case a prior
        // page load got stuck mid-close (e.g. during development/testing).
        document.querySelectorAll("[inert]").forEach((el) => el.removeAttribute("inert"));
        document.body.style.overflow = "";
        document.body.style.paddingRight = "";

        let loaded = false;
        let loading = false;

        const statusStyles = {
            pending:    "bg-amber-100 text-amber-700",
            processing: "bg-blue-100 text-blue-700",
            shipped:    "bg-cyan-100 text-cyan-700",
            delivered:  "bg-emerald-100 text-emerald-700",
            cancelled:  "bg-red-100 text-red-700",
        };

        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        function renderLoading() {
            body.innerHTML = `
                <div class="flex flex-col items-center justify-center gap-3 py-14 text-slate-400">
                    <svg class="h-8 w-8 animate-spin text-blue-600" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <p class="text-sm">Loading your orders&hellip;</p>
                </div>`;
        }

        function renderError(message) {
            body.innerHTML = `
                <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-8 text-center text-sm text-red-600">
                    ${escapeHtml(message || "Couldn't load your order history. Please try again.")}
                </p>`;
        }

        function renderEmpty() {
            body.innerHTML = `
                <p class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                    You haven't placed any orders yet.
                    <a href="products.php" class="font-semibold text-blue-600 hover:underline">Start shopping &rarr;</a>
                </p>`;
        }

        function renderOrders(orders) {
            body.innerHTML = "";

            const wrap = document.createElement("div");
            wrap.className = "space-y-4";

            orders.forEach((order) => {
                const statusClass = statusStyles[order.status] || "bg-slate-100 text-slate-700";
                const statusLabel = order.status.charAt(0).toUpperCase() + order.status.slice(1);

                const details = document.createElement("details");
                details.className = "min-w-0 rounded-xl border border-slate-200 bg-slate-50 open:bg-white";

                const itemsHtml = order.items.length === 0
                    ? `<p class="text-sm text-slate-500">No item details available for this order.</p>`
                    : order.items.map((item) => `
                        <div class="flex items-center gap-4">
                            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-blue-50">
                                ${item.image
                                    ? `<img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" class="h-full w-full object-cover">`
                                    : `<div class="flex h-full w-full items-center justify-center text-[10px] font-bold text-blue-300">No image</div>`}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800">${escapeHtml(item.name)}</p>
                                <p class="text-xs text-slate-500">Qty: ${item.quantity} &times; Rs. ${item.price}</p>
                            </div>
                            <div class="shrink-0 text-sm font-bold text-slate-800">Rs. ${item.subtotal}</div>
                        </div>
                    `).join("");

                details.innerHTML = `
                    <summary class="flex cursor-pointer list-none flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-sm font-bold text-slate-900">Order ${escapeHtml(order.orderNumber)}</span>
                            <span class="text-xs text-slate-500">${escapeHtml(order.date)}</span>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide ${statusClass}">${escapeHtml(statusLabel)}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-xs text-slate-500">${order.itemCount} item${order.itemCount === 1 ? "" : "s"}</span>
                            <span class="text-base font-black text-blue-700">Rs. ${order.total}</span>
                            <span class="text-slate-400">&#9662;</span>
                        </div>
                    </summary>
                    <div class="border-t border-slate-200 p-4">
                        <div class="space-y-3">${itemsHtml}</div>
                    </div>
                `;

                wrap.appendChild(details);
            });

            body.appendChild(wrap);
        }

        function getInertTargets() {
            // Walk up from the modal to <body>, collecting true siblings at
            // each level. This works no matter how deeply the modal is
            // nested (e.g. if header/footer templates wrap the page in
            // extra containers) and guarantees we never inert the modal
            // itself or any of its ancestors.
            const targets = [];
            let node = modal;
            while (node && node !== document.body && node.parentElement) {
                const parent = node.parentElement;
                Array.from(parent.children).forEach((sibling) => {
                    if (sibling !== node) targets.push(sibling);
                });
                node = parent;
            }
            return targets;
        }

        function setBackgroundInert(isInert) {
            getInertTargets().forEach((el) => {
                if (isInert) {
                    el.setAttribute("inert", "");
                } else {
                    el.removeAttribute("inert");
                }
            });
        }

        function loadOrders() {
            loading = true;
            renderLoading();

            fetch("order-history-data.php", { credentials: "same-origin" })
                .then((res) => {
                    return res.text().then((text) => {
                        let data;
                        try {
                            data = JSON.parse(text);
                        } catch (parseErr) {
                            console.error("Order history: response was not valid JSON.", text);
                            throw new Error("Server returned an unexpected response.");
                        }

                        if (!res.ok) {
                            throw new Error(data.error || `Request failed (${res.status}).`);
                        }

                        return data;
                    });
                })
                .then((data) => {
                    loading = false;
                    loaded = true;

                    if (data.error) {
                        renderError(data.error);
                        return;
                    }

                    if (!data.orders || data.orders.length === 0) {
                        renderEmpty();
                    } else {
                        renderOrders(data.orders);
                    }
                })
                .catch((err) => {
                    console.error("Order history fetch failed:", err);
                    loading = false;
                    // Allow retry on next open instead of getting stuck permanently
                    loaded = false;
                    renderError(err.message);
                });
        }

        function openModal() {
            modal.classList.remove("hidden");
            modal.classList.add("flex");
            setBackgroundInert(true);

            if (!loaded && !loading) {
                loadOrders();
            }
        }

        function closeModal() {
            modal.classList.add("hidden");
            modal.classList.remove("flex");
            setBackgroundInert(false);
        }

        openBtn.addEventListener("click", openModal);
        closeBtn.addEventListener("click", closeModal);
        backdrop.addEventListener("click", closeModal);

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && !modal.classList.contains("hidden")) {
                closeModal();
            }
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const form = document.getElementById("shipping-address-form");
        const addressIdInput = form ? form.querySelector("input[name='address_id']") : null;
        const labelInput = form ? form.querySelector("input[name='label']") : null;
        const fullNameInput = form ? form.querySelector("input[name='full_name']") : null;
        const phoneInput = form ? form.querySelector("input[name='phone']") : null;
        const addressInput = form ? form.querySelector("textarea[name='address']") : null;
        const cityInput = form ? form.querySelector("input[name='city']") : null;
        const postalInput = form ? form.querySelector("input[name='postal_code']") : null;
        const defaultCheckbox = form ? form.querySelector("input[name='is_default']") : null;
        const editButtons = document.querySelectorAll(".edit-address-btn");

        const setFieldState = (field, message) => {
            if (!field) return;
            const errorEl = field.parentElement.querySelector(".field-error");
            if (!errorEl) return;

            if (message) {
                field.classList.add("border-red-500", "focus:ring-red-500");
                field.classList.remove("border-slate-300", "focus:ring-blue-500");
                errorEl.textContent = message;
                errorEl.classList.remove("hidden");
            } else {
                field.classList.remove("border-red-500", "focus:ring-red-500");
                field.classList.add("border-slate-300", "focus:ring-blue-500");
                errorEl.textContent = "";
                errorEl.classList.add("hidden");
            }
        };

        const validateField = (fieldName, value) => {
            switch (fieldName) {
                case "full_name":
                    const trimmedName = value.trim();
                    if (!trimmedName) return "Full name is required.";
                    if (trimmedName.length < 2) return "Full name must be at least 2 characters.";
                    if (!/^[A-Za-z]/.test(trimmedName)) return "Full name must start with a letter.";
                    if (!/^[A-Za-z][A-Za-z\s.'-]*$/.test(trimmedName)) return "Full name contains invalid characters.";
                    return "";
                case "phone":
                    if (!value.trim()) return "Phone number is required.";
                    if (!/^[0-9+()\-\s]{7,20}$/.test(value.trim())) return "Phone number format is invalid.";
                    return "";
                case "address":
                    if (!value.trim()) return "Street address is required.";
                    if (value.trim().length < 5) return "Street address is too short.";
                    return "";
                case "city":
                    if (!value.trim()) return "City is required.";
                    if (value.trim().length < 2) return "City name is too short.";
                    return "";
                case "postal_code":
                    if (!value.trim()) return "";
                    if (!/^[A-Za-z0-9\- ]{3,20}$/.test(value.trim())) return "Postal code format is invalid.";
                    return "";
                default:
                    return "";
            }
        };

        const bindFieldValidation = (field, fieldName) => {
            if (!field) return;

            field.addEventListener("input", () => {
                setFieldState(field, validateField(fieldName, field.value));
            });

            field.addEventListener("blur", () => {
                setFieldState(field, validateField(fieldName, field.value));
            });
        };

        bindFieldValidation(fullNameInput, "full_name");
        bindFieldValidation(phoneInput, "phone");
        bindFieldValidation(addressInput, "address");
        bindFieldValidation(cityInput, "city");
        bindFieldValidation(postalInput, "postal_code");

        form.addEventListener("submit", (event) => {
            let hasError = false;
            [
                [fullNameInput, "full_name"],
                [phoneInput, "phone"],
                [addressInput, "address"],
                [cityInput, "city"],
                [postalInput, "postal_code"]
            ].forEach(([field, fieldName]) => {
                const message = validateField(fieldName, field ? field.value : "");
                setFieldState(field, message);
                if (message) hasError = true;
            });

            if (hasError) {
                event.preventDefault();
            }
        });

        editButtons.forEach((button) => {
            button.addEventListener("click", () => {
                if (!form || !addressIdInput || !labelInput || !fullNameInput || !phoneInput || !addressInput || !cityInput || !postalInput || !defaultCheckbox) return;

                addressIdInput.value = button.dataset.id || "0";
                labelInput.value = button.dataset.label || "Home";
                fullNameInput.value = button.dataset.name || "";
                phoneInput.value = button.dataset.phone || "";
                addressInput.value = button.dataset.address || "";
                cityInput.value = button.dataset.city || "";
                postalInput.value = button.dataset.postal || "";
                defaultCheckbox.checked = Number(button.dataset.default || 0) === 1;

                [fullNameInput, phoneInput, addressInput, cityInput, postalInput].forEach((field) => {
                    setFieldState(field, validateField(
                        field.name === "full_name" ? "full_name" :
                        field.name === "phone" ? "phone" :
                        field.name === "address" ? "address" :
                        field.name === "city" ? "city" : "postal_code",
                        field.value
                    ));
                });

                window.scrollTo({ top: 0, behavior: "smooth" });
            });
        });
    });
</script>

<?php require_once "includes/footer.php"; ?>