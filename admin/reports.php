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

// Date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month

try {
    // Revenue Statistics
    $revenue_query = "
        SELECT 
            SUM(total_amount) as total_revenue,
            COUNT(*) as total_bookings,
            AVG(total_amount) as avg_booking_value,
            SUM(CASE WHEN status IN ('approved', 'completed') THEN total_amount ELSE 0 END) as confirmed_revenue,
            SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending_revenue
        FROM bookings 
        WHERE booking_date BETWEEN :start_date AND :end_date
    ";
    $revenue_stmt = $db->prepare($revenue_query);
    $revenue_stmt->bindParam(':start_date', $start_date);
    $revenue_stmt->bindParam(':end_date', $end_date);
    $revenue_stmt->execute();
    $revenue_stats = $revenue_stmt->fetch(PDO::FETCH_ASSOC);

    // Monthly Revenue Trend (Last 6 months)
    $monthly_trend_query = "
        SELECT 
            DATE_FORMAT(booking_date, '%Y-%m') as month,
            SUM(total_amount) as revenue,
            COUNT(*) as booking_count
        FROM bookings 
        WHERE status IN ('approved', 'completed')
        AND booking_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 6
    ";
    $monthly_trend_stmt = $db->query($monthly_trend_query);
    $monthly_trend = array_reverse($monthly_trend_stmt->fetchAll(PDO::FETCH_ASSOC));

    // Popular Products
    $popular_products_query = "
        SELECT 
            p.name,
            p.category_id,
            c.name as category_name,
            COUNT(b.id) as booking_count,
            SUM(b.total_amount) as total_revenue,
            AVG(b.total_amount) as avg_revenue
        FROM products p
        LEFT JOIN bookings b ON p.id = b.product_id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE b.booking_date BETWEEN :start_date AND :end_date
        AND b.status IN ('approved', 'completed')
        GROUP BY p.id, p.name, p.category_id, c.name
        ORDER BY total_revenue DESC
        LIMIT 10
    ";
    $popular_products_stmt = $db->prepare($popular_products_query);
    $popular_products_stmt->bindParam(':start_date', $start_date);
    $popular_products_stmt->bindParam(':end_date', $end_date);
    $popular_products_stmt->execute();
    $popular_products = $popular_products_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Booking Status Distribution
    $status_distribution_query = "
        SELECT 
            status,
            COUNT(*) as count,
            SUM(total_amount) as revenue
        FROM bookings 
        WHERE booking_date BETWEEN :start_date AND :end_date
        GROUP BY status
    ";
    $status_distribution_stmt = $db->prepare($status_distribution_query);
    $status_distribution_stmt->bindParam(':start_date', $start_date);
    $status_distribution_stmt->bindParam(':end_date', $end_date);
    $status_distribution_stmt->execute();
    $status_distribution = $status_distribution_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Customer Analytics
    $customer_analytics_query = "
        SELECT 
            u.id,
            u.name,
            u.email,
            COUNT(b.id) as total_bookings,
            SUM(b.total_amount) as total_spent,
            MAX(b.booking_date) as last_booking
        FROM users u
        LEFT JOIN bookings b ON u.id = b.user_id
        WHERE u.role = 'customer'
        AND b.booking_date BETWEEN :start_date AND :end_date
        GROUP BY u.id, u.name, u.email
        HAVING total_bookings > 0
        ORDER BY total_spent DESC
        LIMIT 10
    ";
    $customer_analytics_stmt = $db->prepare($customer_analytics_query);
    $customer_analytics_stmt->bindParam(':start_date', $start_date);
    $customer_analytics_stmt->bindParam(':end_date', $end_date);
    $customer_analytics_stmt->execute();
    $top_customers = $customer_analytics_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Category Performance
    $category_performance_query = "
        SELECT 
            c.name as category_name,
            COUNT(b.id) as booking_count,
            SUM(b.total_amount) as total_revenue,
            AVG(b.total_amount) as avg_revenue_per_booking
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        LEFT JOIN bookings b ON p.id = b.product_id
        WHERE b.booking_date BETWEEN :start_date AND :end_date
        AND b.status IN ('approved', 'completed')
        GROUP BY c.id, c.name
        ORDER BY total_revenue DESC
    ";
    $category_performance_stmt = $db->prepare($category_performance_query);
    $category_performance_stmt->bindParam(':start_date', $start_date);
    $category_performance_stmt->bindParam(':end_date', $end_date);
    $category_performance_stmt->execute();
    $category_performance = $category_performance_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Amwali Closet</title>
    <link rel="stylesheet" href="/amwali-closet/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .reports-page {
            padding: 2rem 0;
            background: #f8fafc;
            min-height: 100vh;
        }
        
        .date-filter {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .date-filter form {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group label {
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }
        
        .form-group input {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .filter-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: #1e293b;
        }
        
        .stat-card.revenue .number {
            color: #10b981;
        }
        
        .stat-card.bookings .number {
            color: #3b82f6;
        }
        
        .stat-card.avg-value .number {
            color: #f59e0b;
        }
        
        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .chart-container h2 {
            color: #1e293b;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .data-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .data-table h2 {
            padding: 1.5rem 1.5rem 0;
            color: #1e293b;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }
        
        .data-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .data-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
        }
        
        .progress-bar {
            background: #e2e8f0;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: #3b82f6;
            border-radius: 10px;
        }
        
        .export-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }
        
        .export-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="reports-page">
            <div class="container">
                <div class="page-header">
                    <h1>Reports & Analytics</h1>
                    <p>Business insights and performance metrics</p>
                </div>
                
                <!-- Date Filter -->
                <div class="date-filter">
                    <form method="GET">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
                        </div>
                        <button type="submit" class="filter-btn">Apply Filter</button>
                    </form>
                </div>
                
                <!-- Export Actions -->
                <div class="export-actions">
                    <button class="export-btn" onclick="exportToPDF()">
                        📄 Export to PDF
                    </button>
                    <button class="export-btn" onclick="exportToCSV()">
                        📊 Export to CSV
                    </button>
                </div>
                
                <!-- Key Metrics -->
                <div class="stats-grid">
                    <div class="stat-card revenue">
                        <h3>Total Revenue</h3>
                        <div class="number">$<?php echo number_format($revenue_stats['total_revenue'] ?? 0, 2); ?></div>
                        <small>Confirmed: $<?php echo number_format($revenue_stats['confirmed_revenue'] ?? 0, 2); ?></small>
                    </div>
                    <div class="stat-card bookings">
                        <h3>Total Bookings</h3>
                        <div class="number"><?php echo number_format($revenue_stats['total_bookings'] ?? 0); ?></div>
                    </div>
                    <div class="stat-card avg-value">
                        <h3>Avg Booking Value</h3>
                        <div class="number">$<?php echo number_format($revenue_stats['avg_booking_value'] ?? 0, 2); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Pending Revenue</h3>
                        <div class="number">$<?php echo number_format($revenue_stats['pending_revenue'] ?? 0, 2); ?></div>
                    </div>
                </div>
                
                <!-- Charts Grid -->
                <div class="chart-grid">
                    <!-- Revenue Trend Chart -->
                    <div class="chart-container">
                        <h2>Revenue Trend (Last 6 Months)</h2>
                        <canvas id="revenueTrendChart" height="250"></canvas>
                    </div>
                    
                    <!-- Booking Status Distribution -->
                    <div class="chart-container">
                        <h2>Booking Status Distribution</h2>
                        <canvas id="statusDistributionChart" height="250"></canvas>
                    </div>
                </div>
                
                <!-- Category Performance -->
                <div class="chart-container">
                    <h2>Category Performance</h2>
                    <canvas id="categoryPerformanceChart" height="300"></canvas>
                </div>
                
                <!-- Popular Products Table -->
                <div class="data-table">
                    <h2>Top Performing Products</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Bookings</th>
                                <th>Total Revenue</th>
                                <th>Avg Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($popular_products)): ?>
                                <?php foreach($popular_products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                        <td><?php echo $product['booking_count']; ?></td>
                                        <td>$<?php echo number_format($product['total_revenue'], 2); ?></td>
                                        <td>$<?php echo number_format($product['avg_revenue'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2rem; color: #64748b;">
                                        No booking data available for the selected period.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Top Customers Table -->
                <div class="data-table">
                    <h2>Top Customers</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Total Bookings</th>
                                <th>Total Spent</th>
                                <th>Last Booking</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($top_customers)): ?>
                                <?php foreach($top_customers as $customer): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                        <td><?php echo $customer['total_bookings']; ?></td>
                                        <td>$<?php echo number_format($customer['total_spent'], 2); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($customer['last_booking'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2rem; color: #64748b;">
                                        No customer data available for the selected period.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
    // Revenue Trend Chart
    const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
    const revenueTrendChart = new Chart(revenueTrendCtx, {
        type: 'line',
        data: {
            labels: [<?php echo implode(',', array_map(function($item) { 
                return "'" . date('M Y', strtotime($item['month'] . '-01')) . "'"; 
            }, $monthly_trend)); ?>],
            datasets: [{
                label: 'Revenue',
                data: [<?php echo implode(',', array_column($monthly_trend, 'revenue')); ?>],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Bookings',
                data: [<?php echo implode(',', array_column($monthly_trend, 'booking_count')); ?>],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });
    
    // Status Distribution Chart
    const statusDistributionCtx = document.getElementById('statusDistributionChart').getContext('2d');
    const statusDistributionChart = new Chart(statusDistributionCtx, {
        type: 'doughnut',
        data: {
            labels: [<?php echo implode(',', array_map(function($item) { 
                return "'" . ucfirst($item['status']) . "'"; 
            }, $status_distribution)); ?>],
            datasets: [{
                data: [<?php echo implode(',', array_column($status_distribution, 'count')); ?>],
                backgroundColor: [
                    '#f59e0b', // pending
                    '#10b981', // approved
                    '#3b82f6', // completed
                    '#ef4444', // rejected
                    '#6b7280'  // cancelled
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
    
    // Category Performance Chart
    const categoryPerformanceCtx = document.getElementById('categoryPerformanceChart').getContext('2d');
    const categoryPerformanceChart = new Chart(categoryPerformanceCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($item) { 
                return "'" . $item['category_name'] . "'"; 
            }, $category_performance)); ?>],
            datasets: [{
                label: 'Total Revenue',
                data: [<?php echo implode(',', array_column($category_performance, 'total_revenue')); ?>],
                backgroundColor: '#3b82f6',
                borderColor: '#2563eb',
                borderWidth: 1
            }, {
                label: 'Number of Bookings',
                data: [<?php echo implode(',', array_column($category_performance, 'booking_count')); ?>],
                backgroundColor: '#10b981',
                borderColor: '#059669',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Export Functions
    function exportToPDF() {
        alert('PDF export functionality would be implemented here. This would generate a comprehensive report.');
        // In real implementation, this would call a backend PDF generation service
    }
    
    function exportToCSV() {
        alert('CSV export functionality would be implemented here. This would download the report data as CSV.');
        // In real implementation, this would generate and download CSV files
    }
    
    // Auto-refresh data every 5 minutes
    setInterval(() => {
        // In a real implementation, this would refresh the charts with new data
        console.log('Auto-refreshing report data...');
    }, 300000);
    </script>
</body>
</html>