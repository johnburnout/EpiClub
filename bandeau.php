<?php
    if ($isLoggedIn) { 
        $select = [];
        if ($_SESSION['dev']) {
            $cookie_options = [
                'expires' => time() + 86400,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ];
            
            if (!isset($_COOKIE['dev'])) {
                setcookie('dev', '1', $cookie_options);
            }
            
            $select[] = '<form method="post">';
            $select[] = '<input type="hidden" name="dev" value="'.($_COOKIE['dev'] == "1" ? "0" : "1").'">';
            $select[] = '<input type="submit" name="but" class="btn btn-secondary" value="'.($_COOKIE['dev'] == "1" ? "Utilisation" : "Développement").'">';
            $select[] = '</form>';
            $choix_select = implode('', $select);
            
            if (isset($_POST['but'])) {
                $new_value = $_POST['dev'] ?? '1';
                setcookie('dev', $new_value, $cookie_options);
                header("Refresh:0"); // Recharge la page pour prendre en compte le nouveau cookie
                exit;
            }
        }
    }
?>

<?php if ($isLoggedIn): ?>
<form action="index.php" method="post" style="display: inline;">
    <?= $connect ?? '' ?>
    <button type="submit" name="deconnexion" class="btn btn-link">Déconnexion</button>
</form>
<?= $choix_select ?? '' ?>
<?php else: ?>
<a href="login.php" class="btn btn-primary">Connexion</a>
<?php endif; ?>