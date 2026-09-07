(() => {
  const page = document.querySelector('[data-orders-page]');
  if (!page) return;
  const search = page.querySelector('[data-order-search]');
  const filter = page.querySelector('[data-order-filter]');
  const cards = [...page.querySelectorAll('[data-order-card]')];
  const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('fr');
  function applyFilters() {
    const query = normalize(search.value.trim());
    let count = 0;
    cards.forEach(card => {
      const state = filter.value;
      const matches = state === 'all' || (state === 'active' && !['completed', 'cancelled'].includes(card.dataset.status)) || (state === 'paid' && card.dataset.paid === '1') || (state === 'refund' && card.dataset.refund === '1') || (state === 'refunded' && card.dataset.payment === 'refunded') || (state === 'rejected' && card.dataset.refundStatus === 'rejected') || (['completed', 'cancelled'].includes(state) && card.dataset.status === state);
      card.hidden = !matches || !normalize(card.dataset.search).includes(query);
      if (!card.hidden) count++;
    });
    page.querySelector('[data-order-count]').textContent = `${count} commande${count === 1 ? '' : 's'}`;
    page.querySelector('[data-order-empty]').hidden = count > 0;
  }
  search.addEventListener('input', applyFilters);
  filter.addEventListener('change', applyFilters);
  page.querySelector('[data-order-reset]').addEventListener('click', () => { search.value = ''; filter.value = 'all'; applyFilters(); search.focus(); });

  const dialog = page.querySelector('[data-order-dialog]');
  const form = dialog.querySelector('form');
  const submit = dialog.querySelector('[data-dialog-submit]');
  const settings = {
    cancelled: {title: 'Annuler la commande', description: 'La commande sera marquée comme annulée et le motif sera conservé dans son historique. Les accès numériques liés à cet achat seront retirés. Le paiement et le stock restent inchangés. Si elle a déjà été payée ou livrée, traitez le remboursement et le retour séparément.', label: 'Motif de l’annulation *', button: 'Confirmer l’annulation', status: true, required: true, danger: true},
    processing: {title: 'Préparer la commande', description: 'La commande passera en préparation. Vous pourrez ensuite suivre sa remise au client.', button: 'Passer en préparation', status: true},
    shipping: {title: 'Mettre en livraison', description: 'La commande passera en livraison. Le stock sera débité lorsque vous confirmerez la remise au client.', button: 'Confirmer la mise en livraison', status: true},
    completed: {title: 'Confirmer la remise ou la fin de commande', description: 'Confirmez uniquement si la commande est terminée ou remise au client. Les articles physiques sortiront du stock une seule fois.', button: 'Confirmer la remise', status: true},
    collect_cod: {title: 'Enregistrer l’encaissement', description: 'Enregistrez le règlement réellement reçu avec sa référence de reçu et son moyen de paiement.', label: 'Note sur l’encaissement *', button: 'Confirmer le paiement reçu', required: true, financial: true, phone: true},
    request_refund: {title: 'Demander un remboursement', description: 'Une demande de remboursement intégral sera enregistrée. Aucun transfert d’argent ne sera effectué par cette action.', label: 'Motif du remboursement *', button: 'Enregistrer la demande', required: true},
    reject_refund: {title: 'Refuser la demande de remboursement', description: 'Le refus et son motif seront conservés dans le suivi. Le paiement de la commande reste inchangé.', label: 'Motif du refus *', button: 'Confirmer le refus', required: true, danger: true},
    complete_refund: {title: 'Confirmer le remboursement', description: 'Cette action marque la commande comme remboursée et retire les accès numériques associés. Renseignez la référence du remboursement intégral déjà exécuté auprès du prestataire.', label: 'Note sur le remboursement *', button: 'Confirmer le remboursement exécuté', required: true, financial: true},
    receive_return: {title: 'Enregistrer un retour intégral', description: 'Confirmez la réception effective de tous les articles retournés et précisez leur état. Seuls les articles contrôlés et aptes à la vente seront remis en stock.', label: 'Résultat du contrôle des articles *', button: 'Confirmer le retour reçu', required: true, returns: true}
  };
  let activeButton = null;
  page.addEventListener('click', event => {
    const button = event.target.closest('[data-order-action]');
    if (!button) return;
    const action = button.dataset.orderAction;
    const config = settings[action];
    if (!config) return;
    activeButton = button;
    form.reset();
    submit.disabled = false;
    form.action = config.status ? page.querySelector('[data-order-status-url]').href : page.querySelector('[data-order-action-url]').href;
    form.elements.id.value = button.dataset.orderId;
    form.elements.status.value = config.status ? action : '';
    form.elements.action.value = config.status ? '' : action;
    form.elements.status.disabled = !config.status;
    form.elements.action.disabled = !!config.status;
    dialog.querySelector('[data-dialog-reference]').textContent = button.dataset.orderReference;
    dialog.querySelector('#order-dialog-title').textContent = config.title;
    dialog.querySelector('#order-dialog-description').textContent = config.description;
    dialog.querySelector('[data-note-label]').textContent = config.label || 'Note de suivi (facultative)';
    form.elements.note.required = !!config.required;
    form.elements.note.minLength = config.required ? 5 : 0;
    form.elements.note.maxLength = config.status ? 255 : 2000;
    dialog.querySelector('[data-financial-fields]').hidden = !config.financial;
    ['reference', 'method'].forEach(name => { form.elements[name].disabled = !config.financial; form.elements[name].required = !!config.financial; });
    dialog.querySelector('[data-phone-field]').hidden = !config.phone;
    form.elements.payer_phone.disabled = !config.phone;
    dialog.querySelector('[data-return-field]').hidden = !config.returns;
    submit.textContent = config.button;
    submit.className = `order-btn order-btn-${config.danger ? 'danger' : 'primary'}`;
    dialog.showModal();
    form.elements.note.focus();
  });
  dialog.querySelector('[data-return-type]').addEventListener('change', event => { form.elements.action.value = event.target.value; });
  dialog.querySelectorAll('[data-dialog-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
  dialog.addEventListener('close', () => activeButton?.focus());
  form.addEventListener('submit', () => { submit.disabled = true; submit.textContent = 'Enregistrement…'; });
  window.addEventListener('pageshow', () => { submit.disabled = false; });
})();
