<?php
session_start();

// Inclusion des fichiers de configuration avec vérification
defined('ROOT') or define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require __DIR__ . '/config.php';
require ROOT . 'includes/common.php';

// #############################
// Verification connexion
// #############################
$isLoggedIn = !empty($_SESSION['pseudo']);
$connect = $isLoggedIn ? "Connecté comme ".htmlspecialchars($_SESSION['pseudo']) : "Déconnecté";

// #############################
// Initialisation variables
// #############################

$id = isset($_POST['id']) ? $_POST['id'] : 0;
$action = isset($_POST['action']) ? 'validation' : 'maj';
$bouton = ($action == 'validation') ? 'Retour' : 'Abandonner';

// #############################
// Gestion des opérations CRUD
// #############################

if ($id > 0) {
    $result = lecture_fiche($id);
    $donnees = array_merge($donnees, $result['resultat'] ?? []);
}

// Mise à jour des données avec les valeurs POST
foreach ($_POST as $key => $value) {
    if (array_key_exists($key, $donnees)) {
        $donnees[$key] = htmlspecialchars($value);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'validation') {
    $valid = maj_fiche($donnees);
}

// #############################
// Récupération des listes d'options
// #############################
$current_facture_id = $donnees[$id]['facture_id'] - 1 ?? 0;
$current_lieu_id = $donnees[$id]['lieu_id'] - 1 ?? 0;
$current_categorie_id = $donnees[$id]['categorie_id'] - 1 ?? 0;
$current_fabricant_id = $donnees[$id]['fabricant_id'] - 1 ?? 0;

$listeFactures = liste_options(['libelles' => 'facture', 'id' => $current_facture_id]);
$listeLieux = liste_options(['libelles' => 'lieu', 'id' => $current_lieu_id]);
$listeCategories = liste_options(['libelles' => 'categorie', 'id' => $current_categorie_id]);
$listeFabricants = liste_options(['libelles' => 'fabricant', 'id' => $current_fabricant_id]);

// #############################
// Gestion de l'upload de fichiers
// #############################
$uploadSuccess = false;
if (!empty($_FILES['monfichier']['name']) && $id > 0) {
    $allowedExtensions = ['jpeg', 'jpg', 'gif', 'png'];
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxFileSize = 2 * 1024 * 1024; // 2MB
    
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($_FILES['monfichier']['tmp_name']);
    
    $fileExtension = strtolower(pathinfo($_FILES['monfichier']['name'], PATHINFO_EXTENSION));
    
    if (in_array($mimeType, $allowedMimeTypes) && 
        in_array($fileExtension, $allowedExtensions) &&
        $_FILES['monfichier']['size'] <= $maxFileSize &&
        is_uploaded_file($_FILES['monfichier']['tmp_name'])) {
        
        $uploadDir = dirname(__FILE__).'/images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $newFilename = ($donnees['reference'] ?? 'file') . date('YmdHis') . '.' . $fileExtension;
        $destination = $uploadDir . $newFilename;

        if (move_uploaded_file($_FILES['monfichier']['tmp_name'], $destination)) {
            $uploadSuccess = true;
            // Mise à jour de la photo dans la base
            try {
                $sql = "UPDATE matos SET photo = ? WHERE id = ?";
                $stmt = $connection->prepare($sql);
                $stmt->bind_param('si', $newFilename, $id);
                $stmt->execute();
                $donnees['photo'] = $newFilename;
            } catch (Exception $e) {
                error_log("Erreur mise à jour photo: ".$e->getMessage());
            }
        }
    }
}

// #############################
// Préparation des données pour l'affichage
// #############################
//if ($action == 'maj') {
//    $liste = [
//        'lieu_id' => "<label for='lieu_id'>Lieu :</label><select name='lieu_id'>".$listeLieux[0]."</select>",
//        'categorie_id' => m
//        'fabricant_id' => "Fabricant :<br>".htmlspecialchars($donnees['fabricant']),
//        'facture_id' => "Facture :<br>".htmlspecialchars($donnees['vendeur'])." (".htmlspecialchars($donnees['date_facture']).")",
//        'libelle' => "Libellé :<br>".htmlspecialchars($donnees['libelle']),
//        'date_debut' => "Date mise en service :<br>".htmlspecialchars($donnees['date_debut'])
//    ];
//} else {
    $liste = [
        'lieu_id' => "<label for='lieu_id'>Lieu :</label><select name='lieu_id'>".$listeLieux[0]."</select>",
        'categorie_id' => "<label for='categorie_id'>Catégorie :</label><select name='categorie_id'>".$listeCategories[0]."</select>",
        'fabricant_id' => "<label for='fabricant_id'>Fabricant :</label><select name='fabricant_id'>".$listeFabricants[0]."</select>",
        'facture_id' => "<label for='facture_id'>Facture :</label><select name='facture_id'>".$listeFactures[0]."</select>",
        'libelle' => "<label for='libelle'>Libellé :</label><input name='libelle' type='text' required value='".htmlspecialchars($donnees['libelle'])."'>",
        'date_debut' => "<label for='date_debut'>Mise en service :</label><input name='date_debut' type='date' value='".htmlspecialchars($donnees['date_debut'])."'>"
    ];
//};
?>
// #############################
// Affichage HTML
// #############################

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Gestionnaire EPI</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .error { color: red; margin-bottom: 15px; }
        form { margin-top: 20px; }
        table { margin: 20px 0; }
        input[type="text"], input[type="password"] { padding: 5px; width: 200px; }
        input[type="submit"] { padding: 5px 15px; }
    </style>
	<?php include ROOT . 'includes/header.php';?>
</head>
<body>
    <p>
        <?php if ($isLoggedIn): ?>
            <form action="index.php" method="post">
                <?= htmlspecialchars($connect) ?> 
                <input type="submit" name="deconnexion" value="Déconnexion">
            </form>
        <?php else: ?>
            <a href="login.php">Connexion</a>
        <?php endif; ?>
    </p>
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
    <form enctype="multipart/form-data" method="post" action="fiche_verif.php">
        <table>
            <tbody>
                <tr>
                    <td>Référence :<br><?= htmlspecialchars($donnees['reference']) ?></td>
                    <td colspan="1">
                        <label for="photo">Adresse photo :</label>
                        <input name="photo" value="<?= htmlspecialchars($donnees['photo']) ?>">
                    </td>
                </tr>
                <tr>
                    <td><?= $liste['libelle'] ?></td>
                    <td rowspan="7" colspan="2">
                        <img src="images/<?= htmlspecialchars($donnees['photo']) ?>" width="400" alt="Photo du matériel">
                    </td>
                </tr>
                <tr>
                    <td><?= $liste['lieu_id'] ?></td>
                </tr>
                <tr>
                    <td><?= $liste['categorie_id'] ?></td>
                </tr>
                <tr>
                    <td><?= $liste['date_debut'] ?></td>
                </tr>
                <tr>
                    <td><?= $liste['fabricant_id'] ?></td>
                </tr>
                <tr>
                    <td>
                        <label for="nb_elements">Nombre d'éléments :</label>
                        <input type="number" name="nb_elements" value="<?= htmlspecialchars($donnees['nb_elements']) ?>" min="0" max="<?= htmlspecialchars($donnees['nb_elements_initial']) ?>">
                    </td>
                </tr>
                <tr>
                    <td><?= $liste['facture_id'] ?></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <label for="remarques">Remarques</label>
                        <textarea name="remarques" cols="100" rows="5"><?= htmlspecialchars($donnees['remarques']) ?></textarea>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td>
                        <input type="hidden" name="MAX_FILE_SIZE" value="2000000">
                        Envoyer une image :
                    </td>
                    <td colspan="1">
                        <input type="file" name="monfichier">
                    </td>
                </tr>
                <tr>
                    <td align="left">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                        <input type="hidden" name="appel_liste" value="0">
                        <input type="hidden" name="action" value="maj">
                        <input type="hidden" name="debut" value="<?= htmlspecialchars($debut) ?>">
                        <input type="hidden" name="long" value="<?= htmlspecialchars($long) ?>">
                        <input type="hidden" name="nblignes" value="<?= htmlspecialchars($nblignes) ?>">
                            <a href="liste_selection.php">
                                <input type="button" value="<?= htmlspecialchars($bouton) ?>">
                            </a>
                    </td>
                    <td align="right">
                        <input type="submit" name="envoyer" value="Mise à jour de la fiche">
                    </td>
                </tr>
            </tfoot>
        </table>
    </form>
    <p>
        <form method="post" action="fiche_effacer.php">
            <input type="hidden" value="<?= htmlspecialchars($id) ?>" name="id">
            <input type="submit" value="Supprimer la fiche" name="supprimer">            
        </form>
    </p>
    <?php else: ?>
    <p>Vous n'êtes pas connecté</p>
    <?php endif; ?>
<?php
require $root."includes/footer.php"; 
?>