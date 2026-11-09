(function () {
    'use strict';

    const siteMenu = window.SITEMAP_DATA || {};
    const container = document.getElementById('menuContainer');
    const backBtn = document.getElementById('backBtn');
    const titleEl = document.getElementById('menuTitle');

    if (!container || !backBtn || !titleEl) {
        return;
    }

    let currentState = { level: 'categories' };

    function itemUrl(item) {
        return item && typeof item.url === 'string' ? item.url.trim() : '';
    }

    function typeClass(url) {
        if (!url) {
            return '';
        }
        if (url.includes('/proxy_user.php?tp=')) {
            return 'item-proxy';
        }
        if (url.includes('/doku.php/')) {
            return 'item-doku';
        }
        if (url.includes('cours-reseaux.fr')) {
            return 'item-site';
        }
        return 'item-external';
    }

    function hasKey(object, key) {
        return Object.prototype.hasOwnProperty.call(object || {}, key);
    }

    function sameState(a, b) {
        return a.level === b.level && a.key === b.key && a.parent === b.parent;
    }

    function stateToHash(state) {
        if (state.level === 'subcategories') {
            return '#' + encodeURIComponent(state.key);
        }
        if (state.level === 'items') {
            return '#' + encodeURIComponent(state.parent) + '/' + encodeURIComponent(state.key);
        }
        return '';
    }

    function parseHash(hash) {
        const raw = String(hash || '').replace(/^#/, '');

        if (!raw) {
            return { level: 'categories' };
        }

        let parts;

        try {
            parts = raw.split('/').map(function (part) {
                return decodeURIComponent(part);
            });
        } catch (error) {
            return null;
        }

        if (parts.length === 1) {
            return hasKey(siteMenu, parts[0]) ? { level: 'subcategories', key: parts[0] } : null;
        }

        if (parts.length === 2) {
            const subcategories = (siteMenu[parts[0]] || {}).subcategories || {};

            return hasKey(siteMenu, parts[0]) && hasKey(subcategories, parts[1])
                ? { level: 'items', parent: parts[0], key: parts[1] }
                : null;
        }

        return null;
    }

    function makeButton(label, className, dataset, disabled) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = className;
        button.textContent = label;

        Object.keys(dataset || {}).forEach(function (key) {
            button.dataset[key] = dataset[key];
        });

        if (disabled) {
            button.disabled = true;
            button.setAttribute('aria-disabled', 'true');
        }

        return button;
    }

    function makeItem(item) {
        const url = itemUrl(item);
        const label = item && item.label ? item.label : 'Ressource indisponible';

        if (!url) {
            return makeButton(label, 'feature-card-disabled', {}, true);
        }

        const link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.className = 'feature-card ' + typeClass(url);
        link.textContent = label;
        return link;
    }

    function categoryIsClickable(category) {
        const entry = siteMenu[category] || {};
        const subcategories = entry.subcategories || {};
        const items = entry.items || [];

        const hasClickableSubcategory = Object.keys(subcategories).some(function (subcategory) {
            const subEntry = subcategories[subcategory] || {};
            const subItems = subEntry.items || [];
            return subItems.some(function (item) {
                return itemUrl(item) !== '';
            });
        });

        return hasClickableSubcategory || items.some(function (item) {
            return itemUrl(item) !== '';
        });
    }

    function subcategoryIsClickable(category, subcategory) {
        const entry = siteMenu[category] || {};
        const subcategories = entry.subcategories || {};
        const subEntry = subcategories[subcategory] || {};
        const items = subEntry.items || [];

        return items.some(function (item) {
            return itemUrl(item) !== '';
        });
    }

    function renderCategories() {
        Object.keys(siteMenu).forEach(function (category) {
            const clickable = categoryIsClickable(category);
            container.appendChild(makeButton(
                category,
                clickable ? 'feature-card' : 'feature-card-disabled',
                { type: 'category', key: category },
                !clickable
            ));
        });
    }

    function renderSubcategories(category) {
        const entry = siteMenu[category] || {};
        const subcategories = entry.subcategories || {};

        Object.keys(subcategories).forEach(function (subcategory) {
            const clickable = subcategoryIsClickable(category, subcategory);
            container.appendChild(makeButton(
                subcategory,
                clickable ? 'feature-card' : 'feature-card-disabled',
                { type: 'subcategory', key: subcategory, parent: category },
                !clickable
            ));
        });

        (entry.items || []).forEach(function (item) {
            container.appendChild(makeItem(item));
        });
    }

    function renderItems(category, subcategory) {
        const entry = siteMenu[category] || {};
        const subcategories = entry.subcategories || {};
        const subEntry = subcategories[subcategory] || {};

        (subEntry.items || []).forEach(function (item) {
            container.appendChild(makeItem(item));
        });
    }

    function updateTitle(level, key, parent) {
        if (level === 'categories') {
            titleEl.textContent = 'Catégories';
        } else if (level === 'subcategories') {
            titleEl.textContent = key;
        } else {
            titleEl.textContent = parent + ' › ' + key;
        }
    }

    function renderEmptyState() {
        const message = document.createElement('p');
        message.className = 'sitemap-empty';
        message.textContent = 'Aucune ressource disponible à ce niveau.';
        container.appendChild(message);
    }

    function focusFirstControl() {
        const target = container.querySelector('a.feature-card, button.feature-card:not(:disabled)');

        if (target) {
            target.focus({ preventScroll: true });
        } else if (!backBtn.hidden) {
            backBtn.focus({ preventScroll: true });
        }
    }

    function renderMenu(level, key, parent, moveFocus) {
        container.innerHTML = '';
        backBtn.hidden = level === 'categories';
        updateTitle(level, key, parent);

        if (level === 'categories') {
            renderCategories();
        } else if (level === 'subcategories') {
            renderSubcategories(key);
        } else if (level === 'items') {
            renderItems(parent, key);
        }

        if (!container.children.length) {
            renderEmptyState();
        }

        if (moveFocus) {
            focusFirstControl();
        }
    }

    function applyState(state, moveFocus) {
        currentState = state;
        renderMenu(state.level, state.key, state.parent, moveFocus);
    }

    function navigate(state) {
        if (sameState(state, currentState)) {
            return;
        }

        const hash = stateToHash(state);
        const url = window.location.pathname + window.location.search + hash;

        window.history.pushState(null, '', url);
        applyState(state, true);
    }

    function parentState(state) {
        if (state.level === 'items') {
            return { level: 'subcategories', key: state.parent };
        }

        return { level: 'categories' };
    }

    function syncFromLocation() {
        const state = parseHash(window.location.hash);

        if (!state) {
            window.history.replaceState(null, '', window.location.pathname + window.location.search);
            applyState({ level: 'categories' }, true);
            return;
        }

        if (!sameState(state, currentState)) {
            applyState(state, true);
        }
    }

    container.addEventListener('click', function (event) {
        const button = event.target.closest('button.feature-card');

        if (!button || button.disabled) {
            return;
        }

        if (button.dataset.type === 'category') {
            navigate({ level: 'subcategories', key: button.dataset.key });
        } else if (button.dataset.type === 'subcategory') {
            navigate({ level: 'items', parent: button.dataset.parent, key: button.dataset.key });
        }
    });

    backBtn.addEventListener('click', function () {
        navigate(parentState(currentState));
    });

    window.addEventListener('popstate', syncFromLocation);
    window.addEventListener('hashchange', syncFromLocation);

    function animateFloatingBackground() {
        const floats = [document.getElementById('float1'), document.getElementById('float2')];

        if (floats.every(function (float) { return !float; })) {
            return;
        }

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        let t = 0;

        function animate() {
            t += 0.006;

            floats.forEach(function (float, index) {
                if (!float) {
                    return;
                }

                const x = Math.sin(t * (0.6 + index * 0.2)) * 12;
                const y = Math.cos(t * (0.4 + index * 0.12)) * 8;
                float.style.transform = 'translate(' + x + 'px, ' + y + 'px) rotate(' + (t * 6 * (index + 1)) + 'deg)';
            });

            requestAnimationFrame(animate);
        }

        requestAnimationFrame(animate);
    }

    const initialState = parseHash(window.location.hash);

    if (initialState) {
        applyState(initialState, false);
    } else {
        window.history.replaceState(null, '', window.location.pathname + window.location.search);
        applyState({ level: 'categories' }, false);
    }

    animateFloatingBackground();
})();
