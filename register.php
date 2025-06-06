<?php
session_start();

// Traitement du formulaire
if ($_POST) {
    require_once 'classes/User.php';
    
    $user = new User();
    $message = '';
    $error = '';
    
    // Validation simple
    if (empty($_POST['nom']) || empty($_POST['prenom']) || empty($_POST['email']) || empty($_POST['password'])) {
        $error = "Tous les champs obligatoires doivent être remplis.";
    } elseif (strlen($_POST['password']) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        try {
            if ($user->createMenage($_POST)) {
                $message = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
            } else {
                $error = "Erreur lors de la création du compte.";
            }
        } catch (Exception $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - PETO</title>
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
            padding: 2rem 0;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .subtitle {
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
        }
        
        .required {
            color: #e74c3c;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .container {
                margin: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1>Inscription Ménage</h1>
            <p class="subtitle">Créez votre compte pour gérer vos déchets</p>
        </div>
        
        <?php if (isset($message) && $message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error) && $error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="nom">Nom <span class="required">*</span></label>
                    <input type="text" id="nom" name="nom" required value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="prenom">Prénom <span class="required">*</span></label>
                    <input type="text" id="prenom" name="prenom" required value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="telephone">Téléphone <span class="required">*</span></label>
                    <input type="tel" id="telephone" name="telephone" required value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="adresse">Adresse complète <span class="required">*</span></label>
                <textarea id="adresse" name="adresse" rows="3" required><?php echo isset($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : ''; ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="commune">Commune <span class="required">*</span></label>
                    <select id="commune" name="commune" required>
                        <option value="">Sélectionnez une commune</option>
                        <option value="Bandalungwa" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Bandalungwa') ? 'selected' : ''; ?>>Bandalungwa</option>
                        <option value="Barumbu" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Barumbu') ? 'selected' : ''; ?>>Barumbu</option>
                        <option value="Gombe" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Gombe') ? 'selected' : ''; ?>>Gombe</option>
                        <option value="Kalamu" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Kalamu') ? 'selected' : ''; ?>>Kalamu</option>
                        <option value="Kasa-Vubu" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Kasa-Vubu') ? 'selected' : ''; ?>>Kasa-Vubu</option>
                        <option value="Kinshasa" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Kinshasa') ? 'selected' : ''; ?>>Kinshasa</option>
                        <option value="Kintambo" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Kintambo') ? 'selected' : ''; ?>>Kintambo</option>
                        <option value="Lemba" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Lemba') ? 'selected' : ''; ?>>Lemba</option>
                        <option value="Limete" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Limete') ? 'selected' : ''; ?>>Limete</option>
                        <option value="Lingwala" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Lingwala') ? 'selected' : ''; ?>>Lingwala</option>
                        <option value="Makala" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Makala') ? 'selected' : ''; ?>>Makala</option>
                        <option value="Maluku" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Maluku') ? 'selected' : ''; ?>>Maluku</option>
                        <option value="Masina" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Masina') ? 'selected' : ''; ?>>Masina</option>
                        <option value="Matete" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Matete') ? 'selected' : ''; ?>>Matete</option>
                        <option value="Mont-Ngafula" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Mont-Ngafula') ? 'selected' : ''; ?>>Mont-Ngafula</option>
                        <option value="Ndjili" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Ndjili') ? 'selected' : ''; ?>>Ndjili</option>
                        <option value="Ngaba" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Ngaba') ? 'selected' : ''; ?>>Ngaba</option>
                        <option value="Ngaliema" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Ngaliema') ? 'selected' : ''; ?>>Ngaliema</option>
                        <option value="Ngiri-Ngiri" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Ngiri-Ngiri') ? 'selected' : ''; ?>>Ngiri-Ngiri</option>
                        <option value="Nsele" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Nsele') ? 'selected' : ''; ?>>Nsele</option>
                        <option value="Selembao" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Selembao') ? 'selected' : ''; ?>>Selembao</option>
                        <option value="Kisenso" <?php echo (isset($_POST['commune']) && $_POST['commune'] == 'Kisenso') ? 'selected' : ''; ?>>Kisenso</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quartier">Quartier <span class="required">*</span></label>
                    <input type="text" id="quartier" name="quartier" required value="<?php echo isset($_POST['quartier']) ? htmlspecialchars($_POST['quartier']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="avenue">Avenue <span class="required">*</span></label>
                    <input type="text" id="avenue" name="avenue" required value="<?php echo isset($_POST['avenue']) ? htmlspecialchars($_POST['avenue']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="numero">Numéro <span class="required">*</span></label>
                    <input type="text" id="numero" name="numero" required value="<?php echo isset($_POST['numero']) ? htmlspecialchars($_POST['numero']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe <span class="required">*</span></label>
                <input type="password" id="password" name="password" required minlength="6">
                <small style="color: #666;">Au moins 6 caractères</small>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-user-plus"></i>
                Créer mon compte
            </button>
        </form>
        
        <div class="back-link">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Retour à l'accueil</a> |
            <a href="login.php">Déjà un compte ? Se connecter</a>
        </div>
    </div>
</body>
</html> 