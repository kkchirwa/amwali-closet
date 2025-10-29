<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amwali Closet - Premium Clothing Rental</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">
                <a href="/amwali-closet/">Amwali Closet</a>
            </div>
            <div class="nav-links">
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="admin/">Dashboard</a>
                        <a href="admin/inventory.php">Inventory</a>
                        <a href="admin/rentals.php">Rentals</a>
                    <?php else: ?>
                        <a href="customer/products.php">Browse</a>
                        <a href="customer/cart.php">Cart</a>
                        <a href="customer/bookings.php">My Bookings</a>
                    <?php endif; ?>
                    <a href="includes/logout.php">Logout</a>
                <?php else: ?>
                    <a href="customer/products.php">Browse</a>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <main class="main-content">