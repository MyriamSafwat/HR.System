<?php
require_once "auth.php";
require_once "config.php";

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("UPDATE projects SET deleted_at = NOW(), status = 'deleted' WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header("Location: projects.php");
    exit;
}

if (isset($_GET['restore'])) {
    $id = (int) $_GET['restore'];
    $stmt = $conn->prepare("UPDATE projects SET deleted_at = NULL, status = 'active' WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header("Location: projects.php?view=deleted");
    exit;
}

$view = $_GET['view'] ?? 'active';
$editProject = null;

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM projects WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $editProject = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectId = $_POST['project_id'] ?? '';
    $projectName = trim($_POST['project_name'] ?? '');
    $projectCode = trim($_POST['project_code'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($projectId) {
        $stmt = $conn->prepare("
            UPDATE projects
            SET project_name = :project_name,
                project_code = :project_code,
                location = :location,
                status = :status
            WHERE id = :id
        ");

        $stmt->execute([
            ':project_name' => $projectName,
            ':project_code' => $projectCode,
            ':location' => $location,
            ':status' => $status,
            ':id' => $projectId
        ]);

        header("Location: projects.php?updated=1");
        exit;
    } else {
        $stmt = $conn->prepare("
            INSERT INTO projects(project_name, project_code, location, status)
            VALUES(:project_name, :project_code, :location, :status)
        ");

        $stmt->execute([
            ':project_name' => $projectName,
            ':project_code' => $projectCode,
            ':location' => $location,
            ':status' => $status
        ]);

        header("Location: projects.php?added=1");
        exit;
    }
}

if ($view === 'deleted') {
    $projects = $conn->query("SELECT * FROM projects WHERE deleted_at IS NOT NULL ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $projects = $conn->query("SELECT * FROM projects WHERE deleted_at IS NULL ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
}

include "includes/header.php";
include "includes/sidebar.php";
?>

<main class="main">

<div class="page-header">
    <div>
        <h1><?= $lang == 'ar' ? 'المشاريع' : 'Projects' ?></h1>
        <p><?= $lang == 'ar' ? 'إدارة مشاريع الشركة' : 'Manage company projects' ?></p>
    </div>

    <div class="header-actions">
        <a class="reset-btn" href="projects.php">
            <?= $lang == 'ar' ? 'المشاريع الحالية' : 'Active Projects' ?>
        </a>

        <a class="danger-btn" href="projects.php?view=deleted">
            <?= $lang == 'ar' ? 'المشاريع المحذوفة' : 'Deleted Projects' ?>
        </a>
    </div>
</div>

<?php if (isset($_GET['added'])): ?>
    <div class="alert-success"><?= $lang == 'ar' ? 'تم إضافة المشروع' : 'Project added successfully' ?></div>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert-success"><?= $lang == 'ar' ? 'تم تعديل المشروع' : 'Project updated successfully' ?></div>
<?php endif; ?>

<div class="panel">
    <h2>
        <?= $editProject
            ? ($lang == 'ar' ? 'تعديل مشروع' : 'Edit Project')
            : ($lang == 'ar' ? 'إضافة مشروع' : 'Add Project')
        ?>
    </h2>

    <form method="POST" class="form-grid">

        <input type="hidden" name="project_id" value="<?= htmlspecialchars($editProject['id'] ?? '') ?>">

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'اسم المشروع' : 'Project Name' ?></label>
            <input name="project_name" required value="<?= htmlspecialchars($editProject['project_name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'كود المشروع' : 'Project Code' ?></label>
            <input name="project_code" value="<?= htmlspecialchars($editProject['project_code'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'الموقع' : 'Location' ?></label>
            <input name="location" value="<?= htmlspecialchars($editProject['location'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'الحالة' : 'Status' ?></label>
            <?php $currentStatus = $editProject['status'] ?? 'active'; ?>
            <select name="status">
                <option value="active" <?= $currentStatus == 'active' ? 'selected' : '' ?>>Active</option>
                <option value="on_hold" <?= $currentStatus == 'on_hold' ? 'selected' : '' ?>>On Hold</option>
                <option value="completed" <?= $currentStatus == 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
        </div>

        <div class="form-group">
            <label>&nbsp;</label>
            <button class="primary-btn" type="submit">
                <?= $editProject
                    ? ($lang == 'ar' ? 'حفظ التعديل' : 'Save Changes')
                    : ($lang == 'ar' ? 'إضافة المشروع' : 'Add Project')
                ?>
            </button>
        </div>

        <?php if ($editProject): ?>
            <div class="form-group">
                <label>&nbsp;</label>
                <a class="reset-btn" href="projects.php">
                    <?= $lang == 'ar' ? 'إلغاء التعديل' : 'Cancel Edit' ?>
                </a>
            </div>
        <?php endif; ?>

    </form>
</div>

<div class="panel table-panel">
    <div class="table-toolbar">
        <div>
            <strong><?= $lang == 'ar' ? 'قائمة المشاريع' : 'Projects List' ?></strong>
            <span><?= count($projects) ?></span>
        </div>
    </div>

    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= $lang == 'ar' ? 'اسم المشروع' : 'Project Name' ?></th>
                    <th><?= $lang == 'ar' ? 'الكود' : 'Code' ?></th>
                    <th><?= $lang == 'ar' ? 'الموقع' : 'Location' ?></th>
                    <th><?= $lang == 'ar' ? 'الحالة' : 'Status' ?></th>
                    <th><?= $lang == 'ar' ? 'إجراء' : 'Action' ?></th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($projects as $project): ?>
                    <tr>
                        <td><?= $project['id'] ?></td>
                        <td><?= htmlspecialchars($project['project_name']) ?></td>
                        <td><?= htmlspecialchars($project['project_code']) ?></td>
                        <td><?= htmlspecialchars($project['location']) ?></td>
                        <td>
                            <span class="status-pill status-<?= htmlspecialchars($project['status']) ?>">
                                <?= htmlspecialchars($project['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($view === 'deleted'): ?>
                                <a class="table-link" href="projects.php?restore=<?= $project['id'] ?>">
                                    <?= $lang == 'ar' ? 'استرجاع' : 'Restore' ?>
                                </a>
                            <?php else: ?>
                                <a class="table-link" href="projects.php?edit=<?= $project['id'] ?>">
                                    <?= $lang == 'ar' ? 'تعديل' : 'Edit' ?>
                                </a>
                                |
                                <a class="danger-link"
                                   href="projects.php?delete=<?= $project['id'] ?>"
                                   onclick="return confirm('Delete this project?')">
                                    <?= $lang == 'ar' ? 'حذف' : 'Delete' ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>
</div>

</main>

<?php include "includes/footer.php"; ?>