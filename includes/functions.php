<?php
/**
 * Shared utility functions for LaunchPad
 */

require_once __DIR__ . '/db.php';

/**
 * Sanitize user input for safe output
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to another page
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Set a one-time flash message
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Format date for display
 */
function formatDate(?string $date): string
{
    if (!$date) {
        return '—';
    }
    return date('M j, Y', strtotime($date));
}

/**
 * Count records for a user in a given table
 */
function countUserRecords(string $table, int $userId): int
{
    $allowed = ['skills', 'projects', 'certifications', 'internships'];
    if (!in_array($table, $allowed, true)) {
        return 0;
    }

    $db = getDB();
    $sql = "SELECT COUNT(*) AS total FROM {$table} WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int) ($row['total'] ?? 0);
}

/**
 * Calculate overall career progress percentage
 */
function calculateCareerProgress(int $userId): int
{
    $db = getDB();

    // Average skill progress (40% weight)
    $skillStmt = $db->prepare('SELECT AVG(progress_percent) AS avg_progress FROM skills WHERE user_id = ?');
    $skillStmt->bind_param('i', $userId);
    $skillStmt->execute();
    $skillAvg = (float) ($skillStmt->get_result()->fetch_assoc()['avg_progress'] ?? 0);
    $skillStmt->close();

    // Project completion rate (35% weight)
    $projStmt = $db->prepare('SELECT COUNT(*) AS total, SUM(status = "Completed") AS completed FROM projects WHERE user_id = ?');
    $projStmt->bind_param('i', $userId);
    $projStmt->execute();
    $projRow = $projStmt->get_result()->fetch_assoc();
    $projStmt->close();

    $projectRate = 0;
    if ((int) $projRow['total'] > 0) {
        $projectRate = ((int) $projRow['completed'] / (int) $projRow['total']) * 100;
    }

    // Certification bonus (15% weight, max 100 scaled)
    $certCount = countUserRecords('certifications', $userId);
    $certScore = min($certCount * 20, 100);

    // Internship progress (10% weight)
    $intStmt = $db->prepare('SELECT status FROM internships WHERE user_id = ?');
    $intStmt->bind_param('i', $userId);
    $intStmt->execute();
    $intResult = $intStmt->get_result();
    $intScore = 0;
    $intCount = 0;
    while ($row = $intResult->fetch_assoc()) {
        $intCount++;
        $statusScores = [
            'Applied' => 25,
            'Interview' => 50,
            'Offer' => 85,
            'Accepted' => 100,
            'Rejected' => 10,
        ];
        $intScore += $statusScores[$row['status']] ?? 0;
    }
    $intStmt->close();
    $intAvg = $intCount > 0 ? $intScore / $intCount : 0;

    $progress = ($skillAvg * 0.4) + ($projectRate * 0.35) + ($certScore * 0.15) + ($intAvg * 0.1);

    return (int) min(100, round($progress));
}

/**
 * Get recent activity across all modules
 */
function getRecentActivity(int $userId, int $limit = 6): array
{
    $db = getDB();
    $activities = [];

    $queries = [
        ['table' => 'skills', 'label' => 'Skill added', 'field' => 'name'],
        ['table' => 'projects', 'label' => 'Project updated', 'field' => 'title'],
        ['table' => 'certifications', 'label' => 'Certification earned', 'field' => 'name'],
        ['table' => 'internships', 'label' => 'Internship tracked', 'field' => 'company'],
    ];

    foreach ($queries as $q) {
        $sql = "SELECT {$q['field']} AS title, updated_at FROM {$q['table']} WHERE user_id = ? ORDER BY updated_at DESC LIMIT 3";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $activities[] = [
                'label' => $q['label'],
                'title' => $row['title'],
                'time' => $row['updated_at'],
            ];
        }
        $stmt->close();
    }

    usort($activities, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));

    return array_slice($activities, 0, $limit);
}

/**
 * Get chart data for dashboard
 */
function getDashboardChartData(int $userId): array
{
    $db = getDB();

    // Skills by category
    $catStmt = $db->prepare('SELECT category, COUNT(*) AS count FROM skills WHERE user_id = ? GROUP BY category ORDER BY count DESC LIMIT 5');
    $catStmt->bind_param('i', $userId);
    $catStmt->execute();
    $categories = $catStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $catStmt->close();

    // Project status breakdown
    $statusStmt = $db->prepare('SELECT status, COUNT(*) AS count FROM projects WHERE user_id = ? GROUP BY status');
    $statusStmt->bind_param('i', $userId);
    $statusStmt->execute();
    $projectStatuses = $statusStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $statusStmt->close();

    return [
        'categories' => $categories,
        'projectStatuses' => $projectStatuses,
    ];
}

/**
 * Handle file upload for certifications and profile photos
 */
function handleFileUpload(array $file, string $subdir, array $allowedTypes, int $maxSize = 5242880): array
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'path' => null, 'message' => 'No file uploaded.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'path' => null, 'message' => 'Upload failed. Please try again.'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'path' => null, 'message' => 'File is too large. Maximum size is 5MB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowedTypes, true)) {
        return ['success' => false, 'path' => null, 'message' => 'Invalid file type.'];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('lp_', true) . '.' . strtolower($ext);
    $uploadDir = __DIR__ . '/../uploads/' . $subdir;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'path' => null, 'message' => 'Could not save uploaded file.'];
    }

    return ['success' => true, 'path' => 'uploads/' . $subdir . '/' . $filename, 'message' => 'File uploaded successfully.'];
}

/**
 * Delete a file from uploads if it exists
 */
function deleteUploadedFile(?string $relativePath): void
{
    if (!$relativePath) {
        return;
    }

    $fullPath = __DIR__ . '/../' . $relativePath;
    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

/**
 * Get user initials for avatar fallback
 */
function getUserInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper($part[0] ?? '');
    }
    return $initials ?: 'LP';
}
