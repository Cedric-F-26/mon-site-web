<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration de l'environnement
define('ENVIRONMENT', 'development'); // 'development' ou 'production'

// Configuration des erreurs
if (defined('ENVIRONMENT')) {
    if (ENVIRONMENT === 'development') {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    } else {
        error_reporting(0);
        ini_set('display_errors', 0);
    }
}

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'franchini_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// URL de base du site
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . str_replace('index.php', '', $_SERVER['SCRIPT_NAME']));

// Chemins des dossiers
define('ROOT_PATH', dirname(__DIR__) . '/');
define('ADMIN_PATH', __DIR__ . '/');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'admin/uploads/');

// Création des dossiers nécessaires s'ils n'existent pas
$directories = [
    UPLOAD_PATH . 'actualites/',
    UPLOAD_PATH . 'promotions/',
    UPLOAD_PATH . 'occasions/'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Connexion à la base de données
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    } else {
        die("Une erreur est survenue. Veuillez réessayer plus tard.");
    }
}

// Fonctions utilitaires
function redirect($path) {
    header("Location: " . $path);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['admin']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        redirect('login.php');
    }
}

// Protection contre les failles XSS
function escape($data) {
    if (is_array($data)) {
        return array_map('escape', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Nettoyage des entrées
function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Gestion des messages flash
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Fonction pour générer un nom de fichier unique
function generateUniqueFilename($filename, $uploadPath) {
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $basename = pathinfo($filename, PATHINFO_FILENAME);
    
    // Nettoyer le nom du fichier
    $basename = preg_replace('/[^a-zA-Z0-9_-]/', '', $basename);
    $basename = substr($basename, 0, 50); // Limiter la longueur du nom
    
    $filename = $basename . '.' . $extension;
    $counter = 1;
    
    // Vérifier si le fichier existe déjà
    while (file_exists($uploadPath . $filename)) {
        $filename = $basename . '_' . $counter . '.' . $extension;
        $counter++;
    }
    
    return $filename;
}

// Fonction pour valider et télécharger une image
function uploadImage($file, $targetDir) {
    // Vérifier les erreurs de téléchargement initiales
    if ($file['error'] !== UPLOAD_ERR_OK) {
        // Fournir un message plus spécifique basé sur le code d'erreur
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'message' => 'Le fichier est trop volumineux.'];
            case UPLOAD_ERR_PARTIAL:
                return ['success' => false, 'message' => 'Le fichier n\'a été que partiellement téléchargé.'];
            case UPLOAD_ERR_NO_FILE:
                // Ce cas est généralement géré avant d'appeler uploadImage
                return ['success' => false, 'message' => 'Aucun fichier n\'a été téléchargé.'];
            case UPLOAD_ERR_NO_TMP_DIR:
                return ['success' => false, 'message' => 'Dossier temporaire manquant sur le serveur.'];
            case UPLOAD_ERR_CANT_WRITE:
                return ['success' => false, 'message' => 'Échec de l\'écriture du fichier sur le disque.'];
            case UPLOAD_ERR_EXTENSION:
                return ['success' => false, 'message' => 'Une extension PHP a arrêté le téléchargement du fichier.'];
            default:
                return ['success' => false, 'message' => 'Erreur inconnue lors du téléchargement du fichier.'];
        }
    }
    
    // Vérifier le type MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if (!$finfo) {
        return ['success' => false, 'message' => 'Impossible d\'ouvrir la base de données fileinfo.'];
    }
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    
    if (!array_key_exists($mime, $allowedTypes)) {
        return ['success' => false, 'message' => 'Format de fichier non autorisé. Utilisez JPEG, PNG, GIF ou WebP. (Type détecté: ' . htmlspecialchars($mime) . ')'];
    }
    
    // Vérifier la taille du fichier (max 5 Mo)
    $maxSize = 5 * 1024 * 1024; // 5 Mo
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'La taille du fichier ne doit pas dépasser 5 Mo.'];
    }
    
    // S'assurer que le répertoire cible existe et est accessible en écriture
    if (!is_dir($targetDir) || !is_writable($targetDir)) {
        // Essayer de créer le répertoire s'il n'existe pas
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
             return ['success' => false, 'message' => 'Le répertoire de destination n\'existe pas et n\'a pas pu être créé.'];
        }
        if (!is_writable($targetDir)) {
            return ['success' => false, 'message' => 'Le répertoire de destination n\'est pas accessible en écriture. Vérifiez les permissions.'];
        }
    }

    // Générer un nom de fichier unique
    $extension = $allowedTypes[$mime];
    // Utiliser la fonction generateUniqueFilename si elle existe, sinon une simple uniqid
    if (function_exists('generateUniqueFilename')) {
        $newFilename = generateUniqueFilename(basename($file['name']), $targetDir);
    } else {
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $safeOriginalName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $originalName);
        $newFilename = $safeOriginalName . '_' . uniqid() . '.' . $extension;
    }
    $targetPath = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newFilename;
    
    // Déplacer le fichier téléchargé
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement du fichier.'];
    }
    
    // Redimensionner l'image si nécessaire (optionnel) - Laisser pour plus tard
    // ...
    
    return ['success' => true, 'filename' => $newFilename];
}
