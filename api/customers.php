<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/db.php";

// GET: Load all customers
if ($_SERVER["REQUEST_METHOD"] === "GET") {
  $query = "SELECT id, name FROM customers ORDER BY name ASC";
  $result = mysqli_query($conn, $query);
  
  $customers = [];
  if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
      $customers[] = $row;
    }
  }
  
  echo json_encode($customers);
  exit;
}

// POST: Create new customer
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $data = json_decode(file_get_contents("php://input"), true);
  $name = mysqli_real_escape_string($conn, $data["name"] ?? "");
  $phone = mysqli_real_escape_string($conn, $data["phone"] ?? "");
  $address = mysqli_real_escape_string($conn, $data["address"] ?? "");
  $email = mysqli_real_escape_string($conn, $data["email"] ?? "");
  
  if (empty($name)) {
    echo json_encode(["status" => "error", "message" => "Name is required"]);
    exit;
  }
  
  $insert = "INSERT INTO customers (name, phone, address, email) 
             VALUES ('$name', '$phone', '$address', '$email')";
  
  if (mysqli_query($conn, $insert)) {
    $newId = mysqli_insert_id($conn);
    echo json_encode(["status" => "success", "id" => $newId, "name" => $name]);
  } else {
    echo json_encode(["status" => "error", "message" => "Insert failed"]);
  }
  exit;
}

echo json_encode(["error" => "Invalid request"]);
?>
