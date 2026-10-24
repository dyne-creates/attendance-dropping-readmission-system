

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('appDialogs', () => ({
    dialogOpen: false,
    dialogType: 'alert',
    dialogTitle: 'Notice',
    dialogMessage: '',
    pendingForm: null,
    pendingSubmitter: null,
    pendingAction: null,

    openAppDialog(detail) {
        this.dialogType = detail.type || 'alert';
        this.dialogTitle = detail.title || (this.dialogType === 'confirm' ? 'Please confirm' : 'Notice');
        this.dialogMessage = detail.message || '';
        this.pendingForm = detail.form || null;
        this.pendingSubmitter = detail.submitter || null;
        this.pendingAction = detail.action || null;
        this.dialogOpen = true;
    },

    closeAppDialog() {
        this.dialogOpen = false;
        this.pendingForm = null;
        this.pendingSubmitter = null;
        this.pendingAction = null;
    },

    confirmAppDialog() {
        const form = this.pendingForm;
        const submitter = this.pendingSubmitter;
        const action = this.pendingAction;

        this.closeAppDialog();

        if (action) {
            action();
        } else if (form) {
            form.dataset.confirmed = 'true';
            form.requestSubmit(submitter || undefined);
        }
    },
}));

document.addEventListener('submit', event => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (form.dataset.confirmed === 'true') {
        delete form.dataset.confirmed;
        return;
    }

    const submitter = event.submitter;
    const message = submitter?.dataset.confirm || form.dataset.confirm;

    if (!message) {
        return;
    }

    event.preventDefault();
    window.dispatchEvent(new CustomEvent('app-dialog', {
        detail: {
            type: 'confirm',
            message,
            form,
            submitter,
        },
    }));
}, true);

window.showAppAlert = (message, title = 'Notice') => {
    window.dispatchEvent(new CustomEvent('app-dialog', {
        detail: { type: 'alert', message, title },
    }));
};

window.showAppConfirm = (message, action, title = 'Please confirm') => {
    window.dispatchEvent(new CustomEvent('app-dialog', {
        detail: { type: 'confirm', message, action, title },
    }));
};

Alpine.start();
