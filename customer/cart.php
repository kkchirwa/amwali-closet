<?php
include '../includes/functions.php';
include '../includes/header.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: /amwali-closet/');
    exit();
}
?>

<div class="cart-page">
    <div class="container">
        <div class="page-header">
            <h1>Your Rental Cart</h1>
            <p>Review your selected items before booking</p>
        </div>

        <div class="cart-content">
            <div class="cart-items" id="cartItems">
                <!-- Cart items will be loaded here via JavaScript -->
                <div class="empty-cart">
                    <h3>Your cart is empty</h3>
                    <p>Start adding some stylish clothing to rent!</p>
                    <a href="/amwali-closet/customer/products.php" class="cta-button">Browse Collection</a>
                </div>
            </div>

            <div class="cart-summary" id="cartSummary" style="display: none;">
                <h3>Booking Summary</h3>
                <div class="summary-details">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="subtotal">$0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Service Fee (10%):</span>
                        <span id="serviceFee">$0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Insurance (5%):</span>
                        <span id="insuranceFee">$0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Amount:</span>
                        <span id="totalAmount">$0.00</span>
                    </div>
                </div>
                <div class="cart-actions">
                    <button class="btn-secondary" onclick="continueShopping()">Continue Shopping</button>
                    <button class="checkout-btn" onclick="proceedToCheckout()">Proceed to Checkout</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div id="checkoutModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeCheckoutModal()">&times;</span>
        <h2>Checkout</h2>
        <div id="checkoutDetails">
            <!-- Checkout details will be loaded here -->
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// Cart functionality
function loadCart() {
    const cartItems = JSON.parse(localStorage.getItem('amwali_cart') || '[]');
    const cartContainer = document.getElementById('cartItems');
    
    if (cartItems.length === 0) {
        document.getElementById('cartSummary').style.display = 'none';
        cartContainer.innerHTML = `
            <div class="empty-cart">
                <h3>Your cart is empty</h3>
                <p>Start adding some stylish clothing to rent!</p>
                <a href="/amwali-closet/customer/products.php" class="cta-button">Browse Collection</a>
            </div>
        `;
        return;
    }

    let html = '';
    let subtotal = 0;

    cartItems.forEach((item, index) => {
        const itemTotal = item.price * item.rentalDays;
        subtotal += itemTotal;
        
        html += `
            <div class="cart-item" data-id="${item.id}">
                <div class="item-image">
                    <img src="${item.image}" alt="${item.name}" onerror="this.src='/amwali-closet/assets/images/placeholder.jpg'">
                </div>
                <div class="item-details">
                    <h4>${item.name}</h4>
                    <p class="item-category">${item.category}</p>
                    <p class="item-size">Size: ${item.size}</p>
                    <div class="rental-controls">
                        <label>Rental Period:
                            <select onchange="updateRentalDays(${item.id}, this.value)">
                                ${Array.from({length: 30}, (_, i) => 
                                    `<option value="${i+1}" ${item.rentalDays == i+1 ? 'selected' : ''}>
                                        ${i+1} day${i+1 > 1 ? 's' : ''}
                                    </option>`
                                ).join('')}
                            </select>
                        </label>
                    </div>
                </div>
                <div class="item-pricing">
                    <p class="daily-rate">$${item.price}/day</p>
                    <p class="item-total">$${itemTotal.toFixed(2)}</p>
                    <button class="remove-btn" onclick="removeFromCart(${item.id})">
                        🗑️ Remove
                    </button>
                </div>
            </div>
        `;
    });

    cartContainer.innerHTML = html;
    updateCartSummary(subtotal);
    updateCartCount();
}

function updateCartSummary(subtotal) {
    const serviceFee = subtotal * 0.10;
    const insuranceFee = subtotal * 0.05;
    const total = subtotal + serviceFee + insuranceFee;

    document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('serviceFee').textContent = `$${serviceFee.toFixed(2)}`;
    document.getElementById('insuranceFee').textContent = `$${insuranceFee.toFixed(2)}`;
    document.getElementById('totalAmount').textContent = `$${total.toFixed(2)}`;
    document.getElementById('cartSummary').style.display = 'block';
}

function updateRentalDays(productId, days) {
    let cart = JSON.parse(localStorage.getItem('amwali_cart') || '[]');
    cart = cart.map(item => 
        item.id == productId ? {...item, rentalDays: parseInt(days)} : item
    );
    localStorage.setItem('amwali_cart', JSON.stringify(cart));
    loadCart();
}

function removeFromCart(productId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        let cart = JSON.parse(localStorage.getItem('amwali_cart') || '[]');
        cart = cart.filter(item => item.id != productId);
        localStorage.setItem('amwali_cart', JSON.stringify(cart));
        loadCart();
    }
}

function continueShopping() {
    window.location.href = '/amwali-closet/customer/products.php';
}

function proceedToCheckout() {
    const cartItems = JSON.parse(localStorage.getItem('amwali_cart') || '[]');
    
    if (cartItems.length === 0) {
        alert('Your cart is empty!');
        return;
    }

    // Calculate totals
    const subtotal = cartItems.reduce((sum, item) => sum + (item.price * item.rentalDays), 0);
    const serviceFee = subtotal * 0.10;
    const insuranceFee = subtotal * 0.05;
    const total = subtotal + serviceFee + insuranceFee;

    document.getElementById('checkoutDetails').innerHTML = `
        <div class="checkout-summary">
            <h3>Order Summary</h3>
            ${cartItems.map(item => `
                <div class="checkout-item">
                    <span>${item.name} (${item.rentalDays} days)</span>
                    <span>$${(item.price * item.rentalDays).toFixed(2)}</span>
                </div>
            `).join('')}
            <div class="checkout-totals">
                <div class="checkout-row">
                    <span>Subtotal:</span>
                    <span>$${subtotal.toFixed(2)}</span>
                </div>
                <div class="checkout-row">
                    <span>Service Fee:</span>
                    <span>$${serviceFee.toFixed(2)}</span>
                </div>
                <div class="checkout-row">
                    <span>Insurance:</span>
                    <span>$${insuranceFee.toFixed(2)}</span>
                </div>
                <div class="checkout-row total">
                    <span>Total:</span>
                    <span>$${total.toFixed(2)}</span>
                </div>
            </div>
        </div>
        <div class="checkout-actions">
            <button class="btn-secondary" onclick="closeCheckoutModal()">Cancel</button>
            <button class="btn-primary" onclick="confirmBooking()">Confirm Booking & Pay</button>
        </div>
    `;
    
    document.getElementById('checkoutModal').style.display = 'block';
}

function closeCheckoutModal() {
    document.getElementById('checkoutModal').style.display = 'none';
}

function confirmBooking() {
    const cartItems = JSON.parse(localStorage.getItem('amwali-closet_cart') || '[]');
    
    if (cartItems.length === 0) {
        alert('Your cart is empty!');
        return;
    }

    // Show loading
    const confirmBtn = document.querySelector('.checkout-actions .btn-primary');
    const originalText = confirmBtn.textContent;
    confirmBtn.textContent = 'Processing...';
    confirmBtn.disabled = true;

    // Simulate API call to backend
    setTimeout(() => {
        // In real implementation, this would send data to your backend
        console.log('Booking confirmed:', cartItems);
        
        // Clear cart
        localStorage.removeItem('amwali_cart');
        
        // Show success message
        alert('Booking confirmed successfully! Your items have been reserved.');
        
        // Close modal and redirect
        closeCheckoutModal();
        window.location.href = '/amwali-closet/customer/bookings.php';
    }, 2000);
}

// Update cart count in header
function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('amwali_cart') || '[]');
    const cartCount = cart.length;
    
    // Update cart count in navigation
    const cartLinks = document.querySelectorAll('.nav-links a[href*="cart"]');
    cartLinks.forEach(link => {
        let existingCount = link.querySelector('.cart-count');
        if (existingCount) {
            existingCount.textContent = cartCount;
        } else if (cartCount > 0) {
            const countSpan = document.createElement('span');
            countSpan.className = 'cart-count';
            countSpan.textContent = cartCount;
            link.appendChild(countSpan);
        }
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const checkoutModal = document.getElementById('checkoutModal');
    if (event.target === checkoutModal) {
        closeCheckoutModal();
    }
}

// Load cart when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadCart();
});
</script>