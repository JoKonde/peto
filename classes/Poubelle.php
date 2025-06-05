<?php
require_once 'Database.php';

class Poubelle {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Créer une nouvelle poubelle
    public function create($menage_id, $type, $description = '') {
        $sql = "INSERT INTO poubelles (menage_id, type, description, statut, created_at) 
                VALUES (:menage_id, :type, :description, 'vide', NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':menage_id' => $menage_id,
            ':type' => $type,
            ':description' => $description
        ]);
    }
    
    // Récupérer les poubelles d'un ménage
    public function getByMenage($menage_id) {
        $sql = "SELECT p.*, 
                       CASE 
                           WHEN p.alerte_pleine = 1 AND p.date_alerte IS NOT NULL 
                           THEN TIMESTAMPDIFF(HOUR, p.date_alerte, NOW())
                           ELSE 0 
                       END as heures_depuis_alerte
                FROM poubelles p 
                WHERE p.menage_id = :menage_id 
                ORDER BY p.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':menage_id' => $menage_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Alerter qu'une poubelle est pleine
    public function alerterPleine($poubelle_id, $menage_id) {
        // Vérifier que la poubelle appartient au ménage
        $sql = "SELECT id FROM poubelles WHERE id = :id AND menage_id = :menage_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $poubelle_id, ':menage_id' => $menage_id]);
        
        if (!$stmt->fetch()) {
            return false;
        }
        
        // Mettre à jour la poubelle
        $sql = "UPDATE poubelles 
                SET alerte_pleine = 1, 
                    date_alerte = NOW(), 
                    statut = 'pleine' 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([':id' => $poubelle_id]);
        
        if ($result) {
            // Assigner automatiquement à un collecteur
            $this->assignerCollecteur($poubelle_id);
        }
        
        return $result;
    }
    
    // Désactiver l'alerte (poubelle vidée)
    public function desactiverAlerte($poubelle_id, $menage_id) {
        // Vérifier qu'il y a une tâche de collecte associée
        $sql = "SELECT t.id FROM taches_collecte t 
                JOIN poubelles p ON t.poubelle_id = p.id 
                WHERE p.id = :poubelle_id AND p.menage_id = :menage_id 
                AND t.statut IN ('collectee', 'en_route', 'arrive', 'acceptee')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':poubelle_id' => $poubelle_id, ':menage_id' => $menage_id]);
        $tache = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Mettre à jour la poubelle
        $sql = "UPDATE poubelles 
                SET alerte_pleine = 0, 
                    date_alerte = NULL, 
                    statut = 'vide',
                    date_collecte = NOW()
                WHERE id = :id AND menage_id = :menage_id";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([':id' => $poubelle_id, ':menage_id' => $menage_id]);
        
        // Si une tâche existe, la terminer automatiquement
        if ($result && $tache) {
            require_once 'Collecteur.php';
            $collecteur = new Collecteur();
            $collecteur->terminerTache($tache['id']);
        }
        
        return $result;
    }
    
    // Assigner automatiquement à un collecteur
    private function assignerCollecteur($poubelle_id) {
        // Récupérer les informations de la poubelle
        $sql = "SELECT p.*, u.nom, u.prenom, u.adresse, u.commune, u.quartier, u.avenue, u.numero 
                FROM poubelles p 
                JOIN users u ON p.menage_id = u.id 
                WHERE p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $poubelle_id]);
        $poubelle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$poubelle) return false;
        
        // Trouver le collecteur avec le moins de tâches actives
        $sql = "SELECT u.id, u.nom, u.prenom, COUNT(t.id) as nb_taches
                FROM users u 
                LEFT JOIN taches_collecte t ON u.id = t.collecteur_id AND t.statut = 'en_attente'
                WHERE u.role = 'collecteur' 
                GROUP BY u.id 
                ORDER BY nb_taches ASC, u.id ASC 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $collecteur = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($collecteur) {
            // Créer la tâche de collecte
            $adresse_complete = $poubelle['adresse'] . ', ' . $poubelle['avenue'] . ' N°' . $poubelle['numero'] . ', ' . $poubelle['quartier'] . ', ' . $poubelle['commune'];
            
            $sql = "INSERT INTO taches_collecte (collecteur_id, poubelle_id, menage_nom, adresse, type_dechet, statut, created_at) 
                    VALUES (:collecteur_id, :poubelle_id, :menage_nom, :adresse, :type_dechet, 'en_attente', NOW())";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':collecteur_id' => $collecteur['id'],
                ':poubelle_id' => $poubelle_id,
                ':menage_nom' => $poubelle['prenom'] . ' ' . $poubelle['nom'],
                ':adresse' => $adresse_complete,
                ':type_dechet' => $poubelle['type']
            ]);
        }
        
        return false;
    }
    
    // Statistiques pour un ménage
    public function getStatsMenage($menage_id) {
        $stats = [];
        
        // Nombre total de poubelles
        $sql = "SELECT COUNT(*) as total FROM poubelles WHERE menage_id = :menage_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':menage_id' => $menage_id]);
        $stats['total_poubelles'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Poubelles évacuées (collectées)
        $sql = "SELECT COUNT(*) as evacuees FROM poubelles WHERE menage_id = :menage_id AND date_collecte IS NOT NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':menage_id' => $menage_id]);
        $stats['poubelles_evacuees'] = $stmt->fetch(PDO::FETCH_ASSOC)['evacuees'];
        
        // Poubelles en attente
        $sql = "SELECT COUNT(*) as en_attente FROM poubelles WHERE menage_id = :menage_id AND alerte_pleine = 1 AND date_collecte IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':menage_id' => $menage_id]);
        $stats['poubelles_en_attente'] = $stmt->fetch(PDO::FETCH_ASSOC)['en_attente'];
        
        // Temps moyen d'attente pour les alertes non collectées
        $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, date_alerte, NOW())) as temps_moyen 
                FROM poubelles 
                WHERE menage_id = :menage_id AND alerte_pleine = 1 AND date_collecte IS NULL AND date_alerte IS NOT NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':menage_id' => $menage_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['temps_attente_moyen'] = $result['temps_moyen'] ? round($result['temps_moyen'], 1) : 0;
        
        return $stats;
    }
    
    // Supprimer une poubelle
    public function delete($poubelle_id, $menage_id) {
        $sql = "DELETE FROM poubelles WHERE id = :id AND menage_id = :menage_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $poubelle_id, ':menage_id' => $menage_id]);
    }
    
    // Récupérer les collectes avec suivi en temps réel pour un ménage
    public function getCollectesAvecSuivi($menage_id) {
        $sql = "SELECT t.*, u.nom as collecteur_nom, u.prenom as collecteur_prenom, u.telephone as collecteur_tel,
                       p.type as poubelle_type, p.description as poubelle_description,
                       TIMESTAMPDIFF(HOUR, t.created_at, NOW()) as heures_depuis_creation
                FROM taches_collecte t
                JOIN users u ON t.collecteur_id = u.id
                JOIN poubelles p ON t.poubelle_id = p.id
                WHERE p.menage_id = :menage_id
                ORDER BY t.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':menage_id' => $menage_id]);
        $collectes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ajouter les étapes pour chaque collecte
        foreach ($collectes as &$collecte) {
            $sql = "SELECT * FROM etapes_collecte 
                    WHERE tache_id = :tache_id 
                    ORDER BY created_at ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tache_id' => $collecte['id']]);
            $collecte['etapes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $collectes;
    }
    
    // Récupérer le statut en temps réel d'une collecte spécifique
    public function getStatutCollecteTempsReel($tache_id, $menage_id) {
        $sql = "SELECT t.*, u.nom as collecteur_nom, u.prenom as collecteur_prenom, u.telephone as collecteur_tel,
                       p.type as poubelle_type, p.description as poubelle_description
                FROM taches_collecte t
                JOIN users u ON t.collecteur_id = u.id
                JOIN poubelles p ON t.poubelle_id = p.id
                WHERE t.id = :tache_id AND p.menage_id = :menage_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':tache_id' => $tache_id, ':menage_id' => $menage_id]);
        $collecte = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($collecte) {
            // Récupérer les étapes
            $sql = "SELECT * FROM etapes_collecte 
                    WHERE tache_id = :tache_id 
                    ORDER BY created_at ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tache_id' => $tache_id]);
            $collecte['etapes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $collecte;
    }
}
?> 