<?php
require 'config.php';

echo "Démarrage du test de récupération de mot de passe...\n";

// 1. Créer un admin de test
$testEmail = "test_recovery@example.com";
$testPassword = password_hash("old_password", PASSWORD_DEFAULT);
$pdo->prepare("DELETE FROM admins WHERE email = ?")->execute([$testEmail]);
$pdo->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)")
   ->execute(['test_user', $testEmail, $testPassword]);

// 2. Simuler une demande de réinitialisation
$pdo->prepare("DELETE FROM password_resets WHERE admin_id = (SELECT id FROM admins WHERE email = ?)")
   ->execute([$testEmail]);

require 'forgot-password.php';

// Simuler une requête POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['email'] = $testEmail;
ob_start();
$forgotPassword = new ForgotPassword();
$forgotPassword->handleRequest();
ob_end_clean();

// Récupérer le token généré
$stmt = $pdo->prepare("SELECT token FROM password_resets WHERE admin_id = (SELECT id FROM admins WHERE email = ?)");
$stmt->execute([$testEmail]);
$token = $stmt->fetchColumn();

if (!$token) {
    die("Échec : aucun token généré");
}

// 3. Simuler la réinitialisation
require 'reset-password.php';

// Simuler une requête GET avec token
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['token'] = $token;

// Simuler une requête POST de réinitialisation
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['password'] = "new_password";
$_POST['confirm_password'] = "new_password";

ob_start();
$resetPassword = new ResetPassword();
$resetPassword->handleRequest();
$output = ob_get_clean();

// Vérifier si le mot de passe a été changé
$stmt = $pdo->prepare("SELECT password FROM admins WHERE email = ?");
$stmt->execute([$testEmail]);
$newHash = $stmt->fetchColumn();

if (password_verify("new_password", $newHash)) {
    echo "SUCCÈS : Mot de passe réinitialisé avec succès!\n";
} else {
    die("Échec : le mot de passe n'a pas été mis à jour");
}

// Nettoyage
$pdo->prepare("DELETE FROM admins WHERE email = ?")->execute([$testEmail]);
$pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);

echo "Tous les tests ont réussi!\n";
