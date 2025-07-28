<?php
	//DEBUG
	function dev($var) {
		$dev = isset($_COOKIE['dev']) ? $_COOKIE['dev'] : $_SESSION['dev'];
		if ($dev) {		
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
			ini_set('display_startup_errors', 1);
		}
		$aff = '';
		if ($dev) {
			var_dump($var); echo PHP_EOL.'-----------'.PHP_EOL;
		}
		return true;
	}
?>