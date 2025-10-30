<?php
session_start();
header('Content-Type: application/json');

// Include database connection
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $response['message'] = 'All fields are required.';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address.';
        echo json_encode($response);
        exit;
    }

    if (strlen($password) < 8) {
        $response['message'] = 'Password must be at least 8 characters long.';
        echo json_encode($response);
        exit;
    }

    if ($password !== $confirm_password) {
        $response['message'] = 'Passwords do not match.';
        echo json_encode($response);
        exit;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = :email";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            $response['message'] = 'Email address is already registered.';
            echo json_encode($response);
            exit;
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user - FIXED: Use named parameters
        $insert_query = "INSERT INTO users (name, email, password, role) 
                        VALUES (:name, :email, :password, 'customer')";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':name', $name);
        $insert_stmt->bindParam(':email', $email);
        $insert_stmt->bindParam(':password', $hashed_password);

        if ($insert_stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Registration successful! You can now login to your account.';
        } else {
            $response['message'] = 'Registration failed. Please try again.';
        }

    } catch (PDOException $exception) {
        $response['message'] = 'Database error: ' . $exception->getMessage();
        // Log the full error for debugging
        error_log('Registration PDO Error: ' . $exception->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?> 