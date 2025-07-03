<?php
session_start();
# #############################
# Récupère les données communes
# #############################

include "header.php";
require "config.php";  
require "common.php"; 
include('init_donnees.php');

# #############################
# verification connexion
# #############################
  
#
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
  
  $debut = 0 ; $long = 20; $nblignes = 0;
  if (isset($_POST['debut'])) {$debut = $_POST['debut']-1;};
  if (isset($_POST['long'])) {$long = $_POST['long'];};
  if (isset($_POST['nblignes'])) {$nblignes = $_POST['nblignes'];};
  
  
##############################
# CREATION
#############################
  
  #
  #echo "<br>\$POST :"; var_dump($_POST); echo "<br>"; #<-------------------------
  
#echo "<br>\$action : "; var_dump($action)."<br>"; # <------------------------- VERIF

  #
  #echo "<br>\$_POST[appel_liste] : "; var_dump($_POST['appel_liste'])."<br>"; # <------------------------- VERIF
  #echo "<br>\$donnees : avant mise à jour "; var_dump($donnees)."<br>"; # <------------------------- VERIF
  #

if (isset($_POST['action'])) {$action =$_POST['action'];};
if (isset($_POST['id'])) {$id = $_POST['id'];}
  else {$id = 0;};

#
#  echo "<br>\$action : "; var_dump($action); echo "<br>"; # <------------------------- VERIF
#

# #############################
# Création de la connection à la base
# #############################

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$connection = new mysqli($host, $username, $password, $dbname);

# #############################
# initialisation des listes 
# #############################

# Récupération des lieux

$statement = $connection->query("SELECT * FROM lieu ORDER BY id");
$nLieux = $statement->num_rows;
$lieux = $statement->fetch_all(MYSQLI_ASSOC);

# Récupération des categories
$statement = $connection->query("SELECT * FROM categorie ORDER BY id");
$nCategorie = $statement->num_rows;
$categories = $statement->fetch_all(MYSQLI_ASSOC);

#  Récupération des lieux
$statement = $connection->query("SELECT * FROM fabricant ORDER BY id");
$nFab = $statement->num_rows;
$fabricants = $statement->fetch_all(MYSQLI_ASSOC);

# Récupération des factures
$statement = $connection->query("SELECT * FROM facture ORDER BY id");
$nFacture = $statement->num_rows;
$factures = $statement->fetch_all(MYSQLI_ASSOC);
  
# Récupération des utilisateurs
$statement = $connection->query("SELECT * FROM utilisateur ORDER BY id");
$nUtilisateurs = $statement->num_rows;
$utilisateurs = $statement->fetch_all(MYSQLI_ASSOC);

  #
  #echo "<br>\$nb elements  initial : ".$nbInitial."<br>"; # <------------------------- VERIF

  #

#############################
# Récupération des données sur la base
############################
  #
  #     echo "\$id avant update : ".$id."<br>"; #<-------------------------
  #
#if ($action <> 'creation') {
$result = null;

try {
  $connection = new mysqli($host, $username, $password, $dbname);
  
  $sql = "SELECT
    ref AS reference,
    libelle,
    categorie,
    categorie_id,
    fabricant,
    fabricant_id,
    lieu,
    lieu_id,
    vendeur,
    facture_id,
    date_facture,
    username,
    utilisateur_id,
    nb_elements,
    nb_elements_initial,
    date_max,
    date_debut,
    verification_id,
    date_verification,
    remarques,
    photo
  FROM fiche
  WHERE id = ".$id." ;";
  
  $statement = $connection->query($sql);
  
  $result = $statement->fetch_all(MYSQLI_ASSOC);
  
  #
  #echo "\$result : "; var_dump($result); #<-------------------- VERIF $result
  #
  
  $donnees = $result[0];
  if ($action == 'verif') {  
    $donnees['reference'] = '';};
}
  catch(Exception $error) {
  echo $sql . "<br>" . $error->getMessage();
};

  # echo "<br>\$donnees après mise à jour: "; var_dump($donnees)."<br>"; # <------------------------- VERIF
  
foreach ($_POST as $cle => $valeur) {$donnees[$cle] = $_POST[$cle];};
    
  try {
    $sql = "UPDATE matos SET
        libelle = '".escape($donnees['libelle'])."',
        categorie_id = '".$donnees['categorie_id']."',
        photo = '".$donnees['photo']."',
        lieu_id = '".$donnees['lieu_id']."',
        nb_elements = '".$donnees['nb_elements']."',
        facture_id = '".$donnees['facture_id']."',
        remarques = '".escape($donnees['remarques'])."'
      WHERE
        id = ".$id.";";
    $statement = $connection->query($sql);}
  catch(Exception $error)
  {
    echo $sql . "<br>" . $error->getMessage();
  };
  
#};

  #
#echo "<br>\$donnees :"; var_dump($donnees)."<br>"; #<----------------------VERIF
  #  
  
  #
  #echo "<br>\$factures : "; var_dump($factures)."<br>"; # <------------------------- VERIF
  #    
# #############################
# Création des listes d'options
# #############################
  
######################
#echo "<br>.id : ".$id." \$factures : "; var_dump($factures);  # <--------------------------VERIF $lieux
#########################
  
if (isset($lieux[$donnees['lieu_id']-1]['id'])) {$lieux[$donnees['lieu_id']-1]['id'] .=" selected";};
$listeLieux = "";
for ($i = 0; $i < $nLieux; $i++)
  {
    $listeLieux .="<option value= ".$lieux[$i]["id"].">".$lieux[$i]["libelle"]." </option>";
  };

if (isset($categories[$donnees['categorie_id']-1]['id'])) {$categories[$donnees['categorie_id']-1]['id'] .=" selected";};
$listeCategories = "";
  
for ($i = 0; $i < $nCategorie; $i++)
  {
    $listeCategories = $listeCategories."<option value= ".$categories[$i]["id"].">".$categories[$i]["libelle"]." </option>";
  };

if (isset($fabricants[$donnees['fabricant_id']-1]['id'])) {$fabricants[$donnees['fabricant_id']-1]['id'] .=" selected";  };
$listeFabricants = "";
  
for ($i = 0; $i < $nFab; $i++)
  {
    $listeFabricants = $listeFabricants."<option value= ".$fabricants[$i]["id"].">".$fabricants[$i]["libelle"]." </option>";
  };

if (isset($factures[$donnees['facture_id']-1]['id'])) {$factures[$donnees['facture_id']-1]['id'].=" selected"; }; 
$listeFactures = "";

for ($i = 1; $i < $nFacture+1; $i++)
  {
    $listeFactures = $listeFactures ."<option value= ".$factures[$i-1]["id"].">".$factures[$i-1]["vendeur"]." ".$factures[$i-1]["date_facture"]."</option>";      
  };
  
  
  if ($action == 'maj') {
    $liste = [
      'lieu_id'=>"<select name='lieu_id'>".$listeLieux."</select>",
      'categorie_id'=>$donnees['categorie'],
      'fabricant_id'=>$donnees['fabricant'],
      'facture_id'=>$donnees['vendeur']." (".$donnees['date_facture'].")",
      "milieutexte"=>"",
      'fintexte'=>"",
      'finnum'=>"",
      'required'=>'',
      'datemax'=>$donnees['date_max'],
      'libelle'=>$donnees['libelle'],
      'date_debut'=>$donnees['date_debut']
    ];}
  else {
    $liste = [
      'lieu_id'=>"<select name='lieu_id'>".$listeLieux."</select>",
      'categorie_id'=>"<select name='categorie_id'>".$listeCategories."</select>",
      'fabricant_id'=>"<select name='fabricant_id'>".$listeFabricants."</select>",
      'facture_id'=>"<select name='facture_id'>".$listeFactures."</select>",
      "milieutexte"=>"' ",
      'fintexte'=>"'>",
      'finnum'=>">",
      'required'=>' required ',
      'datemax'=>$donnees['date_max'],
      'libelle'=>"<input name='libelle' type='text' required values=".$donnees['libelle'].">",
      'date_debut'=>"<input name='date_debut' type='date' values=".$donnees['date_debut'].">"
    ];
  }

  #
  #echo "<br>\$lieux :"; var_dump($lieux)."<br>"; #<----------------------VERIF
  #  
//echo "<br>\$listeLieux : "; var_dump($listeLieux); echo "<br>"; # <------------------------- VERIF
//echo "<br>\$listeCategories : "; var_dump($listeCategories); echo "<br>"; # <------------------------- VERIF
//echo "<br>\$listeFabricants : "; var_dump($listeFabricants); echo "<br>"; # <------------------------- VERIF
#echo "<br>\$liste['factures_td] : "; var_dump($liste['facture_id']); echo "<br>"; # <------------------------- VERIF

  # #############################
  # Envoi de fichier
  # #############################
  
  #
  #echo "<br>\FILES :"; var_dump($_FILES)."<br>"; #<----------------------VERIF
  #
  if (count($_FILES)>0){        
    $nomOrigine = $_FILES['monfichier']['name'];
    $elementsChemin = pathinfo($nomOrigine);
    
    #
    #        echo "<br>\$elementsChemin :"; var_dump($elementsChemin)."<br>";  #<----------------------VERIF
    #
    if ($elementsChemin["filename"]) {$extensionFichier = $elementsChemin['extension'];};
    $extensionsAutorisees = array("jpeg", "jpg", "gif","png");
    
    #    
    #    echo "<br>\$extensionFichier :"; var_dump($extensionFichier)."<br>"; #<----------------------VERIF
    #            
    
    if(isset($extensionFichier))
      {      
        if (!(in_array($extensionFichier, $extensionsAutorisees)))
          {
            echo "Le fichier n'a pas l'extension attendue";
          }
        else
          {    
            // Copie dans le repertoire du script avec un nom comprenant la date
            $repertoireDestination = dirname(__FILE__)."/images/";
            $nomDestination = $donnees["reference"].date("Ymd").".".$extensionFichier;
            
            if (move_uploaded_file($_FILES["monfichier"]["tmp_name"], 
              $repertoireDestination.$nomDestination))
              {
                echo "<hr>Le fichier temporaire ".$_FILES["monfichier"]["tmp_name"].
                " a été déplacé vers ".$repertoireDestination.$nomDestination."<hr>";
              }
            else
              {
                echo "<hr>ATTENTION : Le fichier n'a pas été uploadé (trop gros ?) ou ".
                "Le déplacement du fichier temporaire a échoué".
                " vérifiez l'existence du répertoire ".$repertoireDestination."<hr>";
              }
          };
      };
    
    
    
    if (isset($nomDestination))
      {
        try {
          $connection = new mysqli($host, $username, $password, $dbname);
          
          $sql = "UPDATE matos SET 
          photo = '".$nomDestination."' WHERE id = ".$id." ;";
          
          $statement = $connection->query($sql);
          
          $connection->close();
          
        } catch(Exception $error) {
          echo $sql . "<br>" . $error->getMessage();
        }
      };
  };
  
  #
  #echo "<br>\$DESTINATION :"; var_dump($nomDestination)."<br>";  #<----------------------VERIF
  #
  
  if(isset($nomDestination))  
    {
      if ($nomDestination<>'')
        {
          $donnees['photo'] = $nomDestination;
        };
    };
  
  #
  #echo "<br>\$action :"; var_dump($action)."<br>";  #<----------------------VERIF
  #  
  
//if (count($_SESSION) > 0)
//{ ?>

<!--# #############################-->
<!--# Code HTML-->
<!--# #############################-->

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
  
  <form  enctype="multipart/form-data" method='post' action="fiche_verif.php">
    <table>
      <tbody>
        <tr>
          <th>Référence</th>
          <td><?php echo $donnees['reference']?></td><td>Photo</td>
          <td colspan=2><input name="photo" value=<?php echo $donnees['photo']?>></td>
        </tr>
        <tr>
          <th>Libelle</th>
          <td><?php echo "<input name='libelle' type='text' required value=".$donnees['libelle'].">"?></td>
          <td rowspan = 7 colspan=2><img src = <?php echo "images/".$donnees['photo']?> width='400'></td>
        </tr>
        <tr>
          <th>Lieu</th>
          <td><?php echo $liste['lieu_id']?></td>
        </tr>
        <tr>
          <th>Categorie</th>
          <td><?php echo $liste['categorie_id'] ?></td>
        </tr>
        <tr>
          <th>Date mise en service</th>
          <td><?php echo $donnees['date_debut'] ?></td>
        </tr>
        <tr>
          <th>Fabricant</th>
          <td><?php echo $liste['fabricant_id']?></td>
        </tr>
        <tr>
          <th>Nb elements</th>
          <td><input type="number" name="nb_elements" value=<?php echo $donnees['nb_elements']?> min=0 max=<?php echo $donnees['nb_elements_initial']?>></td>
        </tr>
        <tr>
          <th>Facture</th>
        <td><?php echo $liste['facture_id']?></td>
        </tr>
        <tr><th colspan =4 >remarques</th></tr>
        <tr>
          <td colspan = 4><textarea name="remarques" cols = 100 rows=5 ></textarea> </td>
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <td><input type="hidden" name="MAX_FILE_SIZE" value="2000000" />  Envoyer une image : </td>
          <td colspan=1><input type="file" name="monfichier" /></td>
          <td>
            <input type="hidden" name="id" value='<?php echo $id?>'>
            <input type='hidden' name='appel_liste' value=0>
            <input type="hidden" name="action" value='maj'>
            <input type="hidden" name='debut' value=<?php echo $debut; ?>>
            <input type="hidden" name='long' value=<?php echo $long; ?>>
            <input type="hidden" name='nblignes' value=<?php echo $nblignes; ?>>
        </td>
          <td align='right'>
            <input type="submit" name="envoyer" value="Mise à jour de la fiche">
            <?php if ($action == 'maj') { 
              echo "<a href='liste_selection.php'>
              <input type='button' value='Abandonner' >
            </a> ";};?></td></tr>
      </tfoot>
    </table>
  </form>
  <p><form method='post' action='fiche_effacer.php'>
    <input type="hidden" value=<?php echo $id?> name='id'>
    <input type="submit" value='Supprimer la fiche' name='supprimer'>            
  </form></p>
  <!-- Test si connecté fin et pied de page -->
  <?php }
    else { ?>
  <p>Tu n'es pas connecté</p>
  <?php 
    };
    ?>
<?php
require "footer.php"; ?>        # Créé par Jean Roussie Périgord Escalade : jean@grimpe.fr