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
$message = '';
$error = '';

// Traitement des actions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_status':
                $id = $_POST['menage_id'];
                $status = $_POST['status'];
                if ($admin->toggleMenageStatus($id, $status)) {
                    $message = "Statut du ménage mis à jour avec succès !";
                } else {
                    $error = "Erreur lors de la mise à jour du statut.";
                }
                break;
        }
    }
}

// Récupérer la liste des ménages
$menages = $admin->getAllMenages();

$page_title = 'Gestion des Ménages';

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
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    
    .btn-success {
        background: linear-gradient(135deg, #27ae60, #2ecc71);
        color: white;
    }
    
    .btn-warning {
        background: linear-gradient(135deg, #f39c12, #fdcb6e);
        color: white;
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #e74c3c, #fd79a8);
        color: white;
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, #6c757d, #95a5a6);
        color: white;
    }
    
    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
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
        background: linear-gradient(135deg, #667eea, #764ba2);
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
    
    .menages-table {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 2rem;
    }
    
    .table-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .table-title {
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
    
    .status-actif {
        background: #d4edda;
        color: #155724;
    }
    
    .status-suspendu {
        background: #f8d7da;
        color: #721c24;
    }
    
    .progress-bar {
        width: 100%;
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 0.25rem;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #667eea, #764ba2);
        transition: width 0.3s ease;
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
    
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .message {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        text-align: center;
        font-weight: 600;
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
    
    .activity-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.8rem;
    }
    
    .activity-recent {
        color: #28a745;
    }
    
    .activity-old {
        color: #dc3545;
    }
    
    .activity-none {
        color: #6c757d;
    }
    
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-direction: column;
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
            Gestion des Ménages
        </h1>
        <div>
            <span style="opacity: 0.8;"><?php echo count($menages); ?> ménage(s) enregistré(s)</span>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number"><?php echo count($menages); ?></div>
            <div class="stat-label">Total Ménages</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-number">
                <?php 
                $actifs = array_filter($menages, function($m) { 
                    return $m['statut'] !== 'suspendu'; 
                });
                echo count($actifs);
                ?>
            </div>
            <div class="stat-label">Ménages Actifs</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-trash"></i>
            </div>
            <div class="stat-number">
                <?php 
                $total_poubelles = array_sum(array_column($menages, 'total_poubelles'));
                echo $total_poubelles;
                ?>
            </div>
            <div class="stat-label">Total Poubelles</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-number">
                <?php 
                $total_alertes = array_sum(array_column($menages, 'poubelles_alertes'));
                echo $total_alertes;
                ?>
            </div>
            <div class="stat-label">Alertes Actives</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-number">
                <?php 
                $total_collectees = array_sum(array_column($menages, 'poubelles_collectees'));
                echo $total_collectees;
                ?>
            </div>
            <div class="stat-label">Poubelles Collectées</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="stat-number">
                <?php 
                $taux_collecte = $total_poubelles > 0 ? round(($total_collectees / $total_poubelles) * 100, 1) : 0;
                echo $taux_collecte;
                ?>%
            </div>
            <div class="stat-label">Taux de Collecte</div>
        </div>
    </div>

    <!-- Table des ménages -->
    <div class="menages-table">
        <div class="table-header">
            <h3 class="table-title">
                <i class="fas fa-list"></i>
                Liste des Ménages
            </h3>
            <span><?php echo count($menages); ?> ménage(s)</span>
        </div>
        
        <?php if (empty($menages)): ?>
            <div class="empty-state">
                <i class="fas fa-home"></i>
                <h3>Aucun ménage</h3>
                <p>Aucun ménage n'est encore enregistré dans le système.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ménage</th>
                            <th>Contact</th>
                            <th>Localisation</th>
                            <th>Poubelles</th>
                            <th>Activité</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($menages as $menage): ?>
                            <?php 
                            $is_suspended = $menage['statut'] === 'suspendu';
                            $derniere_activite = $menage['derniere_poubelle'] ? strtotime($menage['derniere_poubelle']) : null;
                            $jours_inactivite = $derniere_activite ? floor((time() - $derniere_activite) / (24 * 3600)) : null;
                            ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($menage['prenom'] . ' ' . $menage['nom']); ?></strong>
                                        <br>
                                        <small class="text-muted">ID: <?php echo $menage['id']; ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($menage['email']); ?>
                                        <br>
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($menage['telephone']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($menage['commune']); ?></strong>
                                        <br>
                                        <small><?php echo htmlspecialchars($menage['quartier']); ?></small>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($menage['avenue'] . ' N°' . $menage['numero']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo $menage['total_poubelles']; ?></strong> total
                                        <br>
                                        <span style="color: #dc3545;"><strong><?php echo $menage['poubelles_alertes']; ?></strong> en alerte</span>
                                        <br>
                                        <span style="color: #28a745;"><strong><?php echo $menage['poubelles_collectees']; ?></strong> collectées</span>
                                        <?php if ($menage['total_poubelles'] > 0): ?>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo ($menage['poubelles_collectees'] / $menage['total_poubelles']) * 100; ?>%"></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php if ($derniere_activite): ?>
                                            <?php if ($jours_inactivite <= 7): ?>
                                                <div class="activity-indicator activity-recent">
                                                    <i class="fas fa-circle"></i>
                                                    Récente
                                                </div>
                                                <small>Il y a <?php echo $jours_inactivite; ?> jour(s)</small>
                                            <?php elseif ($jours_inactivite <= 30): ?>
                                                <div class="activity-indicator activity-old">
                                                    <i class="fas fa-circle"></i>
                                                    Ancienne
                                                </div>
                                                <small>Il y a <?php echo $jours_inactivite; ?> jour(s)</small>
                                            <?php else: ?>
                                                <div class="activity-indicator activity-none">
                                                    <i class="fas fa-circle"></i>
                                                    Inactive
                                                </div>
                                                <small>Il y a <?php echo $jours_inactivite; ?> jour(s)</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="activity-indicator activity-none">
                                                <i class="fas fa-circle"></i>
                                                Aucune
                                            </div>
                                            <small>Jamais utilisé</small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $is_suspended ? 'status-suspendu' : 'status-actif'; ?>">
                                        <?php echo $is_suspended ? 'Suspendu' : 'Actif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-primary btn-sm" onclick="viewMenage(<?php echo $menage['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($is_suspended): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="menage_id" value="<?php echo $menage['id']; ?>">
                                                <input type="hidden" name="status" value="actif">
                                                <button type="submit" class="btn btn-success btn-sm" title="Activer">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="menage_id" value="<?php echo $menage['id']; ?>">
                                                <input type="hidden" name="status" value="suspendu">
                                                <button type="submit" class="btn btn-warning btn-sm" title="Suspendre">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function viewMenage(id) {
    window.location.href = 'admin-menage-details.php?id=' + id;
}
</script>

<?php
$content = ob_get_clean();
include 'components/layout.php';
?> 