# Flower Shop - Installation et Structure

##  Installation et Démarrage

### Prérequis
- **XAMPP** pour Windows (Apache + MySQL + PHP)
- **Docker Desktop** (pour le serveur LDAP)

### 1. Installation XAMPP
1. Télécharger XAMPP : https://www.apachefriends.org/
2. Installer dans `C:\xampp\`
3. Copier le projet dans `C:\xampp\htdocs\flower-shop\`

### 2. Configuration PHP LDAP
1. Éditer `C:\xampp\php\php.ini`
2. Décommenter la ligne : `extension=ldap`
3. Redémarrer Apache

### 3. Installation LDAP (Docker)
```bash
cd setup
install-ldap-docker.bat
```

### 4. Configuration Base de Données
1. Démarrer Apache et MySQL dans XAMPP
2. Aller sur http://localhost/phpmyadmin/
3. Importer le fichier `sql/database.sql`

### 5. Test de l'Installation
- Accéder à http://localhost/flower-shop/test-connection.php
- Vérifier que tous les services sont 

##  Structure du Projet

```
flower-shop/
├── index.php                    # Page d'accueil
├── test-connection.php          # Test des services
├── 
├── includes/
│   └── config.php              # Configuration LDAP + MySQL
│
├── vulnerable/                 # 🔴 VERSIONS VULNÉRABLES
│   ├── login.php              # LDAP injection



│   └── dashboard.php          # Tableau de bord
│
├── secure/                    # 🟢 VERSIONS SÉCURISÉES
│   ├── login.php              # Authentification sécurisée


│   └── dashboard.php          # Tableau de bord sécurisé
│
├── setup/                     # Scripts d'installation
│   ├── install-ldap-docker.bat
│   ├── test-users.ldif        # Utilisateurs LDAP
│   └── test-ldap.php
│
├── sql/
│   └── database.sql           # Base de données MySQL
│
├── css/
│   └── custom.css
│
├── js/
│   └── main.js
│
├── assets/images/             # Images des produits
```

##  Comptes de Test

### Authentification LDAP
| Utilisateur    | Mot de passe | Rôle    | Accès                  |
|----------------|--------------|---------|------------------------|
| admin          | admin123     | admin   | Toutes fonctionnalités |
| manager.sophie | sophie123    | manager | Gestion boutique       |
| seller.marie   | marie123     | seller  | Vente                  |
| client.alice   | alice123     | customer| Client                 |
| test-deploy    | azerty123    | admin   | Compte de test tech.   |

### Interfaces d'Administration
- **Application** : http://localhost/flower-shop/
- **phpMyAdmin** : http://localhost/phpmyadmin/
- **phpLDAPadmin** : http://localhost:8080/
  - Login DN: `cn=admin,dc=shop,dc=local`
  - Password: `admin123`

##  Fonctionnalités par Version

### Version Vulnérable (`/vulnerable/`)
-  LDAP Injection dans login
-  SQL Injection dans recherche
-  XSS Stored dans commentaires
-  File Inclusion dans pages

### Version Sécurisée (`/secure/`)
-  Validation LDAP stricte
-  Requêtes préparées SQL
-  Protection XSS complète
-  Whitelist d'inclusion

## 🛠️ Outils de Test Recommandés

- **Burp Suite** - Proxy d'interception
- **OWASP ZAP** - Scanner automatisé
- **Nmap** - Scan de ports
- **Hydra** - Force brute
- **SQLmap** - SQL injection
- **Nikto** - Scan web

## 🔧 Dépannage

### Erreur LDAP
```bash
# Vérifier Docker
docker ps | grep ldap
docker restart test-ldap
```

### Erreur MySQL
- Vérifier que MySQL est démarré dans XAMPP
- Importer `sql/database.sql` dans phpMyAdmin

### Erreur PHP
- Vérifier que `extension=ldap` est activé dans php.ini
- Redémarrer Apache après modification
