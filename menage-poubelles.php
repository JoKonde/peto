<?php
session_start();

// Vérifier la suspension
require_once 'components/check_suspension.php';

// Vérifier si l'utilisateur est connecté et est ménage
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'menage') {
    header('Location: login.php');
    exit();
}

require_once 'classes/Poubelle.php';

$poubelle = new Poubelle();
$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Traitement des actions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                if (!empty($_POST['type'])) {
                    if ($poubelle->create($user_id, $_POST['type'], $_POST['description'] ?? '')) {
                        $message = "Poubelle créée avec succès !";
                    } else {
                        $error = "Erreur lors de la création de la poubelle.";
                    }
                } else {
                    $error = "Veuillez sélectionner un type de poubelle.";
                }
                break;
                
            case 'alerter':
                if (!empty($_POST['poubelle_id'])) {
                    if ($poubelle->alerterPleine($_POST['poubelle_id'], $user_id)) {
                        $message = "Alerte envoyée ! Un collecteur sera assigné automatiquement.";
                    } else {
                        $error = "Erreur lors de l'envoi de l'alerte.";
                    }
                }
                break;
                
            case 'desactiver':
                if (!empty($_POST['poubelle_id'])) {
                    if ($poubelle->desactiverAlerte($_POST['poubelle_id'], $user_id)) {
                        $message = "Poubelle marquée comme vidée.";
                    } else {
                        $error = "Erreur lors de la désactivation de l'alerte.";
                    }
                }
                break;
                
            case 'delete':
                if (!empty($_POST['poubelle_id'])) {
                    if ($poubelle->delete($_POST['poubelle_id'], $user_id)) {
                        $message = "Poubelle supprimée avec succès.";
                    } else {
                        $error = "Erreur lors de la suppression.";
                    }
                }
                break;
        }
    }
}

// Récupérer les poubelles du ménage
$mes_poubelles = $poubelle->getByMenage($user_id);
$stats = $poubelle->getStatsMenage($user_id);

$page_title = 'Mes Poubelles';

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
    
    .btn-primary {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: transform 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
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
        color: #4facfe;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.9rem;
    }
    
    .poubelles-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .poubelle-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        transition: transform 0.3s ease;
    }
    
    .poubelle-card:hover {
        transform: translateY(-5px);
    }
    
    .poubelle-header {
        padding: 1.5rem;
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .poubelle-type {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    .poubelle-status {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .status-vide {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }
    
    .status-pleine {
        background: #e74c3c;
        color: white;
    }
    
    .poubelle-body {
        padding: 1.5rem;
    }
    
    .poubelle-info {
        margin-bottom: 1rem;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }
    
    .info-label {
        color: #666;
    }
    
    .info-value {
        font-weight: 600;
        color: #333;
    }
    
    .alerte-info {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        color: #856404;
    }
    
    .alerte-info.urgent {
        background: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
    }
    
    .poubelle-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 5px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .btn-success {
        background: #27ae60;
        color: white;
    }
    
    .btn-warning {
        background: #f39c12;
        color: white;
    }
    
    .btn-danger {
        background: #e74c3c;
        color: white;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn:hover {
        transform: translateY(-1px);
        opacity: 0.9;
    }
    
    .create-form {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        color: #333;
        font-weight: 600;
    }
    
    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #4facfe;
    }
    
    .type-selector {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }
    
    .type-option {
        position: relative;
    }
    
    .type-option input[type="radio"] {
        position: absolute;
        opacity: 0;
    }
    
    .type-label {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 1rem;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }
    
    .type-option input[type="radio"]:checked + .type-label {
        border-color: #4facfe;
        background: rgba(79, 172, 254, 0.1);
    }
    
    .type-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        color: #4facfe;
    }
    
    .type-name {
        font-weight: 600;
        color: #333;
    }
    
    .message {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
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
        .page-header {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }
        
        .poubelles-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="page-header">
    <h1 class="page-title">Mes Poubelles</h1>
    <a href="#create-form" class="btn-primary" onclick="toggleCreateForm()">
        <i class="fas fa-plus"></i>
        Créer une poubelle
    </a>
</div>

<?php if ($message): ?>
    <div class="message success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Statistiques -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total_poubelles']; ?></div>
        <div class="stat-label">Total créées</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['poubelles_evacuees']; ?></div>
        <div class="stat-label">Évacuées</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['poubelles_en_attente']; ?></div>
        <div class="stat-label">En attente</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['temps_attente_moyen']; ?>h</div>
        <div class="stat-label">Temps d'attente moyen</div>
    </div>
</div>

<!-- Formulaire de création -->
<div id="create-form" class="create-form" style="display: <?php echo (isset($_GET['action']) && $_GET['action'] == 'create') ? 'block' : 'none'; ?>;">
    <h3 style="margin-bottom: 1.5rem; color: #333;">
        <i class="fas fa-plus-circle"></i>
        Créer une nouvelle poubelle
    </h3>
    
    <form method="POST">
        <input type="hidden" name="action" value="create">
        
        <div class="form-group">
            <label class="form-label">Type de poubelle *</label>
            <div class="type-selector">
                <div class="type-option">
                    <input type="radio" id="organique" name="type" value="organique" required>
                    <label for="organique" class="type-label">
                        <div class="type-icon">🥬</div>
                        <div class="type-name">Organique</div>
                    </label>
                </div>
                <div class="type-option">
                    <input type="radio" id="plastique" name="type" value="plastique" required>
                    <label for="plastique" class="type-label">
                        <div class="type-icon">♻️</div>
                        <div class="type-name">Plastique</div>
                    </label>
                </div>
                <div class="type-option">
                    <input type="radio" id="mixte" name="type" value="mixte" required>
                    <label for="mixte" class="type-label">
                        <div class="type-icon">🗑️</div>
                        <div class="type-name">Mixte</div>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="description" class="form-label">Description (optionnel)</label>
            <textarea id="description" name="description" class="form-control" rows="3" 
                      placeholder="Ex: Poubelle de la cuisine, du salon..."></textarea>
        </div>
        
        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i>
                Créer la poubelle
            </button>
            <button type="button" class="btn btn-secondary" onclick="toggleCreateForm()">
                <i class="fas fa-times"></i>
                Annuler
            </button>
        </div>
    </form>
</div>

<!-- Liste des poubelles -->
<?php if (empty($mes_poubelles)): ?>
    <div class="empty-state">
        <i class="fas fa-trash"></i>
        <h3>Aucune poubelle créée</h3>
        <p>Commencez par créer votre première poubelle pour gérer vos déchets.</p>
    </div>
<?php else: ?>
    <div class="poubelles-grid">
        <?php foreach ($mes_poubelles as $p): ?>
            <div class="poubelle-card">
                <div class="poubelle-header">
                    <div class="poubelle-type">
                        <?php 
                        $icons = [
                            'organique' => '🥬',
                            'plastique' => '♻️',
                            'mixte' => '🗑️'
                        ];
                        echo $icons[$p['type']] . ' ' . ucfirst($p['type']);
                        ?>
                    </div>
                    <div class="poubelle-status <?php echo $p['statut'] == 'pleine' ? 'status-pleine' : 'status-vide'; ?>">
                        <?php echo $p['statut'] == 'pleine' ? 'Pleine' : 'Vide'; ?>
                    </div>
                </div>
                
                <div class="poubelle-body">
                    <?php if ($p['description']): ?>
                        <div class="poubelle-info">
                            <strong>Description :</strong> <?php echo htmlspecialchars($p['description']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="poubelle-info">
                        <div class="info-row">
                            <span class="info-label">Créée le :</span>
                            <span class="info-value"><?php echo date('d/m/Y à H:i', strtotime($p['created_at'])); ?></span>
                        </div>
                        
                        <?php if ($p['date_collecte']): ?>
                            <div class="info-row">
                                <span class="info-label">Dernière collecte :</span>
                                <span class="info-value"><?php echo date('d/m/Y à H:i', strtotime($p['date_collecte'])); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($p['alerte_pleine'] && !$p['date_collecte']): ?>
                        <div class="alerte-info <?php echo $p['heures_depuis_alerte'] > 24 ? 'urgent' : ''; ?>">
                            <strong>⚠️ Alerte active</strong><br>
                            Depuis <?php echo $p['heures_depuis_alerte']; ?> heure(s)
                            <?php if ($p['heures_depuis_alerte'] > 24): ?>
                                <br><em>Collecte urgente nécessaire !</em>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="poubelle-actions">
                        <?php if (!$p['alerte_pleine']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="alerter">
                                <input type="hidden" name="poubelle_id" value="<?php echo $p['id']; ?>">
                                <button type="submit" class="btn btn-warning" 
                                        onclick="return confirm('Confirmer que cette poubelle est pleine ?')">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Alerter pleine
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="desactiver">
                                <input type="hidden" name="poubelle_id" value="<?php echo $p['id']; ?>">
                                <button type="submit" class="btn btn-success"
                                        onclick="return confirm('Confirmer que cette poubelle a été vidée ?')">
                                    <i class="fas fa-check"></i>
                                    Marquer vidée
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="poubelle_id" value="<?php echo $p['id']; ?>">
                            <button type="submit" class="btn btn-danger"
                                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette poubelle ?')">
                                <i class="fas fa-trash"></i>
                                Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function toggleCreateForm() {
    const form = document.getElementById('create-form');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    
    if (form.style.display === 'block') {
        form.scrollIntoView({ behavior: 'smooth' });
    }
}

// Afficher le formulaire si l'URL contient action=create
if (window.location.search.includes('action=create')) {
    document.getElementById('create-form').style.display = 'block';
}
</script>

<?php
$content = ob_get_clean();
include 'components/layout.php';
?> 