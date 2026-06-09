<?php
/**
 * Dashboard layout wrapper
 * Usage: set $pageTitle, $pageSubtitle, $currentPage before including
 */

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'LaunchPad') ?> — LaunchPad</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <main class="main-content">
            <header class="page-header">
                <div class="page-header-inner">
                    <button class="menu-toggle" aria-label="Toggle menu">☰</button>
                    <div style="flex: 1;">
                        <h1 class="page-title"><?= e($pageTitle ?? '') ?></h1>
                        <?php if (!empty($pageSubtitle)): ?>
                            <p class="page-subtitle"><?= e($pageSubtitle) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($headerAction)): ?>
                        <div><?= $headerAction ?></div>
                    <?php endif; ?>
                </div>
            </header>

            <div class="page-body">
                <?php
                $flash = getFlash();
                if ($flash):
                ?>
                    <div class="alert alert-<?= e($flash['type']) ?>">
                        <?= e($flash['message']) ?>
                    </div>
                <?php endif; ?>

                <?= $content ?? '' ?>
            </div>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
    <?php if (!empty($extraScripts)): ?>
        <?= $extraScripts ?>
    <?php endif; ?>
</body>
</html>
