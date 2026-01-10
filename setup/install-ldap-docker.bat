@echo off
echo ========================================
echo Installation LDAP pour Flower Shop
echo ========================================
echo.

REM Vérifier si Docker est installé
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERREUR: Docker n'est pas installé ou pas dans le PATH
    echo Veuillez installer Docker Desktop depuis: https://www.docker.com/products/docker-desktop
    pause
    exit /b 1
)

echo Docker détecté ✓
echo.

REM Arrêter et supprimer les conteneurs existants
echo Nettoyage des conteneurs existants...
docker stop test-ldap ldap-admin >nul 2>&1
docker rm test-ldap ldap-admin >nul 2>&1

echo Démarrage du serveur LDAP...
docker run -d ^
  --name test-ldap ^
  -p 389:389 ^
  -p 636:636 ^
  -e LDAP_ORGANISATION="Boutique des Jardins" ^
  -e LDAP_DOMAIN="shop.local" ^
  -e LDAP_ADMIN_PASSWORD="admin123" ^
  -e LDAP_CONFIG_PASSWORD="config123" ^
  -e LDAP_READONLY_USER="true" ^
  -e LDAP_READONLY_USER_USERNAME="readonly" ^
  -e LDAP_READONLY_USER_PASSWORD="readonly123" ^
  osixia/openldap:latest

if %errorlevel% neq 0 (
    echo ERREUR: Impossible de démarrer le serveur LDAP
    pause
    exit /b 1
)

echo Serveur LDAP démarré ✓
echo.

echo Démarrage de l'interface d'administration...
docker run -d ^
  --name ldap-admin ^
  -p 8080:80 ^
  --link test-ldap:ldap-host ^
  -e PHPLDAPADMIN_LDAP_HOSTS=ldap-host ^
  -e PHPLDAPADMIN_HTTPS=false ^
  osixia/phpldapadmin:latest

if %errorlevel% neq 0 (
    echo ERREUR: Impossible de démarrer l'interface d'administration
    pause
    exit /b 1
)

echo Interface d'administration démarrée ✓
echo.

REM Attendre que les services démarrent
echo Attente du démarrage des services (30 secondes)...
timeout /t 30 /nobreak >nul

REM Importer les données de test
echo Importation des utilisateurs de test...
docker exec test-ldap ldapadd -x -D "cn=admin,dc=shop,dc=local" -w admin123 -f /container/service/slapd/assets/test/new-user.ldif >nul 2>&1

echo.
echo ========================================
echo Installation terminée avec succès !
echo ========================================
echo.
echo Services disponibles:
echo - Serveur LDAP: localhost:389
echo - Interface admin: http://localhost:8080
echo.
echo Connexion admin LDAP:
echo - DN: cn=admin,dc=shop,dc=local
echo - Mot de passe: admin123
echo.
echo Connexion phpLDAPadmin:
echo - Login DN: cn=admin,dc=shop,dc=local
echo - Password: admin123
echo.
echo Pour arrêter les services:
echo docker stop test-ldap ldap-admin
echo.
echo Pour redémarrer les services:
echo docker start test-ldap ldap-admin
echo.
pause