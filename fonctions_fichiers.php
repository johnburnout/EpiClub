<?php
    declare(strict_types=1);
    
    /**
    * Écrit du texte dans un fichier de manière sécurisée
    * 
    * @param array $fichier Doit contenir ['chemin' => string, 'texte' => string, 'mode' => string?]
    * @param bool $ajouterRetourLigne Ajoute un retour à la ligne automatiquement
    * @return string Message de confirmation
    * @throws InvalidArgumentException Si les paramètres sont invalides
    * @throws RuntimeException Si l'opération d'écriture échoue
    */
    function fichier_ecrire(array $fichier, bool $ajouterRetourLigne = true): string
    {
        // Validation des paramètres
        if (!isset($fichier['chemin'], $fichier['texte'])) {
            throw new InvalidArgumentException('Le tableau doit contenir les clés "chemin" et "texte"');
        }
        
        $chemin = $fichier['chemin'];
        $mode = $fichier['mode'] ?? 'ab'; // Mode par défaut: append binaire
        $texte = $fichier['texte'];
        
        // Nettoyage et validation
        $chemin = trim($chemin);
        if ($ajouterRetourLigne) {
            $texte .= PHP_EOL;
        }
        
        // Vérification du répertoire parent
        $repertoire = dirname($chemin);
        if (!is_dir($repertoire) || !is_writable($repertoire)) {
            throw new RuntimeException("Le répertoire $repertoire n'existe pas ou n'est pas accessible en écriture");
        }
        
        // Opération d'écriture atomique
        try {
            $handle = fopen($chemin, $mode);
            if ($handle === false) {
                throw new RuntimeException("Impossible d'ouvrir le fichier $chemin");
            }
            
            flock($handle, LOCK_EX); // Verrouillage exclusif
            $bytesWritten = fwrite($handle, $texte);
            flock($handle, LOCK_UN);
            
            fclose($handle);
            
            if ($bytesWritten === false || $bytesWritten !== strlen($texte)) {
                throw new RuntimeException("Échec de l'écriture complète dans $chemin");
            }
            
            return "Écriture réussie ($bytesWritten octets écrits)";
            
        } catch (Throwable $e) {
            // Nettoyage en cas d'erreur
            if (isset($handle) && is_resource($handle)) {
                @flock($handle, LOCK_UN);
                @fclose($handle);
            }
            throw new RuntimeException("Erreur lors de l'écriture: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
    * Lit le contenu d'un fichier de manière sécurisée
    * 
    * @param array $fichier Doit contenir ['chemin' => string]
    * @param int $tailleMax Taille maximale autorisée (en octets)
    * @return string Contenu du fichier
    * @throws InvalidArgumentException Si les paramètres sont invalides
    * @throws RuntimeException Si l'opération de lecture échoue
    */
    function fichier_lire(array $fichier, int $tailleMax = 1048576): string
    {
        // Validation des paramètres
        if (!isset($fichier['chemin'])) {
            throw new InvalidArgumentException('Le tableau doit contenir la clé "chemin"');
        }
        
        $chemin = trim($fichier['chemin']);
        
        // Vérification du fichier
        if (!file_exists($chemin)) {
            throw new RuntimeException("Le fichier $chemin n'existe pas");
        }
        
        if (!is_readable($chemin)) {
            throw new RuntimeException("Le fichier $chemin n'est pas accessible en lecture");
        }
        
        // Vérification de la taille
        $taille = filesize($chemin);
        if ($taille > $tailleMax) {
            throw new RuntimeException("Le fichier $chemin dépasse la taille maximale autorisée ($tailleMax octets)");
        }
        
        // Lecture du contenu
        try {
            $contenu = file_get_contents($chemin, false, null, 0, $tailleMax);
            if ($contenu === false) {
                throw new RuntimeException("Échec de la lecture du fichier $chemin");
            }
            
            return $contenu;
            
        } catch (Throwable $e) {
            throw new RuntimeException("Erreur lors de la lecture: " . $e->getMessage(), 0, $e);
        }
    }