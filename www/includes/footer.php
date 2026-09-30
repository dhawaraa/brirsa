<!-- Footer -->
<footer class="bg-mcu-blue text-white py-6 mt-auto">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <p class="text-sm md:text-base" data-th="<?php echo SITE_SUBTITLE; ?>"
            data-en="Buddhist Research Institute | Mahachulalongkornrajavidyalaya University">
            <?php echo SITE_SUBTITLE; ?>
        </p>
        <p class="text-xs text-gray-300 mt-1">©
            <span data-th="<?php echo date('Y') + 543; ?>"
                data-en="<?php echo date('Y'); ?>"><?php echo date('Y') + 543; ?></span>
            <span data-th="สถาบันวิจัยพุทธศาสตร์" data-en="Buddhist Research Institute">สถาบันวิจัยพุทธศาสตร์</span>
        </p>
    </div>
</footer>

<?php 
// Include Frontend Popup Modal
if (file_exists(__DIR__ . '/popup_modal.php')) {
    include __DIR__ . '/popup_modal.php';
}
?>

<!-- Initialize Lucide Icons -->
<script>
    lucide.createIcons();
</script>
</body>

</html>