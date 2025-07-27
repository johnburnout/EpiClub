<?php
    
    // Inclusion des fichiers de configuration
    require __DIR__ . '/config.php';
    require $root . 'includes/common.php';
    
    // #############################
    // Initialisation des variables
    // #############################
    $id = isset($_POST['controle_id']) ? (int)$_POST['controle_id'] : 0;
    $retour = "index.php";
    $abandon = filter_var($_POST['abandon'] ?? '', FILTER_SANITIZE_URL);
    $bouton = '';
    $avis = 'Contrôle cloturé';
    $reference = isset($_POST['reference']) ? htmlspecialchars($_POST['reference'], ENT_QUOTES, 'UTF-8') : '';
    $remarque = isset($_POST['remarque']) ? htmlspecialchars($_POST['remarque'], ENT_QUOTES, 'UTF-8') : '';
    
    // #############################
    // Gestion des opérations
    // #############################
    if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validation CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('Erreur de sécurité: Token CSRF invalide');
        }
        
        
        try {
            global $host, $username, $password, $dbname;
            
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $connection = new mysqli($host, $username, $password, $dbname);
            $connection->set_charset("utf8mb4");
            $shouldCloseConnection = true;
            
            // Fermeture du contrôle
            $stmt = $connection->prepare("UPDATE verification SET en_cours = 0 WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Suppression des cookies
            $_SESSION['controle_en_cours'] = 0;
            
            // 5. PREPARATION DE LA REQUETE
            $sql = "UPDATE utilisateur SET
            controle_en_cours = 0
            WHERE username = ?";
            
            $stmt = $connection->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erreur de préparation de la requête: " . $connection->error);
            }
            
            // 6. EXECUTION DE LA REQUETE
            $stmt->bind_param(
                "s",
                $utilisateur
            );
            
            $stmt->execute();
            
            $avis = "Le contrôle a été clôturé.";
            
            // Journalisation
            if (!empty($reference)) {
                $journalcontrole = $root.'enregistrements/journalcontrole'.$donnees['id'].'.txt';
                $journal = $root.'enregistrements/journal'.date('Y').'.txt';
                $ajoutjournal = date('Y/m/d').' '.$utilisateur.' - '.'Clôture du contrôle'.PHP_EOL.'Motif : '.$remarque;
                
                // Écriture dans les journaux avec vérification des chemins
                $allowedPaths = [$root.'enregistrements/'];
                $isValidPath = false;
                
                foreach ($allowedPaths as $path) {
                    if (strpos($journalmat, $path) === 0 && strpos($journal, $path) === 0) {
                        $isValidPath = true;
                        break;
                    }
                }
                
                if ($isValidPath) {
                    file_put_contents($journal, "--------".PHP_EOL.$ajoutjournal.PHP_EOL, FILE_APPEND | LOCK_EX);
                    file_put_contents($journalcontrole, "-------".PHP_EOL.$ajoutjournal.PHP_EOL, FILE_APPEND | LOCK_EX);
                }
            }
            $connection->close();
        } catch (mysqli_sql_exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] Erreur DB: " . $e->getMessage());
            $avis = "Une erreur technique est survenue lors de la clôture du contrôle";
        } catch (Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] Erreur: " . $e->getMessage());
            $avis = "Une erreur est survenue lors de la journalisation";
        }
    }
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <?php include $root.'includes/head.php';?>
    </head>
    <body>
        <header style="text-align: right; padding: 10px;">
            <?php include $root.'includes/bandeau.php';?>
        </header>
        
        <?php include $root.'includes/en_tete.php';?>
        
        <main>
            <?php if ($avis): ?>
            <div class="alert <?= strpos($avis, 'Attention') !== false ? 'alert-warning' : 'alert-success' ?>">
                <?= $avis ?>
            </div>
            <?php endif; ?>
            <p>
                <?php if ($isLoggedIn): ?>
                <form method="post" action="<?= $retour ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="controle_id" value="<?= $id ?>">
                    <input type="hidden" name="reference" value="<?= $reference ?>">
                    <input type="hidden" name="remarque" value="<?= $remarque ?>">                
                    <div class="actions">
                        <?= $bouton ?>
                        <button type="submit" name="accueil" class="btn btn-primary">
                            Revenir à l'accueil
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-warning">
                    Vous devez être connecté pour accéder à cette fonctionnalité
                </div>
                <a href="login.php" class="btn btn-primary">Se connecter</a>
                <?php endif; ?>
            </p>
        </main>
        
    </body>
    <footer>
        <?php include $root . 'includes/bandeau_bas.php'; ?>
    </footer>
</html>