<?php
require_once __DIR__ . "/../config/db.php";

$msg = "";

/* ===== Load current settings (use id=1) ===== */
$row = [
  "id" => 0,
  "shop_name" => "",
  "phone" => "",
  "address" => "",
  "invoice_prefix" => "INV-",
  "currency" => "USD",
  "low_stock_alert" => 3
];

$get = "SELECT * FROM settings ORDER BY id ASC LIMIT 1";
$resGet = mysqli_query($conn, $get);
if ($resGet && mysqli_num_rows($resGet) == 1) {
  $row = mysqli_fetch_assoc($resGet);
}

/* ===== Save settings ===== */
if (isset($_POST["save"])) {
  $shop_name = mysqli_real_escape_string($conn, $_POST["shop_name"] ?? "");
  $phone = mysqli_real_escape_string($conn, $_POST["phone"] ?? "");
  $address = mysqli_real_escape_string($conn, $_POST["address"] ?? "");
  $invoice_prefix = mysqli_real_escape_string($conn, $_POST["invoice_prefix"] ?? "INV-");
  $currency = mysqli_real_escape_string($conn, $_POST["currency"] ?? "USD");
  $low_stock_alert = (int)($_POST["low_stock_alert"] ?? 3);

  if ($low_stock_alert < 0) $low_stock_alert = 0;
  if ($invoice_prefix == "") $invoice_prefix = "INV-";
  if ($currency == "") $currency = "USD";

  if ((int)$row["id"] == 0) {
    // INSERT first row
    $insert = "INSERT INTO settings(shop_name,phone,address,invoice_prefix,currency,low_stock_alert)
               VALUES('$shop_name','$phone','$address','$invoice_prefix','$currency',$low_stock_alert)";
    $ok = mysqli_query($conn, $insert);
    $msg = $ok ? "Settings saved!" : "Save failed!";
  } else {
    // UPDATE existing row
    $id = (int)$row["id"];
    $update = "UPDATE settings SET
                shop_name='$shop_name',
                phone='$phone',
                address='$address',
                invoice_prefix='$invoice_prefix',
                currency='$currency',
                low_stock_alert=$low_stock_alert
              WHERE id=$id";
    $ok = mysqli_query($conn, $update);
    $msg = $ok ? "Settings updated!" : "Update failed!";
  }

  // Reload after save
  $resGet = mysqli_query($conn, "SELECT * FROM settings ORDER BY id ASC LIMIT 1");
  if ($resGet && mysqli_num_rows($resGet) == 1) {
    $row = mysqli_fetch_assoc($resGet);
  }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings - SalesBook Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/settings.css">
</head>
<body>

<div class="sidebar">
  <h4>SalesBook</h4>
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="books.php">📚 Books</a>
  <a href="customers.php">👤 Customers</a>
  <a href="sales.php">🧾 Sales / POS</a>
  <a href="reports.php">📊 Reports</a>
  <a href="settings.php" class="active">⚙ Settings</a>
</div>

<div class="main">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0">Settings</h4>
      <small class="text-muted">Shop info, invoice prefix, currency, low stock alert</small>
    </div>
  </div>

  <?php if($msg): ?>
    <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <form method="post" class="row g-3">

    <div class="col-lg-6">
      <div class="card-box">
        <h5 class="mb-3">Shop Info</h5>

        <div class="mb-2">
          <label class="form-label fw-semibold">Shop Name</label>
          <input name="shop_name" class="form-control" value="<?= htmlspecialchars($row["shop_name"] ?? "") ?>">
        </div>

        <div class="mb-2">
          <label class="form-label fw-semibold">Phone</label>
          <input name="phone" class="form-control" value="<?= htmlspecialchars($row["phone"] ?? "") ?>">
        </div>

        <div class="mb-2">
          <label class="form-label fw-semibold">Address</label>
          <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($row["address"] ?? "") ?></textarea>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card-box">
        <h5 class="mb-3">Invoice Settings</h5>

        <div class="mb-2">
          <label class="form-label fw-semibold">Invoice Prefix</label>
          <input name="invoice_prefix" class="form-control" value="<?= htmlspecialchars($row["invoice_prefix"] ?? "INV-") ?>">
          <div class="text-muted small mt-1">Example: INV- or SB-</div>
        </div>

        <div class="mb-2">
          <label class="form-label fw-semibold">Currency</label>
          <select name="currency" class="form-select">
            <?php $cur = $row["currency"] ?? "USD"; ?>
            <option value="USD" <?= ($cur==="USD")?"selected":"" ?>>USD ($)</option>
            <option value="KHR" <?= ($cur==="KHR")?"selected":"" ?>>KHR (៛)</option>
          </select>
        </div>

        <div class="mb-2">
          <label class="form-label fw-semibold">Low Stock Alert</label>
          <input name="low_stock_alert" type="number" class="form-control" value="<?= (int)($row["low_stock_alert"] ?? 3) ?>">
          <div class="text-muted small mt-1">Alert when stock <= this number</div>
        </div>

        <button name="save" value="1" class="btn btn-primary w-100 mt-2">Save Settings</button>
      </div>
    </div>

  </form>

  <div class="text-center text-muted small mt-4">
    © <?= date("Y"); ?> SalesBook Admin
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>