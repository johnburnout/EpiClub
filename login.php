<?php
session_start();

require "header.php";
require "config.php";  
require "common.php";

  
  # #############################
  # verification connexion
  # #############################

  
  if (count($_SESSION) > 0)	
    {
      $connect = "Connecté comme ".$_SESSION['pseudo']." : ";
    }
  else
    {
      $connect = "Déconnecté";
    }

  # #############################
  # Création de la connection à la base
  # #############################


  
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/*
Page: connexion.php
*/
//à mettre tout en haut du fichier .php, cette fonction propre à PHP servira à maintenir la $_SESSION

//si le bouton "Connexion" est cliqué
if(isset($_POST['connexion'])){
  // on vérifie que le champ "Pseudo" n'est pas vide
  // empty vérifie à la fois si le champ est vide et si le champ existe belle et bien (is set)
  if(empty($_POST['pseudo'])){
    echo "Le champ Pseudo est vide.";
  } else {
    // on vérifie maintenant si le champ "Mot de passe" n'est pas vide"
    if(empty($_POST['mdp'])){
      echo "Le champ Mot de passe est vide.";
    } else {
      
      // les champs pseudo & mdp sont bien postés et pas vides, on sécurise les données entrées par l'utilisateur
      //le htmlentities() passera les guillemets en entités HTML, ce qui empêchera en partie, les injections SQL
      $Pseudo = htmlentities($_POST['pseudo'], ENT_QUOTES, "UTF-8"); 
      $MotDePasse = htmlentities($_POST['mdp'], ENT_QUOTES, "UTF-8");
      //on se connecte à la base de données:
      $mysqli = mysqli_connect($host, $username, $password, $dbname);
      //on vérifie que la connexion s'effectue correctement:
      if(!$mysqli){
        echo "Erreur de connexion à la base de données.";
      } else {
        //on fait maintenant la requête dans la base de données pour rechercher si ces données existent et correspondent:
        //si vous avez enregistré le mot de passe en md5() il vous faudra faire la vérification en mettant mdp = '".md5($MotDePasse)."' au lieu de mdp = '".$MotDePasse."'
        $Requete = mysqli_query($mysqli,"SELECT * FROM utilisateur WHERE username = '".$Pseudo."' AND password = '".$MotDePasse."'");
        //si il y a un résultat, mysqli_num_rows() nous donnera alors 1
        //si mysqli_num_rows() retourne 0 c'est qu'il a trouvé aucun résultat
        if(mysqli_num_rows($Requete) == 0) {
          echo "Le pseudo ou le mot de passe est incorrect, le compte n'a pas été trouvé.";
        } else {
          //on ouvre la session avec $_SESSION:
          //la session peut être appelée différemment et son contenu aussi peut être autre chose que le pseudo
          $_SESSION['pseudo'] = $Pseudo;
          echo "Vous êtes à présent connecté !";
        }
      }
    }
  }
}
?>

<!-- 
Les balises <form> servent à dire que c'est un formulaire
on lui demande de faire fonctionner la page connexion.php une fois le bouton "Connexion" cliqué
on lui dit également que c'est un formulaire de type "POST" (récupéré via $_POST en PHP)
Les balises <input> sont les champs de formulaire
type="text" sera du texte
type="password" sera des petits points noir (texte caché)
type="submit" sera un bouton pour valider le formulaire
name="nom de l'input" sert à le reconnaitre une fois le bouton submit cliqué, pour le code PHP (récupéré via $_POST["nom de l'input"] en PHP)
-->

<body><p align="right">
  <?php 
    if (count($_SESSION) > 0)
  { ?>
  <form action="index.php" method="post"><?php echo $connect." "; ?><input type='submit' name="deconnexion" value="Déconnexion"></form><?php  
    }
    else 
  { ?>
  <a href=login.php>Connection</a><?php
    }
  ?></p>
  <hr>
  <table>
    <tr>
      <td> <h1>Gestionnaire EPI</h1></td><td rowspan=2><img src="images/logo.png" width="200"></td>
    </tr>
    <tr><td><h2>Périgord Escalade</h2></td></tr>
  </table>
<hr>  
<?php

if (count($_SESSION) == 0)
{ ?>
  
<h3>Se connecter</h3>
<p>Logiciel accessible aux gestionnaires des EPI et encadrants du club 
  <a href="https://perigord-escalade.fr" target="blank">Périgord Escalade</a></p>
<p>Pour un accès, demander à <a href="mailto:jean@roussie.net">Jean Roussie</a>.</p>
<form action="login.php" method="post">
  <table>
    <tr><td>Pseudo : </td><td>  <input type="text" name="pseudo" /></td></tr>
      <tr><td>Mot de passe : </td><td><input type="password" name="mdp" /></td> </tr>
  </table>
  <p><input type="submit" name="connexion" value="Connexion" /></p>
</form>
<?php
};
?>

<p>
  <a href="index.php"><strong>Retour à l'accueil</strong></a> - Revenir à l'accueil
</p>

<?php require "footer.php"; ?>        # Créé par Jean Roussie Périgord Escalade : jean@grimpe.fr