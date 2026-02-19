<?php
require_once __DIR__ . "/../config/db.php";

/* ===== Upload settings ===== */
$uploadDirFs = __DIR__ . "/../uploads/books/";
$uploadDirUrl = "../uploads/books/";
if (!is_dir($uploadDirFs)) {
  @mkdir($uploadDirFs, 0777, true);
}

$msg = "";

/* ===== DELETE ===== */
if (isset($_GET["delete"])) {
  $id = (int)$_GET["delete"];

  // delete image file (optional)
  $qimg = "SELECT image FROM books WHERE id=$id LIMIT 1";
  $rimg = mysqli_query($conn, $qimg);
  if ($rimg && mysqli_num_rows($rimg) == 1) {
    $imgRow = mysqli_fetch_assoc($rimg);
    if (!empty($imgRow["image"])) {
      $oldFs = __DIR__ . "/.." . $imgRow["image"]; // because stored as /system/uploads/...
      if (file_exists($oldFs)) @unlink($oldFs);
    }
  }

  $del = "DELETE FROM books WHERE id=$id";
  $res = mysqli_query($conn, $del);
  $msg = $res ? "Deleted successfully!" : "Delete failed!";
}

/* ===== INSERT / UPDATE ===== */
if (isset($_POST["save"])) {
  $id = (int)($_POST["id"] ?? 0);
  $category_id = (int)($_POST["category_id"] ?? 0);
  $title = mysqli_real_escape_string($conn, $_POST["title"] ?? "");
  $cost  = (float)($_POST["cost"] ?? 0);
  $price = (float)($_POST["price"] ?? 0);
  $stock = (int)($_POST["stock"] ?? 0);
  $barcode = mysqli_real_escape_string($conn, $_POST["barcode"] ?? "");
  $status = (int)($_POST["status"] ?? 1);

  // handle image upload (optional)
  $newImagePath = ""; // store like /system/uploads/books/xxx.jpg
  if (!empty($_FILES["image"]["name"])) {
    $tmp = $_FILES["image"]["tmp_name"];
    $err = $_FILES["image"]["error"];

    if ($err === 0) {
      $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
      $allow = ["jpg", "jpeg", "png", "webp"];
      if (in_array($ext, $allow)) {
        $newName = "book_" . time() . "_" . rand(1000, 9999) . "." . $ext;
        $destFs = $uploadDirFs . $newName;

        if (move_uploaded_file($tmp, $destFs)) {
          $newImagePath = $uploadDirUrl . $newName;
        } else {
          // Change this to see the error
          $msg = "Upload failed! Check if " . $uploadDirFs . " is writable.";
        }
      } else {
        $msg = "Only JPG/PNG/WEBP allowed!";
      }
    } else {
      $msg = "Upload error!";
    }
  }

  if ($msg === "") {
    if ($id == 0) {
      // INSERT
      $imgVal = ($newImagePath !== "") ? ("'" . mysqli_real_escape_string($conn, $newImagePath) . "'") : "NULL";
      $insert = "INSERT INTO books(category_id,title,cost,price,stock,barcode,status,image)
                 VALUES($category_id,'$title',$cost,$price,$stock,'$barcode',$status,$imgVal)";
      $res = mysqli_query($conn, $insert);
      $msg = $res ? "Added successfully!" : "Insert failed!";
    } else {
      // UPDATE
      // if new image uploaded -> update image; else keep old image
      $imgSet = "";
      if ($newImagePath !== "") {
        // delete old file
        $qold = "SELECT image FROM books WHERE id=$id LIMIT 1";
        $rold = mysqli_query($conn, $qold);
        if ($rold && mysqli_num_rows($rold) == 1) {
          $old = mysqli_fetch_assoc($rold);
          if (!empty($old["image"])) {
            $oldFs = __DIR__ . "/.." . $old["image"];
            if (file_exists($oldFs)) @unlink($oldFs);
          }
        }
        $safeNew = mysqli_real_escape_string($conn, $newImagePath);
        $imgSet = ", image='$safeNew'";
      }

      $update = "UPDATE books SET
                  category_id=$category_id,
                  title='$title',
                  cost=$cost,
                  price=$price,
                  stock=$stock,
                  barcode='$barcode',
                  status=$status
                  $imgSet
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
  $q = "SELECT * FROM books WHERE id=$id LIMIT 1";
  $r = mysqli_query($conn, $q);
  if ($r && mysqli_num_rows($r) == 1) $edit = mysqli_fetch_assoc($r);
}

/* ===== CATEGORY LIST ===== */
$catRes = mysqli_query($conn, "SELECT id,name FROM categories WHERE status=1 ORDER BY name ASC");

/* ===== SEARCH + PAGINATION ===== */
$search = mysqli_real_escape_string($conn, $_GET["q"] ?? "");
$where = "";
if ($search !== "") {
  $where = "WHERE b.title LIKE '%$search%' OR b.barcode LIKE '%$search%'";
}

$perPage = 8;
$page = (int)($_GET["page"] ?? 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) AS total
             FROM books b
             $where";
$countRes = mysqli_query($conn, $countSql);
$totalRows = 0;
if ($countRes) {
  $countRow = mysqli_fetch_assoc($countRes);
  $totalRows = (int)$countRow["total"];
}
$totalPages = ($totalRows > 0) ? (int)ceil($totalRows / $perPage) : 1;

$listSql = "
  SELECT b.*, c.name AS category_name
  FROM books b
  LEFT JOIN categories c ON c.id = b.category_id
  $where
  ORDER BY b.id DESC
  LIMIT $perPage OFFSET $offset
";
$listRes = mysqli_query($conn, $listSql);

/* ===== form values ===== */
$formId = $edit["id"] ?? 0;
$formCategory = $edit["category_id"] ?? 0;
$formTitle = $edit["title"] ?? "";
$formCost  = $edit["cost"] ?? "";
$formPrice = $edit["price"] ?? "";
$formStock = $edit["stock"] ?? "";
$formBarcode = $edit["barcode"] ?? "";
$formStatus = isset($edit["status"]) ? (int)$edit["status"] : 1;
$formImage = $edit["image"] ?? "";

// Page settings for layout
$pageTitle = "Books CRUD";
$activePage = "books";
$cssFile = "books";
$extraTitle = "Image upload • Cost/Profit • Pagination";

// Include header with sidebar and topbar
require_once __DIR__ . "/../layouts/header.php";
?>

<?php if ($msg): ?>
  <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Form -->
<div class="card-box mb-4">
  <h5 class="mb-3"><?= $formId ? "Edit Book #$formId" : "Add New Book" ?></h5>

  <form method="post" enctype="multipart/form-data" class="row g-2">
    <input type="hidden" name="id" value="<?= (int)$formId ?>">

    <div class="col-md-3">
      <label class="form-label fw-semibold">Category</label>
      <select name="category_id" class="form-select" required>
        <option value="">-- choose --</option>
        <?php if ($catRes): ?>
          <?php while ($c = mysqli_fetch_assoc($catRes)): ?>
            <option value="<?= (int)$c["id"] ?>" <?= ((int)$formCategory === (int)$c["id"]) ? "selected" : "" ?>>
              <?= htmlspecialchars($c["name"]) ?>
            </option>
          <?php endwhile; ?>
        <?php endif; ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label fw-semibold">Title</label>
      <input name="title" class="form-control" required value="<?= htmlspecialchars($formTitle) ?>">
    </div>

    <div class="col-md-2">
      <label class="form-label fw-semibold">Cost</label>
      <input name="cost" type="number" step="0.01" class="form-control" required value="<?= htmlspecialchars($formCost) ?>">
    </div>

    <div class="col-md-2">
      <label class="form-label fw-semibold">Price</label>
      <input name="price" type="number" step="0.01" class="form-control" required value="<?= htmlspecialchars($formPrice) ?>">
    </div>

    <div class="col-md-2">
      <label class="form-label fw-semibold">Stock</label>
      <input name="stock" type="number" class="form-control" required value="<?= htmlspecialchars($formStock) ?>">
    </div>

    <div class="col-md-3">
      <label class="form-label fw-semibold">Barcode</label>
      <input name="barcode" class="form-control" value="<?= htmlspecialchars($formBarcode) ?>">
    </div>

    <div class="col-md-3">
      <label class="form-label fw-semibold">Image</label>
      <input name="image" type="file" class="form-control" accept=".jpg,.jpeg,.png,.webp">
      <?php if ($formImage): ?>
        <div class="small text-muted mt-2">
          Current: <a href="<?= htmlspecialchars($formImage) ?>" target="_blank">view</a>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-md-2">
      <label class="form-label fw-semibold">Status</label>
      <select name="status" class="form-select">
        <option value="1" <?= $formStatus === 1 ? "selected" : "" ?>>Active</option>
        <option value="0" <?= $formStatus === 0 ? "selected" : "" ?>>Inactive</option>
      </select>
    </div>

    <div class="col-md-4 d-grid">
      <label class="form-label fw-semibold">&nbsp;</label>
      <button class="btn btn-primary" name="save" value="1">
        <?= $formId ? "Update Book" : "Add Book" ?>
      </button>
    </div>
  </form>
</div>

<!-- Search + Table -->
<div class="card-box">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h5 class="mb-0">Books List</h5>
    <form class="d-flex gap-2" method="get">
      <input class="form-control" name="q" placeholder="Search title/barcode..." value="<?= htmlspecialchars($_GET["q"] ?? "") ?>">
      <button class="btn btn-dark">Search</button>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th width="70">Img</th>
          <th>ID</th>
          <th>Category</th>
          <th>Title</th>
          <th class="text-end">Cost</th>
          <th class="text-end">Price</th>
          <th class="text-end">Profit</th>
          <th class="text-end">Stock</th>
          <th>Barcode</th>
          <th>Status</th>
          <th width="170">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($listRes && mysqli_num_rows($listRes) > 0): ?>
          <?php while ($b = mysqli_fetch_assoc($listRes)): ?>
            <?php
            $cost = (float)$b["cost"];
            $price = (float)$b["price"];
            $profit = $price - $cost;
            ?>
            <tr>
              <td>
                <?php if (!empty($b["image"])): ?>
                  <?php 
                    $imgPath = $b["image"];
                    // If path contains /system/, replace it with ../
                    if (strpos($imgPath, '/system/') !== false) {
                      $imgPath = str_replace('/system/', '../', $imgPath);
                    }
                  ?>
                  <img class="thumb" src="<?= htmlspecialchars($imgPath) ?>" alt="">
                <?php endif; ?>
              </td>
              <td><?= (int)$b["id"] ?></td>
              <td><?= htmlspecialchars($b["category_name"] ?? "-") ?></td>
              <td class="fw-semibold"><?= htmlspecialchars($b["title"]) ?></td>
              <td class="text-end">$<?= number_format($cost, 2) ?></td>
              <td class="text-end">$<?= number_format($price, 2) ?></td>
              <td class="text-end fw-semibold <?= ($profit >= 0) ? 'text-success' : 'text-danger' ?>">
                $<?= number_format($profit, 2) ?>
              </td>
              <td class="text-end"><?= (int)$b["stock"] ?></td>
              <td><?= htmlspecialchars($b["barcode"] ?? "") ?></td>
              <td>
                <?= ((int)$b["status"] === 1)
                  ? '<span class="badge bg-success">Active</span>'
                  : '<span class="badge bg-secondary">Inactive</span>' ?>
              </td>
              <td>
                <a class="btn btn-sm btn-warning" href="books.php?edit=<?= (int)$b["id"] ?>">Edit</a>
                <a class="btn btn-sm btn-danger"
                  onclick="return confirm('Delete this book?')"
                  href="books.php?delete=<?= (int)$b["id"] ?>">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="11" class="text-center text-muted py-4">No books found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted small">
      Total: <?= $totalRows ?> item(s) • Page <?= $page ?> / <?= $totalPages ?>
    </div>

    <nav>
      <ul class="pagination pagination-sm mb-0">
        <?php
        $qParam = urlencode($_GET["q"] ?? "");
        $prev = $page - 1;
        $next = $page + 1;
        ?>
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
          <a class="page-link" href="books.php?page=<?= $prev ?>&q=<?= $qParam ?>">Prev</a>
        </li>

        <?php
        // show limited page numbers (max 7)
        $start = max(1, $page - 3);
        $end = min($totalPages, $page + 3);
        for ($p = $start; $p <= $end; $p++):
        ?>
          <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
            <a class="page-link" href="books.php?page=<?= $p ?>&q=<?= $qParam ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>

        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
          <a class="page-link" href="books.php?page=<?= $next ?>&q=<?= $qParam ?>">Next</a>
        </li>
      </ul>
    </nav>
  </div>

</div>

<?php
// Include footer
require_once __DIR__ . "/../layouts/footer.php";
?>