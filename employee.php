<?php
require_once "auth.php";
require_once "config.php";

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: employees.php");
    exit;
}

/* Delete note */
if (isset($_GET['delete_note'])) {
    $noteId = (int) $_GET['delete_note'];

    $stmt = $conn->prepare("
        DELETE FROM employee_notes
        WHERE id = :note_id
        AND employee_id = :employee_id
    ");

    $stmt->execute([
        ':note_id' => $noteId,
        ':employee_id' => $id
    ]);

    header("Location: employee.php?id=" . $id . "#notes");
    exit;
}

function deleteFileIfExists($folder, $file) {
    if (!empty($file)) {
        $path = $folder . "/" . $file;
        if (file_exists($path)) {
            unlink($path);
        }
    }
}

function uploadFile($field, $folder, $oldFile = '') {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return $oldFile;
    }

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    deleteFileIfExists($folder, $oldFile);

    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $fileName = uniqid($field . "_") . "." . $ext;

    move_uploaded_file($_FILES[$field]['tmp_name'], $folder . "/" . $fileName);

    return $fileName;
}

/* Get employee */
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = :id");
$stmt->execute([':id' => $id]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$emp) {
    die("Employee not found.");
}

/* Add note */
if (isset($_POST['new_note']) && trim($_POST['new_note']) !== '') {
    $addNote = $conn->prepare("
        INSERT INTO employee_notes (employee_id, note, created_by)
        VALUES (:employee_id, :note, :created_by)
    ");

    $addNote->execute([
        ':employee_id' => $id,
        ':note' => trim($_POST['new_note']),
        ':created_by' => $_SESSION['user_id'] ?? null
    ]);

    header("Location: employee.php?id=" . $id . "#notes");
    exit;
}

/* Update employee */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['new_note'])) {

    $photo = $emp['photo'] ?? '';
    $contract_pdf = $emp['contract_pdf'] ?? '';
    $offer_letter_pdf = $emp['offer_letter_pdf'] ?? '';
    $passport_pdf = $emp['passport_pdf'] ?? '';
    $resident_file_pdf = $emp['resident_file_pdf'] ?? '';

    if (isset($_POST['remove_photo'])) {
        deleteFileIfExists('uploads/employees', $photo);
        $photo = '';
    } else {
        $photo = uploadFile('photo', 'uploads/employees', $photo);
    }

    foreach (['contract_pdf', 'offer_letter_pdf', 'passport_pdf', 'resident_file_pdf'] as $field) {
        if (isset($_POST['remove_' . $field])) {
            deleteFileIfExists('uploads/documents', $$field);
            $$field = '';
        } else {
            $$field = uploadFile($field, 'uploads/documents', $$field);
        }
    }

    $sql = "UPDATE employees SET
        emp_id=:emp_id,
        passport_no=:passport_no,
        name_en=:name_en,
        name_ar=:name_ar,
        phone_number=:phone_number,
        gender=:gender,
        marital_status=:marital_status,
        date_of_birth=:date_of_birth,
        age=:age,
        nationality=:nationality,
        company_name=:company_name,
        company_code=:company_code,
        job_title_en=:job_title_en,
        job_title_ar=:job_title_ar,
        permit_type=:permit_type,
        expiry_date=:expiry_date,
        contract_type=:contract_type,
        labour_card_no=:labour_card_no,
        visa_emirate=:visa_emirate,
        category=:category,
        member_type=:member_type,
        gross_salary=:gross_salary,
        emirates_id_number=:emirates_id_number,
        resident_file_number=:resident_file_number,
        passport_expiry=:passport_expiry,
        visa_expiry=:visa_expiry,
        emirates_id_expiry=:emirates_id_expiry,
        labour_card_expiry=:labour_card_expiry,
        employee_status=:employee_status,
        leaving_date=:leaving_date,
        leaving_reason=:leaving_reason,
        photo=:photo,
        contract_pdf=:contract_pdf,
        offer_letter_pdf=:offer_letter_pdf,
        passport_pdf=:passport_pdf,
        resident_file_pdf=:resident_file_pdf
        WHERE id=:id";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':emp_id' => $_POST['emp_id'] ?? '',
        ':passport_no' => $_POST['passport_no'] ?? '',
        ':name_en' => $_POST['name_en'] ?? '',
        ':name_ar' => $_POST['name_ar'] ?? '',
        ':phone_number' => $_POST['phone_number'] ?? '',
        ':gender' => $_POST['gender'] ?? '',
        ':marital_status' => $_POST['marital_status'] ?? '',
        ':date_of_birth' => $_POST['date_of_birth'] ?? '',
        ':age' => $_POST['age'] ?? '',
        ':nationality' => $_POST['nationality'] ?? '',
        ':company_name' => $_POST['company_name'] ?? '',
        ':company_code' => $_POST['company_code'] ?? '',
        ':job_title_en' => $_POST['job_title_en'] ?? '',
        ':job_title_ar' => $_POST['job_title_ar'] ?? '',
        ':permit_type' => $_POST['permit_type'] ?? '',
        ':expiry_date' => $_POST['expiry_date'] ?? '',
        ':contract_type' => $_POST['contract_type'] ?? '',
        ':labour_card_no' => $_POST['labour_card_no'] ?? '',
        ':visa_emirate' => $_POST['visa_emirate'] ?? '',
        ':category' => $_POST['category'] ?? '',
        ':member_type' => $_POST['member_type'] ?? '',
        ':gross_salary' => $_POST['gross_salary'] ?? '',
        ':emirates_id_number' => $_POST['emirates_id_number'] ?? '',
        ':resident_file_number' => $_POST['resident_file_number'] ?? '',
        ':passport_expiry' => $_POST['passport_expiry'] ?? '',
        ':visa_expiry' => $_POST['visa_expiry'] ?? '',
        ':emirates_id_expiry' => $_POST['emirates_id_expiry'] ?? '',
        ':labour_card_expiry' => $_POST['labour_card_expiry'] ?? '',
        ':employee_status' => $_POST['employee_status'] ?? 'active',
        ':leaving_date' => $_POST['leaving_date'] ?? '',
        ':leaving_reason' => $_POST['leaving_reason'] ?? '',
        ':photo' => $photo,
        ':contract_pdf' => $contract_pdf,
        ':offer_letter_pdf' => $offer_letter_pdf,
        ':passport_pdf' => $passport_pdf,
        ':resident_file_pdf' => $resident_file_pdf,
        ':id' => $id
    ]);

    header("Location: employee.php?id=" . $id . "&saved=1");
    exit;
}

/* Get notes */
$notesStmt = $conn->prepare("
    SELECT employee_notes.*, users.name AS created_by_name
    FROM employee_notes
    LEFT JOIN users ON employee_notes.created_by = users.id
    WHERE employee_notes.employee_id = :employee_id
    ORDER BY employee_notes.created_at DESC
");

$notesStmt->execute([
    ':employee_id' => $id
]);

$notes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);

include "includes/header.php";
include "includes/sidebar.php";
?>

<main class="main">

<div class="page-header">
    <div>
        <h1>
            <?= htmlspecialchars($lang == 'ar' ? ($emp['name_ar'] ?: $emp['name_en']) : ($emp['name_en'] ?: $emp['name_ar'])) ?>
        </h1>
        <p><?= $lang == 'ar' ? 'ملف الموظف الإلكتروني' : 'Employee Electronic Profile' ?></p>
    </div>

    <a class="reset-btn" href="employees.php">
        <?= $lang == 'ar' ? 'رجوع للموظفين' : 'Back to Employees' ?>
    </a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert-success">
        <?= $lang == 'ar' ? 'تم حفظ البيانات بنجاح' : 'Saved successfully' ?>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<div class="profile-layout">

<section class="profile-card">

    <?php if (!empty($emp['photo'])): ?>
        <img src="uploads/employees/<?= htmlspecialchars($emp['photo']) ?>" class="profile-photo">

        <label class="remove-photo">
            <input type="checkbox" name="remove_photo" value="1">
            <?= $lang == 'ar' ? 'حذف الصورة' : 'Remove Photo' ?>
        </label>
    <?php else: ?>
        <div class="avatar">
            <?= mb_substr($emp['name_en'] ?: $emp['name_ar'], 0, 1) ?>
        </div>
    <?php endif; ?>

    <div class="form-group">
        <label><?= $lang == 'ar' ? 'صورة الموظف' : 'Profile Photo' ?></label>
        <input type="file" name="photo" accept="image/*">
    </div>

    <h2><?= htmlspecialchars($emp['name_en'] ?: $emp['name_ar']) ?></h2>
    <p><?= htmlspecialchars($emp['job_title_en'] ?: $emp['job_title_ar']) ?></p>

    <div class="profile-meta"><span>Emp ID</span><strong dir="ltr"><?= htmlspecialchars((string)$emp['emp_id']) ?></strong></div>
    <div class="profile-meta"><span><?= $lang == 'ar' ? 'الشركة' : 'Company' ?></span><strong><?= htmlspecialchars($emp['company_name']) ?></strong></div>
    <div class="profile-meta"><span><?= $lang == 'ar' ? 'الجنسية' : 'Nationality' ?></span><strong><?= htmlspecialchars($emp['nationality']) ?></strong></div>
    <div class="profile-meta"><span><?= $lang == 'ar' ? 'الراتب' : 'Salary' ?></span><strong><?= htmlspecialchars($emp['gross_salary']) ?></strong></div>

</section>

<section class="panel">
<h2><?= $lang == 'ar' ? 'البيانات الأساسية' : 'Basic Information' ?></h2>

<div class="form-grid">
    <div class="form-group"><label>Emp ID</label><input name="emp_id" dir="ltr" value="<?= htmlspecialchars((string)$emp['emp_id']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'رقم الجواز' : 'Passport No' ?></label><input name="passport_no" dir="ltr" value="<?= htmlspecialchars((string)$emp['passport_no']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'الاسم بالإنجليزية' : 'Name English' ?></label><input name="name_en" dir="ltr" value="<?= htmlspecialchars($emp['name_en']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'الاسم بالعربية' : 'Name Arabic' ?></label><input name="name_ar" dir="rtl" value="<?= htmlspecialchars($emp['name_ar']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'رقم الهاتف' : 'Phone Number' ?></label><input name="phone_number" dir="ltr" value="<?= htmlspecialchars((string)$emp['phone_number']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'النوع' : 'Gender' ?></label><input name="gender" value="<?= htmlspecialchars($emp['gender']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'الحالة الاجتماعية' : 'Marital Status' ?></label><input name="marital_status" value="<?= htmlspecialchars($emp['marital_status']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'تاريخ الميلاد' : 'Date of Birth' ?></label><input name="date_of_birth" value="<?= htmlspecialchars($emp['date_of_birth']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'السن' : 'Age' ?></label><input name="age" value="<?= htmlspecialchars($emp['age']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'الجنسية' : 'Nationality' ?></label><input name="nationality" value="<?= htmlspecialchars($emp['nationality']) ?>"></div>
</div>
</section>

</div>

<section class="panel profile-section">
<h2><?= $lang == 'ar' ? 'بيانات العمل' : 'Work Information' ?></h2>

<div class="form-grid">
    <div class="form-group"><label><?= $lang == 'ar' ? 'الشركة' : 'Company' ?></label><input name="company_name" value="<?= htmlspecialchars($emp['company_name']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'كود الشركة' : 'Company Code' ?></label><input name="company_code" value="<?= htmlspecialchars($emp['company_code']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'المهنة بالإنجليزية' : 'Job English' ?></label><input name="job_title_en" dir="ltr" value="<?= htmlspecialchars($emp['job_title_en']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'المهنة بالعربية' : 'Job Arabic' ?></label><input name="job_title_ar" dir="rtl" value="<?= htmlspecialchars($emp['job_title_ar']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'نوع التصريح' : 'Permit Type' ?></label><input name="permit_type" value="<?= htmlspecialchars($emp['permit_type']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'تاريخ الانتهاء العام' : 'General Expiry Date' ?></label><input name="expiry_date" value="<?= htmlspecialchars($emp['expiry_date']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'نوع العقد' : 'Contract Type' ?></label><input name="contract_type" value="<?= htmlspecialchars($emp['contract_type']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'رقم بطاقة العمل' : 'Labour Card No' ?></label><input name="labour_card_no" dir="ltr" value="<?= htmlspecialchars((string)$emp['labour_card_no']) ?>"></div>
</div>
</section>

<section class="panel profile-section">
<h2><?= $lang == 'ar' ? 'حالة الموظف' : 'Employee Status' ?></h2>

<div class="form-grid">
    <?php $status = $emp['employee_status'] ?? 'active'; ?>

    <div class="form-group">
        <label><?= $lang == 'ar' ? 'الحالة' : 'Status' ?></label>
        <select name="employee_status">
            <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>Active</option>
            <option value="on_leave" <?= $status == 'on_leave' ? 'selected' : '' ?>>On Leave</option>
            <option value="resigned" <?= $status == 'resigned' ? 'selected' : '' ?>>Resigned</option>
            <option value="terminated" <?= $status == 'terminated' ? 'selected' : '' ?>>Terminated</option>
            <option value="deleted" <?= $status == 'deleted' ? 'selected' : '' ?>>Deleted</option>
        </select>
    </div>

    <div class="form-group">
        <label><?= $lang == 'ar' ? 'تاريخ ترك العمل' : 'Leaving Date' ?></label>
        <input name="leaving_date" placeholder="dd-mm-yyyy" value="<?= htmlspecialchars($emp['leaving_date'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label><?= $lang == 'ar' ? 'سبب ترك العمل' : 'Leaving Reason' ?></label>
        <input name="leaving_reason" value="<?= htmlspecialchars($emp['leaving_reason'] ?? '') ?>">
    </div>
</div>
</section>

<section class="panel profile-section">
<h2><?= $lang == 'ar' ? 'بيانات الإقامة والراتب' : 'Residency & Salary' ?></h2>

<div class="form-grid">
    <div class="form-group"><label><?= $lang == 'ar' ? 'إمارة إصدار الفيزا' : 'Visa Emirate' ?></label><input name="visa_emirate" value="<?= htmlspecialchars($emp['visa_emirate']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'الفئة' : 'Category' ?></label><input name="category" value="<?= htmlspecialchars($emp['category']) ?>"></div>
    <div class="form-group"><label>Member Type</label><input name="member_type" value="<?= htmlspecialchars($emp['member_type']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'الراتب الإجمالي' : 'Gross Salary' ?></label><input name="gross_salary" value="<?= htmlspecialchars($emp['gross_salary']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'رقم الهوية الإماراتية' : 'Emirates ID Number' ?></label><input name="emirates_id_number" dir="ltr" value="<?= htmlspecialchars((string)$emp['emirates_id_number']) ?>"></div>
    <div class="form-group"><label><?= $lang == 'ar' ? 'رقم ملف الإقامة' : 'Resident File Number' ?></label><input name="resident_file_number" dir="ltr" value="<?= htmlspecialchars((string)$emp['resident_file_number']) ?>"></div>
    <div class="form-group"><label>Passport Expiry</label><input name="passport_expiry" placeholder="dd-mm-yyyy" value="<?= htmlspecialchars($emp['passport_expiry'] ?? '') ?>"></div>
    <div class="form-group"><label>Visa Expiry</label><input name="visa_expiry" placeholder="dd-mm-yyyy" value="<?= htmlspecialchars($emp['visa_expiry'] ?? '') ?>"></div>
    <div class="form-group"><label>Emirates ID Expiry</label><input name="emirates_id_expiry" placeholder="dd-mm-yyyy" value="<?= htmlspecialchars($emp['emirates_id_expiry'] ?? '') ?>"></div>
    <div class="form-group"><label>Labour Card Expiry</label><input name="labour_card_expiry" placeholder="dd-mm-yyyy" value="<?= htmlspecialchars($emp['labour_card_expiry'] ?? '') ?>"></div>
</div>
</section>

<section class="panel profile-section">
<h2><?= $lang == 'ar' ? 'المستندات' : 'Documents' ?></h2>

<div class="form-grid">
<?php
$docs = [
    'contract_pdf' => 'Contract PDF',
    'offer_letter_pdf' => 'Offer Letter PDF',
    'passport_pdf' => 'Passport PDF',
    'resident_file_pdf' => 'Resident File PDF'
];
?>

<?php foreach ($docs as $field => $label): ?>
    <div class="form-group document-box">
        <label><?= $label ?></label>

        <?php if (!empty($emp[$field])): ?>
            <a class="doc-link" target="_blank" href="uploads/documents/<?= htmlspecialchars($emp[$field]) ?>">
                <?= $lang == 'ar' ? 'عرض الملف الحالي' : 'View current file' ?>
            </a>

            <label class="remove-doc">
                <input type="checkbox" name="remove_<?= $field ?>" value="1">
                <?= $lang == 'ar' ? 'حذف الملف' : 'Remove file' ?>
            </label>
        <?php endif; ?>

        <input type="file" name="<?= $field ?>" accept="application/pdf,image/*">
    </div>
<?php endforeach; ?>
</div>
</section>

<div class="save-bar">
    <button type="submit"><?= $lang == 'ar' ? 'حفظ التعديلات' : 'Save Changes' ?></button>
</div>

</form>

<section class="panel profile-section" id="notes">
    <h2><?= $lang == 'ar' ? 'ملاحظات الموظف' : 'Employee Notes' ?></h2>

    <form method="POST">
        <div class="form-group">
            <textarea
                name="new_note"
                rows="4"
                placeholder="<?= $lang == 'ar' ? 'اكتب ملاحظة...' : 'Write note...' ?>"
                required
            ></textarea>
        </div>

        <button class="primary-btn" type="submit">
            <?= $lang == 'ar' ? 'إضافة ملاحظة' : 'Add Note' ?>
        </button>
    </form>

    <hr style="margin:20px 0;">

    <?php if (empty($notes)): ?>
        <p><?= $lang == 'ar' ? 'لا توجد ملاحظات' : 'No notes found' ?></p>
    <?php else: ?>
        <?php foreach ($notes as $note): ?>
            <div class="note-card">
                <div class="note-header">
                    <strong><?= htmlspecialchars($note['created_by_name'] ?? 'Unknown User') ?></strong>

                    <span><?= htmlspecialchars($note['created_at']) ?></span>

                    <a class="danger-link"
                       href="employee.php?id=<?= $id ?>&delete_note=<?= $note['id'] ?>#notes"
                       onclick="return confirm('Delete this note?')">
                        <?= $lang == 'ar' ? 'حذف' : 'Delete' ?>
                    </a>
                </div>

                <div class="note-content">
                    <?= nl2br(htmlspecialchars($note['note'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

</main>

<?php include "includes/footer.php"; ?>