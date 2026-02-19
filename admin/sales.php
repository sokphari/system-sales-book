<?php
require_once __DIR__ . "/../config/db.php";
session_start();

/* ===== cart in session =====
$_SESSION["cart"] = [
  book_id => ["id"=>..,"title"=>..,"price"=>..,"cost"=>..,"qty"=>..,"stock"=>..]
];
*/
if (!isset($_SESSION["cart"])) $_SESSION["cart"] = [];
$cart = &$_SESSION["cart"];

$msg = "";

/* ===== Helpers ===== */
function cart_subtotal($cart)
{
    $sum = 0;
    foreach ($cart as $it) {
        $sum += ((float)$it["price"] * (int)$it["qty"]);
    }
    return $sum;
}

/* ===== Actions ===== */
$action = $_POST["action"] ?? "";

// Add item
if ($action === "add") {
    $book_id = (int)($_POST["book_id"] ?? 0);

    $q = "SELECT id,title,price,cost,stock FROM books WHERE id=$book_id LIMIT 1";
    $r = mysqli_query($conn, $q);
    if ($r && mysqli_num_rows($r) == 1) {
        $b = mysqli_fetch_assoc($r);
        if ((int)$b["stock"] <= 0) {
            $msg = "Out of stock!";
        } else {
            if (isset($cart[$book_id])) {
                // add qty but not more than stock
                $newQty = (int)$cart[$book_id]["qty"] + 1;
                if ($newQty > (int)$b["stock"]) $newQty = (int)$b["stock"];
                $cart[$book_id]["qty"] = $newQty;
            } else {
                $cart[$book_id] = [
                    "id" => (int)$b["id"],
                    "title" => $b["title"],
                    "price" => (float)$b["price"],
                    "cost" => (float)$b["cost"],
                    "stock" => (int)$b["stock"],
                    "qty" => 1
                ];
            }
        }
    }
}

// Qty plus
if ($action === "plus") {
    $book_id = (int)($_POST["book_id"] ?? 0);
    if (isset($cart[$book_id])) {
        $q = "SELECT stock FROM books WHERE id=$book_id LIMIT 1";
        $r = mysqli_query($conn, $q);
        $stock = 0;
        if ($r && mysqli_num_rows($r) == 1) {
            $row = mysqli_fetch_assoc($r);
            $stock = (int)$row["stock"];
        }
        $newQty = (int)$cart[$book_id]["qty"] + 1;
        if ($newQty > $stock) $newQty = $stock;
        $cart[$book_id]["qty"] = $newQty;
        $cart[$book_id]["stock"] = $stock;
    }
}

// Qty minus
if ($action === "minus") {
    $book_id = (int)($_POST["book_id"] ?? 0);
    if (isset($cart[$book_id])) {
        $newQty = (int)$cart[$book_id]["qty"] - 1;
        if ($newQty <= 0) unset($cart[$book_id]);
        else $cart[$book_id]["qty"] = $newQty;
    }
}

// Remove item
if ($action === "remove") {
    $book_id = (int)($_POST["book_id"] ?? 0);
    if (isset($cart[$book_id])) unset($cart[$book_id]);
}

// Clear cart
if ($action === "clear") {
    $cart = [];
}

// Save invoice
if ($action === "save") {
    $customer_id = (int)($_POST["customer_id"] ?? 0);
    $discount = (float)($_POST["discount"] ?? 0);
    $paid = (float)($_POST["paid"] ?? 0);

    if (count($cart) == 0) {
        $msg = "Cart is empty!";
    } else {
        $subtotal = cart_subtotal($cart);
        if ($discount < 0) $discount = 0;
        if ($discount > $subtotal) $discount = $subtotal;

        $total = $subtotal - $discount;
        $change = $paid - $total;
        if ($change < 0) $change = 0;

        // invoice no (simple)
        $invoice_no = "INV-" . date("Ymd-His");

        // created_by (later use session user id)
        $created_by = 1;

        mysqli_query($conn, "START TRANSACTION");

        // insert sales
        $customerVal = ($customer_id > 0) ? $customer_id : "NULL";
        $insertSale = "INSERT INTO sales(invoice_no,customer_id,subtotal,discount,total,paid,change_amount,created_by,created_at)
                   VALUES('$invoice_no',$customerVal,$subtotal,$discount,$total,$paid,$change,$created_by,NOW())";
        $resSale = mysqli_query($conn, $insertSale);

        if (!$resSale) {
            mysqli_query($conn, "ROLLBACK");
            $msg = "Save failed (sales)!";
        } else {
            $sale_id = mysqli_insert_id($conn);
            $ok = true;

            foreach ($cart as $it) {
                $book_id = (int)$it["id"];
                $qty = (int)$it["qty"];

                // check stock again
                $rs = mysqli_query($conn, "SELECT stock,price,cost FROM books WHERE id=$book_id LIMIT 1");
                if (!$rs || mysqli_num_rows($rs) != 1) {
                    $ok = false;
                    break;
                }

                $b = mysqli_fetch_assoc($rs);
                $stockNow = (int)$b["stock"];
                if ($qty > $stockNow) {
                    $ok = false;
                    $msg = "Stock not enough for " . $it["title"];
                    break;
                }

                $price = (float)$b["price"];
                $cost  = (float)$b["cost"];
                $lineTotal = $price * $qty;

                $insertItem = "INSERT INTO sale_items(sale_id,book_id,qty,price,cost,total)
                       VALUES($sale_id,$book_id,$qty,$price,$cost,$lineTotal)";
                $resItem = mysqli_query($conn, $insertItem);
                if (!$resItem) {
                    $ok = false;
                    break;
                }

                // update stock
                $newStock = $stockNow - $qty;
                $updStock = "UPDATE books SET stock=$newStock WHERE id=$book_id";
                $resUpd = mysqli_query($conn, $updStock);
                if (!$resUpd) {
                    $ok = false;
                    break;
                }
            }

            if (!$ok) {
                mysqli_query($conn, "ROLLBACK");
                if ($msg == "") $msg = "Save failed (items/stock)!";
            } else {
                mysqli_query($conn, "COMMIT");
                $cart = [];
                header("Location: reports.php?view=$sale_id");
                exit;
            }
        }
    }
}

/* ===== Load customers for dropdown ===== */
$custRes = mysqli_query($conn, "SELECT id,name FROM customers ORDER BY name ASC");

/* ===== Load books list (search) ===== */
$q = mysqli_real_escape_string($conn, $_GET["q"] ?? "");
$where = "";
if ($q !== "") {
    $where = "WHERE title LIKE '%$q%' OR barcode LIKE '%$q%'";
}
$booksRes = mysqli_query($conn, "SELECT id,title,price,stock FROM books $where ORDER BY id DESC LIMIT 30");

/* ===== Totals for UI ===== */
$subtotal = cart_subtotal($cart);
$discount_ui = (float)($_POST["discount"] ?? 0);
$paid_ui = (float)($_POST["paid"] ?? 0);
if ($discount_ui < 0) $discount_ui = 0;
if ($discount_ui > $subtotal) $discount_ui = $subtotal;
$total = $subtotal - $discount_ui;
$change = $paid_ui - $total;
if ($change < 0) $change = 0;
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales / POS - SalesBook Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/assets/css/sales.css">
    
</head>

<body>

    <div class="sidebar">
        <h4>SalesBook</h4>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="books.php">📚 Books</a>
        <a href="customers.php">👤 Customers</a>
        <a href="sales.php" class="active">🧾 Sales / POS</a>
        <a href="reports.php">📊 Reports</a>
        <a href="settings.php">⚙ Settings</a>
    </div>

    <div class="main">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">Sales / Invoice (POS)</h4>
                <small class="text-muted">Simple PHP logic with session cart</small>
            </div>
            <form method="post" class="d-flex gap-2">
                <input type="hidden" name="action" value="clear">
                <button class="btn btn-outline-danger">Clear Cart</button>
            </form>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="pos-wrap">

            <!-- LEFT: Products -->
            <div class="card-box">
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="mb-1">Books List</h5>
                        <div class="small-muted">Add items from database</div>
                    </div>

                    <form class="d-flex gap-2" method="get">
                        <input name="q" class="form-control" style="min-width:240px" placeholder="Search title/barcode..." value="<?= htmlspecialchars($_GET["q"] ?? "") ?>">
                        <button class="btn btn-dark">Search</button>
                    </form>
                </div>

                <div class="row g-3">
                    <?php if ($booksRes && mysqli_num_rows($booksRes) > 0): ?>
                        <?php while ($b = mysqli_fetch_assoc($booksRes)): ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="product-item">
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($b["title"]) ?></div>
                                        <div class="small text-muted">$<?= number_format((float)$b["price"], 2) ?> • Stock: <?= (int)$b["stock"] ?></div>
                                    </div>
                                    <form method="post">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="book_id" value="<?= (int)$b["id"] ?>">
                                        <button class="btn btn-sm btn-success" <?= ((int)$b["stock"] <= 0) ? "disabled" : ""; ?>>Add</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-muted">No books found.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT: Cart / Invoice -->
            <!-- RIGHT: Cart / Invoice -->
            <div class="card-box">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1">Invoice</h5>
                        <div class="small-muted">Cart & totals</div>
                    </div>

                    <!-- Clear (separate form) -->
                    <form method="post" class="m-0">
                        <input type="hidden" name="action" value="clear">
                        <button class="btn btn-sm btn-outline-danger">Clear</button>
                    </form>
                </div>

                <!-- Customer (NOT inside save form) -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Customer</label>
                    <select name="customer_id" form="frmSave" class="form-select" style="border-radius:12px;">
                        <option value="0">Walk-in Customer</option>
                        <?php if ($custRes): ?>
                            <?php while ($c = mysqli_fetch_assoc($custRes)): ?>
                                <option value="<?= (int)$c["id"] ?>"><?= htmlspecialchars($c["name"]) ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Cart Items (each button uses its own form) -->
                <div class="vstack gap-2 mb-3" style="max-height:280px; overflow:auto;">
                    <?php if (count($cart) > 0): ?>
                        <?php foreach ($cart as $it): ?>
                            <?php $lineTotal = (float)$it["price"] * (int)$it["qty"]; ?>
                            <div class="cart-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($it["title"]) ?></div>
                                        <div class="small text-muted">$<?= number_format((float)$it["price"], 2) ?> • Stock: <?= (int)$it["stock"] ?></div>
                                    </div>

                                    <form method="post" class="m-0">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="book_id" value="<?= (int)$it["id"] ?>">
                                        <button class="btn btn-sm btn-outline-danger">✕</button>
                                    </form>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <form method="post" class="m-0">
                                            <input type="hidden" name="action" value="minus">
                                            <input type="hidden" name="book_id" value="<?= (int)$it["id"] ?>">
                                            <button class="btn btn-light qty-btn">−</button>
                                        </form>

                                        <span class="fw-semibold"><?= (int)$it["qty"] ?></span>

                                        <form method="post" class="m-0">
                                            <input type="hidden" name="action" value="plus">
                                            <input type="hidden" name="book_id" value="<?= (int)$it["id"] ?>">
                                            <button class="btn btn-light qty-btn">+</button>
                                        </form>
                                    </div>

                                    <div class="fw-bold">$<?= number_format($lineTotal, 2) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-muted">Cart is empty.</div>
                    <?php endif; ?>
                </div>

                <!-- Discount & Paid (fields belong to Save form using form="frmSave") -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Discount</label>
                        <input name="discount" form="frmSave" type="number" step="0.01" class="form-control"
                            value="<?= htmlspecialchars((string)$discount_ui) ?>" style="border-radius:12px;">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Paid</label>
                        <input name="paid" form="frmSave" type="number" step="0.01" class="form-control"
                            value="<?= htmlspecialchars((string)$paid_ui) ?>" style="border-radius:12px;">
                    </div>
                </div>

                <!-- Totals -->
                <div class="total-box mb-3">
                    <div class="d-flex justify-content-between">
                        <div class="opacity-75">Subtotal</div>
                        <div class="fw-semibold">$<?= number_format($subtotal, 2) ?></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <div class="opacity-75">Discount</div>
                        <div class="fw-semibold">-$<?= number_format($discount_ui, 2) ?></div>
                    </div>
                    <hr class="border-light opacity-25 my-2">
                    <div class="d-flex justify-content-between">
                        <div class="fw-bold">Grand Total</div>
                        <div class="fw-bold fs-5">$<?= number_format($total, 2) ?></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <div class="opacity-75">Change</div>
                        <div class="fw-semibold">$<?= number_format($change, 2) ?></div>
                    </div>
                </div>

                <!-- Save form (only one form) -->
                <form method="post" id="frmSave">
                    <input type="hidden" name="action" value="save">
                    <div class="d-grid gap-2">
                        <button class="btn btn-success btn-lg" <?= (count($cart) == 0) ? "disabled" : ""; ?>>Save Invoice</button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>