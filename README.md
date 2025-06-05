# 🗂️ PETO - Système de Gestion des Déchets

**PETO** est un système complet de gestion des déchets développé pour la ville de Kinshasa, République Démocratique du Congo. Il permet aux ménages de signaler leurs poubelles pleines et aux collecteurs de gérer efficacement les tâches de collecte.

## 🎯 Fonctionnalités Principales

### 👥 **3 Rôles Utilisateurs**
- **🏠 Ménages** : Créent des poubelles, alertent quand elles sont pleines
- **🚛 Collecteurs** : Gèrent les tâches de collecte avec suivi temps réel
- **👨‍💼 Admin** : Supervise le système, gère les utilisateurs et les statistiques

### 🔧 **Fonctionnalités par Rôle**

#### 🏠 **Ménages**
- ✅ Création de poubelles (organique, plastique, mixte)
- ✅ Système d'alerte on/off pour poubelles pleines
- ✅ Suivi en temps réel des collectes
- ✅ Historique des collectes avec timeline interactive
- ✅ Statistiques personnelles

#### 🚛 **Collecteurs**
- ✅ Dashboard avec statistiques de performance
- ✅ Liste des tâches assignées automatiquement
- ✅ Suivi 5 étapes : Assignée → Acceptée → En route → Arrivé → Collectée
- ✅ Système de classement compétitif
- ✅ Assignation intelligente selon la charge de travail

#### 👨‍💼 **Admin**
- ✅ Gestion complète des collecteurs (créer, suspendre, supprimer)
- ✅ Gestion des ménages et supervision
- ✅ Statistiques globales et alertes
- ✅ Système de suspension avec déconnexion automatique
- ✅ Vue détaillée des performances

## 🏗️ Architecture Technique

### **Structure du Projet**
```
PETO/
├── classes/                 # Classes PHP principales
│   ├── Database.php        # Connexion base de données
│   ├── User.php           # Gestion utilisateurs et authentification
│   ├── Poubelle.php       # Gestion des poubelles
│   ├── Collecteur.php     # Fonctionnalités collecteurs
│   └── Admin.php          # Fonctionnalités administrateur
├── components/             # Composants réutilisables
│   ├── layout.php         # Template principal avec sidebar
│   └── check_suspension.php # Vérification suspension automatique
├── pages principales/      # Pages d'accès
│   ├── index.php          # Page d'accueil
│   ├── login.php          # Connexion
│   ├── register.php       # Inscription ménages
│   ├── dashboard.php      # Tableau de bord partagé
│   └── profil.php         # Gestion profil utilisateur
├── pages ménages/         # Interface ménages
│   ├── menage-poubelles.php    # Gestion poubelles
│   └── menage-collectes.php    # Suivi collectes
├── pages collecteurs/     # Interface collecteurs
│   └── collecteur-dashboard.php # Dashboard collecteur
├── pages admin/           # Interface administration
│   ├── admin-collecteurs.php   # Gestion collecteurs
│   ├── admin-menages.php       # Gestion ménages
│   └── classement-collecteurs.php # Classement global
├── install.php           # Installation base de données
├── migrate.php          # Migration/mise à jour BD
└── logout.php           # Déconnexion
```

### **Base de Données**
- **users** : Utilisateurs (admin, ménage, collecteur)
- **poubelles** : Poubelles créées par les ménages
- **taches_collecte** : Tâches assignées aux collecteurs
- **etapes_collecte** : Suivi détaillé des étapes
- **statistiques_collecteurs** : Performance des collecteurs

## 🚀 Installation

### **Prérequis**
- PHP 7.4+ avec PDO MySQL
- MySQL/MariaDB
- Serveur web (Apache/Nginx)

### **Étapes d'Installation**

1. **Cloner le projet**
```bash
git clone [url-du-repo]
cd PETO
```

2. **Configuration base de données**
   - Créer une base de données MySQL nommée `peto_simple`
   - Modifier les paramètres dans `classes/Database.php` si nécessaire

3. **Installation automatique**
   - Accéder à `http://votre-domaine/PETO/install.php`
   - Suivre les instructions d'installation
   - Exécuter `migrate.php` pour les mises à jour

4. **Comptes de test créés automatiquement**
   - **Admin** : `admin@peto.cd` / `admin123`
   - **Collecteur 1** : `collecteur@peto.cd` / `collecteur123`
   - **Collecteur 2** : `collecteur2@peto.cd` / `collecteur456`

## 💡 Comment ça Marche

### **Flux de Fonctionnement**

1. **🏠 Ménage crée une poubelle**
   - Sélectionne le type (organique, plastique, mixte)
   - La poubelle est créée avec statut "normale"

2. **🔔 Alerte poubelle pleine**
   - Ménage active l'alerte via bouton on/off
   - Système assigne automatiquement au collecteur le moins chargé

3. **📋 Collecteur reçoit la tâche**
   - Tâche apparaît dans son dashboard
   - Peut suivre les étapes : Accepter → En route → Arrivé → Collecter

4. **📊 Suivi temps réel**
   - Ménage voit l'avancement en direct
   - Timeline interactive avec 5 étapes
   - Système intelligent complète les étapes manquantes

5. **🏆 Statistiques et classement**
   - Performance des collecteurs trackée
   - Classement compétitif pour motivation
   - Statistiques globales pour l'admin

### **Système de Templating**

Le projet utilise un système de templating maison :

```php
// Dans chaque page
ob_start(); // Capture le contenu
?>
<!-- HTML spécifique à la page -->
<?php
$content = ob_get_clean(); // Stocke dans $content
include 'components/layout.php'; // Injecte dans le template
```

**Avantages :**
- Interface cohérente avec sidebar/navigation
- Maintenance centralisée
- Séparation contenu/structure

## 🔒 Sécurité

### **Fonctionnalités de Sécurité**
- ✅ **Authentification sécurisée** avec hachage des mots de passe
- ✅ **Système de suspension** avec déconnexion automatique
- ✅ **Validation des données** côté serveur
- ✅ **Protection CSRF** sur les formulaires
- ✅ **Vérification des rôles** sur chaque page
- ✅ **Échappement HTML** pour éviter XSS

### **Système de Suspension**
- Admin peut suspendre collecteurs/ménages
- Vérification automatique à chaque page
- Déconnexion immédiate si suspendu
- Messages d'information clairs

## 🎨 Interface Utilisateur

### **Design Moderne**
- **Gradients colorés** selon les rôles
- **Interface responsive** (mobile-friendly)
- **Animations fluides** et transitions
- **Icônes Font Awesome** pour clarté
- **Timeline interactive** pour le suivi

### **Couleurs par Rôle**
- **Admin** : Bleu/Violet (autorité)
- **Collecteur** : Rose/Jaune (dynamisme)
- **Ménage** : Vert/Bleu (nature)

## 📊 Statistiques et Reporting

### **Métriques Trackées**
- Nombre de poubelles créées/collectées
- Temps d'attente moyen
- Performance des collecteurs
- Taux de réussite des collectes
- Alertes en cours

### **Tableaux de Bord**
- **Dashboard partagé** avec données par rôle
- **Statistiques temps réel** 
- **Classement compétitif** des collecteurs
- **Alertes et notifications**

## 🔧 Maintenance

### **Fichiers de Configuration**
- `classes/Database.php` : Paramètres base de données
- `install.php` : Installation initiale
- `migrate.php` : Mises à jour de structure

### **Logs et Débogage**
- Erreurs loggées automatiquement
- Système de gestion d'erreurs robuste
- Protection contre les boucles de redirection

## 🤝 Contribution

Le projet PETO est conçu pour être facilement extensible :

1. **Ajouter un nouveau rôle** : Étendre la classe User
2. **Nouvelles fonctionnalités** : Créer de nouvelles classes dans `/classes`
3. **Interface** : Utiliser le système de templating existant
4. **Base de données** : Ajouter les migrations dans `migrate.php`

## 📝 Notes Techniques

### **Choix Architecturaux**
- **PHP pur** sans framework pour simplicité
- **Architecture MVC simplifiée** pour débutants
- **Base de données relationnelle** avec MySQL
- **Pas d'AJAX** pour compatibilité maximale
- **Templating maison** pour contrôle total

### **Évolutions Possibles**
- API REST pour mobile
- Notifications push
- Géolocalisation des collectes
- Système de paiement
- Rapports PDF automatiques

---

**Développé avec ❤️ pour la ville de Kinshasa, RDC**

*Système PETO - Transformer la gestion des déchets urbains* 