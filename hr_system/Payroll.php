<?php
require_once "auth.php";
require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $basic = (float)($_POST['basic_salary'] ?? 0);
    $allowances = (float)($_POST['allowances'] ?? 0);
    $deductions = (float)($_POST['deductions'] ?? 0);
    $advances = (float)($_POST['advances'] ?? 0);

    $net = $basic + $allowances - $deductions - $advances;

    $stmt = $conn->prepare("
        INSERT INTO payroll
        (employee_id, payroll_month, basic_salary, allowances, deductions, advances, net_salary, status, notes)
        VALUES
        (:employee_id, :payroll_month, :basic_salary, :allowances, :deductions, :advances, :net_salary, :status, :notes)
    ");

    $stmt->execute([
        ':employee_id' => $_POST['employee_id'],
        ':payroll_month' => $_POST['payroll_month'],
        ':basic_salary' => $basic,
        ':allowances' => $allowances,
        ':deductions' => $deductions,
        ':advances' => $advances,
        ':net_salary' => $net,
        ':status' => $_POST['status'],
        ':notes' => $_POST['notes'] ?? ''
    ]);

    header("Location: payroll.php?added=1");
    exit;
}

$employees = $conn->query("
    SELECT id, emp_id, name_ar, name_en, gross_salary
    FROM employees
    WHERE deleted_at IS NULL
    ORDER BY name_en ASC
")->fetchAll(PDO::FETCH_ASSOC);

$payrolls = $conn->query("
    SELECT payroll.*, employees.emp_id, employees.name_ar, employees.name_en
    FROM payroll
    LEFT JOIN employees ON payroll.employee_id = employees.id
    ORDER BY payroll.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

include "includes/header.php";
include "includes/sidebar.php";
?>

<main class="main">

<div class="page-header">
    <div>
        <h1><?= $lang == 'ar' ? 'الرواتب' : 'Payroll' ?></h1>
        <p><?= $lang == 'ar' ? 'إدارة رواتب الموظفين' : 'Manage employee payroll' ?></p>
    </div>
</div>

<?php if (isset($_GET['added'])): ?>
    <div class="alert-success">
        <?= $lang == 'ar' ? 'تم إضافة الراتب بنجاح' : 'Payroll added successfully' ?>
    </div>
<?php endif; ?>

<section class="panel">
    <h2><?= $lang == 'ar' ? 'إضافة راتب' : 'Add Payroll' ?></h2>

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
            <label><?= $lang == 'ar' ? 'الشهر' : 'Month' ?></label>
            <input type="month" name="payroll_month" required>
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'الراتب الأساسي' : 'Basic Salary' ?></label>
            <input type="number" step="0.01" name="basic_salary" required>
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'البدلات' : 'Allowances' ?></label>
            <input type="number" step="0.01" name="allowances" value="0">
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'الخصومات' : 'Deductions' ?></label>
            <input type="number" step="0.01" name="deductions" value="0">
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'السلف' : 'Advances' ?></label>
            <input type="number" step="0.01" name="advances" value="0">
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'الحالة' : 'Status' ?></label>
            <select name="status">
                <option value="draft">Draft</option>
                <option value="approved">Approved</option>
                <option value="paid">Paid</option>
            </select>
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'ملاحظات' : 'Notes' ?></label>
            <input name="notes">
        </div>

        <div class="form-group">
            <label>&nbsp;</label>
            <button class="primary-btn" type="submit">
                <?= $lang == 'ar' ? 'حفظ الراتب' : 'Save Payroll' ?>
            </button>
        </div>

    </form>
</section>

<section class="panel table-panel">
    <div class="table-toolbar">
        <div>
            <strong><?= $lang == 'ar' ? 'سجل الرواتب' : 'Payroll Records' ?></strong>
            <span><?= count($payrolls) ?></span>
        </div>
    </div>

    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= $lang == 'ar' ? 'الموظف' : 'Employee' ?></th>
                    <th><?= $lang == 'ar' ? 'الشهر' : 'Month' ?></th>
                    <th><?= $lang == 'ar' ? 'أساسي' : 'Basic' ?></th>
                    <th><?= $lang == 'ar' ? 'بدلات' : 'Allowances' ?></th>
                    <th><?= $lang == 'ar' ? 'خصومات' : 'Deductions' ?></th>
                    <th><?= $lang == 'ar' ? 'سلف' : 'Advances' ?></th>
                    <th><?= $lang == 'ar' ? 'الصافي' : 'Net' ?></th>
                    <th><?= $lang == 'ar' ? 'الحالة' : 'Status' ?></th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($payrolls as $row): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td>
                            <?= htmlspecialchars($row['emp_id'] . ' - ' . ($lang == 'ar' ? ($row['name_ar'] ?: $row['name_en']) : ($row['name_en'] ?: $row['name_ar']))) ?>
                        </td>
                        <td><?= htmlspecialchars($row['payroll_month']) ?></td>
                        <td><?= number_format($row['basic_salary'], 2) ?></td>
                        <td><?= number_format($row['allowances'], 2) ?></td>
                        <td><?= number_format($row['deductions'], 2) ?></td>
                        <td><?= number_format($row['advances'], 2) ?></td>
                        <td><strong><?= number_format($row['net_salary'], 2) ?></strong></td>
                        <td>
                            <span class="status-pill status-<?= htmlspecialchars($row['status']) ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>
</section>

</main>

<?php include "includes/footer.php"; ?>