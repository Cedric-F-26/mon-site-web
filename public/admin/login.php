<?php
session_start();
require_once 'config.php';

// Générer et stocker un token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_message = '';

// Brute-force protection
$max_attempts = 5;
$lockout_time = 300; // 5 minutes

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = 0;
}

// Si l'utilisateur est déjà connecté, rediriger vers le tableau de bord
if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = 'Erreur de sécurité. Veuillez recharger la page.';
    } else {
        // Check if locked out
        if ($_SESSION['login_attempts'] >= $max_attempts && 
            (time() - $_SESSION['last_attempt_time']) < $lockout_time) {
            $error_message = 'Trop de tentatives échouées. Veuillez réessayer dans 5 minutes.';
        } else {
            // Validation et assainissement des entrées
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
            $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
            $is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

            if (!empty($username) && !empty($password)) {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username OR email = :username");
                    $stmt->bindParam(':username', $username);
                    $stmt->execute();
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($admin && password_verify($password, $admin['password'])) {
                        // Reset attempt counter on successful login
                        $_SESSION['login_attempts'] = 0;
                        
                        // Régénération de l'ID de session
                        session_regenerate_id(true);
                        
                        $_SESSION['admin'] = $admin;
                        if ($is_ajax_request) {
                            echo json_encode(['success' => true, 'redirect_url' => 'dashboard.php']);
                        } else {
                            header('Location: dashboard.php');
                        }
                        exit;
                    } else {
                        // Increment failed attempt counter
                        $_SESSION['login_attempts']++;
                        $_SESSION['last_attempt_time'] = time();
                        $error_message = 'Identifiants incorrects. Veuillez réessayer.';
                    }
                } catch (PDOException $e) {
                    error_log('PDOException: ' . $e->getMessage());
                    $error_message = 'Erreur de base de données. Veuillez réessayer.';
                }
            } else {
                $error_message = 'Veuillez saisir un nom d\'utilisateur et un mot de passe.';
            }
        }
    }
    
    // Si c'est une requête AJAX et qu'il y a une erreur (après toutes les tentatives de connexion)
    if ($is_ajax_request && !empty($error_message)) {
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit;
    }
    // Si ce n'est pas AJAX et qu'il y a une erreur, $error_message sera affiché par le HTML plus bas.
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administrateur - Franchini</title>
    <link rel="stylesheet" href="/../assets/css/style.css">
    <link rel="stylesheet" href="/../assets/css/footer-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Styles repris de connexion-prive.html et adaptés */
        body {
            font-family: 'Roboto', 'Arial', sans-serif;
            background-color: #f4f7f6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }
        .main-header, .footer-container {
            flex-shrink: 0; /* Empêche header/footer de rétrécir */
        }
        main.login-main-content {
            flex-grow: 1; /* Permet à main de prendre l'espace restant */
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 1rem; /* Ajout de padding */
            background-color: #fff; /* Fond blanc pour la zone de contenu principal */
        }
        .login-box {
            background-color: #fff; /* Le fond est déjà blanc, mais on s'assure qu'il est sur un fond de page blanc */
            border: 1px solid #e0e0e0; /* Bordure légère comme sur l'image */
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); /* Ombre portée plus subtile */
            padding: 2rem;
            width: 100%;
            max-width: 360px; /* Réduction de la largeur max */
        }
        .login-box-header {
            text-align: center;
            margin-bottom: 1.5rem; /* Réduit l'espace pour compacter */
        }
        .login-box-header .icon-lock { /* Style pour l'icône cadenas */
            font-size: 2.5rem; /* Icône un peu plus petite */
            color: #6abd7a; /* Vert Franchini (ajusté pour correspondre à l'image) */
            margin-bottom: 0.8rem;
            display: block;
        }
        .login-box-header h1 {
            color: #333;
            font-size: 1.5rem; /* Titre un peu plus petit */
            font-weight: 600;
            margin: 0;
        }
        .error-message-box {
            background-color: #ffebee;
            color: #c62828;
            padding: 0.8rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }
        .error-message-box i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }
        .form-group {
            margin-bottom: 1rem; /* Espacement réduit */
        }
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            color: #555;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .form-group label i {
            margin-right: 0.5rem;
            color: #6abd7a;
        }
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 0.7rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.95rem;
            box-sizing: border-box; 
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            border-color: #6abd7a;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(106, 189, 122, 0.25);
        }
        .btn-submit-login {
            background-color: #6abd7a; /* Vert Franchini */
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.7rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .btn-submit-login i {
            margin-right: 0.5rem;
        }
        .btn-submit-login:hover {
            background-color: #58a066; /* Vert Franchini plus foncé */
        }
        .login-links {
            text-align: center;
            margin-top: 1rem;
        }
        .login-links a {
            color: #555;
            font-size: 0.85rem;
            text-decoration: none;
            display: block; /* Pour qu'ils soient l'un au-dessus de l'autre */
        }
        .login-links a:hover {
            text-decoration: underline;
        }
        .login-links a.forgot-password {
            margin-bottom: 0.5rem;
        }
        .login-links a i {
            margin-right: 0.3rem;
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="logo">
                <a href="/../index.html" class="logo-link">
                    <img src="/../assets/images/logo/FRANCHINI logo.svg" alt="FRANCHINI Logo" class="logo-image">
                    <span class="logo-text">FRANCHINI</span>
                </a>
                <div class="header-social">
                    <a href="https://www.facebook.com/franchinimarches" target="_blank" class="social-icon"><i class="fab fa-facebook"></i></a>
                    <a href="https://www.instagram.com/franchini_deutzfahr" target="_blank" class="social-icon"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="/../pages/concession.html">La concession</a></li>
                    <li class="dropdown">
                        <a href="#">Matériel neuf</a>
                        <div class="dropdown-content">
                            <a href="/../pages/materiel.html">Matériel neuf</a>
                            <a href="/../pages/materiel-disponible.html">Matériel neuf disponible</a>
                        </div>
                    </li>
                    <li><a href="/../pages/location.html">Location</a></li>
                    <li><a href="/../pages/occasion.html">Occasion</a></li>
                    <li><a href="/../pages/magasin.html">Magasin</a></li>
                    <li><a href="/../pages/contact.html">Contact</a></li>
                    <li><a href="/tel:0475474037" class="phone-number"><i class="fas fa-phone"></i> 04 75 47 40 37</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="login-main-content">
        <div class="login-box">
            <div class="login-box-header">
                <i class="fas fa-lock icon-lock"></i>
                <h1>Connexion Privée</h1>
            </div>

            <?php if (!empty($error_message)): ?>
            <div class="error-message-box">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Nom d'utilisateur
                    </label>
                    <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-key"></i>
                        Mot de passe
                    </label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-submit-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </button>
            </form>
            <p class="text-center mt-3">
                <a href="/forgot-password.php">Mot de passe oublié ?</a>
            </p>
            <p class="text-center mt-2">
                <a href="/../index.html">← Retour au site</a>
            </p>
        </div>
    </main>

    <footer class="footer-container">
        <div class="footer-content">
            <div class="footer-section about">
                <a href="/../index.html"><img src="/../assets/images/logo/FRANCHINI logo.svg" alt="Logo Franchini Footer" class="footer-logo"></a>
                <p class="footer-company-name">FRANCHINI</p>
                <p class="footer-address">111 Av des Monts du Matin<br>26300 Marches</p>
                <p class="footer-phone"><i class="fas fa-phone"></i> <a href="/tel:0475474037">04 75 47 40 37</a></p>
            </div>
            <div class="footer-section links">
                <h3>Navigation</h3>
                <ul>
                    <li><a href="/../index.html">Accueil</a></li>
                    <li><a href="/../pages/concession.html">La concession</a></li>
                    <li><a href="/../pages/materiel.html">Matériel neuf</a></li>
                    <li><a href="/../pages/location.html">Location</a></li>
                    <li><a href="/../pages/occasion.html">Occasion</a></li>
                    <li><a href="/../pages/magasin.html">Magasin</a></li>
                    <li><a href="/../pages/contact.html">Contact</a></li>
                    <li><a href="/../pages/mentions-legales.html">Mentions Légales</a></li>
                </ul>
            </div>
            <div class="footer-section contact-info">
                <h3>Horaires d'ouverture</h3>
                <p><strong>Magasin :</strong> Lundi au Vendredi<br>8h-12h et 14h-18h</p>
                <p>Samedi : 8h-12h (fermé l'après-midi)</p>
                <p><strong>Atelier :</strong> Lundi au Vendredi<br>8h-12h et 14h-18h</p>
                <div class="footer-social">
                    <a href="https://www.facebook.com/franchinimarches" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/franchini_deutzfahr" target="_blank"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <span id="currentYear"></span> Franchini. Tous droits réservés. Site réalisé par <a href="https://www.votre-agence-web.com" target="_blank">VotreAgenceWeb</a>. <a href="/../pages/connexion-prive.html" class="footer-intranet"><i class="fas fa-lock"></i> Connexion privée</a></p>
        </div>
    </footer>
    <script>
        document.getElementById('currentYear').textContent = new Date().getFullYear();
    </script>
</body>
</html>
