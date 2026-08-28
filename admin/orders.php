<?php

$pageTitle = "Order Management";
require_once "../includes/admin-header.php";
?>

<main class="min-h-[calc(100vh-5rem)] bg-slate-100 py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-7 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Sales</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Order Management</h1>
                <p class="mt-2 text-sm text-slate-500">Review orders and keep customers up to date.</p>
            </div>
            <span id="ordersCount" class="text-sm font-semibold text-slate-500"></span>
        </div>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row">
                <label for="orderSearch" class="sr-only">Search orders</label>
                <input id="orderSearch" type="search" class="flex-1 rounded-lg border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Search by order number, customer, or email">
                <label for="orderStatus" class="sr-only">Filter by status</label>
                <select id="orderStatus" class="rounded-lg border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <button id="refreshOrders" type="button" class="rounded-lg border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50">Refresh</button>
            </div>
        </section>

        <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div id="ordersMessage" class="hidden px-5 py-4 text-sm"></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[780px] text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="px-5 py-4 font-semibold">Order</th><th class="px-5 py-4 font-semibold">Customer</th><th class="px-5 py-4 font-semibold">Date</th><th class="px-5 py-4 text-right font-semibold">Total</th><th class="px-5 py-4 font-semibold">Status</th><th class="px-5 py-4 text-right font-semibold">Action</th></tr>
                    </thead>
                    <tbody id="ordersBody" class="divide-y divide-slate-100"><tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Loading orders...</td></tr></tbody>
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

<script>
const ordersBody = document.getElementById("ordersBody");
const ordersCount = document.getElementById("ordersCount");
const ordersMessage = document.getElementById("ordersMessage");
const orderSearch = document.getElementById("orderSearch");
const orderStatus = document.getElementById("orderStatus");
const orderModal = document.getElementById("orderModal");
const orderDetails = document.getElementById("orderDetails");
let orders = [];

function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>'"]/g, character => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", "'": "&#039;", "\"": "&quot;" })[character]);
}

function showMessage(message, type = "error") {
    ordersMessage.textContent = message;
    ordersMessage.className = `px-5 py-4 text-sm ${type === "success" ? "bg-emerald-50 text-emerald-700" : "bg-red-50 text-red-700"}`;
    ordersMessage.classList.remove("hidden");
}

function renderOrders() {
    ordersCount.textContent = `${orders.length} order${orders.length === 1 ? "" : "s"}`;
    if (!orders.length) {
        ordersBody.innerHTML = `<tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">No orders match your filters.</td></tr>`;
        return;
    }

    ordersBody.innerHTML = orders.map(order => `
        <tr class="hover:bg-slate-50">
            <td class="px-5 py-4 font-bold text-slate-700">#${escapeHtml(order.id)}</td>
            <td class="px-5 py-4"><p class="font-semibold text-slate-700">${escapeHtml(order.customer)}</p><p class="text-xs text-slate-500">${escapeHtml(order.email)}</p></td>
            <td class="px-5 py-4 text-slate-500">${escapeHtml(new Date(order.created_at.replace(" ", "T")).toLocaleDateString())}</td>
            <td class="px-5 py-4 text-right font-semibold text-slate-700">$${Number(order.total_amount).toFixed(2)}</td>
            <td class="px-5 py-4"><select class="rounded-lg border border-slate-300 px-3 py-2 text-sm capitalize focus:outline-none focus:ring-2 focus:ring-blue-500" data-order-status="${escapeHtml(order.id)}"><option value="pending" ${order.status === "pending" ? "selected" : ""}>Pending</option><option value="confirmed" ${order.status === "confirmed" ? "selected" : ""}>Confirmed</option><option value="shipped" ${order.status === "shipped" ? "selected" : ""}>Shipped</option><option value="delivered" ${order.status === "delivered" ? "selected" : ""}>Delivered</option><option value="cancelled" ${order.status === "cancelled" ? "selected" : ""}>Cancelled</option></select></td>
            <td class="px-5 py-4 text-right"><button type="button" class="font-semibold text-blue-600 hover:text-blue-800" data-order-details="${escapeHtml(order.id)}">View details</button></td>
        </tr>
    `).join("");
}

async function loadOrders() {
    ordersBody.innerHTML = `<tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Loading orders...</td></tr>`;
    try {
        const params = new URLSearchParams({ status: orderStatus.value, search: orderSearch.value.trim() });
        const response = await fetch(`../api/orders.php?${params}`);
        const result = await response.json();
        if (!result.success) throw new Error(result.message || "Unable to load orders.");
        orders = result.data;
        ordersMessage.classList.add("hidden");
        renderOrders();
    } catch (error) {
        ordersBody.innerHTML = `<tr><td colspan="6" class="px-5 py-10 text-center text-red-600">${escapeHtml(error.message)}</td></tr>`;
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
        showMessage(result.message, "success");
    } catch (error) {
        showMessage(error.message);
        loadOrders();
    } finally {
        select.disabled = false;
    }
}

async function viewOrder(id) {
    orderModal.classList.remove("hidden");
    orderModal.classList.add("flex");
    orderDetails.innerHTML = `<p class="text-sm text-slate-500">Loading order details...</p>`;
    try {
        const response = await fetch(`../api/orders.php?id=${encodeURIComponent(id)}`);
        const result = await response.json();
        if (!result.success) throw new Error(result.message || "Unable to load order details.");
        const order = result.data;
        orderDetails.innerHTML = `
            <div class="grid gap-4 sm:grid-cols-2"><div><p class="text-xs uppercase tracking-wide text-slate-400">Customer</p><p class="mt-1 font-semibold text-slate-700">${escapeHtml(order.customer)}</p><p class="text-sm text-slate-500">${escapeHtml(order.email)}</p></div><div><p class="text-xs uppercase tracking-wide text-slate-400">Shipping address</p><p class="mt-1 text-sm text-slate-700">${escapeHtml(order.shipping_address)}</p></div><div><p class="text-xs uppercase tracking-wide text-slate-400">Payment</p><p class="mt-1 text-sm capitalize text-slate-700">${escapeHtml(order.payment_method || "Not recorded")} &middot; ${escapeHtml(order.payment_status || "Unknown")}</p></div><div><p class="text-xs uppercase tracking-wide text-slate-400">Total</p><p class="mt-1 text-lg font-bold text-slate-900">$${Number(order.total_amount).toFixed(2)}</p></div></div>
            <h3 class="mt-7 border-b border-slate-200 pb-3 font-bold text-slate-900">Items</h3>
            <div class="divide-y divide-slate-100">${order.items.length ? order.items.map(item => `<div class="flex justify-between gap-4 py-3 text-sm"><span class="text-slate-700">${escapeHtml(item.name || "Deleted product")} <span class="text-slate-400">x${escapeHtml(item.quantity)}</span></span><strong class="text-slate-700">$${(Number(item.price) * Number(item.quantity)).toFixed(2)}</strong></div>`).join("") : `<p class="py-4 text-sm text-slate-500">No items recorded.</p>`}</div>
        `;
    } catch (error) {
        orderDetails.innerHTML = `<p class="text-sm text-red-600">${escapeHtml(error.message)}</p>`;
    }
}

ordersBody.addEventListener("change", event => {
    if (event.target.matches("[data-order-status]")) updateOrderStatus(event.target.dataset.orderStatus, event.target.value, event.target);
});
ordersBody.addEventListener("click", event => {
    const button = event.target.closest("[data-order-details]");
    if (button) viewOrder(button.dataset.orderDetails);
});
document.getElementById("refreshOrders").addEventListener("click", loadOrders);
orderStatus.addEventListener("change", loadOrders);
let searchTimer;
orderSearch.addEventListener("input", () => { clearTimeout(searchTimer); searchTimer = setTimeout(loadOrders, 300); });
document.getElementById("closeOrderModal").addEventListener("click", () => { orderModal.classList.add("hidden"); orderModal.classList.remove("flex"); });
orderModal.addEventListener("click", event => { if (event.target === orderModal) { orderModal.classList.add("hidden"); orderModal.classList.remove("flex"); } });
loadOrders();
</script>