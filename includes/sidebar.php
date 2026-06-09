<?php
/**
 * Sidebar navigation component
 * $currentPage should be set before including this file
 */

$user = getCurrentUser();
$initials = getUserInitials($user['full_name'] ?? 'User');
$currentPage = $currentPage ?? '';

$navItems = [
    ['page' => 'dashboard', 'label' => 'Dashboard', 'icon' => '◈', 'section' => 'Overview'],
    ['page' => 'skills', 'label' => 'Skills', 'icon' => '◇', 'section' => 'Career'],
    ['page' => 'projects', 'label' => 'Projects', 'icon' => '▣', 'section' => 'Career'],
    ['page' => 'certifications', 'label' => 'Certifications', 'icon' => '◆', 'section' => 'Career'],
    ['page' => 'internships', 'label' => 'Internships', 'icon' => '◎', 'section' => 'Career'],
    ['page' => 'profile', 'label' => 'Profile', 'icon' => '○', 'section' => 'Account'],
];
?>
<div class="sidebar-overlay"></div>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <div class="sidebar-brand-icon">LP</div>
            <span class="sidebar-brand-text">LaunchPad</span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <?php
        $lastSection = '';
        foreach ($navItems as $item):
            if ($item['section'] !== $lastSection):
                $lastSection = $item['section'];
        ?>
            <div class="nav-section-label"><?= e($item['section']) ?></div>
        <?php endif; ?>
            <a href="<?= e($item['page']) ?>.php"
               class="nav-link <?= $currentPage === $item['page'] ? 'active' : '' ?>">
                <span class="nav-icon"><?= $item['icon'] ?></span>
                <?= e($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar">
                <?php if (!empty($user['profile_photo'])): ?>
                    <img src="<?= e($user['profile_photo']) ?>" alt="Profile">
                <?php else: ?>
                    <?= e($initials) ?>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= e($user['full_name'] ?? '') ?></div>
                <div class="user-email"><?= e($user['email'] ?? '') ?></div>
            </div>
        </div>
        <a href="logout.php" class="nav-link" style="margin-top: 8px;">
            <span class="nav-icon">↗</span>
            Sign Out
        </a>
    </div>
</aside>
