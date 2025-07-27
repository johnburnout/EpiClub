<?php
        
    require __DIR__ . '/config.php';
    require $root . 'includes/common.php';
    
    if ($isLoggedIn) {
        $controle = $_SESSION['controle_en_cours'] ? "liste_controle.php" : "controle_creation.php" ;
        $facture = $_SESSION['facture_en_saisie'] ? "liste_facture.php" : "facture_creation.php" ;
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
            <h3>Accueil</h3>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Consultation</h5>
                            <p>
                                <?php if ($isLoggedIn and ($_SESSION['role'] == 'admin') and false): ?>    
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
                                    <button type="submit" class="btn btn-primary btn-block">Consulter la liste des EPI</button>
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
                        <button type="submit"  class="btn btn-primary btn-block">Contrôler les EPI</button>
                    </form>
                    </p>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <p>
                        <form action="<?= $facture; ?>" method="post">
                            <input type="hidden" name="action" value="creation">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit"  class="btn btn-primary btn-block">Saisir une facture</button>
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
        
    </body>
    <footer>
        <?php include $root . 'includes/bandeau_bas.php'; ?>
    </footer>
</html>