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
    $sql = "INSERT INTO facture (vendeur, date_facture, reference) VALUES ( '".$_POST['vendeur']."','".$_POST['date']."','".$_POST['reference']."' ) ";

  $statement = $connection->query($sql);
  echo "La facture a bien été déclarée. Vous pouvez revenir à l'accueil.";
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
  <p>ATTENTION : Après avoir cliqué sur le bouton "Créer la facture", toute modification de celle-ci n'est pour l'instant possible que par l'administrateur de la base de données <a href="mailto:jean@roussie.net">Jean</a>.</p> 
<form method='post'>
    <table>
    <tbody>
        <tr>
          <th>Boutique</th>
          <td>           
            <input type="text" name="vendeur">
          </td>
          <td>Date</td>
          <td><input type="date" name="date"></td>
          <td>Réference</td>
          <td ><input type="text" name="reference"></td>
        </tr>
        </tbody>
    </table>
  <p></p>
  <input type="submit" name="envoyer" value="Créer la facture">
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