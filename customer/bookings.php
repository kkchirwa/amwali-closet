<?php
include '../includes/functions.php';
include '../includes/header.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: /amwali-closet/');
    exit();
}

// Include database connection
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get user's bookings
$user_id = $_SESSION['user_id'];
$query = "SELECT b.*, p.name as product_name, p.image_url 
          FROM bookings b 
          JOIN products p ON b.product_id = p.id 
          WHERE b.user_id = :user_id 
          ORDER BY b.booking_date DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bookings-page">
    <div class="container">
        <div class="page-header">
            <h1>My Bookings</h1>
            <p>Manage your clothing rentals</p>
        </div>

        <div class="bookings-list">
            <?php if (count($bookings) > 0): ?>
                <?php foreach($bookings as $booking): ?>
                    <div class="booking-card status-<?php echo $booking['status']; ?>">
                        <div class="booking-image">
                            <img src="<?php echo htmlspecialchars($booking['image_url']); ?>" alt="<?php echo htmlspecialchars($booking['product_name']); ?>">
                        </div>
                        <div class="booking-details">
                            <h3><?php echo htmlspecialchars($booking['product_name']); ?></h3>
                            <div class="booking-meta">
                                <span class="booking-date">Booked: <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></span>
                                <span class="rental-days">Duration: <?php echo $booking['rental_days']; ?> days</span>
                                <span class="total-amount">Total: $<?php echo number_format($booking['total_amount'], 2); ?></span>
                            </div>
                            <div class="booking-status">
                                Status: <span class="status-badge"><?php echo ucfirst($booking['status']); ?></span>
                            </div>
                        </div>
                        <div class="booking-actions">
                            <?php if ($booking['status'] == 'pending'): ?>
                                <button class="btn-secondary" onclick="cancelBooking(<?php echo $booking['id']; ?>)">Cancel</button>
                            <?php elseif ($booking['status'] == 'approved'): ?>
                                <button class="btn-primary" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)">View Details</button>
                            <?php elseif ($booking['status'] == 'completed'): ?>
                                <button class="btn-secondary" onclick="rentAgain(<?php echo $booking['product_id']; ?>)">Rent Again</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-bookings">
                    <h3>No bookings yet</h3>
                    <p>Start renting premium clothing from our collection!</p>
                    <a href="/amwali-closet/customer/products.php" class="cta-button">Browse Collection</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>