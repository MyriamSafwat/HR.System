<?php
require_once "auth.php";
require_once "config.php";

if ($_SESSION['user_role'] !== 'admin') {
    die("Access Denied");
}

$error = '';
$editUser = null;

/* Delete */
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];

    if ($deleteId !== (int) $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $deleteId]);
    }

    header("Location: users.php");
    exit;
}

/* Get edit user */
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $editId]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* Add / Update */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $userId = $_POST['user_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? 'hr';

    if ($userId) {
        $check = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
        $check->execute([
            ':email' => $email,
            ':id' => $userId
        ]);

        if ($check->fetch()) {
            $error = $lang == 'ar' ? 'البريد الإلكتروني موجود بالفعل' : 'Email already exists';
        } else {
            if ($password !== '') {
                $stmt = $conn->prepare("
                    UPDATE users
                    SET name = :name, email = :email, password = :password, role = :role
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $password,
                    ':role' => $role,
                    ':id' => $userId
                ]);
            } else {
                $stmt = $conn->prepare("
                    UPDATE users
                    SET name = :name, email = :email, role = :role
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':role' => $role,
                    ':id' => $userId
                ]);
            }

            header("Location: users.php?updated=1");
            exit;
        }

    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $check->execute([':email' => $email]);

        if ($check->fetch()) {
            $error = $lang == 'ar' ? 'البريد الإلكتروني موجود بالفعل' : 'Email already exists';
        } else {
            $stmt = $conn->prepare("
                INSERT INTO users(name, email, password, role)
                VALUES(:name, :email, :password, :role)
            ");

            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => $password,
                ':role' => $role
            ]);

            header("Location: users.php?added=1");
            exit;
        }
    }
}

$users = $conn->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

include "includes/header.php";
include "includes/sidebar.php";
?>

<main class="main">

    <div class="page-header">
        <div>
            <h1><?= $lang == 'ar' ? 'إدارة المستخدمين' : 'Users Management' ?></h1>
            <p><?= $lang == 'ar' ? 'إضافة وتعديل وحذف مستخدمي النظام' : 'Add, edit and remove system users' ?></p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['added'])): ?>
        <div class="alert-success"><?= $lang == 'ar' ? 'تم إضافة المستخدم بنجاح' : 'User added successfully' ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert-success"><?= $lang == 'ar' ? 'تم تعديل المستخدم بنجاح' : 'User updated successfully' ?></div>
    <?php endif; ?>

    <div class="panel">
        <h2>
            <?= $editUser
                ? ($lang == 'ar' ? 'تعديل مستخدم' : 'Edit User')
                : ($lang == 'ar' ? 'إضافة مستخدم' : 'Add User')
            ?>
        </h2>

        <form method="POST" class="form-grid">

            <input type="hidden" name="user_id" value="<?= htmlspecialchars($editUser['id'] ?? '') ?>">

            <div class="form-group">
                <label><?= $lang == 'ar' ? 'الاسم' : 'Name' ?></label>
                <input name="name" required value="<?= htmlspecialchars($editUser['name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label><?= $lang == 'ar' ? 'البريد الإلكتروني' : 'Email' ?></label>
                <input type="email" name="email" required value="<?= htmlspecialchars($editUser['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>
                    <?= $lang == 'ar' ? 'كلمة المرور' : 'Password' ?>
                    <?= $editUser ? ($lang == 'ar' ? '(اتركها فارغة لو مش هتغيرها)' : '(Leave empty to keep current)') : '' ?>
                </label>
                <input type="text" name="password" <?= $editUser ? '' : 'required' ?>>
            </div>

            <div class="form-group">
                <label><?= $lang == 'ar' ? 'الصلاحية' : 'Role' ?></label>
                <select name="role">
                    <?php
                    $currentRole = $editUser['role'] ?? 'hr';
                    $roles = ['admin', 'hr', 'accountant', 'manager'];
                    foreach ($roles as $roleItem):
                    ?>
                        <option value="<?= $roleItem ?>" <?= $currentRole == $roleItem ? 'selected' : '' ?>>
                            <?= ucfirst($roleItem) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <button class="primary-btn" type="submit">
                    <?= $editUser
                        ? ($lang == 'ar' ? 'حفظ التعديل' : 'Save Changes')
                        : ($lang == 'ar' ? 'إضافة' : 'Add User')
                    ?>
                </button>
            </div>

            <?php if ($editUser): ?>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <a class="reset-btn" href="users.php">
                        <?= $lang == 'ar' ? 'إلغاء التعديل' : 'Cancel Edit' ?>
                    </a>
                </div>
            <?php endif; ?>

        </form>
    </div>

    <div class="panel table-panel">
        <h2><?= $lang == 'ar' ? 'قائمة المستخدمين' : 'Users List' ?></h2>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= $lang == 'ar' ? 'الاسم' : 'Name' ?></th>
                    <th><?= $lang == 'ar' ? 'البريد الإلكتروني' : 'Email' ?></th>
                    <th><?= $lang == 'ar' ? 'الصلاحية' : 'Role' ?></th>
                    <th><?= $lang == 'ar' ? 'إجراء' : 'Action' ?></th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td>
                            <a class="table-link" href="users.php?edit=<?= $user['id'] ?>">
                                <?= $lang == 'ar' ? 'تعديل' : 'Edit' ?>
                            </a>

                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                |
                                <a class="danger-link"
                                   href="users.php?delete=<?= $user['id'] ?>"
                                   onclick="return confirm('Delete this user?')">
                                    <?= $lang == 'ar' ? 'حذف' : 'Delete' ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>

</main>

<?php include "includes/footer.php"; ?>