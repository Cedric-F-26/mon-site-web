<?php
/**
 * Page de connexion administrateur
 * 
 * Gère l'authentification des utilisateurs avec protection contre les attaques par force brute
 * et validation des jetons CSRF.
 */

// Inclure la configuration
require_once __DIR__ . '/config.php';

// Désactiver la mise en cache pour les pages sensibles
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: geolocation=(), microphone=()');
}

// La session est déjà initialisée par config.php

// Vérifier si l'utilisateur est déjà connecté
if (isLoggedIn()) {
    // Vérifier si la requête est AJAX
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'redirect' => 'dashboard.php',
            'message' => 'Vous êtes déjà connecté. Redirection...'
        ]);
    } else {
        redirect('dashboard.php');
    }
    exit;
}

// Initialiser les variables
$error_message = '';
$success_message = '';
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Récupérer les messages de session s'ils existent
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Vérifier si la base de données est disponible
try {
    $pdo = getDatabaseConnection();
} catch (Exception $e) {
    $error_message = 'Erreur de connexion à la base de données. Veuillez réessayer plus tard.';
    if (ENVIRONMENT === 'development') {
        $error_message .= ' Détails : ' . $e->getMessage();
    }
    Logger::critical('Erreur de connexion à la base de données : ' . $e->getMessage());
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le jeton CSRF
    if (!verifyCSRFToken('POST', 'login_form')) {
        $error_message = 'Erreur de sécurité. Veuillez recharger la page et réessayer.';
        
        // Journaliser la tentative de CSRF
        Logger::warning('Tentative de connexion avec jeton CSRF invalide ou manquant', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'inconnue',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'inconnu',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'inconnue'
        ]);
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'inconnue';
        
        // Journalisation de la tentative de connexion
        Logger::info('Tentative de connexion', [
            'username' => $username,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'inconnu'
        ]);

        // Vérification des identifiants
        if (empty($username) || empty($password)) {
            $error_message = 'Veuillez saisir un nom d\'utilisateur et un mot de passe.';
        } else {
            try {
                // Vérifier le nombre de tentatives de connexion échouées
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND created_at > datetime('now', '-15 minutes')");
                $stmt->execute([$ip]);
                $attempts = (int)$stmt->fetchColumn();
                
                if ($attempts >= 5) {
                    $error_message = 'Trop de tentatives de connexion. Veuillez réessayer dans 15 minutes.';
                    Logger::warning('Trop de tentatives de connexion', ['ip' => $ip]);
                } else {
                    // Vérifier les identifiants dans la base de données
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
                    $stmt->execute([$username]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user && password_verify($password, $user['password'])) {
                        // Réinitialiser le compteur de tentatives en cas de succès
                        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?");
                        $stmt->execute([$ip]);
                        
                        // Mettre à jour la date de dernière connexion
                        $stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP, login_attempts = 0 WHERE id = ?");
                        $stmt->execute([$user['id']]);
                        
                        // Créer la session utilisateur
                        $_SESSION['admin'] = [
                            'id' => $user['id'],
                            'username' => $user['username'],
                            'email' => $user['email']
                        ];
                        
                        // Ajouter des informations de sécurité à la session
                        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                        $_SESSION['ip_address'] = $ip;
                        $_SESSION['last_activity'] = time();
                        
                        // Régénérer l'ID de session pour prévenir les attaques de fixation de session
                        session_regenerate_id(true);
                        
                        // Journaliser la connexion réussie
                        Logger::info('Connexion réussie', [
                            'user_id' => $user['id'],
                            'username' => $user['username']
                        ]);
                        
                        // Gestion du "Se souvenir de moi"
                        if ($remember) {
                            // Générer un jeton unique
                            $token = bin2hex(random_bytes(32));
                            $expires = time() + (86400 * 30); // 30 jours
                            
                            // Stocker le jeton dans la base de données
                            $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, remember_token_expires = ? WHERE id = ?");
                            $stmt->execute([
                                password_hash($token, PASSWORD_DEFAULT),
                                date('Y-m-d H:i:s', $expires),
                                $user['id']
                            ]);
                            
                            // Définir le cookie
                            setcookie(
                                'remember_token',
                                $token,
                                [
                                    'expires' => $expires,
                                    'path' => '/admin',
                                    'domain' => $_SERVER['HTTP_HOST'],
                                    'secure' => true,
                                    'httponly' => true,
                                    'samesite' => 'Strict'
                                ]
                            );
                        }
                        
                        // Rediriger vers le tableau de bord
                        $redirect_to = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
                        unset($_SESSION['redirect_after_login']);
                        
                        // Répondre différemment pour les requêtes AJAX
                        if ($is_ajax_request) {
                            header('Content-Type: application/json');
                            echo json_encode([
                                'success' => true,
                                'redirect' => $redirect_to,
                                'message' => 'Connexion réussie. Redirection...'
                            ]);
                            exit;
                        }
                        
                        // Redirection normale
                        $_SESSION['success_message'] = 'Connexion réussie. Bienvenue ' . htmlspecialchars($user['username']) . '!';
                        redirect($redirect_to);
                        exit;
                    } else {
                        // Échec de l'authentification
                        $error_message = 'Identifiants incorrects. Veuillez réessayer.';
                        
                        // Enregistrer la tentative échouée
                        $stmt = $pdo->prepare("INSERT INTO login_attempts (username, ip, user_agent) VALUES (?, ?, ?)");
                        $stmt->execute([
                            $username,
                            $ip,
                            $_SERVER['HTTP_USER_AGENT'] ?? 'inconnu'
                        ]);
                        
                        // Calculer le nombre de tentatives restantes
                        $remaining_attempts = max(0, 5 - ($attempts + 1));
                        if ($remaining_attempts > 0) {
                            $error_message .= " ($remaining_attempts tentatives restantes)";
                        } else {
                            $error_message = 'Trop de tentatives. Veuillez réessayer dans 15 minutes.';
                        }
                        
                        Logger::warning('Échec de connexion', [
                            'username' => $username,
                            'ip' => $ip,
                            'attempts' => $attempts + 1
                        ]);
                    }
                }
            } catch (PDOException $e) {
                $error_message = 'Une erreur est survenue lors de la connexion. Veuillez réessayer.';
                Logger::error('Erreur lors de l\'authentification : ' . $e->getMessage(), [
                    'username' => $username,
                    'ip' => $ip,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
}

// Générer un nouveau jeton CSRF pour le formulaire
$csrf_field = csrfField('login_form');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo img {
            max-height: 80px;
            margin-bottom: 15px;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
            padding: 10px 20px;
            font-weight: 500;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
        }
        .alert {
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo">
                <h2>Connexion</h2>
                <p class="text-muted">Accès à l'administration</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form id="loginForm" method="POST" action="login.php" novalidate>
                <?php echo $csrf_field; ?>
                
                <div class="mb-3">
                    <label for="username" class="form-label">Nom d'utilisateur</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                    <div class="invalid-feedback">Veuillez saisir votre nom d'utilisateur.</div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="invalid-feedback">Veuillez saisir votre mot de passe.</div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Se souvenir de moi</label>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Se connecter
                    </button>
                </div>
                
                <div class="text-center mt-3">
                    <a href="forgot-password.php" class="text-decoration-none">Mot de passe oublié ?</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation du formulaire côté client
        (function () {
            'use strict';
            
            // Récupérer le formulaire
            const form = document.getElementById('loginForm');
            const submitBtn = form.querySelector('button[type="submit"]');
            const spinner = submitBtn.querySelector('.spinner-border');
            
            // Désactiver la validation HTML5 par défaut
            form.setAttribute('novalidate', true);
            
            // Écouter la soumission du formulaire
            form.addEventListener('submit', function (event) {
                // Empêcher la soumission par défaut
                event.preventDefault();
                event.stopPropagation();
                
                // Réinitialiser les états de validation
                form.classList.add('was-validated');
                
                // Vérifier la validité du formulaire
                if (form.checkValidity() === false) {
                    return;
                }
                
                // Désactiver le bouton et afficher le spinner
                submitBtn.disabled = true;
                spinner.classList.remove('d-none');
                
                // Si c'est une requête AJAX
                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.action, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                // Préparer les données du formulaire
                const formData = new FormData(form);
                const urlEncodedData = new URLSearchParams(formData).toString();
                
                xhr.onload = function() {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (xhr.status >= 200 && xhr.status < 300) {
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                window.location.reload();
                            }
                        } else {
                            // Afficher le message d'erreur
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-danger';
                            alertDiv.textContent = response.message || 'Une erreur est survenue. Veuillez réessayer.';
                            
                            // Insérer avant le formulaire
                            form.parentNode.insertBefore(alertDiv, form);
                            
                            // Faire défiler jusqu'au message d'erreur
                            alertDiv.scrollIntoView({ behavior: 'smooth' });
                        }
                    } catch (e) {
                        // En cas d'erreur de parsing JSON, recharger la page
                        window.location.reload();
                    } finally {
                        // Réactiver le bouton et masquer le spinner
                        submitBtn.disabled = false;
                        spinner.classList.add('d-none');
                    }
                };
                
                xhr.onerror = function() {
                    // Réactiver le bouton et masquer le spinner en cas d'erreur
                    submitBtn.disabled = false;
                    spinner.classList.add('d-none');
                    
                    // Afficher un message d'erreur générique
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger';
                    alertDiv.textContent = 'Erreur de connexion. Veuillez vérifier votre connexion Internet et réessayer.';
                    
                    // Insérer avant le formulaire
                    form.parentNode.insertBefore(alertDiv, form);
                };
                
                // Envoyer la requête
                xhr.send(urlEncodedData);
            }, false);
        })();
    </script>
</body>
</html>
