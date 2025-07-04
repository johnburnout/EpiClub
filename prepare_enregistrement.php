<?php

function prepare_enregistrement($entree) {
  if (is_array($entree)) {
    if (count(entree) == 4) {
      if ($entree[0] == $entree[1]) {
        $sortie = '';
      }
      else {
        $sortie = ['resultat' => 1, 'texte' => '\n'.$entree[2].'\n'.$entree[1].'\n'.$entree[3]];
      };
    }
    else {
      $sortie = ['resultat' => 0, 'texte' => 'ERREUR : l\'array n\'a pas le nb de champs (attendus).'];
    };
  }
  else {
    $sortie = ['resultat' => 0, 'texte' => 'ERREUR : l\'argument n\'est pas un champ'];
  };
  return sortie;
 }; 
?>