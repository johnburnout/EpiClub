<?php
  session_start();
  # #############################
  # Récupère les données communes
  # #############################
  
  require "config.php";
  include $root."includes/header.php";  include $root."includes/common.php";;
  include('includes/init_donnees.php');
  
  # #############################
  # verification connexion
  # #############################
  
  #
#var_dump($_POST); # <------------------------- VERIF
  #  echo "<br>\$_SESSION :"; var_dump($_SESSION)."<br>"; # <------------------------- VERIF
  #
  
  if (count($_SESSION) > 0)	
    {
      $connect = "Connecté comme ".$_SESSION['pseudo']." : ";
    }
  else
    {
      $connect = "Déconnecté";
    }
  
  ##############################
  # INITIALISATION VARIABLES
  #############################
  
  # #############################
  # Création de la connection à la base
  # #############################
  $id = 0;
  if (isset($_POST['id'])) {$id = $_POST['id'];};
  
  if ($id == 0) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    $connection = new mysqli($host, $username, $password, $dbname);
    
    # #############################
    # initialisation des listes 
    # #############################
    
    # Récupération des lieux
    
    $statement = $connection->query("SELECT MAX(id) FROM matos");
    $idmax = $statement->fetch_all(MYSQLI_ASSOC);
  #var_dump($idmax); # <------------------------- VERIF
  #print_r($idmax[0]['MAX(id)']);
  $id = $idmax[0]['MAX(id)'];
    if (($id == 0) or (isset($_POST['supprimer']))) {
      $statement = $connection->query("DELETE FROM matos WHERE id = ".$id);
      if ($id == 0) {
        $statement = $connection->query("ALTER TABLE matos AUTO_INCREMENT = ".$id);};
      #
    };
  };
  #
  $bouton='';
  if ($id == 0) {$bouton = '';}; $avis='';
  if (($id > 0) and (isset($_POST['supprimer'])))
    { $bouton = "  <a href='liste_selection.php'><input type='button' value='Abandonner' ></a>";};
    {$avis = "Attention ! Voulez-vous vraiment effacer le fiche ?";}
  if (isset($_POST['confirmer']))
    {"La fiche a été supprimée, cliquer sur le bouton 'Abandonner' pour sortir." ;};  
?>
<body>
  <p>    
    <?php 
      if (count($_SESSION) > 0)
    { ?>
    <form action="index.php" method="post"><?php echo $connect." "; ?><input type='submit' name="deconnexion" value="Déconnexion"></form><?php  
      }
      else 
    { ?>
    <a href=login.php>Connection</a><?php
      }
    ?>
  </p>
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
  <p><?php echo $avis ?></p>
  
  <form  enctype="multipart/form-data" method='post' action="fiche_effacer.php">
    <table>
      <tbody>
      </tbody>
      <tfoot>
        <tr><td><?php echo $bouton?>
            <input type="submit" name="supprimer" value="Confirmer">
          </td>
        </tr>
      </tfoot>
    </table>
  </form>
  <!-- Test si connecté fin et pied de page -->
  <?php }
    else { ?>
  <p>Tu n'es pas connecté</p>
  <?php 
    };
  ?>
  <?php require $root."includes/footer.php"; ?>