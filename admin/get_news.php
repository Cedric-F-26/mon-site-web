<?php
require_once 'config.php'; // Assurez-vous que ce chemin est correct pour accéder à votre config PDO

header('Content-Type: application/json');

$today = date('Y-m-d');

try {
    // Sélectionner les actualités dont la date de début est passée ou aujourd'hui
    // ET (la date de fin n'est pas définie OU la date de fin est future ou aujourd'hui)
    // Trier par date de création la plus récente
    $stmt = $pdo->prepare("
        SELECT id, title, content, image_url, publish_start_date, publish_end_date, created_at 
        FROM news 
        WHERE 
            (publish_start_date IS NULL OR publish_start_date <= :today1) AND 
            (publish_end_date IS NULL OR publish_end_date >= :today2) 
        ORDER BY created_at DESC
    ");
    $stmt->bindParam(':today1', $today);
    $stmt->bindParam(':today2', $today);
    $stmt->execute();
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Renommer les clés pour correspondre à ce qu'attend news-carousel.js (titre, contenu, image_url, date)
    $formatted_news = [];
    foreach ($news as $item) {
        $formatted_news[] = [
            'id' => $item['id'],
            'titre' => $item['title'], // 'titre' au lieu de 'title'
            'contenu' => $item['content'], // 'contenu' au lieu de 'content'
            'image_url' => !empty($item['image_url']) ? $item['image_url'] : 'assets/images/default-news.jpg', // Chemin relatif à la racine du site
            'date' => $item['created_at'] // Utiliser created_at pour le tri, ou publish_start_date si pertinent
        ];
    }

    echo json_encode(['success' => true, 'actualites' => $formatted_news]);

} catch (PDOException $e) {
    error_log('Erreur PDO dans get_news.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
} catch (Exception $e) {
    error_log('Erreur dans get_news.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue.']);
}
?>
