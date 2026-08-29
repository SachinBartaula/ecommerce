<?php

$pageTitle = "Order Management";
require_once "../includes/admin-header.php";
?>

<style>
    .orders-status-pill { border-radius: 999px; padding: .32rem .7rem; font-size: .72rem; font-weight: 700; text-transform: capitalize; }
    .status-pending { background: #fff7ed; color: #c2410c; }
    .status-confirmed { background: #eff6ff; color: #1d4ed8; }
    .status-shipped { background: #f5f3ff; color: #6d28d9; }
    .status-delivered { background: #ecfdf5; color: #047857; }
    .status-cancelled { background: #fef2f2; color: #b91c1c; }
    .order-status-select { appearance: none; border: 1px solid #dbe3ee; border-radius: .8rem; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); padding: .7rem .85rem; font-size: .86rem; color: #0f172a; font-weight: 600; min-width: 7.5rem; }
    .order-status-select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, .12); }
    .payment-status-pill { border-radius: 999px; padding: .2rem .55rem; font-size: .68rem; font-weight: 700; text-transform: capitalize; }
    .payment-status-pending { background: #fff7ed; color: #c2410c; }
    .payment-status-paid { background: #ecfdf5; color: #047857; }
    .payment-status-failed { background: #fef2f2; color: #b91c1c; }
</style>

<main class="min-h-[calc(100vh-5rem)] bg-slate-100 py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-7 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Sales</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Order Management</h1>
                <p class="mt-2 text-sm text-slate-500">Review orders and keep customers up to date.</p>
            </div>
            <span id="ordersCount" class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700 ring-1 ring-inset ring-blue-200"></span>
        </div>

        <section class="rounded-2xl bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <label for="orderSearch" class="sr-only">Search orders</label>
                <input id="orderSearch" type="search" class="flex-1 rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100" placeholder="Search by order number, customer, or email">
                <label for="orderStatus" class="sr-only">Filter by status</label>
                <select id="orderStatus" class="rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 shadow-sm transition focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100">
                    <option value="all">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <button id="refreshOrders" type="button" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">Refresh</button>
            </div>
        </section>

        <section class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm">
            <div id="ordersMessage" class="hidden px-5 py-4 text-sm"></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-[11px] uppercase tracking-[.12em] text-slate-500">
                        <tr><th class="px-5 py-4 font-semibold">Order</th><th class="px-5 py-4 font-semibold">Customer</th><th class="px-5 py-4 font-semibold">Payment</th><th class="px-5 py-4 font-semibold">Date</th><th class="px-5 py-4 text-right font-semibold">Total</th><th class="px-5 py-4 font-semibold">Status</th><th class="px-5 py-4 text-right font-semibold">Action</th></tr>
                    </thead>
                    <tbody id="ordersBody" class="divide-y divide-slate-100"><tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">Loading orders...</td></tr></tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<div id="orderModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-900/50 px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="orderModalTitle">
    <section class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5"><h2 id="orderModalTitle" class="text-xl font-bold text-slate-900">Order details</h2><button id="closeOrderModal" type="button" class="text-2xl leading-none text-slate-400 hover:text-slate-700" aria-label="Close order details">&times;</button></div>
        <div id="orderDetails" class="p-6"><p class="text-sm text-slate-500">Loading...</p></div>
    </section>
</div>

<div id="lockConfirmModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-900/60 px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="lockConfirmTitle">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.2em] text-amber-600">Warning</p>
                <h3 id="lockConfirmTitle" class="mt-2 text-xl font-bold text-slate-900">Lock this order?</h3>
            </div>
            <button id="closeLockConfirmModal" type="button" class="text-2xl leading-none text-slate-400 hover:text-slate-700" aria-label="Close confirmation">&times;</button>
        </div>

        <div class="space-y-4 text-sm text-slate-600">
            <p>Before locking this order, confirm both conditions are complete.</p>
            <label class="flex items-start gap-3 rounded-xl bg-slate-50 px-3 py-2">
                <input id="lockDeliveryDone" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                <span>Delivery is completed.</span>
            </label>
            <label class="flex items-start gap-3 rounded-xl bg-slate-50 px-3 py-2">
                <input id="lockPaymentDone" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                <span>Payment is completed.</span>
            </label>
            <div class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700">
                Once locked, this order cannot be edited again.
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button id="cancelLockConfirm" type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
            <button id="confirmLockOrder" type="button" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white opacity-60 transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50" disabled>Lock now</button>
        </div>
    </div>
</div>

<script>
const ordersBody = document.getElementById("ordersBody");
const ordersCount = document.getElementById("ordersCount");
const ordersMessage = document.getElementById("ordersMessage");
const orderSearch = document.getElementById("orderSearch");
const orderStatus = document.getElementById("orderStatus");
const orderModal = document.getElementById("orderModal");
const orderDetails = document.getElementById("orderDetails");
let orders = [];
let activeOrderId = null;
let pendingLockOrderId = null;

const lockConfirmModal = document.getElementById("lockConfirmModal");
const confirmLockOrderButton = document.getElementById("confirmLockOrder");
const lockDeliveryDone = document.getElementById("lockDeliveryDone");
const lockPaymentDone = document.getElementById("lockPaymentDone");

function updateLockConfirmState() {
    const canLock = lockDeliveryDone.checked && lockPaymentDone.checked;
    confirmLockOrderButton.disabled = !canLock;
    confirmLockOrderButton.classList.toggle("opacity-60", !canLock);
    confirmLockOrderButton.classList.toggle("cursor-not-allowed", !canLock);
}

function openLockConfirmModal(orderId) {
    pendingLockOrderId = orderId;
    lockDeliveryDone.checked = false;
    lockPaymentDone.checked = false;
    updateLockConfirmState();
    lockConfirmModal.classList.remove("hidden");
    lockConfirmModal.classList.add("flex");
}

function closeLockConfirmModal() {
    pendingLockOrderId = null;
    lockDeliveryDone.checked = false;
    lockPaymentDone.checked = false;
    updateLockConfirmState();
    lockConfirmModal.classList.add("hidden");
    lockConfirmModal.classList.remove("flex");
}

function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>'"]/g, character => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", "'": "&#039;", "\"": "&quot;" })[character]);
}

function formatPaymentMethod(method) {
    const normalized = String(method ?? "").trim().toLowerCase();
    if (normalized === "cod") return "Cash on Delivery";
    if (normalized === "card" || normalized === "online_payment" || normalized === "onlinepayment") return "Online Payment";
    if (normalized === "esewa") return "eSewa";
    if (normalized === "khalti") return "Khalti";
    if (normalized === "imepay") return "IME Pay";
    return "Not recorded";
}

function formatPaymentStatus(status) {
    const normalized = String(status ?? "").trim().toLowerCase();
    if (["pending", "paid", "failed"].includes(normalized)) return normalized;
    return "pending";
}

function isOnlinePaymentMethod(method) {
    const normalized = String(method ?? "").trim().toLowerCase();
    return ["card", "online_payment", "onlinepayment", "esewa", "khalti", "imepay"].includes(normalized);
}

function getLockedOrderStatusIds() {
    try {
        return JSON.parse(localStorage.getItem("lockedOrderStatusIds") || "[]");
    } catch {
        return [];
    }
}

function setLockedOrderStatusIds(ids) {
    localStorage.setItem("lockedOrderStatusIds", JSON.stringify(ids));
}

function resolvePaymentStatusForDisplay(order) {
    if (isOnlinePaymentMethod(order.payment_method)) {
        return "paid";
    }
    return formatPaymentStatus(order.payment_status);
}

function showMessage(message, type = "error") {
    ordersMessage.textContent = message;
    ordersMessage.className = `px-5 py-4 text-sm ${type === "success" ? "bg-emerald-50 text-emerald-700" : "bg-red-50 text-red-700"}`;
    ordersMessage.classList.remove("hidden");
}

function renderOrders() {
    ordersCount.textContent = `${orders.length} order${orders.length === 1 ? "" : "s"}`;
    if (!orders.length) {
        ordersBody.innerHTML = `<tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">No orders match your filters.</td></tr>`;
        return;
    }

    ordersBody.innerHTML = orders.map(order => {
        const paymentStatus = resolvePaymentStatusForDisplay(order);
        const isOnlinePayment = isOnlinePaymentMethod(order.payment_method);
        const isLocked = getLockedOrderStatusIds().includes(Number(order.id));
        return `
        <tr class="transition hover:bg-slate-50">
            <td class="px-5 py-4 font-bold text-slate-700">#${escapeHtml(order.id)}</td>
            <td class="px-5 py-4"><p class="font-semibold text-slate-700">${escapeHtml(order.customer)}</p><p class="text-xs text-slate-500">${escapeHtml(order.email)}</p></td>
            <td class="px-5 py-4">
                <div class="space-y-2">
                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">${escapeHtml(formatPaymentMethod(order.payment_method))}</span>
                    <select class="order-status-select w-full min-w-[110px] capitalize" data-payment-status="${escapeHtml(order.id)}" ${isOnlinePayment || isLocked ? "disabled title='This payment status is locked'" : ""}>
                        <option value="pending" ${paymentStatus === "pending" ? "selected" : ""}>Pending</option>
                        <option value="paid" ${paymentStatus === "paid" ? "selected" : ""}>Paid</option>
                        <option value="failed" ${paymentStatus === "failed" ? "selected" : ""}>Failed</option>
                    </select>
                </div>
            </td>
            <td class="px-5 py-4 text-slate-500">${escapeHtml(new Date(order.created_at.replace(" ", "T")).toLocaleDateString())}</td>
            <td class="px-5 py-4 text-right font-semibold text-slate-800">$${Number(order.total_amount).toFixed(2)}</td>
            <td class="px-5 py-4">
                <div class="flex items-center gap-2">
                    <select class="order-status-select capitalize" data-order-status="${escapeHtml(order.id)}" ${isLocked ? "disabled title='Order delivery status is locked'" : ""}><option value="pending" ${order.status === "pending" ? "selected" : ""}>Pending</option><option value="confirmed" ${order.status === "confirmed" ? "selected" : ""}>Confirmed</option><option value="shipped" ${order.status === "shipped" ? "selected" : ""}>Shipped</option><option value="delivered" ${order.status === "delivered" ? "selected" : ""}>Delivered</option><option value="cancelled" ${order.status === "cancelled" ? "selected" : ""}>Cancelled</option></select>
                    <button type="button" class="rounded-lg ${isLocked ? "bg-slate-900 text-white" : "bg-amber-50 text-amber-700 hover:bg-amber-100"} px-2.5 py-2 text-[11px] font-bold uppercase tracking-wide transition" data-order-lock="${escapeHtml(order.id)}" ${isLocked ? "disabled" : ""}>
                        ${isLocked ? "Locked" : "Lock"}
                    </button>
                </div>
            </td>
            <td class="px-5 py-4 text-right"><button type="button" class="rounded-lg bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100" data-order-details="${escapeHtml(order.id)}">View details</button></td>
        </tr>
    `;
    }).join("");
}

async function loadOrders() {
    ordersBody.innerHTML = `<tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">Loading orders...</td></tr>`;
    try {
        const params = new URLSearchParams({ status: orderStatus.value, search: orderSearch.value.trim() });
        const response = await fetch(`../api/orders.php?${params}`);
        const result = await response.json();
        if (!result.success) throw new Error(result.message || "Unable to load orders.");
        orders = result.data;
        ordersMessage.classList.add("hidden");
        renderOrders();
    } catch (error) {
        ordersBody.innerHTML = `<tr><td colspan="7" class="px-5 py-10 text-center text-red-600">${escapeHtml(error.message)}</td></tr>`;
        ordersCount.textContent = "";
    }
}

async function updateOrderStatus(id, status, select) {
    select.disabled = true;
    const data = new FormData();
    data.append("action", "update_status");
    data.append("id", id);
    data.append("status", status);
    try {
        const response = await fetch("../api/orders.php", { method: "POST", body: data });
        const result = await response.json();
        if (!result.success) throw new Error(result.message || "Unable to update order.");
        const order = orders.find(item => String(item.id) === String(id));
        if (order) order.status = status;
        if (activeOrderId === id) {
            await viewOrder(id);
        }
        showMessage(result.message, "success");
    } catch (error) {
        showMessage(error.message);
        loadOrders();
    } finally {
        select.disabled = false;
    }
}

async function updatePaymentStatus(id, status, select) {
    select.disabled = true;
    const data = new FormData();
    data.append("action", "update_payment_status");
    data.append("id", id);
    data.append("payment_status", status);
    try {
        const response = await fetch("../api/orders.php", { method: "POST", body: data });
        const result = await response.json();
        if (!result.success) throw new Error(result.message || "Unable to update payment status.");
        const order = orders.find(item => String(item.id) === String(id));
        if (order) order.payment_status = status;
        if (activeOrderId === id) {
            await viewOrder(id);
        }
        showMessage(result.message, "success");
    } catch (error) {
        showMessage(error.message);
        if (activeOrderId === id) {
            await viewOrder(id);
        }
    } finally {
        select.disabled = false;
    }
}

async function viewOrder(id) {
    activeOrderId = id;
    orderModal.classList.remove("hidden");
    orderModal.classList.add("flex");
    orderDetails.innerHTML = `<p class="text-sm text-slate-500">Loading order details...</p>`;
    try {
        const response = await fetch(`../api/orders.php?id=${encodeURIComponent(id)}`);
        const result = await response.json();
        if (!result.success) throw new Error(result.message || "Unable to load order details.");
        const order = result.data;
        const statusClass = `status-${order.status || "pending"}`;
        const paymentStatus = formatPaymentStatus(order.payment_status);
        orderDetails.innerHTML = `
            <div class="grid gap-4 sm:grid-cols-2"><div><p class="text-xs uppercase tracking-wide text-slate-400">Customer</p><p class="mt-1 font-semibold text-slate-700">${escapeHtml(order.customer)}</p><p class="text-sm text-slate-500">${escapeHtml(order.email)}</p></div><div><p class="text-xs uppercase tracking-wide text-slate-400">Shipping address</p><p class="mt-1 text-sm text-slate-700">${escapeHtml(order.shipping_address)}</p></div><div><p class="text-xs uppercase tracking-wide text-slate-400">Payment method</p><p class="mt-1 text-sm text-slate-700">${escapeHtml(formatPaymentMethod(order.payment_method))}</p></div><div><p class="text-xs uppercase tracking-wide text-slate-400">Payment status</p><div class="mt-2"><span class="payment-status-pill payment-status-${paymentStatus}">${escapeHtml(paymentStatus)}</span></div></div><div><p class="text-xs uppercase tracking-wide text-slate-400">Total</p><p class="mt-1 text-lg font-bold text-slate-900">$${Number(order.total_amount).toFixed(2)}</p></div><div class="sm:col-span-2"><p class="text-xs uppercase tracking-wide text-slate-400">Order status</p><div class="mt-2"><span class="orders-status-pill ${statusClass}">${escapeHtml(order.status || "pending")}</span></div></div></div>
            <h3 class="mt-7 border-b border-slate-200 pb-3 font-bold text-slate-900">Items</h3>
            <div class="divide-y divide-slate-100">${order.items.length ? order.items.map(item => `<div class="flex justify-between gap-4 py-3 text-sm"><span class="text-slate-700">${escapeHtml(item.name || "Deleted product")} <span class="text-slate-400">x${escapeHtml(item.quantity)}</span></span><strong class="text-slate-700">$${(Number(item.price) * Number(item.quantity)).toFixed(2)}</strong></div>`).join("") : `<p class="py-4 text-sm text-slate-500">No items recorded.</p>`}</div>
        `;
    } catch (error) {
        orderDetails.innerHTML = `<p class="text-sm text-red-600">${escapeHtml(error.message)}</p>`;
    }
}

ordersBody.addEventListener("change", event => {
    const orderId = Number(event.target.dataset.orderStatus || event.target.dataset.paymentStatus || 0);
    const isLocked = getLockedOrderStatusIds().includes(orderId);

    if (isLocked) {
        renderOrders();
        return;
    }

    if (event.target.matches("[data-order-status]")) updateOrderStatus(event.target.dataset.orderStatus, event.target.value, event.target);
    if (event.target.matches("[data-payment-status]")) updatePaymentStatus(event.target.dataset.paymentStatus, event.target.value, event.target);
});
ordersBody.addEventListener("click", event => {
    const lockButton = event.target.closest("[data-order-lock]");
    if (lockButton) {
        const orderId = Number(lockButton.dataset.orderLock);
        const lockedIds = getLockedOrderStatusIds();
        if (lockedIds.includes(orderId)) {
            return;
        }

        openLockConfirmModal(orderId);
        return;
    }

    const button = event.target.closest("[data-order-details]");
    if (button) viewOrder(button.dataset.orderDetails);
});

lockDeliveryDone.addEventListener("change", updateLockConfirmState);
lockPaymentDone.addEventListener("change", updateLockConfirmState);
confirmLockOrderButton.addEventListener("click", () => {
    if (!pendingLockOrderId) {
        return;
    }

    const lockedIds = getLockedOrderStatusIds();
    if (!lockedIds.includes(pendingLockOrderId)) {
        setLockedOrderStatusIds([...lockedIds, pendingLockOrderId]);
    }

    renderOrders();
    closeLockConfirmModal();
});
document.getElementById("cancelLockConfirm").addEventListener("click", closeLockConfirmModal);
document.getElementById("closeLockConfirmModal").addEventListener("click", closeLockConfirmModal);
lockConfirmModal.addEventListener("click", event => {
    if (event.target === lockConfirmModal) {
        closeLockConfirmModal();
    }
});
document.getElementById("refreshOrders").addEventListener("click", loadOrders);
orderStatus.addEventListener("change", loadOrders);
let searchTimer;
orderSearch.addEventListener("input", () => { clearTimeout(searchTimer); searchTimer = setTimeout(loadOrders, 300); });
document.getElementById("closeOrderModal").addEventListener("click", () => { activeOrderId = null; orderModal.classList.add("hidden"); orderModal.classList.remove("flex"); });
orderModal.addEventListener("click", event => { if (event.target === orderModal) { activeOrderId = null; orderModal.classList.add("hidden"); orderModal.classList.remove("flex"); } });
updateLockConfirmState();
loadOrders();
</script>