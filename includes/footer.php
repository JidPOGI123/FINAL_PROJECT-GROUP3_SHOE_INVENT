<footer class="site-footer">
        <div class="footer-inner">
            <span class="footer-brand">👟 ShStorage</span>
            <span class="footer-copy">&copy; <?= date('Y') ?> ShStorage. All rights reserved.</span>
            <span class="footer-stack">PHP &bull; MySQL</span>
        </div>
    </footer>

    <script src="<?= $jsPath ?? '../' ?>assets/js/main.js"></script>
    <?php if (isset($extraJs)): ?>
        <?php foreach ($extraJs as $js): ?>
            <script src="<?= $jsPath ?? '../' ?>assets/js/<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>