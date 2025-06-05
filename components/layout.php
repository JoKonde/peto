<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_role = $_SESSION['user_role'];
$user_nom = $_SESSION['user_nom'];
$user_prenom = $_SESSION['user_prenom'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?> - PETO</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header .logo {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .user-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            margin: 1rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-size: 1.5rem;
        }
        
        .user-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .user-role {
            font-size: 0.9rem;
            opacity: 0.8;
            text-transform: capitalize;
        }
        
        .nav-menu {
            padding: 1rem 0;
        }
        
        .nav-item {
            margin-bottom: 0.5rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: white;
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 1rem;
        }
        
        .nav-section {
            padding: 1rem 1.5rem 0.5rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            opacity: 0.7;
            font-weight: 600;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title {
            color: #333;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .btn-logout {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.3s ease;
        }
        
        .btn-logout:hover {
            background: #c82333;
        }
        
        .content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-toggle {
                display: block;
                background: #667eea;
                color: white;
                border: none;
                padding: 0.5rem;
                border-radius: 5px;
                cursor: pointer;
            }
        }
        
        .mobile-toggle {
            display: none;
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-recycle"></i>
            </div>
            <h2>PETO</h2>
        </div>
        
        <div class="user-info">
            <div class="user-avatar">
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
            <div class="user-name"><?php echo htmlspecialchars($user_prenom . ' ' . $user_nom); ?></div>
            <div class="user-role"><?php echo htmlspecialchars($user_role); ?></div>
        </div>
        
        <nav class="nav-menu">
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Tableau de bord</span>
                </a>
            </div>
            
            <?php if ($user_role == 'admin'): ?>
                <div class="nav-section">Administration</div>
                <div class="nav-item">
                    <a href="admin-menages.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Gestion Ménages</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="admin-collecteurs.php" class="nav-link">
                        <i class="fas fa-truck"></i>
                        <span>Gestion Collecteurs</span>
                    </a>
                </div>
                
            <?php endif; ?>
            
            <?php if ($user_role == 'menage'): ?>
                <div class="nav-section">Mes Poubelles</div>
                <div class="nav-item">
                    <a href="menage-poubelles.php" class="nav-link">
                        <i class="fas fa-trash"></i>
                        <span>Mes Poubelles</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="menage-collectes.php" class="nav-link">
                        <i class="fas fa-calendar"></i>
                        <span>Mes Collectes</span>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($user_role == 'collecteur'): ?>
                <div class="nav-section">Mes Tâches</div>
                <div class="nav-item">
                    <a href="collecteur-dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'collecteur-dashboard.php') ? 'active' : ''; ?>">
                        <i class="fas fa-tasks"></i>
                        <span>Mes Tâches</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="collecteur-dashboard.php#classement" class="nav-link">
                        <i class="fas fa-trophy"></i>
                        <span>Classement</span>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="nav-section">Compte</div>
            <div class="nav-item">
                <a href="profil.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    <span>Mon Profil</span>
                </a>
            </div>
            
            <div class="nav-section">Statistiques</div>
            <div class="nav-item">
                <a href="classement-collecteurs.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'classement-collecteurs.php') ? 'active' : ''; ?>">
                    <i class="fas fa-trophy"></i>
                    <span>Classement Collecteurs</span>
                </a>
            </div>
        </nav>
    </div>
    
    <div class="main-content">
        <header class="header">
            <div>
                <button class="mobile-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="header-title"><?php echo isset($page_title) ? $page_title : 'Tableau de bord'; ?></span>
            </div>
            <div class="header-actions">
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Déconnexion
                </a>
            </div>
        </header>
        
        <main class="content">
            <?php echo $content; ?>
        </main>
    </div>
    
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
        
        // Fermer le sidebar sur mobile quand on clique sur un lien
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('active');
                }
            });
        });
    </script>
</body>
</html> 