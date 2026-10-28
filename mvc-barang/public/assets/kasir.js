document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-ajax]');
    if (!form || form.dataset.pending === 'true') {
        return;
    }

    event.preventDefault();
    form.dataset.pending = 'true';

    const submitter = event.submitter;
    const formData = new FormData(form);
    if (submitter && submitter.name) {
        formData.set(submitter.name, submitter.value);
    }

    const method = (form.method || 'GET').toUpperCase();
    const currentWorkspace = document.querySelector('.workspace');
    const scrollY = window.scrollY;
    const focusedId = document.activeElement instanceof HTMLElement
        ? document.activeElement.id
        : '';
    const buttons = Array.from(form.querySelectorAll('button[type="submit"]'));
    const ajaxStatus = document.getElementById('ajax-status');
    if (ajaxStatus) {
        ajaxStatus.textContent = 'Memperbarui data kasir.';
    }
    buttons.forEach((button) => {
        button.disabled = true;
    });
    if (currentWorkspace) {
        currentWorkspace.setAttribute('aria-busy', 'true');
    }

    try {
        let url = new URL(form.action, window.location.href);
        const options = {
            method,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        };

        if (method === 'GET') {
            for (const [key, value] of formData.entries()) {
                url.searchParams.set(key, value.toString());
            }
            window.history.replaceState({}, '', url);
        } else {
            options.body = formData;
        }

        const response = await fetch(url, options);
        let html;
        if (method === 'GET') {
            html = await response.text();
        } else {
            const result = await response.json();
            html = result.html;
        }

        const nextDocument = new DOMParser().parseFromString(html, 'text/html');
        const nextWorkspace = nextDocument.querySelector('.workspace');
        if (!nextWorkspace) {
            throw new Error('Halaman kasir tidak dapat diperbarui.');
        }

        if (currentWorkspace) {
            currentWorkspace.replaceWith(nextWorkspace);
        }
        window.scrollTo({ left: 0, top: scrollY, behavior: 'auto' });

        const focusTarget = focusedId
            ? document.getElementById(focusedId)
            : null;
        const statusTarget = focusTarget
            || document.querySelector('.notice[role="status"], .notice[role="alert"]')
            || document.getElementById('cart-title');
        if (statusTarget instanceof HTMLElement) {
            statusTarget.focus({ preventScroll: true });
        }
        if (ajaxStatus) {
            ajaxStatus.textContent = '';
        }
    } catch (error) {
        const notice = document.createElement('div');
        notice.className = 'notice notice-error';
        notice.setAttribute('role', 'alert');
        notice.tabIndex = -1;
        notice.textContent = 'Pembaruan gagal. Periksa koneksi lalu coba lagi.';
        if (ajaxStatus) {
            ajaxStatus.textContent = notice.textContent;
        }
        const workspace = document.querySelector('.workspace');
        if (workspace) {
            workspace.prepend(notice);
        }
        notice.focus({ preventScroll: true });
    } finally {
        form.dataset.pending = 'false';
        buttons.forEach((button) => {
            button.disabled = false;
        });
        if (currentWorkspace?.isConnected) {
            currentWorkspace.removeAttribute('aria-busy');
        }
    }
});
