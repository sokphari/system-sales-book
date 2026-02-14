<?php
require_once __DIR__ . "/../config/db.php";

function money($n){ return number_format((float)$n, 2); }

/* ===== Shop settings ===== */
$shop_name = "SalesBook Store";
$shop_phone = "";
$shop_address = "";
$shop_currency = "USD";

$set = mysqli_query($conn, "SELECT shop_name,phone,address,currency FROM settings LIMIT 1");
if ($set && mysqli_num_rows($set)==1) {
  $s = mysqli_fetch_assoc($set);
  if(!empty($s["shop_name"])) $shop_name = $s["shop_name"];
  $shop_phone = $s["phone"] ?? "";
  $shop_address = $s["address"] ?? "";
  $shop_currency = $s["currency"] ?? "USD";
}

/* ===== View invoice mode ===== */
$view_id = isset($_GET["view"]) ? (int)$_GET["view"] : 0;

$invoice = null;
$itemsRes = null;

if ($view_id > 0) {
  $qSale = "
    SELECT s.*, IFNULL(c.name,'Walk-in Customer') AS customer_name
    FROM sales s
    LEFT JOIN customers c ON c.id=s.customer_id
    WHERE s.id=$view_id
    LIMIT 1
  ";
  $rSale = mysqli_query($conn, $qSale);
  if ($rSale && mysqli_num_rows($rSale)==1) {
    $invoice = mysqli_fetch_assoc($rSale);

    $qItems = "
      SELECT si.*, b.title
      FROM sale_items si
      LEFT JOIN books b ON b.id=si.book_id
      WHERE si.sale_id=".(int)$invoice["id"]."
      ORDER BY si.id ASC
    ";
    $itemsRes = mysqli_query($conn, $qItems);
  }
}

/* ===== Report list (filters) ===== */
$from = mysqli_real_escape_string($conn, $_GET["from"] ?? "");
$to   = mysqli_real_escape_string($conn, $_GET["to"] ?? "");
$where = "WHERE 1=1";

if ($from !== "") $where .= " AND DATE(s.created_at) >= '$from'";
if ($to !== "")   $where .= " AND DATE(s.created_at) <= '$to'";

$listSql = "
  SELECT s.id, s.invoice_no, s.created_at, s.total, s.discount,
         IFNULL(c.name,'Walk-in') AS customer_name
  FROM sales s
  LEFT JOIN customers c ON c.id=s.customer_id
  $where
  ORDER BY s.id DESC
  LIMIT 100
";
$listRes = mysqli_query($conn, $listSql);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reports - SalesBook Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{font-family:'Poppins',sans-serif;margin:0;background:#f4f6fb;}
.sidebar{position:fixed;top:0;left:0;width:250px;height:100vh;background:#111827;padding:25px 15px;color:white;}
.sidebar h4{font-weight:700;margin-bottom:30px;}
.sidebar a{display:block;color:#cbd5e1;text-decoration:none;padding:10px 15px;border-radius:10px;margin-bottom:8px;font-weight:500;}
.sidebar a:hover,.sidebar a.active{background:#1f2937;color:#fff;}
.main{margin-left:250px;padding:30px;}
.card-box{background:white;border-radius:15px;padding:20px;box-shadow:0 5px 20px rgba(0,0,0,0.05);}
.muted{color:rgba(15,23,42,.65);}
hr.dash{border-top:1px dashed #cbd5e1;}
/* Print only invoice area */
@media print{
  body{background:#fff;}
  .no-print{display:none !important;}
  .sidebar{display:none !important;}
  .main{margin:0;padding:0;}
  .card-box{box-shadow:none;border-radius:0;}
}
</style>
</head>
<body>

<div class="sidebar no-print">
  <h4>SalesBook</h4>
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="books.php">📚 Books</a>
  <a href="customers.php">👤 Customers</a>
  <a href="sales.php">🧾 Sales / POS</a>
  <a href="reports.php" class="active">📊 Reports</a>
  <a href="settings.php">⚙ Settings</a>
</div>

<div class="main">

  <div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
      <h4 class="mb-0">Reports</h4>
      <small class="text-muted">List + View/Print invoice (same page)</small>
    </div>
    <a class="btn btn-outline-dark" href="reports.php">Reset</a>
  </div>

  <!-- Invoice View -->
  <?php if($view_id>0): ?>
    <div class="card-box mb-4">
      <?php if(!$invoice): ?>
        <div class="alert alert-warning mb-0">Invoice not found.</div>
      <?php else: ?>
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h4 class="mb-0"><?= htmlspecialchars($shop_name) ?></h4>
            <div class="muted small">
              <?= htmlspecialchars($shop_address) ?><br>
              <?= htmlspecialchars($shop_phone) ?>
            </div>
          </div>

          <div class="text-end">
            <div class="badge bg-dark">INVOICE</div>
            <div class="fw-bold mt-2"><?= htmlspecialchars($invoice["invoice_no"]) ?></div>
            <div class="muted small">Date: <?= htmlspecialchars($invoice["created_at"]) ?></div>
          </div>
        </div>

        <hr class="dash my-3">

        <div class="row g-2">
          <div class="col-md-6">
            <div class="muted small">BILL TO</div>
            <div class="fw-semibold"><?= htmlspecialchars($invoice["customer_name"]) ?></div>
          </div>
          <div class="col-md-6 text-md-end">
            <div class="muted small">PAYMENT</div>
            <div class="small">Paid: <span class="fw-semibold"><?= money($invoice["paid"]) ?> <?= htmlspecialchars($shop_currency) ?></span></div>
            <div class="small">Change: <span class="fw-semibold"><?= money($invoice["change_amount"]) ?> <?= htmlspecialchars($shop_currency) ?></span></div>
          </div>
        </div>

        <hr class="dash my-3">

        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th>Item</th>
                <th class="text-end" width="90">Price</th>
                <th class="text-end" width="70">Qty</th>
                <th class="text-end" width="110">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php if($itemsRes && mysqli_num_rows($itemsRes)>0): ?>
                <?php while($it = mysqli_fetch_assoc($itemsRes)): ?>
                  <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($it["title"] ?? "Item") ?></td>
                    <td class="text-end"><?= money($it["price"]) ?></td>
                    <td class="text-end"><?= (int)$it["qty"] ?></td>
                    <td class="text-end fw-semibold"><?= money($it["total"]) ?></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="4" class="text-center muted py-4">No items</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <hr class="dash my-3">

        <div class="row">
          <div class="col-md-6">
            <div class="muted small">NOTE</div>
            <div class="small muted">Thank you for your purchase!</div>
          </div>
          <div class="col-md-6">
            <div class="d-flex justify-content-between">
              <div class="muted">Subtotal</div>
              <div class="fw-semibold"><?= money($invoice["subtotal"]) ?> <?= htmlspecialchars($shop_currency) ?></div>
            </div>
            <div class="d-flex justify-content-between mt-1">
              <div class="muted">Discount</div>
              <div class="fw-semibold">-<?= money($invoice["discount"]) ?> <?= htmlspecialchars($shop_currency) ?></div>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between">
              <div class="fw-bold fs-5">Grand Total</div>
              <div class="fw-bold fs-5"><?= money($invoice["total"]) ?> <?= htmlspecialchars($shop_currency) ?></div>
            </div>
          </div>
        </div>

        <div class="no-print d-flex justify-content-between mt-4">
          <a href="reports.php" class="btn btn-outline-dark">← Back to Report</a>
          <button onclick="window.print()" class="btn btn-primary">Print</button>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Filters -->
  <div class="card-box mb-4 no-print">
    <form class="row g-2 align-items-end" method="get">
      <div class="col-md-3">
        <label class="form-label fw-semibold">From</label>
        <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($_GET["from"] ?? "") ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold">To</label>
        <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($_GET["to"] ?? "") ?>">
      </div>
      <div class="col-md-3 d-grid">
        <button class="btn btn-dark">Filter</button>
      </div>
      <div class="col-md-3 d-grid">
        <a class="btn btn-outline-secondary" href="reports.php">Clear</a>
      </div>
    </form>
  </div>

  <!-- Report List -->
  <div class="card-box no-print">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Invoices</h5>
      <div class="text-muted small">Showing up to 100 records</div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Invoice</th>
            <th>Customer</th>
            <th>Date</th>
            <th class="text-end">Discount</th>
            <th class="text-end">Total</th>
            <th width="140" class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if($listRes && mysqli_num_rows($listRes)>0): ?>
            <?php while($r = mysqli_fetch_assoc($listRes)): ?>
              <tr>
                <td><?= (int)$r["id"] ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($r["invoice_no"]) ?></td>
                <td><?= htmlspecialchars($r["customer_name"]) ?></td>
                <td><?= htmlspecialchars($r["created_at"]) ?></td>
                <td class="text-end">$<?= money($r["discount"]) ?></td>
                <td class="text-end fw-semibold">$<?= money($r["total"]) ?></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-primary" href="reports.php?view=<?= (int)$r["id"] ?>">View/Print</a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No invoices found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>