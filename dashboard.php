<?php
require_once "auth.php";
require_once "config.php";

function countExpiringDocs($conn, $field, $type) {
    if ($type === 'expired') {
        $sql = "
            SELECT COUNT(*)
            FROM employees
            WHERE deleted_at IS NULL
            AND $field IS NOT NULL
            AND $field != ''
            AND STR_TO_DATE($field, '%d-%m-%Y') < CURDATE()
        ";
    } elseif ($type === 'soon') {
        $sql = "
            SELECT COUNT(*)
            FROM employees
            WHERE deleted_at IS NULL
            AND $field IS NOT NULL
            AND $field != ''
            AND STR_TO_DATE($field, '%d-%m-%Y') BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ";
    } else {
        $sql = "
            SELECT COUNT(*)
            FROM employees
            WHERE deleted_at IS NULL
            AND $field IS NOT NULL
            AND $field != ''
            AND STR_TO_DATE($field, '%d-%m-%Y') > DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ";
    }

    return $conn->query($sql)->fetchColumn();
}

$totalEmployees = $conn->query("
    SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL
")->fetchColumn();

$deletedEmployees = $conn->query("
    SELECT COUNT(*) FROM employees WHERE deleted_at IS NOT NULL
")->fetchColumn();

$activeEmployees = $conn->query("
    SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL AND employee_status = 'active'
")->fetchColumn();

$resignedEmployees = $conn->query("
    SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL AND employee_status = 'resigned'
")->fetchColumn();

$terminatedEmployees = $conn->query("
    SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL AND employee_status = 'terminated'
")->fetchColumn();

$onLeaveEmployees = $conn->query("
    SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL AND employee_status = 'on_leave'
")->fetchColumn();

$totalSalary = $conn->query("
    SELECT SUM(CAST(gross_salary AS DECIMAL(10,2)))
    FROM employees
    WHERE deleted_at IS NULL
")->fetchColumn();

$totalCompanies = $conn->query("
    SELECT COUNT(DISTINCT company_name)
    FROM employees
    WHERE deleted_at IS NULL
    AND company_name IS NOT NULL
    AND company_name != ''
")->fetchColumn();

$expiredDocs =
    countExpiringDocs($conn, 'passport_expiry', 'expired') +
    countExpiringDocs($conn, 'visa_expiry', 'expired') +
    countExpiringDocs($conn, 'emirates_id_expiry', 'expired') +
    countExpiringDocs($conn, 'labour_card_expiry', 'expired');

$expiringSoonDocs =
    countExpiringDocs($conn, 'passport_expiry', 'soon') +
    countExpiringDocs($conn, 'visa_expiry', 'soon') +
    countExpiringDocs($conn, 'emirates_id_expiry', 'soon') +
    countExpiringDocs($conn, 'labour_card_expiry', 'soon');

$validDocs =
    countExpiringDocs($conn, 'passport_expiry', 'valid') +
    countExpiringDocs($conn, 'visa_expiry', 'valid') +
    countExpiringDocs($conn, 'emirates_id_expiry', 'valid') +
    countExpiringDocs($conn, 'labour_card_expiry', 'valid');

$companies = $conn->query("
    SELECT company_name, COUNT(*) AS total
    FROM employees
    WHERE deleted_at IS NULL
    AND company_name IS NOT NULL
    AND company_name != ''
    GROUP BY company_name
    ORDER BY total DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$latestEmployees = $conn->query("
    SELECT emp_id, name_ar, name_en, company_name, job_title_ar, job_title_en, employee_status
    FROM employees
    WHERE deleted_at IS NULL
    ORDER BY id DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include "includes/header.php"; ?>
<?php include "includes/sidebar.php"; ?>

<main class="main">

    <h1><?= $trans['dashboard'] ?></h1>

    <div class="cards">

        <div class="card border">
            <span><?= $trans['total_employees'] ?></span>
            <strong><?= $totalEmployees ?></strong>
        </div>

        <div class="card border">
            <span><?= $lang == 'ar' ? 'الموظفين النشطين' : 'Active Employees' ?></span>
            <strong><?= $activeEmployees ?></strong>
        </div>

        <div class="card warning">
            <span><?= $lang == 'ar' ? 'في إجازة' : 'On Leave' ?></span>
            <strong><?= $onLeaveEmployees ?></strong>
        </div>

        <div class="card danger">
            <span><?= $lang == 'ar' ? 'مستقيلين' : 'Resigned' ?></span>
            <strong><?= $resignedEmployees ?></strong>
        </div>

        <div class="card danger">
            <span><?= $lang == 'ar' ? 'منتهية خدمتهم' : 'Terminated' ?></span>
            <strong><?= $terminatedEmployees ?></strong>
        </div>

        <div class="card danger">
            <span><?= $lang == 'ar' ? 'محذوفين' : 'Deleted Employees' ?></span>
            <strong><?= $deletedEmployees ?></strong>
        </div>

        <div class="card border">
            <span><?= $trans['total_companies'] ?></span>
            <strong><?= $totalCompanies ?></strong>
        </div>

        <div class="card border">
            <span><?= $trans['total_salaries'] ?></span>
            <strong><?= number_format($totalSalary ?? 0, 2) ?></strong>
        </div>

        <div class="card danger">
            <span><?= $lang == 'ar' ? 'مستندات منتهية' : 'Expired Documents' ?></span>
            <strong><?= $expiredDocs ?></strong>
        </div>

        <div class="card warning">
            <span><?= $lang == 'ar' ? 'مستندات تنتهي خلال 30 يوم' : 'Expiring Within 30 Days' ?></span>
            <strong><?= $expiringSoonDocs ?></strong>
        </div>

        <div class="card border">
            <span><?= $lang == 'ar' ? 'مستندات سارية' : 'Valid Documents' ?></span>
            <strong><?= $validDocs ?></strong>
        </div>

    </div>

    <div class="dashboard-grid">

        <section class="panel">
            <h2><?= $trans['top_companies'] ?></h2>

            <?php foreach ($companies as $company): ?>
                <div class="list-row">
                    <span><?= htmlspecialchars($company['company_name']) ?></span>
                    <strong><?= $company['total'] ?></strong>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="panel">
            <h2><?= $trans['latest_employees'] ?></h2>

            <table class="mini-table">
                <thead>
                    <tr>
                        <th><?= $trans['emp_id'] ?></th>
                        <th><?= $trans['name'] ?></th>
                        <th><?= $trans['company'] ?></th>
                        <th><?= $trans['job'] ?></th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($latestEmployees as $emp): ?>
                        <tr>
                            <td><?= htmlspecialchars($emp['emp_id']) ?></td>

                            <td>
                                <?= htmlspecialchars(
                                    $lang == 'ar'
                                    ? ($emp['name_ar'] ?: $emp['name_en'])
                                    : ($emp['name_en'] ?: $emp['name_ar'])
                                ) ?>
                            </td>

                            <td><?= htmlspecialchars($emp['company_name']) ?></td>

                            <td>
                                <?= htmlspecialchars(
                                    $lang == 'ar'
                                    ? ($emp['job_title_ar'] ?: $emp['job_title_en'])
                                    : ($emp['job_title_en'] ?: $emp['job_title_ar'])
                                ) ?>
                            </td>

                            <td>
                                <span class="status-pill status-<?= htmlspecialchars($emp['employee_status'] ?? 'active') ?>">
                                    <?= htmlspecialchars($emp['employee_status'] ?? 'active') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

    </div>

</main>

<?php include "includes/footer.php"; ?>