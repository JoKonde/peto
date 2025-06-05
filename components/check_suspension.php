<?php
// Vérification de suspension - À inclure dans toutes les pages protégées
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    require_once 'classes/User.php';
    
    $user = new User();
    if ($user->isUserSuspended($_SESSION['user_id'])) {
        // Utilisateur suspendu - déconnecter et rediriger
        session_destroy();
        header('Location: login.php?suspended=1');
        exit();
    }
} catch (Exception $e) {
    // En cas d'erreur (base de données non accessible, etc.), 
    // on laisse l'utilisateur continuer pour éviter les boucles
    error_log("Erreur check_suspension: " . $e->getMessage());
}
?> 