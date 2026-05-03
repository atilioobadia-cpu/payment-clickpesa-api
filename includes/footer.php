<?php
declare(strict_types=1);
?>
<?php if ($authLayout ?? false): ?>
        </main>
    </div>
<?php else: ?>
            </main>
        </div>
    </div>
    </div>
<?php endif; ?>

<script>
    window.paymentSandbox = {
        basePath: <?= json_encode(route_path(''), JSON_UNESCAPED_SLASHES) ?>
    };
</script>
<script src="<?= e(route_path('assets/js/app.js')) ?>"></script>
</body>
</html>
