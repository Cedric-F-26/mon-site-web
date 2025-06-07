<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'actualites';

// Gestion des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'add_news':
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');
                $image_path = '';

                $publish_start_date = trim($_POST['publish_start_date'] ?? '');
                $publish_end_date = trim($_POST['publish_end_date'] ?? '');

                $publish_start_date = !empty($publish_start_date) ? $publish_start_date : null;
                $publish_end_date = !empty($publish_end_date) ? $publish_end_date : null;

                $flashMessageSet = false;

                if (empty($title) || empty($content)) {
                    setFlashMessage('error', 'Le titre et le contenu sont requis.');
                    $flashMessageSet = true;
                } else {
                    // Gestion de l'upload d'image
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $uploadResult = uploadImage($_FILES['image'], UPLOAD_PATH . 'actualites/');
                        if (is_array($uploadResult) && isset($uploadResult['success'])) {
                            if ($uploadResult['success']) {
                                $image_path = UPLOAD_URL . 'actualites/' . ($uploadResult['filename'] ?? '');
                            } else {
                                setFlashMessage('error', $uploadResult['message'] ?? 'Erreur de téléversement inconnue.');
                                $flashMessageSet = true;
                            }
                        } else {
                            setFlashMessage('error', 'Erreur technique : résultat du téléversement invalide.');
                            $flashMessageSet = true;
                        }
                    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                        // Gérer les autres erreurs de téléversement, sauf si aucun fichier n'a été soumis
                        setFlashMessage('error', 'Erreur lors du téléversement de l\'image (Code: ' . $_FILES['image']['error'] . ').');
                        $flashMessageSet = true;
                    }

                    // Insertion en base de données seulement si aucune erreur n'a été rencontrée avant
                    if (!$flashMessageSet) {
                        $stmt = $pdo->prepare("INSERT INTO news (title, content, image_url, publish_start_date, publish_end_date) VALUES (?, ?, ?, ?, ?)");
                        if ($stmt->execute([$title, $content, $image_path, $publish_start_date, $publish_end_date])) {
                            setFlashMessage('success', 'Actualité ajoutée avec succès.');
                        } else {
                            setFlashMessage('error', 'Erreur lors de l\'ajout de l\'actualité.');
                        }
                    }
                }
                break;

            case 'delete_news':
                $news_id = $_POST['news_id'] ?? null;
                if ($news_id) {
                    // Récupérer l'URL de l'image avant de supprimer l'entrée
                    $stmt_img = $pdo->prepare("SELECT image_url FROM news WHERE id = ?");
                    $stmt_img->execute([$news_id]);
                    $image_to_delete = $stmt_img->fetchColumn();

                    $stmt = $pdo->prepare("DELETE FROM news WHERE id = ?");
                    if ($stmt->execute([$news_id])) {
                        setFlashMessage('success', 'Actualité supprimée avec succès.');
                        // Supprimer le fichier image si il existe et n'est pas vide
                        if ($image_to_delete && file_exists(ROOT_PATH . $image_to_delete)) {
                            unlink(ROOT_PATH . $image_to_delete);
                        }
                    } else {
                        setFlashMessage('error', 'Erreur lors de la suppression de l\'actualité.');
                    }
                } else {
                    setFlashMessage('error', 'ID de l\'actualité manquant.');
                }
                break;

            case 'add_promo': // Placeholder - sera traité plus tard
                setFlashMessage('info', 'La fonctionnalité d\'ajout de promotion sera bientôt disponible.');
                // $stmt = $pdo->prepare("INSERT INTO promotions (title, description, start_date, end_date, image_url) VALUES (?, ?, ?, ?, ?)");
                // $stmt->execute([$_POST['title'], $_POST['description'], $_POST['start_date'], $_POST['end_date'], $_POST['image_url']]);
                break;

            case 'delete_promo': // Placeholder - sera traité plus tard
                setFlashMessage('info', 'La fonctionnalité de suppression de promotion sera bientôt disponible.');
                // $stmt = $pdo->prepare("DELETE FROM promotions WHERE id = ?");
                // $stmt->execute([$_POST['promo_id']]);
                break;
        }
    } catch (PDOException $e) {
        error_log('Erreur PDO: ' . $e->getMessage());
        setFlashMessage('error', 'Une erreur de base de données est survenue.');
    } catch (Exception $e) {
        error_log('Erreur: ' . $e->getMessage());
        setFlashMessage('error', 'Une erreur inattendue est survenue.');
    }
    // Rediriger pour afficher le message flash et éviter la resoumission du formulaire
    header('Location: dashboard.php?tab=' . $active_tab);
    exit;
}

// Récupération des actualités
$news = $pdo->query("SELECT * FROM news ORDER BY created_at DESC")->fetchAll();
$promotions = $pdo->query("SELECT * FROM promotions ORDER BY start_date DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modification du site - Franchini</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: #3D6AA2;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #2c4c73;
        }
        .btn i {
            font-size: 14px;
        }
        .flash-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 1rem;
            text-align: center;
        }
        .flash-message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .flash-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .flash-message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <header class="dashboard-header">
            <h1>Modification du site</h1>
            <a href="/logout.php" class="btn-logout">Déconnexion</a>
        </header>

        <div class="tabs">
            <a href="/?tab=actualites" class="tab <?php echo $active_tab === 'actualites' ? 'active' : ''; ?>">
                <i class="fas fa-newspaper"></i> Actualités
            </a>
            <a href="/?tab=magasin" class="tab <?php echo $active_tab === 'magasin' ? 'active' : ''; ?>">
                <i class="fas fa-store"></i> Magasin
            </a>
            <a href="/?tab=occasions" class="tab <?php echo $active_tab === 'occasions' ? 'active' : ''; ?>">
                <i class="fas fa-tractor"></i> Occasions
            </a>
        </div>

        <div class="dashboard-content">
            <?php $flashMessage = getFlashMessage(); if ($flashMessage): ?>
                <div class="flash-message <?php echo htmlspecialchars($flashMessage['type']); ?>">
                    <?php echo htmlspecialchars($flashMessage['message']); ?>
                </div>
            <?php endif; ?>
            <?php if ($active_tab === 'actualites'): ?>
                <section class="section-news">
                    <div class="section-header">
                        <h2>Gestion des actualités</h2>
                        <a href="/add-actualite.html" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Ajouter une actualité
                        </a>
                    </div>
                    <form method="POST" class="form-add" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_news">
                        <div class="form-group">
                            <label>TITRE DE L'ACTUALITÉ</label>
                            <input type="text" name="title" required>
                        </div>
                        <div class="form-group">
                            <label>Contenu</label>
                            <textarea name="content" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Date de début de publication</label>
                            <input type="date" name="publish_start_date">
                        </div>
                        <div class="form-group">
                            <label>Date de fin de publication</label>
                            <input type="date" name="publish_end_date">
                        </div>
                        <div class="form-group">
                            <label>Image</label>
                            <input type="file" name="image" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn-submit">Ajouter une actualité</button>
                    </form>

                    <div class="news-list">
                        <?php
                        foreach ($news as $item):
                        ?>
                        <div class="news-item">
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="/<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="news-image">
                            <?php else: ?>
                                <div class="news-image-placeholder">Image non disponible</div>
                            <?php endif; ?>
                            <div class="news-content">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p><?php echo nl2br(htmlspecialchars($item['content'])); ?></p>
                                <div class="news-dates">
                                    <?php 
                                    $publish_start_formatted = '';
                                    if (!empty($item['publish_start_date'])) {
                                        try {
                                            $startDate = new DateTime($item['publish_start_date']);
                                            $publish_start_formatted = $startDate->format('d/m/Y');
                                        } catch (Exception $e) { $publish_start_formatted = 'Date invalide'; }
                                    }

                                    $publish_end_formatted = '';
                                    if (!empty($item['publish_end_date'])) {
                                        try {
                                            $endDate = new DateTime($item['publish_end_date']);
                                            $publish_end_formatted = $endDate->format('d/m/Y');
                                        } catch (Exception $e) { $publish_end_formatted = 'Date invalide'; }
                                    }
                                    ?>
                                    <?php if ($publish_start_formatted || $publish_end_formatted): ?>
                                        <p style="font-size: 0.9em; color: #555;">
                                            <?php if ($publish_start_formatted): ?>
                                                <strong>Début:</strong> <?php echo $publish_start_formatted; ?><br>
                                            <?php endif; ?>
                                            <?php if ($publish_end_formatted): ?>
                                                <strong>Fin:</strong> <?php echo $publish_end_formatted; ?>
                                            <?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <form method="POST" class="form-delete" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette actualité ?');">
                                    <input type="hidden" name="action" value="delete_news">
                                    <input type="hidden" name="news_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn-delete">Supprimer</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

            <?php elseif ($active_tab === 'magasin'): ?>
                <section class="section-magasin">
                    <h2>Gestion des promotions magasin</h2>
                    <form method="POST" class="form-add" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_promo">
                        <div class="form-group">
                            <label>Titre de la promotion</label>
                            <input type="text" name="title" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Prix</label>
                            <input type="number" name="price" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Image</label>
                            <input type="file" name="image" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn-submit">Ajouter une promotion</button>
                    </form>
                </section>

            <?php elseif ($active_tab === 'occasions'): ?>
                <section class="section-occasions">
                    <h2>Gestion des occasions</h2>
                    <form method="POST" class="form-add" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_occasion">
                        <div class="form-group">
                            <label>Modèle</label>
                            <input type="text" name="model" required>
                        </div>
                        <div class="form-group">
                            <label>Année</label>
                            <input type="number" name="year" required>
                        </div>
                        <div class="form-group">
                            <label>Heures de fonctionnement</label>
                            <input type="number" name="hours" required>
                        </div>
                        <div class="form-group">
                            <label>Prix</label>
                            <input type="number" name="price" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Images</label>
                            <input type="file" name="images[]" accept="image/*" multiple required>
                        </div>
                        <button type="submit" class="btn-submit">Ajouter une occasion</button>
                    </form>
                </section>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 
