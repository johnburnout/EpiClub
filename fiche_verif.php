<?php
    
    // Inclusion des fichiers de configuration
    require __DIR__ . '/config.php';
    require $root . 'includes/common.php';
    
    dev($_GET); dev($_COOKIE);
    // #############################
    // Initialisation des variables
    // #############################
    //var_dump($_POST);
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $action = $_GET['action'];
    $bouton = ($action == 'validation') ? 'Retour' : 'Abandonner';
    $retour = isset($_GET['retour']) ? $_GET['retour'] : '';
    // #############################
    // Gestion des opérations CRUD
    // #############################
    if ($isLoggedIn) {
        try {
            if ($id > 0) {
                $lecture = lecture_fiche($id);
                $donnees = $lecture['donnees'];
                $donneesInitiales = $donnees;
                
                //var_dump($donnees);
                $donnees['id'] = $id;
                //var_dump($donnees); echo PHP_EOL;
                if (!$lecture['success']) {
                    throw new Exception('Erreur lors de la lecture: ' . ($result['error'] ?? ''));
                }
                // Fusion des remarques
                $remarques_temp = [];
                if (!empty($donnees['remarques'])) {
                    $remarques_temp[] = $donnees['remarques'];
                } 
                if (!empty($_POST['remarque'])) {
                    $remarques_temp[] = $_POST['remarque'];
                }
                $remarques = implode(nl2br("\n"), $remarques_temp);
                $donnees['remarques'] = $remarques;
            }
            // Mise à jour des données avec les valeurs POST
            foreach ($_POST as $key => $value) {
                if (array_key_exists($key, $donnees)) {
                    $donnees[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }
            }
            //var_dump($_SERVER['REQUEST_METHOD']);
            //var_dump($action);
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'validation') {
                // Validation CSRF
                if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                    throw new Exception('Erreur de sécurité: Token CSRF invalide');
                }
                //echo "journalisation";
                $valid = mise_a_jour_fiche($donnees);
                if (!$valid['success']) {
                    throw new Exception('Erreur lors de la mise à jour: ' . ($valid['error'] ?? ''));
                }
                //var_dump($donnees);
                // Journalisation des modifications
                $journalmat = $root.'enregistrements/journalmat'.$donnees['reference'].'.txt';
                $journal = $root.'enregistrements/journal'.date('Y').'.txt';
                $ajoutjournal = date('Y/m/d').' '.$utilisateur.PHP_EOL;
                
                $modifications = [];
                foreach ($donnees as $key => $value) {
                    if (isset($donneesInitiales[$key]) && $donneesInitiales[$key] != $value) {
                        //var_dump($key); var_dump($value);
                        $modifications[] = "$key modifié: ".$donneesInitiales[$key]." -> $value";
                    }
                }
                
                if (!empty($modifications)) {
                    // Construction du message de journalisation
                    $ajoutjournal .= implode(PHP_EOL, $modifications) . PHP_EOL;
                    
                    // Ajout des remarques si nécessaire (version commentée)
                    // if (!empty($remarques)) {
                    //     $ajoutjournal .= "Remarques: " . $remarques . PHP_EOL;
                    // }
                    
                    // Ajout d'un timestamp pour le traçage
                    $timestamp = '[' . date('Y-m-d H:i:s') . '] ';
                    $ajoutjournal = $timestamp . $ajoutjournal;
                    
                    try {
                        // Journal matériel
                        $handle = fopen($journalmat, 'a');
                        if ($handle === false) {
                            throw new RuntimeException("Impossible d'ouvrir le journal matériel: " . $journalmat);
                        }
                        
                        if (fwrite($handle, "-------" . PHP_EOL . $ajoutjournal) === false) {
                            throw new RuntimeException("Échec d'écriture dans le journal matériel");
                        }
                        fclose($handle);
                        
                        // Journal principal avec référence
                        $reference = $donnees['reference'] ?? 'REF_INCONNUE';
                        $handle = fopen($journal, 'a');
                        if ($handle === false) {
                            throw new RuntimeException("Impossible d'ouvrir le journal principal: " . $journal);
                        }
                        
                        if (fwrite($handle, "--EPI " . $reference . "--" . PHP_EOL . $ajoutjournal) === false) {
                            throw new RuntimeException("Échec d'écriture dans le journal principal");
                        }
                        fclose($handle);
                        
                    } catch (Exception $e) {
                        // Journalisation de l'erreur et affichage convivial
                        error_log("ERREUR Journalisation: " . $e->getMessage() . " \nStack Trace: " . $e->getTraceAsString());
                        
                        // Message plus propre pour l'utilisateur
                        echo "<div class='alert alert-error'>Une erreur est survenue lors de l'enregistrement des journaux. L'équipe technique a été notifiée.</div>";
                        
                        // Fermeture du handle si jamais il a été ouvert
                        if (isset($handle)) {
                            fclose($handle);
                        }
                    }
                }
            }
            
            // Gestion de l'upload de fichiers
            if (!empty($_FILES['monfichier']['name']) && $id > 0) {
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
                
                $uploadDir = $dossier_images;
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        throw new Exception('Impossible de créer le dossier de destination');
                    }
                }
                
                $newFilename = ($donnees['reference'] ?? 'file') . date('YmdHis') . '.' . $fileExtension;
                $destination = $uploadDir . $newFilename;
                
                if (!move_uploaded_file($_FILES['monfichier']['tmp_name'], $destination)) {
                    throw new Exception('Erreur lors du déplacement du fichier');
                }
                
                // Mise à jour de la photo dans la base
                $connection = new mysqli($host, $username, $password, $dbname);
                $connection->set_charset("utf8mb4");
                
                $sql = "UPDATE matos SET photo = ? WHERE id = ?";
                $stmt = $connection->prepare($sql);
                $stmt->bind_param('si', $newFilename, $id);
                $stmt->execute();
                $donnees['photo'] = $newFilename;
                $connection->close();
            }
        } catch (Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] Erreur: " . $e->getMessage());
            $errorMessage = $e->getMessage();
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
    //var_dump($donnees['date_verification']);
    // #############################
    // Préparation des données pour l'affichage
    // #############################
    //var_dump(htmlspecialchars(date('Y-m-d',strtotime($donnees['date_max'])) ?? '', ENT_QUOTES, 'UTF-8'));
    //echo htmlspecialchars($donnees['date_debut'] ?? '', ENT_QUOTES, 'UTF-8');
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
            htmlspecialchars(date('Y-m-d',strtotime($donnees['date_debut'])) ?? '', ENT_QUOTES, 'UTF-8')),
        'date_max' => sprintf('<input name="date_max" type="date" required value="%s">', 
            htmlspecialchars(date('Y-m-d',strtotime($donnees['date_max'])) ?? '', ENT_QUOTES, 'UTF-8')),
        'date_verification' => htmlspecialchars($donnees['date_verification']),
        'reference' => htmlspecialchars($donnees['reference'] ?? '', ENT_QUOTES, 'UTF-8'),
        'photo' => htmlspecialchars($donnees['photo'] ?? 'null.jpeg', ENT_QUOTES, 'UTF-8'),
        'remarques' => htmlspecialchars($donnees['remarques'] ?? '', ENT_QUOTES, 'UTF-8'),
        'nb_elements_initial' => (int)($donnees['nb_elements_initial'] ?? 1),
        'action' => htmlspecialchars($action, ENT_QUOTES, 'UTF-8'),
        'id' => (int)$donnees['id'],
        'isEditMode' => true
    ];
    //var_dump($viewData);
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
        
        <?php if (isset($errorMessage)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        
        <?php if ($isLoggedIn): ?>
        <form enctype="multipart/form-data" method="post" action="fiche_verif.php">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="validation">
            <input type="hidden" name="id" value="<?= $viewData['id'] ?>">
            <input type="hidden" name="categorie" value="<?= $viewData['categorie'] ?>">
            <input type="hidden" name="fabricant" value="<?= $viewData['fabricant'] ?>">
            <input type="hidden" name="facture" value="<?= $viewData['facture'] ?>">
            <input type="hidden" name="appel_liste" value="0">
            <input type="hidden" name="action" value="validation">
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
                        <td rowspan="10">
                            <img src="images/<?= $viewData['photo'] ?>" class="epi-photo" alt="Photo du matériel" width="400">
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
                            <label for="date_max">Date max :</label>
                            <?= $viewData['date_max'] ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="date_verification">Date vérification :</label>
                            <?= $viewData['date_verification'] ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="fabricant_id">Fabricant :</label>
                            <?= $viewData['fabricant_id'] ?>
                        </td>
                    </tr>
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
                        <td colspan="1" rowspan="2">
                            <label for="remarque">Remarques :</label>
                        </td>
                        <td>
                            <p><?= $donnees['remarques'];?></p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <textarea name="remarque" placeholder="Saisissez vos remarques..."  rows="4" cols="40"></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div class="form-actions">
                <?php if ($viewData['isEditMode']): ?>
                <a href="fiche_effacer.php?id=<?= $viewData['id'] ?>&retour=<?= $retour ?>"
                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette fiche ?')">
                    <input type="button"  class="btn btn-danger" value="Supprimer la fiche" name="supprimer">
                </a>
                <?php endif; ?>
                <a href="liste_selection.php">
                    <input type="button" value=<?= $viewData['isEditMode'] ? "Retour " : "Annuler" ;?> class="btn btn-secondary">
                </a>
                
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
        
    </body>
    <footer>
        <?php include $root . 'includes/bandeau_bas.php'; ?>
    </footer>
</html>