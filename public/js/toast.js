document.querySelectorAll('[data-toast]').forEach((toast) => {
    const hideToast = () => {
        toast.classList.add('is-hidden');
        setTimeout(() => toast.remove(), 300);
    };

    const closeButton = toast.querySelector('[data-toast-close]');
    if (closeButton) {
        closeButton.addEventListener('click', hideToast);
    }

    setTimeout(hideToast, 4500);
});
