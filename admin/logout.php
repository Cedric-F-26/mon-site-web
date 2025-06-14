<?php
/**
 * Script de déconnexion sécurisé
 * 
 * Gère la déconnexion des utilisateurs en toute sécurité et nettoie la session
 */

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Désactiver la mise en cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Inclure la configuration
require_once __DIR__ . '/config.php';

// Vérifier si la requête est de type POST (déconnexion sécurisée)
$isPostRequest = $_SERVER['REQUEST_METHOD'] === 'POST';
$isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Vérifier le jeton CSRF pour les requêtes POST
if ($isPostRequest) {
    $csrfValid = !empty($_POST['csrf_token']) && validateCSRFToken($_POST['csrf_token']);
    
    if (!$csrfValid) {
        // Journaliser la tentative de CSRF
        Logger::warning('Tentative de déconnexion avec jeton CSRF invalide', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'inconnue',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'inconnu',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'inconnue'
        ]);
        
        // Répondre avec une erreur 403 Forbidden
        if ($isAjaxRequest) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur de sécurité. Veuillez recharger la page et réessayer.'
            ]);
            exit;
        } else {
            http_response_code(403);
            die('Erreur de sécurité. Veuillez recharger la page et réessayer.');
        }
    }
}

// Journaliser la déconnexion si l'utilisateur était connecté
$username = $_SESSION['admin']['username'] ?? null;
$userId = $_SESSION['admin']['id'] ?? null;

if ($username) {
    Logger::info('Déconnexion utilisateur', [
        'user_id' => $userId,
        'username' => $username,
        'session_id' => session_id(),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'inconnue'
    ]);
}

// Supprimer le cookie remember_me s'il existe
if (isset($_COOKIE['remember_token'])) {
    setcookie(
        'remember_token',
        '',
        [
            'expires' => time() - 3600 * 24 * 30, // Expire il y a 30 jours
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );
}

// Détruire complètement la session
$_SESSION = [];

// Supprimer le cookie de session
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 3600 * 24, // Expirer il y a 24 heures
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );
}

// Détruire la session
session_destroy();

// Si c'est une requête AJAX, renvoyer une réponse JSON
if ($isAjaxRequest) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'redirect' => 'login.php',
        'message' => 'Déconnexion réussie. Redirection...'
    ]);
    exit;
}

// Pour les requêtes non-AJAX, rediriger vers la page de connexion
// Démarrer une nouvelle session pour le message de succès
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['success_message'] = 'Vous avez été déconnecté avec succès.';

// Rediriger vers la page de connexion
header('Location: login.php');
exit;
?>
