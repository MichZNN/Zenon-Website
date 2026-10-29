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
            if (slot) slot.textContent = text;
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
                ticker.title = 'The price provider is currently unavailable.';
            });
    }

    /* Give each cell the name of its column, so tables can stack into rows on phones. */
    document.querySelectorAll('main table').forEach((table) => {
        const headers = Array.from(table.querySelectorAll('thead th')).map((th) => th.textContent.trim());
        if (!headers.length) return;

        /* Right-align columns that only hold numbers, so amounts line up. */
        const rows = Array.from(table.querySelectorAll('tbody tr')).filter((row) => row.children.length === headers.length);
        headers.forEach((_, index) => {
            if (index === 0) return; /* the first column names the row (epoch, type, address) */
            const values = rows.map((row) => row.children[index].textContent.trim()).filter(Boolean);
            if (values.length && values.every((value) => /^-?[\d.,]+$/.test(value))) {
                table.querySelectorAll(`tr > :nth-child(${index + 1})`).forEach((cell) => cell.classList.add('num'));
            }
        });

        /* Narrow tables fit a phone as they are; wide ones stack into records. */
        if (headers.length < 5) return;
        table.classList.add('stackable');
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
    });
})();
