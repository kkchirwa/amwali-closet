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
                        <span>Service Fee:</span>
                        <span id="serviceFee">$0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span id="totalAmount">$0.00</span>
                    </div>
                </div>
                <button class="checkout-btn" onclick="proceedToCheckout()">Proceed to Checkout</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// Cart functionality
function loadCart() {
    // For now, we'll use a simple example
    // In real implementation, this would fetch from backend
    const cartItems = JSON.parse(localStorage.getItem('amwali_cart') || '[]');
    
    if (cartItems.length === 0) {
        document.getElementById('cartSummary').style.display = 'none';
        return;
    }

    let html = '';
    let subtotal = 0;

    cartItems.forEach(item => {
        const itemTotal = item.price * item.rentalDays;
        subtotal += itemTotal;
        
        html += `
            <div class="cart-item" data-id="${item.id}">
                <div class="item-image">
                    <img src="${item.image}" alt="${item.name}">
                </div>
                <div class="item-details">
                    <h4>${item.name}</h4>
                    <p class="item-category">${item.category}</p>
                    <p class="item-size">Size: ${item.size}</p>
                    <div class="rental-controls">
                        <label>Rental Period:
                            <select onchange="updateRentalDays(${item.id}, this.value)">
                                ${Array.from({length: 30}, (_, i) => 
                                    `<option value="${i+1}" ${item.rentalDays == i+1 ? 'selected' : ''}>${i+1} day${i+1 > 1 ? 's' : ''}</option>`
                                ).join('')}
                            </select>
                        </label>
                    </div>
                </div>
                <div class="item-pricing">
                    <p class="daily-rate">$${item.price}/day</p>
                    <p class="item-total">$${itemTotal.toFixed(2)}</p>
                    <button class="remove-btn" onclick="removeFromCart(${item.id})">Remove</button>
                </div>
            </div>
        `;
    });

    document.getElementById('cartItems').innerHTML = html;
    updateCartSummary(subtotal);
}

function updateCartSummary(subtotal) {
    const serviceFee = subtotal * 0.10; // 10% service fee
    const total = subtotal + serviceFee;

    document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('serviceFee').textContent = `$${serviceFee.toFixed(2)}`;
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
    let cart = JSON.parse(localStorage.getItem('amwali_cart') || '[]');
    cart = cart.filter(item => item.id != productId);
    localStorage.setItem('amwali_cart', JSON.stringify(cart));
    loadCart();
}

function proceedToCheckout() {
    alert('Checkout functionality will be implemented next!');
    // This will redirect to checkout page
}

// Load cart when page loads
document.addEventListener('DOMContentLoaded', loadCart);
</script>