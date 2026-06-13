document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-confirm]');

    if (! trigger) {
        return;
    }

    const message = trigger.getAttribute('data-confirm') ?? 'Confirmer cette action ?';

    if (! window.confirm(message)) {
        event.preventDefault();
    }
});
