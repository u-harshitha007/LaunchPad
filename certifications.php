<?php
/**
 * LaunchPad — Certifications Module
 * CRUD with PDF/image upload support
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$userId = getUserId();
$db = getDB();

$allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $organization = trim($_POST['organization'] ?? '');
        $issueDate = $_POST['issue_date'] ?? '';
        $filePath = null;

        if ($name && $organization && $issueDate) {
            // Handle optional file upload
            if (!empty($_FILES['certificate_file']['name'])) {
                $upload = handleFileUpload($_FILES['certificate_file'], 'certifications', $allowedMimes);
                if ($upload['success']) {
                    $filePath = $upload['path'];
                } else {
                    setFlash('error', $upload['message']);
                    redirect('certifications.php');
                }
            }

            $stmt = $db->prepare('INSERT INTO certifications (user_id, name, organization, issue_date, file_path) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('issss', $userId, $name, $organization, $issueDate, $filePath);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Certification added successfully.');
        } else {
            setFlash('error', 'Please fill in all required fields.');
        }
        redirect('certifications.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        // Get file path before deleting record
        $getStmt = $db->prepare('SELECT file_path FROM certifications WHERE id=? AND user_id=?');
        $getStmt->bind_param('ii', $id, $userId);
        $getStmt->execute();
        $row = $getStmt->get_result()->fetch_assoc();
        $getStmt->close();

        $stmt = $db->prepare('DELETE FROM certifications WHERE id=? AND user_id=?');
        $stmt->bind_param('ii', $id, $userId);
        $stmt->execute();
        $stmt->close();

        if ($row && $row['file_path']) {
            deleteUploadedFile($row['file_path']);
        }

        setFlash('success', 'Certification deleted.');
        redirect('certifications.php');
    }
}

$stmt = $db->prepare('SELECT * FROM certifications WHERE user_id = ? ORDER BY issue_date DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$certifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$currentPage = 'certifications';
$pageTitle = 'Certifications';
$pageSubtitle = 'Manage your credentials and proofs';
$headerAction = '<button class="btn btn-primary" data-modal-open="certModal">+ Add Certification</button>';

ob_start();
?>

<?php if (empty($certifications)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">◆</div>
        <h3>No certifications yet</h3>
        <p>Add your credentials and upload proof documents.</p>
        <button class="btn btn-primary" data-modal-open="certModal">Add Certification</button>
    </div>
<?php else: ?>
    <div class="card" style="padding: 0; overflow: hidden;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Certification</th>
                    <th>Organization</th>
                    <th>Issue Date</th>
                    <th>Document</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($certifications as $cert): ?>
                    <tr>
                        <td><strong><?= e($cert['name']) ?></strong></td>
                        <td><?= e($cert['organization']) ?></td>
                        <td><?= formatDate($cert['issue_date']) ?></td>
                        <td>
                            <?php if ($cert['file_path']): ?>
                                <a href="<?= e($cert['file_path']) ?>" target="_blank" rel="noopener">View File</a>
                            <?php else: ?>
                                <span style="color: var(--text-muted);">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline" onsubmit="return confirmDelete('Delete this certification?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $cert['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Add Certification Modal -->
<div class="modal-overlay" id="certModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add Certification</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label>Certification Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. AWS Cloud Practitioner" required>
                </div>
                <div class="form-group">
                    <label>Organization</label>
                    <input type="text" name="organization" class="form-control" placeholder="e.g. Amazon Web Services" required>
                </div>
                <div class="form-group">
                    <label>Issue Date</label>
                    <input type="date" name="issue_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Upload Certificate (PDF or Image)</label>
                    <input type="file" name="certificate_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    <p class="form-hint">Optional. Max 5MB. Accepted: PDF, JPG, PNG, WebP</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Add Certification</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
