<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/db.php";

// GET: Load books with optional search
if ($_SERVER["REQUEST_METHOD"] === "GET") {
  $q = mysqli_real_escape_string($conn, $_GET["q"] ?? "");
  
  $where = "WHERE b.status = 1";
  if (!empty($q)) {
    $where .= " AND (b.title LIKE '%$q%' OR b.barcode LIKE '%$q%')";
  }
  
  $query = "SELECT b.id, b.title, b.price, b.stock, b.image, c.name AS category 
            FROM books b
            LEFT JOIN categories c ON c.id = b.category_id
            $where
            ORDER BY b.title ASC";
  
  $result = mysqli_query($conn, $query);
  
  if (!$result) {
    echo json_encode(["error" => "Query failed: " . mysqli_error($conn)]);
    exit;
  }
  
  $books = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $books[] = $row;
  }
  
  echo json_encode($books);
  exit;
}

// POST: Add new book
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $data = json_decode(file_get_contents("php://input"), true);
  
  $title = mysqli_real_escape_string($conn, $data["title"] ?? "");
  $price = (float)($data["price"] ?? 0);
  $stock = (int)($data["stock"] ?? 0);
  
  if (empty($title) || $price <= 0) {
    echo json_encode(["status" => "error", "message" => "Title and price are required"]);
    exit;
  }
  
  $insert = "INSERT INTO books (title, price, stock, status) 
             VALUES ('$title', $price, $stock, 1)";
  
  if (mysqli_query($conn, $insert)) {
    $newId = mysqli_insert_id($conn);
    echo json_encode(["status" => "success", "id" => $newId, "title" => $title, "price" => $price]);
  } else {
    echo json_encode(["status" => "error", "message" => "Insert failed"]);
  }
  exit;
}

echo json_encode(["error" => "Invalid request"]);
?>
