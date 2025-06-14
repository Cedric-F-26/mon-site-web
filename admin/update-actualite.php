<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/utils.php';
requireLogin();

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', 'Méthode non autorisée.');
    redirect('list-actualites.php');
}

// Vérifier si l'ID est présent
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    setFlashMessage('error', 'ID d\'actualité non valide.');
    redirect('list-actualites.php');
}

$id = (int)$_POST['id'];

try {
    // Récupérer l'actualité existante
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $actualite = $stmt->fetch();
    
    // Vérifier si l'actualité existe
    if (!$actualite) {
        throw new Exception('Actualité non trouvée.');
    }
    
    // Récupération et validation des données
    $titre = cleanInput($_POST['titre']);
    $date = cleanInput($_POST['date']);
    $contenu = cleanInput($_POST['contenu']);
    $imageChanged = !empty($_FILES['image']['name']);
    
    // Validation des champs obligatoires
    if (empty($titre) || empty($date) || empty($contenu)) {
        throw new Exception('Tous les champs sont obligatoires.');
    }
    
    // Vérification de la date
    $selectedDate = new DateTime($date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($selectedDate < $today) {
        throw new Exception('La date de publication ne peut pas être dans le passé.');
    }
    
    // Gestion de l'image
    $imagePath = $actualite['image_url'];
    
    if ($imageChanged) {
        // Supprimer l'ancienne image si elle existe
        if (!empty($actualite['image_url']) && file_exists($actualite['image_url'])) {
            unlink($actualite['image_url']);
        }
        
        // Télécharger la nouvelle image
        $targetDir = UPLOAD_PATH . 'actualites/';
        $imagePath = 'uploads/actualites/' . uploadImage($_FILES['image'], $targetDir);
    }
    
    // Mise à jour de l'actualité dans la base de données
    $stmt = $pdo->prepare("
        UPDATE news 
        SET title = ?, content = ?, date = ?, image_url = ?, updated_at = NOW() 
        WHERE id = ?
    
    
    $stmt->execute([
        $titre,
        $contenu,
        $date,
        $imagePath,
        $id
    ]);
    
    setFlashMessage('success', 'L\'actualité a été mise à jour avec succès.');
    
} catch (Exception $e) {
    setFlashMessage('error', 'Erreur lors de la mise à jour de l\'actualité : ' . $e->getMessage());
}

// Rediriger vers la liste des actualités
redirect('list-actualites.php');
