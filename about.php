<?php
$pageTitle = "About Us";
require_once "includes/header.php";
?>

<section class="max-w-6xl mx-auto px-6 py-12 md:py-16">

    <!-- Hero -->
    <div class="text-center max-w-3xl mx-auto mb-12 ">
        <div class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 px-4 py-2 rounded-full text-sm font-semibold mb-5">
            🎵 About MusicPasal
        </div>

        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-5">
            Your Online Musical Instrument Store
        </h1>

        <p class="text-gray-600 text-lg leading-relaxed">
            MusicPasal is a web-based e-commerce platform developed to make
            buying musical instruments simple, convenient, and organized.
        </p>
    </div>

    <!-- Project Introduction -->
    <div class="grid md:grid-cols-2 gap-8 mb-10">

        <div class="bg-white rounded-2xl p-7 reveal">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-2xl mb-5">
                🎸
            </div>

            <h2 class="text-2xl font-bold text-gray-900 mb-3">
                About MusicPasal
            </h2>

            <p class="text-gray-600 leading-relaxed">
                MusicPasal is an e-commerce website designed for customers
                to browse musical instruments, view product information,
                check prices and availability, add products to their cart,
                place orders, and make payments online.
            </p>

            <p class="text-gray-600 leading-relaxed mt-4">
                The system also provides an administration panel where
                products, categories, customers, inventory, orders, and
                order statuses can be managed efficiently.
            </p>
        </div>

        <div class="bg-white rounded-2xl p-7 reveal">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-2xl mb-5">
                💻
            </div>

            <h2 class="text-2xl font-bold text-gray-900 mb-3">
                Academic Project
            </h2>

            <p class="text-gray-600 leading-relaxed">
                This project is created as a practical project for the
                <strong class="text-gray-800">BCA 5th Semester</strong>
                <strong class="text-gray-800">Management Information System (MIS)</strong>
                and
                <strong class="text-gray-800">E-Commerce</strong>
                subjects.
            </p>

            <p class="text-gray-600 leading-relaxed mt-4">
                It provides practical experience in developing a complete
                web-based information system and implementing the concepts
                learned during the semester.
            </p>
        </div>

    </div>

    <!-- Technologies -->
    <div class="bg-white rounded-2xl p-7 md:p-9 mb-10 reveal">
        <div class="text-center mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900">
                Technologies Used
            </h2>
            <p class="text-gray-500 mt-2">
                Technologies used to develop the MusicPasal system
            </p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">

            <div class="border border-gray-100 rounded-xl p-5 text-center">
                <div class="text-2xl mb-2">🌐</div>
                <h3 class="font-semibold text-gray-800">HTML</h3>
                <p class="text-xs text-gray-500 mt-1">Structure</p>
            </div>

            <div class="border border-gray-100 rounded-xl p-5 text-center">
                <div class="text-2xl mb-2">🎨</div>
                <h3 class="font-semibold text-gray-800">Tailwind CSS</h3>
                <p class="text-xs text-gray-500 mt-1">Interface</p>
            </div>

            <div class="border border-gray-100 rounded-xl p-5 text-center">
                <div class="text-2xl mb-2">⚡</div>
                <h3 class="font-semibold text-gray-800">JavaScript</h3>
                <p class="text-xs text-gray-500 mt-1">Interaction</p>
            </div>

            <div class="border border-gray-100 rounded-xl p-5 text-center">
                <div class="text-2xl mb-2">🐘</div>
                <h3 class="font-semibold text-gray-800">PHP</h3>
                <p class="text-xs text-gray-500 mt-1">Backend</p>
            </div>

            <div class="border border-gray-100 rounded-xl p-5 text-center">
                <div class="text-2xl mb-2">🗄️</div>
                <h3 class="font-semibold text-gray-800">MySQL</h3>
                <p class="text-xs text-gray-500 mt-1">Database</p>
            </div>

        </div>
    </div>

    <!-- Key Features -->
    <div class="bg-white rounded-2xl p-7 md:p-9 reveal">
        <div class="text-center mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900">
                What MusicPasal Provides
            </h2>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">

            <div class="flex gap-4 p-5 rounded-xl bg-gray-50">
                <span class="text-2xl">🛍️</span>
                <div>
                    <h3 class="font-semibold text-gray-900">Online Shopping</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Browse and purchase musical instruments online.
                    </p>
                </div>
            </div>

            <div class="flex gap-4 p-5 rounded-xl bg-gray-50">
                <span class="text-2xl">🛒</span>
                <div>
                    <h3 class="font-semibold text-gray-900">Shopping Cart</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Add, update, and manage selected products.
                    </p>
                </div>
            </div>

            <div class="flex gap-4 p-5 rounded-xl bg-gray-50">
                <span class="text-2xl">📦</span>
                <div>
                    <h3 class="font-semibold text-gray-900">Order Management</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Place orders and view order information.
                    </p>
                </div>
            </div>

            <div class="flex gap-4 p-5 rounded-xl bg-gray-50">
                <span class="text-2xl">💳</span>
                <div>
                    <h3 class="font-semibold text-gray-900">Online Payment</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Supports Cash on Delivery and eSewa testing payment.
                    </p>
                </div>
            </div>

            <div class="flex gap-4 p-5 rounded-xl bg-gray-50">
                <span class="text-2xl">⭐</span>
                <div>
                    <h3 class="font-semibold text-gray-900">Reviews & Ratings</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Customers can share ratings and product reviews.
                    </p>
                </div>
            </div>

            <div class="flex gap-4 p-5 rounded-xl bg-gray-50">
                <span class="text-2xl">⚙️</span>
                <div>
                    <h3 class="font-semibold text-gray-900">Admin Management</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Manage products, inventory, customers, and orders.
                    </p>
                </div>
            </div>

        </div>
    </div>

</section>

<?php require_once "includes/footer.php"; ?>
