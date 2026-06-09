<?php
/**
 * LaunchPad — Skills Module
 * CRUD operations for student skills
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$userId = getUserId();
$db = getDB();

$categories = ['Programming', 'Framework', 'Database', 'DevOps', 'Design', 'Soft Skills', 'Other'];
$levels = ['Beginner', 'Intermediate', 'Advanced', 'Expert'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $category = $_POST['category'] ?? '';
        $level = $_POST['proficiency_level'] ?? 'Beginner';
        $progress = (int) ($_POST['progress_percent'] ?? 0);
        $progress = max(0, min(100, $progress));

        if ($name && in_array($category, $categories) && in_array($level, $levels)) {
            $stmt = $db->prepare('INSERT INTO skills (user_id, name, category, proficiency_level, progress_percent) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('isssi', $userId, $name, $category, $level, $progress);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Skill added successfully.');
        } else {
            setFlash('error', 'Please fill in all required fields.');
        }
        redirect('skills.php');
    }

    if ($action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $category = $_POST['category'] ?? '';
        $level = $_POST['proficiency_level'] ?? 'Beginner';
        $progress = (int) ($_POST['progress_percent'] ?? 0);
        $progress = max(0, min(100, $progress));

        if ($id && $name && in_array($category, $categories) && in_array($level, $levels)) {
            $stmt = $db->prepare('UPDATE skills SET name=?, category=?, proficiency_level=?, progress_percent=? WHERE id=? AND user_id=?');
            $stmt->bind_param('sssiii', $name, $category, $level, $progress, $id, $userId);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Skill updated successfully.');
        } else {
            setFlash('error', 'Could not update skill.');
        }
        redirect('skills.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $db->prepare('DELETE FROM skills WHERE id=? AND user_id=?');
        $stmt->bind_param('ii', $id, $userId);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Skill deleted.');
        redirect('skills.php');
    }
}

// Fetch all skills for current user
$stmt = $db->prepare('SELECT * FROM skills WHERE user_id = ? ORDER BY updated_at DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$skills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$currentPage = 'skills';
$pageTitle = 'Skills';
$pageSubtitle = 'Track and grow your competencies';
$headerAction = '<button class="btn btn-primary" data-modal-open="skillModal">+ Add Skill</button>';

ob_start();
?>

<?php if (empty($skills)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">◇</div>
        <h3>No skills tracked yet</h3>
        <p>Start building your skill profile to visualize your growth.</p>
        <button class="btn btn-primary" data-modal-open="skillModal">Add Your First Skill</button>
    </div>
<?php else: ?>
    <div class="item-grid">
        <?php foreach ($skills as $skill):
            $badgeClass = match ($skill['proficiency_level']) {
                'Expert' => 'badge-emerald',
                'Advanced' => 'badge-blue',
                'Intermediate' => 'badge-violet',
                default => 'badge-gray',
            };
        ?>
            <div class="item-card">
                <div class="item-card-header">
                    <div>
                        <div class="item-card-title"><?= e($skill['name']) ?></div>
                        <div class="item-card-meta"><?= e($skill['category']) ?></div>
                    </div>
                    <span class="badge <?= $badgeClass ?>"><?= e($skill['proficiency_level']) ?></span>
                </div>

                <div class="skill-progress">
                    <div class="skill-progress-bar">
                        <div class="skill-progress-fill" style="width: <?= (int) $skill['progress_percent'] ?>%"></div>
                    </div>
                    <span class="skill-progress-text"><?= (int) $skill['progress_percent'] ?>%</span>
                </div>

                <div class="item-card-actions">
                    <button class="btn btn-ghost btn-sm"
                            onclick='openEditModal("editSkillModal", <?= json_encode([
                                "id" => $skill["id"],
                                "name" => $skill["name"],
                                "category" => $skill["category"],
                                "proficiency_level" => $skill["proficiency_level"],
                                "progress_percent" => $skill["progress_percent"],
                            ]) ?>)'>
                        Edit
                    </button>
                    <form method="POST" style="display:inline" onsubmit="return confirmDelete('Delete this skill?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $skill['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Add Skill Modal -->
<div class="modal-overlay" id="skillModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add Skill</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label>Skill Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. React, Python, UI Design" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" class="form-control" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Proficiency</label>
                        <select name="proficiency_level" class="form-control">
                            <?php foreach ($levels as $lvl): ?>
                                <option value="<?= e($lvl) ?>"><?= e($lvl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Progress (<span class="progress-preview-text">0</span>%)</label>
                    <input type="range" name="progress_percent" class="form-control" min="0" max="100" value="0"
                           oninput="document.querySelector('#skillModal .progress-preview-text').textContent=this.value; document.querySelector('#skillModal .progress-preview-fill').style.width=this.value+'%'">
                    <div class="skill-progress" style="margin-top: 8px;">
                        <div class="skill-progress-bar">
                            <div class="skill-progress-fill progress-preview-fill" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Add Skill</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Skill Modal -->
<div class="modal-overlay" id="editSkillModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Skill</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label>Skill Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" class="form-control" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Proficiency</label>
                        <select name="proficiency_level" class="form-control">
                            <?php foreach ($levels as $lvl): ?>
                                <option value="<?= e($lvl) ?>"><?= e($lvl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Progress</label>
                    <input type="range" name="progress_percent" class="form-control" min="0" max="100" value="50">
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
