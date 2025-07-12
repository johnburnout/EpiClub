<?php

/**
 * Crée une nouvelle fiche dans la base de données
 * 
 * @param array $donnees Tableau associatif contenant les données de la fiche
 * @return array [
 *     'id' => int,        // ID de la fiche créée (0 en cas d'échec)
 *     'success' => bool,  // Statut de l'opération
 *     'error' => string   // Message d'erreur le cas échéant
 * ]
 */
function creation_fiche(array $donnees): array {
    $requiredFields = [
        'reference' => 'string',
        'libelle' => 'string',
        'categorie_id' => 'integer',
        'fabricant_id' => 'integer'
    ];

	// Configuration de la base de données
    global $host, $username, $password, $dbname;

	// Validation des champs obligatoires
    foreach ($requiredFields as $field => $type) {
        if (!isset($donnees[$field]) {
            return ['id' => 0, 'success' => false, 'error' => "Champ obligatoire manquant: $field"];
        }
        
        // Validation du type
        if ($type === 'integer' && !is_numeric($donnees[$field])) {
            return ['id' => 0, 'success' => false, 'error' => "Type invalide pour $field (nombre attendu)"];
        }
    } 
	
	// #############################
	// Connexion à la base de données
	// #############################
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	try {
	    $connection = new mysqli($host, $username, $password, $dbname);
	    $connection->set_charset("utf8mb4");
	} catch (mysqli_sql_exception $e) {
	    die("Erreur de connexion à la base de données: " . $e->getMessage());
	};

    try {        
        // Requête préparée avec tous les champs
        $sql = "INSERT INTO matos (
            reference, libelle, categorie_id, fabricant_id, 
            photo, lieu_id, nb_elements_initial, nb_elements, 
            facture_id, date_debut, date_facture, date_max, remarques
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $connection->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erreur de préparation de la requête");
        }

        // Gestion des valeurs par défaut et formatage des dates
        $dateDebut = !empty($donnees['date_debut']) ? date('Y-m-d', strtotime($donnees['date_debut'])) : null;
        $dateFacture = !empty($donnees['date_facture']) ? date('Y-m-d', strtotime($donnees['date_facture'])) : null;
		$dateMax = !empty($donnees['date_max']) ? date('Y-m-d', strtotime($donnees['date_max'])) : null;

        $stmt->bind_param(
            "ssiisiiiissss",
            $donnees['reference'],
            $donnees['libelle'],
            $donnees['categorie_id'],
            $donnees['fabricant_id'],
            $donnees['photo'] ?? null,
            $donnees['lieu_id'] ?? null,
            $donnees['nb_elements_initial'] ?? 1,
            $donnees['nb_elements'] ?? 1,
            $donnees['facture_id'] ?? null,
            $dateDebut,
            $dateFacture,
			$dateMax,
            $donnees['remarques'] ?? null
        );

        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de l'exécution de la requête");
        }

        $id = $connection->insert_id;
        return ['id' => $id, 'success' => true, 'error' => ''];

    } catch (mysqli_sql_exception $e) {
        error_log("Erreur MySQL: " . $e->getMessage());
        return ['id' => 0, 'success' => false, 'error' => 'Erreur de base de données'];
    } catch (Exception $e) {
        error_log("Erreur: " . $e->getMessage());
        return ['id' => 0, 'success' => false, 'error' => $e->getMessage()];
    } finally {
        if ($connection instanceof mysqli) {
            $connection->close();
        }
    }
}
?>