# Changelog

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

## [3.0.1] - 2026-08-11

### Modifié

- Le nom Composer public devient `cinetpay-dev/cinetpay-php` afin d'utiliser un namespace Packagist distinct du package CinetPay legacy.

## [3.0.0] - 2026-08-11

### Ajouté

- Pays du compte obligatoire via l'énumération `Country`.
- Validation locale de la devise, du suffixe pays des méthodes de paiement et de l'indicatif téléphonique.
- Documentation et exemples pour les intégrations multi-pays.

### Sécurité

- Cloisonnement des Bearer tokens par clé API, pays, environnement et URL lorsque plusieurs comptes partagent un même `TokenStore`.

### Ruptures de compatibilité

- Les factories `sandbox()` et `production()` ainsi que `Config` exigent désormais un `Country`.
- Les méthodes de `TokenStore` reçoivent désormais une clé de cache opaque.

## [2.0.0] - 2026-08-11

### Ajouté

- Client moderne pour l'API REST CinetPay avec environnements sandbox et production.
- Authentification automatique, cache du jeton et renouvellement sécurisé.
- Initialisation et vérification des paiements.
- Création et vérification des transferts.
- Consultation du solde marchand.
- Traitement sécurisé et typé des notifications de paiement et de transfert.
- DTO immuables, validations locales et exceptions structurées.
- Suite de 121 tests avec seuil obligatoire de couverture à 100 %.
- Analyse statique PHPStan au niveau maximal et CI PHP 8.2 à 8.5.

### Modifié

- Réécriture complète de l'ancien SDK PHP basé sur les API CinetPay V1/V2 legacy.
- PHP 8.2 devient la version minimale prise en charge.

### Ruptures de compatibilité

- Suppression de la classe globale legacy `CinetPay` et de la génération de formulaires HTML.
- Nouvelle API orientée services sous le namespace `CinetPay`.
- Remplacement des identifiants `site_id` / `apikey` par `api_key` / `api_password`.
