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

// Get stats for dashboard
$stats = [];
try {
    // Total users
    $stmt = $db->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'customer'");
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
    
    // Total products
    $stmt = $db->query("SELECT COUNT(*) as total_products FROM products");
    $stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];
    
    // Pending bookings
    $stmt = $db->query("SELECT COUNT(*) as pending_bookings FROM bookings WHERE status = 'pending'");
    $stats['pending_bookings'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending_bookings'];
    
    // Total revenue
    $stmt = $db->query("SELECT SUM(total_amount) as total_revenue FROM bookings WHERE status IN ('approved', 'completed')");
    $stats['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;
    
    // Recent bookings
    $stmt = $db->query("
        SELECT b.*, u.name as user_name, p.name as product_name 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN products p ON b.product_id = p.id 
        ORDER BY b.booking_date DESC 
        LIMIT 5
    ");
    $recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="admin-dashboard">
            <div class="container">
                <div class="page-header">
                    <h1>Admin Dashboard</h1>
                    <p>Manage your clothing rental business</p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Customers</h3>
                        <div class="number"><?php echo $stats['total_users']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Products</h3>
                        <div class="number"><?php echo $stats['total_products']; ?></div>
                    </div>
                    <div class="stat-card pending">
                        <h3>Pending Bookings</h3>
                        <div class="number"><?php echo $stats['pending_bookings']; ?></div>
                    </div>
                    <div class="stat-card revenue">
                        <h3>Total Revenue</h3>
                        <div class="number">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="admin-section">
                    <h2>Quick Actions</h2>
                    <div class="quick-actions">
                        <a href="inventory.php" class="action-btn">
                            <span class="icon">📦</span>
                            <span>Manage Inventory</span>
                        </a>
                        <a href="rentals.php" class="action-btn">
                            <span class="icon">📋</span>
                            <span>Manage Rentals</span>
                        </a>
                        <a href="reports.php" class="action-btn">
                            <span class="icon">📊</span>
                            <span>View Reports</span>
                        </a>
                        <a href="inventory.php?action=add" class="action-btn">
                            <span class="icon">➕</span>
                            <span>Add New Product</span>
                        </a>
                    </div>
                </div>
                
                <!-- Recent Bookings -->
                <div class="admin-section recent-bookings">
                    <h2>Recent Bookings</h2>
                    <?php if (!empty($recent_bookings)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Customer</th>
                                    <th>Product</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_bookings as $booking): ?>
                                    <tr>
                                        <td>#<?php echo $booking['id']; ?></td>
                                        <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['product_name']); ?></td>
                                        <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No recent bookings found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
