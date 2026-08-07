/**
 * Modals génériques Bootstrap
 *
 * showModal(type, message, title)
 *   type : 'info' | 'warning' | 'alert'
 *
 * confirmModal(message, onConfirm, title)
 *   onConfirm : fonction appelée si l'utilisateur clique Confirmer
 */

function showModal(type, message, title) {
    const map = {
        info:    { id: 'modal-info',    defaultTitle: 'Information' },
        warning: { id: 'modal-warning', defaultTitle: 'Avertissement' },
        alert:   { id: 'modal-alert',   defaultTitle: 'Erreur' },
    };

    const cfg = map[type];
    if (!cfg) { console.warn('showModal: type inconnu', type); return; }

    document.getElementById(cfg.id + '-title').textContent = title || cfg.defaultTitle;
    document.getElementById(cfg.id + '-body').innerHTML    = message;

    const el = document.getElementById(cfg.id);
    bootstrap.Modal.getOrCreateInstance(el).show();
}

function confirmModal(message, onConfirm, title) {
    document.getElementById('modal-confirm-title').textContent = title || 'Confirmation';
    document.getElementById('modal-confirm-body').innerHTML    = message;

    const el  = document.getElementById('modal-confirm');
    const btn = document.getElementById('modal-confirm-ok');
    const modal = bootstrap.Modal.getOrCreateInstance(el);

    // Nettoie le listener précédent pour éviter les doublons
    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);

    newBtn.addEventListener('click', function () {
        modal.hide();
        if (typeof onConfirm === 'function') onConfirm();
    });

    modal.show();
}
