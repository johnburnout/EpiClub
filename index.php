<?php
// Démarrage de session sécurisé
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_strict_mode' => true
]);

// Inclusion des fichiers de configuration avec vérification
defined('ROOT') or define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require __DIR__ . '/config.php';
require ROOT . 'includes/common.php';

// Gestion de la déconnexion
if (isset($_POST['deconnexion'])) {
    $_SESSION = array();
    session_destroy();
    header('Location: index.php');
    exit;
}

// Vérification de la connexion
$connect = (count($_SESSION) > 0 ? 
    'Connecté comme ' . htmlspecialchars($_SESSION['pseudo'], ENT_QUOTES, 'UTF-8') . ' : ' : 
    'Déconnecté';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include ROOT . 'includes/header.php'; ?>
    <title>Gestionnaire EPI - Périgord Escalade</title>
</head>
<body>
    <header style="text-align: right; padding: 10px;">
        <?php if (count($_SESSION) > 0): ?>
            <form action="index.php" method="post" style="display: inline;">
                <?php echo $connect; ?>
                <button type="submit" name="deconnexion" class="btn btn-link">Déconnexion</button>
            </form>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary">Connexion</a>
        <?php endif; ?>
    </header>
    <hr>

    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <h1>Gestionnaire EPI</h1>
                <h2>Périgord Escalade</h2>
            </div>
            <div class="col-md-4 text-right">
                <img src="images/logo.png" width="200" alt="Logo Périgord Escalade" class="img-fluid">
            </div>
        </div>
    </div>
    <hr>

    <?php if (count($_SESSION) > 0): ?>
        <main class="container">
            <h3>Gestionnaire des EPI</h3>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Gestion des fiches</h5>
                            <form action="fiche_creation.php" method="post" class="mb-3">
                                <input type="hidden" name="appel_liste" value="0">
                                <input type="hidden" name="id" value="0">  
                                <input type="hidden" name="action" value="creation">
                                <button type="submit" class="btn btn-primary btn-block">Créer une nouvelle fiche</button>
                            </form>
                            
                            <form action="liste_selection.php" method="post">
                                <button type="submit" class="btn btn-info btn-block">Consulter la liste des EPI</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Autres fonctionnalités</h5>
                            <form action="controle_creation.php" method="post" class="mb-3">
                                <input type="hidden" name="action" value="creation">
                                <button type="submit" class="btn btn-warning btn-block">Créer une vérification</button>
                            </form>
                            
                            <form action="facture_creation.php" method="post">
                                <input type="hidden" name="action" value="creation">
                                <button type="submit" class="btn btn-success btn-block">Créer une facture</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    <?php else: ?>
        <div class="container text-center">
            <div class="alert alert-warning">
                <p>Vous n'êtes pas connecté</p>
                <a href="login.php" class="btn btn-primary">Se connecter</a>
            </div>
        </div>
    <?php endif; ?>

    <?php include ROOT . 'includes/footer.php'; ?>
</body>
</html>