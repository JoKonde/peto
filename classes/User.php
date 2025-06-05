<?php
require_once 'Database.php';

class User {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Créer un nouveau ménage
    public function createMenage($data) {
        $sql = "INSERT INTO users (nom, prenom, telephone, email, adresse, commune, quartier, avenue, numero, password, role, statut) 
                VALUES (:nom, :prenom, :telephone, :email, :adresse, :commune, :quartier, :avenue, :numero, :password, 'menage', 'actif')";
        
        $stmt = $this->db->prepare($sql);
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        return $stmt->execute([
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
    }
    
    // Connexion utilisateur avec vérification du statut
    public function login($email, $password) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Vérifier si l'utilisateur est suspendu
            if ($user['statut'] === 'suspendu') {
                return [
                    'success' => false,
                    'message' => 'Votre compte a été suspendu par l\'administrateur. Contactez le support pour plus d\'informations.',
                    'suspended' => true
                ];
            }
            
            return [
                'success' => true,
                'user' => $user
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Email ou mot de passe incorrect.'
        ];
    }
    
    // Vérifier si un utilisateur est suspendu
    public function isUserSuspended($user_id) {
        $sql = "SELECT statut FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user && $user['statut'] === 'suspendu';
    }
    
    // Suspendre un utilisateur
    public function suspendUser($user_id) {
        $sql = "UPDATE users SET statut = 'suspendu' WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $user_id]);
    }
    
    // Activer un utilisateur
    public function activateUser($user_id) {
        $sql = "UPDATE users SET statut = 'actif' WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $user_id]);
    }
    
    // Récupérer un utilisateur par ID
    public function getUserById($id) {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Compter les utilisateurs par rôle
    public function countByRole($role) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE role = :role";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':role' => $role]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    // Vérifier si un email existe déjà (pour la modification de profil)
    public function emailExists($email, $exclude_user_id = null) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = :email";
        $params = [':email' => $email];
        
        if ($exclude_user_id) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $exclude_user_id;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    
    // Mettre à jour le profil utilisateur
    public function updateProfile($user_id, $nom, $prenom, $telephone, $email, $adresse, $commune, $quartier, $avenue, $numero) {
        $sql = "UPDATE users SET 
                nom = :nom, 
                prenom = :prenom, 
                telephone = :telephone, 
                email = :email, 
                adresse = :adresse, 
                commune = :commune, 
                quartier = :quartier, 
                avenue = :avenue, 
                numero = :numero 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':telephone' => $telephone,
            ':email' => $email,
            ':adresse' => $adresse,
            ':commune' => $commune,
            ':quartier' => $quartier,
            ':avenue' => $avenue,
            ':numero' => $numero,
            ':id' => $user_id
        ]);
    }
    
    // Vérifier le mot de passe actuel
    public function verifyPassword($user_id, $password) {
        $sql = "SELECT password FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            return password_verify($password, $user['password']);
        }
        return false;
    }
    
    // Mettre à jour le mot de passe
    public function updatePassword($user_id, $new_password) {
        $sql = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
        return $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $user_id
        ]);
    }
}
?> 