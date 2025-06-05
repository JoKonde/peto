<?php
session_start();

// Vérifier la suspension
require_once 'components/check_suspension.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'classes/Admin.php';

$admin = new Admin();
$menage_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$menage_id) {
    header('Location: admin-menages.php');
    exit();
}

// Récupérer les informations du ménage
$menage = $admin->getMenageById($menage_id);
if (!$menage) {
    header('Location: admin-menages.php');
    exit();
}

// Récupérer les poubelles du ménage
$poubelles = $admin->getMenagePoubelles($menage_id);

$page_title = 'Détails Ménage - ' . $menage['prenom'] . ' ' . $menage['nom'];

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
    
    .poubelles-section {
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
    
    .alerte-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .alerte-active {
        background: #f8d7da;
        color: #721c24;
        animation: pulse 2s infinite;
    }
    
    .alerte-inactive {
        background: #d4edda;
        color: #155724;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
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
    
    .progress-bar {
        width: 100%;
        height: 10px;
        background: #e9ecef;
        border-radius: 5px;
        overflow: hidden;
        margin-top: 0.5rem;
    }
    
    .progress-fill {
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
            <i class="fas fa-home"></i>
            Détails du Ménage
        </h1>
        <a href="admin-menages.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Retour à la liste
        </a>
    </div>

    <!-- Contenu principal -->
    <div class="content-grid">
        <!-- Informations du ménage -->
        <div class="info-card">
            <div class="info-header">
                <div class="avatar">
                    <?php echo strtoupper(substr($menage['prenom'], 0, 1) . substr($menage['nom'], 0, 1)); ?>
                </div>
                <div class="info-details">
                    <h2><?php echo htmlspecialchars($menage['prenom'] . ' ' . $menage['nom']); ?></h2>
                    <span class="role">Ménage</span>
                </div>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-address-card"></i> Informations Personnelles</h3>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($menage['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Téléphone</span>
                    <span class="info-value"><?php echo htmlspecialchars($menage['telephone']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Adresse</span>
                    <span class="info-value"><?php echo htmlspecialchars($menage['adresse']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Commune</span>
                    <span class="info-value"><?php echo htmlspecialchars($menage['commune']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Quartier</span>
                    <span class="info-value"><?php echo htmlspecialchars($menage['quartier']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Avenue</span>
                    <span class="info-value"><?php echo htmlspecialchars($menage['avenue'] . ' N°' . $menage['numero']); ?></span>
                </div>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-chart-bar"></i> Résumé d'Activité</h3>
                <div class="info-item">
                    <span class="info-label">Total Poubelles</span>
                    <span class="info-value">
                        <strong><?php echo $menage['total_poubelles']; ?></strong>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">En Alerte</span>
                    <span class="info-value">
                        <strong style="color: #dc3545;"><?php echo $menage['poubelles_alertes']; ?></strong>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Collectées</span>
                    <span class="info-value">
                        <strong style="color: #28a745;"><?php echo $menage['poubelles_collectees']; ?></strong>
                        <?php if ($menage['total_poubelles'] > 0): ?>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo ($menage['poubelles_collectees'] / $menage['total_poubelles']) * 100; ?>%"></div>
                            </div>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-trash"></i>
                    </div>
                    <div class="stat-number"><?php echo $menage['total_poubelles']; ?></div>
                    <div class="stat-label">Total Poubelles</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-number"><?php echo $menage['poubelles_alertes']; ?></div>
                    <div class="stat-label">En Alerte</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?php echo $menage['poubelles_collectees']; ?></div>
                    <div class="stat-label">Collectées</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-number">
                        <?php 
                        $taux = $menage['total_poubelles'] > 0 ? round(($menage['poubelles_collectees'] / $menage['total_poubelles']) * 100, 1) : 0;
                        echo $taux;
                        ?>%
                    </div>
                    <div class="stat-label">Taux Collecte</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des poubelles -->
    <div class="poubelles-section">
        <div class="section-header">
            <h3 class="section-title">
                <i class="fas fa-list"></i>
                Historique des Poubelles
            </h3>
            <span><?php echo count($poubelles); ?> poubelle(s)</span>
        </div>
        
        <?php if (empty($poubelles)): ?>
            <div class="empty-state">
                <i class="fas fa-trash"></i>
                <h3>Aucune poubelle</h3>
                <p>Ce ménage n'a pas encore créé de poubelles.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Poubelle</th>
                            <th>Type</th>
                            <th>Statut Alerte</th>
                            <th>Collecte</th>
                            <th>Collecteur</th>
                            <th>Dates</th>
                            <th>Urgence</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($poubelles as $poubelle): ?>
                            <?php 
                            $heures_attente = $poubelle['heures_attente'] ?? 0;
                            $is_urgent = $heures_attente > 24;
                            ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong>Poubelle #<?php echo $poubelle['id']; ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($poubelle['description']); ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <span class="type-badge type-<?php echo $poubelle['type']; ?>">
                                        <?php echo ucfirst($poubelle['type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="alerte-badge <?php echo $poubelle['alerte_pleine'] ? 'alerte-active' : 'alerte-inactive'; ?>">
                                        <?php echo $poubelle['alerte_pleine'] ? 'PLEINE' : 'Normal'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($poubelle['collecte_statut']): ?>
                                        <span class="status-badge status-<?php echo $poubelle['collecte_statut']; ?>">
                                            <?php 
                                            $statuts = [
                                                'en_attente' => 'En Attente',
                                                'acceptee' => 'Acceptée',
                                                'en_route' => 'En Route',
                                                'arrive' => 'Arrivé',
                                                'collectee' => 'Collectée',
                                                'terminee' => 'Terminée'
                                            ];
                                            echo $statuts[$poubelle['collecte_statut']] ?? $poubelle['collecte_statut'];
                                            ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Pas de collecte</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($poubelle['collecteur_nom']): ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($poubelle['collecteur_prenom'] . ' ' . $poubelle['collecteur_nom']); ?></strong>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Non assigné</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <small>
                                            <strong>Créée:</strong> <?php echo date('d/m/Y H:i', strtotime($poubelle['created_at'])); ?>
                                            <br>
                                            <?php if ($poubelle['date_alerte']): ?>
                                                <strong>Alerte:</strong> <?php echo date('d/m/Y H:i', strtotime($poubelle['date_alerte'])); ?>
                                                <br>
                                            <?php endif; ?>
                                            <?php if ($poubelle['date_assignation']): ?>
                                                <strong>Assignée:</strong> <?php echo date('d/m/Y H:i', strtotime($poubelle['date_assignation'])); ?>
                                                <br>
                                            <?php endif; ?>
                                            <?php if ($poubelle['date_completion']): ?>
                                                <strong>Terminée:</strong> <?php echo date('d/m/Y H:i', strtotime($poubelle['date_completion'])); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($poubelle['alerte_pleine'] && $is_urgent): ?>
                                        <div class="urgence-indicator">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Urgent
                                        </div>
                                        <small><?php echo $heures_attente; ?>h d'attente</small>
                                    <?php elseif ($poubelle['alerte_pleine']): ?>
                                        <div style="color: #f39c12;">
                                            <i class="fas fa-clock"></i>
                                            En attente
                                        </div>
                                        <small><?php echo $heures_attente; ?>h</small>
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