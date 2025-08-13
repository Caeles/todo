# Todo & Co

## Description
TodoList est une application de gestion de tâches avec une interface utilisateur et une interface administrateur. 

## Fonctionnalités
- Gestion complète des tâches 
- Système d'authentification et gestion des utilisateurs
- Interface d'administration pour la gestion des utilisateurs
- Architecture sécurisée avec Voters pour les autorisations
- Tests unitaires et fonctionnels complets
- Couverture de tests à 71.28%

## Architecture Sécurisée
- **UserVoter** : Gestion des droits utilisateurs (admin seulement)
- **TaskVoter** : Contrôle qui peut créer, modifier ou supprimer les tâches
- Protection CSRF 


## Prérequis
- PHP 8.4 ou supérieur
- MySQL
- Serveur web
- Composer


## Installation

### 1. Cloner le dépôt
```bash
git clone https://github.com/votre-username/todolist.git
cd todolist
```

### 2. Installer les dépendances
```bash
composer install
```

### 3. Configurer l'environnement
Copiez le fichier `.env` en `.env.local` et configurez les variables d'environnement :
```bash
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
# ou pour MySQL :
# DATABASE_URL="mysql://username:password@127.0.0.1:3306/todolist"
```

### 4. Créer la base de données
```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
```

### 5. Charger les données de test 
```bash
php bin/console doctrine:fixtures:load
```

### 6. Démarrer le serveur
```bash
symfony serve
```

L'application sera accessible à l'adresse : `http://localhost:8000`

## Tests

### Exécuter tous les tests
```bash
php bin/phpunit
```

### Tests avec couverture
```bash
php bin/phpunit --coverage-html coverage/
```

## Accès utilisateur

**Administrateur :**
- Nom d'utilisateur : `admin`
- Mot de passe : `admin`

**Utilisateur standard :**
- Nom d'utilisateur : `usertest`
- Mot de passe : `user`


## Performances
- **OPcache PHP** : Activé pour l'accélération du code
- **Configuration Symfony** : Optimisée pour le développement


## Licence
MIT License

Copyright (c) 2024 TodoList Project

Base du projet OpenClassrooms #8 : Améliorez un projet existant
https://openclassrooms.com/projects/ameliorer-un-projet-existant-1
