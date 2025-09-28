(function () {
    const root = document.querySelector('[data-shop-root]');
    if (!root) {
        return;
    }

    const form = root.querySelector('[data-shop-form]');
    const searchInput = form ? form.querySelector('[data-search-input]') : null;
    const sortSelect = form ? form.querySelector('[data-sort-select]') : null;
    const productsGrid = root.querySelector('[data-products-grid]');
    const loaderGrid = root.querySelector('[data-shop-loader]');
    const emptyState = root.querySelector('[data-empty-state]');
    const pagination = root.querySelector('[data-pagination]');
    const feedback = root.querySelector('[data-feedback]');
    const resultsCount = root.querySelector('[data-results-count]');
    const filterPills = root.querySelector('[data-active-filters]');
    const heroTitle = document.querySelector('[data-hero-title]');
    const heroSubtitle = document.querySelector('[data-hero-subtitle]');
    const clearFiltersTrigger = root.querySelector('[data-clear-filters]');
    const pageInput = form ? form.querySelector('[data-filter-input="page"]') : null;
    const categoryInput = form ? form.querySelector('[data-filter-input="category"]') : null;
    const availabilityInput = form ? form.querySelector('[data-filter-input="availability"]') : null;

    const endpoint = root.dataset.searchEndpoint || '';
    const productUrlBase = root.dataset.productUrl || '';
    const pageUrl = root.dataset.pageUrl || 'shop.php';

    let config = {};
    const configEl = document.getElementById('shop-config');
    if (configEl) {
        try {
            config = JSON.parse(configEl.textContent || '{}');
        } catch (error) {
            console.warn('Unable to parse shop configuration payload.', error);
            config = {};
        }
    }

    const availabilityLabels = config.availabilityLabels || {};
    const formatter = new Intl.NumberFormat();

    function applyOverrides(params, overrides) {
        Object.entries(overrides).forEach(([key, value]) => {
            if (value === null) {
                params.delete(key);
            } else if (value !== undefined) {
                params.set(key, String(value));
            }
        });
        return params;
    }

    function buildParams(overrides = {}) {
        if (!form) {
            return new URLSearchParams();
        }
        const formData = new FormData(form);
        const params = new URLSearchParams();
        formData.forEach((value, key) => {
            if (value === '') {
                return;
            }
            params.append(key, value.toString());
        });
        return applyOverrides(params, overrides);
    }

    function toggleLoader(isLoading) {
        if (!productsGrid || !loaderGrid) {
            return;
        }
        root.classList.toggle('is-loading', isLoading);
        productsGrid.setAttribute('aria-busy', isLoading ? 'true' : 'false');
        loaderGrid.hidden = !isLoading;
    }

    function setFeedback(message) {
        if (!feedback) {
            return;
        }
        feedback.textContent = message || '';
    }

    function formatResultsCount(meta) {
        const total = formatter.format(meta.totalProducts);
        const label = meta.totalProducts === 1 ? 'product' : 'products';
        return `<strong>${total}</strong> ${label} found`;
    }

    function renderResultsCount(meta) {
        if (!resultsCount) {
            return;
        }
        resultsCount.innerHTML = formatResultsCount(meta);
    }

    function renderFeedback(meta) {
        if (!feedback) {
            return;
        }
        if (meta.totalProducts === 0) {
            setFeedback('No products match your filters yet.');
            return;
        }
        const start = (meta.page - 1) * meta.perPage + 1;
        const end = Math.min(meta.totalProducts, start + meta.perPage - 1);
        const rangeText = `${formatter.format(start)} – ${formatter.format(end)}`;
        const totalText = formatter.format(meta.totalProducts);
        setFeedback(`Showing ${rangeText} of ${totalText} items`);
    }

    function createBadgeElement(text, modifier) {
        const badge = document.createElement('span');
        badge.className = modifier ? `badge ${modifier}` : 'badge';
        badge.textContent = text;
        return badge;
    }

    function createProductCard(product) {
        const article = document.createElement('article');
        article.className = 'product-card';
        article.dataset.productId = String(product.id);

        if (!product.inStock) {
            article.appendChild(createBadgeElement('Out of Stock', 'out-of-stock'));
        } else if (product.category && product.category.name) {
            article.appendChild(createBadgeElement(product.category.name));
        }

        const link = document.createElement('a');
        link.className = 'product-thumb';
        link.href = product.url || `${productUrlBase}?id=${product.id}`;
        link.dataset.productLink = 'true';

        const image = document.createElement('img');
        image.src = product.image;
        image.alt = product.name;
        image.loading = 'lazy';
        link.appendChild(image);
        article.appendChild(link);

        const heading = document.createElement('h3');
        const headingLink = document.createElement('a');
        headingLink.href = product.url || `${productUrlBase}?id=${product.id}`;
        headingLink.textContent = product.name;
        heading.appendChild(headingLink);
        article.appendChild(heading);

        const description = document.createElement('p');
        description.textContent = product.description;
        article.appendChild(description);

        const meta = document.createElement('div');
        meta.className = 'product-meta';
        const priceSpan = document.createElement('span');
        priceSpan.textContent = product.priceFormatted;
        const stockSpan = document.createElement('span');
        stockSpan.textContent = product.inStock ? 'In stock' : 'Sold out';
        meta.appendChild(priceSpan);
        meta.appendChild(stockSpan);
        article.appendChild(meta);

        const button = document.createElement('button');
        button.className = 'add-to-cart';
        button.dataset.productId = String(product.id);
        if (!product.inStock) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-bell"></i> Notify Me';
        } else {
            button.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
        }
        article.appendChild(button);

        return article;
    }

    function renderProducts(products) {
        if (!productsGrid) {
            return;
        }
        productsGrid.innerHTML = '';
        if (!products || products.length === 0) {
            return;
        }
        const fragment = document.createDocumentFragment();
        products.forEach((product) => {
            fragment.appendChild(createProductCard(product));
        });
        productsGrid.appendChild(fragment);
        if (typeof window.kidstoreSetupAddToCartButtons === 'function') {
            window.kidstoreSetupAddToCartButtons();
        }
    }

    function renderEmptyState(hasProducts) {
        if (!emptyState) {
            return;
        }
        emptyState.hidden = hasProducts;
    }

    function renderPagination(meta) {
        if (!pagination) {
            return;
        }
        pagination.innerHTML = '';
        if (meta.totalPages <= 1) {
            pagination.hidden = true;
            return;
        }
        pagination.hidden = false;
        for (let i = 1; i <= meta.totalPages; i += 1) {
            if (i === meta.page) {
                const span = document.createElement('span');
                span.className = 'current';
                span.setAttribute('aria-current', 'page');
                span.textContent = String(i);
                pagination.appendChild(span);
            } else {
                const anchor = document.createElement('a');
                anchor.href = `${pageUrl}?page=${i}`;
                anchor.dataset.pageLink = 'true';
                anchor.dataset.page = String(i);
                anchor.textContent = String(i);
                pagination.appendChild(anchor);
            }
        }
    }

    function setFormValues(filters, meta) {
        if (!form) {
            return;
        }
        if (categoryInput) {
            categoryInput.value = filters.category && filters.category.id ? filters.category.id : '';
        }
        if (availabilityInput) {
            availabilityInput.value = filters.availability || '';
        }
        if (pageInput) {
            pageInput.value = String(meta.page);
        }
        if (searchInput) {
            searchInput.value = filters.search || '';
        }
        if (sortSelect && filters.sort) {
            sortSelect.value = filters.sort;
        }
    }

    function generateHref(overrides) {
        const params = buildParams(overrides);
        const query = params.toString();
        return query ? `${pageUrl}?${query}` : pageUrl;
    }

    function renderFilterPills(filters) {
        if (!filterPills) {
            return;
        }
        filterPills.innerHTML = '';
        const fragment = document.createDocumentFragment();
        const active = [];

        if (filters.category && filters.category.name) {
            active.push({
                label: 'Category',
                value: filters.category.name,
                overrides: { category: null, page: 1 },
                type: 'category',
            });
        }
        if (filters.availability) {
            const label = availabilityLabels[filters.availability] || 'Availability';
            active.push({
                label: 'Availability',
                value: label,
                overrides: { availability: null, page: 1 },
                type: 'availability',
            });
        }
        if (filters.search) {
            active.push({
                label: 'Search',
                value: filters.search,
                overrides: { search: null, page: 1 },
                type: 'search',
            });
        }

        active.forEach((item) => {
            const anchor = document.createElement('a');
            anchor.className = 'filter-pill';
            anchor.href = generateHref(item.overrides);
            anchor.dataset.filterLink = 'true';
            anchor.dataset.filterType = item.type;
            anchor.dataset.filterValue = '';

            const labelSpan = document.createElement('span');
            labelSpan.textContent = `${item.label}:`;
            const valueStrong = document.createElement('strong');
            valueStrong.textContent = item.value;
            const icon = document.createElement('i');
            icon.className = 'fas fa-times';
            icon.setAttribute('aria-hidden', 'true');
            const srOnly = document.createElement('span');
            srOnly.className = 'sr-only';
            srOnly.textContent = `Remove ${item.label} filter`;

            anchor.append(labelSpan, valueStrong, icon, srOnly);
            fragment.appendChild(anchor);
        });

        if (active.length > 0) {
            const clear = document.createElement('a');
            clear.className = 'filter-pill filter-pill--clear';
            clear.href = generateHref({ category: null, availability: null, search: null, page: 1, sort: 'newest' });
            clear.dataset.filterLink = 'true';
            clear.dataset.filterType = 'reset';
            clear.dataset.filterValue = '';
            const icon = document.createElement('i');
            icon.className = 'fas fa-broom';
            const text = document.createTextNode(' Clear all');
            clear.append(icon, text);
            fragment.appendChild(clear);
        }

        filterPills.appendChild(fragment);
        filterPills.hidden = active.length === 0;
    }

    function updateHero(hero) {
        const defaultSubtitle = config.copy && config.copy.default ? config.copy.default : '';
        if (heroTitle) {
            heroTitle.textContent = hero.title || 'Shop Our Collection';
        }
        if (heroSubtitle) {
            heroSubtitle.textContent = hero.subtitle || defaultSubtitle;
        }
    }

    function updateHistory(meta) {
        const params = buildParams({ page: meta.page });
        const query = params.toString();
        const newUrl = query ? `${pageUrl}?${query}` : pageUrl;
        window.history.replaceState({}, '', newUrl);
    }

    function applyResponse(data, { updateHistory: shouldUpdateHistory = true } = {}) {
        const { products, meta, filters, hero } = data;
        const productList = Array.isArray(products) ? products : [];
        setFormValues(filters, meta);
        renderProducts(productList);
        renderEmptyState(productList.length > 0);
        renderPagination(meta);
        renderResultsCount(meta);
        renderFeedback(meta);
        renderFilterPills(filters);
        updateHero(hero || {});
        root.dataset.currentPage = String(meta.page);
        root.dataset.totalPages = String(meta.totalPages);
        root.dataset.totalProducts = String(meta.totalProducts);
        if (shouldUpdateHistory) {
            updateHistory(meta);
        }
    }

    function handleError(error) {
        console.error(error);
        setFeedback('Something went wrong. Please try again.');
        if (typeof window.kidstoreShowNotification === 'function') {
            window.kidstoreShowNotification('Unable to update products. Please try again.', true);
        }
    }

    async function fetchProducts(params, options = {}) {
        if (!endpoint) {
            return;
        }
        toggleLoader(true);
        try {
            const response = await fetch(`${endpoint}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }
            const payload = await response.json();
            if (!payload.success) {
                throw new Error(payload.message || 'Unable to fetch products');
            }
            applyResponse(payload, options);
        } catch (error) {
            handleError(error);
        } finally {
            toggleLoader(false);
        }
    }

    function requestUpdate(overrides = {}) {
        if (pageInput) {
            pageInput.value = String(overrides.page || 1);
        }
        const params = buildParams({ ...overrides, page: overrides.page || 1 });
        fetchProducts(params);
    }

    function handleFormSubmit(event) {
        event.preventDefault();
        requestUpdate({ page: 1 });
    }

    function debounce(fn, delay) {
        let timer;
        return function debounced(...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    const debouncedSearch = debounce(() => {
        requestUpdate({ search: searchInput ? searchInput.value : '', page: 1 });
    }, 320);

    if (form) {
        form.addEventListener('submit', handleFormSubmit);
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            if (!searchInput) {
                return;
            }
            debouncedSearch();
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            requestUpdate({ sort: sortSelect.value, page: 1 });
        });
    }

    if (pagination) {
        pagination.addEventListener('click', (event) => {
            const target = event.target.closest('[data-page-link]');
            if (!target) {
                return;
            }
            event.preventDefault();
            const nextPage = parseInt(target.dataset.page || '1', 10);
            if (Number.isNaN(nextPage)) {
                return;
            }
            if (pageInput) {
                pageInput.value = String(nextPage);
            }
            const params = buildParams({ page: nextPage });
            fetchProducts(params);
        });
    }

    root.addEventListener('click', (event) => {
        const anchor = event.target.closest('[data-filter-link]');
        if (!anchor) {
            return;
        }
        event.preventDefault();
        const { filterType } = anchor.dataset;
        let overrides = { page: 1 };
        switch (filterType) {
            case 'category':
                overrides.category = anchor.dataset.filterValue ? anchor.dataset.filterValue : null;
                break;
            case 'availability':
                overrides.availability = anchor.dataset.filterValue ? anchor.dataset.filterValue : null;
                break;
            case 'search':
                overrides.search = null;
                break;
            case 'reset':
                overrides = { category: null, availability: null, search: null, sort: 'newest', page: 1 };
                if (sortSelect) {
                    sortSelect.value = 'newest';
                }
                break;
            default:
                break;
        }
        if (filterType === 'category' && categoryInput) {
            categoryInput.value = overrides.category || '';
        }
        if (filterType === 'availability' && availabilityInput) {
            availabilityInput.value = overrides.availability || '';
        }
        requestUpdate(overrides);
    });

    if (clearFiltersTrigger) {
        clearFiltersTrigger.addEventListener('click', (event) => {
            event.preventDefault();
            if (sortSelect) {
                sortSelect.value = 'newest';
            }
            requestUpdate({ category: null, availability: null, search: null, sort: 'newest', page: 1 });
        });
    }
})();

