(function () {
    'use strict';

    const siteMenu = window.SITEMAP_DATA || {};
    const container = document.getElementById('menuContainer');
    const backBtn = document.getElementById('backBtn');
    const titleEl = document.getElementById('menuTitle');

    if (!container || !backBtn || !titleEl) {
        return;
    }

    const historyStack = [];

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
        backBtn.hidden = historyStack.length === 0;
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

    container.addEventListener('click', function (event) {
        const button = event.target.closest('button.feature-card');

        if (!button || button.disabled) {
            return;
        }

        if (button.dataset.type === 'category') {
            historyStack.push({ level: 'categories' });
            renderMenu('subcategories', button.dataset.key, null, true);
        } else if (button.dataset.type === 'subcategory') {
            historyStack.push({ level: 'subcategories', key: button.dataset.parent });
            renderMenu('items', button.dataset.key, button.dataset.parent, true);
        }
    });

    backBtn.addEventListener('click', function () {
        const previous = historyStack.pop();

        if (!previous) {
            renderMenu('categories', null, null, true);
            return;
        }

        renderMenu(previous.level, previous.key, previous.parent, true);
    });

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

    renderMenu('categories');
    animateFloatingBackground();
})();
