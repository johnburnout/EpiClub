<?php
session_start();

include "header.php";

if (isset($_POST['deconnexion'])) {$_SESSION=array();};

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
  
  $debut = 0 ; $long = 20; $nblignes = 0;
  if (isset($_POST['debut'])) {$debut = $_POST['debut']-1;};
  if (isset($_POST['long'])) {$long = $_POST['long'];};
  if (isset($_POST['nblignes'])) {$nblignes = $_POST['nblignes'];};

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
  
<h3>Gestionnaire (minimaliste) des EPI</h3>
  <p></p>
    <table>
      <tr>
        <td>
        <form action="fiche_creation.php" method="post">
          <input type="hidden" name='appel_liste' value=0>
          <input type="hidden" name='id' value='0'>  
          <input type="hidden" name='action' value='creation'>
          <input type="submit" name='submit' value="Créer une nouvelle fiche">
          <input type="hidden" name='debut' value=<?php echo $debut; ?>>
          <input type="hidden" name='long' value=<?php echo $long; ?>>
          <input type="hidden" name='nblignes' value=<?php echo $nblignes; ?>>
      </form>
    </td>
        <td> 
          <form action="liste_selection.php" method="post">
            <input type="submit" name='submit' value="Consulter la liste des EPI">
            <input type="hidden" name='debut' value=<?php echo $debut; ?>>
            <input type="hidden" name='long' value=<?php echo $long; ?>>
            <input type="hidden" name='nblignes' value=<?php echo $nblignes; ?>>
        </form>
      </td>
      </tr>
      <tr>
      <td> 
          <form action="verification_creer.php" method="post">
            <input type="hidden" name='action' value='creation'>
            <input type="submit" name='submit' value="Créer une vérification">
            <input type="hidden" name='debut' value=<?php echo $debut; ?>>
            <input type="hidden" name='long' value=<?php echo $long; ?>>
            <input type="hidden" name='nblignes' value=<?php echo $nblignes; ?>>
          </form>
      </td>
      <td>
        <form action="facture_creation.php" method="post">
          <input type="hidden" name='action' value='creation'>
          <input type="submit" name='submit' value="Créer une facture">
          <input type="hidden" name='debut' value=<?php echo $debut; ?>>
          <input type="hidden" name='long' value=<?php echo $long; ?>>
          <input type="hidden" name='nblignes' value=<?php echo $nblignes; ?>>
        </form>
      </td>
    </tr>

<p></p>
<p></p>
<p></p>
<p></p>
<!-- Test si connecté fin -->
<?php 
}
  else { ?>
  <p>Tu n'es pas connecté</p>
  <?php 
  }?>
<!---->
<?php include "footer.php"; ?>