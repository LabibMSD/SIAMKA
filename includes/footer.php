<?php
if (!defined("SECURE")) {
    die("Direct access not allowed");
}
?>

</main> <!-- .main-content -->
</div> <!-- .d-flex (wrapper) -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?= BASE_URL ?>assets/js/siamka-ui.js"></script>

<!-- Tambahan JS per halaman -->
<?php if (isset($additional_js)): ?>
    <?php foreach ($additional_js as $js): ?>
        <script src="<?= BASE_URL . $js ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Sidebar Fixed Behavior -->
<script>
    // Pastikan sidebar tidak ikut scroll
    document.addEventListener("DOMContentLoaded", () => {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.style.position = "fixed";
            sidebar.style.top = "0";
            sidebar.style.left = "0";
            sidebar.style.height = "100vh";
            sidebar.style.overflowY = "auto";
        }
    });
</script>

</body>

</html>