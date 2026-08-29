<?php

$pageTitle = "Inventory Management";
require_once "../includes/admin-header.php";

require_once "../config/database.php";

$inventorySummary = [
    "total_products" => (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM products"))['total'],
    "low_stock" => (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM products WHERE stock <= 5"))['total'],
    "out_of_stock" => (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM products WHERE stock = 0"))['total'],
];
?>

<main class="min-h-[calc(100vh-5rem)] bg-slate-100 py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-7 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-600">Stock</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Inventory Management</h1>
                <p class="mt-2 text-sm text-slate-500">Track stock, identify low inventory, and update quantities quickly.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">Updated live</span>
        </div>

        <section class="mb-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total products</p>
                <p class="mt-2 text-3xl font-bold text-slate-900"><?php echo number_format($inventorySummary["total_products"]); ?></p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Low stock</p>
                <p class="mt-2 text-3xl font-bold text-amber-600"><?php echo number_format($inventorySummary["low_stock"]); ?></p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Out of stock</p>
                <p class="mt-2 text-3xl font-bold text-red-600"><?php echo number_format($inventorySummary["out_of_stock"]); ?></p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-1 flex-col gap-3 md:flex-row">
                    <label for="inventorySearch" class="sr-only">Search products</label>
                    <input id="inventorySearch" type="search" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 transition focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100" placeholder="Search by product name or category">
                    <label for="inventoryFilter" class="sr-only">Filter stock level</label>
                    <select id="inventoryFilter" class="rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 transition focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100">
                        <option value="all">All stock</option>
                        <option value="low">Low stock</option>
                        <option value="out">Out of stock</option>
                    </select>
                </div>
                <button id="refreshInventory" type="button" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Refresh</button>
            </div>
        </section>

        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100">
            <div id="inventoryMessage" class="hidden px-5 py-4 text-sm"></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] table-fixed text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-[11px] uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th class="px-5 py-4 font-semibold">Product</th>
                            <th class="px-5 py-4 font-semibold">Category</th>
                            <th class="px-5 py-4 font-semibold">Stock</th>
                            <th class="px-5 py-4 font-semibold">Status</th>
                            <th class="px-5 py-4 text-right font-semibold">Quick actions</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryBody" class="divide-y divide-slate-100">
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-slate-500">Loading inventory...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<script>
const inventoryBody = document.getElementById("inventoryBody");
const inventoryMessage = document.getElementById("inventoryMessage");
const inventorySearch = document.getElementById("inventorySearch");
const inventoryFilter = document.getElementById("inventoryFilter");
let inventoryItems = [];

function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>\'\"]/g, character => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", "'": "&#039;", "\"": "&quot;" })[character]);
}

function setInventoryMessage(message, type = "error") {
    inventoryMessage.textContent = message;
    inventoryMessage.className = `px-5 py-4 text-sm ${type === "success" ? "bg-emerald-50 text-emerald-700" : "bg-red-50 text-red-700"}`;
    inventoryMessage.classList.remove("hidden");
}

function getStockStatus(stock) {
    const value = Number(stock) || 0;
    if (value === 0) return { label: "Out of stock", className: "bg-red-100 text-red-700" };
    if (value <= 5) return { label: "Low stock", className: "bg-amber-100 text-amber-700" };
    return { label: "Healthy", className: "bg-emerald-100 text-emerald-700" };
}

function renderInventory() {
    const query = inventorySearch.value.trim().toLowerCase();
    const filter = inventoryFilter.value;

    const filtered = inventoryItems.filter(item => {
        const matchesSearch = !query || (item.name + " " + (item.category || "")).toLowerCase().includes(query);
        const stock = Number(item.stock) || 0;
        const matchesFilter = filter === "all" || (filter === "low" && stock > 0 && stock <= 5) || (filter === "out" && stock === 0);
        return matchesSearch && matchesFilter;
    });

    if (!filtered.length) {
        inventoryBody.innerHTML = `<tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">No matching products found.</td></tr>`;
        return;
    }

    inventoryBody.innerHTML = filtered.map(item => {
        const status = getStockStatus(item.stock);
        return `
            <tr class="hover:bg-slate-50">
                <td class="px-5 py-4">
                    <div class="flex items-center gap-3">
                        <img src="${escapeHtml(item.image || "../assets/images/products/default.jpg")}" alt="${escapeHtml(item.name)}" class="h-12 w-12 rounded-lg border border-slate-200 object-cover bg-slate-100" onerror="this.src='../assets/images/products/default.jpg'">
                        <div>
                            <p class="font-semibold text-slate-800">${escapeHtml(item.name)}</p>
                            <p class="text-xs text-slate-500">#${escapeHtml(item.id)}</p>
                        </div>
                    </div>
                </td>
                <td class="px-5 py-4 text-slate-600">${escapeHtml(item.category || "Uncategorized")}</td>
                <td class="px-5 py-4">
                    <div class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5">
                        <span class="text-lg font-bold text-slate-800">${escapeHtml(Number(item.stock))}</span>
                        <span class="text-xs uppercase tracking-wide text-slate-400">units</span>
                    </div>
                </td>
                <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${status.className}">${status.label}</span></td>
                <td class="px-5 py-4">
                    <div class="flex justify-end gap-2">
                        <button type="button" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50" data-adjust="${escapeHtml(item.id)}" data-delta="-5">-5</button>
                        <button type="button" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50" data-adjust="${escapeHtml(item.id)}" data-delta="10">+10</button>
                        <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2">
                            <input type="number" min="0" value="${escapeHtml(Number(item.stock))}" class="w-16 border-0 bg-transparent text-center text-sm font-semibold text-slate-700 outline-none" data-stock-input="${escapeHtml(item.id)}">
                            <button type="button" class="rounded-md bg-blue-600 px-2 py-1 text-[11px] font-semibold text-white hover:bg-blue-700" data-set-stock="${escapeHtml(item.id)}">Set</button>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    }).join("");
}

async function loadInventory() {
    inventoryBody.innerHTML = `<tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Loading inventory...</td></tr>`;
    try {
        const response = await fetch("../api/inventory.php");
        const result = await response.json();
        if (!result.success) throw new Error(result.message || "Unable to load inventory.");
        inventoryItems = result.data;
        inventoryMessage.classList.add("hidden");
        renderInventory();
    } catch (error) {
        inventoryBody.innerHTML = `<tr><td colspan="5" class="px-5 py-10 text-center text-red-600">${escapeHtml(error.message)}</td></tr>`;
    }
}

async function updateInventoryStock(id, mode, value) {
    const formData = new FormData();
    formData.append("id", id);
    formData.append("mode", mode);
    formData.append("quantity", value);

    try {
        const response = await fetch("../api/inventory.php", { method: "POST", body: formData });
        const result = await response.json();
        if (!result.success) throw new Error(result.message || "Unable to update stock.");
        setInventoryMessage(result.message || "Stock updated successfully.", "success");
        await loadInventory();
    } catch (error) {
        setInventoryMessage(error.message || "Unable to update stock.");
    }
}

inventoryBody.addEventListener("click", event => {
    const adjustButton = event.target.closest("[data-adjust]");
    if (adjustButton) {
        const id = adjustButton.dataset.adjust;
        const delta = Number(adjustButton.dataset.delta || 0);
        updateInventoryStock(id, "add", delta);
    }

    const setButton = event.target.closest("[data-set-stock]");
    if (setButton) {
        const id = setButton.dataset.setStock;
        const input = inventoryBody.querySelector(`[data-stock-input="${CSS.escape(id)}"]`);
        const value = Number(input?.value ?? 0);
        if (Number.isFinite(value)) {
            updateInventoryStock(id, "set", Math.max(0, value));
        }
    }
});

inventorySearch.addEventListener("input", renderInventory);
inventoryFilter.addEventListener("change", renderInventory);
document.getElementById("refreshInventory").addEventListener("click", loadInventory);

loadInventory();
</script>
