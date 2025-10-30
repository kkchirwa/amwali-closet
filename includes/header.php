<?php
// includes/header.php
?>
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
                        <a href="/admin/">Dashboard</a>
                        <a href="/admin/inventory.php">Inventory</a>
                        <a href="/admin/rentals.php">Rentals</a>
                    <?php else: ?>
                        <a href="/customer/products.php">Browse</a>
                        <a href="/customer/cart.php">Cart</a>
                        <a href="/customer/bookings.php">My Bookings</a>
                    <?php endif; ?>
                    <a href="/includes/logout.php">Logout</a>
                <?php else: ?>
                    <a href="/customer/products.php">Browse</a>
                    <a href="#" onclick="showAuthModal(); return false;">Login</a>
                    <a href="#" onclick="showAuthModal('register'); return false;">Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- Authentication Modal -->
    <div id="authModal" class="modal">
        <div class="modal-content auth-modal">
            <span class="close-modal" onclick="closeAuthModal()">&times;</span>
            
            <div class="auth-tabs">
                <button id="loginTab" class="auth-tab active" onclick="switchToLogin()">Sign In</button>
                <button id="registerTab" class="auth-tab" onclick="switchToRegister()">Register</button>
            </div>
            
            <div class="auth-content">
                <!-- Login Form -->
                <form id="loginForm" class="auth-form" onsubmit="handleLogin(event)">
                    <h3>Welcome Back</h3>
                    <p>Sign in to your account to continue renting</p>
                    
                    <!-- Error/Success Messages -->
                    <div id="loginMessage" class="message-container" style="display: none;"></div>
                    
                    <div class="form-group">
                        <label for="loginEmail">Email Address</label>
                        <input type="email" id="loginEmail" name="email" required placeholder="Enter your email">
                    </div>
                    
                    <div class="form-group">
                        <label for="loginPassword">Password</label>
                        <input type="password" id="loginPassword" name="password" required placeholder="Enter your password">
                    </div>
                    
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember"> Remember me
                        </label>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="auth-submit-btn">Sign In</button>
                    
                    <div class="auth-divider">
                        <span>or continue with</span>
                    </div>
                    
                    <div class="social-auth">
                        <button type="button" class="social-btn google-btn">
                            <span>Google</span>
                        </button>
                        <button type="button" class="social-btn facebook-btn">
                            <span>Facebook</span>
                        </button>
                    </div>
                </form>
                
                <!-- Register Form -->
                <form id="registerForm" class="auth-form" style="display: none;" onsubmit="handleRegister(event)">
                    <h3>Create Account</h3>
                    <p>Join Amwali Closet to start renting premium clothing</p>
                    
                    <!-- Error/Success Messages -->
                    <div id="registerMessage" class="message-container" style="display: none;"></div>
                    
                    <div class="form-group">
                        <label for="registerName">Full Name</label>
                        <input type="text" id="registerName" name="name" required placeholder="Enter your full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="registerEmail">Email Address</label>
                        <input type="email" id="registerEmail" name="email" required placeholder="Enter your email">
                    </div>
                    
                    <div class="form-group">
                        <label for="registerPassword">Password</label>
                        <input type="password" id="registerPassword" name="password" required placeholder="Create a password">
                        <small>Must be at least 8 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="registerConfirmPassword">Confirm Password</label>
                        <input type="password" id="registerConfirmPassword" name="confirm_password" required placeholder="Confirm your password">
                    </div>
                    
                    <div class="form-group">
                        <label class="terms-agreement">
                            <input type="checkbox" id="termsCheckbox" name="terms" required>
                            I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="auth-submit-btn">Create Account</button>
                    
                    <div class="auth-divider">
                        <span>or sign up with</span>
                    </div>
                    
                    <div class="social-auth">
                        <button type="button" class="social-btn google-btn">
                            <span>Google</span>
                        </button>
                        <button type="button" class="social-btn facebook-btn">
                            <span>Facebook</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <main class="main-content">