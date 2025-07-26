<?php
	function init_donnees() {
		$donnees = array(
			'id' => 0,
			'libelle' => '', 
			'reference' => date('y').strval(rand(100000,999999)),
			'date_facture' => null,
			'username' => '',
			'utilisateur_id' => 1,
			'nb_elements' => 1,
			'nb_elements_initial' => 1,
			'date_max' => null,
			'date_debut' => date('Ymd'),
			'verification_id' => 1,
			'date_verification' => date('Ymd',strtotime(date('Ymd').' +10 years')),
			'remarques' => '',
			'photo' => 'null.jpeg'
		);
		return $donnees;
	}
?>