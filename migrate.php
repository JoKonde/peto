<?php
require_once 'classes/Database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>Migration de la base de données PETO</h2>";
    
    // Vérifier si la colonne statut existe dans users
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'statut'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN statut ENUM('actif', 'suspendu') DEFAULT 'actif'");
        echo "✅ Colonne 'statut' ajoutée à la table users<br>";
    } else {
        echo "ℹ️ Colonne 'statut' existe déjà dans users<br>";
    }
    
    // Migrer les données existantes (utilisateurs avec [SUSPENDU] dans l'adresse)
    $stmt = $pdo->prepare("UPDATE users SET statut = 'suspendu' WHERE adresse LIKE '%[SUSPENDU]%'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "✅ " . $stmt->rowCount() . " utilisateur(s) marqué(s) comme suspendu(s)<br>";
    }
    
    // Nettoyer les adresses (supprimer [SUSPENDU])
    $stmt = $pdo->prepare("UPDATE users SET adresse = REPLACE(adresse, '[SUSPENDU] ', '') WHERE adresse LIKE '%[SUSPENDU]%'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "✅ Adresses nettoyées (suppression des marqueurs [SUSPENDU])<br>";
    }
    
    // Vérifier si la colonne date_acceptation existe
    $stmt = $pdo->prepare("SHOW COLUMNS FROM taches_collecte LIKE 'date_acceptation'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE taches_collecte ADD COLUMN date_acceptation DATETIME NULL");
        echo "✅ Colonne 'date_acceptation' ajoutée<br>";
    } else {
        echo "ℹ️ Colonne 'date_acceptation' existe déjà<br>";
    }
    
    // Vérifier si la colonne date_en_route existe
    $stmt = $pdo->prepare("SHOW COLUMNS FROM taches_collecte LIKE 'date_en_route'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE taches_collecte ADD COLUMN date_en_route DATETIME NULL");
        echo "✅ Colonne 'date_en_route' ajoutée<br>";
    } else {
        echo "ℹ️ Colonne 'date_en_route' existe déjà<br>";
    }
    
    // Vérifier si la colonne date_arrivee existe
    $stmt = $pdo->prepare("SHOW COLUMNS FROM taches_collecte LIKE 'date_arrivee'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE taches_collecte ADD COLUMN date_arrivee DATETIME NULL");
        echo "✅ Colonne 'date_arrivee' ajoutée<br>";
    } else {
        echo "ℹ️ Colonne 'date_arrivee' existe déjà<br>";
    }
    
    // Vérifier si la colonne date_collecte existe
    $stmt = $pdo->prepare("SHOW COLUMNS FROM taches_collecte LIKE 'date_collecte'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE taches_collecte ADD COLUMN date_collecte DATETIME NULL");
        echo "✅ Colonne 'date_collecte' ajoutée<br>";
    } else {
        echo "ℹ️ Colonne 'date_collecte' existe déjà<br>";
    }
    
    // Mettre à jour l'ENUM statut de taches_collecte
    try {
        $pdo->exec("ALTER TABLE taches_collecte MODIFY COLUMN statut ENUM('en_attente', 'acceptee', 'en_route', 'arrive', 'collectee', 'terminee') DEFAULT 'en_attente'");
        echo "✅ ENUM statut de taches_collecte mis à jour<br>";
    } catch (Exception $e) {
        echo "ℹ️ ENUM statut déjà à jour<br>";
    }
    
    // Créer la table etapes_collecte si elle n'existe pas
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'etapes_collecte'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE etapes_collecte (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tache_id INT NOT NULL,
                etape ENUM('acceptee', 'en_route', 'arrive', 'collectee', 'terminee') NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (tache_id) REFERENCES taches_collecte(id) ON DELETE CASCADE
            )
        ");
        echo "✅ Table 'etapes_collecte' créée<br>";
    } else {
        echo "ℹ️ Table 'etapes_collecte' existe déjà<br>";
    }
    
    // Créer la table statistiques_collecteurs si elle n'existe pas
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'statistiques_collecteurs'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE statistiques_collecteurs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                collecteur_id INT NOT NULL UNIQUE,
                taches_totales INT DEFAULT 0,
                taches_terminees INT DEFAULT 0,
                taux_reussite DECIMAL(5,2) DEFAULT 0,
                temps_moyen_minutes INT DEFAULT 0,
                score_performance DECIMAL(5,2) DEFAULT 0,
                derniere_maj TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (collecteur_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        echo "✅ Table 'statistiques_collecteurs' créée<br>";
    } else {
        echo "ℹ️ Table 'statistiques_collecteurs' existe déjà<br>";
    }
    
    echo "<br><strong>✅ Migration terminée avec succès !</strong>";
    
} catch (Exception $e) {
    echo "<br><strong>❌ Erreur lors de la migration :</strong> " . $e->getMessage();
}
?> 