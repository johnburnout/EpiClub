<?php

/**
 * Lit une fiche spécifique depuis la base de données
 * 
 * @param int $id Identifiant de la fiche à récupérer
 * @return array [
 *     'data' => array|null, // Données de la fiche ou null si non trouvée
 *     'success' => bool,    // Statut de l'opération
 *     'error' => string     // Message d'erreur le cas échéant
 * ]
 */
function lecture_fiche(int $id): array {  
    // Validation de l'ID
    if ($id <= 0) {
        return ['data' => null, 'success' => false, 'error' => 'ID invalide'];
    }
	
	// Configuration de la base de données (à externaliser idéalement)
    global $host, $username, $password, $dbname;
	
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
        // Requête préparée pour éviter les injections SQL
        $sql = "SELECT 
                ref AS reference, 
                libelle, 
                categorie, 
                categorie_id, 
                fabricant, 
                fabricant_id, 
                lieu, 
                lieu_id, 
                facture, 
                facture_id, 
                DATE_FORMAT(date_facture, '%d/%m/%Y') AS date_facture,
                username, 
                utilisateur_id, 
                nb_elements, 
                nb_elements_initial, 
                DATE_FORMAT(date_max, '%d/%m/%Y') AS date_max,
                DATE_FORMAT(date_debut, '%d/%m/%Y') AS date_debut,
                verification_id, 
                DATE_FORMAT(date_verification, '%d/%m/%Y') AS date_verification,
                remarques, 
                photo 
                FROM fiche 
                WHERE id = ?";
        
        $stmt = $connection->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        return [
            'data' => $data ?: null,
            'success' => (bool)$data,
            'error' => $data ? '' : 'Aucune fiche trouvée'
        ];

    } catch (mysqli_sql_exception $e) {
        error_log("Erreur DB: " . $e->getMessage());
        return ['data' => null, 'success' => false, 'error' => 'Erreur de base de données'];
    } catch (Exception $e) {
        error_log("Erreur: " . $e->getMessage());
        return ['data' => null, 'success' => false, 'error' => 'Erreur système'];
    } finally {
        if ($connection instanceof mysqli) {
            $connection->close();
        }
    }
}
?>