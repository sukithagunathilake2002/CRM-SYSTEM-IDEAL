document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-exchange-assessment]').forEach(root => {
        const items = root.querySelector('[data-exchange-items]');
        const prototype = items.querySelector('[data-exchange-item]').cloneNode(true);
        const finance = root.querySelector('[data-exchange-finance]');
        const updateFinance = () => {
            root.querySelectorAll('[data-exchange-finance-field]').forEach(field => {
                field.hidden = finance.value !== 'yes';
                field.querySelector('input').required = finance.value === 'yes';
            });
        };
        const updateTotal = () => {
            let cents = 0;
            items.querySelectorAll('[data-exchange-item]').forEach((row, index) => {
                row.querySelector('[data-item-description]').name = `exchange_assessment[items][${index}][description]`;
                const amount = row.querySelector('[data-item-amount]');
                amount.name = `exchange_assessment[items][${index}][amount]`;
                cents += Math.round((Number(amount.value) || 0) * 100);
            });
            root.querySelector('[data-exchange-total]').textContent = (cents / 100).toLocaleString('en-LK', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        };
        root.querySelector('[data-add-exchange-item]').addEventListener('click', () => {
            if (items.children.length >= 100) return;
            const row = prototype.cloneNode(true);
            row.querySelectorAll('input').forEach(input => { input.value = ''; });
            items.appendChild(row);
            updateTotal();
        });
        items.addEventListener('click', event => {
            if (!event.target.closest('[data-remove-exchange-item]')) return;
            const row = event.target.closest('[data-exchange-item]');
            if (items.children.length > 1) row.remove();
            else row.querySelectorAll('input').forEach(input => { input.value = ''; });
            updateTotal();
        });
        items.addEventListener('input', updateTotal);
        finance.addEventListener('change', updateFinance);
        updateFinance();
        updateTotal();
    });
});
