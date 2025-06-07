<?php
// Fichier d'initialisation de la base de données

// Inclure le fichier de configuration
require_once 'config.php';

// Désactiver l'affichage des erreurs en production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Vérifier si la table des actualités existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'news'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table des actualités si elle n'existe pas
        $pdo->exec("CREATE TABLE IF NOT EXISTS `news` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `content` text NOT NULL,
            `date` date NOT NULL,
            `image_url` varchar(255) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        echo "Table 'news' créée avec succès.<br>";
    } else {
        echo "La table 'news' existe déjà.<br>";
    }
    
    // Vérifier si la table des promotions existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'promotions'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table des promotions si elle n'existe pas
        $pdo->exec("CREATE TABLE IF NOT EXISTS `promotions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `description` text NOT NULL,
            `start_date` date NOT NULL,
            `end_date` date NOT NULL,
            `image_url` varchar(255) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        echo "Table 'promotions' créée avec succès.<br>";
    } else {
        echo "La table 'promotions' existe déjà.<br>";
    }
    
    // Vérifier si la table des administrateurs existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'admins'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table des administrateurs si elle n'existe pas
        $pdo->exec("CREATE TABLE IF NOT EXISTS `admins` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `password` varchar(255) NOT NULL,
            `email` varchar(100) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        // Créer un administrateur par défaut (mot de passe: admin123)
        $username = 'admin';
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $email = 'admin@example.com';
        
        $stmt = $pdo->prepare("INSERT INTO `admins` (username, password, email) VALUES (?, ?, ?)");
        $stmt->execute([$username, $password, $email]);
        
        echo "Table 'admins' créée avec succès.<br>";
        echo "<strong>Compte administrateur créé :</strong><br>";
        echo "Nom d'utilisateur: admin<br>";
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
    
    <a href="/index.php" class="btn">Retour à la page de connexion</a>
</body>
</html>
