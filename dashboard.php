<?php
/**
 * LaunchPad — Dashboard
 * Overview of career progress and activity
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$user = getCurrentUser();
$userId = getUserId();

// Gather dashboard statistics
$skillsCount = countUserRecords('skills', $userId);
$projectsCount = countUserRecords('projects', $userId);
$certificationsCount = countUserRecords('certifications', $userId);
$internshipsCount = countUserRecords('internships', $userId);
$careerProgress = calculateCareerProgress($userId);
$activities = getRecentActivity($userId);
$chartData = getDashboardChartData($userId);

// Prepare chart JSON for JavaScript
$categoryChart = array_map(fn($c) => [
    'label' => $c['category'],
    'count' => (int) $c['count'],
], $chartData['categories']);

$statusChart = array_map(fn($s) => [
    'label' => $s['status'],
    'count' => (int) $s['count'],
], $chartData['projectStatuses']);

$currentPage = 'dashboard';
$pageTitle = 'Dashboard';
$pageSubtitle = 'Your career command center';
$firstName = explode(' ', $user['full_name'])[0];

ob_start();
?>
<div class="welcome-banner">
    <h2>Welcome back, <?= e($firstName) ?> 👋</h2>
    <p>Track your skills, build projects, and launch your career — all in one place.</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Skills</span>
            <div class="stat-icon blue">◇</div>
        </div>
        <div class="stat-value"><?= $skillsCount ?></div>
        <div class="stat-change">Tracked competencies</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Projects</span>
            <div class="stat-icon emerald">▣</div>
        </div>
        <div class="stat-value"><?= $projectsCount ?></div>
        <div class="stat-change">Portfolio entries</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Certifications</span>
            <div class="stat-icon violet">◆</div>
        </div>
        <div class="stat-value"><?= $certificationsCount ?></div>
        <div class="stat-change">Credentials earned</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Internships</span>
            <div class="stat-icon amber">◎</div>
        </div>
        <div class="stat-value"><?= $internshipsCount ?></div>
        <div class="stat-change">Applications tracked</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Career Progress</div>
                <div class="card-subtitle">Overall readiness score</div>
            </div>
        </div>
        <div class="progress-ring-container">
            <div class="progress-ring">
                <svg width="140" height="140" viewBox="0 0 140 140">
                    <defs>
                        <linearGradient id="progressGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#60A5FA"/>
                            <stop offset="100%" stop-color="#10B981"/>
                        </linearGradient>
                    </defs>
                    <circle class="progress-ring-bg" cx="70" cy="70" r="60"/>
                    <circle class="progress-ring-fill" cx="70" cy="70" r="60"
                            data-percent="<?= $careerProgress ?>"/>
                </svg>
                <div class="progress-ring-text">
                    <span class="progress-ring-value"><?= $careerProgress ?>%</span>
                    <span class="progress-ring-label">Complete</span>
                </div>
            </div>
            <div class="progress-details">
                <div class="progress-detail-item">
                    <span class="progress-detail-label">Skills Mastery</span>
                    <span class="progress-detail-value"><?= $skillsCount ?> skills</span>
                </div>
                <div class="progress-detail-item">
                    <span class="progress-detail-label">Active Projects</span>
                    <span class="progress-detail-value"><?= $projectsCount ?> total</span>
                </div>
                <div class="progress-detail-item">
                    <span class="progress-detail-label">Certifications</span>
                    <span class="progress-detail-value"><?= $certificationsCount ?> earned</span>
                </div>
                <div class="progress-detail-item">
                    <span class="progress-detail-label">Internship Pipeline</span>
                    <span class="progress-detail-value"><?= $internshipsCount ?> tracked</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Skills by Category</div>
                <div class="card-subtitle">Distribution overview</div>
            </div>
        </div>
        <?php if (empty($categoryChart)): ?>
            <div class="chart-empty">Add skills to see your category breakdown</div>
        <?php else: ?>
            <div class="chart-container" data-chart='<?= e(json_encode($categoryChart)) ?>'></div>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Project Status</div>
                <div class="card-subtitle">Pipeline breakdown</div>
            </div>
        </div>
        <?php if (empty($statusChart)): ?>
            <div class="chart-empty">Start a project to track your progress</div>
        <?php else: ?>
            <div class="chart-container" data-chart='<?= e(json_encode($statusChart)) ?>'></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Recent Activity</div>
                <div class="card-subtitle">Latest updates across modules</div>
            </div>
        </div>
        <?php if (empty($activities)): ?>
            <div class="empty-state" style="padding: 24px;">
                <p>No activity yet. Start by adding a skill or project!</p>
            </div>
        <?php else: ?>
            <ul class="activity-list">
                <?php
                $dotColors = ['blue', 'emerald', 'violet', 'amber'];
                foreach ($activities as $i => $activity):
                    $dotClass = $dotColors[$i % count($dotColors)];
                ?>
                    <li class="activity-item">
                        <span class="activity-dot <?= $dotClass ?>"></span>
                        <div class="activity-content">
                            <div class="activity-label"><?= e($activity['label']) ?></div>
                            <div class="activity-title"><?= e($activity['title']) ?></div>
                            <div class="activity-time"><?= formatDate($activity['time']) ?></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
