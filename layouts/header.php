<?php
/**
 * Layout Header - Common header with sidebar for admin pages
 * @param string $pageTitle - Page title for the <title> tag
 * @param string $activePage - Which menu item is active (dashboard|books|customers|sales|reports|settings)
 * @param string $cssFile - CSS file name (e.g., 'dashboard', 'books', etc.)
 * @param string $extraTitle - Extra subtitle shown in topbar
 * @param string $noPrint - Add 'no-print' class to sidebar and topbar (for reports)
 */

$activePage = $activePage ?? 'dashboard';
$cssFile = $cssFile ?? 'dashboard';
$extraTitle = $extraTitle ?? '';
$noPrint = $noPrint ?? '';

// Admin info (later use session)
$adminName = "Admin";
$adminEmail = "admin@salesbook.com";
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle ?? 'SalesBook Admin') ?></title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/<?= htmlspecialchars($cssFile) ?>.css">

</head>
<body>

<div class="sidebar <?= $noPrint ?>">
  <h4>SalesBook</h4>
  <a href="dashboard.php" class="<?= ($activePage === 'dashboard') ? 'active' : '' ?>">🏠 Dashboard</a>
  <a href="books.php" class="<?= ($activePage === 'books') ? 'active' : '' ?>">📚 Books</a>
  <a href="customers.php" class="<?= ($activePage === 'customers') ? 'active' : '' ?>">👤 Customers</a>
  <a href="sales.php" class="<?= ($activePage === 'sales') ? 'active' : '' ?>">🧾 Sales</a>
  <a href="reports.php" class="<?= ($activePage === 'reports') ? 'active' : '' ?>">📊 Reports</a>
  <a href="settings.php" class="<?= ($activePage === 'settings') ? 'active' : '' ?>">⚙ Settings</a>
</div>

<div class="main">

  <div class="topbar d-flex justify-content-between align-items-center <?= $noPrint ?>">
    <div>
      <h5 class="mb-0"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h5>
      <?php if($extraTitle): ?>
        <small class="text-muted"><?= htmlspecialchars($extraTitle) ?></small>
      <?php endif; ?>
    </div>

    <div class="profile-top">
      <div class="avatar"><?= strtoupper(substr($adminName,0,1)) ?></div>
      <div class="profile-info">
        <div class="fw-semibold"><?= htmlspecialchars($adminName) ?></div>
        <div class="small small-muted"><?= htmlspecialchars($adminEmail) ?></div>
      </div>
    </div>
  </div>
