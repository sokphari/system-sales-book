<?php
require_once __DIR__ . "/../config/db.php";

$msg = "";

/* ===== DELETE ===== */
if (isset($_GET["delete"])) {
  $id = (int)$_GET["delete"];
  $del = "DELETE FROM customers WHERE id=$id";
  $res = mysqli_query($conn, $del);
  $msg = $res ? "Deleted successfully!" : "Delete failed!";
}

/* ===== INSERT / UPDATE ===== */
if (isset($_POST["save"])) {
  $id = (int)($_POST["id"] ?? 0);

  $name = mysqli_real_escape_string($conn, $_POST["name"] ?? "");
  $phone = mysqli_real_escape_string($conn, $_POST["phone"] ?? "");
  $address = mysqli_real_escape_string($conn, $_POST["address"] ?? "");
  $email = mysqli_real_escape_string($conn, $_POST["email"] ?? "");

  if ($name == "") {
    $msg = "Name is required!";
  } else {
    if ($id == 0) {
      $insert = "INSERT INTO customers(name,phone,address,email)
                 VALUES('$name','$phone','$address','$email')";
      $res = mysqli_query($conn, $insert);
      $msg = $res ? "Added successfully!" : "Insert failed!";
    } else {
      $update = "UPDATE customers SET
                  name='$name',
                  phone='$phone',
                  address='$address',
                  email='$email'
                WHERE id=$id";
      $res = mysqli_query($conn, $update);
      $msg = $res ? "Updated successfully!" : "Update failed!";
    }
  }
}

/* ===== EDIT LOAD ===== */
$edit = null;
if (isset($_GET["edit"])) {
  $id = (int)$_GET["edit"];
  $q = "SELECT * FROM customers WHERE id=$id LIMIT 1";
  $r = mysqli_query($conn, $q);
  if ($r && mysqli_num_rows($r) == 1) $edit = mysqli_fetch_assoc($r);
}

/* ===== SEARCH + PAGINATION ===== */
$search = mysqli_real_escape_string($conn, $_GET["q"] ?? "");
$where = "";
if ($search !== "") {
  $where = "WHERE name LIKE '%$search%' OR phone LIKE '%$search%' OR email LIKE '%$search%'";
}

$perPage = 8;
$page = (int)($_GET["page"] ?? 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) AS total FROM customers $where";
$countRes = mysqli_query($conn, $countSql);
$totalRows = 0;
if ($countRes) {
  $countRow = mysqli_fetch_assoc($countRes);
  $totalRows = (int)$countRow["total"];
}
$totalPages = ($totalRows > 0) ? (int)ceil($totalRows / $perPage) : 1;

$listSql = "SELECT * FROM customers $where ORDER BY id DESC LIMIT $perPage OFFSET $offset";
$listRes = mysqli_query($conn, $listSql);

/* ===== form values ===== */
$formId = $edit["id"] ?? 0;
$formName = $edit["name"] ?? "";
$formPhone = $edit["phone"] ?? "";
$formAddress = $edit["address"] ?? "";
$formEmail = $edit["email"] ?? "";
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Customers - SalesBook Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../public/assets/css/customers.css">

</head>
<body>

<div class="sidebar">
  <h4>SalesBook</h4>
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="books.php">📚 Books</a>
  <a href="customers.php" class="active">👤 Customers</a>
  <a href="sales.php">🧾 Sales</a>
  <a href="reports.php">📊 Reports</a>
  <a href="settings.php">⚙ Settings</a>
</div>

<div class="main">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0">Customers CRUD</h4>
      <small class="text-muted">Add, edit, delete, search + pagination</small>
    </div>
    <a class="btn btn-outline-dark" href="customers.php">Reset</a>
  </div>

  <?php if($msg): ?>
    <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- Form -->
  <div class="card-box mb-4">
    <h5 class="mb-3"><?= $formId ? "Edit Customer #$formId" : "Add New Customer" ?></h5>

    <form method="post" class="row g-2">
      <input type="hidden" name="id" value="<?= (int)$formId ?>">

      <div class="col-md-3">
        <label class="form-label fw-semibold">Name *</label>
        <input name="name" class="form-control" required value="<?= htmlspecialchars($formName) ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label fw-semibold">Phone</label>
        <input name="phone" class="form-control" value="<?= htmlspecialchars($formPhone) ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label fw-semibold">Email</label>
        <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($formEmail) ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label fw-semibold">Address</label>
        <input name="address" class="form-control" value="<?= htmlspecialchars($formAddress) ?>">
      </div>

      <div class="col-md-12 d-grid">
        <button class="btn btn-primary" name="save" value="1">
          <?= $formId ? "Update Customer" : "Add Customer" ?>
        </button>
      </div>
    </form>
  </div>

  <!-- Search + Table -->
  <div class="card-box">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <h5 class="mb-0">Customer List</h5>
      <form class="d-flex gap-2" method="get">
        <input class="form-control" name="q" placeholder="Search name/phone/email..." value="<?= htmlspecialchars($_GET["q"] ?? "") ?>">
        <button class="btn btn-dark">Search</button>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Address</th>
            <th width="170">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if($listRes && mysqli_num_rows($listRes) > 0): ?>
            <?php while($c = mysqli_fetch_assoc($listRes)): ?>
              <tr>
                <td><?= (int)$c["id"] ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($c["name"]) ?></td>
                <td><?= htmlspecialchars($c["phone"] ?? "") ?></td>
                <td><?= htmlspecialchars($c["email"] ?? "") ?></td>
                <td><?= htmlspecialchars($c["address"] ?? "") ?></td>
                <td>
                  <a class="btn btn-sm btn-warning" href="customers.php?edit=<?= (int)$c["id"] ?>">Edit</a>
                  <a class="btn btn-sm btn-danger"
                     onclick="return confirm('Delete this customer?')"
                     href="customers.php?delete=<?= (int)$c["id"] ?>">Delete</a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No customers found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-between align-items-center mt-3">
      <div class="text-muted small">
        Total: <?= $totalRows ?> customer(s) • Page <?= $page ?> / <?= $totalPages ?>
      </div>

      <nav>
        <ul class="pagination pagination-sm mb-0">
          <?php
            $qParam = urlencode($_GET["q"] ?? "");
            $prev = $page - 1;
            $next = $page + 1;
          ?>
          <li class="page-item <?= ($page<=1)?'disabled':'' ?>">
            <a class="page-link" href="customers.php?page=<?= $prev ?>&q=<?= $qParam ?>">Prev</a>
          </li>

          <?php
          $start = max(1, $page - 3);
          $end = min($totalPages, $page + 3);
          for($p=$start; $p<=$end; $p++):
          ?>
            <li class="page-item <?= ($p==$page)?'active':'' ?>">
              <a class="page-link" href="customers.php?page=<?= $p ?>&q=<?= $qParam ?>"><?= $p ?></a>
            </li>
          <?php endfor; ?>

          <li class="page-item <?= ($page>=$totalPages)?'disabled':'' ?>">
            <a class="page-link" href="customers.php?page=<?= $next ?>&q=<?= $qParam ?>">Next</a>
          </li>
        </ul>
      </nav>
    </div>

  </div>

  <div class="text-center text-muted small mt-4">
    © <?= date("Y"); ?> SalesBook Admin
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>