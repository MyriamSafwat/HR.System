<?php
$role = $_SESSION['user_role'] ?? '';
?>

<aside class="sidebar">

    <h2>AL WATAD HRMS</h2>

    <a href="dashboard.php">
        <?= $trans['dashboard'] ?>
    </a>

    <?php if (in_array($role, ['admin', 'hr', 'manager'])): ?>
        <a href="employees.php">
            <?= $trans['employees'] ?>
        </a>
    <?php endif; ?>

    <?php if (in_array($role, ['admin', 'hr' , 'manager'])): ?>
        <a href="documents.php">
            <?= $trans['documents'] ?>
        </a>
    <?php endif; ?>

    <?php if (in_array($role, ['admin', 'hr' , 'manager'])): ?>
        <a href="attendance.php">
            <?= $trans['attendance'] ?>
        </a>
    <?php endif; ?>

    <?php if (in_array($role, ['admin', 'accountant' , 'manager'])): ?>
        <a href="payroll.php">
            <?= $trans['payroll'] ?>
        </a>
    <?php endif; ?>

    <?php if (in_array($role, ['admin', 'hr' , 'manager'])): ?>
        <a href="leaves.php">
            <?= $trans['leaves'] ?>
        </a>
    <?php endif; ?>

    <?php if (in_array($role, ['admin', 'manager' , 'manager'])): ?>
        <a href="projects.php">
            <?= $trans['projects'] ?>
        </a>
    <?php endif; ?>

    <?php if ($role === 'admin' ): ?>
        <a href="users.php">
            <?= $lang == 'ar' ? 'المستخدمين' : 'Users' ?>
        </a>

        <a href="settings.php">
            <?= $trans['settings'] ?>
        </a>
    <?php endif; ?>

    <a href="logout.php">
        <?= $lang == 'ar' ? 'تسجيل الخروج' : 'Logout' ?>
    </a>

</aside>