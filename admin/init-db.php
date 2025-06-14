<?php
// Fichier d'initialisation de la base de données SQLite

// Inclure le fichier de configuration
require_once __DIR__ . '/config.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier si le fichier de base de données existe et est accessible
if (!file_exists(DB_PATH)) {
    touch(DB_PATH);
    chmod(DB_PATH, 0666);
    echo "Fichier de base de données créé avec succès.<br>";
}

try {
    // Vérifier si la table des actualités existe
    $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='news'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table des actualités si elle n'existe pas
        $pdo->exec("CREATE TABLE IF NOT EXISTS news (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            date DATE NOT NULL,
            image_url TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        echo "Table 'news' créée avec succès.<br>";
    } else {
        echo "La table 'news' existe déjà.<br>";
    }
    
    // Vérifier si la table des promotions existe
    $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='promotions'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table des promotions si elle n'existe pas
        $pdo->exec("CREATE TABLE IF NOT EXISTS promotions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            image_url TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        echo "Table 'promotions' créée avec succès.<br>";
    } else {
        echo "La table 'promotions' existe déjà.<br>";
    }
    
    // Vérifier si la table des utilisateurs existe
    $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table des utilisateurs si elle n'existe pas
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            email TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Créer un utilisateur admin par défaut
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (username, password, email) VALUES ('admin', '$password_hash', 'admin@example.com')");
        
        echo "Table 'users' créée avec succès et utilisateur admin ajouté.<br>";
    } else {
        echo "La table 'users' existe déjà.<br>";
    }
    
    // Vérifier si la table des administrateurs existe
    $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='admins'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table des administrateurs si elle n'existe pas
        $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            email TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Ajouter un administrateur par défaut (mot de passe: admin123)
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO admins (username, password, email) VALUES ('admin', '$password_hash', 'admin@example.com')");
        
        echo "Table 'admins' créée avec succès et administrateur ajouté.<br>";
        echo "Mot de passe: admin123<br>";
        echo "<strong>IMPORTANT :</strong> Changez ce mot de passe immédiatement après la première connexion.<br>";
    } else {
        echo "La table 'admins' existe déjà.<br>";
    }
    
    echo "<p>L'initialisation de la base de données est terminée.</p>";
    
} catch (PDOException $e) {
    die("Erreur lors de l'initialisation de la base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialisation de la base de données</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .success {
            color: #27ae60;
            background-color: #e8f8f0;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .warning {
            color: #f39c12;
            background-color: #fef5e7;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error {
            color: #e74c3c;
            background-color: #fde8e8;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            background-color: #3D6AA2;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #2c4c73;
        }
    </style>
</head>
<body>
    <h1>Initialisation de la base de données</h1>
    
    <div class="info-box">
        <p>Ce script a initialisé la structure de la base de données nécessaire pour le site.</p>
        <p>Pour des raisons de sécurité, il est recommandé de supprimer ce fichier après l'initialisation.</p>
    </div>
    
    <a href="index.php" class="btn">Retour à la page de connexion</a>
</body>
</html>
