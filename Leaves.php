<?php
require_once "auth.php";
require_once "config.php";

if (isset($_GET['approve'])) {
    $id = (int) $_GET['approve'];
    $conn->prepare("UPDATE leave_requests SET status='approved' WHERE id=:id")
         ->execute([':id' => $id]);
    header("Location: leaves.php");
    exit;
}

if (isset($_GET['reject'])) {
    $id = (int) $_GET['reject'];
    $conn->prepare("UPDATE leave_requests SET status='rejected' WHERE id=:id")
         ->execute([':id' => $id]);
    header("Location: leaves.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from = new DateTime($_POST['from_date']);
    $to = new DateTime($_POST['to_date']);
    $days = $from->diff($to)->days + 1;

    $stmt = $conn->prepare("
        INSERT INTO leave_requests
        (employee_id, leave_type, from_date, to_date, days_count, reason)
        VALUES
        (:employee_id, :leave_type, :from_date, :to_date, :days_count, :reason)
    ");

    $stmt->execute([
        ':employee_id' => $_POST['employee_id'],
        ':leave_type' => $_POST['leave_type'],
        ':from_date' => $_POST['from_date'],
        ':to_date' => $_POST['to_date'],
        ':days_count' => $days,
        ':reason' => $_POST['reason']
    ]);

    header("Location: leaves.php?added=1");
    exit;
}

$employees = $conn->query("
    SELECT id, emp_id, name_ar, name_en
    FROM employees
    WHERE deleted_at IS NULL
    ORDER BY name_en ASC
")->fetchAll(PDO::FETCH_ASSOC);

$leaves = $conn->query("
    SELECT leave_requests.*, employees.emp_id, employees.name_ar, employees.name_en
    FROM leave_requests
    LEFT JOIN employees ON leave_requests.employee_id = employees.id
    ORDER BY leave_requests.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

include "includes/header.php";
include "includes/sidebar.php";
?>

<main class="main">

<div class="page-header">
    <div>
        <h1><?= $lang == 'ar' ? 'الإجازات' : 'Leaves' ?></h1>
        <p><?= $lang == 'ar' ? 'إدارة طلبات الإجازات' : 'Manage leave requests' ?></p>
    </div>
</div>

<?php if (isset($_GET['added'])): ?>
    <div class="alert-success">
        <?= $lang == 'ar' ? 'تم إضافة طلب الإجازة' : 'Leave request added' ?>
    </div>
<?php endif; ?>

<section class="panel">
    <h2><?= $lang == 'ar' ? 'طلب إجازة جديد' : 'New Leave Request' ?></h2>

    <form method="POST" class="form-grid">

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'الموظف' : 'Employee' ?></label>
            <select name="employee_id" required>
                <option value=""><?= $lang == 'ar' ? 'اختر الموظف' : 'Select Employee' ?></option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>">
                        <?= htmlspecialchars($emp['emp_id'] . ' - ' . ($lang == 'ar' ? ($emp['name_ar'] ?: $emp['name_en']) : ($emp['name_en'] ?: $emp['name_ar']))) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'نوع الإجازة' : 'Leave Type' ?></label>
            <select name="leave_type">
                <option value="annual"><?= $lang == 'ar' ? 'سنوية' : 'Annual' ?></option>
                <option value="sick"><?= $lang == 'ar' ? 'مرضية' : 'Sick' ?></option>
                <option value="emergency"><?= $lang == 'ar' ? 'طارئة' : 'Emergency' ?></option>
                <option value="unpaid"><?= $lang == 'ar' ? 'بدون راتب' : 'Unpaid' ?></option>
            </select>
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'من تاريخ' : 'From Date' ?></label>
            <input type="date" name="from_date" required>
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'إلى تاريخ' : 'To Date' ?></label>
            <input type="date" name="to_date" required>
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'السبب' : 'Reason' ?></label>
            <input name="reason">
        </div>

        <div class="form-group">
            <label>&nbsp;</label>
            <button class="primary-btn" type="submit">
                <?= $lang == 'ar' ? 'إضافة الطلب' : 'Add Request' ?>
            </button>
        </div>

    </form>
</section>

<section class="panel table-panel">
    <div class="table-toolbar">
        <div>
            <strong><?= $lang == 'ar' ? 'طلبات الإجازات' : 'Leave Requests' ?></strong>
            <span><?= count($leaves) ?></span>
        </div>
    </div>

    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= $lang == 'ar' ? 'الموظف' : 'Employee' ?></th>
                    <th><?= $lang == 'ar' ? 'النوع' : 'Type' ?></th>
                    <th><?= $lang == 'ar' ? 'من' : 'From' ?></th>
                    <th><?= $lang == 'ar' ? 'إلى' : 'To' ?></th>
                    <th><?= $lang == 'ar' ? 'الأيام' : 'Days' ?></th>
                    <th><?= $lang == 'ar' ? 'الحالة' : 'Status' ?></th>
                    <th><?= $lang == 'ar' ? 'إجراء' : 'Action' ?></th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($leaves as $leave): ?>
                    <tr>
                        <td><?= $leave['id'] ?></td>
                        <td>
                            <?= htmlspecialchars($leave['emp_id'] . ' - ' . ($lang == 'ar' ? ($leave['name_ar'] ?: $leave['name_en']) : ($leave['name_en'] ?: $leave['name_ar']))) ?>
                        </td>
                        <td><?= htmlspecialchars($leave['leave_type']) ?></td>
                        <td><?= htmlspecialchars($leave['from_date']) ?></td>
                        <td><?= htmlspecialchars($leave['to_date']) ?></td>
                        <td><?= htmlspecialchars($leave['days_count']) ?></td>
                        <td>
                            <span class="status-pill status-<?= htmlspecialchars($leave['status']) ?>">
                                <?= htmlspecialchars($leave['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($leave['status'] == 'pending'): ?>
                                <a class="table-link" href="leaves.php?approve=<?= $leave['id'] ?>">
                                    <?= $lang == 'ar' ? 'قبول' : 'Approve' ?>
                                </a>
                                |
                                <a class="danger-link" href="leaves.php?reject=<?= $leave['id'] ?>">
                                    <?= $lang == 'ar' ? 'رفض' : 'Reject' ?>
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>
</section>

</main>

<?php include "includes/footer.php"; ?>