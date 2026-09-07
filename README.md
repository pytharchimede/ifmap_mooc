# IFMAP Learning

Plateforme MOOC PHP 8.2+ sans dépendance externe, structurée autour d'un routeur, de contrôleurs, de vues et de migrations versionnées.

## Installation

```bash
cp .env.example .env
# Créer la base MySQL puis renseigner les accès dans .env
php migrate.php
php -S 127.0.0.1:8080
```

Ouvrir `http://127.0.0.1:8080`. Le site public couvre `/formations`, `/boutique`, `/panier` et `/mon-compte`. L'académie est disponible sur `/academie`, l'administration sur `/admin` et le paramétrage de marque sur `/admin/branding`.

Avec Apache, le fichier `.htaccess` redirige automatiquement toutes les URLs vers le routeur. Le document root doit pointer vers ce dossier et `mod_rewrite` doit être activé.

## Architecture

- `app/Core` : routeur, environnement, connexion PDO et moteur de migration
- `app/Controllers` : contrôleurs HTTP
- `resources/views` : vues et layouts
- `routes/web.php` : routes de l'application
- `database/migrations` : évolutions automatiques du schéma
- `public/assets` : CSS et JavaScript

Le prototype utilise des données de démonstration pour être immédiatement navigable. Le schéma MySQL couvre utilisateurs, rôles, cours, modules, leçons, inscriptions, progression, produits, commandes mixtes, paiements, livraison, attestations et réglages de marque.

Si MySQL est momentanément indisponible, l'application utilise `storage/data.json` comme stockage persistant de secours : le panier, les commandes et les créations de formations restent fonctionnels. Après correction des droits MySQL, relancer `php migrate.php` pour installer le schéma complet.

# ifmap_mooc

### Paiement Pro (boutique et cours)

Les nouvelles commandes en ligne utilisent Paiement Pro, marchand `PP-F92695`
(variable `PAIEMENTPRO_MERCHANT_ID`). Les anciennes routes CinetPay restent disponibles
pour les transactions déjà initiées. PHP doit disposer de cURL et SimpleXML.
Configurer `APP_URL` avec l’URL HTTPS publique du site avant utilisation réelle.

- Initialisation SOAP : `https://www.paiementpro.net/webservice/OnlineServicePayment_v2.php?wsdl`.
- Retour : `/paiement/paiementpro/retour` ; ne valide jamais le paiement.
- Notification : `/paiement/paiementpro/notification?token=…` ; URL générée par commande.

Le PDF fourni mentionne un hashcode sans définir son calcul ; le WSDL consulté
le 7 septembre 2026 ne contient pas ce champ ni de méthode de consultation du statut.
L’intégration protège donc la notification avec un secret aléatoire de 256 bits,
transmis uniquement dans l’URL de notification lors de l’initialisation HTTPS et
stocké sous forme d’empreinte. Ne pas journaliser les paramètres de cette URL dans
les journaux HTTP. Le marchand, la référence, le montant et la devise sont contrôlés.
Les paramètres du retour navigateur ne peuvent pas accorder un accès.
Faire confirmer par Paiement Pro la conservation du paramètre `token` dans les
notifications et son mécanisme officiel de signature avant la mise en production.

La validation réutilise une transaction SQL avec verrou de commande pour inscrire
aux cours et créer les téléchargements une seule fois. Le stock est débité à la livraison, comme décrit ci-dessous. Une
notification répétée ne répète pas ces opérations. Une commande annulée ou remboursée
n’est pas réactivée. Actualiser la confirmation recharge le statut depuis la base.

Vérifications locales : `php tests/paiement-pro.php` et syntaxe PHP.
Un essai complet sur le compte marchand reste nécessaire : succès, échec,
notification avant/après retour et répétition de notification, pour une commande
boutique et une commande cours. Aucun paiement réel n’a été exécuté pendant l’intégration.

### Suivi commercial, livraison et remboursements (7 septembre 2026)

Migration : `2026_09_07_000014_payment_delivery_refunds.php` (via `php migrate.php`
ou migration automatique au démarrage). Elle ajoute le lien commande/inscription,
les informations de paiement, les dates de livraison/retour, le journal des
remboursements et la version des conditions acceptées. Les anciennes inscriptions
issues d’un achat payé sont rattachées à la commande. Les inscriptions payantes
créées par le catalogue ou l’inscription directe sans règlement restent en attente.

Dans `/admin/commandes` :
- Le paiement et la logistique sont distincts. Les reçus affichent le contact client,
  le téléphone transmis lors de l’encaissement, la référence opérateur si transmise,
  la référence de session, le canal et la date de confirmation. Ne pas présenter le
  téléphone de contact ou la référence de session comme une preuve opérateur.
- « Livrée / retirée » débite le stock une fois, sous verrou SQL, après paiement.
  Le paiement à la livraison doit d’abord être encaissé avec un numéro de reçu.
  Les mouvements anciens sont pris en compte pour éviter un second débit.
  Le stock disponible est vérifié à la livraison ; il n’y a pas de réservation
  automatique de stock pendant le passage sur la passerelle de paiement.
- Annuler ne rembourse pas et n’ajoute jamais de stock fictif. La réception d’un
  retour intégral est explicite, avec ou sans remise en stock selon son état.
- Le remboursement intégral comporte une demande puis une confirmation avec la
  référence et le moyen d’un remboursement réellement exécuté. Aucune API de
  transfert/remboursement n’est appelée : l’opérateur doit effectuer la restitution
  avant de la confirmer ici. Les remboursements et retours partiels ne sont pas
  pris en charge par cette première version.
- Après remboursement, les accès numériques attachés à la commande sont révoqués.
  Une autre commande payée de la même formation conserve l’accès. Les reçus des
  cours sont visibles dans `/cours`, ceux des commandes dans la confirmation et
  les documents administratifs. L’export CSV reprend les détails de paiement.

Les pages `/conditions-generales` et `/retours-remboursements` sont liées dans le
pied de page et doivent être acceptées à la commande. Leur version est conservée.
Les règles commerciales spécifiques (délais, frais, formations commencées) et
l’identité juridique complète/RCCM restent à compléter avec IFMAP ; aucun délai
commercial ni renoncement automatique aux droits du client n’a été inventé.
Référence consultée : ARTCI, loi n° 2013-546 relative aux transactions électroniques,
https://www.artci.ci/index.php?Itemid=118&id=54&option=com_content&view=article .
Ces textes de site ne constituent pas une validation juridique des mentions de l’entreprise.

Tests : `php tests/paiement-pro.php`, `php tests/commerce-lifecycle.php`.
Le second utilise uniquement des tables MySQL temporaires propres à sa connexion
pour tester la migration, les paiements cours/produits, les reçus, la livraison,
le retour, le remboursement, les notifications tardives, les répétitions et le
rollback en cas de stock insuffisant. Aucun paiement réel n’est exécuté.


### Page administrative des commandes

`/admin/commandes` affiche les commandes sous forme de cartes adaptées au mobile,
avec indicateurs, recherche (client, commande, téléphone, transaction ou remboursement)
et filtres pour le traitement, l’annulation et les remboursements. Les actions
proposées dépendent du paiement et de la logistique. Les reçus et l’historique sont
repliables. Une fenêtre dédiée précise les effets et les champs requis de chaque action.

Le bouton « Annuler la commande » demande un motif, conservé dans l’historique.
L’annulation est possible même après livraison, sans réintégration automatique du
stock ni remboursement fictif. Elle retire les accès numériques attachés à cet
achat tout en conservant les droits issus d’un autre achat valide. Une commande
annulée ne peut pas être remise en préparation. Une opération répétée ne répète
pas les mouvements. Un retour ou remboursement reste une opération distincte.

Une demande de remboursement peut être refusée avec un motif, puis réexaminée.
Le montant, les motifs, les dates et la référence du remboursement exécuté sont
visibles dans son suivi. Aucun transfert de fonds automatique n’est déclenché.
Les retours et remboursements partiels restent hors de ce parcours intégral.

Vérifications supplémentaires :
- `php tests/commerce-actions.php` : 19 scénarios sur les routes et contrôleurs,
  y compris documents, export, champs manquants, état obsolète, contrôle CSRF et accès administrateur.
- `IFMAP_TEST_HTML_PATH=/tmp/ifmap-orders-test.html php tests/commerce-lifecycle.php`
  génère une page administrative avec uniquement des données temporaires fictives.
- `node tests/admin-orders-browser.cjs` vérifie recherche, filtres, boutons,
  validations, requêtes POST, fenêtres et affichage mobile. Playwright doit être
  disponible (`IFMAP_PLAYWRIGHT_PATH` peut indiquer son module local et
  `IFMAP_CHROME_PATH` un exécutable Chrome). Les envois sont interceptés : aucune
  commande réelle n’est modifiée. Les captures sont enregistrées dans `/tmp`.
