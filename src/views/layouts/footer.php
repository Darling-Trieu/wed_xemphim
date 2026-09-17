</main>
<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> <?= APP_NAME ?></p>
    </div>
</footer>
<script src="<?= BASE_URL ?>/js/nav.js"></script>
<?php if (!empty($pageScripts)): ?>
    <?php foreach ($pageScripts as $script): ?>
        <script src="<?= BASE_URL ?>/js/<?= htmlspecialchars($script) ?>.js"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
