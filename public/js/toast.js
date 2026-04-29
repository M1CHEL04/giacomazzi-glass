const initToast = (toast) => {
    const hideToast = () => {
        toast.classList.add('is-hidden');
        setTimeout(() => toast.remove(), 300);
    };

    const closeButton = toast.querySelector('[data-toast-close]');
    if (closeButton) {
        closeButton.addEventListener('click', hideToast);
    }

    setTimeout(hideToast, 4500);
};

const ensureToastStack = () => {
    let stack = document.querySelector('.internal-toast-stack');
    if (!stack) {
        stack = document.createElement('div');
        stack.className = 'internal-toast-stack';
        stack.setAttribute('aria-live', 'polite');
        stack.setAttribute('aria-atomic', 'true');
        document.body.appendChild(stack);
    }
    return stack;
};

const buildToastFromTemplate = (templateId, message) => {
    const template = document.getElementById(templateId);
    if (!template || !template.content || !template.content.firstElementChild) {
        return null;
    }

    const toast = template.content.firstElementChild.cloneNode(true);
    const messageEl = toast.querySelector('[data-toast-message]');
    if (messageEl) {
        messageEl.textContent = message;
    }
    return toast;
};

window.showToast = (message, type = 'success') => {
    const templateId = type === 'error' ? 'toast-error-template' : 'toast-success-template';
    const toast = buildToastFromTemplate(templateId, message) || document.createElement('div');

    if (!toast.hasAttribute('data-toast')) {
        const iconText = type === 'error' ? '!' : 'OK';
        toast.className = `internal-toast internal-toast-${type}`;
        toast.setAttribute('data-toast', '');
        toast.innerHTML = `
            <span class="internal-toast-icon" aria-hidden="true">${iconText}</span>
            <div><div class="internal-toast-message"></div></div>
            <button class="internal-toast-close" type="button" aria-label="Cerrar" data-toast-close>x</button>
        `;
        const messageEl = toast.querySelector('.internal-toast-message');
        if (messageEl) {
            messageEl.textContent = message;
        }
    }

    const stack = ensureToastStack();
    stack.appendChild(toast);
    initToast(toast);
};

document.querySelectorAll('[data-toast]').forEach((toast) => initToast(toast));
