<?php
session_start();
header('Content-Type: application/json');

// Include database connection
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($email) || empty($password)) {
        $response['message'] = 'Email and password are required.';
        echo json_encode($response);
        exit;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        // Check if user exists
        $query = "SELECT id, name, email, password, role FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                $response['success'] = true;
                $response['message'] = 'Login successful! Redirecting...';
            } else {
                $response['message'] = 'Invalid email or password.';
            }
        } else {
            $response['message'] = 'Invalid email or password.';
        }

    } catch (PDOException $exception) {
        $response['message'] = 'Database error: ' . $exception->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>