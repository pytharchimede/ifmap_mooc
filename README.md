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

# ifmap_mooc
