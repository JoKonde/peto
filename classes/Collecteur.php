<?php
require_once 'Database.php';

class Collecteur {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Récupérer les tâches d'un collecteur
    public function getTaches($collecteur_id) {
        $sql = "SELECT t.*, p.type as poubelle_type, p.description as poubelle_description,
                       u.nom as menage_nom, u.prenom as menage_prenom, u.telephone as menage_tel,
                       TIMESTAMPDIFF(HOUR, t.created_at, NOW()) as heures_depuis_creation
                FROM taches_collecte t
                JOIN poubelles p ON t.poubelle_id = p.id
                JOIN users u ON p.menage_id = u.id
                WHERE t.collecteur_id = :collecteur_id
                ORDER BY 
                    CASE t.statut 
                        WHEN 'en_attente' THEN 1
                        WHEN 'acceptee' THEN 2
                        WHEN 'en_route' THEN 3
                        WHEN 'arrive' THEN 4
                        WHEN 'collectee' THEN 5
                        WHEN 'terminee' THEN 6
                    END,
                    t.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':collecteur_id' => $collecteur_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Accepter une tâche
    public function accepterTache($tache_id, $collecteur_id) {
        $sql = "UPDATE taches_collecte 
                SET statut = 'acceptee', 
                    date_acceptation = NOW() 
                WHERE id = :id AND collecteur_id = :collecteur_id AND statut = 'en_attente'";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([':id' => $tache_id, ':collecteur_id' => $collecteur_id]);
        
        if ($result) {
            $this->ajouterEtapeCollecte($tache_id, 'acceptee', 'Tâche acceptée par le collecteur');
        }
        
        return $result;
    }
    
    // Marquer en route
    public function marquerEnRoute($tache_id, $collecteur_id) {
        $sql = "UPDATE taches_collecte 
                SET statut = 'en_route', 
                    date_en_route = NOW() 
                WHERE id = :id AND collecteur_id = :collecteur_id AND statut = 'acceptee'";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([':id' => $tache_id, ':collecteur_id' => $collecteur_id]);
        
        if ($result) {
            $this->ajouterEtapeCollecte($tache_id, 'en_route', 'Collecteur en route vers le ménage');
        }
        
        return $result;
    }
    
    // Marquer arrivé
    public function marquerArrive($tache_id, $collecteur_id) {
        $sql = "UPDATE taches_collecte 
                SET statut = 'arrive', 
                    date_arrivee = NOW() 
                WHERE id = :id AND collecteur_id = :collecteur_id AND statut = 'en_route'";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([':id' => $tache_id, ':collecteur_id' => $collecteur_id]);
        
        if ($result) {
            $this->ajouterEtapeCollecte($tache_id, 'arrive', 'Collecteur arrivé chez le ménage');
        }
        
        return $result;
    }
    
    // Marquer collecté
    public function marquerCollecte($tache_id, $collecteur_id) {
        $sql = "UPDATE taches_collecte 
                SET statut = 'collectee', 
                    date_collecte = NOW() 
                WHERE id = :id AND collecteur_id = :collecteur_id AND statut = 'arrive'";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([':id' => $tache_id, ':collecteur_id' => $collecteur_id]);
        
        if ($result) {
            $this->ajouterEtapeCollecte($tache_id, 'collectee', 'Poubelle collectée, en attente de confirmation du ménage');
        }
        
        return $result;
    }
    
    // Terminer la tâche (appelé quand le ménage confirme)
    public function terminerTache($tache_id, $collecteur_id = null) {
        // Si pas d'étapes précédentes, les remplir automatiquement
        $this->completerEtapesManquantes($tache_id);
        
        $sql = "UPDATE taches_collecte 
                SET statut = 'terminee', 
                    date_completion = NOW() 
                WHERE id = :id" . ($collecteur_id ? " AND collecteur_id = :collecteur_id" : "");
        
        $stmt = $this->db->prepare($sql);
        $params = [':id' => $tache_id];
        if ($collecteur_id) {
            $params[':collecteur_id'] = $collecteur_id;
        }
        
        $result = $stmt->execute($params);
        
        if ($result) {
            $this->ajouterEtapeCollecte($tache_id, 'terminee', 'Tâche terminée et confirmée par le ménage');
            $this->mettreAJourStatistiques($collecteur_id ?: $this->getCollecteurByTache($tache_id));
        }
        
        return $result;
    }
    
    // Ajouter une étape de collecte pour le suivi
    private function ajouterEtapeCollecte($tache_id, $etape, $description) {
        $sql = "INSERT INTO etapes_collecte (tache_id, etape, description, created_at) 
                VALUES (:tache_id, :etape, :description, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':tache_id' => $tache_id,
            ':etape' => $etape,
            ':description' => $description
        ]);
    }
    
    // Compléter les étapes manquantes automatiquement
    private function completerEtapesManquantes($tache_id) {
        // Récupérer la tâche
        $sql = "SELECT * FROM taches_collecte WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $tache_id]);
        $tache = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tache) return;
        
        $collecteur_id = $tache['collecteur_id'];
        $now = date('Y-m-d H:i:s');
        
        // Vérifier quelles étapes existent déjà
        $sql = "SELECT etape FROM etapes_collecte WHERE tache_id = :tache_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':tache_id' => $tache_id]);
        $etapes_existantes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Compléter les étapes manquantes avec des timestamps rétroactifs
        $etapes_requises = [
            'acceptee' => 'Tâche acceptée automatiquement',
            'en_route' => 'Collecteur parti automatiquement',
            'arrive' => 'Collecteur arrivé automatiquement',
            'collectee' => 'Poubelle collectée automatiquement'
        ];
        
        $interval = 15; // 15 minutes entre chaque étape
        $timestamp = strtotime($tache['created_at']);
        
        foreach ($etapes_requises as $etape => $description) {
            if (!in_array($etape, $etapes_existantes)) {
                $timestamp += $interval * 60; // Ajouter 15 minutes
                $date_etape = date('Y-m-d H:i:s', $timestamp);
                
                // Mettre à jour la tâche avec la date correspondante
                $date_field = 'date_' . ($etape === 'acceptee' ? 'acceptation' : 
                                       ($etape === 'en_route' ? 'en_route' : 
                                       ($etape === 'arrive' ? 'arrivee' : 'collecte')));
                
                $sql = "UPDATE taches_collecte SET $date_field = :date WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':date' => $date_etape, ':id' => $tache_id]);
                
                // Ajouter l'étape
                $sql = "INSERT INTO etapes_collecte (tache_id, etape, description, created_at) 
                        VALUES (:tache_id, :etape, :description, :created_at)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':tache_id' => $tache_id,
                    ':etape' => $etape,
                    ':description' => $description,
                    ':created_at' => $date_etape
                ]);
            }
        }
    }
    
    // Récupérer les étapes d'une tâche
    public function getEtapesCollecte($tache_id) {
        $sql = "SELECT * FROM etapes_collecte 
                WHERE tache_id = :tache_id 
                ORDER BY created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':tache_id' => $tache_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Statistiques d'un collecteur
    public function getStatsCollecteur($collecteur_id) {
        $stats = [];
        
        // Tâches totales
        $sql = "SELECT COUNT(*) as total FROM taches_collecte WHERE collecteur_id = :collecteur_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':collecteur_id' => $collecteur_id]);
        $stats['total_taches'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Tâches terminées
        $sql = "SELECT COUNT(*) as terminees FROM taches_collecte WHERE collecteur_id = :collecteur_id AND statut = 'terminee'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':collecteur_id' => $collecteur_id]);
        $stats['taches_terminees'] = $stmt->fetch(PDO::FETCH_ASSOC)['terminees'];
        
        // Tâches en cours
        $sql = "SELECT COUNT(*) as en_cours FROM taches_collecte WHERE collecteur_id = :collecteur_id AND statut IN ('acceptee', 'en_route', 'arrive', 'collectee')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':collecteur_id' => $collecteur_id]);
        $stats['taches_en_cours'] = $stmt->fetch(PDO::FETCH_ASSOC)['en_cours'];
        
        // Tâches en attente
        $sql = "SELECT COUNT(*) as en_attente FROM taches_collecte WHERE collecteur_id = :collecteur_id AND statut = 'en_attente'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':collecteur_id' => $collecteur_id]);
        $stats['taches_en_attente'] = $stmt->fetch(PDO::FETCH_ASSOC)['en_attente'];
        
        // Taux de réussite
        $stats['taux_reussite'] = $stats['total_taches'] > 0 ? 
            round(($stats['taches_terminees'] / $stats['total_taches']) * 100, 1) : 0;
        
        // Temps moyen de collecte
        $sql = "SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, date_completion)) as temps_moyen 
                FROM taches_collecte 
                WHERE collecteur_id = :collecteur_id AND statut = 'terminee' AND date_completion IS NOT NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':collecteur_id' => $collecteur_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['temps_moyen_minutes'] = $result['temps_moyen'] ? round($result['temps_moyen']) : 0;
        
        return $stats;
    }
    
    // Mettre à jour les statistiques du collecteur
    private function mettreAJourStatistiques($collecteur_id) {
        $stats = $this->getStatsCollecteur($collecteur_id);
        
        // Calculer le score de performance (basé sur taux de réussite et rapidité)
        $score_taux = $stats['taux_reussite'] / 100;
        $score_rapidite = $stats['temps_moyen_minutes'] > 0 ? 
            max(0, (120 - $stats['temps_moyen_minutes']) / 120) : 0; // 120 min = temps de référence
        $score_performance = ($score_taux * 0.7 + $score_rapidite * 0.3) * 100;
        
        $sql = "INSERT INTO statistiques_collecteurs (collecteur_id, taches_totales, taches_terminees, taux_reussite, temps_moyen_minutes, score_performance, derniere_maj) 
                VALUES (:collecteur_id, :total, :terminees, :taux, :temps, :score, NOW())
                ON DUPLICATE KEY UPDATE 
                taches_totales = :total, 
                taches_terminees = :terminees, 
                taux_reussite = :taux, 
                temps_moyen_minutes = :temps, 
                score_performance = :score, 
                derniere_maj = NOW()";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':collecteur_id' => $collecteur_id,
            ':total' => $stats['total_taches'],
            ':terminees' => $stats['taches_terminees'],
            ':taux' => $stats['taux_reussite'],
            ':temps' => $stats['temps_moyen_minutes'],
            ':score' => round($score_performance, 1)
        ]);
    }
    
    // Récupérer le collecteur d'une tâche
    private function getCollecteurByTache($tache_id) {
        $sql = "SELECT collecteur_id FROM taches_collecte WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $tache_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['collecteur_id'] : null;
    }
    
    // Récupérer les statistiques de tous les collecteurs (pour classement)
    public function getClassementCollecteurs() {
        $sql = "SELECT u.nom, u.prenom, s.* 
                FROM statistiques_collecteurs s
                JOIN users u ON s.collecteur_id = u.id
                WHERE u.role = 'collecteur'
                ORDER BY s.score_performance DESC, s.taux_reussite DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Récupérer le statut en temps réel d'une tâche pour le ménage
    public function getStatutTacheTempsReel($tache_id) {
        $sql = "SELECT t.*, u.nom as collecteur_nom, u.prenom as collecteur_prenom, u.telephone as collecteur_tel
                FROM taches_collecte t
                JOIN users u ON t.collecteur_id = u.id
                WHERE t.id = :tache_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':tache_id' => $tache_id]);
        $tache = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tache) {
            $tache['etapes'] = $this->getEtapesCollecte($tache_id);
        }
        
        return $tache;
    }
    
    // Récupérer les tâches par période
    public function getTachesParPeriode($collecteur_id, $periode) {
        $sql = "";
        
        switch ($periode) {
            case 'today':
                $sql = "SELECT COUNT(*) as count FROM taches_collecte 
                        WHERE collecteur_id = :collecteur_id 
                        AND DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $sql = "SELECT COUNT(*) as count FROM taches_collecte 
                        WHERE collecteur_id = :collecteur_id 
                        AND statut = 'terminee'
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $sql = "SELECT COUNT(*) as count FROM taches_collecte 
                        WHERE collecteur_id = :collecteur_id 
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            default:
                return 0;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':collecteur_id' => $collecteur_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] ?? 0;
    }
    
    // Récupérer les tâches par statut
    public function getTachesParStatut($collecteur_id, $statuts) {
        if (is_array($statuts)) {
            $placeholders = str_repeat('?,', count($statuts) - 1) . '?';
            $sql = "SELECT COUNT(*) as count FROM taches_collecte 
                    WHERE collecteur_id = ? AND statut IN ($placeholders)";
            $params = array_merge([$collecteur_id], $statuts);
        } else {
            $sql = "SELECT COUNT(*) as count FROM taches_collecte 
                    WHERE collecteur_id = ? AND statut = ?";
            $params = [$collecteur_id, $statuts];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] ?? 0;
    }
}
?> 