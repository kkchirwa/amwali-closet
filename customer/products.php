<?php
include '../includes/functions.php';
include '../includes/header.php';

// Include database connection
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get all products
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.available = 1 
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$category_query = "SELECT * FROM categories ORDER BY name";
$category_stmt = $db->prepare($category_query);
$category_stmt->execute();
$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="products-page">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Browse Our Collection</h1>
            <p>Discover premium clothing available for rent</p>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filters-grid">
                <!-- Search Box -->
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search clothing items...">
                    <button onclick="searchProducts()">🔍</button>
                </div>

                <!-- Category Filter -->
                <div class="filter-group">
                    <label for="categoryFilter">Category:</label>
                    <select id="categoryFilter" onchange="filterProducts()">
                        <option value="all">All Categories</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Size Filter -->
                <div class="filter-group">
                    <label for="sizeFilter">Size:</label>
                    <select id="sizeFilter" onchange="filterProducts()">
                        <option value="all">All Sizes</option>
                        <option value="XS">XS</option>
                        <option value="S">S</option>
                        <option value="M">M</option>
                        <option value="L">L</option>
                        <option value="XL">XL</option>
                    </select>
                </div>

                <!-- Sort Options -->
                <div class="filter-group">
                    <label for="sortBy">Sort By:</label>
                    <select id="sortBy" onchange="sortProducts()">
                        <option value="newest">Newest First</option>
                        <option value="price_low">Price: Low to High</option>
                        <option value="price_high">Price: High to Low</option>
                        <option value="name">Name: A to Z</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="products-grid" id="productsGrid">
            <?php if(count($products) > 0): ?>
                <?php foreach($products as $product): ?>
                    <div class="product-card" 
                         data-category="<?php echo $product['category_id']; ?>" 
                         data-size="<?php echo $product['size']; ?>"
                         data-price="<?php echo $product['price']; ?>"
                         data-name="<?php echo strtolower($product['name']); ?>">
                        
                        <div class="product-image">
                            <?php if($product['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                            
                            <?php if($product['stock_quantity'] == 0): ?>
                                <div class="out-of-stock-badge">Out of Stock</div>
                            <?php elseif($product['stock_quantity'] <= 2): ?>
                                <div class="low-stock-badge">Low Stock</div>
                            <?php endif; ?>
                            
                            <div class="product-actions">
                                <button class="quick-view-btn" onclick="quickView(<?php echo $product['id']; ?>)">
                                    👁️ Quick View
                                </button>
                            </div>
                        </div>

                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            
                            <div class="product-meta">
                                <span class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                <span class="product-size">Size: <?php echo $product['size']; ?></span>
                            </div>

                            <div class="product-pricing">
                                <div class="daily-rate">$<?php echo number_format($product['price'], 2); ?>/day</div>
                                <div class="stock-info">
                                    <?php if($product['stock_quantity'] > 0): ?>
                                        <span class="in-stock"><?php echo $product['stock_quantity']; ?> available</span>
                                    <?php else: ?>
                                        <span class="out-of-stock">Out of stock</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="product-actions-main">
                                <button class="rent-btn" 
                                        onclick="openRentalModal(<?php echo $product['id']; ?>)" 
                                        <?php echo $product['stock_quantity'] == 0 ? 'disabled' : ''; ?>>
                                    <?php echo $product['stock_quantity'] == 0 ? 'Out of Stock' : 'Rent Now'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-products">
                    <h3>No products available at the moment.</h3>
                    <p>Please check back later for new arrivals.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Results Count -->
        <div class="results-info">
            <p id="resultsCount">Showing <?php echo count($products); ?> products</p>
        </div>
    </div>
</div>

<!-- Rental Modal -->
<div id="rentalModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeRentalModal()">&times;</span>
        <h2>Rent This Item</h2>
        <div id="modalProductDetails">
            <!-- Product details will be loaded here via JavaScript -->
        </div>
    </div>
</div>

<!-- Quick View Modal -->
<div id="quickViewModal" class="modal">
    <div class="modal-content quick-view-content">
        <span class="close-modal" onclick="closeQuickViewModal()">&times;</span>
        <div id="quickViewDetails">
            <!-- Quick view details will be loaded here -->
        </div>
    </div>
</div>

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
                        <input type="checkbox" name="terms" required>
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

<?php include '../includes/footer.php'; ?>

<script>
// Filter and Search functionality
function filterProducts() {
    const categoryFilter = document.getElementById('categoryFilter').value;
    const sizeFilter = document.getElementById('sizeFilter').value;
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    const productCards = document.querySelectorAll('.product-card');
    let visibleCount = 0;
    
    productCards.forEach(card => {
        const category = card.getAttribute('data-category');
        const size = card.getAttribute('data-size');
        const name = card.getAttribute('data-name');
        
        const categoryMatch = categoryFilter === 'all' || category === categoryFilter;
        const sizeMatch = sizeFilter === 'all' || size === sizeFilter;
        const searchMatch = name.includes(searchTerm) || 
                           card.querySelector('.product-description').textContent.toLowerCase().includes(searchTerm);
        
        if (categoryMatch && sizeMatch && searchMatch) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    document.getElementById('resultsCount').textContent = `Showing ${visibleCount} products`;
}

function searchProducts() {
    filterProducts();
}

function sortProducts() {
    const sortBy = document.getElementById('sortBy').value;
    const productsGrid = document.getElementById('productsGrid');
    const productCards = Array.from(document.querySelectorAll('.product-card'));
    
    productCards.sort((a, b) => {
        switch(sortBy) {
            case 'price_low':
                return parseFloat(a.getAttribute('data-price')) - parseFloat(b.getAttribute('data-price'));
            case 'price_high':
                return parseFloat(b.getAttribute('data-price')) - parseFloat(a.getAttribute('data-price'));
            case 'name':
                return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
            case 'newest':
            default:
                return 0; // Already sorted by newest in PHP
        }
    });
    
    // Reappend sorted products
    productCards.forEach(card => productsGrid.appendChild(card));
}

// Rental functionality - UPDATED (uses global auth modal)
function openRentalModal(productId) {
    <?php if (!isLoggedIn()): ?>
        showAuthModal();
        return;
    <?php else: ?>
        // If user is logged in, proceed with rental
        document.getElementById('modalProductDetails').innerHTML = `
            <p>Product ID: ${productId}</p>
            <p>Rental functionality will be implemented in the next step.</p>
            <div class="modal-actions">
                <button onclick="closeRentalModal()" class="btn-secondary">Cancel</button>
                <button onclick="addToCart(${productId})" class="btn-primary">Add to Cart</button>
            </div>
        `;
        document.getElementById('rentalModal').style.display = 'block';
    <?php endif; ?>
}

function closeRentalModal() {
    document.getElementById('rentalModal').style.display = 'none';
}

function quickView(productId) {
    document.getElementById('quickViewDetails').innerHTML = `
        <h3>Quick View</h3>
        <p>Quick view for product ${productId} will be implemented later.</p>
    `;
    document.getElementById('quickViewModal').style.display = 'block';
}

function closeQuickViewModal() {
    document.getElementById('quickViewModal').style.display = 'none';
}

function addToCart(productId) {
    alert(`Product ${productId} added to cart!`);
    closeRentalModal();
}

// Close modals when clicking outside
window.onclick = function(event) {
    const rentalModal = document.getElementById('rentalModal');
    const quickViewModal = document.getElementById('quickViewModal');
    
    if (event.target === rentalModal) {
        closeRentalModal();
    }
    if (event.target === quickViewModal) {
        closeQuickViewModal();
    }
}
function addToCart(productId) {
    // Get product details (in real app, this would be from database)
    const productCard = document.querySelector(`.product-card[data-product-id="${productId}"]`);
    const productName = productCard.querySelector('.product-name').textContent;
    const productPrice = parseFloat(productCard.getAttribute('data-price'));
    const productImage = productCard.querySelector('img').src;
    const productSize = productCard.querySelector('.product-size').textContent.replace('Size: ', '');
    const productCategory = productCard.querySelector('.product-category').textContent;
    
    const cartItem = {
        id: productId,
        name: productName,
        price: productPrice,
        image: productImage,
        size: productSize,
        category: productCategory,
        rentalDays: 3 // Default rental period
    };
    
    // Get existing cart or initialize empty array
    let cart = JSON.parse(localStorage.getItem('amwali_cart') || '[]');
    
    // Check if item already in cart
    const existingItemIndex = cart.findIndex(item => item.id == productId);
    if (existingItemIndex > -1) {
        cart[existingItemIndex].rentalDays += 3; // Add more days if already in cart
    } else {
        cart.push(cartItem);
    }
    
    // Save to localStorage
    localStorage.setItem('amwali_cart', JSON.stringify(cart));
    
    // Show success message
    alert(`${productName} added to cart!`);
    closeRentalModal();
    
    // Update cart count in header
    updateCartCount();
}

function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('amwali_cart') || '[]');
    const cartCount = cart.reduce((total, item) => total + 1, 0);
    
    // Update cart count in navigation
    const cartLinks = document.querySelectorAll('.nav-links a[href*="cart"]');
    cartLinks.forEach(link => {
        let existingCount = link.querySelector('.cart-count');
        if (existingCount) {
            existingCount.textContent = cartCount;
        } else if (cartCount > 0) {
            link.innerHTML += `<span class="cart-count">${cartCount}</span>`;
        }
    });
}

// Call this on page load
document.addEventListener('DOMContentLoaded', updateCartCount);

// Initialize filters
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('searchInput').addEventListener('input', filterProducts);
});
</script>