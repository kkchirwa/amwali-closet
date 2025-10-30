<?php
session_start();
header('Content-Type: application/json');

// Include database connection
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'You must be logged in to cancel a booking.';
        echo json_encode($response);
        exit;
    }

    // Get booking ID from POST
    $booking_id = $_POST['booking_id'] ?? '';
    
    if (empty($booking_id)) {
        $response['message'] = 'Booking ID is required.';
        echo json_encode($response);
        exit;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if booking exists and belongs to the user
        $check_query = "SELECT id, status FROM bookings WHERE id = :booking_id AND user_id = :user_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':booking_id', $booking_id);
        $check_stmt->bindParam(':user_id', $_SESSION['user_id']);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() === 0) {
            $response['message'] = 'Booking not found or you do not have permission to cancel it.';
            echo json_encode($response);
            exit;
        }
        
        $booking = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if booking can be cancelled (only pending bookings can be cancelled)
        if ($booking['status'] !== 'pending') {
            $response['message'] = 'Only pending bookings can be cancelled.';
            echo json_encode($response);
            exit;
        }
        
        // Update booking status to cancelled
        $update_query = "UPDATE bookings SET status = 'cancelled', end_date = NOW() WHERE id = :booking_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':booking_id', $booking_id);
        
        if ($update_stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Booking cancelled successfully.';
            
            // Log the cancellation
            error_log("Booking {$booking_id} cancelled by user {$_SESSION['user_id']}");
        } else {
            
            $response['message'] = 'Failed to cancel booking. Please try again.';
        }
        
    } catch (PDOException $exception) {
        $response['message'] = 'Database error: ' . $exception->getMessage();
        error_log('Cancel Booking PDO Error: ' . $exception->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>