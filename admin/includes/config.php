<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');
define('DB_NAME', 'franchini_admin');

// Configuration de la session
session_start();

// Fonction de redirection
function redirect($url) {
    header("Location: $url");
    exit();
}

// Vérifier si l'utilisateur est connecté
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Protéger une page
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        redirect('login.php');
    }
}
?>
