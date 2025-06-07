<?php
session_start();
require 'config.php';

$error = '';
$success = '';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: forgot-password.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$resetRequest = $stmt->fetch();

if (!$resetRequest) {
    $error = "Lien invalide ou expiré";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($newPassword === $confirmPassword) {
        // Mettre à jour le mot de passe
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $resetRequest['admin_id']]);
        
        // Supprimer la demande de reset
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE admin_id = ?");
        $stmt->execute([$resetRequest['admin_id']]);
        
        $success = "Mot de passe mis à jour avec succès. <a href='login.php'>Se connecter</a>";
    } else {
        $error = "Les mots de passe ne correspondent pas";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe</title>
    <link rel="stylesheet" href="/../assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Définir un nouveau mot de passe</h2>
        
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="password">Nouveau mot de passe :</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe :</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>
                <button type="submit" class="btn">Réinitialiser</button>
            </form>
        <?php endif; ?>
        
        <p><a href="/login.php">Retour à la connexion</a></p>
    </div>
</body>
</html>
