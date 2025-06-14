<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Inclure le fichier de configuration de la base de données
require_once __DIR__ . '/config.php';

// Répondre en JSON
header('Content-Type: application/json');

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    // Récupérer les données du formulaire
    $titre = filter_input(INPUT_POST, 'titre', FILTER_SANITIZE_STRING);
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $contenu = filter_input(INPUT_POST, 'contenu', FILTER_SANITIZE_STRING);
    
    // Valider les données
    if (empty($titre) || empty($date) || empty($contenu)) {
        echo json_encode(['success' => false, 'message' => 'Tous les champs sont obligatoires']);
        exit;
    }
    
    // Vérifier si un fichier a été téléchargé
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Veuillez sélectionner une image']);
        exit;
    }
    
    $file = $_FILES['image'];
    
    // Vérifier le type de fichier
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Format de fichier non supporté. Utilisez JPEG, PNG, GIF ou WebP']);
        exit;
    }
    
    // Vérifier la taille du fichier (max 5 Mo)
    $maxSize = 5 * 1024 * 1024; // 5 Mo
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'La taille du fichier ne doit pas dépasser 5 Mo']);
        exit;
    }
    
    // Créer le dossier d'upload s'il n'existe pas
    $uploadDir = __DIR__ . '/uploads/actualites/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Générer un nom de fichier unique
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('actualite_') . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    
    // Déplacer le fichier téléchargé
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Erreur lors du téléchargement du fichier');
    }
    
    // Chemin relatif pour la base de données
    $relativePath = 'admin/uploads/actualites/' . $fileName;
    
    // Insérer les données dans la base de données
    $stmt = $pdo->prepare("INSERT INTO news (title, content, date, image_url) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$titre, $contenu, $date, $relativePath]);
    
    if ($result) {
        // Mettre à jour le localStorage avec les nouvelles actualités
        $stmt = $pdo->query("SELECT * FROM news ORDER BY date DESC");
        $actualites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convertir les dates au format ISO pour le JavaScript
        foreach ($actualites as &$actualite) {
            $dateObj = new DateTime($actualite['date']);
            $actualite['date'] = $dateObj->format('Y-m-d');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Actualité ajoutée avec succès',
            'actualites' => $actualites
        ]);
    } else {
        throw new Exception('Erreur lors de l\'ajout de l\'actualité dans la base de données');
    }
    
} catch (Exception $e) {
    // En cas d'erreur, supprimer le fichier téléchargé s'il existe
    if (isset($filePath) && file_exists($filePath)) {
        unlink($filePath);
    }
    
    error_log('Erreur save-actualite.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Une erreur est survenue: ' . $e->getMessage()
    ]);
}
