<?php
session_start();

// Inclusion des fichiers de configuration avec vérification
defined('ROOT') or define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require __DIR__ . '/config.php';
require ROOT . 'includes/common.php';

// Initialisation de la connexion MySQLi avec gestion d'erreurs
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $connection = new mysqli($host, $username, $password, $dbname);
    $connection->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
};

// Vérification de la connexion utilisateur
$connect = (count($_SESSION) > 0) ? "Connecté comme ".htmlspecialchars($_SESSION['pseudo']) : "Déconnecté";

// #############################
// Gestion de la pagination avec validation
// #############################
$defaults = [
    'debut' => 1,
    'long' => 20,
    'nblignes' => 20,
    'id' => 1,
    'lieu_id' => 0,
    'cat_id' => 0,
    'tri' => 'id'
];

// Fonction de validation/sanitization
function sanitizeInput($input, $type = 'int') {
    switch ($type) {
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        case 'string':
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        default:
            return null;
    }
}

// Traitement des paramètres
$params = [];
foreach ($defaults as $key => $default) {
    if (isset($_POST[$key])) {
        $params[$key] = sanitizeInput($_POST[$key], is_numeric($default) ? 'int' : 'string');
    } else {
        $params[$key] = $default;
    }
}

// Gestion des cookies
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    setcookie('debut', $params['debut'], time()+86400, '/', '', true, true);
    setcookie('long', $params['long'], time()+86400, '/', '', true, true);
    setcookie('nblignes', $params['nblignes'], time()+86400, '/', '', true, true);
} else {
    $params['debut'] = $_COOKIE['debut'] ?? $defaults['debut'];
    $params['long'] = $_COOKIE['long'] ?? $defaults['long'];
    $params['nblignes'] = $_COOKIE['nblignes'] ?? $defaults['nblignes'];
}

// ############################
// Récupération des listes (lieux et catégories)
// ############################
try {
    // Récupération des lieux
    $stmt = $connection->prepare("SELECT id, libelle FROM lieu ORDER BY id");
    $stmt->execute();
    $lieux = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $nLieux = count($lieux);

    // Récupération des catégories
    $stmt = $connection->prepare("SELECT id, libelle FROM categorie ORDER BY id");
    $stmt->execute();
    $categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $nCategorie = count($categories);
} catch (mysqli_sql_exception $e) {
    die("Erreur lors de la récupération des données: " . $e->getMessage());
}

// Construction des listes déroulantes
$listeLieux = "<option value='0'>*</option>";
foreach ($lieux as $lieu) {
    $selected = ($params['lieu_id'] == $lieu['id']) ? 'selected' : '';
    $listeLieux .= "<option value='{$lieu['id']}' $selected>{$lieu['libelle']}</option>";
}

$listeCategories = "<option value='0'>*</option>";
foreach ($categories as $cat) {
    $selected = ($params['cat_id'] == $cat['id']) ? 'selected' : '';
    $listeCategories .= "<option value='{$cat['id']}' $selected>{$cat['libelle']}</option>";
}

// ###################################
// Construction de la requête principale avec protection
// ###################################
$whereClauses = [];
$queryParams = [];
$types = '';

if ($params['lieu_id'] > 0) {
    $whereClauses[] = "lieu_id = ?";
    $queryParams[] = $params['lieu_id'];
    $types .= 'i';
}

if ($params['cat_id'] > 0) {
    $whereClauses[] = "categorie_id = ?";
    $queryParams[] = $params['cat_id'];
    $types .= 'i';
}

$where = empty($whereClauses) ? '' : 'WHERE ' . implode(' AND ', $whereClauses);

// Validation du champ de tri
$allowedSort = ['id', 'ref', 'lieu_id', 'date_verification', 'fabricant'];
$sort = in_array($params['tri'], $allowedSort) ? $params['tri'] : 'id';

try {
    $sql = "SELECT id, ref, libelle, fabricant, categorie, categorie_id, 
                   lieu, lieu_id, nb_elements, date_verification, date_max
            FROM liste $where ORDER BY $sort";
    
    $stmt = $connection->prepare($sql);
    
    if (!empty($queryParams)) {
        $stmt->bind_param($types, ...$queryParams);
    }
    
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $nblignes = count($result);
    
    // Calcul de la pagination
    $nbpages = ceil($nblignes / $params['long']);
} catch (mysqli_sql_exception $e) {
    die("Erreur lors de l'exécution de la requête: " . $e->getMessage());
}
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
    <p align="right">
        <?php if (count($_SESSION) > 0): ?>
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

    <?php if (count($_SESSION) > 0): ?>
        <h3>Filtrer les données</h3>
        
        <form method="post">
            <table>
                <thead>
                    <tr>
                        <th colspan="2">Filtrer par :</th>
                        <th colspan="2">Trier par :</th>
                        <th>Nb de lignes par feuille:</th>
                        <th>Première ligne :</th>
                    </tr>
                    <tr>
                        <td>Lieu</td>
                        <td>Catégorie</td>
                        <td rowspan="2">
                            <select name="tri">
                                <option value="id" <?= $sort === 'id' ? 'selected' : '' ?>>Identifiant</option>
                                <option value="ref" <?= $sort === 'ref' ? 'selected' : '' ?>>Référence</option>
                                <option value="lieu_id" <?= $sort === 'lieu_id' ? 'selected' : '' ?>>Lieu</option>
                                <option value="date_verification" <?= $sort === 'date_verification' ? 'selected' : '' ?>>Date de vérification</option>
                                <option value="fabricant" <?= $sort === 'fabricant' ? 'selected' : '' ?>>Fabricant</option>
                            </select>
                        </td>
                        <td colspan="2" rowspan="2">
                            <input type="number" name="long" min="5" max="<?= min($nblignes + $params['long'], $params['long']*($nbpages+1)) ?>" step="5" value="<?= $params['long'] ?>">
                        </td>
                        <td rowspan="2">
                            <input type="number" name="debut" min="1" step="<?= $params['long'] ?>" max="<?= max($nblignes, $nbpages*$params['long']) ?>" value="<?= $params['debut'] + 1 ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <select name="lieu_id">
                                <?= $listeLieux ?>
                            </select>
                        </td>
                        <td>
                            <select name="cat_id">
                                <?= $listeCategories ?>
                            </select>
                        </td>
                    </tr>
                </thead>
            </table>
            <p></p>
            <input type="submit" name="choix" value="Filtrer et trier">
        </form>

        <?php if ($result && $nblignes > 0): ?>
            <hr>
            <h3>Liste</h3>
            
            <form method="post" action="fiche_verif.php">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Ref</th>
                            <th>Libellé</th>
                            <th>Fabricant</th>
                            <th>Cat</th>
                            <th>Lieu</th>
                            <th>Nb éléments</th>
                            <th>Date Vérif</th>
                            <th>Date Max</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = $params['debut']; $i < min($params['debut'] + $params['long'], $nblignes); $i++): ?>
                            <tr>
                                <td><input type="radio" name="id" required value="<?= $result[$i]['id'] ?>"></td>
                                <td><?= htmlspecialchars($result[$i]['ref']) ?></td>
                                <td><?= htmlspecialchars($result[$i]['libelle']) ?></td>
                                <td><?= htmlspecialchars($result[$i]['fabricant']) ?></td>
                                <td><?= htmlspecialchars($result[$i]['categorie']) ?></td>
                                <td><?= htmlspecialchars($result[$i]['lieu']) ?></td>
                                <td><?= htmlspecialchars($result[$i]['nb_elements']) ?></td>
                                <td><?= htmlspecialchars($result[$i]['date_verification']) ?></td>
                                <td><?= htmlspecialchars($result[$i]['date_max']) ?></td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
                <p></p>
                <input type="hidden" name="appel_liste" value="1">
                <input type="submit" name="submit" value="Afficher la fiche">
                <a href="fiche_creation.php">
                    <input type="button" value="Créer une nouvelle fiche">
                </a>
                <a href="index.php">
                    <input type="button" value="Revenir à l'accueil">
                </a>
            </form>
        <?php else: ?>
            <p>Aucune fiche trouvée !</p>
        <?php endif; ?>
    <?php else: ?>
        <p>Vous n'êtes pas connecté</p>
    <?php endif; ?>

<?php 
$connection->close();
require $root."includes/footer.php"; 
?>