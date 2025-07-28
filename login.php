<?php
    
    // Inclusion des fichiers de configuration
    require __DIR__ . '/config.php';          // Fichier de configuration principal
    require $root . "includes/common.php";

    // Initialisation
    $error_message = "";
    $isLoggedIn = isset($_SESSION['user_id']);
    
    // Traitement de la déconnexion
    if (isset($_POST['deconnexion'])) {
        // Nettoyage complet de la session
        $_SESSION = [];
        
        // Invalide le cookie de session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }
        
        // Destruction et redirection
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('Erreur de sécurité: Token CSRF invalide');
        }
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    // Traitement du formulaire de connexion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connexion'])) {
        // Validation des champs
        if (empty($_POST['pseudo']) || empty($_POST['mdp'])) {
            $error_message = "Tous les champs doivent être remplis";
        } else {
            // Nettoyage des entrées
            $pseudo = trim($_POST['pseudo']);
            $mdp = $_POST['mdp'];
            
            try {
                // Connexion sécurisée à la base
                $mysqli = new mysqli($host, $username, $password, $dbname);
                $mysqli->set_charset("utf8mb4");
                
                // Requête préparée avec statement
                $stmt = $mysqli->prepare("
                SELECT id, username, password, role, last_login, controle_en_cours, facture_en_saisie, dev 
                FROM utilisateur 
                WHERE username = ? 
                AND is_active = 1
                LIMIT 1
            ");
                $stmt->bind_param("s", $pseudo);
                $stmt->execute();
                
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // Vérification du mot de passe (password_verify pour les hash)
                    if ($mdp == $user['password']) {
                        // Authentification réussie
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['pseudo'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['last_login'] = time();
                        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                        $_SESSION['controle_en_cours'] = $user['controle_en_cours'];
                        $_SESSION['facture_en_saisie'] = $user['facture_en_saisie'];   
                        $_SESSION['dev'] = $user['dev'];                   
                        // Mise à jour du last_login en base
                        $update_stmt = $mysqli->prepare("
                        UPDATE utilisateur 
                        SET last_login = NOW() 
                        WHERE id = ?
                    ");
                        $update_stmt->bind_param("i", $user['id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        // Régénération de l'ID de session
                        session_regenerate_id(true);
                        
                        // Redirection sécurisée
                        header('Location: index.php');
                        exit;
                    }
                }
                
                // Message d'erreur générique pour éviter l'enumération
                $error_message = "Identifiants incorrects";
                usleep(random_int(1000000, 3000000)); // Délai aléatoire
                
                $stmt->close();
                $mysqli->close();
                $cookie_options = [
                    'expires' => time() + 86400,
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ];
                setcookie('dev', $_SESSION['dev'], $cookie_options);
                
            } catch (mysqli_sql_exception $e) {
                error_log("Login error: ".$e->getMessage());
                $error_message = "Erreur système. Veuillez réessayer.";
            }
        }
    }
    //var_dump($_SESSION);
    // Affichage HTML
    
dev($_SESSION);?>
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
        
        <div class="login-container">
            <?php if (!$isLoggedIn): ?>
            <h3 style="text-align: left;">Connexion à l'espace gestion</h3>
            <p style="text-align: left;">Accès réservé aux membres habilités du club.</p>
            
            <?php if (!empty($error_message)): ?>
            <div class="error"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            
            <form action="login.php" method="post">
                <div>
                    <label for="pseudo">Identifiant :</label>
                    <input type="text" id="pseudo" name="pseudo" required autofocus>
                </div>
                
                <div>
                    <label for="mdp">Mot de passe :</label>
                    <input type="password" id="mdp" name="mdp" required>
                </div>
                
                <div style="text-align: left; margin-top: 20px;">
                    <input class="btn btn-primary" type="submit" name="connexion" value="Se connecter">
                </div>
            </form>
            
            <div style="margin-top: 20px; text-align: left;">
                <p>Pour obtenir un accès, contactez <a href="mailto:contact@perigord-escalade.fr">l'administrateur</a>.</p>
            </div>
            <?php else: ?>
            <div style="text-align: left;">
                <p>Vous êtes déjà connecté.</p>
                <p><a href="index.php">Accéder à l'interface</a></p>
            </div>
            <?php endif; ?>
        </div>
        <div style="text-align: left;">
        <form>            
            <a href="index.php">
                <input type="button" class="btn btn-secondary btn-block"value="Revenir à l'accueil">
            </a>
        </form>
        </div>
    </body>
    <footer>
        <?php include $root . 'includes/bandeau_bas.php'; ?>
    </footer>
</html>