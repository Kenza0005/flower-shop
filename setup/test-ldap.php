<?php
/**
 * Script de test pour vérifier la configuration LDAP
 * Usage: php test-ldap.php
 */

// Configuration LDAP
$ldap_host = 'localhost';
$ldap_port = 389;
$ldap_base_dn = 'dc=school,dc=local';
$ldap_bind_dn = 'cn=admin,dc=school,dc=local';
$ldap_bind_password = 'admin123';
$ldap_user_base = 'ou=users,dc=school,dc=local';

echo "========================================\n";
echo "Test de Configuration LDAP\n";
echo "========================================\n\n";

// 1. Vérifier l'extension LDAP
echo "1. Vérification de l'extension LDAP...\n";
if (extension_loaded('ldap')) {
    echo "   ✓ Extension LDAP chargée\n\n";
} else {
    echo "   ✗ Extension LDAP non disponible\n";
    echo "   Activez php_ldap.dll dans php.ini et redémarrez Apache\n\n";
    exit(1);
}

// 2. Test de connexion
echo "2. Test de connexion au serveur LDAP...\n";
$ldap_conn = ldap_connect($ldap_host, $ldap_port);

if (!$ldap_conn) {
    echo "   ✗ Impossible de se connecter à $ldap_host:$ldap_port\n";
    echo "   Vérifiez que le serveur LDAP fonctionne\n\n";
    exit(1);
}

echo "   ✓ Connexion établie avec $ldap_host:$ldap_port\n\n";

// 3. Configuration LDAP
echo "3. Configuration des options LDAP...\n";
ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
echo "   ✓ Options LDAP configurées\n\n";

// 4. Test de bind admin
echo "4. Test d'authentification admin...\n";
if (ldap_bind($ldap_conn, $ldap_bind_dn, $ldap_bind_password)) {
    echo "   ✓ Authentification admin réussie\n\n";
} else {
    echo "   ✗ Échec de l'authentification admin\n";
    echo "   Erreur: " . ldap_error($ldap_conn) . "\n\n";
    ldap_close($ldap_conn);
    exit(1);
}

// 5. Test de recherche globale
echo "5. Test de recherche dans l'annuaire...\n";
$search_result = ldap_search($ldap_conn, $ldap_base_dn, "(objectClass=*)");

if ($search_result) {
    $entries = ldap_get_entries($ldap_conn, $search_result);
    echo "   ✓ Recherche réussie\n";
    echo "   Nombre total d'entrées: " . $entries['count'] . "\n\n";
} else {
    echo "   ✗ Échec de la recherche\n";
    echo "   Erreur: " . ldap_error($ldap_conn) . "\n\n";
}

// 6. Test de recherche des utilisateurs
echo "6. Test de recherche des utilisateurs...\n";
$user_search = ldap_search($ldap_conn, $ldap_user_base, "(objectClass=inetOrgPerson)");

if ($user_search) {
    $user_entries = ldap_get_entries($ldap_conn, $user_search);
    echo "   ✓ Recherche d'utilisateurs réussie\n";
    echo "   Nombre d'utilisateurs trouvés: " . $user_entries['count'] . "\n";
    
    if ($user_entries['count'] > 0) {
        echo "   Utilisateurs disponibles:\n";
        for ($i = 0; $i < $user_entries['count']; $i++) {
            $uid = $user_entries[$i]['uid'][0] ?? 'N/A';
            $cn = $user_entries[$i]['cn'][0] ?? 'N/A';
            $role = $user_entries[$i]['employeetype'][0] ?? 'N/A';
            echo "   - $uid ($cn) - Rôle: $role\n";
        }
    }
    echo "\n";
} else {
    echo "   ✗ Échec de la recherche d'utilisateurs\n";
    echo "   Erreur: " . ldap_error($ldap_conn) . "\n\n";
}

// 7. Test d'authentification utilisateur
echo "7. Test d'authentification d'un utilisateur...\n";
$test_username = 'admin';
$test_password = 'admin123';

$user_filter = "(uid=$test_username)";
$user_search = ldap_search($ldap_conn, $ldap_user_base, $user_filter);

if ($user_search) {
    $user_entries = ldap_get_entries($ldap_conn, $user_search);
    
    if ($user_entries['count'] > 0) {
        $user_dn = $user_entries[0]['dn'];
        echo "   Utilisateur trouvé: $user_dn\n";
        
        // Test d'authentification
        if (ldap_bind($ldap_conn, $user_dn, $test_password)) {
            echo "   ✓ Authentification utilisateur réussie\n";
            
            // Afficher les informations utilisateur
            echo "   Informations utilisateur:\n";
            echo "   - CN: " . ($user_entries[0]['cn'][0] ?? 'N/A') . "\n";
            echo "   - Email: " . ($user_entries[0]['mail'][0] ?? 'N/A') . "\n";
            echo "   - Rôle: " . ($user_entries[0]['employeetype'][0] ?? 'N/A') . "\n";
            echo "   - Département: " . ($user_entries[0]['ou'][0] ?? 'N/A') . "\n";
        } else {
            echo "   ✗ Échec de l'authentification utilisateur\n";
            echo "   Erreur: " . ldap_error($ldap_conn) . "\n";
        }
    } else {
        echo "   ✗ Utilisateur '$test_username' non trouvé\n";
    }
} else {
    echo "   ✗ Échec de la recherche utilisateur\n";
    echo "   Erreur: " . ldap_error($ldap_conn) . "\n";
}

echo "\n";

// 8. Test de vulnérabilité LDAP Injection
echo "8. Test de vulnérabilité LDAP Injection...\n";
$malicious_input = 'admin)(|(uid=*';
$vulnerable_filter = "(uid=$malicious_input)";

echo "   Filtre vulnérable testé: $vulnerable_filter\n";

$vuln_search = ldap_search($ldap_conn, $ldap_user_base, $vulnerable_filter);
if ($vuln_search) {
    $vuln_entries = ldap_get_entries($ldap_conn, $vuln_search);
    if ($vuln_entries['count'] > 1) {
        echo "   ⚠️  VULNÉRABILITÉ DÉTECTÉE: LDAP Injection possible\n";
        echo "   Le filtre malveillant a retourné " . $vuln_entries['count'] . " utilisateurs\n";
    } else {
        echo "   ✓ Pas de vulnérabilité LDAP Injection détectée\n";
    }
} else {
    echo "   ✓ Filtre malveillant rejeté\n";
}

echo "\n";

// Fermeture de la connexion
ldap_close($ldap_conn);

echo "========================================\n";
echo "Test terminé\n";
echo "========================================\n\n";

echo "Configuration recommandée pour config.php:\n";
echo "define('LDAP_HOST', '$ldap_host');\n";
echo "define('LDAP_PORT', $ldap_port);\n";
echo "define('LDAP_BASE_DN', '$ldap_base_dn');\n";
echo "define('LDAP_BIND_DN', '$ldap_bind_dn');\n";
echo "define('LDAP_BIND_PASSWORD', '$ldap_bind_password');\n";
echo "define('LDAP_USER_BASE', '$ldap_user_base');\n\n";

echo "Comptes de test disponibles:\n";
echo "- admin / admin123 (Administrateur)\n";
echo "- prof.martin / martin123 (Professeur)\n";
echo "- etudiant.alice / alice123 (Étudiant)\n";
echo "- secretaire / secret123 (Personnel)\n\n";

echo "Interface d'administration: http://localhost:8080\n";
echo "Login: cn=admin,dc=school,dc=local\n";
echo "Password: admin123\n";
?>