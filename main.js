// Main JavaScript functionality for Decathlon E-commerce Website

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Add to cart functionality
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const quantity = 1;
            
            addToCart(productId, quantity);
        });
    });

    // Quantity selectors
    const quantitySelectors = document.querySelectorAll('.quantity-selector');
    quantitySelectors.forEach(selector => {
        selector.addEventListener('change', function() {
            updateCartQuantity(this.getAttribute('data-product-id'), this.value);
        });
    });

    // Remove from cart buttons
    const removeFromCartButtons = document.querySelectorAll('.remove-from-cart');
    removeFromCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            removeFromCart(productId);
        });
    });

    // Search form
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[type="search"]');
            if (searchInput.value.trim() === '') {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }
});

// Add to cart function
function addToCart(productId, quantity) {
    // Show loading state
    const button = document.querySelector(`[data-product-id;="${productId}.
    if; const originalText = button.textContent;
    button.textContent = 'Adding...';
    button.disabled = true;

    // Create form data
    const formData = new FormData();
    formData.append('add_to_cart', 'true');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    // Send AJAX request
    fetch('index.php?page=cart', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Product added to cart!', 'success');
            updateCartCount(data.cart_count);
            
            // Update button state
            button.textContent = 'Added!';
            setTimeout(() => {
                button.textContent = originalText;
                button.disabled = false;
            }, 1000);
        } else {
            showToast(data.message || 'Error adding to cart', 'error');
            button.textContent = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error adding to cart', 'error');
        button.textContent = originalText;
        button.disabled = false;
    });
}

// Update cart quantity
function updateCartQuantity(productId, quantity) {
    if (quantity < 1) return;

    const formData = new FormData();
    formData.append('update_cart', 'true');
    formData.append('quantities[' + productId + ']', quantity);

    fetch('index.php?page=cart', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.cart_count);
            location.reload(); // Reload to update prices
        } else {
            showToast(data.message || 'Error updating cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error updating cart', 'error');
    });
}

// Remove from cart
function removeFromCart(productId) {
    if (!confirm('Are you sure you want to remove this item?')) return;

    const formData = new FormData();
    formData.append('remove_item', productId);

    fetch('index.php?page=cart', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Item removed from cart', 'success');
            updateCartCount(data.cart_count);
            location.reload(); // Reload to update cart display
        } else {
            showToast(data.message || 'Error removing item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error removing item', 'error');
    });
}

// Update cart count in navigation
function updateCartCount(count) {
    const cartBadge = document.querySelector('.cart-badge');
    if (cartBadge) {
        if (count > 0) {
            cartBadge.textContent = count;
            cartBadge.style.display = 'flex';
        } else {
            cartBadge.style.display = 'none';
        }
    }
}

// Show toast notification
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove toast element after it's hidden
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Create toast container if it doesn't exist
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1050';
    document.body.appendChild(container);
    return container;
}

// Form validation
function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    return strength;
}

// Product image gallery
function initProductGallery() {
    const mainImage = document.getElementById('main-product-image');
    const thumbnails = document.querySelectorAll('.product-thumbnail');
    
    if (mainImage && thumbnails.length > 0) {
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                const imageUrl = this.getAttribute('data-image');
                mainImage.src = imageUrl;
                
                // Update active state
                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }
}

// Initialize product gallery when page loads
if (document.getElementById('main-product-image')) {
    initProductGallery();
}

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Lazy loading for images
const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.classList.remove('lazy');
            observer.unobserve(img);
        }
    });
});

document.querySelectorAll('img[data-src]').forEach(img => {
    imageObserver.observe(img);
});
