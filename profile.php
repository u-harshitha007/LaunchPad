<?php
/**
 * LaunchPad — Student Profile
 * Personal information and profile photo management
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$userId = getUserId();
$db = getDB();
$user = getCurrentUser();

$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $about = trim($_POST['about'] ?? '');
    $college = trim($_POST['college'] ?? '');
    $degree = trim($_POST['degree'] ?? '');
    $gradYear = ($_POST['graduation_year'] ?? '') !== '' ? (int) $_POST['graduation_year'] : 0;
    $profilePhoto = $user['profile_photo'];

    if (empty($fullName)) {
        setFlash('error', 'Full name is required.');
        redirect('profile.php');
    }

    // Handle profile photo upload
    if (!empty($_FILES['profile_photo']['name'])) {
        $upload = handleFileUpload($_FILES['profile_photo'], 'profiles', $allowedMimes, 2097152);
        if ($upload['success']) {
            // Delete old photo if exists
            if ($profilePhoto) {
                deleteUploadedFile($profilePhoto);
            }
            $profilePhoto = $upload['path'];
        } else {
            setFlash('error', $upload['message']);
            redirect('profile.php');
        }
    }

    // Update profile (graduation_year set to NULL when empty)
    if ($gradYear > 0) {
        $stmt = $db->prepare('UPDATE users SET full_name=?, about=?, college=?, degree=?, graduation_year=?, profile_photo=? WHERE id=?');
        $stmt->bind_param('ssssisi', $fullName, $about, $college, $degree, $gradYear, $profilePhoto, $userId);
    } else {
        $stmt = $db->prepare('UPDATE users SET full_name=?, about=?, college=?, degree=?, graduation_year=NULL, profile_photo=? WHERE id=?');
        $stmt->bind_param('sssssi', $fullName, $about, $college, $degree, $profilePhoto, $userId);
    }
    $stmt->execute();
    $stmt->close();

    setFlash('success', 'Profile updated successfully.');
    redirect('profile.php');
}

$user = getCurrentUser();
$initials = getUserInitials($user['full_name']);

$currentPage = 'profile';
$pageTitle = 'Profile';
$pageSubtitle = 'Manage your personal information';

ob_start();
?>

<div class="dashboard-grid" style="grid-template-columns: 1fr;">
    <div class="card">
        <div class="profile-header">
            <div class="profile-photo">
                <?php if (!empty($user['profile_photo'])): ?>
                    <img src="<?= e($user['profile_photo']) ?>" alt="Profile photo">
                <?php else: ?>
                    <?= e($initials) ?>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h2><?= e($user['full_name']) ?></h2>
                <p><?= e($user['email']) ?></p>
                <?php if ($user['college']): ?>
                    <p style="margin-top: 4px;"><?= e($user['college']) ?><?= $user['degree'] ? ' · ' . e($user['degree']) : '' ?></p>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control"
                           value="<?= e($user['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                    <p class="form-hint">Email cannot be changed</p>
                </div>
            </div>

            <div class="form-group">
                <label>About</label>
                <textarea name="about" class="form-control" rows="4"
                          placeholder="Tell us about yourself, your goals, and interests..."><?= e($user['about'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>College / University</label>
                    <input type="text" name="college" class="form-control"
                           value="<?= e($user['college'] ?? '') ?>" placeholder="e.g. MIT">
                </div>
                <div class="form-group">
                    <label>Degree / Program</label>
                    <input type="text" name="degree" class="form-control"
                           value="<?= e($user['degree'] ?? '') ?>" placeholder="e.g. B.Tech CSE">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Graduation Year</label>
                    <input type="number" name="graduation_year" class="form-control"
                           value="<?= e($user['graduation_year'] ?? '') ?>"
                           min="2020" max="2035" placeholder="2026">
                </div>
                <div class="form-group">
                    <label>Profile Photo</label>
                    <input type="file" name="profile_photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    <p class="form-hint">JPG, PNG, or WebP. Max 2MB.</p>
                </div>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary">Save Profile</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
