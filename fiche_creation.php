<?php
  session_start();
  # #############################
  # Récupère les données communes
  # #############################
  
  require "config.php";
  include $root."includes/header.php";
  include $root."includes/common.php";;
  include('includes/init_donnees.php');
  
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
  
  #echo "<br>\$POST :"; var_dump($_POST); echo "<br>"; #<-------------------------
  
  $action = '';
  if(isset($_POST['action'])) {$action = $_POST['action'];};
  
  #echo var_dump($action); # <------------------------- VERIF
  
  if (isset($_POST['action'])) {$action =$_POST['action'];} else {$action ='creation';};
  if (isset($_POST['id'])) {$id = $_POST['id'];}
  else {$id = 0;};
  
  if ($action == 'creation') {$donnees = init_donnees();} ;
  
  #echo "<br>\$_POST[appel_liste] : "; var_dump($_POST['appel_liste'])."<br>"; # <------------------------- VERIF
  #echo var_dump($donnees); # <------------------------- VERIF
  
  #  echo "<br>\$action : "; var_dump($action); echo "<br>"; # <------------------------- VERIF
  
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
  
  if ($action == 'creation') {
    try {
      $sql = "INSERT INTO matos (
        reference,
        libelle,
        categorie_id,
        fabricant_id,
        photo,
        lieu_id,
        nb_elements_initial,
        nb_elements,
        facture_id,
        date_debut,
        remarques)
      VALUES (
      '".$donnees['reference']."',
  '".escape($donnees['libelle'])."',
  '".$donnees['categorie_id']."',
  '".$donnees['fabricant_id']."',
  '".$donnees['photo']."',
  '".$donnees['lieu_id']."',
  '".$donnees['nb_elements_initial']."',
  '".$donnees['nb_elements']."',
  '".$donnees['facture_id']."',
  '".$donnees['date_debut']."',
  '".escape($donnees['remarques'])."'
    )";
      
      $statement = $connection->query($sql);
      $id = $connection->insert_id;
      $action = 'verif';
    }
    catch(Exception $error)
    {
      echo $sql . "<br>" . $error->getMessage();
    }
  };
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
    reference = '".$donnees['reference']."',
        libelle = '".escape($donnees['libelle'])."',
        categorie_id = '".$donnees['categorie_id']."',
        fabricant_id = '".$donnees['fabricant_id']."',
        photo = '".$donnees['photo']."',
        lieu_id = '".$donnees['lieu_id']."',
        date_debut = '".$donnees['date_debut']."',
        nb_elements_initial = '".$donnees['nb_elements_initial']."',
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
      'lieu_id'=>"<label for='lieu_id'>Lieu :</label><select name='lieu_id'>".$listeLieux."</select>",
      'categorie_id'=>"Catégorie :<br>".$donnees['categorie'],
      'fabricant_id'=>"Fabricant :<br>".$donnees['fabricant'],
      'facture_id'=>"Facture :<br>".$donnees['vendeur']." (".$donnees['date_facture'].")",
      "milieutexte"=>"",
      'fintexte'=>"",
      'finnum'=>"",
      'required'=>'',
      'datemax'=>"Date maximum :<br>".$donnees['date_max'],
      'libelle'=>"Libellé :<br>".$donnees['libelle'],
      'date_debut'=>"Date mise en service :<br>".$donnees['date_debut'],
      'accueil'=>"<a href='index.php'><input type='button' value='Retour à l`accueil' name='accueil'></a>",
      'valid'=>'CLIQUER POUR ENREGISTRER LA FICHE ---------->'
    ];}
  else {
    $liste = [
      'lieu_id'=>"<select name='lieu_id'>".$listeLieux."</select>",
      'categorie_id'=>"<label for='categorie_id'>Catégorie :</label><select name='categorie_id'>".$listeCategories."</select>",
      'fabricant_id'=>"<label for='fabricant_id'>Fabricat :</label><select name='fabricant_id'>".$listeFabricants."</select>",
      'facture_id'=>"
          <label for='facture'_id>Facture</label><select name='facture_id'>".$listeFactures."</select>",
      "milieutexte"=>"' ",
      'fintexte'=>"'>",
      'finnum'=>">",
      'required'=>' required ',
      'datemax'=>"Date maximum :<br>".$donnees['date_max'],
      'libelle'=>"<label for='libelle'>Libellé</label><input name='libelle' type='text' required value=".$donnees['libelle'].">",
      'date_debut'=>"<label for='date_debut'>Mise en service</label><input name='date_debut' type='date' value=".$donnees['date_debut'].">",
      'accueil'=>'',
      'valid'=>'ATTENTION : FICHE NON ENREGISTREE'
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
  #var_dump($id);  
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
  
  <form  enctype="multipart/form-data" method='post' action="fiche_creation.php">
    <table>
      <tbody>
        <tr>
          <td><label for='reference'>Référence :</label><input type='text' required name='reference' value=<?php echo $donnees['reference']?>  ></td><td>Photo : <input name="photo" value=<?php echo $donnees['photo']?>></td>
        </tr>
        <tr>
          <td><label for='Libellé'>Libellé :</label><?php echo "<input name='libelle' size=60 type='text' required value="."'".$donnees['libelle']."'>"?></td>
          <td rowspan = 7 colspan=1><img src = <?php echo "images/".$donnees['photo']?> width='400'></td>
        </tr>
        <tr>
          <td><?php echo $liste['lieu_id']?></td>
        </tr>
        <tr>
          <td><?php echo $liste['categorie_id'] ?></td>
        </tr>
        <tr>
          <td><label for='date_debut'>Mise en service ;</label><input type="date" name='date_debut' value=<?php echo $donnees['date_debut'] ?>></td>
        </tr>
        <tr>
          <td><?php echo $liste['fabricant_id']?></td>
        </tr>
        <tr>
          <td><label for='nb_elements_initial'>Nombre d'éléments</label><input type="number" name="nb_elements_initial" value=1 min=1></td>
        </tr>
        <tr>
          <td><?php echo $liste['facture_id']?></td>
        </tr>
        <tr>
          <label for="remarques">Remarques</label>
          <td colspan = 2><textarea name="remarques" cols = 100 rows=5 >
            <?php echo $donnees['remarques']?>
          </textarea> </td>
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
        </tr><tr>
          <td><?php echo $liste['valid']?></td>
          <td align="right">
            <input type="submit" name="envoyer" value="Enregistrer la fiche">
          </td>
          <tr><td>
            <a href='fiche_effacer.php'>
              <input type='button' value='Abandonner et effacer la fiche' >
            </a></td>
            <td align='right'><?php echo $liste['accueil']?></td></tr>
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