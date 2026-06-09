<?php
/**
 * LaunchPad — Projects Module
 * CRUD operations for portfolio projects
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$userId = getUserId();
$db = getDB();

$statuses = ['Planning', 'In Progress', 'Completed', 'On Hold'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $technologies = trim($_POST['technologies'] ?? '');
        $github = trim($_POST['github_link'] ?? '');
        $status = $_POST['status'] ?? 'Planning';

        if ($title && in_array($status, $statuses)) {
            $stmt = $db->prepare('INSERT INTO projects (user_id, title, description, technologies, github_link, status) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('isssss', $userId, $title, $description, $technologies, $github, $status);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Project added successfully.');
        } else {
            setFlash('error', 'Project title is required.');
        }
        redirect('projects.php');
    }

    if ($action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $technologies = trim($_POST['technologies'] ?? '');
        $github = trim($_POST['github_link'] ?? '');
        $status = $_POST['status'] ?? 'Planning';

        if ($id && $title && in_array($status, $statuses)) {
            $stmt = $db->prepare('UPDATE projects SET title=?, description=?, technologies=?, github_link=?, status=? WHERE id=? AND user_id=?');
            $stmt->bind_param('sssssii', $title, $description, $technologies, $github, $status, $id, $userId);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Project updated successfully.');
        } else {
            setFlash('error', 'Could not update project.');
        }
        redirect('projects.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $db->prepare('DELETE FROM projects WHERE id=? AND user_id=?');
        $stmt->bind_param('ii', $id, $userId);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Project deleted.');
        redirect('projects.php');
    }
}

$stmt = $db->prepare('SELECT * FROM projects WHERE user_id = ? ORDER BY updated_at DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$currentPage = 'projects';
$pageTitle = 'Projects';
$pageSubtitle = 'Build and showcase your portfolio';
$headerAction = '<button class="btn btn-primary" data-modal-open="projectModal">+ Add Project</button>';

ob_start();
?>

<?php if (empty($projects)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">▣</div>
        <h3>No projects yet</h3>
        <p>Document your builds and track progress from idea to launch.</p>
        <button class="btn btn-primary" data-modal-open="projectModal">Add Your First Project</button>
    </div>
<?php else: ?>
    <div class="item-grid">
        <?php foreach ($projects as $project):
            $statusBadge = match ($project['status']) {
                'Completed' => 'badge-emerald',
                'In Progress' => 'badge-blue',
                'On Hold' => 'badge-amber',
                default => 'badge-gray',
            };
        ?>
            <div class="item-card">
                <div class="item-card-header">
                    <div>
                        <div class="item-card-title"><?= e($project['title']) ?></div>
                        <?php if ($project['technologies']): ?>
                            <div class="item-card-meta"><?= e($project['technologies']) ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="badge <?= $statusBadge ?>"><?= e($project['status']) ?></span>
                </div>

                <?php if ($project['description']): ?>
                    <p style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 12px;">
                        <?= e(mb_strimwidth($project['description'], 0, 120, '...')) ?>
                    </p>
                <?php endif; ?>

                <?php if ($project['github_link']): ?>
                    <a href="<?= e($project['github_link']) ?>" target="_blank" rel="noopener"
                       style="font-size: 0.8125rem;">View on GitHub →</a>
                <?php endif; ?>

                <div class="item-card-actions">
                    <button class="btn btn-ghost btn-sm"
                            onclick='openEditModal("editProjectModal", <?= json_encode([
                                "id" => $project["id"],
                                "title" => $project["title"],
                                "description" => $project["description"],
                                "technologies" => $project["technologies"],
                                "github_link" => $project["github_link"],
                                "status" => $project["status"],
                            ]) ?>)'>
                        Edit
                    </button>
                    <form method="POST" style="display:inline" onsubmit="return confirmDelete('Delete this project?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $project['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Add Project Modal -->
<div class="modal-overlay" id="projectModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add Project</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label>Project Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. TaskFlow App" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" placeholder="What does this project do?"></textarea>
                </div>
                <div class="form-group">
                    <label>Technologies Used</label>
                    <input type="text" name="technologies" class="form-control" placeholder="React, Node.js, MongoDB">
                </div>
                <div class="form-group">
                    <label>GitHub Link</label>
                    <input type="url" name="github_link" class="form-control" placeholder="https://github.com/you/project">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= e($s) ?>"><?= e($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Add Project</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Project Modal -->
<div class="modal-overlay" id="editProjectModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Project</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label>Project Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>Technologies Used</label>
                    <input type="text" name="technologies" class="form-control">
                </div>
                <div class="form-group">
                    <label>GitHub Link</label>
                    <input type="url" name="github_link" class="form-control">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= e($s) ?>"><?= e($s) ?></option>
                        <?php endforeach; ?>
                    </select>
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
