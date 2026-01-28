</div>

<footer class="p-6">
    <hr class="h-px mb-6 bg-gray-200 border-0 dark:bg-gray-700">
    <div class="text-center">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            สถาบันวิจัยพุทธศาสตร์ | มหาวิทยาลัยมหาจุฬาลงกรณราชวิทยาลัย
        </p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
            © 2569 Buddhist Research Institute
        </p>
    </div>
</footer>
</div>

<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest"></script>
<!-- Flowbite JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
<script>
    var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
    var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

    // Change the icons inside the button based on previous settings
    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        themeToggleLightIcon.classList.remove('hidden');
    } else {
        themeToggleDarkIcon.classList.remove('hidden');
    }

    var themeToggleBtn = document.getElementById('theme-toggle');

    themeToggleBtn.addEventListener('click', function () {
        // toggle icons inside button
        themeToggleDarkIcon.classList.toggle('hidden');
        themeToggleLightIcon.classList.toggle('hidden');

        // if set via local storage previously
        if (localStorage.getItem('color-theme')) {
            if (localStorage.getItem('color-theme') === 'light') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            }

            // if NOT set via local storage previously
        } else {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
        }
    });

    // Initialize Lucide Icons
    if (window.lucide) {
        lucide.createIcons();
    }
</script>
</body>

</html>