<?php require_once __DIR__ . '/../language.php'; ?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<link rel="stylesheet" href="\assets\css\style.css">
<head>
    
  <meta charset="UTF-8">
  <title>AL WATAD HRMS</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  
  <?php
$params = $_GET;

$params['lang'] = 'ar';
$arLink = '?' . http_build_query($params);

$params['lang'] = 'en';
$enLink = '?' . http_build_query($params);
?>

<header class="topbar">

    <div class="topbar-title">
        AL WATAD HRMS
    </div>

    <div class="lang-switch">

        <a href="<?= $arLink ?>"
           class="lang-btn <?= $lang == 'ar' ? 'active' : '' ?>">
            العربية
        </a>

        <a href="<?= $enLink ?>"
           class="lang-btn <?= $lang == 'en' ? 'active' : '' ?>">
            English
        </a>

    </div>

</header>

    
