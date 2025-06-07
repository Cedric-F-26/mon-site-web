<?php
require_once 'config.php';
requireLogin();

$pageTitle = 'Gestion des actualités';

// Traitement de la suppression d'une actualité
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        // Récupérer l'URL de l'image avant de supprimer l'actualité
        $stmt = $pdo->prepare("SELECT image_url FROM news WHERE id = ?");
        $stmt->execute([$id]);
        $actualite = $stmt->fetch();
        
        if ($actualite) {
            // Supprimer l'image du serveur si elle existe
            if (!empty($actualite['image_url']) && file_exists($actualite['image_url'])) {
                unlink($actualite['image_url']);
            }
            
            // Supprimer l'actualité de la base de données
            $stmt = $pdo->prepare("DELETE FROM news WHERE id = ?");
            $stmt->execute([$id]);
            
            setFlashMessage('success', 'L\'actualité a été supprimée avec succès.');
        } else {
            setFlashMessage('error', 'Actualité non trouvée.');
        }
        
    } catch (Exception $e) {
        setFlashMessage('error', 'Erreur lors de la suppression de l\'actualité : ' . $e->getMessage());
    }
    
    // Rediriger pour éviter la soumission multiple du formulaire
    redirect('list-actualites.php');
}

// Récupérer la liste des actualités
$stmt = $pdo->query("SELECT * FROM news ORDER BY date DESC, created_at DESC");
$actualites = $stmt->fetchAll();

// Inclure l'en-tête
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><?php echo $pageTitle; ?></h1>
        <a href="/add-actualite.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Ajouter une actualité
        </a>
    </div>
    
    <?php 
    // Afficher les messages flash
    $flash = getFlashMessage();
    if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>
    
    <?php if (count($actualites) > 0): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Titre</th>
                        <th>Date de publication</th>
                        <th>Date de création</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($actualites as $actualite): ?>
                        <tr>
                            <td>
                                <?php if (!empty($actualite['image_url'])): ?>
                                    <img src="/../<?php echo $actualite['image_url']; ?>" alt="" class="thumbnail">
                                <?php else: ?>
                                    <span class="no-image">Aucune image</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo escape($actualite['title']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($actualite['date'])); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($actualite['created_at'])); ?></td>
                            <td class="actions">
                                <a href="/edit-actualite.php?id=<?php echo $actualite['id']; ?>" class="btn btn-edit" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="#" 
                                   class="btn btn-delete" 
                                   title="Supprimer"
                                   onclick="return confirmDelete(<?php echo $actualite['id']; ?>, '<?php echo addslashes(htmlspecialchars($actualite['title'])); ?>')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="no-data">
            <p>Aucune actualité n'a été trouvée.</p>
            <a href="/add-actualite.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Ajouter votre première actualité
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete(id, title) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'actualité "${title}" ?`)) {
        window.location.href = `list-actualites.php?action=delete&id=${id}`;
    }
    return false;
}
</script>

<?php include 'includes/footer.php'; ?>
