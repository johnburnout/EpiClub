<?php
	
;
	
	function fichier_ecrire ($fichier) {
		$handle = fopen($fichier['chemin'],"ab");
		fwrite($handle, $fichier['texte']."\n");
		return "ecriture correcte";
	};

	function fichier_lire ($fichier) {
		$handle = fopen($fichier['chemin'],"r");
		$texte = fread($handle);
		return $texte;		
	}
?>