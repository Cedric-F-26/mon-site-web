<?php
session_start();
require 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        // Générer un token sécurisé
        $token = bin2hex(random_bytes(50));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Stocker en base
        $stmt = $pdo->prepare("INSERT INTO password_resets (admin_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$admin['id'], $token, $expires]);
        
        // Envoyer l'email (exemple simplifié)
        $resetLink = "http://".$_SERVER['HTTP_HOST']."/admin/reset-password.php?token=$token";
        // En production: mail($email, "Réinitialisation de mot de passe", "Cliquez ici: $resetLink");
        
        $message = "Un email de réinitialisation a été envoyé à $email";
    } else {
        $message = "Aucun compte associé à cet email";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié</title>
    <link rel="stylesheet" href="/../assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Réinitialisation du mot de passe</h2>
        <?php if ($message): ?>
            <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email :</label>
                <input type="email" name="email" id="email" required>
            </div>
            <button type="submit" class="btn">Envoyer le lien de réinitialisation</button>
        </form>
        <p><a href="/login.php">Retour à la connexion</a></p>
    </div>
</body>
</html>
