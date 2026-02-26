# Configuration LDAP pour ENT Flower Shop

## Installation OpenLDAP sur Windows (avec XAMPP)

### Option 1: Serveur LDAP Local (Recommandé pour les tests)

#### Installation avec Apache Directory Studio
1. **Télécharger Apache Directory Studio**
   - URL: https://directory.apache.org/studio/
   - Inclut un serveur LDAP intégré pour les tests

2. **Configuration du serveur de test**
   - Créer un nouveau serveur LDAP
   - Port: 10389 (par défaut)
   - Base DN: `dc=school,dc=local`

#### Installation OpenLDAP (Alternative)
1. **Télécharger OpenLDAP pour Windows**
   - URL: https://www.userbooster.de/en/download/openldap-for-windows.aspx
   - Ou utiliser Docker (voir Option 2)

### Option 2: Docker LDAP (Plus Simple)

```bash
# Créer un serveur LDAP avec Docker
docker run -d \
  --name test-ldap \
  -p 389:389 \
  -p 636:636 \
  -e LDAP_ORGANISATION="École des Jardins" \
  -e LDAP_DOMAIN="school.local" \
  -e LDAP_ADMIN_PASSWORD="admin123" \
  osixia/openldap:latest

# Interface d'administration web
docker run -d \
  --name ldap-admin \
  -p 8080:80 \
  --link test-ldap:ldap-host \
  -e PHPLDAPADMIN_LDAP_HOSTS=ldap-host \
  osixia/phpldapadmin:latest
```

## Structure LDAP Requise

### Base DN: `dc=school,dc=local`

```ldif
# Unité organisationnelle pour les utilisateurs
dn: ou=users,dc=school,dc=local
objectClass: organizationalUnit
ou: users

# Unité organisationnelle pour les groupes
dn: ou=groups,dc=school,dc=local
objectClass: organizationalUnit
ou: groups

# Utilisateur Admin
dn: uid=admin,ou=users,dc=school,dc=local
objectClass: inetOrgPerson
objectClass: posixAccount
objectClass: shadowAccount
uid: admin
cn: Administrateur Système
sn: Système
givenName: Administrateur
mail: admin@ecole-jardins.fr
employeeType: admin
ou: Administration
uidNumber: 1000
gidNumber: 1000
homeDirectory: /home/admin
userPassword: {SSHA}admin123

# Professeur Martin
dn: uid=prof.martin,ou=users,dc=school,dc=local
objectClass: inetOrgPerson
objectClass: posixAccount
objectClass: shadowAccount
uid: prof.martin
cn: Jean Martin
sn: Martin
givenName: Jean
mail: j.martin@ecole-jardins.fr
employeeType: teacher
ou: Sciences Naturelles
uidNumber: 1001
gidNumber: 1001
homeDirectory: /home/prof.martin
userPassword: {SSHA}martin123

# Professeur Dubois
dn: uid=prof.dubois,ou=users,dc=school,dc=local
objectClass: inetOrgPerson
objectClass: posixAccount
objectClass: shadowAccount
uid: prof.dubois
cn: Marie Dubois
sn: Dubois
givenName: Marie
mail: m.dubois@ecole-jardins.fr
employeeType: teacher
ou: Arts Plastiques
uidNumber: 1002
gidNumber: 1002
homeDirectory: /home/prof.dubois
userPassword: {SSHA}marie123

# Étudiant Alice
dn: uid=etudiant.alice,ou=users,dc=school,dc=local
objectClass: inetOrgPerson
objectClass: posixAccount
objectClass: shadowAccount
uid: etudiant.alice
cn: Alice Moreau
sn: Moreau
givenName: Alice
mail: alice.moreau@eleve.ecole-jardins.fr
employeeType: student
ou: Terminale S
uidNumber: 2001
gidNumber: 2000
homeDirectory: /home/etudiant.alice
userPassword: {SSHA}alice123

# Étudiant Bob
dn: uid=etudiant.bob,ou=users,dc=school,dc=local
objectClass: inetOrgPerson
objectClass: posixAccount
objectClass: shadowAccount
uid: etudiant.bob
cn: Bob Leroy
sn: Leroy
givenName: Bob
mail: bob.leroy@eleve.ecole-jardins.fr
employeeType: student
ou: Première ES
uidNumber: 2002
gidNumber: 2000
homeDirectory: /home/etudiant.bob
userPassword: {SSHA}bob123

# Personnel Secrétaire
dn: uid=secretaire,ou=users,dc=school,dc=local
objectClass: inetOrgPerson
objectClass: posixAccount
objectClass: shadowAccount
uid: secretaire
cn: Sophie Secrétaire
sn: Secrétaire
givenName: Sophie
mail: secretaire@ecole-jardins.fr
employeeType: staff
ou: Secrétariat
uidNumber: 3001
gidNumber: 3000
homeDirectory: /home/secretaire
userPassword: {SSHA}secret123

# Groupes
dn: cn=admins,ou=groups,dc=school,dc=local
objectClass: groupOfNames
cn: admins
member: uid=admin,ou=users,dc=school,dc=local

dn: cn=teachers,ou=groups,dc=school,dc=local
objectClass: groupOfNames
cn: teachers
member: uid=prof.martin,ou=users,dc=school,dc=local
member: uid=prof.dubois,ou=users,dc=school,dc=local

dn: cn=students,ou=groups,dc=school,dc=local
objectClass: groupOfNames
cn: students
member: uid=etudiant.alice,ou=users,dc=school,dc=local
member: uid=etudiant.bob,ou=users,dc=school,dc=local

dn: cn=staff,ou=groups,dc=school,dc=local
objectClass: groupOfNames
cn: staff
member: uid=secretaire,ou=users,dc=school,dc=local
```

## Configuration PHP

### Vérifier l'extension LDAP
```php
<?php
// Vérifier si LDAP est activé
if (extension_loaded('ldap')) {
    echo "Extension LDAP chargée ✓";
} else {
    echo "Extension LDAP non disponible ✗";
    echo "Activez php_ldap.dll dans php.ini";
}
?>
```

### Activer LDAP dans XAMPP
1. Ouvrir `C:\xampp\php\php.ini`
2. Décommenter la ligne: `extension=ldap`
3. Redémarrer Apache

## Tests de Connexion

### Script de Test LDAP
```php
<?php
// Test de connexion LDAP
$ldap_host = 'localhost';
$ldap_port = 389;
$ldap_base_dn = 'dc=school,dc=local';

$conn = ldap_connect($ldap_host, $ldap_port);
if ($conn) {
    ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    
    // Test bind anonyme
    if (ldap_bind($conn)) {
        echo "Connexion LDAP réussie ✓\n";
        
        // Test recherche
        $search = ldap_search($conn, $ldap_base_dn, "(objectClass=*)");
        if ($search) {
            $entries = ldap_get_entries($conn, $search);
            echo "Nombre d'entrées trouvées: " . $entries['count'] . "\n";
        }
    } else {
        echo "Échec du bind LDAP ✗\n";
    }
    
    ldap_close($conn);
} else {
    echo "Impossible de se connecter au serveur LDAP ✗\n";
}
?>
```

## Comptes de Test

| Utilisateur | Mot de passe | Rôle | Département |
|-------------|--------------|------|-------------|
| admin | admin123 | admin | Administration |
| prof.martin | martin123 | teacher | Sciences Naturelles |
| prof.dubois | marie123 | teacher | Arts Plastiques |
| etudiant.alice | alice123 | student | Terminale S |
| etudiant.bob | bob123 | student | Première ES |
| secretaire | secret123 | staff | Secrétariat |

## Vulnérabilités LDAP à Tester

### 1. LDAP Injection
- Payload: `admin)(|(uid=*`
- Résultat: Bypass d'authentification

### 2. Énumération d'Utilisateurs
- Payload: `*)(uid=admin`
- Résultat: Test d'existence d'utilisateur

### 3. Extraction de Données
- Payload: `*)(|(cn=*)(mail=*`
- Résultat: Récupération d'informations

## Dépannage

### Erreurs Communes
1. **"Call to undefined function ldap_connect()"**
   - Solution: Activer l'extension LDAP dans php.ini

2. **"Can't contact LDAP server"**
   - Vérifier que le serveur LDAP fonctionne
   - Vérifier le port (389 ou 10389)

3. **"Invalid credentials"**
   - Vérifier les DN et mots de passe
   - Tester avec un client LDAP externe

### Outils Utiles
- **Apache Directory Studio**: Interface graphique LDAP
- **ldapsearch**: Outil en ligne de commande
- **phpLDAPadmin**: Interface web d'administration