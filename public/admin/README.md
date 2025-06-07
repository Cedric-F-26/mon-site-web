# Système d'authentification administrateur

## Fonctionnalités implémentées
1. Connexion sécurisée avec protection CSRF
2. Protection contre les attaques par force brute
3. Réinitialisation de mot de passe

## Fichiers clés
- `login.php` - Page de connexion principale
- `forgot-password.php` - Demande de réinitialisation
- `reset-password.php` - Définition d'un nouveau mot de passe
- `test_password_reset.php` - Script de test

## Configuration requise
1. Table `admins` dans la base de données
```sql
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL
);
```

2. Table `password_resets`
```sql
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);
```

## Tester le système
1. Exécuter le script de test :
   `http://localhost/mon-site-agri/admin/test_password_reset.php`

2. Tester manuellement :
   a. Visiter `login.php`
   b. Cliquer sur "Mot de passe oublié ?"
   c. Saisir un email valide
   d. Utiliser le lien généré (simulé dans le code)
   e. Définir un nouveau mot de passe

## Personnalisation production
1. Configurer un vrai système d'envoi d'emails dans `forgot-password.php`
2. Ajouter HTTPS
3. Mettre à jour les chemins absolus si nécessaire
