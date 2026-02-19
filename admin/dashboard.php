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

// Page settings for layout
$pageTitle = "Dashboard Overview";
$activePage = "dashboard";
$cssFile = "dashboard";
$extraTitle = "System Summary";

// Include header with sidebar and topbar
require_once __DIR__ . "/../layouts/header.php";
?>

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

<?php
// Include footer
require_once __DIR__ . "/../layouts/footer.php";
?>
