<?php
session_start();

// Vérifier la suspension
require_once 'components/check_suspension.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'classes/Collecteur.php';

$collecteur = new Collecteur();
$user_role = $_SESSION['user_role'];
$user_nom = $_SESSION['user_nom'];
$user_prenom = $_SESSION['user_prenom'];

// Récupérer le classement des collecteurs
$classement = $collecteur->getClassementCollecteurs();

$page_title = 'Classement des Collecteurs';

// Contenu de la page
ob_start();
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        text-align: center;
    }
    
    .page-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
    }
    
    .page-subtitle {
        font-size: 1.2rem;
        opacity: 0.9;
        margin: 0;
    }
    
    .classement-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    .classement-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .classement-item {
        padding: 1.5rem;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background-color 0.3s ease;
    }
    
    .classement-item:last-child {
        border-bottom: none;
    }
    
    .classement-item:hover {
        background-color: #f8f9fa;
    }
    
    .classement-item.podium {
        background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
        color: #333;
    }
    
    .classement-item.podium.second {
        background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%);
    }
    
    .classement-item.podium.third {
        background: linear-gradient(135deg, #cd7f32 0%, #daa520 100%);
        color: white;
    }
    
    .classement-rank {
        font-weight: 700;
        font-size: 1.5rem;
        margin-right: 1.5rem;
        min-width: 50px;
        text-align: center;
    }
    
    .rank-1 { color: #ffd700; }
    .rank-2 { color: #c0c0c0; }
    .rank-3 { color: #cd7f32; }
    .rank-other { color: #667eea; }
    
    .collecteur-info {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .collecteur-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .collecteur-details {
        flex: 1;
    }
    
    .collecteur-nom {
        font-weight: 600;
        font-size: 1.2rem;
        color: #333;
        margin-bottom: 0.25rem;
    }
    
    .collecteur-stats {
        font-size: 0.9rem;
        color: #666;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .collecteur-score {
        text-align: center;
        min-width: 100px;
    }
    
    .score-number {
        font-weight: 700;
        font-size: 1.5rem;
        color: #27ae60;
        margin-bottom: 0.25rem;
    }
    
    .score-label {
        font-size: 0.8rem;
        color: #666;
        text-transform: uppercase;
    }
    
    .performance-bar {
        width: 100%;
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 0.5rem;
    }
    
    .performance-fill {
        height: 100%;
        background: linear-gradient(90deg, #27ae60, #2ecc71);
        transition: width 0.3s ease;
    }
    
    .badges {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .badge {
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .badge-excellent {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-bon {
        background: #cce5ff;
        color: #004085;
    }
    
    .badge-moyen {
        background: #fff3cd;
        color: #856404;
    }
    
    .badge-faible {
        background: #f8d7da;
        color: #721c24;
    }
    
    .stats-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .summary-card {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        text-align: center;
    }
    
    .summary-number {
        font-size: 2rem;
        font-weight: 700;
        color: #667eea;
        margin-bottom: 0.5rem;
    }
    
    .summary-label {
        color: #666;
        font-size: 0.9rem;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #666;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 1rem;
    }
    
    @media (max-width: 768px) {
        .classement-item {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .collecteur-info {
            flex-direction: column;
            text-align: center;
        }
        
        .collecteur-stats {
            justify-content: center;
        }
    }
</style>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-trophy"></i>
        Classement des Collecteurs
    </h1>
    <p class="page-subtitle">Performances et statistiques en temps réel</p>
</div>

<?php if (!empty($classement)): ?>
    <!-- Statistiques générales -->
    <div class="stats-summary">
        <div class="summary-card">
            <div class="summary-number"><?php echo count($classement); ?></div>
            <div class="summary-label">Collecteurs actifs</div>
        </div>
        <div class="summary-card">
            <div class="summary-number">
                <?php 
                $total_taches = array_sum(array_column($classement, 'taches_totales'));
                echo $total_taches;
                ?>
            </div>
            <div class="summary-label">Tâches totales</div>
        </div>
        <div class="summary-card">
            <div class="summary-number">
                <?php 
                $total_terminees = array_sum(array_column($classement, 'taches_terminees'));
                echo $total_terminees;
                ?>
            </div>
            <div class="summary-label">Tâches terminées</div>
        </div>
        <div class="summary-card">
            <div class="summary-number">
                <?php 
                $taux_moyen = count($classement) > 0 ? 
                    round(array_sum(array_column($classement, 'taux_reussite')) / count($classement), 1) : 0;
                echo $taux_moyen;
                ?>%
            </div>
            <div class="summary-label">Taux de réussite moyen</div>
        </div>
    </div>

    <!-- Classement -->
    <div class="classement-container">
        <div class="classement-header">
            <h3><i class="fas fa-medal"></i> Classement par Performance</h3>
            <span>Mis à jour en temps réel</span>
        </div>
        
        <?php foreach ($classement as $index => $collecteur_stats): ?>
            <?php 
            $rank = $index + 1;
            $podium_class = '';
            $rank_class = 'rank-other';
            
            if ($rank == 1) {
                $podium_class = 'podium';
                $rank_class = 'rank-1';
            } elseif ($rank == 2) {
                $podium_class = 'podium second';
                $rank_class = 'rank-2';
            } elseif ($rank == 3) {
                $podium_class = 'podium third';
                $rank_class = 'rank-3';
            }
            
            // Déterminer le niveau de performance
            $performance = $collecteur_stats['score_performance'];
            $badge_class = 'badge-faible';
            $badge_text = 'À améliorer';
            
            if ($performance >= 80) {
                $badge_class = 'badge-excellent';
                $badge_text = 'Excellent';
            } elseif ($performance >= 60) {
                $badge_class = 'badge-bon';
                $badge_text = 'Bon';
            } elseif ($performance >= 40) {
                $badge_class = 'badge-moyen';
                $badge_text = 'Moyen';
            }
            ?>
            
            <div class="classement-item <?php echo $podium_class; ?>">
                <div class="classement-rank <?php echo $rank_class; ?>">
                    <?php if ($rank <= 3): ?>
                        <?php if ($rank == 1): ?>
                            <i class="fas fa-crown"></i>
                        <?php elseif ($rank == 2): ?>
                            <i class="fas fa-medal"></i>
                        <?php else: ?>
                            <i class="fas fa-award"></i>
                        <?php endif; ?>
                    <?php else: ?>
                        #<?php echo $rank; ?>
                    <?php endif; ?>
                </div>
                
                <div class="collecteur-info">
                    <div class="collecteur-avatar">
                        <?php echo strtoupper(substr($collecteur_stats['prenom'], 0, 1) . substr($collecteur_stats['nom'], 0, 1)); ?>
                    </div>
                    
                    <div class="collecteur-details">
                        <div class="collecteur-nom">
                            <?php echo htmlspecialchars($collecteur_stats['prenom'] . ' ' . $collecteur_stats['nom']); ?>
                        </div>
                        
                        <div class="collecteur-stats">
                            <div class="stat-item">
                                <i class="fas fa-tasks"></i>
                                <span><?php echo $collecteur_stats['taches_terminees']; ?>/<?php echo $collecteur_stats['taches_totales']; ?> tâches</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-percentage"></i>
                                <span><?php echo $collecteur_stats['taux_reussite']; ?>% réussite</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $collecteur_stats['temps_moyen_minutes']; ?> min/tâche</span>
                            </div>
                        </div>
                        
                        <div class="performance-bar">
                            <div class="performance-fill" style="width: <?php echo $performance; ?>%"></div>
                        </div>
                        
                        <div class="badges">
                            <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                            <?php if ($rank <= 3): ?>
                                <span class="badge badge-excellent">Top 3</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="collecteur-score">
                    <div class="score-number"><?php echo $collecteur_stats['score_performance']; ?></div>
                    <div class="score-label">Score</div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
<?php else: ?>
    <div class="empty-state">
        <i class="fas fa-users"></i>
        <h3>Aucun collecteur enregistré</h3>
        <p>Les statistiques des collecteurs apparaîtront ici une fois qu'ils auront commencé à travailler.</p>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include 'components/layout.php';
?> 