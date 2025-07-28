<?php
    
    require __DIR__ . '/config.php';          // Fichier de configuration principal
    require $root.'includes/common.php';  // Fonctions communes
    
    // ##############################################
    // CONNEXION À LA BASE DE DONNÉES AVEC GESTION D'ERREURS
    // ##############################################
    
    // Configuration du rapport d'erreurs MySQLi
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    try {
        $connection = new mysqli($host, $username, $password, $dbname);
        if ($connection->connect_error) {
            throw new Exception("Échec de la connexion : " . $connection->connect_error);
        }
        $connection->set_charset("utf8mb4");
    } catch (Exception $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
        
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
    
    $whereClauses = ["en_service = 1"];
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
    
    $where = implode(' AND ', $whereClauses);
    
    // Validation du champ de tri
    $allowedSort = ['id', 'ref', 'lieu_id', 'date_verification', 'fabricant'];
    $sort = in_array($params['tri'], $allowedSort) ? $params['tri'] : 'id';
    
    try {
        // Requête pour le comptage total
        $countSql = "SELECT COUNT(*) AS total FROM liste WHERE $where";
        $countStmt = $connection->prepare($countSql);
        
        if (!empty($queryParams)) {
            $countStmt->bind_param($types, ...$queryParams);
        }
        
        $countStmt->execute();
        $totalCount = $countStmt->get_result()->fetch_assoc()['total'];
        $nblignes = (int)$totalCount;
        $nbpages = ceil($nblignes / max(1, $params['long']));
        
        // Requête pour les données paginées
        $sql = "SELECT id, ref, libelle, fabricant, categorie, categorie_id, 
        lieu, lieu_id, nb_elements, date_verification, date_max
        FROM liste 
        WHERE $where 
        ORDER BY $sort 
        LIMIT ? OFFSET ?";
        
        $stmt = $connection->prepare($sql);
        
        $limit = max(1, min($params['long'], 100));
        $offset = max(0, ($params['debut'] - 1) * $limit);
        
        // Fusion des paramètres
        $allParams = $queryParams;
        $allParams[] = $limit;
        $allParams[] = $offset;
        $allTypes = $types . 'ii';
        
        if (!empty($queryParams)) {
            $stmt->bind_param($allTypes, ...$allParams);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (mysqli_sql_exception $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] Erreur DB: " . $e->getMessage());
        die("Une erreur est survenue lors de la récupération des données. Veuillez réessayer.");
    }
    $connection->close();
                
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Connexion - Gestionnaire EPI</title>
        <?php include $root.'includes/bandeau.php'; ?>
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
                        <td align="left"><img src="images/EPIClub.png" alt="Logo EPIClub" width="100" class="img-fluid"></td>
                        <td align="center"><h1>Gestionnaire EPI</h1></td>
                        <td align="right"><img src="images/logo.png" width="150" alt="Logo Périgord Escalade" class="img-fluid"></td>
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
            <input class="btn btn-secondary" type="submit" name="choix" value="Filtrer et trier">
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
                        <td><input type="radio" name="id" value="<?= $result[$i]['id'] ?>"></td>
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
            
            <!-- Pagination -->
            <?php if ($nbpages > 1): ?>
            <div class="pagination">
                <button class="btn btn-secondary" type="submit" name="debut" value="1" <?= $params['debut'] <= 1 ? 'disabled' : '' ?>>Première</button>
                <button class="btn btn-secondary" type="submit" name="debut" value="<?= max(1, $params['debut'] - $params['long']) ?>" <?= $params['debut'] <= 1 ? 'disabled' : '' ?>>Précédente</button>
                <span>Page <?= ceil($params['debut'] / $params['long']) ?> sur <?= $nbpages ?></span>
                <button  class="btn btn-secondary" type="submit" name="debut" value="<?= min($nblignes, $params['debut'] + $params['long']) ?>" <?= $params['debut'] + $params['long'] > $nblignes ? 'disabled' : '' ?>>Suivante</button>
                <button  class="btn btn-secondary" type="submit" name="debut" value="<?= max(1, ($nbpages - 1) * $params['long'] + 1) ?>" <?= $params['debut'] + $params['long'] > $nblignes ? 'disabled' : '' ?>>Dernière</button>
            </div>
            <?php endif; ?>
            
            <p></p>
            <input type="hidden" name="action" value="affichage">                
            <input type="hidden" name="appel_liste" value="1">
            <input type="submit" class="btn btn-primary btn-block" name="submit" value="Afficher la fiche">
            <input type="hidden" name="retour" value="<?= $retour ?>" >
            <a href="index.php">
                <input type="button" class="btn btn-primary btn-block"value="Revenir à l'accueil">
            </a>
        </form>
        <?php else: ?>
        <p>Aucune fiche trouvée !</p>
        <?php endif; ?>
        <?php else: ?>
        <p>Vous n'êtes pas connecté</p>
        <?php endif; ?>
        
    </body>
    <footer>
        <?php include $root . 'includes/bandeau_bas.php'; ?>
    </footer>
</html>