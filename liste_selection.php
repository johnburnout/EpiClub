<?php
    
    echo "semble ok";  
    
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    
    // Démarrage de session sécurisé avec plusieurs paramètres de protection
    session_start([
        'cookie_httponly' => true,    // Empêche l'accès au cookie via JavaScript
        'cookie_secure' => true,      // Cookie uniquement envoyé en HTTPS
        'use_strict_mode' => true     // Protection contre les attaques de fixation de session
    ]);
    
    // Inclusion des fichiers de configuration avec vérification implicite
    // Note: $root devrait être définie quelque part avant ces inclusions
    require 'config.php';          // Fichier de configuration principal
    require $root.'includes/common.php';  // Fonctions communes
    
    // ##############################################
    // CONNEXION À LA BASE DE DONNÉES AVEC GESTION D'ERREURS
    // ##############################################
    
    // Configuration du rapport d'erreurs MySQLi
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    try {
        // Création de la connexion MySQLi
        $connection = new mysqli($host, $username, $password, $dbname);
        
        // Définition du charset pour supporter tous les caractères (y compris émojis)
        $connection->set_charset("utf8mb4");
    } catch (mysqli_sql_exception $e) {
        // En production, vous pourriez logger cette erreur et afficher un message générique
        die("Erreur de connexion à la base de données: " . $e->getMessage());
    }
    
    // ##############################################
    // VÉRIFICATION DE LA CONNEXION UTILISATEUR
    // ##############################################
    
    $isLoggedIn = !empty($_SESSION['pseudo']);  // Vérifie si l'utilisateur est connecté
    $connect = $isLoggedIn ? "Connecté comme ".htmlspecialchars($_SESSION['pseudo']) : "Déconnecté";
    $utilisateur = $isLoggedIn ? $_SESSION['pseudo'] : "Déconnecté";
    
    // ##############################################
    // GESTION DE LA PAGINATION ET DES FILTRES
    // ##############################################
    
    // Valeurs par défaut pour les paramètres
    $defaults = [
        'debut' => 1,            // Première ligne à afficher
        'long' => 20,            // Nombre de lignes par page
        'nblignes' => 20,        // Nombre total de lignes
        'id' => 1,               // ID par défaut
        'lieu_id' => 0,          // Filtre lieu (0 = tous)
        'cat_id' => 0,           // Filtre catégorie (0 = tous)
        'tri' => 'id',           // Champ de tri par défaut
        'est_en_service' => '1'  // Filtre "en service" par défaut (1 = oui)
    ];
    $retour = 'liste_selection.php';
    /**
    * Nettoie et valide une entrée utilisateur
    * @param mixed $input La donnée à nettoyer
    * @param string $type Le type de validation ('int' ou 'string')
    * @return mixed La donnée nettoyée
    */
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
    
    // Traitement des paramètres de requête
    $params = [];
    foreach ($defaults as $key => $default) {
        if (isset($_POST[$key])) {
            // Nettoie l'entrée selon son type (int ou string)
            $params[$key] = sanitizeInput($_POST[$key], is_numeric($default) ? 'int' : 'string');
            } else {
                // Utilise la valeur par défaut si le paramètre n'est pas fourni
                $params[$key] = $default;
            }
                }
                //var_dump($params);
                // Gestion des cookies pour conserver les préférences utilisateur
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    // Enregistre les préférences dans des cookies sécurisés
                    setcookie('debut', $params['debut'], time() + 24*60*60, '/', '', true, true);
                    setcookie('long', $params['long'], time() + 24*60*60, '/', '', true, true);
                    setcookie('nblignes', $params['nblignes'], time() + 24*60*60, '/', '', true, true);
                } else {
                    // Récupère les préférences depuis les cookies ou utilise les valeurs par défaut
                    $params['debut'] = $_COOKIE['debut'] ?? $defaults['debut'];
                    $params['long'] = $_COOKIE['long'] ?? $defaults['long'];
                    $params['nblignes'] = $_COOKIE['nblignes'] ?? $defaults['nblignes'];
                }
                
                // ##############################################
                // PRÉPARATION DES LISTES D'OPTIONS
                // ##############################################
                
                // Génère les listes déroulantes pour les lieux et catégories
                // Note: liste_options() est probablement définie dans common.php
                $listeLieux = liste_options(['libelles' => 'lieu', 'id' => $params['lieu_id']]);
                $listeLieux[0] = "<option value='*'>Tous</option>".$listeLieux[0];
                $listeCategories = liste_options(['libelles' => 'categorie', 'id' => $params['cat_id']]);
                $listeCategories[0] = "<option value='*'>Toutes</option>".$listeCategories[0];
                
                // Options pour le filtre "en service"
                $enservice = [
                    'oui' => (!isset($_POST['est_en_service']) || $_POST['est_en_service'] == '1') ? '"1" selected' : '"1"',
                    'non' => (isset($_POST['est_en_service']) && $_POST['est_en_service'] == '0') ? '"0" selected' : '"0"',
                    '*' => (isset($_POST['est_en_service']) && $_POST['est_en_service'] == '*') ? '"*" selected' : '"*"'
                ];
                
                // ##############################################
                // CONSTRUCTION DE LA REQUÊTE SQL SÉCURISÉE
                // ##############################################
                
                $whereClauses = [];  // Conditions WHERE
                $queryParams = [];   // Paramètres pour la requête préparée
                $types = '';         // Types des paramètres (i = integer, s = string)
                
                // Construction dynamique de la clause WHERE
                if ($params['lieu_id'] > 0) {
                    $whereClauses[] = "lieu_id = ?";
                    $queryParams[] = $params['lieu_id'];
                    $types .= 'i';  // Type integer
                }
                
                if ($params['cat_id'] > 0) {
                    $whereClauses[] = "categorie_id = ?";
                    $queryParams[] = $params['cat_id'];
                    $types .= 'i';  // Type integer
                }
                
                // Filtre "en service" (toujours présent)
                $whereClauses[] = "en_service = ?";
                $queryParams[] = $params['est_en_service'];
                $types .= 's';  // Type string
                
                // Combinaison des conditions WHERE
                $where = empty($whereClauses) ? '' : 'WHERE ' . implode(' AND ', $whereClauses);
                
                // Validation du champ de tri (whitelist)
                $allowedSort = ['id', 'ref', 'lieu_id', 'date_verification', 'fabricant'];
                $sort = in_array($params['tri'], $allowedSort) ? $params['tri'] : 'id';
                
                // Exécution de la requête principale
                try {
                    $sql = "SELECT id, ref, libelle, fabricant, categorie, categorie_id, 
                    lieu, lieu_id, nb_elements, date_verification, date_max
                    FROM liste $where ORDER BY $sort";
                    
                    $stmt = $connection->prepare($sql);
                    
                    // Liaison des paramètres si nécessaire
                    if (!empty($queryParams)) {
                        $stmt->bind_param($types, ...$queryParams);
                    }
                    
                    // Exécution et récupération des résultats
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $nblignes = count($result);
                    
                    // Calcul de la pagination
                    $nbpages = ceil($nblignes / $params['long']);
                } catch (mysqli_sql_exception $e) {
                    die("Erreur lors de l'exécution de la requête: " . $e->getMessage());
                }
                
                // ##############################################
                // AFFICHAGE HTML
                // ##############################################
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Connexion - Gestionnaire EPI</title>
        <?php include $root.'includes/header.php'; ?>
    </head>
    
    <body>
        <!-- En-tête avec statut de connexion -->
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
        
        <!-- Logo et titre -->
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
        <!-- Formulaire de filtrage -->
        <h3>Filtrer les données</h3>
        
        <form method="post">
            <table>
                <thead>
                    <tr>
                        <th colspan="3">Filtrer par :</th>
                        <th colspan="2">Trier par :</th>
                        <th>Nb de lignes par feuille:</th>
                        <th>Première ligne :</th>
                    </tr>
                    <tr>
                        <td>Lieu</td>
                        <td>Catégorie</td>
                        <td>En service</td>
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
                            <input type="number" name="debut" min="1" step="<?= $params['long'] ?>" max="<?= max($nblignes, $nbpages*$params['long'],1) ?>" value="<?= $params['debut'] ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <select name="lieu_id">
                                <?= $listeLieux[0] ?>
                            </select>
                        </td>
                        <td>
                            <select name="cat_id">
                                <?= $listeCategories[0] ?>
                            </select>
                        </td>
                        <td>
                            <select name="est_en_service">
                                <option value=<?=$enservice['oui']?>>Oui</option>
                                <option value=<?=$enservice['non']?>>Non</option>
                                <option value=<?=$enservice['*']?>>Tous</option>
                            </select>
                        </td>
                    </tr>
                </thead>
            </table>
            <p></p>
            <input type="submit" name="choix" value="Filtrer et trier">
        </form>
        
        <!-- Affichage des résultats -->
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
            <input type="hidden" name="action" value="affichage">                
            <input type="hidden" name="appel_liste" value="1">
            <input type="submit" name="submit" value="Afficher la fiche">
            <input type="hidden" name="retour" value="<?= $retour ?>" >
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
            // Fermeture de la connexion et pied de page
            $connection->close();
            require $root."includes/footer.php"; 
        ?>
    </body>
</html>