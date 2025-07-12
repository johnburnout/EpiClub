<?php
session_start();
// #############################
// Load common data and configuration
// #############################
require "config.php";
require $root."includes/common.php";
require $root."includes/init_donnees.php";

// #############################
// Verify user session
// #############################
$isLoggedIn = !empty($_SESSION['pseudo']);
$connect = $isLoggedIn ? "Connecté comme ".htmlspecialchars($_SESSION['pseudo']) : "Déconnecté";

// #############################
// Initialize variables
// #############################
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$bouton = '';
$avis = '';

// #############################
// Database operations
// #############################
if ($id == 0) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
	    $connection = new mysqli($host, $username, $password, $dbname);
	    $connection->set_charset("utf8mb4");
	} catch (mysqli_sql_exception $e) {
	    die("Erreur de connexion à la base de données: " . $e->getMessage());
	};

    // Get the maximum ID from matos table
    $statement = $connection->query("SELECT MAX(id) AS max_id FROM matos");
    $idmax = $statement->fetch_assoc();
    $id = (int)$idmax['max_id'];

    // Delete record if ID is 0 or delete button was pressed
    if ($id == 0 || isset($_POST['supprimer'])) {
        $statement = $connection->query("DELETE FROM matos WHERE id = ".$id);
        if ($id == 0) {
            $statement = $connection->query("ALTER TABLE matos AUTO_INCREMENT = ".$id);
        }
    }
    $connection->close();
}

// Set button and message based on conditions
if ($id > 0 && isset($_POST['supprimer'])) {
    $bouton = "<a href='liste_selection.php'><input type='button' value='Abandonner'></a>";
    $avis = "Attention ! Voulez-vous vraiment effacer la fiche ?";
}

if (isset($_POST['confirmer'])) {
    $avis = "La fiche a été supprimée, cliquer sur le bouton 'Abandonner' pour sortir.";
}

// #############################
// HTML Output
// #############################
require $root."includes/header.php";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suppression de fiche</title>
</head>
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
        <p><?= htmlspecialchars($avis) ?></p>
        
        <form method="post" action="fiche_effacer.php">
            <table>
                <tbody>
                    <!-- Additional form fields can be added here if needed -->
                </tbody>
                <tfoot>
                    <tr>
                        <td>
                            <?= $bouton ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <input type="submit" name="confirmer" value="Confirmer">
                        </td>
                    </tr>
                </tfoot>
            </table>
        </form>
    <?php else: ?>
        <p>Vous n'êtes pas connecté</p>
    <?php endif; ?>

    <?php require $root."includes/footer.php"; ?>
</body>
</html>