<?php
require_once 'Database.php';
require_once 'User.php';

class Admin {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // ==================== GESTION DES COLLECTEURS ====================
    
    // Créer un nouveau collecteur
    public function createCollecteur($data) {
        $sql = "INSERT INTO users (nom, prenom, telephone, email, adresse, commune, quartier, avenue, numero, password, role) 
                VALUES (:nom, :prenom, :telephone, :email, :adresse, :commune, :quartier, :avenue, :numero, :password, 'collecteur')";
        
        $stmt = $this->db->prepare($sql);
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $result = $stmt->execute([
            ':nom' => $data['nom'],
            ':prenom' => $data['prenom'],
            ':telephone' => $data['telephone'],
            ':email' => $data['email'],
            ':adresse' => $data['adresse'],
            ':commune' => $data['commune'],
            ':quartier' => $data['quartier'],
            ':avenue' => $data['avenue'],
            ':numero' => $data['numero'],
            ':password' => $hashedPassword
        ]);
        
        if ($result) {
            $collecteur_id = $this->db->lastInsertId();
            // Initialiser les statistiques du collecteur
            $this->initStatsCollecteur($collecteur_id);
            return $collecteur_id;
        }
        return false;
    }
    
    // Initialiser les statistiques d'un collecteur
    private function initStatsCollecteur($collecteur_id) {
        $sql = "INSERT INTO statistiques_collecteurs (collecteur_id) VALUES (:collecteur_id)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':collecteur_id' => $collecteur_id]);
    }
    
    // Récupérer tous les collecteurs avec leurs statistiques
    public function getAllCollecteurs() {
        $sql = "SELECT u.*, 
                       COALESCE(s.taches_totales, 0) as taches_totales,
                       COALESCE(s.taches_terminees, 0) as taches_terminees,
                       COALESCE(s.taux_reussite, 0) as taux_reussite,
                       COALESCE(s.temps_moyen_minutes, 0) as temps_moyen_minutes,
                       COALESCE(s.score_performance, 0) as score_performance,
                       (SELECT COUNT(*) FROM taches_collecte WHERE collecteur_id = u.id AND statut IN ('en_attente', 'acceptee', 'en_route', 'arrive', 'collectee')) as taches_actives,
                       (SELECT MAX(created_at) FROM taches_collecte WHERE collecteur_id = u.id) as derniere_activite
                FROM users u 
                LEFT JOIN statistiques_collecteurs s ON u.id = s.collecteur_id
                WHERE u.role = 'collecteur' 
                ORDER BY u.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Récupérer un collecteur par ID avec détails
    public function getCollecteurById($id) {
        $sql = "SELECT u.*, 
                       COALESCE(s.taches_totales, 0) as taches_totales,
                       COALESCE(s.taches_terminees, 0) as taches_terminees,
                       COALESCE(s.taux_reussite, 0) as taux_reussite,
                       COALESCE(s.temps_moyen_minutes, 0) as temps_moyen_minutes,
                       COALESCE(s.score_performance, 0) as score_performance
                FROM users u 
                LEFT JOIN statistiques_collecteurs s ON u.id = s.collecteur_id
                WHERE u.id = :id AND u.role = 'collecteur'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Suspendre/Activer un collecteur
    public function toggleCollecteurStatus($id, $status) {
        $user = new User();
        
        if ($status === 'suspendu') {
            return $user->suspendUser($id);
        } else {
            return $user->activateUser($id);
        }
    }
    
    // Supprimer un collecteur
    public function deleteCollecteur($id) {
        // Vérifier s'il a des tâches actives
        $sql = "SELECT COUNT(*) as taches_actives FROM taches_collecte 
                WHERE collecteur_id = :id AND statut IN ('en_attente', 'acceptee', 'en_route', 'arrive', 'collectee')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['taches_actives'] > 0) {
            return ['success' => false, 'message' => 'Impossible de supprimer : le collecteur a des tâches actives'];
        }
        
        // Supprimer le collecteur (les tâches terminées seront conservées grâce à ON DELETE CASCADE)
        $sql = "DELETE FROM users WHERE id = :id AND role = 'collecteur'";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([':id' => $id]);
        
        return ['success' => $success, 'message' => $success ? 'Collecteur supprimé avec succès' : 'Erreur lors de la suppression'];
    }
    
    // Récupérer les tâches d'un collecteur
    public function getCollecteurTaches($collecteur_id, $limit = null) {
        $sql = "SELECT t.*, 
                       p.type as poubelle_type, 
                       p.description as poubelle_description,
                       u.nom as menage_nom, 
                       u.prenom as menage_prenom, 
                       u.telephone as menage_tel,
                       CONCAT(u.adresse, ', ', u.commune, ', ', u.quartier) as adresse_complete,
                       TIMESTAMPDIFF(HOUR, t.created_at, NOW()) as heures_depuis_creation
                FROM taches_collecte t
                JOIN poubelles p ON t.poubelle_id = p.id
                JOIN users u ON p.menage_id = u.id
                WHERE t.collecteur_id = :collecteur_id
                ORDER BY t.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':collecteur_id' => $collecteur_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ==================== GESTION DES MÉNAGES ====================
    
    // Récupérer tous les ménages avec leurs statistiques
    public function getAllMenages() {
        $sql = "SELECT u.*, 
                       COUNT(DISTINCT p.id) as total_poubelles,
                       COUNT(DISTINCT CASE WHEN p.alerte_pleine = 1 THEN p.id END) as poubelles_alertes,
                       COUNT(DISTINCT CASE WHEN p.date_collecte IS NOT NULL THEN p.id END) as poubelles_collectees,
                       COUNT(DISTINCT t.id) as total_collectes,
                       MAX(p.created_at) as derniere_poubelle,
                       MAX(t.date_completion) as derniere_collecte
                FROM users u 
                LEFT JOIN poubelles p ON u.id = p.menage_id
                LEFT JOIN taches_collecte t ON p.id = t.poubelle_id AND t.statut = 'terminee'
                WHERE u.role = 'menage' 
                GROUP BY u.id
                ORDER BY u.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Récupérer un ménage par ID avec détails
    public function getMenageById($id) {
        $sql = "SELECT u.*, 
                       COUNT(DISTINCT p.id) as total_poubelles,
                       COUNT(DISTINCT CASE WHEN p.alerte_pleine = 1 THEN p.id END) as poubelles_alertes,
                       COUNT(DISTINCT CASE WHEN p.date_collecte IS NOT NULL THEN p.id END) as poubelles_collectees
                FROM users u 
                LEFT JOIN poubelles p ON u.id = p.menage_id
                WHERE u.id = :id AND u.role = 'menage'
                GROUP BY u.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Récupérer les poubelles d'un ménage
    public function getMenagePoubelles($menage_id) {
        $sql = "SELECT p.*, 
                       t.statut as collecte_statut,
                       t.date_assignation,
                       t.date_completion,
                       u.nom as collecteur_nom,
                       u.prenom as collecteur_prenom,
                       TIMESTAMPDIFF(HOUR, p.date_alerte, NOW()) as heures_attente
                FROM poubelles p
                LEFT JOIN taches_collecte t ON p.id = t.poubelle_id
                LEFT JOIN users u ON t.collecteur_id = u.id
                WHERE p.menage_id = :menage_id
                ORDER BY p.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':menage_id' => $menage_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Suspendre/Activer un ménage
    public function toggleMenageStatus($id, $status) {
        $user = new User();
        
        if ($status === 'suspendu') {
            return $user->suspendUser($id);
        } else {
            return $user->activateUser($id);
        }
    }
    
    // ==================== STATISTIQUES GÉNÉRALES ====================
    
    // Statistiques globales du système
    public function getStatsGlobales() {
        $stats = [];
        
        // Utilisateurs
        $sql = "SELECT role, COUNT(*) as total FROM users GROUP BY role";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $user) {
            $stats['users_' . $user['role']] = $user['total'];
        }
        
        // Poubelles
        $sql = "SELECT 
                    COUNT(*) as total_poubelles,
                    COUNT(CASE WHEN alerte_pleine = 1 THEN 1 END) as poubelles_alertes,
                    COUNT(CASE WHEN date_collecte IS NOT NULL THEN 1 END) as poubelles_collectees,
                    COUNT(CASE WHEN type = 'organique' THEN 1 END) as poubelles_organiques,
                    COUNT(CASE WHEN type = 'plastique' THEN 1 END) as poubelles_plastiques,
                    COUNT(CASE WHEN type = 'mixte' THEN 1 END) as poubelles_mixtes
                FROM poubelles";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $poubelles = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats = array_merge($stats, $poubelles);
        
        // Tâches
        $sql = "SELECT 
                    COUNT(*) as total_taches,
                    COUNT(CASE WHEN statut = 'terminee' THEN 1 END) as taches_terminees,
                    COUNT(CASE WHEN statut IN ('en_attente', 'acceptee', 'en_route', 'arrive', 'collectee') THEN 1 END) as taches_actives,
                    AVG(CASE WHEN statut = 'terminee' AND date_completion IS NOT NULL AND date_assignation IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, date_assignation, date_completion) END) as temps_moyen_minutes
                FROM taches_collecte";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $taches = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats = array_merge($stats, $taches);
        
        // Calculer le taux de réussite global
        if ($stats['total_taches'] > 0) {
            $stats['taux_reussite_global'] = round(($stats['taches_terminees'] / $stats['total_taches']) * 100, 2);
        } else {
            $stats['taux_reussite_global'] = 0;
        }
        
        return $stats;
    }
    
    // Statistiques par période
    public function getStatsPeriode($periode = '7') {
        $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as nouvelles_taches,
                    COUNT(CASE WHEN statut = 'terminee' THEN 1 END) as taches_terminees
                FROM taches_collecte 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL :periode DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':periode' => $periode]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Top collecteurs
    public function getTopCollecteurs($limit = 5) {
        $sql = "SELECT u.nom, u.prenom, 
                       s.taches_terminees, 
                       s.taux_reussite, 
                       s.score_performance,
                       s.temps_moyen_minutes
                FROM users u
                JOIN statistiques_collecteurs s ON u.id = s.collecteur_id
                WHERE u.role = 'collecteur'
                ORDER BY s.score_performance DESC, s.taches_terminees DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Ménages les plus actifs
    public function getTopMenages($limit = 5) {
        $sql = "SELECT u.nom, u.prenom, u.commune, u.quartier,
                       COUNT(DISTINCT p.id) as total_poubelles,
                       COUNT(DISTINCT t.id) as total_collectes
                FROM users u
                LEFT JOIN poubelles p ON u.id = p.menage_id
                LEFT JOIN taches_collecte t ON p.id = t.poubelle_id AND t.statut = 'terminee'
                WHERE u.role = 'menage'
                GROUP BY u.id
                ORDER BY total_poubelles DESC, total_collectes DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Alertes et notifications pour l'admin
    public function getAlertes() {
        $alertes = [];
        
        // Poubelles en attente depuis plus de 24h
        $sql = "SELECT COUNT(*) as count FROM poubelles 
                WHERE alerte_pleine = 1 AND date_alerte < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['count'] > 0) {
            $alertes[] = [
                'type' => 'urgent',
                'message' => $result['count'] . ' poubelle(s) en attente depuis plus de 24h',
                'count' => $result['count']
            ];
        }
        
        // Collecteurs inactifs
        $sql = "SELECT COUNT(*) as count FROM users u
                LEFT JOIN taches_collecte t ON u.id = t.collecteur_id AND t.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                WHERE u.role = 'collecteur' AND t.id IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['count'] > 0) {
            $alertes[] = [
                'type' => 'attention',
                'message' => $result['count'] . ' collecteur(s) sans activité cette semaine',
                'count' => $result['count']
            ];
        }
        
        return $alertes;
    }
}
?> 