<?php

/**
  * nettoie le code HTML pour affichage
  *
  */

function escape($html) {
  if ($html) {
    return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
  }
  else {
    return "";
  }
}
  
?>