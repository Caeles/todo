# Documentation technique – Authentification 

## I. Prérequis & dépendances
- **PHP** : 8.4
- **Composer**  
- **Symfony CLI**   
- **Xdebug** (pour profilage/couverture)  

---

## II. Installation & Configuration

### II.1 Cloner le dépôt
```bash
git clone https://github.com/Caeles/todo.git
```

### II.2 Installer les dépendances
```bash
composer install
```

### II.3 Configurer l’environnement
1. Copier le fichier `.env` et le placer dans le fichier `.env.local`.  
2. Configurer la variable `DATABASE_URL` selon votre environnement :  
   ```env
   DATABASE_URL=mysql://username:password@127.0.0.1:3306/todolist
   ```

### II.4 Créer et initialiser la base de données
```bash
php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### II.5 Charger des données de test (fixtures)
```bash
php bin/console doctrine:fixtures:load --append
```

Création de l’admin pour garantir un environnement fonctionnel immédiatement
après l’installation.
Création de 10 utilisateurs aléatoires
Création de 50 tâches aléatoires
Création de l’utilisateur anonyme et migration des tâches orphelines vers
l’utilisateur anonyme.


### II.6 Démarrer le serveur
```bash
symfony serve
```

---

## III. Accès Admin
Pour vous connecter avec un compte :  
- **Utilisateur** : `admin`  
- **Mot de passe** : `admin`

---

## IV. Accès Utilisateur
Pour vous connecter avec un compte :  
- **Utilisateur** : `usertest`  
- **Mot de passe** : `user`

---

## IV. Structure & fichiers clés
- Config sécurité : `config/packages/security.yaml`  
- Contrôleur sécurité : `src/Controller/SecurityController.php`  
- Formulaire login : `templates/security/login.html.twig`  
- Entité utilisateur : `src/Entity/User.php`  
- Contrôleur utilisateur : `src/Controller/UserController.php`  
- Contrôleur de tâches : `src/Controller/TaskController.php`

---

## V. Configuration de la sécurité
L’application utilise le composant **Security** de Symfony basé sur :  
- Sessions  
- Rôles :  
  - `ROLE_USER` : accès aux tâches (`/tasks`) et à la page d’accueil (`/`)  
  - `ROLE_ADMIN` : accès à la création d'utilisateurs (`/users/create`) et à la gestion des utilisateurs (`/users`)  

**Extrait `security.yaml` :**
```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
        App\Entity\User:
            algorithm: auto

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: username

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        main:
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: login
                check_path: login_check
                default_target_path: task_list
            logout:
                path: app_logout
                target: login

    access_control:
        - { path: ^/login, roles: PUBLIC_ACCESS }
        - { path: ^/register, roles: PUBLIC_ACCESS }
        - { path: ^/users/create, roles:ROLE_ADMIN }
        - { path: ^/users, roles: ROLE_ADMIN }
        - { path: ^/tasks, roles: ROLE_USER }
        - { path: ^/, roles: PUBLIC_ACCESS }



**Points importants :**
- Authentification par **username**  
- Redirection après connexion : `/tasks`
- Accès `/users` réservé aux `ROLE_ADMIN`  
- `/tasks` et `/` nécessitent `ROLE_USER`  

---

## VI. Entité utilisateur (`User`)
**Champs principaux :**
- `id` : int (PK)  
- `username` : string(25), unique  
- `email` : string(60), unique (non utilisé pour login)  
- `password` : string(64), hashé  
- `roles` : json, par défaut `['ROLE_USER']`  
- `tasks` : `OneToMany` vers `Task`  
 

---

## VII. Processus d’authentification
1. L’utilisateur accède à `/login`  
2. Formulaire soumis vers `/login_check`  
3. Si identifiants corrects :  
   - Création d’une session  
   - Redirection vers `/tasks`  
4. En cas d’erreur :  
   - Formulaire réaffiché avec une indication liée à l'erreur  

**Formulaire (`login.html.twig`) :**
- Champs : `_username`, `_password`  
- Jeton CSRF : `{{ csrf_token('authenticate') }}`  
- Action : `{{ path('login_check') }}`  

---

## VIII. Routes liées aux tâches
- **Liste** : `GET /tasks` → `task_list`  
- **Création** : `GET|POST /tasks/create` → `task_create`  
- **Édition** : `GET|POST /tasks/{id}/edit` → `task_edit`  
- **Toggle** : `POST /tasks/{id}/validate` → `task_validate`  
- **Suppression** : `POST /tasks/{id}/delete` → `task_delete`  

Toutes protégées par `ROLE_USER`.

---

## IX. Routes liées aux utilisateurs
Basées sur `src/Controller/UserController.php` :

- **Liste** : `GET /users` → `user_list`  
  - Accès : `ROLE_ADMIN`  

- **Création** : `GET|POST /users/create` → `user_create`  
  - Accès : `ROLE_ADMIN`  

- **Édition** : `GET|POST /users/{id}/edit` → `user_edit`  
  - Accès : `ROLE_ADMIN`  
  - GET : formulaire pré-rempli  
  - POST : mise à jour du rôle, hash du nouveau mot de passe si fourni  

---

**Dernière mise à jour** : 2025-08-11
