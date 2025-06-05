<?php
session_start();

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'classes/Admin.php';

$admin = new Admin();
$collecteur_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$collecteur_id) {
    header('Location: admin-collecteurs.php');
    exit();
}

// Récupérer les informations du collecteur
$collecteur = $admin->getCollecteurById($collecteur_id);
if (!$collecteur) {
    header('Location: admin-collecteurs.php');
    exit();
}

// Récupérer les tâches du collecteur
$taches = $admin->getCollecteurTaches($collecteur_id);

$page_title = 'Détails Collecteur - ' . $collecteur['prenom'] . ' ' . $collecteur['nom'];

// Contenu de la page
ob_start();
?>

<style>
    .admin-container {
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .page-title {
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
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
    }
    
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, #6c757d, #95a5a6);
        color: white;
    }
    
    .content-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .info-card {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        height: fit-content;
    }
    
    .info-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
        font-weight: 700;
    }
    
    .info-details h2 {
        margin: 0 0 0.5rem 0;
        color: #333;
        font-size: 1.5rem;
    }
    
    .info-details .role {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .info-section {
        margin-bottom: 2rem;
    }
    
    .info-section h3 {
        color: #333;
        margin-bottom: 1rem;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        color: #666;
        font-weight: 600;
    }
    
    .info-value {
        color: #333;
        font-weight: 500;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        text-align: center;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.2rem;
        color: white;
        background: linear-gradient(135deg, #667eea, #764ba2);
    }
    
    .stat-number {
        font-size: 1.8rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.9rem;
    }
    
    .taches-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    .section-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .section-title {
        font-size: 1.3rem;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table th,
    .table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
    }
    
    .table tr:hover {
        background: #f8f9fa;
    }
    
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-en_attente {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-acceptee {
        background: #cce5ff;
        color: #004085;
    }
    
    .status-en_route {
        background: #e2e3e5;
        color: #383d41;
    }
    
    .status-arrive {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .status-collectee {
        background: #d4edda;
        color: #155724;
    }
    
    .status-terminee {
        background: #d4edda;
        color: #155724;
    }
    
    .type-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .type-organique {
        background: #d4edda;
        color: #155724;
    }
    
    .type-plastique {
        background: #cce5ff;
        color: #004085;
    }
    
    .type-mixte {
        background: #fff3cd;
        color: #856404;
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
    
    .performance-bar {
        width: 100%;
        height: 10px;
        background: #e9ecef;
        border-radius: 5px;
        overflow: hidden;
        margin-top: 0.5rem;
    }
    
    .performance-fill {
        height: 100%;
        background: linear-gradient(90deg, #667eea, #764ba2);
        transition: width 0.3s ease;
    }
    
    .urgence-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        color: #dc3545;
        font-size: 0.8rem;
    }
    
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .content-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .table-responsive {
            font-size: 0.9rem;
        }
    }
</style>

<div class="admin-container">
    <!-- En-tête -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-user"></i>
            Détails du Collecteur
        </h1>
        <a href="admin-collecteurs.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Retour à la liste
        </a>
    </div>

    <!-- Contenu principal -->
    <div class="content-grid">
        <!-- Informations du collecteur -->
        <div class="info-card">
            <div class="info-header">
                <div class="avatar">
                    <?php echo strtoupper(substr($collecteur['prenom'], 0, 1) . substr($collecteur['nom'], 0, 1)); ?>
                </div>
                <div class="info-details">
                    <h2><?php echo htmlspecialchars($collecteur['prenom'] . ' ' . $collecteur['nom']); ?></h2>
                    <span class="role">Collecteur</span>
                </div>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-address-card"></i> Informations Personnelles</h3>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($collecteur['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Téléphone</span>
                    <span class="info-value"><?php echo htmlspecialchars($collecteur['telephone']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Adresse</span>
                    <span class="info-value"><?php echo htmlspecialchars($collecteur['adresse']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Commune</span>
                    <span class="info-value"><?php echo htmlspecialchars($collecteur['commune']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Quartier</span>
                    <span class="info-value"><?php echo htmlspecialchars($collecteur['quartier']); ?></span>
                </div>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-chart-line"></i> Performance Globale</h3>
                <div class="info-item">
                    <span class="info-label">Score Performance</span>
                    <span class="info-value">
                        <strong><?php echo $collecteur['score_performance']; ?>/100</strong>
                        <div class="performance-bar">
                            <div class="performance-fill" style="width: <?php echo $collecteur['score_performance']; ?>%"></div>
                        </div>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Taux de Réussite</span>
                    <span class="info-value"><?php echo $collecteur['taux_reussite']; ?>%</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Temps Moyen</span>
                    <span class="info-value"><?php echo $collecteur['temps_moyen_minutes']; ?> min/tâche</span>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-number"><?php echo $collecteur['taches_totales']; ?></div>
                    <div class="stat-label">Total Tâches</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?php echo $collecteur['taches_terminees']; ?></div>
                    <div class="stat-label">Terminées</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number">
                        <?php 
                        $taches_actives = array_filter($taches, function($t) {
                            return in_array($t['statut'], ['en_attente', 'acceptee', 'en_route', 'arrive', 'collectee']);
                        });
                        echo count($taches_actives);
                        ?>
                    </div>
                    <div class="stat-label">En Cours</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-number"><?php echo $collecteur['score_performance']; ?></div>
                    <div class="stat-label">Score/100</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des tâches -->
    <div class="taches-section">
        <div class="section-header">
            <h3 class="section-title">
                <i class="fas fa-list"></i>
                Historique des Tâches
            </h3>
            <span><?php echo count($taches); ?> tâche(s)</span>
        </div>
        
        <?php if (empty($taches)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>Aucune tâche</h3>
                <p>Ce collecteur n'a pas encore de tâches assignées.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tâche</th>
                            <th>Ménage</th>
                            <th>Poubelle</th>
                            <th>Statut</th>
                            <th>Dates</th>
                            <th>Urgence</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($taches as $tache): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong>Tâche #<?php echo $tache['id']; ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            Créée il y a <?php echo $tache['heures_depuis_creation']; ?>h
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($tache['menage_prenom'] . ' ' . $tache['menage_nom']); ?></strong>
                                        <br>
                                        <small>
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($tache['menage_tel']); ?>
                                        </small>
                                        <br>
                                        <small>
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($tache['adresse_complete']); ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="type-badge type-<?php echo $tache['poubelle_type']; ?>">
                                            <?php echo ucfirst($tache['poubelle_type']); ?>
                                        </span>
                                        <br>
                                        <small><?php echo htmlspecialchars($tache['poubelle_description']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $tache['statut']; ?>">
                                        <?php 
                                        $statuts = [
                                            'en_attente' => 'En Attente',
                                            'acceptee' => 'Acceptée',
                                            'en_route' => 'En Route',
                                            'arrive' => 'Arrivé',
                                            'collectee' => 'Collectée',
                                            'terminee' => 'Terminée'
                                        ];
                                        echo $statuts[$tache['statut']] ?? $tache['statut'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <small>
                                            <strong>Créée:</strong> <?php echo date('d/m/Y H:i', strtotime($tache['created_at'])); ?>
                                            <br>
                                            <?php if ($tache['date_assignation']): ?>
                                                <strong>Assignée:</strong> <?php echo date('d/m/Y H:i', strtotime($tache['date_assignation'])); ?>
                                                <br>
                                            <?php endif; ?>
                                            <?php if ($tache['date_completion']): ?>
                                                <strong>Terminée:</strong> <?php echo date('d/m/Y H:i', strtotime($tache['date_completion'])); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($tache['heures_depuis_creation'] > 24): ?>
                                        <div class="urgence-indicator">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Urgent
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Normal</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'components/layout.php';
?> 