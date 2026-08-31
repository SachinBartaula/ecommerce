</main>

    <!-- ==========================================
         FOOTER
    =========================================== -->
    <footer class="bg-white border-t mt-12">
        <div class="max-w-6xl mx-auto px-6 py-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">

                <span class="text-sm text-gray-500 text-center sm:text-left">
                    &copy; <?php echo date("Y"); ?> MusicPasal. All rights reserved.
                </span>

                <nav class="flex items-center gap-5 text-sm font-medium text-gray-500">
                    <a
                        href="<?php echo $basePath; ?>/about.php"
                        class="hover:text-blue-600 transition-colors duration-200"
                    >
                        About Us
                    </a>

                    <span class="text-gray-300">|</span>

                    <a
                        href="<?php echo $basePath; ?>/settings.php"
                        class="hover:text-blue-600 transition-colors duration-200"
                    >
                        Settings
                    </a>
                </nav>

            </div>
        </div>
    </footer>

    <script>
        // Add shadow to navbar once the page is scrolled
        const siteNav = document.getElementById("site-nav");

        if (siteNav) {
            const toggleNavShadow = () => {
                if (window.scrollY > 8) {
                    siteNav.classList.add("shadow-md");
                } else {
                    siteNav.classList.remove("shadow-md");
                }
            };

            toggleNavShadow();
            window.addEventListener("scroll", toggleNavShadow);
        }

        // Mobile hamburger menu toggle
        const menuBtn = document.getElementById("mobile-menu-btn");
        const mobileMenu = document.getElementById("mobile-menu");

        if (menuBtn && mobileMenu) {
            const lines = menuBtn.querySelectorAll(".hamburger-line");

            menuBtn.addEventListener("click", () => {
                const isOpen = mobileMenu.classList.contains("menu-open");

                if (isOpen) {
                    mobileMenu.style.maxHeight = "0px";
                    mobileMenu.classList.remove("menu-open");
                    lines[0].style.transform = "";
                    lines[1].style.opacity = "1";
                    lines[2].style.transform = "";
                    menuBtn.setAttribute("aria-expanded", "false");
                } else {
                    mobileMenu.style.maxHeight = mobileMenu.scrollHeight + "px";
                    mobileMenu.classList.add("menu-open");
                    lines[0].style.transform = "translateY(8px) rotate(45deg)";
                    lines[1].style.opacity = "0";
                    lines[2].style.transform = "translateY(-8px) rotate(-45deg)";
                    menuBtn.setAttribute("aria-expanded", "true");
                }
            });
        }

        // Live cart-count badge
        function updateCartBadge(count) {
            const badge = document.getElementById("cart-badge");
            const badgeMobile = document.getElementById("cart-badge-mobile");

            [badge, badgeMobile].forEach((el) => {
                if (!el) return;
                el.textContent = count > 99 ? "99+" : count;
                if (count > 0) {
                    el.classList.remove("hidden");
                    el.classList.add("flex");
                } else {
                    el.classList.add("hidden");
                    el.classList.remove("flex");
                }
            });
        }

        // Expose globally so product pages can call it after "Add to Cart"
        window.updateCartBadge = updateCartBadge;

        <?php if ($isLoggedIn): ?>
        fetch("<?php echo $basePath; ?>/api/cart.php?action=count")
            .then((r) => r.json())
            .then((data) => {
                if (data.success) {
                    updateCartBadge(data.total_count);
                }
            })
            .catch(() => {});
        <?php endif; ?>

        // Fade/slide elements into view as they enter the viewport
        const revealEls = document.querySelectorAll(".reveal");

        if (revealEls.length && "IntersectionObserver" in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("is-visible");
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });

            revealEls.forEach((el) => observer.observe(el));
        } else {
            revealEls.forEach((el) => el.classList.add("is-visible"));
        }
    </script>

</body>

</html>