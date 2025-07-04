<?php
session_start();
# #############################
# Récupère les données communes
# #############################

  
require "config.php";  
include $root."includes/header.php";
include $root."includes/common.php";;


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

$connection = new mysqli($host, $username, $password, $dbname);

  # #############################
  # initialisation des listes 
  # #############################
  
  # Récupération des lieux
  
  $statement = $connection->query("SELECT * FROM utilisateur ORDER BY id");
  $nUtil = $statement->num_rows;
  $utilisateurs = $statement->fetch_all(MYSQLI_ASSOC);

  # #############################
  # Création des listes 
  # #############################
  
  $listeUtilisateurs = "";
  
  for ($i = 0; $i < $nUtil; $i++)
    {
      $listeUtilisateurs .="<option value= '".$utilisateurs[$i]["id"]."'>".$utilisateurs[$i]["username"]." </option>";
      
    };

# #############################
# Initialisation variables 
# #############################

if (isset($_POST['id'])) {$id = $_POST['id'];}
else {$id = 0; };


# #############################
# Envoi des données à la BDD 
# #############################

if (isset($_POST['envoyer'])) {
  try {
    $sql = "INSERT INTO verification ( utilisateur_id, remarques)
      VALUES ( '".$_POST['utilisateur_id']."','".$_POST['remarques']."')";

  $statement = $connection->query($sql);
  echo "La fiche de vérification a bien été crée. Vous pouvez revenir à l'accueil.";
  } catch(Exception $error) {
    echo $sql . "<br>" . $error->getMessage();
  }
}  
  
$result = NULL;
?>

<!--# #############################-->
<!--# Code HTML-->
<!--# #############################-->
<body><p>
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

  <!-- Test si connecté debut -->
  <?php if (count($_SESSION) > 0)
    { 
  ?>
  <!---->
  
<h3>Créer une Vérification</h3>

<!--Formulaire de saisie -->
  <p>ATTENTION : Après avoir cliqué sur le bouton "Créer la vérification", toute modification de celle-ci n'est pour l'instant possible que par l'administrateur de la base de données <a href="mailto:jean@roussie.net">Jean</a>.</p> 
<form method='post'>
    <table>
    <tbody>
        <tr>
          <th>Utilisateur</th>
          <td>           
            <select name='utilisateur_id'>
              <?php echo $listeUtilisateurs ?>
            </select></td>
          <td>Date</td>
          <td><?php echo date('d-m-Y');?></td>
        </tr>
        <tr>
          <th colspan =4 >remarques</th>
        </tr>
        <tr>
          <td colspan = 4><textarea name="remarques" cols = 100 rows=5 ></textarea> </td>
        </tr>
        </tbody>
    </table>
  <p></p>
  <input type="submit" name="envoyer" value="Créer la vérification">
</form>

<!--Pied de page-->

<hr>
<p></p>

<p>
  <a href="index.php"><strong>Retour à l'accueil</strong></a> - Revenir à l'accueil
</p>

<!---->
  <!-- Test si connecté fin -->
  <?php 
    }
    else { ?>
  <p>Tu n'es pas connecté</p>
  <?php 
  }?>
  <!---->
<?php require $root."includes/footer.php"; ?>