<?php
require_once 'config.php';
requireLogin();

// Vérifier si un ID d'actualité est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID d\'actualité non valide.');
    redirect('list-actualites.php');
}

$id = (int)$_GET['id'];

// Récupérer l'actualité existante
$stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
$stmt->execute([$id]);
$actualite = $stmt->fetch();

// Vérifier si l'actualité existe
if (!$actualite) {
    setFlashMessage('error', 'Actualité non trouvée.');
    redirect('list-actualites.php');
}

$pageTitle = 'Modifier une actualité';
$formAction = 'update-actualite.php';

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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
        ");
        
        $stmt->execute([
            $titre,
            $contenu,
            $date,
            $imagePath,
            $id
        ]);
        
        setFlashMessage('success', 'L\'actualité a été mise à jour avec succès.');
        redirect('list-actualites.php');
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Inclure l'en-tête
include 'includes/header.php';
?>

<div class="container">
    <h1><?php echo $pageTitle; ?></h1>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo $formAction; ?>" enctype="multipart/form-data" class="actualite-form">
        <input type="hidden" name="id" value="<?php echo $actualite['id']; ?>">
        
        <div class="form-group">
            <label for="titre">Titre *</label>
            <input type="text" id="titre" name="titre" value="<?php echo escape($actualite['title']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="date">Date de publication *</label>
            <input type="date" id="date" name="date" value="<?php echo date('Y-m-d', strtotime($actualite['date'])); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Image actuelle</label>
            <?php if (!empty($actualite['image_url'])): ?>
                <div class="current-image">
                    <img src="../<?php echo $actualite['image_url']; ?>" alt="Image actuelle" style="max-width: 300px; display: block; margin: 10px 0;">
                </div>
            <?php else: ?>
                <p>Aucune image actuelle</p>
            <?php endif; ?>
            
            <label for="image">Nouvelle image (laisser vide pour conserver l'actuelle)</label>
            <div id="drop-zone" class="drop-zone">
                <div class="drop-zone-content">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Glissez-déposez une image ici ou cliquez pour sélectionner</p>
                </div>
                <input type="file" id="image" name="image" accept="image/*" style="display: none;">
                <img id="image-preview" src="#" alt="Aperçu de l'image" style="display: none; max-width: 100%; margin-top: 15px;">
            </div>
            <p class="help-text">Formats acceptés : JPG, PNG, GIF, WebP. Taille maximale : 5 Mo.</p>
        </div>
        
        <div class="form-group">
            <label for="contenu">Contenu *</label>
            <textarea id="contenu" name="contenu" required><?php echo escape($actualite['content']); ?></textarea>
        </div>
        
        <div class="form-actions">
            <a href="list-actualites.php" class="btn btn-cancel">Annuler</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

<!-- Charger TinyMCE -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
// Configuration de TinyMCE
tinymce.init({
    selector: '#contenu',
    height: 400,
    menubar: false,
    plugins: [
        'advlist autolink lists link image charmap print preview anchor',
        'searchreplace visualblocks code fullscreen',
        'insertdatetime media table paste code help wordcount'
    ],
    toolbar: 'undo redo | formatselect | ' +
    'bold italic backcolor | alignleft aligncenter ' +
    'alignright alignjustify | bullist numlist outdent indent | ' +
    'removeformat | help',
    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px }',
    language: 'fr_FR'
});

// Gestion du glisser-déposer pour l'image
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('image');
    const imagePreview = document.getElementById('image-preview');
    const dropZoneContent = dropZone.querySelector('.drop-zone-content');
    
    // Empêcher les comportements par défaut
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    // Ajouter/supprimer la classe highlight
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        dropZone.classList.add('highlight');
    }
    
    function unhighlight() {
        dropZone.classList.remove('highlight');
    }
    
    // Gérer le dépôt de fichier
    dropZone.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }
    
    // Gérer la sélection de fichier via le bouton
    dropZone.addEventListener('click', function() {
        fileInput.click();
    });
    
    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });
    
    // Afficher la prévisualisation de l'image
    function handleFiles(files) {
        if (files.length > 0) {
            const file = files[0];
            
            // Vérifier le type de fichier
            if (!file.type.match('image.*')) {
                alert('Veuillez sélectionner un fichier image valide (JPEG, PNG, GIF, WebP)');
                return;
            }
            
            // Vérifier la taille du fichier (max 5 Mo)
            if (file.size > 5 * 1024 * 1024) {
                alert('L\'image ne doit pas dépasser 5 Mo');
                return;
            }
            
            // Afficher la prévisualisation
            const reader = new FileReader();
            
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
                dropZoneContent.style.display = 'none';
            };
            
            reader.readAsDataURL(file);
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
