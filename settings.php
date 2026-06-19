<?php
require_once "auth.php";
require_once "config.php";

if ($_SESSION['user_role'] !== 'admin') {
    die("Access Denied");
}

function uploadLogo($oldLogo = '') {
    if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        return $oldLogo;
    }

    $folder = "uploads/settings";

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    if (!empty($oldLogo) && file_exists($folder . "/" . $oldLogo)) {
        unlink($folder . "/" . $oldLogo);
    }

    $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
    $fileName = "logo_" . time() . "." . $ext;

    move_uploaded_file($_FILES['logo']['tmp_name'], $folder . "/" . $fileName);

    return $fileName;
}

$stmt = $conn->prepare("SELECT * FROM company_settings WHERE id = 1");
$stmt->execute();
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$settings) {
    $conn->query("INSERT INTO company_settings (id, company_name) VALUES (1, 'AL WATAD')");
    $stmt = $conn->prepare("SELECT * FROM company_settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $logo = uploadLogo($settings['logo'] ?? '');

    $stmt = $conn->prepare("
        UPDATE company_settings SET
            company_name = :company_name,
            company_name_ar = :company_name_ar,
            phone = :phone,
            email = :email,
            website = :website,
            address = :address,
            trn = :trn,
            trade_license = :trade_license,
            default_language = :default_language,
            currency = :currency,
            timezone = :timezone,
            expiry_alert_days = :expiry_alert_days,
            logo = :logo
        WHERE id = 1
    ");

    $stmt->execute([
        ':company_name' => $_POST['company_name'] ?? '',
        ':company_name_ar' => $_POST['company_name_ar'] ?? '',
        ':phone' => $_POST['phone'] ?? '',
        ':email' => $_POST['email'] ?? '',
        ':website' => $_POST['website'] ?? '',
        ':address' => $_POST['address'] ?? '',
        ':trn' => $_POST['trn'] ?? '',
        ':trade_license' => $_POST['trade_license'] ?? '',
        ':default_language' => $_POST['default_language'] ?? 'ar',
        ':currency' => $_POST['currency'] ?? 'AED',
        ':timezone' => $_POST['timezone'] ?? 'Asia/Dubai',
        ':expiry_alert_days' => $_POST['expiry_alert_days'] ?? 30,
        ':logo' => $logo
    ]);

    header("Location: settings.php?saved=1");
    exit;
}

include "includes/header.php";
include "includes/sidebar.php";
?>

<main class="main">

<div class="page-header">
    <div>
        <h1><?= $lang == 'ar' ? 'الإعدادات' : 'Settings' ?></h1>
        <p><?= $lang == 'ar' ? 'إعدادات الشركة والنظام' : 'Company and system settings' ?></p>
    </div>

    <a class="primary-btn" href="users.php">
        <?= $lang == 'ar' ? 'إدارة المستخدمين' : 'Manage Users' ?>
    </a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert-success">
        <?= $lang == 'ar' ? 'تم حفظ الإعدادات بنجاح' : 'Settings saved successfully' ?>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<section class="panel">
    <h2><?= $lang == 'ar' ? 'بيانات الشركة' : 'Company Information' ?></h2>

    <?php if (!empty($settings['logo'])): ?>
        <img src="uploads/settings/<?= htmlspecialchars($settings['logo']) ?>" class="settings-logo">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-group">
            <label><?= $lang == 'ar' ? 'اسم الشركة بالإنجليزية' : 'Company Name English' ?></label>
            <input name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'اسم الشركة بالعربية' : 'Company Name Arabic' ?></label>
            <input name="company_name_ar" value="<?= htmlspecialchars($settings['company_name_ar'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'الهاتف' : 'Phone' ?></label>
            <input name="phone" value="<?= htmlspecialchars($settings['phone'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'البريد الإلكتروني' : 'Email' ?></label>
            <input name="email" value="<?= htmlspecialchars($settings['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'الموقع الإلكتروني' : 'Website' ?></label>
            <input name="website" value="<?= htmlspecialchars($settings['website'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>TRN</label>
            <input name="trn" value="<?= htmlspecialchars($settings['trn'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'رقم الرخصة التجارية' : 'Trade License' ?></label>
            <input name="trade_license" value="<?= htmlspecialchars($settings['trade_license'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'شعار الشركة' : 'Company Logo' ?></label>
            <input type="file" name="logo" accept="image/*">
        </div>
    </div>

    <div class="form-group" style="margin-top:14px;">
        <label><?= $lang == 'ar' ? 'العنوان' : 'Address' ?></label>
        <textarea name="address" rows="3"><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
    </div>
</section>

<section class="panel profile-section">
    <h2><?= $lang == 'ar' ? 'إعدادات النظام' : 'System Settings' ?></h2>

    <div class="form-grid">
        <div class="form-group">
            <label><?= $lang == 'ar' ? 'اللغة الافتراضية' : 'Default Language' ?></label>
            <select name="default_language">
                <option value="ar" <?= ($settings['default_language'] ?? '') == 'ar' ? 'selected' : '' ?>>Arabic</option>
                <option value="en" <?= ($settings['default_language'] ?? '') == 'en' ? 'selected' : '' ?>>English</option>
            </select>
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'العملة' : 'Currency' ?></label>
            <input name="currency" value="<?= htmlspecialchars($settings['currency'] ?? 'AED') ?>">
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'المنطقة الزمنية' : 'Timezone' ?></label>
            <input name="timezone" value="<?= htmlspecialchars($settings['timezone'] ?? 'Asia/Dubai') ?>">
        </div>

        <div class="form-group">
            <label><?= $lang == 'ar' ? 'تنبيه انتهاء المستندات قبل كام يوم' : 'Document expiry alert days' ?></label>
            <input type="number" name="expiry_alert_days" value="<?= htmlspecialchars($settings['expiry_alert_days'] ?? 30) ?>">
        </div>
    </div>
</section>

<section class="panel profile-section">
    <h2><?= $lang == 'ar' ? 'إعدادات الإدارة' : 'Administration' ?></h2>

    <div class="settings-links">
        <a class="primary-btn" href="users.php">
            <?= $lang == 'ar' ? 'إدارة المستخدمين والصلاحيات' : 'Users & Permissions' ?>
        </a>

        <a class="reset-btn" href="projects.php">
            <?= $lang == 'ar' ? 'إدارة المشاريع' : 'Manage Projects' ?>
        </a>
    </div>
</section>

<div class="save-bar">
    <button type="submit">
        <?= $lang == 'ar' ? 'حفظ الإعدادات' : 'Save Settings' ?>
    </button>
</div>

</form>

</main>

<?php include "includes/footer.php"; ?>