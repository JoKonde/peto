<?php
// Configuration de la base de données
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'peto_simple';

try {
    // Connexion sans spécifier la base de données
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer la base de données si elle n'existe pas
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8 COLLATE utf8_general_ci");
    echo "✅ Base de données '$dbname' créée avec succès<br>";
    
    // Se connecter à la base de données
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer la table users
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        prenom VARCHAR(100) NOT NULL,
        telephone VARCHAR(20) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        adresse TEXT NOT NULL,
        commune VARCHAR(100) NOT NULL,
        quartier VARCHAR(100) NOT NULL,
        avenue VARCHAR(100) NOT NULL,
        numero VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'menage', 'collecteur') NOT NULL,
        statut ENUM('actif', 'suspendu') DEFAULT 'actif',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "✅ Table 'users' créée avec succès<br>";
    
    // Créer la table poubelles
    $sql = "CREATE TABLE IF NOT EXISTS poubelles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        menage_id INT NOT NULL,
        type ENUM('organique', 'plastique', 'mixte') NOT NULL,
        description TEXT,
        statut ENUM('vide', 'pleine') DEFAULT 'vide',
        alerte_pleine BOOLEAN DEFAULT FALSE,
        date_alerte DATETIME NULL,
        date_collecte DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (menage_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "✅ Table 'poubelles' créée avec succès<br>";
    
    // Créer la table taches_collecte
    $sql = "CREATE TABLE IF NOT EXISTS taches_collecte (
        id INT AUTO_INCREMENT PRIMARY KEY,
        collecteur_id INT NOT NULL,
        poubelle_id INT NOT NULL,
        menage_nom VARCHAR(200) NOT NULL,
        adresse TEXT NOT NULL,
        type_dechet ENUM('organique', 'plastique', 'mixte') NOT NULL,
        statut ENUM('en_attente', 'acceptee', 'en_route', 'arrive', 'collectee', 'terminee') DEFAULT 'en_attente',
        date_assignation DATETIME DEFAULT CURRENT_TIMESTAMP,
        date_acceptation DATETIME NULL,
        date_en_route DATETIME NULL,
        date_arrivee DATETIME NULL,
        date_collecte DATETIME NULL,
        date_completion DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (collecteur_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (poubelle_id) REFERENCES poubelles(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "✅ Table 'taches_collecte' créée avec succès<br>";
    
    // Créer la table etapes_collecte pour le suivi en temps réel
    $sql = "CREATE TABLE IF NOT EXISTS etapes_collecte (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tache_id INT NOT NULL,
        etape ENUM('acceptee', 'en_route', 'arrive', 'collectee', 'terminee') NOT NULL,
        description TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (tache_id) REFERENCES taches_collecte(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "✅ Table 'etapes_collecte' créée avec succès<br>";
    
    // Créer la table statistiques_collecteurs
    $sql = "CREATE TABLE IF NOT EXISTS statistiques_collecteurs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        collecteur_id INT NOT NULL UNIQUE,
        taches_totales INT DEFAULT 0,
        taches_terminees INT DEFAULT 0,
        taux_reussite DECIMAL(5,2) DEFAULT 0.00,
        temps_moyen_minutes INT DEFAULT 0,
        score_performance DECIMAL(5,2) DEFAULT 0.00,
        derniere_maj TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (collecteur_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "✅ Table 'statistiques_collecteurs' créée avec succès<br>";
    
    // Créer le compte admin par défaut
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT IGNORE INTO users (nom, prenom, telephone, email, adresse, commune, quartier, avenue, numero, password, role, statut) 
            VALUES ('Admin', 'Système', '0000000000', 'admin@peto.cd', 'Bureau Central', 'Kinshasa', 'Gombe', 'Avenue Admin', '1', ?, 'admin', 'actif')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$adminPassword]);
    echo "✅ Compte admin créé (email: admin@peto.cd, mot de passe: admin123)<br>";
    
    // Créer un collecteur de test
    $collecteurPassword = password_hash('collecteur123', PASSWORD_DEFAULT);
    $sql = "INSERT IGNORE INTO users (nom, prenom, telephone, email, adresse, commune, quartier, avenue, numero, password, role, statut) 
            VALUES ('Mbuyi', 'Joseph', '0812345678', 'collecteur@peto.cd', 'Zone Nord', 'Kinshasa', 'Lemba', 'Avenue Collecte', '15', ?, 'collecteur', 'actif')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$collecteurPassword]);
    echo "✅ Compte collecteur créé (email: collecteur@peto.cd, mot de passe: collecteur123)<br>";
    
    // Créer un deuxième collecteur pour tester la répartition
    $collecteur2Password = password_hash('collecteur456', PASSWORD_DEFAULT);
    $sql = "INSERT IGNORE INTO users (nom, prenom, telephone, email, adresse, commune, quartier, avenue, numero, password, role, statut) 
            VALUES ('Kasongo', 'Pierre', '0823456789', 'collecteur2@peto.cd', 'Zone Sud', 'Kinshasa', 'Kalamu', 'Avenue Sud', '25', ?, 'collecteur', 'actif')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$collecteur2Password]);
    echo "✅ Deuxième collecteur créé (email: collecteur2@peto.cd, mot de passe: collecteur456)<br>";
    
    echo "<br><h3>🎉 Installation terminée avec succès !</h3>";
    echo "<p><strong>Comptes disponibles :</strong></p>";
    echo "<ul>";
    echo "<li>Admin : admin@peto.cd / admin123</li>";
    echo "<li>Collecteur 1 : collecteur@peto.cd / collecteur123</li>";
    echo "<li>Collecteur 2 : collecteur2@peto.cd / collecteur456</li>";
    echo "<li>Ménages : Créez vos comptes via l'inscription</li>";
    echo "</ul>";
    echo "<p><a href='index.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Accéder au site</a></p>";
    
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?> 