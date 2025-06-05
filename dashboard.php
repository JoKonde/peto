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
require_once 'classes/Poubelle.php';
require_once 'classes/Collecteur.php';
require_once 'classes/Admin.php';

$user = new User();
$poubelle = new Poubelle();
$collecteur = new Collecteur();
$admin = new Admin();
$user_role = $_SESSION['user_role'];
$user_nom = $_SESSION['user_nom'];
$user_prenom = $_SESSION['user_prenom'];
$user_id = $_SESSION['user_id'];

// Récupérer les statistiques selon le rôle
$stats = [];
if ($user_role == 'admin') {
    $stats = $admin->getStatsGlobales();
    $alertes = $admin->getAlertes();
    $top_collecteurs = $admin->getTopCollecteurs(3);
    $top_menages = $admin->getTopMenages(3);
} elseif ($user_role == 'menage') {
    $stats = $poubelle->getStatsMenage($user_id);
} elseif ($user_role == 'collecteur') {
    $stats_collecteur = $collecteur->getStatsCollecteur($user_id);
    $stats = [
        'taches_du_jour' => $collecteur->getTachesParPeriode($user_id, 'today'),
        'terminees_semaine' => $collecteur->getTachesParPeriode($user_id, 'week'),
        'taux_reussite' => $stats_collecteur['taux_reussite'] ?? 0,
        'temps_moyen' => $stats_collecteur['temps_moyen_minutes'] ?? 0,
        'en_attente' => $collecteur->getTachesParStatut($user_id, 'en_attente'),
        'en_cours' => $collecteur->getTachesParStatut($user_id, ['acceptee', 'en_route', 'arrive', 'collectee'])
    ];
}

$page_title = 'Tableau de Bord';

// Contenu de la page
ob_start();
?>

<style>
    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .welcome-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        text-align: center;
    }
    
    .welcome-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .welcome-subtitle {
        font-size: 1.2rem;
        opacity: 0.9;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.5rem;
        color: white;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.9rem;
    }
    
    .admin-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .admin-actions {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .admin-actions h3 {
        color: #333;
        margin-bottom: 1.5rem;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .action-btn {
        display: block;
        width: 100%;
        padding: 1rem;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        text-decoration: none;
        border-radius: 10px;
        font-weight: 600;
        text-align: center;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    
    .action-btn i {
        margin-right: 0.5rem;
    }
    
    .alerts-section {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .alerts-section h3 {
        color: #333;
        margin-bottom: 1.5rem;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .alert-item {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .alert-urgent {
        background: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }
    
    .alert-attention {
        background: #fff3cd;
        color: #856404;
        border-left: 4px solid #ffc107;
    }
    
    .alert-icon {
        font-size: 1.2rem;
    }
    
    .top-performers {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }
    
    .top-performers h3 {
        color: #333;
        margin-bottom: 1.5rem;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .performer-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .performer-item:last-child {
        border-bottom: none;
    }
    
    .performer-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .performer-rank {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
    }
    
    .performer-stats {
        text-align: right;
        font-size: 0.9rem;
        color: #666;
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .quick-action {
        background: white;
        padding: 1.5rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        text-align: center;
        text-decoration: none;
        color: #333;
        transition: all 0.3s ease;
    }
    
    .quick-action:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        color: #333;
    }
    
    .quick-action-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.2rem;
    }
    
    .quick-action-title {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .quick-action-desc {
        font-size: 0.9rem;
        color: #666;
    }
    
    @media (max-width: 768px) {
        .admin-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .quick-actions {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dashboard-container">
    <!-- Section de bienvenue -->
    <div class="welcome-section">
        <h1 class="welcome-title">
            Bienvenue, <?php echo htmlspecialchars($user_prenom . ' ' . $user_nom); ?> !
        </h1>
        <p class="welcome-subtitle">
            <?php 
            if ($user_role == 'admin') {
                echo "Tableau de bord administrateur - Gérez votre système PETO";
            } elseif ($user_role == 'menage') {
                echo "Gérez vos poubelles et suivez vos collectes";
            } else {
                echo "Consultez vos tâches et votre performance";
            }
            ?>
        </p>
    </div>

    <?php if ($user_role == 'admin'): ?>
        <!-- Dashboard Admin -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $stats['users_menage'] ?? 0; ?></div>
                <div class="stat-label">Ménages</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-number"><?php echo $stats['users_collecteur'] ?? 0; ?></div>
                <div class="stat-label">Collecteurs</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f39c12, #fdcb6e);">
                    <i class="fas fa-trash"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_poubelles'] ?? 0; ?></div>
                <div class="stat-label">Total Poubelles</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #e74c3c, #fd79a8);">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-number"><?php echo $stats['poubelles_alertes'] ?? 0; ?></div>
                <div class="stat-label">Alertes Actives</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_taches'] ?? 0; ?></div>
                <div class="stat-label">Total Tâches</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #1abc9c, #16a085);">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-number"><?php echo $stats['taux_reussite_global'] ?? 0; ?>%</div>
                <div class="stat-label">Taux de Réussite</div>
            </div>
        </div>

        <div class="admin-grid">
            <div class="alerts-section">
                <h3><i class="fas fa-bell"></i> Alertes & Notifications</h3>
                <?php if (!empty($alertes)): ?>
                    <?php foreach ($alertes as $alerte): ?>
                        <div class="alert-item alert-<?php echo $alerte['type']; ?>">
                            <i class="alert-icon fas fa-<?php echo $alerte['type'] == 'urgent' ? 'exclamation-triangle' : 'info-circle'; ?>"></i>
                            <span><?php echo htmlspecialchars($alerte['message']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert-item" style="background: #d4edda; color: #155724; border-left: 4px solid #28a745;">
                        <i class="alert-icon fas fa-check-circle"></i>
                        <span>Aucune alerte - Tout fonctionne normalement</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="admin-actions">
                <h3><i class="fas fa-cogs"></i> Actions Rapides</h3>
                <a href="admin-collecteurs.php" class="action-btn">
                    <i class="fas fa-truck"></i>
                    Gérer les Collecteurs
                </a>
                <a href="admin-menages.php" class="action-btn">
                    <i class="fas fa-home"></i>
                    Gérer les Ménages
                </a>
                <a href="classement-collecteurs.php" class="action-btn">
                    <i class="fas fa-trophy"></i>
                    Voir le Classement
                </a>
                <a href="profil.php" class="action-btn">
                    <i class="fas fa-user-cog"></i>
                    Mon Profil
                </a>
            </div>
        </div>

        <?php if (!empty($top_collecteurs)): ?>
            <div class="top-performers">
                <h3><i class="fas fa-star"></i> Top Collecteurs</h3>
                <?php foreach ($top_collecteurs as $index => $collecteur): ?>
                    <div class="performer-item">
                        <div class="performer-info">
                            <div class="performer-rank"><?php echo $index + 1; ?></div>
                            <div>
                                <strong><?php echo htmlspecialchars($collecteur['prenom'] . ' ' . $collecteur['nom']); ?></strong>
                            </div>
                        </div>
                        <div class="performer-stats">
                            <div><strong><?php echo $collecteur['taches_terminees']; ?></strong> tâches</div>
                            <div><?php echo $collecteur['taux_reussite']; ?>% réussite</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($user_role == 'menage'): ?>
        <!-- Dashboard Ménage -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                    <i class="fas fa-trash"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_poubelles']; ?></div>
                <div class="stat-label">Poubelles Créées</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #e74c3c, #fd79a8);">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-number"><?php echo $stats['poubelles_en_attente']; ?></div>
                <div class="stat-label">En Alerte</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo $stats['poubelles_evacuees']; ?></div>
                <div class="stat-label">Évacuées</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f39c12, #fdcb6e);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo $stats['temps_attente_moyen']; ?>h</div>
                <div class="stat-label">Temps d'Attente Moyen</div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="menage-poubelles.php" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="quick-action-title">Créer une Poubelle</div>
                <div class="quick-action-desc">Ajouter une nouvelle poubelle</div>
            </a>
            
            <a href="menage-collectes.php" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="quick-action-title">Mes Collectes</div>
                <div class="quick-action-desc">Suivre l'état des collectes</div>
            </a>
            
            <a href="profil.php" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="quick-action-title">Mon Profil</div>
                <div class="quick-action-desc">Gérer mes informations</div>
            </a>
        </div>

    <?php else: ?>
        <!-- Dashboard Collecteur -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-number"><?php echo $stats['taches_du_jour']; ?></div>
                <div class="stat-label">Tâches du Jour</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo $stats['terminees_semaine']; ?></div>
                <div class="stat-label">Complétées cette Semaine</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f39c12, #fdcb6e);">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-number"><?php echo $stats['taux_reussite']; ?>%</div>
                <div class="stat-label">Taux de Réussite</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo $stats['temps_moyen']; ?></div>
                <div class="stat-label">Minutes Moyennes</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #e74c3c, #fd79a8);">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-number"><?php echo $stats['en_attente']; ?></div>
                <div class="stat-label">En Attente</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #1abc9c, #16a085);">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-number"><?php echo $stats['en_cours']; ?></div>
                <div class="stat-label">En Cours</div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="collecteur-dashboard.php" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="quick-action-title">Mes Tâches</div>
                <div class="quick-action-desc">Gérer mes collectes</div>
            </a>
            
            <a href="classement-collecteurs.php" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="quick-action-title">Classement</div>
                <div class="quick-action-desc">Voir ma position</div>
            </a>
            
            <a href="profil.php" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="quick-action-title">Mon Profil</div>
                <div class="quick-action-desc">Mes informations</div>
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include 'components/layout.php';
?> 