<?php
require_once "auth.php";
require_once "config.php";

/* Soft Delete / Restore */
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];

    $stmt = $conn->prepare("
        UPDATE employees
        SET deleted_at = NOW(),
            employee_status = 'deleted'
        WHERE id = :id
    ");

    $stmt->execute([':id' => $deleteId]);

    header("Location: employees.php");
    exit;
}

if (isset($_GET['restore'])) {
    $restoreId = (int) $_GET['restore'];

    $stmt = $conn->prepare("
        UPDATE employees
        SET deleted_at = NULL,
            employee_status = 'active'
        WHERE id = :id
    ");

    $stmt->execute([':id' => $restoreId]);

    header("Location: employees.php?view=deleted");
    exit;
}

$search = $_GET['search'] ?? '';
$company = $_GET['company'] ?? '';
$nationality = $_GET['nationality'] ?? '';
$job = $_GET['job'] ?? '';
$memberType = $_GET['member_type'] ?? '';
$category = $_GET['category'] ?? '';
$minSalary = $_GET['min_salary'] ?? '';
$maxSalary = $_GET['max_salary'] ?? '';
$minAge = $_GET['min_age'] ?? '';
$maxAge = $_GET['max_age'] ?? '';
$view = $_GET['view'] ?? 'active';

$companiesList = $conn->query("SELECT DISTINCT company_name FROM employees WHERE company_name != '' ORDER BY company_name")->fetchAll(PDO::FETCH_COLUMN);
$nationalitiesList = $conn->query("SELECT DISTINCT nationality FROM employees WHERE nationality != '' ORDER BY nationality")->fetchAll(PDO::FETCH_COLUMN);
$jobsList = $conn->query("SELECT DISTINCT job_title_en FROM employees WHERE job_title_en != '' ORDER BY job_title_en")->fetchAll(PDO::FETCH_COLUMN);
$memberTypesList = $conn->query("SELECT DISTINCT member_type FROM employees WHERE member_type != '' ORDER BY member_type")->fetchAll(PDO::FETCH_COLUMN);
$categoriesList = $conn->query("SELECT DISTINCT category FROM employees WHERE category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT * FROM employees
        WHERE emp_id != 'emp_id'
        AND name_ar != 'name_ar'
        AND passport_no != 'passport_no'";

if ($view === 'deleted') {
    $sql .= " AND deleted_at IS NOT NULL";
} else {
    $sql .= " AND deleted_at IS NULL";
}

$params = [];

if ($search !== '') {
    $sql .= " AND (
        emp_id LIKE :search
        OR name_en LIKE :search
        OR name_ar LIKE :search
        OR passport_no LIKE :search
        OR company_name LIKE :search
        OR phone_number LIKE :search
    )";
    $params[':search'] = "%$search%";
}

if ($company !== '') {
    $sql .= " AND company_name = :company";
    $params[':company'] = $company;
}

if ($nationality !== '') {
    $sql .= " AND nationality = :nationality";
    $params[':nationality'] = $nationality;
}

if ($job !== '') {
    $sql .= " AND job_title_en = :job";
    $params[':job'] = $job;
}

if ($memberType !== '') {
    $sql .= " AND member_type = :member_type";
    $params[':member_type'] = $memberType;
}

if ($category !== '') {
    $sql .= " AND category = :category";
    $params[':category'] = $category;
}

if ($minSalary !== '') {
    $sql .= " AND CAST(gross_salary AS DECIMAL(10,2)) >= :min_salary";
    $params[':min_salary'] = $minSalary;
}

if ($maxSalary !== '') {
    $sql .= " AND CAST(gross_salary AS DECIMAL(10,2)) <= :max_salary";
    $params[':max_salary'] = $maxSalary;
}

if ($minAge !== '') {
    $sql .= " AND CAST(age AS UNSIGNED) >= :min_age";
    $params[':min_age'] = $minAge;
}

if ($maxAge !== '') {
    $sql .= " AND CAST(age AS UNSIGNED) <= :max_age";
    $params[':max_age'] = $maxAge;
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$avgAge = 0;
$totalAge = 0;
$ageCount = 0;

foreach ($employees as $emp) {
    if (!empty($emp['age']) && is_numeric($emp['age'])) {
        $totalAge += (int)$emp['age'];
        $ageCount++;
    }
}

if ($ageCount > 0) {
    $avgAge = round($totalAge / $ageCount, 1);
}

include "includes/header.php";
include "includes/sidebar.php";
?>

<main class="main">

  <div class="page-header">
    <div>
      <h1>
        <?= $view === 'deleted'
          ? ($lang == 'ar' ? 'الموظفين المحذوفين' : 'Deleted Employees')
          : $trans['employees']
        ?>
      </h1>
      <p><?= $trans['total_employees'] ?>: <?= count($employees) ?></p>
    </div>

    <div class="header-actions">
      <a class="primary-btn" href="add_employee.php">
        <?= $lang == 'ar' ? 'إضافة موظف' : 'Add Employee' ?>
      </a>

      <a class="reset-btn" href="employees.php">
        <?= $lang == 'ar' ? 'الموظفين الحاليين' : 'Active Employees' ?>
      </a>

      <a class="danger-btn" href="employees.php?view=deleted">
        <?= $lang == 'ar' ? 'الموظفين المحذوفين' : 'Deleted Employees' ?>
      </a>
    </div>
  </div>

  <div class="panel">
    <form method="GET" class="filters-box">

      <?php if ($view === 'deleted'): ?>
        <input type="hidden" name="view" value="deleted">
      <?php endif; ?>

      <input
        type="text"
        name="search"
        placeholder="<?= $lang == 'ar' ? 'بحث بالاسم / الجواز / رقم الموظف...' : 'Search name / passport / emp id...' ?>"
        value="<?= htmlspecialchars($search) ?>"
      >

      <select name="company">
        <option value=""><?= $lang == 'ar' ? 'كل الشركات' : 'All Companies' ?></option>
        <?php foreach ($companiesList as $item): ?>
          <option value="<?= htmlspecialchars($item) ?>" <?= $company == $item ? 'selected' : '' ?>>
            <?= htmlspecialchars($item) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="nationality">
        <option value=""><?= $lang == 'ar' ? 'كل الجنسيات' : 'All Nationalities' ?></option>
        <?php foreach ($nationalitiesList as $item): ?>
          <option value="<?= htmlspecialchars($item) ?>" <?= $nationality == $item ? 'selected' : '' ?>>
            <?= htmlspecialchars($item) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="job">
        <option value=""><?= $lang == 'ar' ? 'كل المهن' : 'All Jobs' ?></option>
        <?php foreach ($jobsList as $item): ?>
          <option value="<?= htmlspecialchars($item) ?>" <?= $job == $item ? 'selected' : '' ?>>
            <?= htmlspecialchars($item) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="member_type">
        <option value=""><?= $lang == 'ar' ? 'كل الأنواع' : 'All Member Types' ?></option>
        <?php foreach ($memberTypesList as $item): ?>
          <option value="<?= htmlspecialchars($item) ?>" <?= $memberType == $item ? 'selected' : '' ?>>
            <?= htmlspecialchars($item) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="category">
        <option value=""><?= $lang == 'ar' ? 'كل الفئات' : 'All Categories' ?></option>
        <?php foreach ($categoriesList as $item): ?>
          <option value="<?= htmlspecialchars($item) ?>" <?= $category == $item ? 'selected' : '' ?>>
            <?= htmlspecialchars($item) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <input
        type="number"
        name="min_salary"
        placeholder="<?= $lang == 'ar' ? 'أقل راتب' : 'Min Salary' ?>"
        value="<?= htmlspecialchars($minSalary) ?>"
      >

      <input
        type="number"
        name="max_salary"
        placeholder="<?= $lang == 'ar' ? 'أعلى راتب' : 'Max Salary' ?>"
        value="<?= htmlspecialchars($maxSalary) ?>"
      >

      <input
        type="number"
        name="min_age"
        placeholder="<?= $lang == 'ar' ? 'أقل سن' : 'Min Age' ?>"
        value="<?= htmlspecialchars($minAge) ?>"
      >

      <input
        type="number"
        name="max_age"
        placeholder="<?= $lang == 'ar' ? 'أعلى سن' : 'Max Age' ?>"
        value="<?= htmlspecialchars($maxAge) ?>"
      >

      <button type="submit"><?= $lang == 'ar' ? 'تصفية' : 'Filter' ?></button>

      <a class="reset-btn" href="<?= $view === 'deleted' ? 'employees.php?view=deleted' : 'employees.php' ?>">
        <?= $lang == 'ar' ? 'إلغاء الفلتر' : 'Reset' ?>
      </a>

    </form>

    <div class="stats-line">
      <span><?= $lang == 'ar' ? 'متوسط الأعمار' : 'Average Age' ?>:</span>
      <strong><?= $avgAge ?></strong>
    </div>
  </div>

<div class="panel table-panel">

  <div class="table-toolbar">
    <div>
      <strong>
        <?= $lang == 'ar' ? 'نتائج الموظفين' : 'Employees Results' ?>
      </strong>
      <span><?= count($employees) ?></span>
    </div>
  </div>

  <div class="table-scroll">
    <table class="data-table employees-table">
      <thead>
        <tr>
          <th><?= $trans['emp_id'] ?></th>
          <th><?= $trans['name'] ?></th>
          <th>Passport</th>
          <th><?= $trans['company'] ?></th>
          <th><?= $trans['job'] ?></th>
          <th>Nationality</th>
          <th><?= $lang == 'ar' ? 'السن' : 'Age' ?></th>
          <th>Status</th>
          <th>Salary</th>
          <th class="actions-col">Action</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($employees as $emp): ?>
          <tr>
            <td dir="ltr"><?= htmlspecialchars($emp['emp_id'] ?? '') ?></td>

            <td class="name-cell">
              <?= htmlspecialchars(
                $lang == 'ar'
                ? ($emp['name_ar'] ?: $emp['name_en'])
                : ($emp['name_en'] ?: $emp['name_ar'])
              ) ?>
            </td>

            <td dir="ltr"><?= htmlspecialchars($emp['passport_no'] ?? '') ?></td>
            <td><?= htmlspecialchars($emp['company_name'] ?? '') ?></td>

            <td>
              <?= htmlspecialchars(
                $lang == 'ar'
                ? ($emp['job_title_ar'] ?: $emp['job_title_en'])
                : ($emp['job_title_en'] ?: $emp['job_title_ar'])
              ) ?>
            </td>

            <td><?= htmlspecialchars($emp['nationality'] ?? '') ?></td>
            <td><?= htmlspecialchars($emp['age'] ?? '') ?></td>

            <td>
              <span class="status-pill status-<?= htmlspecialchars($emp['employee_status'] ?? 'active') ?>">
                <?= htmlspecialchars($emp['employee_status'] ?? 'active') ?>
              </span>
            </td>

            <td><?= htmlspecialchars($emp['gross_salary'] ?? '') ?></td>

            <td class="actions-col">
              <div class="table-actions">
                <?php if ($view === 'deleted'): ?>
                  <a class="small-btn" href="employees.php?restore=<?= $emp['id'] ?>">
                    <?= $lang == 'ar' ? 'استرجاع' : 'Restore' ?>
                  </a>
                <?php else: ?>
                  <a class="small-btn" href="employee.php?id=<?= $emp['id'] ?>">
                    <?= $lang == 'ar' ? 'عرض' : 'View' ?>
                  </a>

                  <a class="small-btn danger"
                     href="employees.php?delete=<?= $emp['id'] ?>"
                     onclick="return confirm('Move this employee to deleted list?')">
                    <?= $lang == 'ar' ? 'حذف' : 'Delete' ?>
                  </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div>

</main>

<?php include "includes/footer.php"; ?>