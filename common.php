<?php
  
  //echo "appel fonctions_bdd.php ";
  
  require $root."includes/session.php";
  require $root."includes/debug.php";
  //echo "appel creation_controle.php ";
  require $root."includes/bdd/creation_controle.php";
  //echo "appel creation_fiche.php ";
  require $root."includes/bdd/creation_fiche.php";
  //echo "appel creation_facture.php ";
  require $root."includes/bdd/creation_facture.php";
  //echo "appel lecture_controle.php ";
  require $root."includes/bdd/lecture_controle.php";
  //echo "appel lecture_fiche.php ";
  require $root."includes/bdd/lecture_fiche.php";
  //echo "appel lecture_facture.php ";
  require $root."includes/bdd/lecture_facture.php";
  //echo "appel liste_options.php ";
  require $root."includes/bdd/liste_options.php";
  //echo "appel maj_controle.php ";
  require $root."includes/bdd/maj_controle.php";
  //echo "appel maj_fiche.php ";
  require $root."includes/bdd/maj_fiche.php";
  //echo "appel maj_facture.php ";
  require $root."includes/bdd/maj_facture.php";
  //echo "appel fonctions_editions.php ";
  require $root."includes/fonctions_edition.php";
  //echo "appel fonctions_fichiers.php ";
  require $root."includes/fonctions_fichiers.php";
  //echo "appels termines ";
  
?>