<?php
session_start();
header('Content-Type: application/json');

// Include database connection
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'You must be logged in to make a booking.';
        echo json_encode($response);
        exit;
    }

    // Get cart data from POST
    $cart_data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($cart_data) || !is_array($cart_data)) {
        $response['message'] = 'No items in cart.';
        echo json_encode($response);
        exit;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Start transaction
        $db->beginTransaction();
        
        $success_count = 0;
        
        foreach ($cart_data as $item) {
            // Calculate total amount
            $total_amount = $item['price'] * $item['rentalDays'];
            $service_fee = $total_amount * 0.10;
            $insurance_fee = $total_amount * 0.05;
            $final_total = $total_amount + $service_fee + $insurance_fee;
            
            // Insert booking
            $query = "INSERT INTO bookings (user_id, product_id, rental_days, total_amount, status, start_date) 
                     VALUES (:user_id, :product_id, :rental_days, :total_amount, 'pending', DATE_ADD(NOW(), INTERVAL 1 DAY))";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->bindParam(':product_id', $item['id']);
            $stmt->bindParam(':rental_days', $item['rentalDays']);
            $stmt->bindParam(':total_amount', $final_total);
            
            if ($stmt->execute()) {
                $success_count++;
                
                // Update product stock (optional - you might want to reserve the item)
                // $update_query = "UPDATE products SET stock_quantity = stock_quantity - 1 WHERE id = :product_id";
                // $update_stmt = $db->prepare($update_query);
                // $update_stmt->bindParam(':product_id', $item['id']);
                // $update_stmt->execute();
            }
        }
        
        // Commit transaction
        $db->commit();
        
        if ($success_count > 0) {
            $response['success'] = true;
            $response['message'] = "Booking confirmed for {$success_count} item(s)!";
        } else {
            $response['message'] = 'Failed to create bookings.';
        }
        
    } catch (PDOException $exception) {
        // Rollback transaction on error
        $db->rollBack();
        $response['message'] = 'Database error: ' . $exception->getMessage();
        error_log('Bookings PDO Error: ' . $exception->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>