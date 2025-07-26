<?php    
    //  error_reporting(E_ALL);
    //  ini_set('display_errors', 1);
    //  ini_set('display_startup_errors', 1);
    
    // Démarrage de session sécurisé
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => true,
        'use_strict_mode' => true,
        'cookie_samesite' => 'Strict'
    ]);
    
    // Protection contre les attaques par fixation de session
    if (empty($_SESSION['regenerate_time'])) {
        session_regenerate_id(true);
        $_SESSION['regenerate_time'] = time();
    } elseif (time() - $_SESSION['regenerate_time'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['regenerate_time'] = time();
    }
    
    //    var_dump($_SESSION);
    // Inclusion des fichiers de configuration
    require __DIR__ . '/config.php';
    require $root . 'includes/common.php';
    require $root . 'includes/init_donnees.php';
    
    //var_dump($_SESSION);
    if (!isset($_POST) or count($_POST) == 0) {
        $host  = $_SERVER['HTTP_HOST'];
        $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $extra = 'index.php';
        header("Location: http://$host$uri/$extra");
        exit;
    }
    
    // #############################
    // Vérification connexion utilisateur
    // #############################
    
    $isLoggedIn = !empty($_SESSION['pseudo']) && is_string($_SESSION['pseudo']);
    $connect = $isLoggedIn ? "Connecté comme ".htmlspecialchars($_SESSION['pseudo']) : "Déconnecté";
    $utilisateur = $isLoggedIn ? htmlspecialchars($_SESSION['pseudo'], ENT_QUOTES, 'UTF-8') : '';
    
    // Génération du token CSRF
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    //var_dump($_POST);
    // #############################
    // Initialisation des variables
    // #############################
    $action =  isset($_POST['action']) ? $_POST['action'] : 'creation';
    $validation = $action;
    
    $defaults = [
        'id' => isset($_POST['id']) ? $_POST['id'] : 0,
        'facture_id' => $_SESSION['facture_en_saisie'] ? intval($_SESSION['facture_en_saisie']) : 1,
        'reference' => date('y').strval(rand(100000,999999)),
        'libelle' => '',
        'photo' => 'null.jpeg',
        'lieu_id' => 1,
        'categorie_id' => 1,
        'date_debut' => date('Y-m-d'),
        'fabricant_id' => 1,
        'nb_elements_initial' => 1,
        'remarques' => '',
        'appel_liste' => 0,
        'retour' => isset($_POST['retour']) ? $_POST['retour'] : 'index.php'
    ];
    $retour = $defaults['retour'];
    //var_dump($defaults);
    // Initialisation des données
    
    foreach ($defaults as $key => $value) {
        $donnees[$key] = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?? $value;
        if (in_array($key, ['id', 'facture_id', 'lieu_id', 'categorie_id', 'fabricant_id', 'nb_elements_initial', 'appel_liste'])) {
            $donnees[$key] = (int)$donnees[$key];
        }
    }
    
    
    //var_dump($donnees);
    // #############################
    // Gestion des opérations CRUD
    // #############################
    if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Validation CSRF
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new Exception('Erreur de sécurité: Token CSRF invalide');
            }
            
            if ($action === 'creation') {
                $creation = creation_fiche($donnees);
                if (!$creation['success']) {
                    throw new Exception('Erreur lors de la création: ' . ($creation['error'] ?? ''));
                }
                $donnees['id'] = $creation['id'];
            }
            
            //var_dump($donnees);
            // Lecture des données après création/mise à jour
            if ($donnees['id'] > 0) {
                $result = lecture_fiche($donnees['id']);
                if (!$result['success']) {
                    throw new Exception('Erreur lors de la lecture: ' . ($result['error'] ?? ''));
                }
                $donnees = array_merge($donnees, $result['donnees']);
                $donnesInitiales = $donnees;
                if ($action === 'maj') {
                    foreach ($donnees as $key => $value) {
                        $donnees[$key] = isset($_POST[$key]) ? $_POST[$key] : $donnees[$key];
                    }
                    $valid = mise_a_jour_fiche($donnees);
                    if (!$valid['success']) {
                        throw new Exception('Erreur lors de la mise à jour: ' . ($valid['error'] ?? ''));
                    }
                }
            }
            // Gestion de l'upload de fichiers
            if (!empty($_FILES['monfichier']['name']) && $_POST['id'] > 0) {
                $allowedExtensions = ['jpeg', 'jpg', 'gif', 'png'];
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxFileSize = 2 * 1024 * 1024; // 2MB
                
                $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $fileInfo->file($_FILES['monfichier']['tmp_name']);
                
                $fileExtension = strtolower(pathinfo($_FILES['monfichier']['name'], PATHINFO_EXTENSION));
                
                if (!in_array($mimeType, $allowedMimeTypes) || 
                    !in_array($fileExtension, $allowedExtensions)) {
                        throw new Exception('Type de fichier non autorisé');
                    }
                
                if ($_FILES['monfichier']['size'] > $maxFileSize) {
                    throw new Exception('Fichier trop volumineux (max 2MB)');
                }
                
                if (!is_uploaded_file($_FILES['monfichier']['tmp_name'])) {
                    throw new Exception('Erreur de téléchargement');
                }
                
                $uploadDir = dirname(__FILE__).'/images/';
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        throw new Exception('Impossible de créer le dossier de destination');
                    }
                }
                
                $newFilename = ($donnees['reference'] ?? 'file') . '.' . $fileExtension;
                $destination = $uploadDir . $newFilename;
                
                if (!move_uploaded_file($_FILES['monfichier']['tmp_name'], $destination)) {
                    throw new Exception('Erreur lors du déplacement du fichier');
                }
                
                // Mise à jour de la photo dans la base
                $connection = new mysqli($host, $username, $password, $dbname);
                $connection->set_charset("utf8mb4");
                
                $sql = "UPDATE matos SET photo = ? WHERE id = ?";
                $stmt = $connection->prepare($sql);
                $stmt->bind_param('si', $newFilename, $_POST['id']);
                $stmt->execute();
                $donnees['photo'] = $newFilename;
                $connection->close();
            }
        } catch (Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] Erreur: " . $e->getMessage());
            $errorMessage = $e->getMessage();
            echo $errorMessage;
        }
        // Journalisation
        if (isset($valid['success']) or isset($creation['success'])) {
            $journalcontrole = $root.'enregistrements/journal'.$donnees['reference'].'.txt';
            $journal = $root.'enregistrements/journal'.date('Y').'.txt';
            $reference = $donnees['reference'];
            
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
                
                $ajoutjournal = '-----'.PHP_EOL."$reference ".date('Y/m/d')." $utilisateur".PHP_EOL;
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
    
    // #############################
    // Récupération des listes d'options
    // #############################
    $current_facture_id = $donnees['facture_id'] ?? 0;
    $current_lieu_id = $donnees['lieu_id'] ?? 0;
    $current_categorie_id = $donnees['categorie_id'] ?? 0;
    $current_fabricant_id = $donnees['fabricant_id'] ?? 0;
    
    $listeFactures = liste_options(['libelles' => 'facture', 'id' => $current_facture_id]);
    $listeLieux = liste_options(['libelles' => 'lieu', 'id' => $current_lieu_id]);
    $listeCategories = liste_options(['libelles' => 'categorie', 'id' => $current_categorie_id]);
    $listeFabricants = liste_options(['libelles' => 'fabricant', 'id' => $current_fabricant_id]);
    
    // #############################
    // Préparation des données pour l'affichage
    // #############################
    //var_dump($donnees);
    $viewData = [
        'lieu_id' => sprintf('<select name="lieu_id" required>%s</select>', $listeLieux[0] ?? ''),
        'categorie_id' => sprintf('<select name="categorie_id" required>%s</select>', $listeCategories[0] ?? ''),
        'categorie' => htmlspecialchars($donnees['categorie'] ?? '', ENT_QUOTES, 'UTF-8'),
        'fabricant_id' => sprintf('<select name="fabricant_id" required>%s</select>', $listeFabricants[0] ?? ''),
        'fabricant' => htmlspecialchars($donnees['fabricant'] ?? '', ENT_QUOTES, 'UTF-8'),
        'facture_id' => sprintf('<select name="facture_id">%s</select>', $listeFactures[0] ?? ''),
        'facture' => htmlspecialchars($donnees['facture'] ?? '', ENT_QUOTES, 'UTF-8'),
        'libelle' => sprintf('<input name="libelle" type="text" required value="%s">', 
            htmlspecialchars($donnees['libelle'] ?? '', ENT_QUOTES, 'UTF-8')),
        'date_debut' => sprintf('<input name="date_debut" type="date" required value="%s">', 
            date('Y-m-d')),
        'reference' => htmlspecialchars($donnees['reference'] ?? '', ENT_QUOTES, 'UTF-8'),
        'photo' => htmlspecialchars($donnees['photo'] ?? 'null.jpeg', ENT_QUOTES, 'UTF-8'),
        'remarques' => htmlspecialchars($donnees['remarques'] ?? '', ENT_QUOTES, 'UTF-8'),
        'nb_elements_initial' => (int)($donnees['nb_elements_initial'] ?? 1),
        'action' => "maj",
        'id' => (int)$donnees['id'],
        'retour' => $donnees['retour'],
        'isEditMode' => $validation === 'maj',
        'isNewMode' => $validation === 'creation',
        'hasPhoto' => ($donnees['photo'] ?? '') !== 'null.jpeg'
    ];
    //htmlspecialchars(var_dump($viewData['date_debut']), ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $viewData['isEditMode'] ? 'Modification' : 'Création' ?> Fiche EPI - Gestionnaire EPI</title>
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
        <hr>
        <?php if (isset($errorMessage)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        
        <?php if ($isLoggedIn): ?>
        <form enctype="multipart/form-data" method="post" action="fiche_creation.php">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="maj">
            <input type="hidden" name="id" value="<?= $viewData['id'] ?>">
            <input type="hidden" name="categorie" value="<?= $viewData['categorie'] ?>">
            <input type="hidden" name="fabricant" value="<?= $viewData['fabricant'] ?>">
            <input type="hidden" name="facture" value="<?= $viewData['facture'] ?>">
            <input type="hidden" name="retour" value="<?= $viewData['retour'] ?>">
            <input type="hidden" name="appel_liste" value="0">
            <input type="hidden" name="MAX_FILE_SIZE" value="2000000">
            
            <table>
                <tbody>
                    <tr>
                        <th colspan="2">Informations de base</th>
                    </tr>
                    <tr>
                        <td width="30%">
                            <label for="reference">Référence :</label>
                            <input type="text" name="reference" required value="<?= $viewData['reference'] ?>">
                        </td>
                        <td rowspan="<?= $viewData['hasPhoto'] ? '8' : '7' ?>">
                            <?php if ($viewData['hasPhoto']): ?>
                            <img src="images/<?= $viewData['photo'] ?>" class="epi-photo" alt="Photo du matériel" width="400">
                            <?php endif; ?>
                            <br>
                            <input type="file" name="monfichier" accept="image/jpeg,image/png,image/gif">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="libelle">Libellé :</label>
                            <?= $viewData['libelle'] ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="lieu_id">Lieu :</label>
                            <?= $viewData['lieu_id'] ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="categorie_id">Catégorie :</label>
                            <?= $viewData['categorie_id'] ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="date_debut">Mise en service :</label>
                            <?= $viewData['date_debut'] ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="fabricant_id">Fabricant :</label>
                            <?= $viewData['fabricant_id'] ?>
                        </td>
                    </tr>
                    <?php if ($viewData['hasPhoto']): ?>
                    <!--                <tr>
                    <td>
                    <label for="photo">Nom du fichier photo:</label>
                    <input type="text" name="photo" value="<?= $viewData['photo'] ?>" readonly>
                    </td>
                    </tr> -->
                    <?php endif; ?>
                    <tr>
                        <td colspan="1">
                            <label for="nb_elements_initial">Nombre d'éléments :</label>
                            <input type="number" name="nb_elements_initial" 
                                value="<?= $viewData['nb_elements_initial'] ?>" min="1" required>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="1">
                            <label for="facture_id">Facture :</label>
                            <?= $viewData['facture_id'] ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="1">
                            <label for="remarques">Remarques :</label>
                        </td>
                        <td>
                            <textarea name="remarques" placeholder="Saisissez vos remarques..."  rows="4" cols="40"><?= $viewData['remarques'] ?></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div class="form-actions">
                <?php if ($viewData['isEditMode']): ?>
                <a href=<?= $viewData['retour']; ?>>
                    <input type="button" value="Retour " class="btn btn-secondary">
                </a>
                <a href="fiche_effacer.php?id=<?= $viewData['id'] ?>&retour=<?= $retour ?>"
                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette fiche ?')">
                    <input type="button"  class="btn btn-danger" value="Supprimer la fiche" name="supprimer">
                </a>
                <?php else: ?>
                <a href="index.php">
                    <input type="button" value="Annuler" class="btn btn-secondary">
                </a>
                <?php endif; ?>
                
                <button type="submit" name="envoyer" class="btn btn-primary">
                    <?= $viewData['isEditMode'] ? 'Mettre à jour' : 'Créer' ?> la fiche
                </button>
            </div>
        </form>
        <?php else: ?>
        <div class="alert alert-error">
            Vous devez être connecté pour accéder à cette fonctionnalité.
            <a href="login.php" class="btn btn-primary">Se connecter</a>
        </div>
        <?php endif; ?>
        
        <?php require $root."includes/footer.php"; ?>
    </body>
</html>