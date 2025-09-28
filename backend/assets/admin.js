document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-confirm]').forEach(element => {
        const message = element.getAttribute('data-confirm') || 'Are you sure?';

        if (element.tagName === 'FORM') {
            element.addEventListener('submit', event => {
                if (!confirm(message)) {
                    event.preventDefault();
                }
            });
        } else {
            element.addEventListener('click', event => {
                if (!confirm(message)) {
                    event.preventDefault();
                }
            });
        }
    });
});
