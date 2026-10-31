(function () {
    'use strict';

    /* Tools menu: one button, works the same on phone and desktop. */
    const toggle = document.querySelector('.menu-toggle');
    const menu = document.getElementById('toolMenu');
    if (toggle && menu) {
        const setOpen = (open) => {
            toggle.setAttribute('aria-expanded', String(open));
            menu.hidden = !open;
            document.body.classList.toggle('menu-open', open);
        };
        toggle.addEventListener('click', () => setOpen(menu.hidden));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !menu.hidden) {
                setOpen(false);
                toggle.focus();
            }
        });
        document.addEventListener('click', (event) => {
            if (!menu.hidden && !menu.contains(event.target) && !toggle.contains(event.target)) {
                setOpen(false);
            }
        });
    }

    /* Prices in the top bar. */
    const ticker = document.querySelector('[data-prices]');
    if (ticker) {
        const show = (symbol, text) => {
            const slot = ticker.querySelector(`[data-symbol="${symbol}"] b`);
            if (!slot) return;
            slot.textContent = text;
            slot.classList.remove('skeleton', 'skeleton-price');
            slot.removeAttribute('aria-label');
        };
        fetch('api/prices.php')
            .then((response) => {
                if (!response.ok) throw new Error('Price request failed');
                return response.json();
            })
            .then((payload) => {
                const prices = Array.isArray(payload.data) ? payload.data : [];
                ['ZNN', 'QSR'].forEach((symbol) => {
                    const item = prices.find((price) => price.symbol === symbol);
                    const value = item ? Number(item.price) : NaN;
                    show(symbol, Number.isFinite(value) && value > 0
                        ? '$' + value.toLocaleString('en-US', { maximumSignificantDigits: 5 })
                        : '–');
                });
                ticker.title = prices.some((item) => item.source === 'stale-cache')
                    ? 'Last available prices; the price provider could not be refreshed.'
                    : 'USD prices. ZNN may be calculated from the QSR/ZNN market.';
            })
            .catch(() => {
                show('ZNN', '–');
                show('QSR', '–');
                ticker.title = 'The price provider is currently unavailable.';
            });
    }

    /* Current momentum height in the footer, refreshed every 10 seconds while the page is visible. */
    const momentum = document.querySelector('[data-momentum] b');
    if (momentum) {
        const refresh = () => {
            if (document.hidden) return;
            fetch('/api/momentum.php', { cache: 'no-store' })
                .then((response) => {
                    if (!response.ok) throw new Error('Momentum request failed');
                    return response.json();
                })
                .then((payload) => {
                    const height = Number(payload.height);
                    if (!Number.isFinite(height) || height <= 0) throw new Error('No height');
                    momentum.textContent = height.toLocaleString('en-US');
                    momentum.classList.remove('skeleton', 'skeleton-price');
                    momentum.removeAttribute('aria-label');
                    if (payload.timestamp) {
                        momentum.closest('[data-momentum]').title = 'Momentum height, produced ' + new Date(payload.timestamp * 1000).toISOString().replace('T', ' ').slice(0, 19) + ' UTC';
                    }
                })
                .catch(() => {
                    if (momentum.classList.contains('skeleton')) {
                        momentum.textContent = '–';
                        momentum.classList.remove('skeleton', 'skeleton-price');
                    }
                });
        };
        refresh();
        setInterval(refresh, 10000);
        document.addEventListener('visibilitychange', refresh);
    }

    /* Lookups: leave empty fields out of the link, and show placeholders while the result loads. */
    document.querySelectorAll('.lookup-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented) return;
            form.querySelectorAll('input[name], select[name]').forEach((field) => {
                if (field.value.trim() === '' && !field.required) field.disabled = true;
            });
            showLoading(form);
        });
    });

    function showLoading(form) {
        const button = form.querySelector('.lookup-btn');
        if (button) {
            button.classList.add('is-loading');
            button.setAttribute('aria-busy', 'true');
            const label = button.querySelector('span');
            if (label) label.textContent = 'Loading';
        }
        const lookup = form.closest('.lookup');
        if (!lookup) return;
        let sibling = lookup.nextElementSibling;
        while (sibling) {
            sibling.hidden = true;
            sibling = sibling.nextElementSibling;
        }
        if (!document.querySelector('.loading-placeholder')) {
            const placeholder = document.createElement('div');
            placeholder.className = 'loading-placeholder';
            placeholder.setAttribute('aria-label', 'Loading results');
            placeholder.innerHTML = '<span class="skeleton skeleton-line skeleton-title"></span>'
                + '<span class="skeleton skeleton-line"></span>'.repeat(6);
            lookup.after(placeholder);
        }
    }

    /* Coming back with the browser's Back button shows the page as it was, not the loading state. */
    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) return;
        document.querySelectorAll('.loading-placeholder').forEach((node) => node.remove());
        document.querySelectorAll('.lookup ~ [hidden]').forEach((node) => { node.hidden = false; });
        document.querySelectorAll('.lookup-form [disabled]').forEach((field) => { field.disabled = false; });
        document.querySelectorAll('.lookup-btn.is-loading').forEach((button) => {
            button.classList.remove('is-loading');
            button.removeAttribute('aria-busy');
            const label = button.querySelector('span');
            if (label) label.textContent = 'Look up';
        });
    });

    /* Give each cell the name of its column, so tables can stack into rows on phones. */
    const stackTables = [];
    document.querySelectorAll('main table').forEach((table) => {
        const headers = Array.from(table.querySelectorAll('thead th')).map((th) => th.textContent.trim());
        if (!headers.length) return;

        /* Right-align columns that only hold numbers, so amounts line up. */
        const rows = Array.from(table.querySelectorAll('tbody tr')).filter((row) => row.children.length === headers.length);
        headers.forEach((_, index) => {
            if (index === 0) return; /* the first column names the row (epoch, type, address) */
            const values = rows.map((row) => row.children[index].textContent.trim()).filter(Boolean);
            /* Real amounts only: long digit strings such as an all-zero hash must still wrap. */
            if (values.length && values.every((value) => value.length <= 32 && /^-?[\d.,]+$/.test(value))) {
                table.querySelectorAll(`tr > :nth-child(${index + 1})`).forEach((cell) => cell.classList.add('num'));
            }
        });

        table.querySelectorAll('tbody tr').forEach((row) => {
            const cells = Array.from(row.children);
            if (cells.length === 1 && headers.length > 1) {
                row.classList.add('row-summary');
                return;
            }
            cells.forEach((cell, index) => {
                if (headers[index]) cell.setAttribute('data-label', headers[index]);
            });
        });
        if (!table.classList.contains('record-table')) stackTables.push({ table, columns: headers.length });
    });

    /* On phones, tables that don't fit unwrapped stack into records; tables that fit stay tables. */
    function layoutTables() {
        const phone = window.matchMedia('(max-width: 680px)').matches;
        stackTables.forEach(({ table, columns }) => {
            table.classList.remove('stackable');
            if (!phone) return;
            if (columns >= 5) {
                table.classList.add('stackable');
                return;
            }
            table.classList.add('measuring');
            const tooWide = table.scrollWidth > table.parentElement.clientWidth + 1;
            table.classList.remove('measuring');
            if (tooWide) table.classList.add('stackable');
        });
    }
    layoutTables();
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(layoutTables, 150);
    });
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(layoutTables);
})();
