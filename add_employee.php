<?php
require_once "auth.php";
require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $conn->prepare("
        INSERT INTO employees (
            emp_id,
            name_en,
            name_ar,
            passport_no,
            company_name,
            job_title_en,
            job_title_ar,
            nationality,
            phone_number,
            gross_salary,
            employee_status
        )
        VALUES (
            :emp_id,
            :name_en,
            :name_ar,
            :passport_no,
            :company_name,
            :job_title_en,
            :job_title_ar,
            :nationality,
            :phone_number,
            :gross_salary,
            :employee_status
        )
    ");

    $stmt->execute([
        ':emp_id' => $_POST['emp_id'],
        ':name_en' => $_POST['name_en'],
        ':name_ar' => $_POST['name_ar'],
        ':passport_no' => $_POST['passport_no'],
        ':company_name' => $_POST['company_name'],
        ':job_title_en' => $_POST['job_title_en'],
        ':job_title_ar' => $_POST['job_title_ar'],
        ':nationality' => $_POST['nationality'],
        ':phone_number' => $_POST['phone_number'],
        ':gross_salary' => $_POST['gross_salary'],
        ':employee_status' => $_POST['employee_status']
    ]);

    $employeeId = $conn->lastInsertId();

    header("Location: employee.php?id=" . $employeeId);
    exit;
}

include "includes/header.php";
include "includes/sidebar.php";
?>

<main class="main">

<div class="page-header">
    <div>
        <h1><?= $lang == 'ar' ? 'إضافة موظف جديد' : 'Add Employee' ?></h1>
    </div>
</div>

<div class="panel">

<form method="POST" class="form-grid">

    <div class="form-group">
        <label>Emp ID</label>
        <input name="emp_id" required>
    </div>

    <div class="form-group">
        <label>Name English</label>
        <input name="name_en">
    </div>

    <div class="form-group">
        <label>الاسم بالعربية</label>
        <input name="name_ar">
    </div>

    <div class="form-group">
        <label>Passport No</label>
        <input name="passport_no">
    </div>

    <div class="form-group">
        <label>Company</label>
        <input name="company_name">
    </div>

    <div class="form-group">
        <label>Job English</label>
        <input name="job_title_en">
    </div>

    <div class="form-group">
        <label>المهنة بالعربية</label>
        <input name="job_title_ar">
    </div>

    <div class="form-group">
        <label>Nationality</label>
        <input name="nationality">
    </div>

    <div class="form-group">
        <label>Phone Number</label>
        <input name="phone_number">
    </div>

    <div class="form-group">
        <label>Gross Salary</label>
        <input name="gross_salary">
    </div>

    <div class="form-group">
        <label>Status</label>
        <select name="employee_status">
            <option value="active">Active</option>
            <option value="on_leave">On Leave</option>
            <option value="resigned">Resigned</option>
            <option value="terminated">Terminated</option>
        </select>
    </div>

    <div class="form-group">
        <label>&nbsp;</label>
        <button class="primary-btn" type="submit">
            <?= $lang == 'ar' ? 'إضافة الموظف' : 'Add Employee' ?>
        </button>
    </div>

</form>

</div>

</main>

<?php include "includes/footer.php"; ?>