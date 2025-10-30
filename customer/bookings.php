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
$query = "SELECT b.*, p.name as product_name, p.image_url, p.price as daily_rate
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
            <p>Manage your clothing rentals and track their status</p>
        </div>

        <div class="bookings-list">
            <?php if (count($bookings) > 0): ?>
                <?php foreach($bookings as $booking): ?>
                    <div class="booking-card status-<?php echo $booking['status']; ?>">
                        <div class="booking-image">
                            <img src="<?php echo htmlspecialchars($booking['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($booking['product_name']); ?>"
                                 onerror="this.src='/assets/images/placeholder.jpg'">
                        </div>
                        <div class="booking-details">
                            <h3><?php echo htmlspecialchars($booking['product_name']); ?></h3>
                            <div class="booking-meta">
                                <span class="booking-date">
                                    <strong>Booked:</strong> <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?>
                                </span>
                                <span class="rental-days">
                                    <strong>Duration:</strong> <?php echo $booking['rental_days']; ?> days
                                </span>
                                <span class="daily-rate">
                                    <strong>Daily Rate:</strong> $<?php echo number_format($booking['daily_rate'], 2); ?>
                                </span>
                                <span class="total-amount">
                                    <strong>Total:</strong> $<?php echo number_format($booking['total_amount'], 2); ?>
                                </span>
                            </div>
                            <div class="booking-status">
                                <strong>Status:</strong> 
                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                    <?php 
                                    $statusLabels = [
                                        'pending' => '⏳ Pending Approval',
                                        'approved' => '✅ Approved - Ready for Pickup',
                                        'rejected' => '❌ Rejected',
                                        'completed' => '📦 Returned & Completed',
                                        'cancelled' => '🚫 Cancelled'
                                    ];
                                    echo $statusLabels[$booking['status']] ?? ucfirst($booking['status']);
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="booking-actions">
                            <?php if ($booking['status'] == 'pending'): ?>
                                <button class="btn-secondary" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                    Cancel Booking
                                </button>
                            <?php elseif ($booking['status'] == 'approved'): ?>
                                <button class="btn-primary" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)">
                                    View Details
                                </button>
                            <?php elseif ($booking['status'] == 'completed'): ?>
                                <button class="btn-secondary" onclick="rentAgain(<?php echo $booking['product_id']; ?>)">
                                    Rent Again
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-bookings">
                    <h3>No bookings yet</h3>
                    <p>Start renting premium clothing from our collection!</p>
                    <a href="/customer/products.php" class="cta-button">Browse Collection</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Booking Details Modal -->
<div id="bookingDetailsModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeBookingDetailsModal()">&times;</span>
        <div id="bookingDetailsContent">
            <!-- Booking details will be loaded here -->
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
        // Show loading state on the button
        const cancelBtn = event.target;
        const originalText = cancelBtn.textContent;
        cancelBtn.textContent = 'Cancelling...';
        cancelBtn.disabled = true;

        // Send cancellation request to backend
        const formData = new FormData();
        formData.append('booking_id', bookingId);

        fetch('/includes/cancel_booking_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showBookingMessage('Booking cancelled successfully!', 'success');
                
                // Reload the page to reflect changes
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showBookingMessage('Cancellation failed: ' + data.message, 'error');
                cancelBtn.textContent = originalText;
                cancelBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Cancellation error:', error);
            showBookingMessage('Cancellation failed. Please try again.', 'error');
            cancelBtn.textContent = originalText;
            cancelBtn.disabled = false;
        });
    }
}

function showBookingMessage(message, type) {
    // Remove existing messages
    const existingMessage = document.querySelector('.booking-message');
    if (existingMessage) {
        existingMessage.remove();
    }

    // Create message element
    const messageEl = document.createElement('div');
    messageEl.className = `booking-message booking-message-${type}`;
    messageEl.textContent = message;
    messageEl.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        max-width: 300px;
    `;
    
    if (type === 'success') {
        messageEl.style.background = '#10b981';
    } else {
        messageEl.style.background = '#ef4444';
    }
    
    document.body.appendChild(messageEl);
    
    // Remove after 5 seconds
    setTimeout(() => {
        messageEl.remove();
    }, 5000);
}

function viewBookingDetails(bookingId) {
    // In real implementation, this would fetch details from backend
    document.getElementById('bookingDetailsContent').innerHTML = `
        <h3>Booking Details</h3>
        <div class="booking-details-content">
            <p><strong>Booking ID:</strong> #${bookingId}</p>
            <p><strong>Status:</strong> Approved ✅</p>
            <p><strong>Pickup Instructions:</strong></p>
            <ul>
                <li>Bring your ID for verification</li>
                <li>Inspect the clothing before leaving</li>
                <li>Keep the receipt for returns</li>
            </ul>
            <p><strong>Return Date:</strong> Within the rental period</li>
            <p><strong>Late Fees:</strong> 20% of daily rate per day</p>
        </div>
        <div class="modal-actions">
            <button class="btn-secondary" onclick="closeBookingDetailsModal()">Close</button>
        </div>
    `;
    document.getElementById('bookingDetailsModal').style.display = 'block';
}

function closeBookingDetailsModal() {
    document.getElementById('bookingDetailsModal').style.display = 'none';
}

function rentAgain(productId) {
    window.location.href = '/customer/products.php?product=' + productId;
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('bookingDetailsModal');
    if (event.target === modal) {
        closeBookingDetailsModal();
    }
}

// Add click handlers for all cancel buttons when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Add confirmation for all cancel buttons
    const cancelButtons = document.querySelectorAll('.booking-actions .btn-secondary');
    cancelButtons.forEach(button => {
        if (button.textContent.includes('Cancel')) {
            // Remove any existing click handlers and add our own
            button.replaceWith(button.cloneNode(true));
        }
    });
});
</script>