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
            case 'create_collecteur':
                $data = [
                    'nom' => trim($_POST['nom']),
                    'prenom' => trim($_POST['prenom']),
                    'telephone' => trim($_POST['telephone']),
                    'email' => trim($_POST['email']),
                    'adresse' => trim($_POST['adresse']),
                    'commune' => trim($_POST['commune']),
                    'quartier' => trim($_POST['quartier']),
                    'avenue' => trim($_POST['avenue']),
                    'numero' => trim($_POST['numero']),
                    'password' => $_POST['password']
                ];
                
                if ($admin->createCollecteur($data)) {
                    $message = "Collecteur créé avec succès !";
                } else {
                    $error = "Erreur lors de la création du collecteur.";
                }
                break;
                
            case 'toggle_status':
                $id = $_POST['collecteur_id'];
                $status = $_POST['status'];
                if ($admin->toggleCollecteurStatus($id, $status)) {
                    $message = "Statut du collecteur mis à jour avec succès !";
                } else {
                    $error = "Erreur lors de la mise à jour du statut.";
                }
                break;
                
            case 'delete_collecteur':
                $id = $_POST['collecteur_id'];
                $result = $admin->deleteCollecteur($id);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Récupérer la liste des collecteurs
$collecteurs = $admin->getAllCollecteurs();

$page_title = 'Gestion des Collecteurs';

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
    
    .collecteurs-table {
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
    
    .performance-bar {
        width: 100%;
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 0.25rem;
    }
    
    .performance-fill {
        height: 100%;
        background: linear-gradient(90deg, #667eea, #764ba2);
        transition: width 0.3s ease;
    }
    
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
    }
    
    .modal-content {
        background: white;
        margin: 2% auto;
        padding: 0;
        border-radius: 15px;
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        animation: modalSlideIn 0.3s ease;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    
    @keyframes modalSlideIn {
        from { transform: translateY(-50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    .modal-header {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 1.5rem;
        border-radius: 15px 15px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }
    
    .modal-title {
        font-size: 1.3rem;
        font-weight: 600;
        margin: 0;
    }
    
    .close {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background 0.3s ease;
    }
    
    .close:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    .modal-body {
        padding: 2rem;
        overflow-y: auto;
        flex: 1;
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
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
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
    
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .form-row {
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
            <i class="fas fa-truck"></i>
            Gestion des Collecteurs
        </h1>
        <button class="btn btn-primary" onclick="openModal('createModal')">
            <i class="fas fa-plus"></i>
            Nouveau Collecteur
        </button>
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
            <div class="stat-number"><?php echo count($collecteurs); ?></div>
            <div class="stat-label">Total Collecteurs</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-number">
                <?php 
                $actifs = array_filter($collecteurs, function($c) { 
                    return $c['statut'] !== 'suspendu'; 
                });
                echo count($actifs);
                ?>
            </div>
            <div class="stat-label">Collecteurs Actifs</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-number">
                <?php 
                $total_taches = array_sum(array_column($collecteurs, 'taches_actives'));
                echo $total_taches;
                ?>
            </div>
            <div class="stat-label">Tâches Actives</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-number">
                <?php 
                $scores = array_filter(array_column($collecteurs, 'score_performance'));
                echo !empty($scores) ? round(array_sum($scores) / count($scores), 1) : 0;
                ?>%
            </div>
            <div class="stat-label">Performance Moyenne</div>
        </div>
    </div>

    <!-- Table des collecteurs -->
    <div class="collecteurs-table">
        <div class="table-header">
            <h3 class="table-title">
                <i class="fas fa-list"></i>
                Liste des Collecteurs
            </h3>
            <span><?php echo count($collecteurs); ?> collecteur(s)</span>
        </div>
        
        <?php if (empty($collecteurs)): ?>
            <div class="empty-state">
                <i class="fas fa-truck"></i>
                <h3>Aucun collecteur</h3>
                <p>Commencez par créer votre premier collecteur.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Collecteur</th>
                            <th>Contact</th>
                            <th>Zone</th>
                            <th>Statistiques</th>
                            <th>Performance</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($collecteurs as $collecteur): ?>
                            <?php $is_suspended = $collecteur['statut'] === 'suspendu'; ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($collecteur['prenom'] . ' ' . $collecteur['nom']); ?></strong>
                                        <br>
                                        <small class="text-muted">ID: <?php echo $collecteur['id']; ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($collecteur['email']); ?>
                                        <br>
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($collecteur['telephone']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($collecteur['commune']); ?></strong>
                                        <br>
                                        <small><?php echo htmlspecialchars($collecteur['quartier']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <small>
                                            <strong><?php echo $collecteur['taches_terminees']; ?></strong>/<?php echo $collecteur['taches_totales']; ?> terminées
                                            <br>
                                            <strong><?php echo $collecteur['taches_actives']; ?></strong> en cours
                                            <br>
                                            <strong><?php echo $collecteur['temps_moyen_minutes']; ?></strong> min/tâche
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo $collecteur['score_performance']; ?>/100</strong>
                                        <div class="performance-bar">
                                            <div class="performance-fill" style="width: <?php echo $collecteur['score_performance']; ?>%"></div>
                                        </div>
                                        <small><?php echo $collecteur['taux_reussite']; ?>% réussite</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $is_suspended ? 'status-suspendu' : 'status-actif'; ?>">
                                        <?php echo $is_suspended ? 'Suspendu' : 'Actif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-primary btn-sm" onclick="viewCollecteur(<?php echo $collecteur['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($is_suspended): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="collecteur_id" value="<?php echo $collecteur['id']; ?>">
                                                <input type="hidden" name="status" value="actif">
                                                <button type="submit" class="btn btn-success btn-sm" title="Activer">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="collecteur_id" value="<?php echo $collecteur['id']; ?>">
                                                <input type="hidden" name="status" value="suspendu">
                                                <button type="submit" class="btn btn-warning btn-sm" title="Suspendre">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce collecteur ?')">
                                            <input type="hidden" name="action" value="delete_collecteur">
                                            <input type="hidden" name="collecteur_id" value="<?php echo $collecteur['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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

<!-- Modal Création Collecteur -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-plus"></i>
                Nouveau Collecteur
            </h3>
            <button class="close" onclick="closeModal('createModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="create_collecteur">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="nom" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prénom *</label>
                        <input type="text" name="prenom" class="form-input" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Téléphone *</label>
                        <input type="tel" name="telephone" class="form-input" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Adresse *</label>
                    <input type="text" name="adresse" class="form-input" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Commune *</label>
                        <select name="commune" class="form-input" required>
                            <option value="">Sélectionner...</option>
                            <option value="Bandalungwa">Bandalungwa</option>
                            <option value="Barumbu">Barumbu</option>
                            <option value="Gombe">Gombe</option>
                            <option value="Kalamu">Kalamu</option>
                            <option value="Kasa-Vubu">Kasa-Vubu</option>
                            <option value="Kinshasa">Kinshasa</option>
                            <option value="Kintambo">Kintambo</option>
                            <option value="Lemba">Lemba</option>
                            <option value="Limete">Limete</option>
                            <option value="Lingwala">Lingwala</option>
                            <option value="Makala">Makala</option>
                            <option value="Maluku">Maluku</option>
                            <option value="Masina">Masina</option>
                            <option value="Matete">Matete</option>
                            <option value="Mont-Ngafula">Mont-Ngafula</option>
                            <option value="Ndjili">Ndjili</option>
                            <option value="Ngaba">Ngaba</option>
                            <option value="Ngaliema">Ngaliema</option>
                            <option value="Ngiri-Ngiri">Ngiri-Ngiri</option>
                            <option value="Nsele">Nsele</option>
                            <option value="Selembao">Selembao</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quartier *</label>
                        <input type="text" name="quartier" class="form-input" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Avenue *</label>
                        <input type="text" name="avenue" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Numéro *</label>
                        <input type="text" name="numero" class="form-input" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mot de passe *</label>
                    <input type="password" name="password" class="form-input" required minlength="6">
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">
                        <i class="fas fa-times"></i>
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Créer le Collecteur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function viewCollecteur(id) {
    window.location.href = 'admin-collecteur-details.php?id=' + id;
}

// Fermer modal en cliquant à l'extérieur
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}
</script>

<?php
$content = ob_get_clean();
include 'components/layout.php';
?> 