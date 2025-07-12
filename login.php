<?php
// Configuration sécurisée des sessions
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

// Inclusion des fichiers de configuration avec vérification
defined('ROOT') or define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require __DIR__ . '/config.php';
require ROOT . 'includes/common.php';

// Initialisation des variables
$connect = "Déconnecté";
$error_message = "";

// Traitement de la déconnexion
if (isset($_POST['deconnexion'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

// Vérification de la connexion existante
if (isset($_SESSION['pseudo'])) {
    $connect = "Connecté comme ".htmlspecialchars($_SESSION['pseudo'], ENT_QUOTES, 'UTF-8')." : ";
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
            // Connexion sécurisée à la base de données
            $mysqli = new mysqli($host, $username, $password, $dbname);
            $mysqli->set_charset("utf8mb4");
            
            // Requête préparée pour éviter les injections SQL
            $stmt = $mysqli->prepare("SELECT id, username, password FROM utilisateur WHERE username = ?");
            $stmt->bind_param("s", $pseudo);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Vérification du mot de passe (suppose que le mot de passe est haché dans la base)
                if (password_verify($mdp, $user['password'])) {
                    // Authentification réussie
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['pseudo'] = $user['username'];
                    $_SESSION['last_login'] = time();
                    
                    // Régénération de l'ID de session pour prévenir les attaques par fixation
                    session_regenerate_id(true);
                    
                    header('Location: index.php');
                    exit;
                } else {
                    $error_message = "Identifiants incorrects";
                }
            } else {
                $error_message = "Identifiants incorrects";
            }
            
            $stmt->close();
            $mysqli->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Erreur de connexion: ".$e->getMessage());
            $error_message = "Erreur système. Veuillez réessayer plus tard.";
        }
    }
}
?>

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
    <header style="text-align: right;">
        <?php if (isset($_SESSION['pseudo'])): ?>
            <form action="login.php" method="post" style="display: inline;">
                <?php echo $connect; ?>
                <input type="submit" name="deconnexion" value="Déconnexion">
            </form>
        <?php else: ?>
            <a href="login.php">Connexion</a>
        <?php endif; ?>
    </header>
    <hr>

    <div style="text-align: center;">
        <h1>Gestionnaire EPI</h1>
        <h2>Périgord Escalade</h2>
        <img src="images/logo.png" width="200" alt="Logo Périgord Escalade">
    </div>
    <hr>

    <?php if (!isset($_SESSION['pseudo'])): ?>
        <div style="max-width: 500px; margin: 0 auto;">
            <h3>Se connecter</h3>
            <p>Logiciel accessible aux gestionnaires des EPI et encadrants du club 
                <a href="https://perigord-escalade.fr" target="_blank" rel="noopener noreferrer">Périgord Escalade</a>
            </p>
            <p>Pour un accès, demander à <a href="mailto:jean@roussie.net">Jean Roussie</a>.</p>
            
            <?php if (!empty($error_message)): ?>
                <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <form action="login.php" method="post">
                <table style="margin: 0 auto;">
                    <tr>
                        <td>Pseudo :</td>
                        <td><input type="text" name="pseudo" required></td>
                    </tr>
                    <tr>
                        <td>Mot de passe :</td>
                        <td><input type="password" name="mdp" required></td>
                    </tr>
                </table>
                <p><input type="submit" name="connexion" value="Connexion"></p>
            </form>
        </div>
    <?php else: ?>
        <p>Vous êtes déjà connecté. <a href="index.php">Accéder à l'interface</a></p>
    <?php endif; ?>

    <p style="text-align: center; margin-top: 30px;">
        <a href="index.php"><strong>Retour à l'accueil</strong></a>
    </p>

    <?php require "footer.php"; ?>
</body>
</html>