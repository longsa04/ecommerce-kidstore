(function () {
    function getNotificationElement() {
        let el = document.getElementById('notification');
        if (!el) {
            return null;
        }
        return el;
    }

    function buildJsonHeaders() {
        const headers = { 'Content-Type': 'application/json' };
        const token = window.KIDSTORE_CSRF_TOKEN;
        if (token) {
            const headerName = window.KIDSTORE_CSRF_HEADER || 'X-Kidstore-CSRF';
            headers[headerName] = token;
        }
        return headers;
    }

    const PRODUCT_IMAGE_FALLBACK = 'https://images.pexels.com/photos/45982/pexels-photo-45982.jpeg?auto=compress&cs=tinysrgb&w=600';

    function resolveProductImage(imageUrl) {
        if (!imageUrl || typeof imageUrl !== 'string') {
            return PRODUCT_IMAGE_FALLBACK;
        }
        const trimmed = imageUrl.trim();
        if (trimmed === '') {
            return PRODUCT_IMAGE_FALLBACK;
        }
        if (/^(?:https?:)?\/\//i.test(trimmed)) {
            return trimmed;
        }
        const base = window.KIDSTORE_FRONT_PREFIX || '';
        return `${base}${trimmed.replace(/^\/+/, '')}`;
    }

    function showNotification(messageOrOptions, maybeIsError) {
        const el = getNotificationElement();
        if (!el) {
            return;
        }

        let message = '';
        let isError = Boolean(maybeIsError);
        let image = '';
        let hasImageFlag = false;
        let meta = '';

        if (typeof messageOrOptions === 'object' && messageOrOptions !== null) {
            message = messageOrOptions.message || '';
            if (typeof messageOrOptions.isError === 'boolean') {
                isError = messageOrOptions.isError;
            }
            if (Object.prototype.hasOwnProperty.call(messageOrOptions, 'image')) {
                image = messageOrOptions.image;
                hasImageFlag = true;
            }
            meta = messageOrOptions.meta || '';
        } else {
            message = String(messageOrOptions ?? '');
        }

        if (!message) {
            message = isError ? 'Something went wrong.' : 'Success!';
        }

        const resolvedImage = hasImageFlag ? resolveProductImage(image) : '';

        el.className = 'notification-toast';
        if (isError) {
            el.classList.add('notification-toast--error');
        }
        if (hasImageFlag) {
            el.classList.add('notification-toast--with-image');
        }

        el.innerHTML = '';
        if (hasImageFlag) {
            const media = document.createElement('div');
            media.className = 'notification-toast__media';
            const img = document.createElement('img');
            img.src = resolvedImage;
            img.alt = '';
            img.loading = 'lazy';
            media.appendChild(img);
            el.appendChild(media);
        }

        const body = document.createElement('div');
        body.className = 'notification-toast__body';
        const messageEl = document.createElement('p');
        messageEl.className = 'notification-toast__message';
        messageEl.textContent = message;
        body.appendChild(messageEl);

        if (meta) {
            const metaEl = document.createElement('span');
            metaEl.className = 'notification-toast__meta';
            metaEl.textContent = meta;
            body.appendChild(metaEl);
        }

        el.appendChild(body);

        el.classList.remove('show');
        // Force reflow so the animation retriggers even when the toast is shown rapidly.
        void el.offsetWidth; // eslint-disable-line no-unused-expressions
        el.classList.add('show');
        clearTimeout(el._kidstoreTimer);
        el._kidstoreTimer = setTimeout(() => {
            el.classList.remove('show');
        }, 3200);
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
                headers: buildJsonHeaders(),
                body: JSON.stringify({ productId: parseInt(productId, 10), quantity: 1 })
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        updateCartBadge(data.cartCount || 0);
                        const product = data.product || {};
                        const metaParts = [];
                        if (typeof product.price === 'number') {
                            metaParts.push(`$${product.price.toFixed(2)}`);
                        }
                        if (typeof data.itemQuantity === 'number' && data.itemQuantity > 0) {
                            metaParts.push(`In cart: ${data.itemQuantity}`);
                        }
                        showNotification({
                            message: data.message || 'Added to cart',
                            image: product.image,
                            meta: metaParts.join(' • '),
                        });
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
    window.kidstoreBuildJsonHeaders = buildJsonHeaders;
})();
