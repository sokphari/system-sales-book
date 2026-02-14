<?php
// admin/dashboard.php
require_once __DIR__ . "/../config/db.php";

// later add session
// session_start();
// $adminName  = $_SESSION['user']['name'] ?? "Admin";
// $adminEmail = $_SESSION['user']['email'] ?? "admin@salesbook.com";

$adminName  = "Admin";
$adminEmail = "admin@salesbook.com";

/* ===== SIMPLE QUERIES ===== */

// total sales (sum of total)
$q_total = "SELECT IFNULL(SUM(total),0) AS total_sales FROM sales";
$r_total = mysqli_query($conn, $q_total);
$row_total = mysqli_fetch_assoc($r_total);
$totalSales = (float)$row_total["total_sales"];

// invoices count
$q_inv = "SELECT COUNT(*) AS invoices FROM sales";
$r_inv = mysqli_query($conn, $q_inv);
$row_inv = mysqli_fetch_assoc($r_inv);
$invoices = (int)$row_inv["invoices"];

// books count
$q_books = "SELECT COUNT(*) AS books FROM books";
$r_books = mysqli_query($conn, $q_books);
$row_books = mysqli_fetch_assoc($r_books);
$booksCount = (int)$row_books["books"];

// low stock alert (get setting or default 3)
$lowAlert = 3;
$q_set = "SELECT low_stock_alert FROM settings LIMIT 1";
$r_set = mysqli_query($conn, $q_set);
if($r_set && mysqli_num_rows($r_set) > 0){
  $row_set = mysqli_fetch_assoc($r_set);
  if(!empty($row_set["low_stock_alert"])) $lowAlert = (int)$row_set["low_stock_alert"];
}

$q_low = "SELECT COUNT(*) AS low_stock FROM books WHERE stock <= $lowAlert";
$r_low = mysqli_query($conn, $q_low);
$row_low = mysqli_fetch_assoc($r_low);
$lowStock = (int)$row_low["low_stock"];

// recent sales (last 5)
$q_recent = "
  SELECT s.invoice_no, s.created_at, s.total,
         IFNULL(c.name,'Walk-in') AS customer_name
  FROM sales s
  LEFT JOIN customers c ON c.id = s.customer_id
  ORDER BY s.id DESC
  LIMIT 5
";
$r_recent = mysqli_query($conn, $q_recent);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SalesBook Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{font-family:'Poppins', sans-serif;margin:0;background:#f4f6fb;}
.sidebar{position:fixed;top:0;left:0;width:250px;height:100vh;background:#111827;padding:25px 15px;color:white;}
.sidebar h4{font-weight:700;margin-bottom:30px;}
.sidebar a{display:block;color:#cbd5e1;text-decoration:none;padding:10px 15px;border-radius:10px;margin-bottom:8px;font-weight:500;}
.sidebar a:hover,.sidebar a.active{background:#1f2937;color:#fff;}
.main{margin-left:250px;padding:30px;}
.dashboard-card{background:white;border-radius:15px;padding:20px;box-shadow:0 5px 20px rgba(0,0,0,0.05);}
.stat-number{font-size:24px;font-weight:700;}
.topbar{background:white;padding:15px 25px;border-radius:15px;box-shadow:0 5px 20px rgba(0,0,0,0.05);margin-bottom:25px;}
.profile-top{display:flex;align-items:center;gap:12px;}
.avatar{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:700;background:#eef2ff;color:#4338ca;}
.profile-info{line-height:1.2;}
.small-muted{color:rgba(15,23,42,.65);}
</style>
</head>

<body>

<div class="sidebar">
  <h4>SalesBook</h4>
  <a href="dashboard.php" class="active">🏠 Dashboard</a>
  <a href="books.php">📚 Books</a>
  <a href="customers.php">👤 Customers</a>
  <a href="sales.php">🧾 Sales</a>
  <a href="reports.php">📊 Reports</a>
  <a href="settings.php">⚙ Settings</a>
</div>

<div class="main">

  <div class="topbar d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-0">Dashboard Overview</h5>
      <small class="text-muted">System Summary</small>
    </div>

    <div class="profile-top">
      <div class="avatar"><?= strtoupper(substr($adminName,0,1)) ?></div>
      <div class="profile-info">
        <div class="fw-semibold"><?= htmlspecialchars($adminName) ?></div>
        <div class="small small-muted"><?= htmlspecialchars($adminEmail) ?></div>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-4 mb-4">
    <div class="col-md-3">
      <div class="dashboard-card">
        <div class="text-muted">Total Sales</div>
        <div class="stat-number text-primary">$<?= number_format($totalSales,2) ?></div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="dashboard-card">
        <div class="text-muted">Invoices</div>
        <div class="stat-number text-success"><?= $invoices ?></div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="dashboard-card">
        <div class="text-muted">Books</div>
        <div class="stat-number text-dark"><?= $booksCount ?></div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="dashboard-card">
        <div class="text-muted">Low Stock (≤ <?= $lowAlert ?>)</div>
        <div class="stat-number text-danger"><?= $lowStock ?></div>
      </div>
    </div>
  </div>

  <!-- Recent Sales -->
  <div class="dashboard-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Recent Sales</h5>
      <a class="btn btn-success btn-sm" href="sales.php">+ Add Invoice</a>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>#Invoice</th>
            <th>Customer</th>
            <th>Date</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php if($r_recent && mysqli_num_rows($r_recent) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($r_recent)): ?>
              <tr>
                <td class="fw-semibold"><?= htmlspecialchars($row["invoice_no"]) ?></td>
                <td><?= htmlspecialchars($row["customer_name"]) ?></td>
                <td><?= htmlspecialchars($row["created_at"]) ?></td>
                <td>$<?= number_format((float)$row["total"], 2) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="text-center text-muted py-4">No sales yet.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="text-center text-muted small mt-4">
    © <?= date("Y"); ?> SalesBook Admin
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>