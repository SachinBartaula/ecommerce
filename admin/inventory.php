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
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total products</p>
                <p class="mt-2 text-3xl font-bold text-slate-900"><?php echo number_format($inventorySummary["total_products"]); ?></p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Low stock</p>
                <p class="mt-2 text-3xl font-bold text-amber-600"><?php echo number_format($inventorySummary["low_stock"]); ?></p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Out of stock</p>
                <p class="mt-2 text-3xl font-bold text-red-600"><?php echo number_format($inventorySummary["out_of_stock"]); ?></p>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-sm">
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
                <div class="flex items-center gap-3">
                    <button id="openInventoryModalBtn" type="button" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">Add product</button>
                    <button id="refreshInventory" type="button" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Refresh</button>
                </div>
            </div>
        </section>

        <section class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm">
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

<div id="inventoryProductModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 px-4">
    <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
        <div class="mb-5 flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.15em] text-blue-600">Product form</p>
                <h2 id="inventoryModalTitle" class="mt-2 text-2xl font-bold text-slate-900">Add product</h2>
            </div>
            <button id="closeInventoryModalBtn" type="button" class="rounded-full border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">✕</button>
        </div>

        <form id="inventoryForm" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" id="inventoryProductId" name="id" value="">
            <input type="hidden" id="inventoryAction" name="action" value="create">

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="inventoryName" class="mb-2 block text-sm font-medium text-slate-700">Product name</label>
                    <input id="inventoryName" name="name" type="text" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 transition focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100" placeholder="Enter product name">
                </div>
                <div>
                    <label for="inventoryPrice" class="mb-2 block text-sm font-medium text-slate-700">Price</label>
                    <input id="inventoryPrice" name="price" type="number" step="0.01" min="0" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 transition focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100" placeholder="0.00">
                </div>
                <div>
                    <label for="inventoryStock" class="mb-2 block text-sm font-medium text-slate-700">Stock</label>
                    <input id="inventoryStock" name="stock" type="number" min="0" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 transition focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100" placeholder="0">
                </div>
                <div>
                    <label for="inventoryCategory" class="mb-2 block text-sm font-medium text-slate-700">Category</label>
                    <select id="inventoryCategory" name="category_id" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 transition focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100">
                        <option value="">Select category</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="inventoryDescription" class="mb-2 block text-sm font-medium text-slate-700">Description</label>
                <textarea id="inventoryDescription" name="description" rows="4" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 transition focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100" placeholder="Write a short product description"></textarea>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Image</label>
                <div class="flex flex-col gap-3 md:flex-row md:items-center">
                    <input id="inventoryImageUrl" name="image_url" type="url" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 transition focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100" placeholder="Paste image URL (optional)">
                    <label for="inventoryImageFile" class="inline-flex cursor-pointer items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Upload image</label>
                    <input id="inventoryImageFile" name="image_file" type="file" accept="image/jpeg,image/png,image/webp" class="hidden">
                </div>
                <p class="mt-2 text-sm text-slate-500">Use either a URL or upload a file. JPG, PNG, and WEBP supported.</p>
                <div id="inventoryPreview" class="mt-4 hidden">
                    <img id="inventoryPreviewImage" src="" alt="Preview" class="h-24 w-24 rounded-xl border border-slate-200 object-cover bg-slate-100">
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3">
                <button id="inventoryCancelBtn" type="button" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</button>
                <button id="inventorySubmitBtn" type="submit" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">Add product</button>
            </div>
        </form>
    </div>
</div>

<script>
const inventoryBody = document.getElementById("inventoryBody");
const inventoryMessage = document.getElementById("inventoryMessage");
const inventorySearch = document.getElementById("inventorySearch");
const inventoryFilter = document.getElementById("inventoryFilter");
const inventoryModal = document.getElementById("inventoryProductModal");
const inventoryModalTitle = document.getElementById("inventoryModalTitle");
const openInventoryModalBtn = document.getElementById("openInventoryModalBtn");
const closeInventoryModalBtn = document.getElementById("closeInventoryModalBtn");
const inventoryForm = document.getElementById("inventoryForm");
const inventoryName = document.getElementById("inventoryName");
const inventoryPrice = document.getElementById("inventoryPrice");
const inventoryStock = document.getElementById("inventoryStock");
const inventoryCategory = document.getElementById("inventoryCategory");
const inventoryDescription = document.getElementById("inventoryDescription");
const inventoryImageUrl = document.getElementById("inventoryImageUrl");
const inventoryImageFile = document.getElementById("inventoryImageFile");
const inventoryProductId = document.getElementById("inventoryProductId");
const inventoryAction = document.getElementById("inventoryAction");
const inventorySubmitBtn = document.getElementById("inventorySubmitBtn");
const inventoryCancelBtn = document.getElementById("inventoryCancelBtn");
const inventoryPreview = document.getElementById("inventoryPreview");
const inventoryPreviewImage = document.getElementById("inventoryPreviewImage");
let inventoryItems = [];
let productsById = {};

function getProductImageUrl(path) {
    if (!path) return "../assets/images/products/default.jpg";
    if (/^https?:\/\//i.test(path) || path.startsWith("/")) return path;
    if (path.startsWith("../") || path.startsWith("./")) return path;
    return "../" + path.replace(/^\.?\//, "");
}

function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>\'\"]/g, character => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", "'": "&#039;", "\"": "&quot;" })[character]);
}

function setInventoryMessage(message, type = "error") {
    inventoryMessage.textContent = message;
    inventoryMessage.className = `px-5 py-4 text-sm ${type === "success" ? "bg-emerald-50 text-emerald-700" : "bg-red-50 text-red-700"}`;
    inventoryMessage.classList.remove("hidden");
}

function showInventoryFieldError(input, message) {
    if (!input || !input.parentElement) return;

    const parent = input.parentElement;
    const existingError = parent.querySelector(".inventory-error");
    if (existingError) existingError.remove();

    input.classList.add("border-red-500", "focus:border-red-500");
    input.classList.remove("border-slate-300");

    const error = document.createElement("p");
    error.className = "inventory-error mt-2 text-xs text-red-600";
    error.textContent = message;
    parent.appendChild(error);
}

function clearInventoryFieldError(input) {
    if (!input || !input.parentElement) return;

    const parent = input.parentElement;
    const existingError = parent.querySelector(".inventory-error");
    if (existingError) existingError.remove();

    input.classList.remove("border-red-500", "focus:border-red-500");
    input.classList.add("border-slate-300");
}

function clearInventoryFieldErrors() {
    const fields = [inventoryName, inventoryPrice, inventoryStock, inventoryCategory, inventoryDescription, inventoryImageUrl];
    fields.forEach(field => {
        if (field) clearInventoryFieldError(field);
    });
}

function validateInventoryName() {
    const value = inventoryName.value.trim();
    if (!value) {
        showInventoryFieldError(inventoryName, "Product name is required.");
        return false;
    }
    if (value.length < 3) {
        showInventoryFieldError(inventoryName, "Product name must be at least 3 characters.");
        return false;
    }
    if (value.length > 100) {
        showInventoryFieldError(inventoryName, "Product name cannot exceed 100 characters.");
        return false;
    }
    if (!/^[A-Za-z][A-Za-z0-9\s&'().\/-]*$/.test(value)) {
        showInventoryFieldError(inventoryName, "Product name must start with a letter and contain valid characters only.");
        return false;
    }
    if (!/[A-Za-z]/.test(value)) {
        showInventoryFieldError(inventoryName, "Product name must contain at least one letter.");
        return false;
    }
    if (/\s{2,}/.test(value)) {
        showInventoryFieldError(inventoryName, "Product name cannot contain multiple spaces.");
        return false;
    }
    clearInventoryFieldError(inventoryName);
    return true;
}

function validateInventoryForm() {
    let valid = true;

    if (!validateInventoryName()) valid = false;

    const priceValue = Number(inventoryPrice.value);
    if (inventoryPrice.value.trim() === "") {
        showInventoryFieldError(inventoryPrice, "Price is required.");
        valid = false;
    } else if (!Number.isFinite(priceValue) || priceValue <= 0) {
        showInventoryFieldError(inventoryPrice, "Enter a valid price greater than 0.");
        valid = false;
    } else {
        clearInventoryFieldError(inventoryPrice);
    }

    const stockValue = Number(inventoryStock.value);
    if (inventoryStock.value.trim() === "") {
        showInventoryFieldError(inventoryStock, "Stock quantity is required.");
        valid = false;
    } else if (!Number.isInteger(stockValue) || stockValue < 0) {
        showInventoryFieldError(inventoryStock, "Stock must be a whole number and cannot be negative.");
        valid = false;
    } else {
        clearInventoryFieldError(inventoryStock);
    }

    if (!inventoryCategory.value) {
        showInventoryFieldError(inventoryCategory, "Please select a category.");
        valid = false;
    } else {
        clearInventoryFieldError(inventoryCategory);
    }

    const descriptionValue = inventoryDescription.value.trim();
    if (!descriptionValue) {
        showInventoryFieldError(inventoryDescription, "Product description is required.");
        valid = false;
    } else if (descriptionValue.length < 10) {
        showInventoryFieldError(inventoryDescription, "Description must be at least 10 characters.");
        valid = false;
    } else {
        clearInventoryFieldError(inventoryDescription);
    }

    const imageValue = inventoryImageUrl.value.trim();
    if (imageValue && !/^https?:\/\//i.test(imageValue)) {
        showInventoryFieldError(inventoryImageUrl, "Image URL must start with http:// or https://.");
        valid = false;
    } else {
        clearInventoryFieldError(inventoryImageUrl);
    }

    return valid;
}

function getStockStatus(stock) {
    const value = Number(stock) || 0;
    if (value === 0) return { label: "Out of stock", className: "bg-red-100 text-red-700" };
    if (value <= 5) return { label: "Low stock", className: "bg-amber-100 text-amber-700" };
    return { label: "Healthy", className: "bg-emerald-100 text-emerald-700" };
}

function openInventoryModal(product = null) {
    resetInventoryForm();

    if (product) {
        inventoryProductId.value = product.id;
        inventoryAction.value = "update";
        inventoryModalTitle.textContent = "Edit product";
        inventoryName.value = product.name || "";
        inventoryPrice.value = product.price || "";
        inventoryStock.value = product.stock || "";
        inventoryDescription.value = product.description || "";
        inventoryImageUrl.value = product.image ? product.image : "";
        inventoryImageFile.value = "";

        if (product.image) {
            inventoryPreviewImage.src = getProductImageUrl(product.image);
            inventoryPreview.classList.remove("hidden");
        } else {
            inventoryPreview.classList.add("hidden");
            inventoryPreviewImage.src = "";
        }

        const categoryMatch = Array.from(inventoryCategory.options).find(option => option.textContent === product.category);
        inventoryCategory.value = categoryMatch ? categoryMatch.value : "";
        inventorySubmitBtn.textContent = "Update product";
    } else {
        inventoryModalTitle.textContent = "Add product";
        inventoryAction.value = "create";
        inventorySubmitBtn.textContent = "Add product";
    }

    inventoryModal.classList.remove("hidden");
    inventoryModal.classList.add("flex");
}

function closeInventoryModal() {
    inventoryModal.classList.add("hidden");
    inventoryModal.classList.remove("flex");
    resetInventoryForm();
}

function resetInventoryForm() {
    inventoryForm.reset();
    inventoryProductId.value = "";
    inventoryAction.value = "create";
    inventorySubmitBtn.textContent = "Add product";
    inventoryPreview.classList.add("hidden");
    inventoryPreviewImage.src = "";
    inventoryModalTitle.textContent = "Add product";
    clearInventoryFieldErrors();
}

async function loadCategories() {
    try {
        const response = await fetch("../api/categories.php");
        const result = await response.json();
        if (!result.success) return;

        inventoryCategory.innerHTML = '<option value="">Select category</option>';
        result.data.forEach(category => {
            const option = document.createElement("option");
            option.value = category.id;
            option.textContent = category.name;
            inventoryCategory.appendChild(option);
        });
    } catch (error) {
        console.error("Category loading error:", error);
    }
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
        const stockValue = Number(item.stock) || 0;
        return `
            <tr class="hover:bg-slate-50">
                <td class="px-5 py-4">
                    <div class="flex items-center gap-3">
                        <img src="${escapeHtml(getProductImageUrl(item.image))}" alt="${escapeHtml(item.name)}" class="h-12 w-12 rounded-lg border border-slate-200 object-cover bg-slate-100" onerror="this.src='../assets/images/products/default.jpg'">
                        <div>
                            <p class="font-semibold text-slate-800">${escapeHtml(item.name)}</p>
                            <p class="text-xs text-slate-500">#${escapeHtml(item.id)}</p>
                        </div>
                    </div>
                </td>
                <td class="px-5 py-4 text-slate-600">${escapeHtml(item.category || "Uncategorized")}</td>
                <td class="px-5 py-4">
                    <div class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5">
                        <span class="text-lg font-bold text-slate-800">${escapeHtml(stockValue)}</span>
                        <span class="text-xs uppercase tracking-wide text-slate-400">units</span>
                    </div>
                </td>
                <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${status.className}">${status.label}</span></td>
                <td class="px-5 py-4">
                    <div class="flex flex-wrap justify-end gap-2">
                        <button type="button" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50" data-edit-product="${escapeHtml(item.id)}">Edit</button>
                        <button type="button" class="rounded-lg border border-red-200 bg-red-50 px-2 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100" data-delete-product="${escapeHtml(item.id)}">Delete</button>
                        <button type="button" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50" data-adjust="${escapeHtml(item.id)}" data-delta="-5">-5</button>
                        <button type="button" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50" data-adjust="${escapeHtml(item.id)}" data-delta="10">+10</button>
                        <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2">
                            <input type="number" min="0" value="${escapeHtml(stockValue)}" class="w-16 border-0 bg-transparent text-center text-sm font-semibold text-slate-700 outline-none" data-stock-input="${escapeHtml(item.id)}">
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
        productsById = Object.fromEntries(result.data.map(item => [item.id, item]));
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

async function deleteInventoryProduct(id) {
    const product = productsById[id];
    if (!product || !window.confirm(`Delete ${product.name}?`)) return;

    try {
        const response = await fetch(`../api/products.php?id=${encodeURIComponent(id)}`, { method: "DELETE" });
        const result = await response.json();
        if (!result.success) throw new Error(result.message || "Unable to delete product.");
        setInventoryMessage(result.message || "Product deleted successfully.", "success");
        await loadInventory();
    } catch (error) {
        setInventoryMessage(error.message || "Unable to delete product.");
    }
}

inventoryName.addEventListener("input", () => {
    if (inventoryName.value.trim() === "") {
        showInventoryFieldError(inventoryName, "Product name is required.");
        return;
    }
    validateInventoryName();
});

inventoryPrice.addEventListener("input", () => {
    const value = inventoryPrice.value.trim();
    if (!value) {
        showInventoryFieldError(inventoryPrice, "Price is required.");
        return;
    }
    if (!Number.isFinite(Number(value)) || Number(value) <= 0) {
        showInventoryFieldError(inventoryPrice, "Enter a valid price greater than 0.");
        return;
    }
    clearInventoryFieldError(inventoryPrice);
});

inventoryStock.addEventListener("input", () => {
    const value = inventoryStock.value.trim();
    if (!value) {
        showInventoryFieldError(inventoryStock, "Stock quantity is required.");
        return;
    }
    if (!Number.isInteger(Number(value)) || Number(value) < 0) {
        showInventoryFieldError(inventoryStock, "Stock must be a whole number and cannot be negative.");
        return;
    }
    clearInventoryFieldError(inventoryStock);
});

inventoryCategory.addEventListener("change", () => {
    if (!inventoryCategory.value) {
        showInventoryFieldError(inventoryCategory, "Please select a category.");
        return;
    }
    clearInventoryFieldError(inventoryCategory);
});

inventoryDescription.addEventListener("input", () => {
    const value = inventoryDescription.value.trim();
    if (!value) {
        showInventoryFieldError(inventoryDescription, "Product description is required.");
        return;
    }
    if (value.length < 10) {
        showInventoryFieldError(inventoryDescription, "Description must be at least 10 characters.");
        return;
    }
    clearInventoryFieldError(inventoryDescription);
});

inventoryImageUrl.addEventListener("input", () => {
    const value = inventoryImageUrl.value.trim();
    if (!value) {
        clearInventoryFieldError(inventoryImageUrl);
        return;
    }
    if (!/^https?:\/\//i.test(value)) {
        showInventoryFieldError(inventoryImageUrl, "Image URL must start with http:// or https://.");
        return;
    }
    clearInventoryFieldError(inventoryImageUrl);
});

inventoryForm.addEventListener("submit", async event => {
    event.preventDefault();
    clearInventoryFieldErrors();

    if (!validateInventoryForm()) {
        const firstError = document.querySelector(".inventory-error");
        if (firstError && firstError.parentElement) {
            const input = firstError.parentElement.querySelector("input, select, textarea");
            if (input) input.focus();
        }
        return;
    }

    const formData = new FormData(inventoryForm);
    const isUpdate = inventoryAction.value === "update";

    try {
        const response = await fetch("../api/products.php", { method: "POST", body: formData });
        const result = await response.json();
        if (!result.success) throw new Error(result.message || "Unable to save product.");

        setInventoryMessage(result.message || (isUpdate ? "Product updated successfully." : "Product added successfully."), "success");
        closeInventoryModal();
        await loadInventory();
    } catch (error) {
        setInventoryMessage(error.message || "Unable to save product.");
    }
});

inventoryImageFile.addEventListener("change", function () {
    const file = this.files[0];
    if (!file) return;

    const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
    if (!allowedTypes.includes(file.type)) {
        setInventoryMessage("Only JPG, PNG, and WEBP images are supported.");
        this.value = "";
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        setInventoryMessage("Image size must be less than 2 MB.");
        this.value = "";
        return;
    }

    inventoryImageUrl.value = "";
    inventoryPreviewImage.src = URL.createObjectURL(file);
    inventoryPreview.classList.remove("hidden");
});

openInventoryModalBtn.addEventListener("click", () => openInventoryModal());
closeInventoryModalBtn.addEventListener("click", closeInventoryModal);
inventoryCancelBtn.addEventListener("click", closeInventoryModal);
inventoryModal.addEventListener("click", event => {
    if (event.target === inventoryModal) {
        closeInventoryModal();
    }
});

inventoryBody.addEventListener("click", event => {
    const editButton = event.target.closest("[data-edit-product]");
    if (editButton) {
        const id = editButton.dataset.editProduct;
        const product = productsById[id];
        if (product) openInventoryModal(product);
        return;
    }

    const deleteButton = event.target.closest("[data-delete-product]");
    if (deleteButton) {
        deleteInventoryProduct(deleteButton.dataset.deleteProduct);
        return;
    }

    const adjustButton = event.target.closest("[data-adjust]");
    if (adjustButton) {
        const id = adjustButton.dataset.adjust;
        const delta = Number(adjustButton.dataset.delta || 0);
        updateInventoryStock(id, "add", delta);
        return;
    }

    const setButton = event.target.closest("[data-set-stock]");
    if (setButton) {
        const id = setButton.dataset.setStock;
        const input = inventoryBody.querySelector(`[data-stock-input="${id}"]`);
        const value = Number(input?.value ?? 0);
        if (Number.isFinite(value)) {
            updateInventoryStock(id, "set", Math.max(0, value));
        }
    }
});

inventorySearch.addEventListener("input", renderInventory);
inventoryFilter.addEventListener("change", renderInventory);
document.getElementById("refreshInventory").addEventListener("click", loadInventory);

loadCategories();
loadInventory();
</script>
