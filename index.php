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
    };
    
    //var_dump($_COOKIE);
    //var_dump($_SESSION);
    
    // Inclusion des fichiers de configuration avec vérification
    
    require 'config.php';
    //require $root .'styles/style.css';
    require $root . 'includes/fonctions_edition.php';
    
    // Gestion de la déconnexion
    if (isset($_POST['deconnexion'])) {
        $_SESSION = array();
        session_destroy();
        header('Location: index.php');
        exit;
    }
    
    // #############################
    // Verification connexion
    // #############################
    $isLoggedIn = !empty($_SESSION['pseudo']);
    $connect = $isLoggedIn ? "Connecté comme ".htmlspecialchars($_SESSION['pseudo']) : "Déconnecté";
    
    // #############################
    // Initialisation variables
    // #############################
    
    if ($isLoggedIn) {
        $controle = $_SESSION['controle_en_cours'] ? "liste_controle.php" : "controle_creation.php" ;
        $facture = $_SESSION['facture_en_saisie'] ? "liste_facture.php" : "facture_creation.php" ;
    }
    
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Connexion - Gestionnaire EPI</title>
        <?php include $root.'includes/header.php';?>
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
        <?php if ( !$isLoggedIn): ?>
        <h3>Version de test</h3>
        <p>
            Pour vous connecter comme controleur EPI :<br>
            login : usager mdp : usager
        </p>
        <p>
            Pour vous connecter comme admin EPI :<br>
            login : admin mdp : admin
        </p> 
        <p>
            Reste à implémenter la lecture des journaux et la génération de qrcodes pour le contrôle.
        </p>
        <hr>
        <?php endif; ?>
        <?php if (count($_SESSION) > 0): ?>
        <main class="container">
            <h3>Gestionnaire des EPI</h3>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Consultation</h5>
                            <p>
                                <?php if ($isLoggedIn and ($_SESSION['role'] == 'admin')): ?>    
                                <form action="fiche_creation.php" method="post" class="mb-3">
                                    <input type="hidden" name="appel_liste" value="0">
                                    <input type="hidden" name="id" value="0">  
                                    <input type="hidden" name="action" value="creation">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="retour" value="index.php">
                                    <button type="submit" class="btn btn-primary btn-block">Créer une nouvelle fiche</button>
                                </form>
                                <?php endif; ?>
                            </p>
                            <p>
                                <form action="liste_selection.php" method="post">
                                    <button type="submit" class="btn btn-info btn-block">Consulter la liste des EPI</button>
                                </form>
                            </p>
                        </div>
                    </div>
                </div>
                <?php if ($isLoggedIn): ?>                
                <div class="col-md-6">
                    <h5 class="card-title">Autres fonctionnalités</h5>
                    <p><form action="<?= $controle; ?>" method="post" class="mb-3">
                        <input type="hidden" name="action" value="creation">
                        <input type="hidden" name="id" value="<?= $_SESSION['controle_en_cours']; ?>"> 
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" class="btn btn-warning btn-block">Contrôler les EPI</button>
                    </form>
                    </p>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <p>
                        <form action="<?= $facture; ?>" method="post">
                            <input type="hidden" name="action" value="creation">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit" class="btn btn-success btn-block">Saisir une facture</button>
                        </form>
                    </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
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
        
        <?php include $root . 'includes/footer.php'; ?>
    </body>
</html>