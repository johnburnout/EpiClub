<?php
session_start();
require "header.php";


	
	require "config.php";
	require "common.php";
	
	
	#
	#echo "<br>\$_POST : "; escape(print_r($_POST)); echo "<br>"; # <------------------------- VERIF
	#
	#
	#echo "<br>\$23/5 : ".(intval(23%5))."<br>"; # <------------------------- VERIF
	#
	


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


	
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); 
	
$connection = new mysqli($host, $username, $password, $dbname);

	
# #############################
# initialisation variables pagination
# #############################
	
$debut = 1 ; $long = 20; $nblignes = 20;
if (isset($_POST['debut'])) {$debut = max(0, $_POST['debut']-1);};
if (isset($_POST['long'])) {$long = $_POST['long'];};
if (isset($_POST['nblignes'])) {$nblignes = $_POST['nblignes'];};
$id = 1; if (isset($_POST['id'])) {$id = $_POST['id'];};

# ############################
# Création des liste
# ############################


#   # Récupération des lieux
$statement = $connection->query("SELECT * FROM lieu ORDER BY id");
$nLieux = $statement->num_rows;
$lieux = $statement->fetch_all(MYSQLI_ASSOC);
//$lieux = $lieux[0];
	
#   # Récupération des categories
$statement = $connection->query("SELECT * FROM categorie ORDER BY id");
$nCategorie = $statement->num_rows;
$categories = $statement->fetch_all(MYSQLI_ASSOC);

$choixLieu = "";
$nomLieu = '*';
$id_lieu = 0;

if (isset($_POST['lieu_id'])and $_POST['lieu_id'] > 0)
	{	
		$id_lieu = $lieux[$_POST['lieu_id']-1]['id'];
		$lieux[$id_lieu-1]['id'] = $lieux[$id_lieu-1]['id']." selected";
		$choixLieu = "lieu_id = ".$id_lieu;
		$nomLieu = $lieux[$id_lieu]["libelle"];
	};
	#
	#echo "<br>\$lieux : "; print_r($lieux);echo "<br>"; # <------------------------- VERIF
	# 
	$listeLieux = "<option value = 0>*</option>";
		
for ($i = 0; $i < $nLieux; $i++)
	{
		$listeLieux = $listeLieux."<option value= ".$lieux[$i]["id"].">".$lieux[$i]["libelle"]." </option>";
	};
	
	#
	#  echo "<br>\$listeLieux : ".escape($listeLieux)."<br>"; # <------------------------- VERIF
	# 
	#
	#echo "<br>\$id_lieu : ".$id_lieu."<br>"; # <------------------------- VERIF
	# 
	
$choixCat = "";
$nomCat = '*';
$id_cat = 0;

if (isset($_POST['cat_id']) and $_POST['cat_id'] > 0)
	{
		$id_cat = $categories[$_POST['cat_id']-1]['id'];
		$categories[$id_cat-1]['id'] = $categories[$id_cat-1]['id']." selected";
		$choixCat = "categorie_id = ".$id_cat;
		$nomCat = $categories[$id_cat]["libelle"];
	};
	#
	#echo "<br>\$categories : "; print_r($categories);echo "<br>"; # <------------------------- VERIF
	#
	
	$listeCategories = "<option value =0>*</option>";
	
for ($i = 0; $i < $nCategorie; $i++)
	{
		$listeCategories = $listeCategories."<option value= ".($categories[$i]["id"]).">".$categories[$i]["libelle"]." </option>";
	};

	#
	 #echo "<br>\$listeCategories : ".escape($listeCategories)."<br>"; # <------------------------- VERIF
	# 
	#
	#echo "<br>\$id_cat : ".$id_cat."<br>"; # <------------------------- VERIF
	# 
	
	
###################################
# Création du critère de sélection
###################################

$count = 0;

if ($id_lieu > 0) { $count += 1;};
if ($id_cat > 0) { $count += 2;};

	#
	#echo "<br>\$count : ".$count."<br>"; # <------------------------- VERIF
	# 
	
if ($count == 3)
	{
		$sepChoix = " AND ";
	}
else
	{
		$sepChoix = "";
	};
if ($count > 0)	{
		$initChoix = " WHERE ";
	}
else
	{
		$initChoix = "";
	};



$choix = $initChoix.$choixLieu.$sepChoix.$choixCat;

	#
	#echo "<br>\$choix : ".escape($choix)."<br>"; # <------------------------- VERIF
	#

# ###################
# Critères de tri
# ###################

$choixTri="
<option value ='id'>Identifiant</option>,
<option value ='ref'>Référence</option>,
<option value ='lieu_id'>Lieu</option>,
<option value ='date_verification'>Date de vérification</option>
<option value ='fabricant'>Fabricant</option>
";
	
$tri = 'id';

if (isset($_POST['tri']))
	{
	$tri = $_POST['tri'];
	};

###########################
# Recherche dans la base
###########################

try {
	
	$sql = "SELECT
		id,
		ref,
		libelle,
		fabricant,
		categorie,
		categorie_id,
		lieu,
		lieu_id,
		nb_elements,
		date_verification,
		date_max
	FROM
		liste ".$choix."
	ORDER BY ".$tri.";";
//	print_r($sql);
//	echo $sql;
	
	$statement = $connection->query($sql);

//	$statement->execute();
//	$result = $statement->get_result();
	$result = $statement->fetch_all(MYSQLI_ASSOC);
	$nblignes = count($result);

	
	#
	#	echo "<br>\$result : "; var_dump($result); echo  '<br>'  ; #<-------------------------
	#
	
	#
	#	echo "<br>\$nblignes : "; print_r($nblignes); echo  '<br>'  ; #<-------------------------
	#
	
	$n = $statement->num_rows; 
//	for ($i=0; $i < $n; $i++)
//	{
//		var_dump($result[$i]['id']);
//		echo '  '.intval($result[$i]["id"]);
//	};
	
//	echo $n.$result[1];
}
catch(Exception $error) {
echo $sql . "<br>" . $error->getMessage();
}

	if ($nblignes < $long) {$nbpages = 1;}
	else {
		if ($nblignes % $long == 0) {
			$nbpages = $nblignes / $long;
		}
		else {
			$nbpages = intval($nblignes / $long )+ 1;
		};
	};	

	#
	 #echo "<br>\$_debut, \$nblignes, \$nbpages : ".escape($debut)." - ".$nblignes." - ".$nbpages."<br>"; # <------------------------- VERIF
	#
	
	
# #############################
# Code HTML 
# #############################
	
?>
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

	<!-- Test si connecté debut -->
	<?php if (count($_SESSION) > 0)
		{ 
	?>
	<!---->
	
<h3>Filtrer les données</h3>

<form method="post">
	<table>
		<thead>
			<tr>
				<th colspan=2>Filtrer par :</th><th colspan = 2>Trier par : </th>
				<th>
					Nb de lignes par feuille:
				</th>
				<th>
					Permière ligne :
				</th>
			</tr>
			<tr>
				<td>Lieu</td>
				<td>Catégorie</td>
				<td rowspan = 2>
					<select name='tri'>
						<?php echo $choixTri ?>	
					</select>	
				</td>
				<td colspan=2  rowspan=2>
					<input type="number" name="long" min=5 max=<?php echo (min($nblignes+$long, $long*($nbpages+1))) ?> step=5 value="<?php echo ($long) ?>">
				</td>
				<td rowspan=2>
					<input type="number" name="debut" min=1 step=<?php echo $long?> max=<?php echo max($nblignes, $nbpages*$long)?>  value="<?php echo ($debut+1) ?>">
				</td>
			</tr>
			<tr>
				<td>
					<select name='lieu_id'>
						<?php echo $listeLieux ?>	
					</select>					
				</td>
				<td>
					<select name='cat_id'>
						<?php echo $listeCategories ?>	
					</select>
				</td>
			</tr>

		</thead>
	</table>
	<p></p>
	<input type="submit" name="choix" value="Filtrer et trier">
	
</form>
<?php
if ($result # && $statement->rowCount() > 0
) { ?>
<hr>
<h3>Liste</h3>

<form method="post" action="fiche_verif.php">
	<table>
		<thead>
			<tr>
				<th>#</th>
				<th>Ref</th>
				<th>Libellé</th>
				<th>Fabricant</th>
				<th>Cat</th>
				<th>Lieu</th>
				<th>Nb éléments</th>
				<th>Date Vérif</th>
				<th>Date Max</th>
			</tr>
		</thead>
		<tbody>
			<?php
				for ($i = $debut; $i < min($debut + $long, $nblignes); $i++) { 						
			?>
			<tr>
				<td><input type="radio" name="id" required value=<?php echo $result[$i]["id"]?> <?php echo " ".$i." : ".$result[$i]["id"]; ?>></td>
				<td><?php echo $result[$i]["ref"]; ?></td>
				<td><?php echo $result[$i]["libelle"]; ?></td>
				<td><?php echo $result[$i]["fabricant"]; ?></td>
				<td><?php echo $result[$i]["categorie"]; ?></td>
				<td><?php echo $result[$i]["lieu"]; ?></td>
				<td><?php echo $result[$i]["nb_elements"]; ?> </td>
				<td><?php echo $result[$i]["date_verification"]; ?> </td>
				<td><?php echo $result[$i]["date_max"]; ?> </td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
	<p></p>
	<input type='hidden' name='appel_liste' value=1>
	<input type="hidden" name='action' value='maj'>
	<input type="hidden" name='debut' value=<?php echo $debut; ?>>
	<input type="hidden" name='long' value=<?php echo $long; ?>>
	<input type="hidden" name='nblignes' value=<?php echo $nblignes; ?>>
	<input type="submit" name="submit" value="Afficher la fiche">
	<a href='fiche_creation.php'>
		<input type='button' value="Créer une nouvelle fiche" >
	</a>
	<a href='index.php'>
		<input type='button' value="Revenir à l'accueil" >
	</a>
</form>
<?php } else { ?>
Pas de fiches trouvées !<?php# echo escape($_POST['location']); ?>.
 <?php 
 } ?>

<p></p>
<hr>
	
	<!-- Test si connecté fin -->
	<?php 
		}
		else { ?>
	<p>Tu n'es pas connecté</p>
	<?php 
	}?>
	<!---->
	
<?php require "footer.php"; ?>        # Créé par Jean Roussie Périgord Escalade : jean@grimpe.fr