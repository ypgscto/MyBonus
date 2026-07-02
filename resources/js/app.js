import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-confirm]');
    if (!trigger) {
        return;
    }

    e.preventDefault();
    const form = trigger.closest('form');

    window.dispatchEvent(new CustomEvent('open-confirm', {
        detail: {
            title: trigger.dataset.confirmTitle || 'Konfirmasi',
            message: trigger.dataset.confirm || 'Apakah Anda yakin?',
            form: form,
        },
    }));
});

Alpine.start();
