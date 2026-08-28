<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Product Management</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

    <div class="max-w-6xl mx-auto px-6 py-10">

        <!-- PAGE TITLE -->
        <h1 class="text-3xl font-bold text-gray-800 mb-8">
            Product Management
        </h1>


        <!-- ==========================================
             ADD PRODUCT FORM
        =========================================== -->

        <div class="bg-white rounded-xl shadow p-6 mb-8">

            <h2 class="text-xl font-semibold mb-6">
                Add New Product
            </h2>

            <form id="productForm" enctype="multipart/form-data">

                <!-- INPUT GRID -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">


                    <!-- PRODUCT NAME -->
                    <div>

                        <label
                            for="name"
                            class="block text-sm font-medium mb-2">

                            Product Name

                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Enter product name">

                    </div>


                    <!-- PRICE -->
                    <div>

                        <label
                            for="price"
                            class="block text-sm font-medium mb-2">

                            Price

                        </label>

                        <input
                            type="number"
                            id="price"
                            name="price"
                            step="0.01"
                            min="0"
                            class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Enter price">

                    </div>


                    <!-- STOCK -->
                    <div>

                        <label
                            for="stock"
                            class="block text-sm font-medium mb-2">

                            Stock

                        </label>

                        <input
                            type="number"
                            id="stock"
                            name="stock"
                            min="0"
                            class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Enter stock quantity">

                    </div>


                    <!-- CATEGORY -->
                    <div>

                        <label
                            for="category_id"
                            class="block text-sm font-medium mb-2">

                            Category

                        </label>

                        <select
                            id="category_id"
                            name="category_id"
                            class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">

                            <option value="">
                                Select Category
                            </option>

                        </select>

                    </div>

                </div>


                <!-- ==========================================
                     DESCRIPTION
                =========================================== -->

                <div class="mt-5">

                    <label
                        for="description"
                        class="block text-sm font-medium mb-2">

                        Description

                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter product description"></textarea>

                </div>


                <!-- ==========================================
                     IMAGE
                =========================================== -->

                <div class="mt-5">

                    <label
                        class="block text-sm font-medium mb-2">

                        Product Image

                    </label>


                    <div class="flex flex-col md:flex-row gap-3">

                        <!-- IMAGE URL -->

                        <input
                            type="url"
                            id="image_url"
                            name="image_url"
                            class="flex-1 border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="https://example.com/image.jpg">


                        <!-- UPLOAD BUTTON -->

                        <label
                            for="image_file"
                            class="cursor-pointer bg-gray-800 hover:bg-gray-900 text-white px-5 py-3 rounded-lg flex items-center justify-center">

                            Upload Image

                        </label>


                        <!-- FILE INPUT -->

                        <input
                            type="file"
                            id="image_file"
                            name="image_file"
                            accept="image/jpeg,image/png,image/webp"
                            class="hidden">

                    </div>


                    <p class="text-sm text-gray-500 mt-2">

                        Use an image URL or upload an image.
                        Maximum size: 2 MB.

                    </p>


                    <!-- IMAGE PREVIEW -->

                    <div
                        id="imagePreview"
                        class="mt-4 hidden">

                        <img
                            id="previewImg"
                            src=""
                            alt="Image Preview"
                            class="w-32 h-32 object-cover rounded-lg border">

                    </div>

                </div>


                <!-- ==========================================
                     SUBMIT BUTTON
                =========================================== -->

                <button
                    type="submit"
                    id="submitBtn"
                    class="mt-6 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg">

                    Add Product

                </button>

            </form>

        </div>


        <!-- ==========================================
             PRODUCT LIST
        =========================================== -->

        <div class="bg-white rounded-xl shadow p-6">

            <h2 class="text-xl font-semibold mb-6">
                Products
            </h2>


            <div id="productsContainer">

                <p class="text-gray-500">
                    Loading products...
                </p>

            </div>

        </div>

    </div>


    <!-- ==============================================
         JAVASCRIPT
    =============================================== -->

<script>

const form = document.getElementById("productForm");
const productsContainer = document.getElementById("productsContainer");
const categorySelect = document.getElementById("category_id");
const imageFile = document.getElementById("image_file");
const imageUrl = document.getElementById("image_url");
const imagePreview = document.getElementById("imagePreview");
const previewImg = document.getElementById("previewImg");
const submitBtn = document.getElementById("submitBtn");

const name = document.getElementById("name");
const price = document.getElementById("price");
const stock = document.getElementById("stock");
const description = document.getElementById("description");


// =====================================================
// VALIDATION MESSAGE
// =====================================================

function showError(input, message) {

    clearError(input);

    input.classList.add("border-red-500");
    input.classList.remove("border-gray-300");

    const error = document.createElement("p");

    error.className =
        "validation-error text-red-500 text-sm mt-1";

    error.textContent = message;

    input.parentElement.appendChild(error);
}


// =====================================================
// CLEAR ERROR
// =====================================================

function clearError(input) {

    input.classList.remove("border-red-500");
    input.classList.add("border-gray-300");

    const oldError =
        input.parentElement.querySelector(
            ".validation-error"
        );

    if (oldError) {
        oldError.remove();
    }
}


// =====================================================
// CLEAR ALL ERRORS
// =====================================================

function clearAllErrors() {

    document
        .querySelectorAll(".validation-error")
        .forEach(error => error.remove());

    document
        .querySelectorAll(
            "#productForm input, #productForm textarea, #productForm select"
        )
        .forEach(input => {

            input.classList.remove("border-red-500");

        });
}


// =====================================================
// LOAD PRODUCTS
// =====================================================

async function loadProducts() {

    try {

        const response =
            await fetch("../api/products.php");

        const result =
            await response.json();


        if (!result.success) {

            productsContainer.innerHTML = `
                <p class="text-red-500">
                    ${result.message || "Failed to load products."}
                </p>
            `;

            return;
        }


        if (result.data.length === 0) {

            productsContainer.innerHTML = `
                <p class="text-gray-500">
                    No products found.
                </p>
            `;

            return;
        }


        productsContainer.innerHTML =
            result.data.map(product => `

                <div class="border rounded-lg p-4 mb-4">

                    <div class="flex gap-4">

                        ${
                            product.image
                            ?
                            `
                            <img
                                src="${product.image}"
                                class="w-24 h-24 object-cover rounded-lg border"
                                alt="${product.name}">
                            `
                            :
                            ""
                        }

                        <div>

                            <h3 class="font-semibold text-lg">
                                ${product.name}
                            </h3>

                            <p class="text-gray-600 mt-1">
                                ${product.description || ""}
                            </p>

                            <p class="font-semibold mt-2">
                                Price: $${product.price}
                            </p>

                            <p class="text-gray-600">
                                Stock: ${product.stock}
                            </p>

                            <p class="text-gray-500">
                                Category: ${product.category || "None"}
                            </p>

                        </div>

                    </div>

                </div>

            `).join("");


    } catch (error) {

        console.error(error);

        productsContainer.innerHTML = `
            <p class="text-red-500">
                Unable to connect to server.
            </p>
        `;
    }
}


// =====================================================
// LOAD CATEGORIES
// =====================================================

async function loadCategories() {

    try {

        const response =
            await fetch("../api/categories.php");

        const result =
            await response.json();


        if (!result.success) {
            return;
        }


        categorySelect.innerHTML = `
            <option value="">
                Select Category
            </option>
        `;


        result.data.forEach(category => {

            const option =
                document.createElement("option");

            option.value =
                category.id;

            option.textContent =
                category.name;

            categorySelect.appendChild(option);

        });


    } catch (error) {

        console.error(
            "Category loading error:",
            error
        );
    }
}


// =====================================================
// PRODUCT NAME VALIDATION
// =====================================================

function validateName() {

    const value =
        name.value.trim();


    // Must START with a letter
    // Allowed:
    // A-Z, a-z, 0-9, space, &, ', (, ), ., /, -

    const namePattern =
        /^[A-Za-z][A-Za-z0-9\s&'().\/-]*$/;


    // At least one alphabet character
    const hasLetter =
        /[A-Za-z]/;


    // Prevent multiple spaces
    const repeatedSpaces =
        /\s{2,}/;


    // Prevent repeated special characters
    const repeatedSpecial =
        /([&'().\/-])\1+/;


    if (value === "") {

        showError(
            name,
            "Product name is required."
        );

        return false;

    }


    if (value.length < 3) {

        showError(
            name,
            "Product name must be at least 3 characters."
        );

        return false;

    }


    if (value.length > 100) {

        showError(
            name,
            "Product name cannot exceed 100 characters."
        );

        return false;

    }


    if (!namePattern.test(value)) {

        showError(
            name,
            "Product name must start with a letter and contain only valid characters."
        );

        return false;

    }


    if (!hasLetter.test(value)) {

        showError(
            name,
            "Product name must contain at least one letter."
        );

        return false;

    }


    if (repeatedSpaces.test(value)) {

        showError(
            name,
            "Product name cannot contain multiple spaces."
        );

        return false;

    }


    if (repeatedSpecial.test(value)) {

        showError(
            name,
            "Product name contains repeated special characters."
        );

        return false;

    }


    clearError(name);

    return true;
}


// =====================================================
// REAL-TIME NAME VALIDATION
// =====================================================

name.addEventListener(
    "input",
    validateName
);


// =====================================================
// REAL-TIME PRICE VALIDATION
// =====================================================

price.addEventListener(
    "input",
    function () {

        const value =
            Number(this.value);


        if (this.value === "") {

            showError(
                this,
                "Price is required."
            );

        } else if (
            !Number.isFinite(value) ||
            value <= 0
        ) {

            showError(
                this,
                "Enter a valid price greater than 0."
            );

        } else {

            clearError(this);
        }
    }
);


// =====================================================
// REAL-TIME STOCK VALIDATION
// =====================================================

stock.addEventListener(
    "input",
    function () {

        const value =
            Number(this.value);


        if (this.value === "") {

            showError(
                this,
                "Stock quantity is required."
            );

        } else if (
            !Number.isInteger(value) ||
            value < 0
        ) {

            showError(
                this,
                "Stock must be a whole number and cannot be negative."
            );

        } else {

            clearError(this);
        }
    }
);


// =====================================================
// REAL-TIME CATEGORY VALIDATION
// =====================================================

categorySelect.addEventListener(
    "change",
    function () {

        if (this.value === "") {

            showError(
                this,
                "Please select a category."
            );

        } else {

            clearError(this);
        }
    }
);


// =====================================================
// REAL-TIME DESCRIPTION VALIDATION
// =====================================================

description.addEventListener(
    "input",
    function () {

        const value =
            this.value.trim();


        if (value === "") {

            showError(
                this,
                "Product description is required."
            );

        } else if (value.length < 10) {

            showError(
                this,
                "Description must be at least 10 characters."
            );

        } else {

            clearError(this);
        }
    }
);


// =====================================================
// IMAGE FILE PREVIEW
// =====================================================

imageFile.addEventListener(
    "change",
    function () {

        const file =
            this.files[0];


        if (!file) {
            return;
        }


        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];


        if (!allowedTypes.includes(file.type)) {

            showError(
                this.parentElement,
                "Only JPG, PNG and WEBP images are allowed."
            );

            this.value = "";

            imagePreview.classList.add(
                "hidden"
            );

            return;
        }


        if (file.size > 2 * 1024 * 1024) {

            showError(
                this.parentElement,
                "Image size must be less than 2 MB."
            );

            this.value = "";

            imagePreview.classList.add(
                "hidden"
            );

            return;
        }


        // Clear URL
        imageUrl.value = "";


        // Preview
        const url =
            URL.createObjectURL(file);

        previewImg.src =
            url;

        imagePreview.classList.remove(
            "hidden"
        );

    }
);


// =====================================================
// IMAGE URL
// =====================================================

imageUrl.addEventListener(
    "input",
    function () {

        const url =
            this.value.trim();


        if (url === "") {

            imagePreview.classList.add(
                "hidden"
            );

            return;
        }


        // Remove uploaded image
        imageFile.value = "";


        try {

            const parsedURL =
                new URL(url);


            if (
                parsedURL.protocol !== "http:" &&
                parsedURL.protocol !== "https:"
            ) {

                showError(
                    this,
                    "Image URL must start with http:// or https://."
                );

                imagePreview.classList.add(
                    "hidden"
                );

                return;
            }


            clearError(this);

            previewImg.src =
                url;


            previewImg.onload =
                function () {

                    imagePreview.classList.remove(
                        "hidden"
                    );
                };


            previewImg.onerror =
                function () {

                    showError(
                        imageUrl,
                        "This image URL could not be loaded."
                    );

                    imagePreview.classList.add(
                        "hidden"
                    );
                };


        } catch {

            showError(
                this,
                "Please enter a valid image URL."
            );

            imagePreview.classList.add(
                "hidden"
            );
        }
    }
);


// =====================================================
// FORM SUBMIT
// =====================================================

form.addEventListener(
    "submit",
    async function (e) {

        e.preventDefault();

        clearAllErrors();


        let valid = true;


        // -------------------------
        // NAME
        // -------------------------

        if (!validateName()) {
            valid = false;
        }


        // -------------------------
        // PRICE
        // -------------------------

        const priceValue =
            Number(price.value);


        if (price.value.trim() === "") {

            showError(
                price,
                "Price is required."
            );

            valid = false;

        } else if (
            !Number.isFinite(priceValue) ||
            priceValue <= 0
        ) {

            showError(
                price,
                "Enter a valid price greater than 0."
            );

            valid = false;
        }


        // -------------------------
        // STOCK
        // -------------------------

        const stockValue =
            Number(stock.value);


        if (stock.value.trim() === "") {

            showError(
                stock,
                "Stock quantity is required."
            );

            valid = false;

        } else if (
            !Number.isInteger(stockValue) ||
            stockValue < 0
        ) {

            showError(
                stock,
                "Stock must be a whole number and cannot be negative."
            );

            valid = false;
        }


        // -------------------------
        // CATEGORY
        // -------------------------

        if (categorySelect.value === "") {

            showError(
                categorySelect,
                "Please select a category."
            );

            valid = false;
        }


        // -------------------------
        // DESCRIPTION
        // -------------------------

        const descriptionValue =
            description.value.trim();


        if (descriptionValue === "") {

            showError(
                description,
                "Product description is required."
            );

            valid = false;

        } else if (
            descriptionValue.length < 10
        ) {

            showError(
                description,
                "Description must be at least 10 characters."
            );

            valid = false;
        }


        // -------------------------
        // IMAGE
        // -------------------------

        const url =
            imageUrl.value.trim();

        const file =
            imageFile.files[0];


        if (url === "" && !file) {

            showError(
                imageUrl,
                "Please provide an image URL or upload an image."
            );

            valid = false;
        }


        // -------------------------
        // STOP IF INVALID
        // -------------------------

        if (!valid) {

            const firstError =
                document.querySelector(
                    ".border-red-500"
                );


            if (firstError) {
                firstError.focus();
            }


            return;
        }


        // =================================================
        // SEND TO PHP
        // =================================================

        const formData =
            new FormData(form);


        submitBtn.disabled =
            true;


        submitBtn.textContent =
            "Adding Product...";


        try {

            const response =
                await fetch(
                    "../api/products.php",
                    {
                        method: "POST",
                        body: formData
                    }
                );


            const result =
                await response.json();


            // =================================================
            // SUCCESS
            // =================================================

            if (result.success) {

                const success =
                    document.createElement("div");


                success.className =
                    "bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-5";


                success.textContent =
                    result.message ||
                    "Product added successfully.";


                form.parentElement.insertBefore(
                    success,
                    form
                );


                setTimeout(() => {

                    success.remove();

                }, 3000);


                form.reset();


                imagePreview.classList.add(
                    "hidden"
                );


                previewImg.src = "";


                clearAllErrors();


                loadProducts();


            } else {

                const error =
                    document.createElement("div");


                error.className =
                    "bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-5";


                error.textContent =
                    result.message ||
                    "Unable to add product.";


                form.parentElement.insertBefore(
                    error,
                    form
                );


                setTimeout(() => {

                    error.remove();

                }, 4000);
            }


        } catch (error) {

            console.error(error);


            const errorBox =
                document.createElement("div");


            errorBox.className =
                "bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-5";


            errorBox.textContent =
                "Unable to connect to the server.";


            form.parentElement.insertBefore(
                errorBox,
                form
            );


            setTimeout(() => {

                errorBox.remove();

            }, 4000);
        }


        submitBtn.disabled =
            false;


        submitBtn.textContent =
            "Add Product";

    }
);


// =====================================================
// INITIAL LOAD
// =====================================================

loadProducts();

loadCategories();

</script>




</body>

</html>