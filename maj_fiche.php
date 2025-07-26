<?php
    declare(strict_types=1);
    
    /**
    * Met à jour une fiche matériel dans la base de données avec validation avancée
    * 
    * @param array $donnees Tableau associatif des données à mettre à jour
    * @param int $id ID de la fiche à modifier
    * @param mysqli|null $connection Connexion MySQLi existante (optionnelle)
    * @return array [
    *     'success' => bool,        // Statut de l'opération
    *     'affected_rows' => int,   // Nombre de lignes affectées
    *     'error' => string         // Message d'erreur le cas échéant
    * ]
    */
    function mise_a_jour_fiche(array $donnees, ?mysqli $connection = null): array {
        // 1. VALIDATION DES ENTREES
        if ($donnees['id'] <= 0) {
            return [
                'success' => false, 
                'affected_rows' => 0, 
                'error' => 'ID de fiche invalide (doit être un entier positif)'
            ];
        }
        var_dump($donnees['verification_id']);
        // 2. CONFIGURATION ET VALIDATION
        $champsObligatoires = [
            'reference' => ['type' => 'string', 'max' => 50],
            'libelle' => ['type' => 'string', 'max' => 255],
            'categorie_id' => ['type' => 'integer'],
            'fabricant_id' => ['type' => 'integer']
        ];
        
        $champsOptionnels = [
            'photo' => ['type' => 'string', 'max' => 255],
            'lieu_id' => ['type' => 'integer'],
            'date_debut' => ['type' => 'date'],
            'nb_elements_initial' => ['type' => 'integer', 'min' => 1],
            'nb_elements' => ['type' => 'integer', 'min' => 0],
            'facture_id' => ['type' => 'integer'],
            'remarques' => ['type' => 'string', 'max' => 1000],
            'verification_id' => ['type' => 'integer'],
            'utilisateur' => ['type' => 'string', 'max' => 50]
        ];
        //var_dump($donnees);
        // Validation des champs obligatoires
        foreach ($champsObligatoires as $champ => $config) {
            if (!isset($donnees[$champ])) {
                return [
                    'success' => false,
                    'affected_rows' => 0,
                    'error' => "Champ obligatoire manquant: $champ"
                ];
            }
            
            // Validation des types et contraintes
            $erreur = valider_champ($donnees[$champ], $config);
            if ($erreur !== null) {
                return [
                    'success' => false,
                    'affected_rows' => 0,
                    'error' => "Erreur de validation pour $champ: $erreur"
                ];
            }
        }
        
        // 3. GESTION DE LA CONNEXION
        $shouldCloseConnection = false;
        //var_dump($donnees);
        try {
            if ($connection === null) {
                global $host, $username, $password, $dbname;
                
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                $connection = new mysqli($host, $username, $password, $dbname);
                $connection->set_charset("utf8mb4");
                $shouldCloseConnection = true;
            }
            
            // 4. PRÉPARATION DE LA REQUÊTE
            $sql = "UPDATE matos SET
            reference = ?,
            libelle = ?,
            categorie_id = ?,
            fabricant_id = ?,
            photo = ?,
            lieu_id = ?,
            date_debut = ?,
            date_max = ?,
            nb_elements_initial = ?,
            nb_elements = ?,
            facture_id = ?,
            remarques = ?,
            date_modification = NOW(),
            verification_id = ?,
            utilisateur = ?
            WHERE id = ?";
            
            $stmt = $connection->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erreur de préparation de la requête: " . $connection->error);
            }
            //var_dump($donnees);
            // 5. FORMATAGE DES DONNÉES
            //$dateDebut = !empty($donnees['date_debut']) ? formater_date($donnees['date_debut']) : null;
            //$dateFacture = date('Ymd',strtotime($donnees['date_facture']));
            //var_dump($donnees['date_max']);
            // 6. EXÉCUTION DE LA REQUÊTE
            $reference = $donnees['reference'];
            $libelle = $donnees['libelle'];
            $categorie_id = (int)$donnees['categorie_id'];
            $fabricant_id = (int)$donnees['fabricant_id'];
            $photo = $donnees['photo'] ?? null;
            $lieu_id = isset($donnees['lieu_id']) ? (int)$donnees['lieu_id'] : null;
            $dateDebut = date('Ymd',strtotime($donnees['date_debut']));
            $dateMax = date('Ymd',strtotime($donnees['date_max']));
            $nb_elements_initial = isset($donnees['nb_elements_initial']) ? (int)$donnees['nb_elements_initial'] : 1;
            $nb_elements = isset($donnees['nb_elements']) ? (int)$donnees['nb_elements'] : $nb_elements_initial;
            $facture_id = isset($donnees['facture_id']) ? (int)$donnees['facture_id'] : null;
            $remarques = $donnees['remarques'] ?? null;
            $verification_id = isset($donnees['verification_id']) ? (int)$donnees['verification_id'] : null;
            $utilisateur = $donnees['utilisateur'] ?? null;
            $id = (int)$donnees['id'];
            
            $stmt = $connection->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erreur de préparation de la requête: " . $connection->error);
            }
            //var_dump($remarques);
            // Liaison des paramètres avec les variables préparées
            $stmt->bind_param(
                "ssiisssiiiisisi",
                $reference,
                $libelle,
                $categorie_id,
                $fabricant_id,
                $photo,
                $lieu_id,
                $dateDebut,
                $dateMax,
                $nb_elements_initial,
                $nb_elements,
                $facture_id,
                $remarques,
                $verification_id,
                $utilisateur,
                $id
            );
            
            $stmt->execute();
            $affectedRows = $stmt->affected_rows;
            
            // 7. VÉRIFICATION DU RÉSULTAT
            if ($affectedRows === -1) {
                throw new Exception("Erreur lors de la mise à jour");
            }
            
            return [
                'success' => $affectedRows >= 0,
                'affected_rows' => $affectedRows,
                'error' => $affectedRows >= 0 ? '' : 'Aucune ligne mise à jour (ID peut-être inexistant)'
            ];
            
        } catch (mysqli_sql_exception $e) {
            error_log("Erreur MySQL lors de la mise à jour fiche ".$donnees['id']." : " . $e->getMessage());
            return [
                'success' => false,
                'affected_rows' => 0,
                'error' => 'Erreur de base de données'. $e->getMessage()
            ];
        } catch (Exception $e) {
            error_log("Erreur lors de la mise à jour fiche $id: " . $e->getMessage());
            return [
                'success' => false,
                'affected_rows' => 0,
                'error' => $e->getMessage()
            ];
        } finally {
            // 8. FERMETURE PROPRE DE LA CONNEXION SI NÉCESSAIRE
            if ($shouldCloseConnection && $connection instanceof mysqli) {
                $connection->close();
            }
        }
    }
    
    /**
    * Valide un champ selon sa configuration
    */
    function valider_champ($valeur, array $config): ?string {
        $type = $config['type'] ?? null;
        
        if ($type === 'integer' && !filter_var($valeur, FILTER_VALIDATE_INT)) {
            return "doit être un entier";
        }
        
        if ($type === 'string' && !is_string($valeur)) {
            return "doit être une chaîne de caractères";
        }
        
        if ($type === 'date' && !strtotime($valeur)) {
            return "date invalide";
        }
        
        if (isset($config['max'])) {
            if ($type === 'string' && strlen($valeur) > $config['max']) {
                return "ne doit pas dépasser {$config['max']} caractères";
            }
            
            if ($type === 'integer' && $valeur > $config['max']) {
                return "doit être inférieur ou égal à {$config['max']}";
            }
        }
        
        if (isset($config['min'])) {
            if ($type === 'integer' && $valeur < $config['min']) {
                return "doit être supérieur ou égal à {$config['min']}";
            }
        }
        
        return null;
    }
    
    /**
    * Formate une date pour MySQL
    */
    function formater_date(string $date): ?string {
        echo $date;
        //$timestamp = strtotime($date);
        //echo date('Y-m-d', $timestamp);
        return $date;//$timestamp ? date('Y-m-d', $timestamp) : null;
    }