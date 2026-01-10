@echo off
echo ========================================
echo Import des utilisateurs LDAP
echo ========================================
echo.

REM Vérifier si le conteneur LDAP fonctionne
docker ps | findstr test-ldap >nul
if %errorlevel% neq 0 (
    echo ERREUR: Le conteneur test-ldap n'est pas en cours d'exécution
    echo Démarrez-le avec: docker start test-ldap
    pause
    exit /b 1
)

echo Conteneur LDAP détecté ✓
echo.

REM Copier le fichier LDIF dans le conteneur
echo Copie du fichier des utilisateurs...
docker cp test-users.ldif test-ldap:/tmp/test-users.ldif

if %errorlevel% neq 0 (
    echo ERREUR: Impossible de copier le fichier LDIF
    pause
    exit /b 1
)

echo Fichier copié ✓
echo.

REM Importer les utilisateurs
echo Importation des utilisateurs...
docker exec test-ldap ldapadd -x -D "cn=admin,dc=shop,dc=local" -w admin123 -f /tmp/test-users.ldif

if %errorlevel% neq 0 (
    echo ATTENTION: Erreur lors de l'importation (les utilisateurs existent peut-être déjà)
) else (
    echo Utilisateurs importés avec succès ✓
)

echo.

REM Vérifier l'importation
echo Vérification de l'importation...
docker exec test-ldap ldapsearch -x -D "cn=admin,dc=shop,dc=local" -w admin123 -b "ou=users,dc=shop,dc=local" "(objectClass=inetOrgPerson)" uid cn employeeType

echo.
echo ========================================
echo Import terminé !
echo ========================================
echo.
echo Vous pouvez maintenant tester la connexion avec:
echo - admin.martin / admin123
echo - manager.sophie / sophie123  
echo - seller.marie / marie123
echo - client.alice / alice123
echo.
pause