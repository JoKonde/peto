<?php
session_start();

// Vérifier la suspension
require_once 'components/check_suspension.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'classes/User.php';

$user = new User();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$message = '';
$error = '';

// Récupérer les informations actuelles de l'utilisateur
$user_info = $user->getUserById($user_id);

// Traitement du formulaire de modification
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $nom = trim($_POST['nom']);
                $prenom = trim($_POST['prenom']);
                $telephone = trim($_POST['telephone']);
                $email = trim($_POST['email']);
                $adresse = trim($_POST['adresse']);
                $commune = trim($_POST['commune']);
                $quartier = trim($_POST['quartier']);
                $avenue = trim($_POST['avenue']);
                $numero = trim($_POST['numero']);
                
                // Validation
                if (empty($nom) || empty($prenom) || empty($telephone) || empty($email)) {
                    $error = "Tous les champs obligatoires doivent être remplis.";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Format d'email invalide.";
                } else {
                    // Vérifier si l'email n'est pas déjà utilisé par un autre utilisateur
                    if ($user->emailExists($email) && $email !== $user_info['email']) {
                        $error = "Cette adresse email est déjà utilisée.";
                    } else {
                        // Mettre à jour le profil
                        if ($user->updateProfile($user_id, $nom, $prenom, $telephone, $email, $adresse, $commune, $quartier, $avenue, $numero)) {
                            $message = "Profil mis à jour avec succès !";
                            // Mettre à jour les variables de session
                            $_SESSION['user_nom'] = $nom;
                            $_SESSION['user_prenom'] = $prenom;
                            // Recharger les informations
                            $user_info = $user->getUserById($user_id);
                        } else {
                            $error = "Erreur lors de la mise à jour du profil.";
                        }
                    }
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Validation
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    $error = "Tous les champs de mot de passe sont obligatoires.";
                } elseif ($new_password !== $confirm_password) {
                    $error = "Les nouveaux mots de passe ne correspondent pas.";
                } elseif (strlen($new_password) < 6) {
                    $error = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
                } else {
                    // Vérifier l'ancien mot de passe
                    if ($user->verifyPassword($user_id, $current_password)) {
                        if ($user->updatePassword($user_id, $new_password)) {
                            $message = "Mot de passe modifié avec succès !";
                        } else {
                            $error = "Erreur lors de la modification du mot de passe.";
                        }
                    } else {
                        $error = "Mot de passe actuel incorrect.";
                    }
                }
                break;
        }
    }
}

$page_title = 'Mon Profil';

// Contenu de la page
ob_start();
?>

<style>
    .profile-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        text-align: center;
    }
    
    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 2.5rem;
    }
    
    .profile-name {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .profile-role {
        font-size: 1.2rem;
        opacity: 0.9;
        text-transform: capitalize;
    }
    
    .profile-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }
    
    .profile-section {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .section-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: block;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
    }
    
    .form-input {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #667eea;
    }
    
    .form-input:disabled {
        background: #f8f9fa;
        color: #6c757d;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        justify-content: center;
    }
    
    .btn-primary {
        background: #667eea;
        color: white;
    }
    
    .btn-primary:hover {
        background: #5a6fd8;
        transform: translateY(-1px);
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #5a6268;
    }
    
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .btn-danger:hover {
        background: #c82333;
    }
    
    .message {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        text-align: center;
    }
    
    .message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .info-display {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e9ecef;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: #333;
    }
    
    .info-value {
        color: #666;
    }
    
    .edit-toggle {
        margin-bottom: 1.5rem;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-top: 1.5rem;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem;
        border-radius: 8px;
        text-align: center;
    }
    
    .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        font-size: 0.8rem;
        opacity: 0.9;
    }
    
    @media (max-width: 768px) {
        .profile-content {
            grid-template-columns: 1fr;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="profile-container">
    <!-- En-tête du profil -->
    <div class="profile-header">
        <div class="profile-avatar">
            <?php 
            $avatar_icon = '';
            switch($user_role) {
                case 'admin': $avatar_icon = 'fas fa-shield-alt'; break;
                case 'collecteur': $avatar_icon = 'fas fa-truck'; break;
                case 'menage': $avatar_icon = 'fas fa-home'; break;
                default: $avatar_icon = 'fas fa-user';
            }
            ?>
            <i class="<?php echo $avatar_icon; ?>"></i>
        </div>
        <div class="profile-name"><?php echo htmlspecialchars($user_info['prenom'] . ' ' . $user_info['nom']); ?></div>
        <div class="profile-role"><?php echo htmlspecialchars($user_role); ?></div>
    </div>

    <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="profile-content">
        <!-- Informations personnelles -->
        <div class="profile-section">
            <h2 class="section-title">
                <i class="fas fa-user"></i>
                Informations personnelles
            </h2>
            
            <div class="edit-toggle">
                <button type="button" class="btn btn-secondary" onclick="toggleEdit('profile')">
                    <i class="fas fa-edit"></i>
                    Modifier mes informations
                </button>
            </div>

            <!-- Affichage des informations -->
            <div id="profile-display" class="info-display">
                <div class="info-item">
                    <span class="info-label">Nom :</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_info['nom']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Prénom :</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_info['prenom']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email :</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_info['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Téléphone :</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_info['telephone']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Adresse :</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_info['adresse']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Commune :</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_info['commune']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Quartier :</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_info['quartier']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Avenue :</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_info['avenue']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Numéro :</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_info['numero']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Membre depuis :</span>
                    <span class="info-value"><?php echo date('d/m/Y', strtotime($user_info['created_at'])); ?></span>
                </div>
            </div>

            <!-- Formulaire de modification -->
            <form method="POST" id="profile-edit" style="display: none;">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="nom" class="form-input" value="<?php echo htmlspecialchars($user_info['nom']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prénom *</label>
                        <input type="text" name="prenom" class="form-input" value="<?php echo htmlspecialchars($user_info['prenom']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Téléphone *</label>
                        <input type="tel" name="telephone" class="form-input" value="<?php echo htmlspecialchars($user_info['telephone']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Adresse</label>
                    <input type="text" name="adresse" class="form-input" value="<?php echo htmlspecialchars($user_info['adresse']); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Commune</label>
                        <input type="text" name="commune" class="form-input" value="<?php echo htmlspecialchars($user_info['commune']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quartier</label>
                        <input type="text" name="quartier" class="form-input" value="<?php echo htmlspecialchars($user_info['quartier']); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Avenue</label>
                        <input type="text" name="avenue" class="form-input" value="<?php echo htmlspecialchars($user_info['avenue']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Numéro</label>
                        <input type="text" name="numero" class="form-input" value="<?php echo htmlspecialchars($user_info['numero']); ?>">
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Sauvegarder
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="toggleEdit('profile')">
                        <i class="fas fa-times"></i>
                        Annuler
                    </button>
                </div>
            </form>
        </div>

        <!-- Sécurité -->
        <div class="profile-section">
            <h2 class="section-title">
                <i class="fas fa-lock"></i>
                Sécurité
            </h2>
            
            <div class="edit-toggle">
                <button type="button" class="btn btn-secondary" onclick="toggleEdit('password')">
                    <i class="fas fa-key"></i>
                    Changer le mot de passe
                </button>
            </div>

            <!-- Informations de sécurité -->
            <div id="password-display" class="info-display">
                <div class="info-item">
                    <span class="info-label">Mot de passe :</span>
                    <span class="info-value">••••••••</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Dernière connexion :</span>
                    <span class="info-value">Aujourd'hui</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Rôle :</span>
                    <span class="info-value"><?php echo ucfirst($user_role); ?></span>
                </div>
            </div>

            <!-- Formulaire de changement de mot de passe -->
            <form method="POST" id="password-edit" style="display: none;">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label class="form-label">Mot de passe actuel *</label>
                    <input type="password" name="current_password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nouveau mot de passe *</label>
                    <input type="password" name="new_password" class="form-input" required minlength="6">
                </div>

                <div class="form-group">
                    <label class="form-label">Confirmer le nouveau mot de passe *</label>
                    <input type="password" name="confirm_password" class="form-input" required minlength="6">
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Changer le mot de passe
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="toggleEdit('password')">
                        <i class="fas fa-times"></i>
                        Annuler
                    </button>
                </div>
            </form>

            <!-- Statistiques utilisateur -->
            <?php if ($user_role == 'menage'): ?>
                <?php
                require_once 'classes/Poubelle.php';
                $poubelle = new Poubelle();
                $stats = $poubelle->getStatsMenage($user_id);
                ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_poubelles']; ?></div>
                        <div class="stat-label">Poubelles créées</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['poubelles_evacuees']; ?></div>
                        <div class="stat-label">Collectes effectuées</div>
                    </div>
                </div>
            <?php elseif ($user_role == 'collecteur'): ?>
                <?php
                require_once 'classes/Collecteur.php';
                $collecteur = new Collecteur();
                $stats = $collecteur->getStatsCollecteur($user_id);
                ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_taches']; ?></div>
                        <div class="stat-label">Tâches totales</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['taux_reussite']; ?>%</div>
                        <div class="stat-label">Taux de réussite</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleEdit(section) {
    const display = document.getElementById(section + '-display');
    const edit = document.getElementById(section + '-edit');
    
    if (edit.style.display === 'none') {
        display.style.display = 'none';
        edit.style.display = 'block';
    } else {
        display.style.display = 'block';
        edit.style.display = 'none';
        // Reset form
        edit.reset();
    }
}
</script>

<?php
$content = ob_get_clean();
include 'components/layout.php';
?> 