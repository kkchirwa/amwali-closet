<?php
include 'includes/functions.php';
include 'includes/header.php';
?>

<!-- Image Slider Section -->
<div class="hero-slider">
    <div class="slider-container">
        <!-- Slide 1 -->
        <div class="slide active">
            <img src="assets/images/slide1.jpeg" alt="Designer Dresses">
            <div class="slide-content">
                <h1>Welcome to Amwali Closet</h1>
                <p>Rent premium designer clothing for any occasion</p>
                <a href="/customer/products.php" class="cta-button">Browse Collection</a>
            </div>
        </div>
        
        <!-- Slide 2 -->
        <div class="slide">
            <img src="assets/images/slide2.jpeg" alt="Business Suits">
            <div class="slide-content">
                <h1>Elevate Your Style</h1>
                <p>Professional attire for important meetings and events</p>
                <a href="/customer/products.php" class="cta-button">Rent Now</a>
            </div>
        </div>
        
        <!-- Slide 3 -->
        <div class="slide">
            <img src="assets/images/slide3.jpeg" alt="Evening Wear">
            <div class="slide-content">
                <h1>Special Occasion Ready</h1>
                <p>Stunning evening wear for your memorable moments</p>
                <a href="/customer/products.php" class="cta-button">Discover More</a>
            </div>
        </div>
    </div>
    
    <!-- Slider Controls -->
    <button class="slider-btn prev-btn">‹</button>
    <button class="slider-btn next-btn">›</button>
    
    <!-- Slider Dots -->
    <div class="slider-dots">
        <span class="dot active" data-slide="0"></span>
        <span class="dot" data-slide="1"></span>
        <span class="dot" data-slide="2"></span>
    </div>
</div>

<div class="features-section">
    <div class="container">
        <h2>Why Choose Amwali Closet?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <h3>Premium Quality</h3>
                <p>High-end designer clothing from top brands</p>
            </div>
            <div class="feature-card">
                <h3>Easy Rental Process</h3>
                <p>Simple booking and return process</p>
            </div>
            <div class="feature-card">
                <h3>Affordable Pricing</h3>
                <p>Rent instead of buying expensive outfits</p>
            </div>
            <div class="feature-card">
                <h3>Dry Cleaning Included</h3>
                <p>Professional cleaning after every rental</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>