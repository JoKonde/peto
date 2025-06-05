<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PETO - Système de Gestion des Déchets</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        
        .logo {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        h1 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 2.5rem;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        .buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #e9ecef;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .features {
            margin-top: 2rem;
            text-align: left;
        }
        
        .feature {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            color: #666;
        }
        
        .feature i {
            color: #667eea;
            width: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <i class="fas fa-recycle"></i>
        </div>
        <h1>PETO</h1>
        <p class="subtitle">Système de Gestion des Déchets - Kinshasa</p>
        
        <div class="buttons">
            <a href="register.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i>
                Créer un compte ménage
            </a>
            <a href="login.php" class="btn btn-secondary">
                <i class="fas fa-sign-in-alt"></i>
                Se connecter
            </a>
        </div>
        
        <div class="features">
            <div class="feature">
                <i class="fas fa-home"></i>
                <span>Gestion simple des poubelles</span>
            </div>
            <div class="feature">
                <i class="fas fa-truck"></i>
                <span>Collecte automatisée</span>
            </div>
            <div class="feature">
                <i class="fas fa-chart-line"></i>
                <span>Suivi en temps réel</span>
            </div>
        </div>
    </div>
</body>
</html> 