// Ensure functions are available globally
window.showAuthModal = function(defaultTab = 'login') {
    console.log('showAuthModal called with:', defaultTab);
    if (defaultTab === 'register') {
        switchToRegister();
    } else {
        switchToLogin();
    }
    document.getElementById('authModal').style.display = 'block';
}

window.closeAuthModal = function() {
    document.getElementById('authModal').style.display = 'none';
    // Clear messages when closing
    document.getElementById('loginMessage').style.display = 'none';
    document.getElementById('registerMessage').style.display = 'none';
    // Clear forms
    document.getElementById('loginForm').reset();
    document.getElementById('registerForm').reset();
}

window.switchToLogin = function() {
    document.getElementById('loginForm').style.display = 'block';
    document.getElementById('registerForm').style.display = 'none';
    document.getElementById('loginTab').classList.add('active');
    document.getElementById('registerTab').classList.remove('active');
    // Clear messages
    document.getElementById('loginMessage').style.display = 'none';
    document.getElementById('registerMessage').style.display = 'none';
}

window.switchToRegister = function() {
    document.getElementById('loginForm').style.display = 'none';
    document.getElementById('registerForm').style.display = 'block';
    document.getElementById('loginTab').classList.remove('active');
    document.getElementById('registerTab').classList.add('active');
    // Clear messages
    document.getElementById('loginMessage').style.display = 'none';
    document.getElementById('registerMessage').style.display = 'none';
}

window.showMessage = function(elementId, message, type) {
    const messageEl = document.getElementById(elementId);
    messageEl.innerHTML = message;
    messageEl.className = `message ${type}`;
    messageEl.style.display = 'block';
    
    // Scroll to message
    messageEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Auto-hide success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            messageEl.style.display = 'none';
        }, 5000);
    }
}

// Form submission handlers
window.handleLogin = function(event) {
    event.preventDefault();
    console.log('Login form submitted');
    
    const form = event.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    // Show loading state
    submitBtn.textContent = 'Signing in...';
    submitBtn.disabled = true;
    
    // Send AJAX request
    fetch('/includes/login_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Login response status:', response.status);
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('Login response data:', data);
        if (data.success) {
            showMessage('loginMessage', data.message, 'success');
            // Redirect after successful login
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showMessage('loginMessage', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Login error:', error);
        showMessage('loginMessage', 'An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

window.handleRegister = function(event) {
    event.preventDefault();
    console.log('Register form submitted');
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Log form data
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    // Basic validation
    const password = formData.get('password');
    const confirmPassword = formData.get('confirm_password');
    
    if (password.length < 8) {
        showMessage('registerMessage', 'Password must be at least 8 characters long.', 'error');
        return;
    }
    
    if (password !== confirmPassword) {
        showMessage('registerMessage', 'Passwords do not match.', 'error');
        return;
    }
    
    if (!document.getElementById('termsCheckbox').checked) {
        showMessage('registerMessage', 'Please agree to the Terms of Service and Privacy Policy.', 'error');
        return;
    }
    
    // Show loading state
    submitBtn.textContent = 'Creating Account...';
    submitBtn.disabled = true;
    
    console.log('Sending registration request...');
    
    // Send AJAX request
    fetch('/includes/register_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Register response status:', response.status);
        console.log('Register response URL:', response.url);
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('Register response data:', data);
        if (data.success) {
            showMessage('registerMessage', data.message, 'success');
            form.reset();
            
            // Switch to login tab after successful registration
            setTimeout(() => {
                switchToLogin();
                showMessage('loginMessage', 'Account created successfully! Please sign in.', 'success');
            }, 2000);
        } else {
            showMessage('registerMessage', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Registration error:', error);
        showMessage('registerMessage', 'Registration failed: ' + error.message, 'error');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const authModal = document.getElementById('authModal');
    if (event.target === authModal) {
        closeAuthModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAuthModal();
    }
});

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Auth.js loaded - modal functions are ready');
});