<?php

$pageTitle = "Category Management";
require_once "../includes/admin-header.php";
?>

<main class="min-h-[calc(100vh-5rem)] bg-slate-100 py-8">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mb-7">
            <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Catalog</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Category Management</h1>
            <p class="mt-2 text-sm text-slate-500">Create and maintain the categories used across your products.</p>
        </div>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <h2 class="text-xl font-semibold text-slate-900">Categories</h2>
                <span id="categoryStatus" class="text-sm text-slate-500" role="status"></span>
            </div>

            <form id="categoryForm" class="mt-5 flex flex-col items-start gap-3 sm:flex-row">
                <input type="hidden" id="category_id" name="id" value="">
                <input type="hidden" id="category_action" name="action" value="create">
                <div class="w-full flex-1">
                    <label for="category_name" class="sr-only">Category name</label>
                    <input type="text" id="category_name" name="name" maxlength="100" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Category name" required>
                </div>
                <button type="submit" id="categorySubmitBtn" class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700">Add Category</button>
                <button type="button" id="cancelCategoryBtn" class="hidden rounded-lg border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
            </form>

            <div id="categoriesContainer" class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <p class="text-sm text-slate-500">Loading categories...</p>
            </div>
        </section>
    </div>
</main>

<script>
const categoryForm = document.getElementById("categoryForm");
const categoryId = document.getElementById("category_id");
const categoryAction = document.getElementById("category_action");
const categoryName = document.getElementById("category_name");
const categorySubmitBtn = document.getElementById("categorySubmitBtn");
const cancelCategoryBtn = document.getElementById("cancelCategoryBtn");
const categoriesContainer = document.getElementById("categoriesContainer");
const categoryStatus = document.getElementById("categoryStatus");
let categoriesById = {};

function showCategoryError(message) {
    clearCategoryError();
    categoryName.classList.add("border-red-500");
    const error = document.createElement("p");
    error.className = "category-validation-error mt-1 text-sm text-red-600";
    error.textContent = message;
    categoryName.parentElement.appendChild(error);
}

function clearCategoryError() {
    categoryName.classList.remove("border-red-500");
    const error = categoryName.parentElement.querySelector(".category-validation-error");
    if (error) error.remove();
}

function validateCategoryName() {
    const value = categoryName.value.trim();
    const namePattern = /^[A-Za-z][A-Za-z0-9\s&'().\/-]*$/;
    const repeatedSpecial = /([&'().\/-])\1+/;

    if (value === "") {
        showCategoryError("Category name is required.");
        return false;
    }
    if (value.length < 3) {
        showCategoryError("Category name must be at least 3 characters.");
        return false;
    }
    if (value.length > 100) {
        showCategoryError("Category name cannot exceed 100 characters.");
        return false;
    }
    if (!namePattern.test(value)) {
        showCategoryError("Category name must start with a letter and contain only valid characters.");
        return false;
    }
    if (!/[A-Za-z]/.test(value)) {
        showCategoryError("Category name must contain at least one letter.");
        return false;
    }
    if (/\s{2,}/.test(value)) {
        showCategoryError("Category name cannot contain multiple spaces.");
        return false;
    }
    if (repeatedSpecial.test(value)) {
        showCategoryError("Category name contains repeated special characters.");
        return false;
    }

    clearCategoryError();
    return true;
}

categoryName.addEventListener("input", validateCategoryName);

async function loadCategories() {
    try {
        const response = await fetch("../api/categories.php");
        const result = await response.json();
        if (!result.success) {
            categoryStatus.textContent = result.message || "Unable to load categories.";
            categoryStatus.className = "text-sm text-red-600";
            return;
        }

        categoriesById = Object.fromEntries(result.data.map(category => [category.id, category]));
        if (!result.data.length) {
            categoriesContainer.innerHTML = `<p class="text-sm text-slate-500">No categories found.</p>`;
            return;
        }

        categoriesContainer.innerHTML = result.data.map(category => `
            <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 p-4">
                <span class="truncate pr-3 text-sm font-semibold text-slate-700">${category.name}</span>
                <div class="flex shrink-0 gap-2">
                    <button type="button" class="text-sm font-semibold text-blue-600 hover:text-blue-800" onclick="editCategory(${category.id})">Edit</button>
                    <button type="button" class="text-sm font-semibold text-red-600 hover:text-red-800" onclick="deleteCategory(${category.id})">Delete</button>
                </div>
            </div>
        `).join("");
    } catch (error) {
        categoryStatus.textContent = "Unable to connect to the server.";
        categoryStatus.className = "text-sm text-red-600";
    }
}

function editCategory(id) {
    const category = categoriesById[id];
    if (!category) return;
    categoryId.value = category.id;
    categoryAction.value = "update";
    categoryName.value = category.name;
    categorySubmitBtn.textContent = "Update Category";
    cancelCategoryBtn.classList.remove("hidden");
    categoryName.focus();
}

function resetCategoryForm() {
    categoryForm.reset();
    categoryId.value = "";
    categoryAction.value = "create";
    categorySubmitBtn.textContent = "Add Category";
    cancelCategoryBtn.classList.add("hidden");
    categoryStatus.textContent = "";
}

async function deleteCategory(id) {
    const category = categoriesById[id];
    if (!category || !window.confirm(`Delete ${category.name}? Products will become uncategorized.`)) return;

    try {
        const response = await fetch(`../api/categories.php?id=${encodeURIComponent(id)}`, { method: "DELETE" });
        const result = await response.json();
        if (!result.success) {
            categoryStatus.textContent = result.message || "Unable to delete category.";
            categoryStatus.className = "text-sm text-red-600";
            return;
        }
        resetCategoryForm();
        loadCategories();
    } catch (error) {
        categoryStatus.textContent = "Unable to connect to the server.";
        categoryStatus.className = "text-sm text-red-600";
    }
}

categoryForm.addEventListener("submit", async event => {
    event.preventDefault();
    if (!validateCategoryName()) return;
    categoryStatus.textContent = "Saving...";
    categoryStatus.className = "text-sm text-slate-500";

    try {
        const response = await fetch("../api/categories.php", { method: "POST", body: new FormData(categoryForm) });
        const result = await response.json();
        if (!result.success) {
            categoryStatus.textContent = result.message || "Unable to save category.";
            categoryStatus.className = "text-sm text-red-600";
            return;
        }
        resetCategoryForm();
        categoryStatus.textContent = result.message;
        categoryStatus.className = "text-sm text-emerald-600";
        loadCategories();
    } catch (error) {
        categoryStatus.textContent = "Unable to connect to the server.";
        categoryStatus.className = "text-sm text-red-600";
    }
});

cancelCategoryBtn.addEventListener("click", resetCategoryForm);
loadCategories();
</script>