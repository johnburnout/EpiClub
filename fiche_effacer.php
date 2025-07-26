<?php
    echo "semble ok";  
    
    //error_reporting(E_ALL);
    //ini_set('display_errors', 1);
    //ini_set('display_startup_errors', 1);
    
    // Démarrage de session sécurisé
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => true,
        'use_strict_mode' => true,
        'cookie_samesite' => 'Strict'
    ]);
    //var_dump($_SESSION);
    //var_dump($_POST);
    // Protection contre les attaques par fixation de session
    if (empty($_SESSION['regenerate_time'])) {
        session_regenerate_id(true);
        $_SESSION['regenerate_time'] = time();
    } elseif (time() - $_SESSION['regenerate_time'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['regenerate_time'] = time();
    }
    
    // Inclusion des fichiers de configuration
    require __DIR__ . '/config.php';
    require $root . 'includes/common.php';
    
    // #############################
    // Vérification connexion et permissions
    // #############################
    
    $isLoggedIn = !empty($_SESSION['pseudo']) && is_string($_SESSION['pseudo']);
    $connect = $isLoggedIn ? "Connecté comme ".htmlspecialchars($_SESSION['pseudo']) : "Déconnecté";
    $utilisateur = $isLoggedIn ? htmlspecialchars($_SESSION['pseudo'], ENT_QUOTES, 'UTF-8') : '';
    
    // Vérification des permissions
    if ($_SESSION['role'] !== 'admin' or !$isLoggedIn) {
        die('Erreur : Permissions insuffisantes');
    }
    
    // Génération du token CSRF
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    // #############################
    // Initialisation des variables
    // #############################
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    //var_dump($id);
    $avis = '';
    
    //var_dump($retour);
    // #############################
    // Opérations base de données
    // #############################
    if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'GET') {
        
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        
        try {
            $connection = new mysqli($host, $username, $password, $dbname);
            $connection->set_charset("utf8mb4");
            
            // Si ID non fourni, on récupère le max
            if ($id === 0) {
                $stmt = $connection->prepare("SELECT MAX(id) AS max_id FROM matos");
                $stmt->execute();
                $result = $stmt->get_result();
                $idmax = $result->fetch_assoc();
                $id = (int)$idmax['max_id'];
            }
            
            // Suppression
            $stmt = $connection->prepare("SELECT reference FROM matos WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $reference = $result->fetch_assoc()['reference'];
            //var_dump($reference);
            $stmt = $connection->prepare("DELETE FROM matos WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            if ($connection->affected_rows > 0) {
                $avis = "La fiche a été supprimée avec succès";
                
                // Réinitialisation auto_increment si table vide
                if ($_GET['id'] === 0) {
                    $connection->query("ALTER TABLE matos AUTO_INCREMENT = 1");
                }
                
                // Journalisation
                $journalmat = $root.'enregistrements/journalmat'.$reference.'.txt';
                $journal = $root.'enregistrements/journal'.date('Y').'.txt';
                $ajoutjournal = date('Y/m/d').' '.$utilisateur.' - '.'Suppression de la fiche';
                
                // Vérification des chemins avant écriture
                $allowedPath = $root.'enregistrements/';
                if (strpos($journalmat, $allowedPath) === 0 && strpos($journal, $allowedPath) === 0) {
                    try {
                        file_put_contents($journalmat, $ajoutjournal.PHP_EOL, FILE_APPEND | LOCK_EX);
                        file_put_contents($journal, $reference.PHP_EOL.$ajoutjournal.PHP_EOL, FILE_APPEND | LOCK_EX);
                    } catch (Exception $e) {
                        error_log("Erreur journalisation: ".$e->getMessage());
                    }
                }
            } else {
                $avis = "Aucune fiche trouvée à supprimer";
            }
            
            
            $connection->close();
        } catch (mysqli_sql_exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] Erreur DB: " . $e->getMessage());
            $avis = "Une erreur technique est survenue lors de la suppression";
        }
    }
    
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Suppression fiche - Gestionnaire EPI</title>
        <?php include $root.'includes/header.php'; ?>
    </head>
    <body>
        <header style="text-align: right; padding: 10px;">
            <?php if ($isLoggedIn): ?>
            <form action="index.php" method="post" style="display: inline;">
                <?php echo $connect; ?>
                <button type="submit" name="deconnexion" class="btn btn-link">Déconnexion</button>
            </form>
            <?php else: ?>
            <a href="login.php" class="btn btn-primary">Connexion</a>
            <?php endif; ?>
        </header>
        <hr>
        <div class="header-container">
            <div class="logo-title">
                <img src="images/logo.png" width="200" alt="Logo Périgord Escalade" class="img-fluid">
                <div>
                    <h1>Gestionnaire EPI</h1>
                    <h2>Périgord Escalade</h2>
                </div>
            </div>
        </div>
        
        <?php if ($avis): ?>
        <div class="alert <?= strpos($avis, 'Attention') !== false ? 'alert-warning' : 'alert-success' ?>">
            <?= htmlspecialchars($avis, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>
        <div>
            <p>
                <form method="get" action=<?= $_GET['retour'] ?> >
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="submit" class="btn btn-primary" name="retour" value="Retour">
                </form>
            </p>
        </div>
        
        <?php require $root."includes/footer.php"; ?>
    </body>
</html>