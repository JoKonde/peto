<?php
session_start();

// Si l'utilisateur est déjà connecté, rediriger vers le dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

require_once 'classes/User.php';

$error = '';
$suspended_message = '';

// Vérifier si l'utilisateur a été déconnecté pour suspension
if (isset($_GET['suspended']) && $_GET['suspended'] == '1') {
    $suspended_message = 'Votre compte a été suspendu par l\'administrateur. Vous avez été automatiquement déconnecté. Contactez le support pour plus d\'informations.';
}

if ($_POST) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (!empty($email) && !empty($password)) {
        $user = new User();
        $result = $user->login($email, $password);
        
        if ($result['success']) {
            // Connexion réussie
            $user_data = $result['user'];
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['user_role'] = $user_data['role'];
            $_SESSION['user_nom'] = $user_data['nom'];
            $_SESSION['user_prenom'] = $user_data['prenom'];
            $_SESSION['user_email'] = $user_data['email'];
            
            header('Location: dashboard.php');
            exit();
        } else {
            // Vérifier si c'est une suspension
            if (isset($result['suspended']) && $result['suspended']) {
                $suspended_message = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    } else {
        $error = 'Veuillez remplir tous les champs.';
    }
}

$page_title = 'Connexion';

?>

<style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .login-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 3rem;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 450px;
        text-align: center;
    }
    
    .logo {
        font-size: 3rem;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 0.5rem;
    }
    
    .subtitle {
        color: #666;
        margin-bottom: 2rem;
        font-size: 1.1rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
        text-align: left;
    }
    
    .form-label {
        display: block;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
    }
    
    .form-input {
        width: 100%;
        padding: 1rem;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-size: 1rem;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .btn {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }
    
    .error-message {
        background: #f8d7da;
        color: #721c24;
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        border: 1px solid #f5c6cb;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .suspended-message {
        background: #fff3cd;
        color: #856404;
        padding: 1.5rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        border: 1px solid #ffeaa7;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        text-align: left;
    }
    
    .suspended-message i {
        font-size: 1.5rem;
        margin-top: 0.25rem;
        color: #f39c12;
    }
    
    .suspended-content h4 {
        margin: 0 0 0.5rem 0;
        color: #856404;
        font-size: 1.1rem;
    }
    
    .suspended-content p {
        margin: 0;
        line-height: 1.5;
    }
    
    .links {
        text-align: center;
    }
    
    .links a {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }
    
    .links a:hover {
        color: #764ba2;
    }
    
    .test-accounts {
        background: #e8f4fd;
        border: 1px solid #bee5eb;
        border-radius: 10px;
        padding: 1.5rem;
        margin-top: 2rem;
        text-align: left;
    }
    
    .test-accounts h4 {
        color: #0c5460;
        margin: 0 0 1rem 0;
        font-size: 1rem;
        text-align: center;
    }
    
    .test-account {
        background: white;
        padding: 0.75rem;
        border-radius: 8px;
        margin-bottom: 0.75rem;
        border-left: 4px solid #17a2b8;
    }
    
    .test-account:last-child {
        margin-bottom: 0;
    }
    
    .test-account strong {
        color: #0c5460;
    }
    
    .test-account small {
        color: #6c757d;
        display: block;
        margin-top: 0.25rem;
    }
    
    @media (max-width: 768px) {
        .login-container {
            margin: 1rem;
            padding: 2rem;
        }
        
        .logo {
            font-size: 2.5rem;
        }
    }
</style>

<div class="login-container">
    <div class="logo">PETO</div>
    <p class="subtitle">Système de Gestion des Déchets</p>
    
    <?php if ($suspended_message): ?>
        <div class="suspended-message">
            <i class="fas fa-ban"></i>
            <div class="suspended-content">
                <h4>Compte Suspendu</h4>
                <p><?php echo htmlspecialchars($suspended_message); ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-input" required 
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Mot de passe</label>
            <input type="password" name="password" class="form-input" required>
        </div>
        
        <button type="submit" class="btn">
            <i class="fas fa-sign-in-alt"></i>
            Se connecter
        </button>
    </form>
    
    <div class="links">
        <a href="register.php">Créer un compte ménage</a> | 
        <a href="index.php">Retour à l'accueil</a>
    </div>
    
    
</div>
