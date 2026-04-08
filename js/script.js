// Main JavaScript file for Realm

document.addEventListener('DOMContentLoaded', function () {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                performSearch(this.value);
            }
        });
    }

    const searchBtn = document.querySelector('.search-btn');
    if (searchBtn) {
        searchBtn.addEventListener('click', function () {
            const input = document.getElementById('searchInput');
            if (input) {
                performSearch(input.value);
            }
        });
    }

    // Mobile menu toggle (if needed)
    initMobileMenu();

    // Smooth scroll for anchor links
    initSmoothScroll();

    // Cookie Consent
    initCookieConsent();

    // Check cart status for all product buttons on page load
    initCartStatusCheck();
});

function initCookieConsent() {
    if (!localStorage.getItem('cookieConsent')) {
        const banner = document.createElement('div');
        banner.id = 'cookieConsentBanner';
        banner.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 20px;
            z-index: 9999;
            width: 90%;
            max-width: 500px;
            border: 1px solid #eee;
        `;

        banner.innerHTML = `
            <div style="flex: 1; font-size: 14px; color: #666;">
                <strong style="color: #333; display: block; margin-bottom: 4px;">We use cookies</strong>
                We use cookies to improve your experience. By using our site, you agree to our use of cookies.
            </div>
            <button id="acceptCookies" style="
                background: #2c3e50;
                color: white;
                border: none;
                padding: 8px 20px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 13px;
                font-weight: 600;
                white-space: nowrap;
            ">Accept</button>
        `;

        document.body.appendChild(banner);

        document.getElementById('acceptCookies').addEventListener('click', function () {
            localStorage.setItem('cookieConsent', 'true');
            banner.remove();
        });
    }
}

function performSearch(query) {
    if (query.trim() === '') {
        return;
    }

    // Redirect to products page with search query
    const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
    window.location.href = baseUrl + '/products.php?search=' + encodeURIComponent(query);
}

// Mobile menu function removed as requested.
function initMobileMenu() {
    // Logic removed.
}

function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
}

// Product page image gallery
function changeMainImage(src, element) {
    const mainImage = document.getElementById('mainImage');
    if (mainImage) {
        mainImage.src = src;

        // Update active thumbnail
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
        });
        element.classList.add('active');
    }
}

// Form validation helper
function validateForm(formElement) {
    const inputs = formElement.querySelectorAll('[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.style.borderColor = '#c53940';
        } else {
            input.style.borderColor = '#ddd';
        }
    });

    return isValid;
}

// Cart Quantity Management
function initCartStatusCheck() {
    // Check all add-to-cart buttons and replace with quantity selector if already in cart
    const addButtons = document.querySelectorAll('.add-to-cart-btn[onclick*="addToCart"]');
    addButtons.forEach(button => {
        const onclickAttr = button.getAttribute('onclick');
        const match = onclickAttr.match(/addToCart\((\d+)/);

        if (match) {
            const productId = parseInt(match[1]);
            checkProductInCart(productId, button);
        }
    });
}

function checkProductInCart(productId, buttonElement) {
    const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
    fetch(baseUrl + '/api/check_cart_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.in_cart) {
                replaceWithQuantitySelector(buttonElement, productId, data.cart_item_id, data.quantity);
            }
        })
        .catch(err => console.error('Cart status check failed:', err));
}

function replaceWithQuantitySelector(buttonElement, productId, cartItemId, quantity) {
    const selector = document.createElement('div');
    selector.className = 'quantity-selector';
    selector.dataset.cartItemId = cartItemId;
    selector.dataset.quantity = quantity;

    selector.innerHTML = `
        <div class="qty-btn minus">-</div>
        <div class="qty-display">${quantity}</div>
        <div class="qty-btn plus">+</div>
    `;

    // Add event listeners
    selector.querySelector('.minus').addEventListener('click', () => {
        updateQuantity(cartItemId, quantity - 1, selector, productId);
    });

    selector.querySelector('.plus').addEventListener('click', () => {
        updateQuantity(cartItemId, quantity + 1, selector, productId);
    });

    buttonElement.parentElement.replaceChild(selector, buttonElement);
}

function updateQuantity(cartItemId, newQuantity, selectorElement, productId) {
    if (newQuantity < 0) return;

    const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
    fetch(baseUrl + '/api/update_cart_quantity.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cart_item_id: cartItemId, quantity: newQuantity })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (newQuantity === 0) {
                    // Remove selector, show add to cart button again
                    const addBtn = document.createElement('button');
                    addBtn.className = 'add-to-cart-btn';
                    addBtn.textContent = 'Add To Cart';
                    addBtn.setAttribute('onclick', `addToCart(${productId}, event)`);
                    selectorElement.parentElement.replaceChild(addBtn, selectorElement);
                } else {
                    // Update display
                    selectorElement.dataset.quantity = newQuantity;
                    selectorElement.querySelector('.qty-display').textContent = newQuantity;

                    // Update event listeners
                    selectorElement.querySelector('.minus').onclick = () => updateQuantity(cartItemId, newQuantity - 1, selectorElement, productId);
                    selectorElement.querySelector('.plus').onclick = () => updateQuantity(cartItemId, newQuantity + 1, selectorElement, productId);
                }

                // Update header cart
                const cartBadge = document.querySelector('.cart-badge');
                if (cartBadge) {
                    if (data.cart_count > 0) {
                        cartBadge.textContent = data.cart_count;
                    } else {
                        cartBadge.remove();
                    }
                }

                const cartTotal = document.querySelector('.cart-total');
                if (cartTotal) {
                    cartTotal.textContent = 'Ush ' + Number(data.cart_total).toLocaleString('en-US');
                }
            }
        })
        .catch(err => console.error('Update quantity failed:', err));
}

// Global Add to Cart Function - used across all pages
function addToCart(productId, event) {
    event.preventDefault();
    event.stopPropagation();

    const btn = event.target;
    const originalText = btn.textContent;

    // Get base URL dynamically
    const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));

    // First, check if product is already in cart
    fetch(baseUrl + '/api/check_cart_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId })
    })
        .then(response => response.json())
        .then(checkData => {
            if (checkData.success && checkData.in_cart) {
                // Product already in cart - replace button with quantity selector
                replaceWithQuantitySelector(btn, productId, checkData.cart_item_id, checkData.quantity);
                return;
            }

            // Product not in cart - proceed to add it
            btn.textContent = 'Adding...';
            btn.classList.add('adding');

            fetch(baseUrl + '/api/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        btn.textContent = 'Added!';

                        // Update cart count
                        const cartBadge = document.querySelector('.cart-badge');
                        if (cartBadge) {
                            cartBadge.textContent = data.cart_count;
                        } else if (data.cart_count > 0) {
                            const cartIconWrapper = document.querySelector('.cart-icon-wrapper');
                            if (cartIconWrapper) {
                                const badge = document.createElement('span');
                                badge.className = 'cart-badge';
                                badge.textContent = data.cart_count;
                                cartIconWrapper.appendChild(badge);
                            }
                        }

                        // Update cart total
                        const cartTotal = document.querySelector('.cart-total');
                        if (cartTotal && data.cart_total !== undefined) {
                            cartTotal.textContent = 'Ush ' + Number(data.cart_total).toLocaleString('en-US');
                        }

                        // Check cart status again to get cart_item_id and replace with quantity selector
                        setTimeout(() => {
                            fetch(baseUrl + '/api/check_cart_status.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ product_id: productId })
                            })
                                .then(r => r.json())
                                .then(statusData => {
                                    if (statusData.success && statusData.in_cart) {
                                        replaceWithQuantitySelector(btn, productId, statusData.cart_item_id, statusData.quantity);
                                    }
                                });
                        }, 800);
                    } else {
                        console.error('API Error:', data);
                        btn.textContent = 'Error!';
                        alert('Failed to add to cart: ' + (data.message || 'Unknown error'));
                        setTimeout(() => {
                            btn.textContent = originalText;
                            btn.classList.remove('adding');
                        }, 1500);
                    }
                })
                .catch(error => {
                    console.error('Network/Script Error:', error);
                    btn.textContent = 'Error!';
                    alert('System Error: ' + error.message);
                    setTimeout(() => {
                        btn.textContent = originalText;
                        btn.classList.remove('adding');
                    }, 1500);
                });
        })
        .catch(error => {
            console.error('Cart Status Check Error:', error);
            btn.textContent = 'Error!';
            alert('Cart Check Error: ' + error.message);
            setTimeout(() => {
                btn.textContent = originalText;
            }, 1500);
        });
}
