<?php
    
    // Inclusion des fichiers de configuration
    require __DIR__ . '/config.php';
    require $root . 'includes/common.php';
    
    //var_dump($_POST);
    if (!isset($_POST) or count($_POST) == 0) {
        $host  = $_SERVER['HTTP_HOST'];
        $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $extra = 'index.php';
        header("Location: http://$host$uri/$extra");
        exit;
    }
    
    // #############################
    // Initialisation des variables
    // #############################
    $action =  isset($_POST['action']) ? $_POST['action'] : 'creation';
    $validation = $action;    
    
    $defaults = [
        'action' => 'creation',
        'controle_id' => 0,
        'utilisateur' => $utilisateur,
        'remarques' => '',
        'date_verification' => date('Y-m-d'),
        'error' => '',
        'success' => ''
    ];
    
    // Initialisation des données
    
    foreach ($defaults as $key => $value) {
        $donnees[$key] = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?? $value;
        if (in_array($key, ['controle_id'])) {
            $donnees[$key] = (int)$donnees[$key];
        }
    }
    
    //var_dump($_POST);
    // Récupération de l'ID
    $donnees['controle_id'] = isset($_SESSION['controle_en_cours']) ? intval($_SESSION['controle_en_cours']) : 0 ;
    $isStarted = ($donnees['controle_id'] != 0);
    
    //var_dump($isStarted);
    //var_dump($isLoggedIn);
    
    // #############################
    // Gestion des opérations CRUD
    // #############################
    if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validation CSRF
        try{
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new Exception('Erreur de sécurité: Token CSRF invalide');
            }
            
            if ($action === 'creation') {
                $creation = creation_controle([
                    'utilisateur' => $utilisateur,
                    'date_verification' => $donnees['date_verification']
                ]);
                if (!$creation['success']) {
                    throw new Exception('Erreur lors de la création: ' . ($creation['error'] ?? ''));
                }
                $donnees['controle_id'] = $creation['controle_id'];
                $_SESSION['controle_en_cours'] = $donnees['controle_id'];
                $isStarted = true;
                $donnees['success'] = "Nouveau contrôle créé avec succès.";
            }
            //        var_dump($donnees);
            
            // Lecture des données après création/mise à jour
            if ($donnees['controle_id'] > 0) {
                $result = lecture_controle($donnees['controle_id'], $utilisateur);
                if (!$result['success']) {
                    throw new Exception('Erreur lors de la lecture: ' . ($result['error'] ?? ''));
                }
                $donnees = array_merge($donnees, $result['donnees']);
                $donnesInitiales = $donnees;
                //var_dump($donnees);
                if ($action === 'maj') {
                    foreach ($donnees as $key => $value) {
                        $donnees[$key] = isset($_POST[$key]) ? $_POST[$key] : $donnees[$key];
                    }
                    // Traitement des champs du formulaire
                    $donnees['remarques'] = isset($_POST['remarques']) 
                    ? htmlspecialchars(trim($_POST['remarques']), ENT_QUOTES, 'UTF-8') 
                    : '';
                    $maj = mise_a_jour_controle([
                        'remarques' => $donnees['remarques'],
                        'utilisateur' => $utilisateur
                    ], $donnees['controle_id']);
                    if (!$maj['success']) {
                        throw new Exception('Erreur lors de la mise à jour: ' . ($valid['error'] ?? ''));
                    }
                }
            }
            $action = 'maj';
        } catch (Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] Erreur: " . $e->getMessage());
            $errorMessage = $e->getMessage();
            echo $errorMessage;
        }
        
        // Journalisation
        if (isset($maj['success']) or isset($creation['success'])) {
            $journalcontrole = $root.'enregistrements/journalcontrole'.$donnees['controle_id'].'.txt';
            $journal = $root.'enregistrements/journal'.date('Y').'.txt';
            $controle_id = $donnees['controle_id'];
            
            // Vérification des chemins avant écriture
            $allowedPath = $root.'enregistrements/';
            //      var_dump($donnesInitiales);
            //      var_dump($_POST);
            if (strpos($journalcontrole, $allowedPath) === 0 && strpos($journal, $allowedPath) === 0) {
                $modifications = [];
                
                foreach ($donnesInitiales as $key => $value) {
                    if (isset($donnees[$key]) && $donnees[$key] != $value) {
                        $modifications[] = "$key modifié: ".$donnees[$key]." -> $value";
                    }
                }
                
                $ajoutjournal = '-----'.PHP_EOL."controle $controle_id ".date('Y/m/d')." $utilisateur".PHP_EOL;
                if (!empty($modifications)) {
                    $ajoutjournal .= implode(PHP_EOL, $modifications).PHP_EOL;
                }
                
                try {
                    file_put_contents($journalcontrole, $ajoutjournal, FILE_APPEND | LOCK_EX);
                    file_put_contents($journal, $ajoutjournal, FILE_APPEND | LOCK_EX);
                } catch (Exception $e) {
                    error_log("Erreur journalisation: ".$e->getMessage());
                }
            }
        }
    }

    $viewData = [
        'date_verification' => $donnees['date_verification'],
        'remarques' => htmlspecialchars($donnees['remarques'] ?? '', ENT_QUOTES, 'UTF-8'),
        'action' => htmlspecialchars($action, ENT_QUOTES, 'UTF-8'),
        'controle_id' => (int)$donnees['controle_id'],
        'isEditMode' => $validation === 'maj',
        'isNewMode' => $validation === 'creation'
    ];
        
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
            <?php if ($donnees['error']): ?>
            <div class="alert alert-error"><?= $donnees['error'] ?></div>
            <?php endif; ?>
            <?php if ($donnees['success']): ?>
            <div class="alert alert-success"><?= $donnees['success'] ?></div>
            <?php endif; ?>
            
            <?php if ($isLoggedIn): ?>
            
            <form method="post" id="form-controle">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="<?= $isStarted ? 'maj' : 'creation' ?>">
                <input type="hidden" name="controle_id" value="<?= $donnees['controle_id'] ?>">
                
                <table>
                    <tbody>
                        <tr>
                            <th width="20%">Utilisateur</th>
                            <td width="30%"><?= $utilisateur ?></td>
                            <th width="20%">Date</th>
                            <td width="30%"><?= $viewData['date_verification'] ?></td>
                        </tr>
                        <tr>
                            <th colspan="4">Remarques & Observations</th>
                        </tr>
                        <tr>
                            <td colspan="4">
                                <textarea name="remarques" placeholder="Saisissez vos observations..." rows="4" cols="40"><?= $donnees['remarques'] ?></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="form-actions">
                    
                    <?php if ($isStarted): ?>
                    <a href="controle_effacer.php?id=<?= $donnees['controle_id'] ?>"
                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer supprimer définitivement ce contrôle ?')">
                        <input type="button"  class="btn btn-danger" value="Annuler le contrôle" name="supprimer">
                    </a>
                    <?php else: ?>
                    <a href="index.php" class="btn btn-secondary">Retour</a>
                    <?php endif; ?>
                    
                    <button type="submit" name="envoyer" class="btn btn-primary">
                        <?= $isStarted ? 'Enregistrer les modifications' : 'Créer le contrôle' ?>
                    </button>`
                    <?php if ($isStarted): ?>
                    <a href="liste_controle.php"
                        onclick="return confirm('Êtes-vous prêt à commencer contrôle ?')">
                        <input type="button"  class="btn btn-primary" value="Commencer le contrôle" name="controler">
                    </a>
                    <?php endif; ?>
                </div>
            </form>
            <?php else: ?>
            <div class="alert alert-error">
                Vous devez être connecté pour accéder à cette fonctionnalité.
            </div>
            <div class="actions">
                <a href="login.php" class="btn btn-primary">Se connecter</a>
                <a href="index.php" class="btn btn-secondary">Retour à l'accueil</a>
            </div>
            <?php endif; ?>
        </main>
        <script>
            // Validation client du formulaire
            document.getElementById('form-controle').addEventListener('submit', function(e) {
                const remarques = this.elements['remarques'].value.trim();
                
                if (remarques.length > 1000) {
                    alert('Les remarques ne doivent pas dépasser 1000 caractères.');
                    e.preventDefault();
                }
            });
        </script>
    </body>
    <footer>
        <?php include $root . 'includes/bandeau_bas.php'; ?>
    </footer>
</html>