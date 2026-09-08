<div class="ifmap-chat" data-ifmap-chat data-bootstrap-url="/discussion/bootstrap" data-messages-url="/discussion/messages" data-send-url="/discussion/envoyer">
    <button class="ifmap-chat-launcher" type="button" data-chat-toggle aria-label="Ouvrir la discussion IFMAP" aria-expanded="false">
        <span class="ifmap-chat-launcher-icon" aria-hidden="true">✦</span>
        <span class="ifmap-chat-launcher-copy"><strong>Discussion IFMAP</strong><small>Un conseiller peut vous répondre ici</small></span>
        <span class="ifmap-chat-status-dot" aria-hidden="true"></span>
    </button>
    <section class="ifmap-chat-panel" data-chat-panel aria-hidden="true">
        <header class="ifmap-chat-head">
            <div class="ifmap-chat-brand"><span class="ifmap-chat-mark">IF</span><div><strong>IFMAP Assistance</strong><small><i></i> Discussion instantanée</small></div></div>
            <button type="button" data-chat-close aria-label="Fermer la discussion">×</button>
        </header>
        <div class="ifmap-chat-welcome"><strong>Bonjour 👋</strong><span>Posez votre question. Vous pouvez aussi envoyer une image, un PDF ou un document.</span></div>
        <div class="ifmap-chat-messages" data-chat-messages aria-live="polite"></div>
        <div class="ifmap-chat-login" data-chat-login hidden>
            <strong>Connectez-vous pour démarrer la discussion.</strong>
            <p>Votre conversation sera conservée dans votre espace IFMAP.</p>
            <a href="/connexion">Se connecter</a>
        </div>
        <form class="ifmap-chat-compose" data-chat-form enctype="multipart/form-data">
            <div class="ifmap-chat-file-preview" data-chat-file-preview hidden></div>
            <div class="ifmap-chat-compose-row">
                <label class="ifmap-chat-attach" title="Joindre un fichier"><input type="file" name="attachment" data-chat-file accept="image/jpeg,image/png,image/webp,image/gif,application/pdf,.doc,.docx,.xls,.xlsx,.txt,.zip"><span>＋</span></label>
                <textarea name="message" data-chat-input rows="1" maxlength="4000" placeholder="Écrivez votre message…"></textarea>
                <button class="ifmap-chat-send" type="submit" aria-label="Envoyer">➤</button>
            </div>
            <small class="ifmap-chat-note">Pièce jointe max. 10 Mo · échanges sécurisés IFMAP</small>
        </form>
    </section>
</div>
