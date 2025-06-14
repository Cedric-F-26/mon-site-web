<?php
/**
 * Fichier de configuration principal
 * 
 * Ce fichier charge la configuration de l'application et initialise
 * les composants essentiels comme la base de données, la session et le système de journalisation.
 */

// Vérifier si app.php a déjà été inclus
if (!defined('BASE_PATH')) {
    // Inclure la configuration de l'application
    require_once __DIR__ . '/config/app.php';
}

// Inclure les fichiers nécessaires (après la configuration et le logger)
require_once __DIR__ . '/includes/Logger.php'; // Logger doit être disponible tôt
require_once __DIR__ . '/config/database.php'; // Contient les détails de connexion à la base de données
require_once __DIR__ . '/config/logger.php'; // Configuration spécifique du logger
require_once __DIR__ . '/includes/init-logger.php'; // Initialisation du logger
require_once __DIR__ . '/includes/csrf.php'; // Fonctions CSRF
require_once __DIR__ . '/includes/utils.php'; // Fonctions utilitaires
require_once __DIR__ . '/includes/security_functions.php'; // Fonctions de sécurité

// Vérifier si la session est active
if (session_status() === PHP_SESSION_NONE) {
    Logger::critical('La session n\'a pas pu être démarrée. Vérifiez config/app.php');
    // Une erreur critique ici signifie que quelque chose est gravement mal configuré dans app.php
    die('Erreur de configuration du serveur. Veuillez réessayer plus tard. (Erreur de session)');
}

// Initialisation du système de journalisation
if (!file_exists(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/includes/Logger.php';

// Charger la configuration de la base de données
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/logger.php';
require_once __DIR__ . '/includes/init-logger.php';
require_once __DIR__ . '/includes/csrf.php';

// Vérifier si la session est déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    // La session sera démarrée par app.php
    Logger::warning('La session n\'est pas démarrée, vérifiez la configuration');
}

// Vérifier si la session a expiré
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
    // Détruire la session et rediriger vers la page de connexion
    session_unset();
    session_destroy();
    
    // Rediriger vers la page de connexion avec un message
    if (!defined('API_REQUEST')) {
        setFlashMessage('error', 'Votre session a expiré. Veuillez vous reconnecter.');
        header('Location: login.php');
        exit;
    }
}

// Mettre à jour le timestamp de la dernière activité
$_SESSION['LAST_ACTIVITY'] = time();

// Régénérer l'ID de session périodiquement pour prévenir les attaques de fixation de session
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > SESSION_REGENERATE_TIME) {
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

// Configuration de la base de données
try {
    // Créer une instance PDO avec journalisation
    class LoggedPDO extends PDO {
        public function prepare($statement, $options = []) {
            try {
                $start = microtime(true);
                $stmt = parent::prepare($statement, $options);
                
                // Retourner une déclaration avec journalisation
                return new class($stmt, $statement, $start) extends PDOStatement {
                    private $pdoStatement;
                    private $query;
                    private $startTime;
                    private $params = [];
                    
                    public function __construct($pdoStatement, $query, $startTime) {
                        $this->pdoStatement = $pdoStatement;
                        $this->query = $query;
                        $this->startTime = $startTime;
                    }
                    
                    public function execute($params = null) {
                        $this->params = $params ?: [];
                        $start = microtime(true);
                        
                        try {
                            $result = $this->pdoStatement->execute($params);
                            $this->logQuery($start);
                            return $result;
                        } catch (PDOException $e) {
                            $this->logError($e, $start);
                            throw $e;
                        }
                    }
                    
                    private function logQuery($startTime) {
                        $executionTime = (microtime() - $startTime) * 1000; // en ms
                        $isSlow = $executionTime > 1000; // 1 seconde
                        
                        $context = [
                            'query' => $this->query,
                            'params' => $this->params,
                            'time_ms' => round($executionTime, 2),
                            'slow' => $isSlow
                        ];
                        
                        if ($isSlow) {
                            Logger::warning(sprintf(
                                'Requête SQL lente (%.2fms): %s',
                                $executionTime,
                                $this->query
                            ), $context);
                        } else {
                            Logger::debug(sprintf(
                                'Requête SQL exécutée en %.2fms',
                                $executionTime
                            ), $context);
                        }
                    }
                    
                    private function logError($exception, $startTime) {
                        $executionTime = (microtime() - $startTime) * 1000;
                        
                        Logger::error('Erreur SQL', [
                            'query' => $this->query,
                            'params' => $this->params,
                            'error' => $exception->getMessage(),
                            'code' => $exception->getCode(),
                            'time_ms' => round($executionTime, 2),
                            'trace' => $exception->getTraceAsString()
                        ]);
                    }
                    
                    // Délégation des autres appels à PDOStatement
                    public function __call($method, $args) {
                        return call_user_func_array([$this->pdoStatement, $method], $args);
                    }
                };
            } catch (PDOException $e) {
                Logger::error('Erreur de préparation de requête SQL', [
                    'query' => $statement,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ]);
                throw $e;
            }
        }
    }
    
    // Créer la connexion PDO SQLite avec journalisation
    $dsn = 'sqlite:' . DB_PATH;
    $pdo = new LoggedPDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Enveloppe de compatibilité pour le code existant
    $db = new class($pdo) {
        private $pdo;
        
        public function __construct($pdo) {
            $this->pdo = $pdo;
        }
        
        public function query($q) { 
            return $this->pdo->query($q); 
        }
        
        public function prepare($q) { 
            return $this->pdo->prepare($q); 
        }
        
        public function execute($q, $params = []) {
            $stmt = $this->prepare($q);
            return $stmt->execute($params);
        }
        
        public function lastInsertId() {
            return $this->pdo->lastInsertId();
        }
        
        // Ajout de méthodes utiles
        public function fetchOne($query, $params = []) {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch();
        }
        
        public function fetchAll($query, $params = []) {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }
        
        public function fetchColumn($query, $params = [], $column = 0) {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchColumn($column);
        }
        
        public function getPdo() {
            return $this->pdo;
        }
    };
    
    Logger::info('Connexion à la base de données établie', [
        'host' => DB_HOST,
        'database' => DB_NAME,
        'charset' => 'utf8mb4'
    ]);
    
} catch (PDOException $e) {
    $errorMsg = 'Erreur de connexion à la base de données: ' . $e->getMessage();
    Logger::critical($errorMsg, [
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'trace' => $e->getTraceAsString()
    ]);
    
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
        die('Une erreur est survenue lors de la connexion à la base de données. Veuillez réessayer plus tard.');
    } else {
        die('Erreur de base de données: ' . $e->getMessage());
    }
}

// Les fonctions utilitaires sont maintenant dans includes/utils.php
// Les fonctions d'upload sont dans includes/upload_functions.php
// Les fonctions de sécurité sont dans includes/security_functions.php
