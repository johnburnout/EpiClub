<?php
	//DEBUG
	if ($_SESSION['dev']) {
		
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
	}
	function dev($var) {
		$aff = '';
		if ($_SESSION['dev']) {
			var_dump($var); echo PHP_EOL.'-----------'.PHP_EOL;
		}
		return true;
	}
?>