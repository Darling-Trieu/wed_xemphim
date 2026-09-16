        </div>
    </div>
</div>
<?php if (!empty($pageScripts)): ?>
    <?php foreach ($pageScripts as $script): ?>
        <script src="<?= BASE_URL ?>/js/<?= htmlspecialchars($script) ?>.js"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
