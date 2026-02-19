<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $customer_id = (int)($data["customer_id"] ?? 0);
    $items = $data["items"] ?? [];
    
    if (empty($items) || $customer_id <= 0) {
        echo json_encode(["status" => "error", "message" => "Customer and items are required"]);
        exit;
    }
    
    mysqli_begin_transaction($conn); // Start DB Transaction
    
    try {
        $invoiceNo = "INV-" . date("YmdHis") . rand(100, 999);
        $subtotal = 0;
        $itemDetails = [];
        
        foreach ($items as $item) {
            $bookId = (int)$item["book_id"];
            $qty = (int)$item["qty"];
            $price = (float)$item["price"];
            
            // Check stock and get cost
            $bookQ = "SELECT stock, cost FROM books WHERE id = $bookId FOR UPDATE";
            $bookR = mysqli_query($conn, $bookQ);
            $book = mysqli_fetch_assoc($bookR);
            
            if ($qty > $book["stock"]) {
                throw new Exception("Insufficient stock for Book ID $bookId");
            }
            
            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;
            $itemDetails[] = [
                "book_id" => $bookId,
                "qty" => $qty,
                "price" => $price,
                "cost" => (float)$book["cost"],
                "total" => $lineTotal
            ];
        }
        
        // Insert into sales table
        // Note: created_by is set to 1. Ensure user ID 1 exists in your 'users' table.
        $saleInsert = "INSERT INTO sales (invoice_no, customer_id, subtotal, total, paid, created_by)
                       VALUES ('$invoiceNo', $customer_id, $subtotal, $subtotal, $subtotal, 1)";
        
        if (!mysqli_query($conn, $saleInsert)) throw new Exception(mysqli_error($conn));
        $saleId = mysqli_insert_id($conn);
        
        foreach ($itemDetails as $it) {
            // Insert items
            $sqlItem = "INSERT INTO sale_items (sale_id, book_id, qty, price, cost, total)
                        VALUES ($saleId, {$it['book_id']}, {$it['qty']}, {$it['price']}, {$it['cost']}, {$it['total']})";
            mysqli_query($conn, $sqlItem);
            
            // Update stock
            mysqli_query($conn, "UPDATE books SET stock = stock - {$it['qty']} WHERE id = {$it['book_id']}");
        }
        
        mysqli_commit($conn); // Success
        echo json_encode(["status" => "success", "invoice_no" => $invoiceNo]);
        
    } catch (Exception $e) {
        mysqli_rollback($conn); // Fail
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}