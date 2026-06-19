<?php
require_once "auth.php";
require_once "config.php";

$status = $_GET['status'] ?? '';

$sql = "
SELECT *
FROM employees
WHERE expiry_date IS NOT NULL
AND expiry_date != ''
";

$stmt = $conn->query($sql);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "includes/header.php";
include "includes/sidebar.php";
?>

<main class="main">

<div class="page-header">
    <div>
        <h1><?= $lang == 'ar' ? 'متابعة المستندات' : 'Documents Tracker' ?></h1>
        <p><?= $lang == 'ar' ? 'حالة انتهاء مستندات الموظفين' : 'Employees documents status' ?></p>
    </div>
</div>

<div class="panel">

    <form method="GET" class="filters-box">

        <select name="status">

            <option value="">
                <?= $lang == 'ar' ? 'كل الحالات' : 'All Statuses' ?>
            </option>

            <option value="expired" <?= $status == 'expired' ? 'selected' : '' ?>>
                <?= $lang == 'ar' ? 'منتهي' : 'Expired' ?>
            </option>

            <option value="soon" <?= $status == 'soon' ? 'selected' : '' ?>>
                <?= $lang == 'ar' ? 'ينتهي قريباً' : 'Expiring Soon' ?>
            </option>

            <option value="valid" <?= $status == 'valid' ? 'selected' : '' ?>>
                <?= $lang == 'ar' ? 'ساري' : 'Valid' ?>
            </option>

        </select>

        <button class="primary-btn">
            <?= $lang == 'ar' ? 'تصفية' : 'Filter' ?>
        </button>

    </form>

</div>

<div class="panel table-panel">

<table class="data-table">

<thead>
<tr>
    <th>Emp ID</th>
    <th><?= $lang == 'ar' ? 'الموظف' : 'Employee' ?></th>
    <th><?= $lang == 'ar' ? 'الشركة' : 'Company' ?></th>
    <th><?= $lang == 'ar' ? 'تاريخ الانتهاء' : 'Expiry Date' ?></th>
    <th><?= $lang == 'ar' ? 'الحالة' : 'Status' ?></th>
</tr>
</thead>

<tbody>

<?php

foreach ($employees as $emp) {

    $expiry = DateTime::createFromFormat('d-m-Y', $emp['expiry_date']);

    if (!$expiry) {
        continue;
    }

    $today = new DateTime();
    $days = $today->diff($expiry)->days;

    if ($expiry < $today) {
        $docStatus = 'expired';
        $label = $lang == 'ar' ? 'منتهي' : 'Expired';
        $class = 'status-expired';
    }
    elseif ($days <= 30) {
        $docStatus = 'soon';
        $label = $lang == 'ar' ? 'ينتهي قريباً' : 'Expiring Soon';
        $class = 'status-soon';
    }
    else {
        $docStatus = 'valid';
        $label = $lang == 'ar' ? 'ساري' : 'Valid';
        $class = 'status-valid';
    }

    if ($status && $status != $docStatus) {
        continue;
    }

?>

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

    <td><?= htmlspecialchars($emp['expiry_date']) ?></td>

    <td>
        <span class="<?= $class ?>">
            <?= $label ?>
        </span>
    </td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</main>

<?php include "includes/footer.php"; ?>