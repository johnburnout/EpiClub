<?php
    
    // Inclusion des fichiers de configuration
    require __DIR__ . '/config.php';
    require $root . 'includes/common.php';

    dev($_GET);
    // #############################
    // Initialisation des variables
    // #############################
    $id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
    $retour = isset($_GET['retour']) ? (int)$_GET['retour'] : (isset($_POST['retour']) ? (int)$_POST['retour'] : '');
    $donnees = [
        'reference' => '',
        'libelle' => '',
        'photo' => 'null.jpeg',
        'lieu_id' => 0,
        'categorie' => '',
        'date_debut' => date('Y-m-d'),
        'fabricant' => '',
        'nb_elements' => 0,
        'nb_elements_initial' => 0,
        'date_max' => '',
        'en_service' => 1,
        'remarques' => '',
        'remarque' => '', // Ajouté pour cohérence
        'verification_id' => $_SESSION['controle_en_cours'],
        'utilisateur' => $utilisateur
    ];
    $remarques = ''; // Initialisation explicite
    $bouton = 'Valider le contrôle';
    
    // #############################
    // Traitement des données
    // #############################
    if ($id > 0) {
        $result = lecture_fiche($id);
        if ($result['success']) {
            $donnees = array_merge($donnees, $result['donnees']);
            // Initialisation des remarques avec les données existantes
            $remarques = $donnees['remarques'];
        }
    }
    $donneesInitiales = $donnees;
    if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $bouton = 'Modifier le contrôle';
        
        // Validation CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('Erreur de sécurité: Token CSRF invalide');
        }
        //var_dump($_POST['remarque']); var_dump($_POST['remarques']) ;
        foreach ($_POST as $key => $value) {
            if (array_key_exists($key, $donnees)) {
                $donnees[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }
        // Fusion des remarques
        $remarques_temp = [];
        if (!empty($donnees['remarques'])) {
            $remarques_temp[] = $donnees['remarques'];
        } 
        if (!empty($_POST['remarque'])) {
            $remarques_temp[] = $_POST['remarque'];
        }
        $remarques = implode(nl2br("\n"), array_filter($remarques_temp));
        $donnees['remarques'] = $remarques;
        //var_dump($remarques);
        //var_dump($donnees['remarques']);
        // Mise à jour des données
        
        $donnees['en_service'] = ($_POST['enservice'] === '1') ? 1 : 0;
        $donnees['verification_id'] = isset($_SESSION['controle_en_cours']) ? (int)$_SESSION['controle_en_cours'] : 0;
        $donnees['id'] = $id;
        
        // Validation des données
        if ($donnees['nb_elements'] > $donnees['nb_elements_initial']) {
            die('Erreur: Le nombre d\'éléments ne peut dépasser la quantité initiale');
        }
        //var_dump($donnees);
        // Mise à jour en base
        //var_dump($donnees);
        if ($_POST['action'] == 'validation') {
            $valid = mise_a_jour_fiche($donnees);
            //($valid);
            // Journalisation
            if ($valid['success']) {
                $journalDir = $root.'enregistrements/';
                if (!is_dir($journalDir)) {
                    mkdir($journalDir, 0755, true);
                }
                $journalmat = $root.'enregistrements/journalmat'.$donnees['reference'].'.txt';
                $journal = $root.'enregistrements/journal'.date('Y').'.txt';
                $journalcontrole = $journalDir.'journalcontrole'.$donnees['verification_id'].'.txt';
                $ajoutjournal = date('Y/m/d').' '.$utilisateur.PHP_EOL;
                $reference = $donnees['reference'];
                
                // Vérification des chemins avant écriture
                $modifications = [];
                
                foreach ($donnees as $key => $value) {
                    if (isset($donneesInitiales[$key]) && $donneesInitiales[$key] != $value) {
                        //var_dump($key); var_dump($value);
                        $modifications[] = "$key modifié: ".$donneesInitiales[$key]." -> $value";
                    }
                }
                
                $ajoutjournal = "$reference ".date('Y/m/d')." $utilisateur".PHP_EOL;
                if (!empty($modifications)) {
                    $ajoutjournal .= implode(PHP_EOL, $modifications).PHP_EOL;
                }
                
                try {
                    // Vérification des chemins de fichiers
                    $filesToCheck = [$journalcontrole, $journal, $journalmat];
                    foreach ($filesToCheck as $file) {
                        if (!is_writable($file)) {
                            throw new RuntimeException("Fichier non accessible en écriture: $file");
                        }
                    }
                    
                    // Préparation du contenu avec timestamp
                    $timestamp = date('[Y-m-d H:i:s] ');
                    $logContent = "-------" . PHP_EOL . $timestamp . $ajoutjournal;
                    $logContentControle = "--Controle " . ($_SESSION['controle_en_cours'] ?? 'INCONNU') . "--" . PHP_EOL . $timestamp . $ajoutjournal;
                    
                    // Journal contrôle
                    $handle = fopen($journalcontrole, 'a');
                    flock($handle, LOCK_EX); // Verrouillage pour écriture exclusive
                    fwrite($handle, $logContent) or throw new RuntimeException("Échec écriture journal contrôle");
                    flock($handle, LOCK_UN);
                    fclose($handle);
                    
                    // Journal principal
                    $handle = fopen($journal, 'a');
                    flock($handle, LOCK_EX);
                    fwrite($handle, $logContentControle) or throw new RuntimeException("Échec écriture journal principal");
                    flock($handle, LOCK_UN);
                    fclose($handle);
                    
                    // Journal matériel
                    $handle = fopen($journalmat, 'a');
                    flock($handle, LOCK_EX);
                    fwrite($handle, $logContent) or throw new RuntimeException("Échec écriture journal matériel");
                    flock($handle, LOCK_UN);
                    fclose($handle);
                    
                } catch (Throwable $e) {
                    error_log("ERREUR Journalisation: " . $e->getMessage() . "\nStack Trace:\n" . $e->getTraceAsString());
                    
                    // Message utilisateur générique + identifiant d'erreur unique
                    $errorId = uniqid('LOG_ERR_');
                    echo "<div class='error-notice'>Erreur technique (référence: $errorId). Contactez le support.</div>";
                    
                    // Nettoyage des ressources en cas d'erreur
                    if (isset($handle)) {
                        try {
                            flock($handle, LOCK_UN);
                            fclose($handle);
                        } catch (Throwable $cleanupError) {
                            error_log("Échec nettoyage handle: " . $cleanupError->getMessage());
                        }
                    }
                }
            }
        }
    }
    
    // ... [le reste du code HTML reste inchangé]
    
    // #############################
    // Préparation des données pour l'affichage
    // #############################
    $current_lieu_id = $donnees['lieu_id'] ?? 0;
    $listeLieux = liste_options(['libelles' => 'lieu', 'id' => $current_lieu_id]);
    $selectLieux = $listeLieux[0] ?? '';
    
    $enservice = [
        '1' => $donnees['en_service'] ? 'checked' : '',
        '0' => !$donnees['en_service'] ? 'checked' : ''
    ];
    
    $avis = !$donnees['en_service'] 
    ? '<div class="alert alert-warning">Attention: Vous avez indiqué que l\'EPI n\'est plus en service. Veuillez en indiquer la raison dans les remarques.</div>' 
    : '';
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
        
        <?= $avis ?>
        
        <?php if ($isLoggedIn): ?>
        <form method="post" action="fiche_controle.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="appel_liste" value="0">
            <input type="hidden" name="action" value="maj">
            <input type="hidden" name="reference" value="<?= htmlspecialchars($donnees['reference'], ENT_QUOTES, 'UTF-8') ?>">        
            <input type="hidden" name="controle_id" value="<?= $_SESSION['controle_en_cours'] ?>">
            <table>
                <tbody>
                    <tr>
                        <th colspan="2">Informations de base</th>
                    </tr>
                    <tr>
                        <td width="30%">Référence:</td>
                        <td><?= htmlspecialchars($donnees['reference'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <td>Libellé:</td>
                        <td><?= htmlspecialchars($donnees['libelle'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php if ($donnees['photo'] != 'null.jpeg'): ?>
                    <tr>
                        <td>Photo:</td>
                        <td>
                            <img src="images/<?= htmlspecialchars($donnees['photo'], ENT_QUOTES, 'UTF-8') ?>" 
                                class="epi-photo" 
                                alt="Photo du matériel" width="300">
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td>Lieu:</td>
                        <td>
                            <select name="lieu_id">
                                <?= $selectLieux ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>Catégorie:</td>
                        <td><?= htmlspecialchars($donnees['categorie'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <th colspan="2">Dates</th>
                    </tr>
                    <tr>
                        <td>Date début:</td>
                        <td><?= date('d/m/Y', strtotime($donnees['date_debut'])) ?></td>
                    </tr>
                    <tr>
                        <td>Date max:</td>
                        <td><?= date('d/m/Y', strtotime($donnees['date_max'])) ?></td>
                    </tr>
                    <tr>
                        <th colspan="2">État</th>
                    </tr>
                    <tr>
                        <td>Fabricant:</td>
                        <td><?= htmlspecialchars($donnees['fabricant'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <td>Nombre d'éléments:</td>
                        <td>
                            <input type="number" name="nb_elements" 
                                value="<?= htmlspecialchars($donnees['nb_elements'], ENT_QUOTES, 'UTF-8') ?>" 
                                min="0" max="<?= htmlspecialchars($donnees['nb_elements_initial'], ENT_QUOTES, 'UTF-8') ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>En service:</td>
                        <td>
                            <label>
                                <input type="radio" name="enservice" value="1" <?= $enservice['1'] ?>> Oui
                            </label>
                            <label>
                                <input type="radio" name="enservice" value="0" <?= $enservice['0'] ?>> Non
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="2">Remarques</th>
                    </tr>
                    <tr>
                        <td colspan="2"><p><?= $donnees['remarques'];?></p></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <textarea name="remarque" placeholder="Saisissez vos remarques..." rows="4" cols="40"></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div class="form-actions">
                <a href="liste_controle.php">
                    <input class="btn btn-secondary" type="button" value="Retour à la liste" class="btn btn-secondary">
                </a>
                
                <input type="hidden" name="action" value="validation">
                <input  class="btn btn-primary" type="submit" name="envoyer" value=<?= $bouton ?>>
            </div>
        </form>
        <?php else: ?>
        <div class="alert alert-warning">
            Vous devez être connecté pour accéder à cette fonctionnalité.
            <a href="login.php" class="btn-primary">Se connecter</a>
        </div>
        <?php endif; ?>
        
    </body>
    <footer>
        <?php include $root . 'includes/bandeau_bas.php'; ?>
    </footer>
</html>