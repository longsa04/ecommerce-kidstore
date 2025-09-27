(function () {
    function getNotificationElement() {
        let el = document.getElementById('notification');
        if (!el) {
            return null;
        }
        return el;
    }

    function showNotification(message, isError) {
        const el = getNotificationElement();
        if (!el) {
            return;
        }
        el.textContent = message;
        el.style.background = isError
            ? 'linear-gradient(135deg, #f44336, #e53935)'
            : 'linear-gradient(135deg, #4caf50, #45a049)';
        el.classList.add('show');
        clearTimeout(el._kidstoreTimer);
        el._kidstoreTimer = setTimeout(() => {
            el.classList.remove('show');
        }, 2800);
    }

    function updateCartBadge(count) {
        const badge = document.querySelector('.cart-count');
        if (!badge) {
            return;
        }
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    }

    function bindAddToCartButton(button) {
        if (!button || button.dataset.cartBound === 'true') {
            return;
        }
        button.dataset.cartBound = 'true';
        button.addEventListener('click', () => {
            if (!button.dataset.productId) {
                return;
            }

            if (button.hasAttribute('disabled')) {
                showNotification('This item is currently out of stock.', true);
                return;
            }

            const productId = button.dataset.productId;

            const originalLabel = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            button.disabled = true;

            const basePath = window.KIDSTORE_FRONT_PREFIX || '';
            fetch(`${basePath}actions/add_to_cart.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ productId: parseInt(productId, 10), quantity: 1 })
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        updateCartBadge(data.cartCount || 0);
                        showNotification(data.message || 'Added to cart', false);
                        button.innerHTML = '<i class="fas fa-check"></i> Added';
                        button.style.background = 'linear-gradient(135deg, #4caf50, #43a047)';
                    } else {
                        showNotification(data.message || 'Unable to add to cart', true);
                        button.innerHTML = originalLabel;
                    }
                })
                .catch(() => {
                    showNotification('Something went wrong. Please try again.', true);
                    button.innerHTML = originalLabel;
                })
                .finally(() => {
                    setTimeout(() => {
                        button.innerHTML = originalLabel;
                        button.style.background = '';
                        button.disabled = false;
                    }, 1800);
                });
        });
    }

    function setupAddToCartButtons() {
        document.querySelectorAll('.add-to-cart[data-product-id]').forEach(bindAddToCartButton);
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupAddToCartButtons();

        const scrollToTopButton = document.querySelector('.scroll-to-top');
        if (scrollToTopButton) {
            window.addEventListener('scroll', function () {
                if (window.scrollY > 200) {
                    scrollToTopButton.style.display = 'block';
                } else {
                    scrollToTopButton.style.display = 'none';
                }
            });
            scrollToTopButton.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    });

    window.kidstoreShowNotification = showNotification;
    window.kidstoreUpdateCartBadge = updateCartBadge;
    window.kidstoreSetupAddToCartButtons = setupAddToCartButtons;
})();
