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
aux cours, créer les téléchargements et débiter le stock une seule fois. Une
notification répétée ne répète pas ces opérations. Une commande annulée ou remboursée
n’est pas réactivée. Actualiser la confirmation recharge le statut depuis la base.

Vérifications locales : `php tests/paiement-pro.php` et syntaxe PHP.
Un essai complet sur le compte marchand reste nécessaire : succès, échec,
notification avant/après retour et répétition de notification, pour une commande
boutique et une commande cours. Aucun paiement réel n’a été exécuté pendant l’intégration.
