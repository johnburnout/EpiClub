<?php
session_start();

// #############################
// Récupère les données communes
// #############################
require "config.php";
require $root."includes/common.php";
require $root."includes/init_donnees.php";

// #############################
// Vérification connexion utilisateur
// #############################
$isLoggedIn = !empty($_SESSION['pseudo']);
$connect = $isLoggedIn ? "Connecté comme ".htmlspecialchars($_SESSION['pseudo']) : "Déconnecté";

// #############################
// Initialisation des variables
// #############################
$defaults = [
    'action' => 'creation',
    'id' => 0,
	'facture_id' => 0
];

foreach ($defaults as $key => $value) {
    $$key = $_POST[$key] ?? $value;
}

if ($action === 'creation') {
    $donnees = init_donnees();
}

foreach ($defaults as $key => $value) {
    $donnees[$key] = $_POST[$key] ?? $value;
}

// #############################
// Gestion des opérations CRUD
// #############################
if ($action === 'creation') {
    $creation = creation_fiche($donnees);
    $id = $creation['id'] ?? 0;
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'maj') {
    $valid = maj_fiche($donnees);
}

// #############################
// Récupération des listes d'options
// #############################
$current_facture_id = $donnees[$id]['facture_id'] - 1 ?? 0;
$current_lieu_id = $donnees[$id]['lieu_id'] - 1 ?? 0;
$current_categorie_id = $donnees[$id]['categorie_id'] - 1 ?? 0;
$current_fabricant_id = $donnees[$id]['fabricant_id'] - 1 ?? 0;

$liste1ctures = liste_options(['libelles' => 'facture', 'id' => $current_facture_id]);
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
$viewData = [
    'lieu_id' => sprintf('<select name="lieu_id">%s</select>', $listeLieux[0] ?? ''),
    'categorie_id' => sprintf('<select name="categorie_id">%s</select>', $listeCategories[0] ?? ''),
    'fabricant_id' => sprintf('<select name="fabricant_id">%s</select>', $listeFabricants[0] ?? ''),
    'facture_id' => sprintf('<select name="facture_id">%s</select>', $listeFactures[0] ?? ''),
    'libelle' => sprintf('<input name="libelle" type="text" required value="%s">', 
                        htmlspecialchars($donnees['libelle'] ?? '')),
    'date_debut' => sprintf('<input name="date_debut" type="date" value="%s">', 
                           htmlspecialchars($donnees['date_debut'] ?? '')),
    'reference' => htmlspecialchars($donnees['reference'] ?? ''),
    'photo' => htmlspecialchars($donnees['photo'] ?? ''),
    'remarques' => htmlspecialchars($donnees['remarques'] ?? ''),
    'nb_elements_initial' => (int)($donnees['nb_elements_initial'] ?? 1),
    'action' => htmlspecialchars($action),
    'id' => (int)$id,
    'debut' => (int)$debut,
    'long' => (int)$long,
    'nblignes' => (int)$nblignes,
    'isEditMode' => $action === 'maj'
];

// #############################
// Affichage du HTML
// #############################

include $root."includes/header.php";
?>

<body>
    <p>
        <?php if ($isLoggedIn): ?>
            <form action="index.php" method="post">
                <?= $connect ?> 
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
    <form enctype="multipart/form-data" method="post" action="fiche_creation.php">
        <table>
            <tbody>
                <tr>
                    <td>
                        <label for="reference">Référence :</label>
                        <input type="text" required name="reference" value="<?= $viewData['reference'] ?>">
                    </td>
                    <td>
                        Photo : <input name="photo" value="<?= $viewData['photo'] ?>">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="libelle">Libellé :</label>
                        <?= $viewData['libelle'] ?>
                    </td>
                    <td rowspan="7" colspan="1">
                        <img src="images/<?= $viewData['photo'] ?>" width="400" alt="Photo du matériel">
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
                <tr>
                    <td>
                        <label for="nb_elements_initial">Nombre d'éléments :</label>
                        <input type="number" name="nb_elements_initial" value="<?= $viewData['nb_elements_initial'] ?>" min="1">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="facture_id">Facture :</label>
                        <?= $viewData['facture_id'] ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <label for="remarques">Remarques :</label>
                        <textarea name="remarques" cols="100" rows="5"><?= $viewData['remarques'] ?></textarea>
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
                    <td>
                        <input type="hidden" name="id" value="<?= $viewData['id'] ?>">
                        <input type="hidden" name="appel_liste" value="0">
                        <input type="hidden" name="action" value="maj">
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= $viewData['isEditMode'] ? 'CLIQUER POUR ENREGISTRER LA FICHE ---------->' : 'ATTENTION : FICHE NON ENREGISTREE' ?>
                    </td>
                    <td align="right">
                        <input type="submit" name="envoyer" value="Enregistrer la fiche">
                    </td>
                </tr>
                <tr>
                    <td>
                        <a href="fiche_effacer.php">
                            <input type="button" value="Abandonner et effacer la fiche">
                        </a>
                    </td>
                    <td align="right">
                        <?php if ($viewData['isEditMode']): ?>
                            <a href="index.php"><input type="button" value="Retour à l'accueil" name="accueil"></a>
                        <?php endif; ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </form>
    <?php else: ?>
    <p>Vous n'êtes pas connecté</p>
    <?php endif; ?>

    <?php require $root."includes/footer.php"; ?>