<?php
include '../includes/functions.php';

// Redirect if not admin
if (!isAdmin()) {
    header('Location: /amwali-closet/');
    exit();
}

// Include database connection
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle rental actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $booking_id = intval($_POST['booking_id'] ?? 0);
    
    try {
        if ($action === 'approve') {
            $query = "UPDATE bookings SET status = 'approved' WHERE id = :booking_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':booking_id', $booking_id);
            
            if ($stmt->execute()) {
                $message = 'Booking approved successfully!';
            }
            
        } elseif ($action === 'reject') {
            $reason = trim($_POST['rejection_reason'] ?? '');
            $query = "UPDATE bookings SET status = 'rejected', end_date = NOW() WHERE id = :booking_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':booking_id', $booking_id);
            
            if ($stmt->execute()) {
                $message = 'Booking rejected successfully!';
            }
            
        } elseif ($action === 'complete') {
            $query = "UPDATE bookings SET status = 'completed', end_date = NOW() WHERE id = :booking_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':booking_id', $booking_id);
            
            if ($stmt->execute()) {
                $message = 'Booking marked as completed!';
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get all bookings with filters
$status_filter = $_GET['status'] ?? 'all';

try {
    $query = "SELECT b.*, u.name as customer_name, u.email as customer_email, 
                     p.name as product_name, p.image_url, p.price as daily_rate,
                     c.name as category_name
              FROM bookings b 
              JOIN users u ON b.user_id = u.id 
              JOIN products p ON b.product_id = p.id
              LEFT JOIN categories c ON p.category_id = c.id";
    
    // Apply status filter
    if ($status_filter !== 'all') {
        $query .= " WHERE b.status = :status";
    }
    
    $query .= " ORDER BY b.booking_date DESC";
    
    $stmt = $db->prepare($query);
    
    if ($status_filter !== 'all') {
        $stmt->bindParam(':status', $status_filter);
    }
    
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get stats for each status
    $status_stats = [
        'pending' => 0,
        'approved' => 0,
        'completed' => 0,
        'rejected' => 0,
        'cancelled' => 0
    ];
    
    $stats_stmt = $db->query("SELECT status, COUNT(*) as count FROM bookings GROUP BY status");
    $stats_data = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($stats_data as $stat) {
        $status_stats[$stat['status']] = $stat['count'];
    }
    $status_stats['all'] = array_sum($status_stats);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Management - Amwali Closet</title>
    <link rel="stylesheet" href="/amwali-closet/assets/css/style.css">
    <style>
        .rental-management {
            padding: 2rem 0;
            background: #f8fafc;
            min-height: 100vh;
        }
        
        .status-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .status-filter-btn {
            padding: 0.75rem 1.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-filter-btn.active {
            border-color: #3b82f6;
            background: #3b82f6;
            color: white;
        }
        
        .status-filter-btn .count {
            background: #e2e8f0;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .status-filter-btn.active .count {
            background: rgba(255,255,255,0.2);
        }
        
        .bookings-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .booking-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-left: 4px solid #e2e8f0;
        }
        
        .booking-card.status-pending {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }
        
        .booking-card.status-approved {
            border-left-color: #10b981;
            background: #f0fdf4;
        }
        
        .booking-card.status-completed {
            border-left-color: #3b82f6;
            background: #eff6ff;
        }
        
        .booking-card.status-rejected {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .booking-info h3 {
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .booking-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
        }
        
        .meta-label {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        
        .meta-value {
            font-weight: 500;
            color: #1e293b;
        }
        
        .booking-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-approve {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-reject {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-complete {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #374151;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-approved { background: #d1fae5; color: #059669; }
        .status-completed { background: #dbeafe; color: #2563eb; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        .status-cancelled { background: #f3f4f6; color: #374151; }
        
        .no-bookings {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 12px;
            color: #64748b;
        }
        
        /* Modal Styles */
        .rejection-reason {
            margin: 1rem 0;
        }
        
        .rejection-reason textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            resize: vertical;
            min-height: 80px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="rental-management">
            <div class="container">
                <div class="page-header">
                    <h1>Rental Management</h1>
                    <p>Manage booking requests and rental status</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error" style="background: #fee2e2; color: #dc2626; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Status Filters -->
                <div class="status-filters">
                    <button class="status-filter-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>" 
                            onclick="window.location.href='?status=all'">
                        All Bookings <span class="count"><?php echo $status_stats['all']; ?></span>
                    </button>
                    <button class="status-filter-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" 
                            onclick="window.location.href='?status=pending'">
                        ⏳ Pending <span class="count"><?php echo $status_stats['pending']; ?></span>
                    </button>
                    <button class="status-filter-btn <?php echo $status_filter === 'approved' ? 'active' : ''; ?>" 
                            onclick="window.location.href='?status=approved'">
                        ✅ Approved <span class="count"><?php echo $status_stats['approved']; ?></span>
                    </button>
                    <button class="status-filter-btn <?php echo $status_filter === 'completed' ? 'active' : ''; ?>" 
                            onclick="window.location.href='?status=completed'">
                        📦 Completed <span class="count"><?php echo $status_stats['completed']; ?></span>
                    </button>
                    <button class="status-filter-btn <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>" 
                            onclick="window.location.href='?status=rejected'">
                        ❌ Rejected <span class="count"><?php echo $status_stats['rejected']; ?></span>
                    </button>
                </div>
                
                <!-- Bookings List -->
                <div class="bookings-list">
                    <?php if (!empty($bookings)): ?>
                        <?php foreach($bookings as $booking): ?>
                            <div class="booking-card status-<?php echo $booking['status']; ?>">
                                <div class="booking-header">
                                    <div class="booking-info">
                                        <h3>Booking #<?php echo $booking['id']; ?> - <?php echo htmlspecialchars($booking['product_name']); ?></h3>
                                        <div class="booking-meta">
                                            <div class="meta-item">
                                                <span class="meta-label">Customer</span>
                                                <span class="meta-value"><?php echo htmlspecialchars($booking['customer_name']); ?></span>
                                            </div>
                                            <div class="meta-item">
                                                <span class="meta-label">Email</span>
                                                <span class="meta-value"><?php echo htmlspecialchars($booking['customer_email']); ?></span>
                                            </div>
                                            <div class="meta-item">
                                                <span class="meta-label">Category</span>
                                                <span class="meta-value"><?php echo htmlspecialchars($booking['category_name']); ?></span>
                                            </div>
                                            <div class="meta-item">
                                                <span class="meta-label">Rental Period</span>
                                                <span class="meta-value"><?php echo $booking['rental_days']; ?> days</span>
                                            </div>
                                            <div class="meta-item">
                                                <span class="meta-label">Daily Rate</span>
                                                <span class="meta-value">$<?php echo number_format($booking['daily_rate'], 2); ?></span>
                                            </div>
                                            <div class="meta-item">
                                                <span class="meta-label">Total Amount</span>
                                                <span class="meta-value">$<?php echo number_format($booking['total_amount'], 2); ?></span>
                                            </div>
                                            <div class="meta-item">
                                                <span class="meta-label">Booking Date</span>
                                                <span class="meta-value"><?php echo date('M j, Y g:i A', strtotime($booking['booking_date'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="booking-status">
                                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                                            <?php 
                                            $statusLabels = [
                                                'pending' => '⏳ Pending Approval',
                                                'approved' => '✅ Approved',
                                                'completed' => '📦 Completed',
                                                'rejected' => '❌ Rejected',
                                                'cancelled' => '🚫 Cancelled'
                                            ];
                                            echo $statusLabels[$booking['status']] ?? ucfirst($booking['status']);
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons based on status -->
                                <div class="booking-actions">
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" class="btn-approve" onclick="return confirm('Approve this booking?')">
                                                ✅ Approve Booking
                                            </button>
                                        </form>
                                        <button class="btn-reject" onclick="openRejectModal(<?php echo $booking['id']; ?>)">
                                            ❌ Reject Booking
                                        </button>
                                    <?php elseif ($booking['status'] === 'approved'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="complete">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" class="btn-complete" onclick="return confirm('Mark this booking as completed?')">
                                                📦 Mark as Completed
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <button class="btn-secondary" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)">
                                        👁️ View Details
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-bookings">
                            <h3>No bookings found</h3>
                            <p>There are no bookings with the selected status.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Rejection Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeRejectModal()">&times;</span>
            <h2>Reject Booking</h2>
            <form id="rejectForm" method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="booking_id" id="rejectBookingId">
                
                <div class="rejection-reason">
                    <label for="rejection_reason">Reason for rejection (optional):</label>
                    <textarea id="rejection_reason" name="rejection_reason" placeholder="Provide a reason for rejecting this booking..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" class="btn-reject">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Booking Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeDetailsModal()">&times;</span>
            <div id="detailsContent">
                <!-- Details will be loaded via JavaScript -->
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
    let currentBookingId = null;
    
    function openRejectModal(bookingId) {
        currentBookingId = bookingId;
        document.getElementById('rejectBookingId').value = bookingId;
        document.getElementById('rejectModal').style.display = 'block';
    }
    
    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
        currentBookingId = null;
    }
    
    function viewBookingDetails(bookingId) {
        // In a real implementation, you would fetch booking details via AJAX
        // For now, we'll show a simple message
        document.getElementById('detailsContent').innerHTML = `
            <h3>Booking Details #${bookingId}</h3>
            <p>Detailed booking information would be displayed here, including:</p>
            <ul>
                <li>Customer contact information</li>
                <li>Product details and images</li>
                <li>Rental period and dates</li>
                <li>Payment information</li>
                <li>Booking history and timeline</li>
            </ul>
            <div class="form-actions">
                <button class="btn-secondary" onclick="closeDetailsModal()">Close</button>
            </div>
        `;
        document.getElementById('detailsModal').style.display = 'block';
    }
    
    function closeDetailsModal() {
        document.getElementById('detailsModal').style.display = 'none';
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        const modals = ['rejectModal', 'detailsModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (event.target === modal) {
                if (modalId === 'rejectModal') closeRejectModal();
                if (modalId === 'detailsModal') closeDetailsModal();
            }
        });
    }
    </script>
</body>
</html>