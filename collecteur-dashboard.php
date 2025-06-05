<?php
session_start();

// Vérifier la suspension
require_once 'components/check_suspension.php';

// Vérifier si l'utilisateur est connecté et est collecteur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'collecteur') {
    header('Location: login.php');
    exit();
}

require_once 'classes/Collecteur.php';

$collecteur = new Collecteur();
$user_id = $_SESSION['user_id'];
$user_nom = $_SESSION['user_nom'];
$user_prenom = $_SESSION['user_prenom'];
$message = '';
$error = '';

// Traitement des actions
if ($_POST) {
    if (isset($_POST['action']) && isset($_POST['tache_id'])) {
        $tache_id = $_POST['tache_id'];
        
        switch ($_POST['action']) {
            case 'accepter':
                if ($collecteur->accepterTache($tache_id, $user_id)) {
                    $message = "Tâche acceptée avec succès !";
                } else {
                    $error = "Erreur lors de l'acceptation de la tâche.";
                }
                break;
                
            case 'en_route':
                if ($collecteur->marquerEnRoute($tache_id, $user_id)) {
                    $message = "Statut mis à jour : En route !";
                } else {
                    $error = "Erreur lors de la mise à jour du statut.";
                }
                break;
                
            case 'arrive':
                if ($collecteur->marquerArrive($tache_id, $user_id)) {
                    $message = "Statut mis à jour : Arrivé chez le ménage !";
                } else {
                    $error = "Erreur lors de la mise à jour du statut.";
                }
                break;
                
            case 'collecte':
                if ($collecteur->marquerCollecte($tache_id, $user_id)) {
                    $message = "Collecte effectuée ! En attente de confirmation du ménage.";
                } else {
                    $error = "Erreur lors de la mise à jour du statut.";
                }
                break;
        }
    }
}

// Filtres
$filtre_statut = isset($_GET['statut']) ? $_GET['statut'] : 'tous';
$filtre_type = isset($_GET['type']) ? $_GET['type'] : 'tous';
$filtre_urgence = isset($_GET['urgence']) ? $_GET['urgence'] : 'tous';

// Récupérer les tâches et statistiques
$mes_taches = $collecteur->getTaches($user_id);
$stats = $collecteur->getStatsCollecteur($user_id);
$classement = $collecteur->getClassementCollecteurs();

// Appliquer les filtres
$taches_filtrees = $mes_taches;
if ($filtre_statut !== 'tous') {
    $taches_filtrees = array_filter($taches_filtrees, function($tache) use ($filtre_statut) {
        return $tache['statut'] === $filtre_statut;
    });
}
if ($filtre_type !== 'tous') {
    $taches_filtrees = array_filter($taches_filtrees, function($tache) use ($filtre_type) {
        return $tache['poubelle_type'] === $filtre_type;
    });
}
if ($filtre_urgence === 'urgent') {
    $taches_filtrees = array_filter($taches_filtrees, function($tache) {
        return $tache['heures_depuis_creation'] > 24;
    });
}

// Statistiques du jour
$taches_aujourd_hui = array_filter($mes_taches, function($tache) {
    return date('Y-m-d', strtotime($tache['created_at'])) === date('Y-m-d');
});

$taches_terminees_aujourd_hui = array_filter($taches_aujourd_hui, function($tache) {
    return $tache['statut'] === 'terminee';
});

// Position dans le classement
$ma_position = 0;
foreach ($classement as $index => $collecteur_stats) {
    if ($collecteur_stats['collecteur_id'] == $user_id) {
        $ma_position = $index + 1;
        break;
    }
}

$page_title = 'Dashboard Collecteur';

// Contenu de la page
ob_start();
?>

<style>
    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .page-header {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .page-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
        animation: float 20s infinite linear;
    }
    
    @keyframes float {
        0% { transform: translateX(-100px) translateY(-100px); }
        100% { transform: translateX(100px) translateY(100px); }
    }
    
    .page-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        position: relative;
        z-index: 1;
    }
    
    .page-subtitle {
        font-size: 1.2rem;
        opacity: 0.9;
        margin: 0;
        position: relative;
        z-index: 1;
    }
    
    .quick-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .quick-stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .quick-stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #fa709a, #fee140);
    }
    
    .quick-stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
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
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .stat-change {
        font-size: 0.8rem;
        margin-top: 0.25rem;
    }
    
    .stat-change.positive { color: #27ae60; }
    .stat-change.negative { color: #e74c3c; }
    
    .stat-total .stat-icon { background: linear-gradient(135deg, #fa709a, #fee140); }
    .stat-total .stat-number { color: #fa709a; }
    
    .stat-terminees .stat-icon { background: linear-gradient(135deg, #27ae60, #2ecc71); }
    .stat-terminees .stat-number { color: #27ae60; }
    
    .stat-en-cours .stat-icon { background: linear-gradient(135deg, #3498db, #74b9ff); }
    .stat-en-cours .stat-number { color: #3498db; }
    
    .stat-en-attente .stat-icon { background: linear-gradient(135deg, #f39c12, #fdcb6e); }
    .stat-en-attente .stat-number { color: #f39c12; }
    
    .stat-taux .stat-icon { background: linear-gradient(135deg, #9b59b6, #a29bfe); }
    .stat-taux .stat-number { color: #9b59b6; }
    
    .stat-position .stat-icon { background: linear-gradient(135deg, #e17055, #fd79a8); }
    .stat-position .stat-number { color: #e17055; }
    
    .dashboard-content {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
    }
    
    .main-content {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }
    
    .sidebar-content {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }
    
    .filters-container {
        background: white;
        padding: 1.5rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }
    
    .filters-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .filter-label {
        font-weight: 600;
        color: #333;
        font-size: 0.9rem;
    }
    
    .filter-select {
        padding: 0.5rem;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: border-color 0.3s ease;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: #fa709a;
    }
    
    .taches-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    .taches-header {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: white;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .taches-header h3 {
        margin: 0;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .taches-count {
        background: rgba(255, 255, 255, 0.2);
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.9rem;
    }
    
    .tache-item {
        padding: 1.5rem;
        border-bottom: 1px solid #f0f0f0;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .tache-item:last-child {
        border-bottom: none;
    }
    
    .tache-item:hover {
        background-color: #f8f9fa;
        transform: translateX(5px);
    }
    
    .tache-item.urgent {
        border-left: 4px solid #e74c3c;
    }
    
    .tache-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    
    .tache-info {
        flex: 1;
    }
    
    .tache-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .tache-meta {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .tache-status {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        white-space: nowrap;
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
    
    .tache-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .detail-group {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .detail-label {
        font-weight: 600;
        color: #333;
        font-size: 0.8rem;
        text-transform: uppercase;
    }
    
    .detail-value {
        color: #666;
        font-size: 0.9rem;
    }
    
    .tache-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }
    
    .btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #27ae60, #2ecc71);
        color: white;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #3498db, #74b9ff);
        color: white;
    }
    
    .btn-warning {
        background: linear-gradient(135deg, #f39c12, #fdcb6e);
        color: white;
    }
    
    .btn-info {
        background: linear-gradient(135deg, #17a2b8, #00cec9);
        color: white;
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, #6c757d, #95a5a6);
        color: white;
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
    
    .urgence-badge {
        background: linear-gradient(135deg, #e74c3c, #fd79a8);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        margin-left: 0.5rem;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
    
    .performance-card {
        background: white;
        padding: 1.5rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .performance-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .performance-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .performance-item:last-child {
        border-bottom: none;
    }
    
    .performance-label {
        font-weight: 600;
        color: #333;
    }
    
    .performance-value {
        font-weight: 700;
        color: #fa709a;
    }
    
    .progress-bar {
        width: 100%;
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 0.5rem;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #fa709a, #fee140);
        transition: width 0.3s ease;
    }
    
    @media (max-width: 1200px) {
        .dashboard-content {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .quick-stats {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .filters-grid {
            grid-template-columns: 1fr;
        }
        
        .tache-header {
            flex-direction: column;
            gap: 1rem;
        }
        
        .tache-details {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dashboard-container">
    <!-- En-tête du dashboard -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-truck"></i>
            Dashboard Collecteur
        </h1>
        <p class="page-subtitle">Bienvenue <?php echo htmlspecialchars($user_prenom . ' ' . $user_nom); ?> - Gérez vos collectes efficacement</p>
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

    <!-- Statistiques rapides -->
    <div class="quick-stats">
        <div class="quick-stat-card stat-total">
            <div class="stat-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-number"><?php echo $stats['total_taches']; ?></div>
            <div class="stat-label">Tâches totales</div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                +<?php echo count($taches_aujourd_hui); ?> aujourd'hui
            </div>
        </div>
        
        <div class="quick-stat-card stat-terminees">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-number"><?php echo $stats['taches_terminees']; ?></div>
            <div class="stat-label">Terminées</div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                +<?php echo count($taches_terminees_aujourd_hui); ?> aujourd'hui
            </div>
        </div>
        
        <div class="quick-stat-card stat-en-cours">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-number"><?php echo $stats['taches_en_cours']; ?></div>
            <div class="stat-label">En cours</div>
        </div>
        
        <div class="quick-stat-card stat-en-attente">
            <div class="stat-icon">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="stat-number"><?php echo $stats['taches_en_attente']; ?></div>
            <div class="stat-label">En attente</div>
        </div>
        
        <div class="quick-stat-card stat-taux">
            <div class="stat-icon">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="stat-number"><?php echo $stats['taux_reussite']; ?>%</div>
            <div class="stat-label">Taux de réussite</div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $stats['taux_reussite']; ?>%"></div>
            </div>
        </div>
        
        <div class="quick-stat-card stat-position">
            <div class="stat-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-number"><?php echo $ma_position > 0 ? '#' . $ma_position : '-'; ?></div>
            <div class="stat-label">Position classement</div>
            <?php if ($ma_position <= 3 && $ma_position > 0): ?>
                <div class="stat-change positive">
                    <i class="fas fa-medal"></i>
                    Top 3 !
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filters-container">
        <h3 class="filters-title">
            <i class="fas fa-filter"></i>
            Filtrer les tâches
        </h3>
        <form method="GET" class="filters-grid">
            <div class="filter-group">
                <label class="filter-label">Statut</label>
                <select name="statut" class="filter-select" onchange="this.form.submit()">
                    <option value="tous" <?php echo $filtre_statut === 'tous' ? 'selected' : ''; ?>>Tous</option>
                    <option value="en_attente" <?php echo $filtre_statut === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                    <option value="acceptee" <?php echo $filtre_statut === 'acceptee' ? 'selected' : ''; ?>>Acceptées</option>
                    <option value="en_route" <?php echo $filtre_statut === 'en_route' ? 'selected' : ''; ?>>En route</option>
                    <option value="arrive" <?php echo $filtre_statut === 'arrive' ? 'selected' : ''; ?>>Arrivé</option>
                    <option value="collectee" <?php echo $filtre_statut === 'collectee' ? 'selected' : ''; ?>>Collectées</option>
                    <option value="terminee" <?php echo $filtre_statut === 'terminee' ? 'selected' : ''; ?>>Terminées</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Type de déchet</label>
                <select name="type" class="filter-select" onchange="this.form.submit()">
                    <option value="tous" <?php echo $filtre_type === 'tous' ? 'selected' : ''; ?>>Tous</option>
                    <option value="organique" <?php echo $filtre_type === 'organique' ? 'selected' : ''; ?>>🥬 Organique</option>
                    <option value="plastique" <?php echo $filtre_type === 'plastique' ? 'selected' : ''; ?>>♻️ Plastique</option>
                    <option value="mixte" <?php echo $filtre_type === 'mixte' ? 'selected' : ''; ?>>🗑️ Mixte</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Urgence</label>
                <select name="urgence" class="filter-select" onchange="this.form.submit()">
                    <option value="tous" <?php echo $filtre_urgence === 'tous' ? 'selected' : ''; ?>>Tous</option>
                    <option value="urgent" <?php echo $filtre_urgence === 'urgent' ? 'selected' : ''; ?>>🚨 Urgent (+24h)</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">&nbsp;</label>
                <a href="collecteur-dashboard.php" class="btn btn-secondary" style="text-align: center;">
                    <i class="fas fa-times"></i>
                    Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <div class="dashboard-content">
        <!-- Contenu principal -->
        <div class="main-content">
            <!-- Liste des tâches -->
            <div class="taches-container">
                <div class="taches-header">
                    <h3>
                        <i class="fas fa-list"></i>
                        Mes Tâches
                    </h3>
                    <span class="taches-count"><?php echo count($taches_filtrees); ?> tâche(s)</span>
                </div>
                
                <?php if (empty($taches_filtrees)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>Aucune tâche trouvée</h3>
                        <p>
                            <?php if ($filtre_statut !== 'tous' || $filtre_type !== 'tous' || $filtre_urgence !== 'tous'): ?>
                                Aucune tâche ne correspond aux filtres sélectionnés.
                            <?php else: ?>
                                Vos nouvelles tâches apparaîtront ici automatiquement.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($taches_filtrees as $tache): ?>
                        <div class="tache-item <?php echo $tache['heures_depuis_creation'] > 24 ? 'urgent' : ''; ?>">
                            <div class="tache-header">
                                <div class="tache-info">
                                    <div class="tache-title">
                                        <?php 
                                        $icons = [
                                            'organique' => '🥬',
                                            'plastique' => '♻️',
                                            'mixte' => '🗑️'
                                        ];
                                        echo $icons[$tache['poubelle_type']];
                                        ?>
                                        Collecte <?php echo ucfirst($tache['poubelle_type']); ?>
                                        <?php if ($tache['heures_depuis_creation'] > 24): ?>
                                            <span class="urgence-badge">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                Urgent
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tache-meta">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($tache['menage_prenom'] . ' ' . $tache['menage_nom']); ?>
                                    </div>
                                    <div class="tache-meta">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($tache['adresse']); ?>
                                    </div>
                                    <div class="tache-meta">
                                        <i class="fas fa-clock"></i>
                                        Assignée il y a <?php echo $tache['heures_depuis_creation']; ?> heure(s)
                                    </div>
                                    <?php if ($tache['poubelle_description']): ?>
                                        <div class="tache-meta">
                                            <i class="fas fa-info-circle"></i>
                                            <?php echo htmlspecialchars($tache['poubelle_description']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="tache-status status-<?php echo $tache['statut']; ?>">
                                    <?php 
                                    $statuts = [
                                        'en_attente' => 'En attente',
                                        'acceptee' => 'Acceptée',
                                        'en_route' => 'En route',
                                        'arrive' => 'Arrivé',
                                        'collectee' => 'Collectée',
                                        'terminee' => 'Terminée'
                                    ];
                                    echo $statuts[$tache['statut']];
                                    ?>
                                </div>
                            </div>
                            
                            <div class="tache-details">
                                <div class="detail-group">
                                    <div class="detail-label">Contact ménage</div>
                                    <div class="detail-value">
                                        <i class="fas fa-phone"></i>
                                        <?php echo htmlspecialchars($tache['menage_tel']); ?>
                                    </div>
                                </div>
                                
                                <div class="detail-group">
                                    <div class="detail-label">Date assignation</div>
                                    <div class="detail-value">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('d/m/Y à H:i', strtotime($tache['date_assignation'])); ?>
                                    </div>
                                </div>
                                
                                <?php if ($tache['date_completion']): ?>
                                    <div class="detail-group">
                                        <div class="detail-label">Date completion</div>
                                        <div class="detail-value">
                                            <i class="fas fa-check-circle"></i>
                                            <?php echo date('d/m/Y à H:i', strtotime($tache['date_completion'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="detail-group">
                                    <div class="detail-label">Priorité</div>
                                    <div class="detail-value">
                                        <?php if ($tache['heures_depuis_creation'] > 48): ?>
                                            <span style="color: #e74c3c;">🔴 Très urgent</span>
                                        <?php elseif ($tache['heures_depuis_creation'] > 24): ?>
                                            <span style="color: #f39c12;">🟡 Urgent</span>
                                        <?php else: ?>
                                            <span style="color: #27ae60;">🟢 Normal</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Actions selon le statut -->
                            <div class="tache-actions">
                                <?php if ($tache['statut'] == 'en_attente'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="accepter">
                                        <input type="hidden" name="tache_id" value="<?php echo $tache['id']; ?>">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check"></i>
                                            Accepter la tâche
                                        </button>
                                    </form>
                                    
                                <?php elseif ($tache['statut'] == 'acceptee'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="en_route">
                                        <input type="hidden" name="tache_id" value="<?php echo $tache['id']; ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-route"></i>
                                            Je suis en route
                                        </button>
                                    </form>
                                    
                                <?php elseif ($tache['statut'] == 'en_route'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="arrive">
                                        <input type="hidden" name="tache_id" value="<?php echo $tache['id']; ?>">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-map-marker-alt"></i>
                                            Je suis arrivé
                                        </button>
                                    </form>
                                    
                                <?php elseif ($tache['statut'] == 'arrive'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="collecte">
                                        <input type="hidden" name="tache_id" value="<?php echo $tache['id']; ?>">
                                        <button type="submit" class="btn btn-info">
                                            <i class="fas fa-trash"></i>
                                            Collecte effectuée
                                        </button>
                                    </form>
                                    
                                <?php elseif ($tache['statut'] == 'collectee'): ?>
                                    <div class="btn btn-secondary">
                                        <i class="fas fa-hourglass-half"></i>
                                        En attente de confirmation du ménage
                                    </div>
                                    
                                <?php elseif ($tache['statut'] == 'terminee'): ?>
                                    <div class="btn btn-success">
                                        <i class="fas fa-check-circle"></i>
                                        Tâche terminée
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="sidebar-content">
            <!-- Performance personnelle -->
            <div class="performance-card">
                <h3 class="performance-title">
                    <i class="fas fa-chart-line"></i>
                    Ma Performance
                </h3>
                
                <div class="performance-item">
                    <span class="performance-label">Temps moyen/tâche</span>
                    <span class="performance-value"><?php echo $stats['temps_moyen_minutes']; ?> min</span>
                </div>
                
                <div class="performance-item">
                    <span class="performance-label">Tâches aujourd'hui</span>
                    <span class="performance-value"><?php echo count($taches_aujourd_hui); ?></span>
                </div>
                
                <div class="performance-item">
                    <span class="performance-label">Terminées aujourd'hui</span>
                    <span class="performance-value"><?php echo count($taches_terminees_aujourd_hui); ?></span>
                </div>
                
                <div class="performance-item">
                    <span class="performance-label">Position classement</span>
                    <span class="performance-value">
                        <?php if ($ma_position > 0): ?>
                            #<?php echo $ma_position; ?>/<?php echo count($classement); ?>
                        <?php else: ?>
                            Non classé
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <!-- Top 3 du classement -->
            <?php if (!empty($classement)): ?>
            <div class="performance-card">
                <h3 class="performance-title">
                    <i class="fas fa-trophy"></i>
                    Top 3 Collecteurs
                </h3>
                
                <?php foreach (array_slice($classement, 0, 3) as $index => $collecteur_stats): ?>
                    <div class="performance-item <?php echo $collecteur_stats['collecteur_id'] == $user_id ? 'bg-light' : ''; ?>">
                        <span class="performance-label">
                            <?php 
                            $medals = ['🥇', '🥈', '🥉'];
                            echo $medals[$index] . ' ';
                            echo htmlspecialchars($collecteur_stats['prenom'] . ' ' . $collecteur_stats['nom']);
                            if ($collecteur_stats['collecteur_id'] == $user_id) echo ' (Vous)';
                            ?>
                        </span>
                        <span class="performance-value"><?php echo $collecteur_stats['score_performance']; ?>/100</span>
                    </div>
                <?php endforeach; ?>
                
                <div style="margin-top: 1rem;">
                    <a href="classement-collecteurs.php" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fas fa-trophy"></i>
                        Voir le classement complet
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'components/layout.php';
?> 