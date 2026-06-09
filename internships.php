<?php
/**
 * LaunchPad — Internship Tracker
 * Track applications, interviews, and offers
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$userId = getUserId();
$db = getDB();

$statuses = ['Applied', 'Interview', 'Offer', 'Rejected', 'Accepted'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $company = trim($_POST['company'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $status = $_POST['status'] ?? 'Applied';
        $appDate = $_POST['application_date'] ?? '';
        $notes = trim($_POST['notes'] ?? '');

        if ($company && $role && $appDate && in_array($status, $statuses)) {
            $stmt = $db->prepare('INSERT INTO internships (user_id, company, role, status, application_date, notes) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('isssss', $userId, $company, $role, $status, $appDate, $notes);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Internship application tracked.');
        } else {
            setFlash('error', 'Please fill in all required fields.');
        }
        redirect('internships.php');
    }

    if ($action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $company = trim($_POST['company'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $status = $_POST['status'] ?? 'Applied';
        $appDate = $_POST['application_date'] ?? '';
        $notes = trim($_POST['notes'] ?? '');

        if ($id && $company && $role && $appDate && in_array($status, $statuses)) {
            $stmt = $db->prepare('UPDATE internships SET company=?, role=?, status=?, application_date=?, notes=? WHERE id=? AND user_id=?');
            $stmt->bind_param('sssssii', $company, $role, $status, $appDate, $notes, $id, $userId);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Internship updated.');
        } else {
            setFlash('error', 'Could not update internship.');
        }
        redirect('internships.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $db->prepare('DELETE FROM internships WHERE id=? AND user_id=?');
        $stmt->bind_param('ii', $id, $userId);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Internship removed.');
        redirect('internships.php');
    }
}

$stmt = $db->prepare('SELECT * FROM internships WHERE user_id = ? ORDER BY application_date DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$internships = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$currentPage = 'internships';
$pageTitle = 'Internships';
$pageSubtitle = 'Track your application pipeline';
$headerAction = '<button class="btn btn-primary" data-modal-open="internModal">+ Track Application</button>';

ob_start();
?>

<?php if (empty($internships)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">◎</div>
        <h3>No internships tracked</h3>
        <p>Start logging applications to manage your career pipeline.</p>
        <button class="btn btn-primary" data-modal-open="internModal">Track Application</button>
    </div>
<?php else: ?>
    <div class="item-grid">
        <?php foreach ($internships as $intern):
            $statusBadge = match ($intern['status']) {
                'Offer', 'Accepted' => 'badge-emerald',
                'Interview' => 'badge-blue',
                'Rejected' => 'badge-red',
                default => 'badge-amber',
            };
        ?>
            <div class="item-card">
                <div class="item-card-header">
                    <div>
                        <div class="item-card-title"><?= e($intern['company']) ?></div>
                        <div class="item-card-meta"><?= e($intern['role']) ?></div>
                    </div>
                    <span class="badge <?= $statusBadge ?>"><?= e($intern['status']) ?></span>
                </div>

                <div style="font-size: 0.8125rem; color: var(--text-secondary); margin-bottom: 8px;">
                    Applied: <?= formatDate($intern['application_date']) ?>
                </div>

                <?php if ($intern['notes']): ?>
                    <p style="font-size: 0.875rem; color: var(--text-muted);">
                        <?= e(mb_strimwidth($intern['notes'], 0, 100, '...')) ?>
                    </p>
                <?php endif; ?>

                <div class="item-card-actions">
                    <button class="btn btn-ghost btn-sm"
                            onclick='openEditModal("editInternModal", <?= json_encode([
                                "id" => $intern["id"],
                                "company" => $intern["company"],
                                "role" => $intern["role"],
                                "status" => $intern["status"],
                                "application_date" => $intern["application_date"],
                                "notes" => $intern["notes"],
                            ]) ?>)'>
                        Edit
                    </button>
                    <form method="POST" style="display:inline" onsubmit="return confirmDelete('Remove this internship?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $intern['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Add Internship Modal -->
<div class="modal-overlay" id="internModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Track Application</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" name="company" class="form-control" placeholder="e.g. Google" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" name="role" class="form-control" placeholder="e.g. SWE Intern" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?= e($s) ?>"><?= e($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Application Date</label>
                        <input type="date" name="application_date" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" placeholder="Interview dates, contacts, follow-ups..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Track Application</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Internship Modal -->
<div class="modal-overlay" id="editInternModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Application</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" name="company" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" name="role" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?= e($s) ?>"><?= e($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Application Date</label>
                        <input type="date" name="application_date" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
