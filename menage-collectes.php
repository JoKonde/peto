<?php
session_start();

// Vérifier la suspension
require_once 'components/check_suspension.php';

// Vérifier si l'utilisateur est connecté et est ménage
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'menage') {
    header('Location: login.php');
    exit();
}

require_once 'classes/Database.php';
require_once 'classes/Poubelle.php';

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

$poubelle = new Poubelle();

// Récupérer les collectes avec suivi en temps réel
$collectes = $poubelle->getCollectesAvecSuivi($user_id);

// Statistiques avec gestion de tous les statuts possibles
$stats = [
    'en_attente' => 0,
    'en_cours' => 0,
    'terminees' => 0,
    'total' => count($collectes)
];

foreach ($collectes as $collecte) {
    $statut = $collecte['statut'];
    
    // Mapper les statuts de la base vers nos catégories d'affichage
    if ($statut == 'en_attente') {
        $stats['en_attente']++;
    } elseif (in_array($statut, ['acceptee', 'en_route', 'arrive', 'collectee'])) {
        $stats['en_cours']++;
    } elseif ($statut == 'terminee') {
        $stats['terminees']++;
    }
}

$page_title = 'Mes Collectes';

// Contenu de la page
ob_start();
?>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    
    .page-title {
        color: #333;
        font-size: 2rem;
        font-weight: 600;
        margin: 0;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        text-align: center;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.9rem;
    }
    
    .stat-en-attente .stat-number { color: #f39c12; }
    .stat-en-cours .stat-number { color: #3498db; }
    .stat-terminees .stat-number { color: #27ae60; }
    .stat-total .stat-number { color: #4facfe; }
    
    .collectes-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    .collectes-header {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        padding: 1.5rem;
    }
    
    .collectes-header h3 {
        margin: 0;
        font-size: 1.3rem;
    }
    
    .collecte-item {
        padding: 1.5rem;
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.3s ease;
    }
    
    .collecte-item:last-child {
        border-bottom: none;
    }
    
    .collecte-item:hover {
        background-color: #f8f9fa;
    }
    
    .collecte-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    
    .collecte-info {
        flex: 1;
    }
    
    .collecte-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .collecte-meta {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }
    
    .collecte-status {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-en_attente {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    
    .status-acceptee {
        background: #cce5ff;
        color: #004085;
        border: 1px solid #99d6ff;
    }
    
    .status-en_route {
        background: #e1f5fe;
        color: #01579b;
        border: 1px solid #81d4fa;
    }
    
    .status-arrive {
        background: #f3e5f5;
        color: #4a148c;
        border: 1px solid #ce93d8;
    }
    
    .status-collectee {
        background: #e8f5e8;
        color: #2e7d32;
        border: 1px solid #a5d6a7;
    }
    
    .status-terminee {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .collecte-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .detail-group {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
    }
    
    .detail-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .detail-value {
        color: #666;
        font-size: 0.9rem;
    }
    
    .collecteur-info {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem;
        border-radius: 8px;
        margin-top: 1rem;
    }
    
    .collecteur-info .detail-label {
        color: white;
        margin-bottom: 0.5rem;
    }
    
    .collecteur-info .detail-value {
        color: rgba(255, 255, 255, 0.9);
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
    
    .timeline {
        position: relative;
        padding-left: 2rem;
        margin-top: 1rem;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 0.5rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e9ecef;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 1rem;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -0.75rem;
        top: 0.25rem;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #4facfe;
        border: 2px solid white;
        box-shadow: 0 0 0 2px #4facfe;
    }
    
    .timeline-content {
        background: white;
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    .timeline-time {
        font-size: 0.8rem;
        color: #666;
        margin-bottom: 0.25rem;
    }
    
    .timeline-text {
        color: #333;
        font-weight: 500;
    }
    
    .urgence-badge {
        background: #e74c3c;
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        margin-left: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .collecte-header {
            flex-direction: column;
            gap: 1rem;
        }
        
        .collecte-details {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="page-header">
    <h1 class="page-title">Mes Collectes</h1>
</div>

<!-- Statistiques -->
<div class="stats-grid">
    <div class="stat-card stat-en-attente">
        <div class="stat-number"><?php echo $stats['en_attente']; ?></div>
        <div class="stat-label">En attente</div>
    </div>
    <div class="stat-card stat-en-cours">
        <div class="stat-number"><?php echo $stats['en_cours']; ?></div>
        <div class="stat-label">En cours</div>
    </div>
    <div class="stat-card stat-terminees">
        <div class="stat-number"><?php echo $stats['terminees']; ?></div>
        <div class="stat-label">Terminées</div>
    </div>
    <div class="stat-card stat-total">
        <div class="stat-number"><?php echo $stats['total']; ?></div>
        <div class="stat-label">Total</div>
    </div>
</div>

<!-- Liste des collectes -->
<div class="collectes-container">
    <div class="collectes-header">
        <h3><i class="fas fa-calendar-alt"></i> Historique des collectes</h3>
    </div>
    
    <?php if (empty($collectes)): ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>Aucune collecte programmée</h3>
            <p>Vos collectes apparaîtront ici une fois que vous aurez alerté qu'une poubelle est pleine.</p>
        </div>
    <?php else: ?>
        <?php foreach ($collectes as $collecte): ?>
            <div class="collecte-item">
                <div class="collecte-header">
                    <div class="collecte-info">
                        <div class="collecte-title">
                            <?php 
                            $icons = [
                                'organique' => '🥬',
                                'plastique' => '♻️',
                                'mixte' => '🗑️'
                            ];
                            echo $icons[$collecte['poubelle_type']];
                            ?>
                            Collecte <?php echo ucfirst($collecte['poubelle_type']); ?>
                            <?php if ($collecte['statut'] == 'en_attente'): ?>
                                <?php
                                $heures_attente = (time() - strtotime($collecte['created_at'])) / 3600;
                                if ($heures_attente > 24): ?>
                                    <span class="urgence-badge">Urgent</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="collecte-meta">
                            <i class="fas fa-clock"></i>
                            Programmée le <?php echo date('d/m/Y à H:i', strtotime($collecte['created_at'])); ?>
                        </div>
                        <div class="collecte-meta">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($collecte['adresse']); ?>
                        </div>
                        <?php if ($collecte['poubelle_description']): ?>
                            <div class="collecte-meta">
                                <i class="fas fa-info-circle"></i>
                                <?php echo htmlspecialchars($collecte['poubelle_description']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="collecte-status status-<?php echo $collecte['statut']; ?>">
                        <?php 
                        switch($collecte['statut']) {
                            case 'en_attente': echo 'En attente'; break;
                            case 'acceptee': echo 'Acceptée'; break;
                            case 'en_route': echo 'En route'; break;
                            case 'arrive': echo 'Arrivé'; break;
                            case 'collectee': echo 'Collectée'; break;
                            case 'terminee': echo 'Terminée'; break;
                            default: echo ucfirst($collecte['statut']); break;
                        }
                        ?>
                    </div>
                </div>
                
                <div class="collecte-details">
                    <div class="detail-group">
                        <div class="detail-label">
                            <i class="fas fa-trash"></i>
                            Type de déchet
                        </div>
                        <div class="detail-value">
                            <?php echo ucfirst($collecte['type_dechet']); ?>
                        </div>
                    </div>
                    
                    <div class="detail-group">
                        <div class="detail-label">
                            <i class="fas fa-calendar"></i>
                            Date d'assignation
                        </div>
                        <div class="detail-value">
                            <?php echo date('d/m/Y à H:i', strtotime($collecte['date_assignation'])); ?>
                        </div>
                    </div>
                    
                    <?php if ($collecte['date_completion']): ?>
                        <div class="detail-group">
                            <div class="detail-label">
                                <i class="fas fa-check-circle"></i>
                                Date de completion
                            </div>
                            <div class="detail-value">
                                <?php echo date('d/m/Y à H:i', strtotime($collecte['date_completion'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Informations du collecteur -->
                <div class="collecteur-info">
                    <div class="detail-label">
                        <i class="fas fa-user"></i>
                        Collecteur assigné
                    </div>
                    <div class="detail-value">
                        <strong><?php echo htmlspecialchars($collecte['collecteur_prenom'] . ' ' . $collecte['collecteur_nom']); ?></strong>
                        <br>
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($collecte['collecteur_tel']); ?>
                    </div>
                </div>
                
                <!-- Timeline de la collecte -->
                <div class="timeline">
                    <?php if (!empty($collecte['etapes'])): ?>
                        <?php foreach ($collecte['etapes'] as $etape): ?>
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <div class="timeline-time">
                                        <?php echo date('d/m/Y à H:i', strtotime($etape['created_at'])); ?>
                                    </div>
                                    <div class="timeline-text">
                                        <?php echo htmlspecialchars($etape['description']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Timeline par défaut basée sur les dates de la tâche -->
                        <div class="timeline-item">
                            <div class="timeline-content">
                                <div class="timeline-time">
                                    <?php echo date('d/m/Y à H:i', strtotime($collecte['created_at'])); ?>
                                </div>
                                <div class="timeline-text">
                                    Collecte programmée et assignée au collecteur
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($collecte['date_acceptation']): ?>
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <div class="timeline-time">
                                        <?php echo date('d/m/Y à H:i', strtotime($collecte['date_acceptation'])); ?>
                                    </div>
                                    <div class="timeline-text">
                                        ✅ Tâche acceptée par le collecteur
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($collecte['date_en_route']): ?>
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <div class="timeline-time">
                                        <?php echo date('d/m/Y à H:i', strtotime($collecte['date_en_route'])); ?>
                                    </div>
                                    <div class="timeline-text">
                                        🚗 Collecteur en route vers votre domicile
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($collecte['date_arrivee']): ?>
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <div class="timeline-time">
                                        <?php echo date('d/m/Y à H:i', strtotime($collecte['date_arrivee'])); ?>
                                    </div>
                                    <div class="timeline-text">
                                        📍 Collecteur arrivé chez vous
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($collecte['date_collecte']): ?>
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <div class="timeline-time">
                                        <?php echo date('d/m/Y à H:i', strtotime($collecte['date_collecte'])); ?>
                                    </div>
                                    <div class="timeline-text">
                                        🗑️ Poubelle collectée par le collecteur
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($collecte['date_completion']): ?>
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <div class="timeline-time">
                                        <?php echo date('d/m/Y à H:i', strtotime($collecte['date_completion'])); ?>
                                    </div>
                                    <div class="timeline-text">
                                        ✅ Collecte terminée et confirmée
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include 'components/layout.php';
?> 