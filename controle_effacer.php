<?php
    
    //error_reporting(E_ALL);
    //ini_set('display_errors', 1);
    //ini_set('display_startup_errors', 1);
    
    // Démarrage de session sécurisé
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => true,
        'use_strict_mode' => true
    ]);
    
    // Génération du token CSRF
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    // Inclusion des fichiers de configuration
    require __DIR__ . '/config.php';
    require $root . 'includes/common.php';
    
    //var_dump($_GET);
    // #############################
    // Initialisation variables
    // #############################
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    //$bouton = '';
    $avis = '';
    $retour = 'index.php';
    // #############################
    // Vérification connexion et permissions
    // #############################
    $isLoggedIn = !empty($_SESSION['pseudo']) && is_string($_SESSION['pseudo']);
    $connect = $isLoggedIn ? "Connecté comme ".htmlspecialchars($_SESSION['pseudo']) : "Déconnecté";
    $utilisateur = $isLoggedIn ? htmlspecialchars($_SESSION['pseudo'], ENT_QUOTES, 'UTF-8') : '';
    
    // Vérification des permissions
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' or !$isLoggedIn or ($id != $_SESSION['controle_en_cours'])) {
        die('Erreur : Permissions insuffisantes');
    }
    
    // Validation CSRF pour les requêtes POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('Erreur de sécurité : Token CSRF invalide');
        }
    }
    
    // #############################
    // Opérations base de données
    // #############################
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    try {
        $connection = new mysqli($host, $username, $password, $dbname);
        $connection->set_charset("utf8mb4");
        
        // Si ID non fourni, on récupère le max
        if ($id === 0) {
            $stmt = $connection->prepare("SELECT MAX(id) AS max_id FROM verification");
            $stmt->execute();
            $result = $stmt->get_result();
            $idmax = $result->fetch_assoc();
            $id = (int)$idmax['max_id'];
        }
        
        // Suppression
        $stmt1 = $connection->prepare("DELETE FROM verification WHERE id = ?");
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        
        if ($connection->affected_rows > 0) {
            $avis = "Le contrôle a été supprimé avec succès";
            
            $sql = "UPDATE utilisateur SET
            controle_en_cours = 0
            WHERE username = ?";
            
            $stmt2 = $connection->prepare($sql);
            if (!$stmt2) {
                throw new Exception("Erreur de préparation de la requête: " . $connection->error);
            }
            
            // 6. EXECUTION DE LA REQUETE
            $stmt2->bind_param(
                "s",
                $utilisateur
            );
            
            $stmt2->execute();
            
            // Réinitialisation auto_increment si table vide
            if ($id === 0) {
                $connection->query("ALTER TABLE verification AUTO_INCREMENT = 1");
            }
        } else {
            $avis = "Aucun enregistrement trouvé à supprimer";
        }
        
        
        $connection->close();
    } catch (mysqli_sql_exception $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] Erreur DB: " . $e->getMessage() . " dans " . __FILE__);
        $avis = "Une erreur technique est survenue lors de la suppression";
    }
    
    // Configuration des messages et boutons
    //if ($id > 0 && isset($_POST['supprimer'])) {
    //  $bouton = "<a href='liste_selection.php' class='btn btn-secondary'>Abandonner</a>";
    //  $avis = $avis ?: "Attention ! Voulez-vous vraiment effacer le contrôle #$id ?";
    //}
    //
    //if (isset($_POST['confirmer'])) {
    //  $avis = "Opération confirmée. " . $avis;
    //}
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Suppression contrôle - Gestionnaire EPI</title>
        <?php include $root.'includes/header.php'; ?>
    </head>
    <body>
        <header style="text-align: right; padding: 10px;">
            <?php if ($isLoggedIn): ?>
            <form action="index.php" method="post" style="display: inline;">
                <?php echo $connect; ?>
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" name="deconnexion" class="btn btn-secondary">Déconnexion</button>
            </form>
            <?php else: ?>
            <a href="login.php" class="btn btn-primary">Connexion</a>
            <?php endif; ?>
        </header>
        <hr>
        
        <table>
            <tr>
                <td><h1>Gestionnaire EPI</h1></td>
                <td rowspan="2"><img src="images/logo.png" width="200" alt="Logo"></td>
            </tr>
            <tr>
                <td><h2>Périgord Escalade</h2></td>
            </tr>
        </table>
        <hr>
        
        <?php if ($isLoggedIn): ?>
        <?php if ($avis): ?>
        <div class="alert <?= strpos($avis, 'Attention') !== false ? 'alert-warning' : 'alert-info' ?>">
            <?= htmlspecialchars($avis) ?>
        </div>
        <?php endif; ?>
        
        <!--       <form method="post" action="controle_effacer.php">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        
        <div style="margin-top: 20px;">
        <?= $bouton ?>
        <?php if (isset($_POST['supprimer']) && !isset($_POST['confirmer'])): ?>
        <button type="submit" name="confirmer" class="btn btn-primary">Confirmer la suppression</button>
        <?php endif; ?>
        </div>
        </form> -->
        <?php else: ?>
        <div class="alert alert-warning">
            Vous devez être connecté pour accéder à cette fonctionnalité.
        </div>
        <a href="login.php" class="btn btn-primary">Se connecter</a>
        <?php endif; ?>
        <div>
            <p>
                <form action="<?= $retour ?>" >
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="submit" class="btn btn-primary" name="retour" value="Retour">
                </form>
            </p>
        </div>
        <?php require $root."includes/footer.php"; ?>
    </body>
</html>