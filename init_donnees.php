<?php
	function init_donnees() {
		$donnees = array(
			'id' => 0,
			'reference' => rand(10000000,99999999),
			'libelle' => '' ,
			'categorie' => '',
			'categorie_id' => 1,
			'libelle' => '',
			'fabricant_id' => 1,
			'lieu' => '',
			'lieu_id' => 3,
			'vendeur' => '',
			'facture_id' => 1,
			'date_facture' => null,
			'username' => '',
			'utilisateur_id' => 1,
			'nb_elements' => 1,
			'nb_elements_initial' => 1,
			'date_max' => null,
			'date_debut' => date('Ymd'),
			'verification_id' => 1,
			'date_verification' => null,
			'remarques' => '',
			'photo' => 'null.jpeg'
		);
		return $donnees;
	}
?>