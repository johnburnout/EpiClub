
<?php

/**
 * Met à jour une fiche matériel dans la base de données
 * 
 * @param array $donnees Tableau associatif des données à mettre à jour
 * @param int $id ID de la fiche à modifier
 * @return array [
 *     'success' => bool,    // Statut de l'opération
 *     'affected_rows' => int, // Nombre de lignes affectées
 *     'error' => string     // Message d'erreur le cas échéant
 * ]
 */
function mise_a_jour_fiche(array $donnees, int $id): array {
    // Validation de l'ID
    if ($id <= 0) {
        return ['success' => false, 'affected_rows' => 0, 'error' => 'ID invalide'];
    }
	
    // Configuration de la base de données
    global $host, $username, $password, $dbname;

    // Validation des champs obligatoires
    $requiredFields = [
        'reference' => 'string',
        'libelle' => 'string', 
        'categorie_id' => 'integer',
        'fabricant_id' => 'integer'
    ];

    foreach ($requiredFields as $field => $type) {
        if (!isset($donnees[$field])) {
            return ['success' => false, 'affected_rows' => 0, 'error' => "Champ obligatoire manquant: $field"];
        }
        
        // Validation du type
        if ($type === 'integer' && !is_numeric($donnees[$field])) {
            return ['success' => false, 'affected_rows' => 0, 'error' => "Type invalide pour $field (nombre attendu)"];
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
        // Requête préparée avec gestion des dates
        $sql = "UPDATE matos SET
                reference = ?,
                libelle = ?,
                categorie_id = ?,
                fabricant_id = ?,
                photo = ?,
                lieu_id = ?,
                date_debut = ?,
                nb_elements_initial = ?,
                nb_elements = ?,
                facture_id = ?,
                date_facture = ?,
                remarques = ?,
                date_modification = NOW()
            WHERE id = ?";

        $stmt = $connection->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erreur de préparation de la requête: " . $connection->error);
        }

        // Formatage des dates
        $dateDebut = !empty($donnees['date_debut']) ? date('Y-m-d', strtotime($donnees['date_debut'])) : null;
        $dateFacture = !empty($donnees['date_facture']) ? date('Y-m-d', strtotime($donnees['date_facture'])) : null;

        $stmt->bind_param(
            "ssiisssiiissi",
            $donnees['reference'],
            $donnees['libelle'],
            $donnees['categorie_id'],
            $donnees['fabricant_id'],
            $donnees['photo'] ?? null,
            $donnees['lieu_id'] ?? null,
            $dateDebut,
            $donnees['nb_elements_initial'] ?? 1,
            $donnees['nb_elements'] ?? 1,
            $donnees['facture_id'] ?? null,
            $dateFacture,
            $donnees['remarques'] ?? null,
            $id
        );

        $stmt->execute();
        $affectedRows = $stmt->affected_rows;

        return [
            'success' => true,
            'affected_rows' => $affectedRows,
            'error' => $affectedRows > 0 ? '' : 'Aucune ligne mise à jour'
        ];

    } catch (mysqli_sql_exception $e) {
        error_log("Erreur MySQL: " . $e->getMessage());
        return ['success' => false, 'affected_rows' => 0, 'error' => 'Erreur de base de données'];
    } catch (Exception $e) {
        error_log("Erreur: " . $e->getMessage());
        return ['success' => false, 'affected_rows' => 0, 'error' => $e->getMessage()];
    } finally {
        if ($connection instanceof mysqli) {
            $connection->close();
        }
    }
}
?>