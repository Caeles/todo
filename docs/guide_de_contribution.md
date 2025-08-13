# Guide de contribution – Projet ToDoList

Bienvenue dans le projet ToDoList ! Ce document a été créé pour faciliter la contribution au projet en respectant les standards de qualité du code.

---

## 1. Pré‑requis


- PHP 8.4.x (projet testé sur 8.4.8)
- Composer
- Symfony CLI
- MySQL/MariaDB (dev/prod) et SQLite pour les tests (.env.test)
- Git
- Xdebug 

Installation de dépendances:

```bash
composer install
```

Notes projet :
- Tests : activer `framework.test: true` et `.env.test` avec `DATABASE_URL="sqlite:///:memory:"`.
- Migrations : synchroniser Doctrine

```bash
php bin/console doctrine:migrations:sync-metadata-storage
```

---

## 2. Workflow Git

Workflow basé sur branches et sur les Pull Requests :

1) Mettez à jour `main` :
```bash
git checkout main
git pull origin main
```

2) Créez une branche par fonctionnalité/correction :
```bash
git checkout -b feature/feature_name
# ou
git checkout -b fix/title
```

3) Commitez avec un message compréhensible:
```bash
git commit -m "fix: correction bug suppression tâche anonyme"
```
Types utilisés :
- feature: ajout d'une nouvelle fonctionnalité
- enhancement: amélioration d'une fonctionnalité
- fix: correction de bug
- doc: documentation
- test: ajout/modification de tests


4) Poussez votre branche :
```bash
git push origin feature/feature_name
```


## 3. Règles de qualité

- Respecter PSR‑1, PSR‑2 et PSR‑12
- Écrire des tests unitaires et fonctionnels pour toute nouvelle fonctionnalité
- Couverture minimale supérieure à 70%:
```bash
vendor/bin/phpunit --coverage-html var/coverage
```

---



## 4. Lancement des tests

- Lancer l’ensemble des tests :
```bash
vendor/bin/phpunit
```

- Lancer un test précis :
```bash
vendor/bin/phpunit --filter TestName
```

- Configuration tests : `.env.test`  et `framework.test: true`.

---

## 5. Communication

- Utiliser les Issues GitHub pour les nouvelles fonctionnalités, les bugs et les questions;
- Décrire clairement le besoin.

---

## 6. Ressources utiles

- Symfony : https://symfony.com/doc
- PSR‑12 – Coding Style Guide : https://www.php-fig.org/psr/psr-12/
- PHPUnit : https://phpunit.de/
- Doctrine ORM : https://www.doctrine-project.org/projects/doctrine-orm/en/current/

