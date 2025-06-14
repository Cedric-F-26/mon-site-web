<?php
session_start();
require_once __DIR__ . '/config.php'; // Charge la configuration de l'application et initialise la session

// Si l'utilisateur est déjà connecté (la session est définie par login.php après une connexion réussie)
if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
} else {
    // Si non connecté, rediriger vers la page de connexion correcte
    header('Location: login.php');
    exit;
}
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Intranet - Franchini</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-lock"></i>
                <h1>Connexion Intranet</h1>
            </div>
            <?php if (isset($error)): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Nom d'utilisateur
                    </label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-key"></i>
                        Mot de passe
                    </label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </button>
            </form>
            <a href="../index.html" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Retour au site
            </a>
        </div>
    </div>
</body>
</html>
