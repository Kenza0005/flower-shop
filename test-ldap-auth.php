<?php
/**
 * Test LDAP Authentication
 * Simple script to test LDAP connection and user authentication
 */

require_once 'includes/config.php';

echo "<h1>Test LDAP Authentication</h1>";

// Test users
$test_users = [
    ['username' => 'admin', 'password' => 'admin123'],
    ['username' => 'manager.sophie', 'password' => 'sophie123'],
    ['username' => 'seller.marie', 'password' => 'marie123'],
    ['username' => 'client.alice', 'password' => 'alice123'],
    ['username' => 'client.bob', 'password' => 'bob123']
];

echo "<h2>Configuration LDAP:</h2>";
echo "<ul>";
echo "<li><strong>Host:</strong> " . LDAP_HOST . "</li>";
echo "<li><strong>Port:</strong> " . LDAP_PORT . "</li>";
echo "<li><strong>Base DN:</strong> " . LDAP_BASE_DN . "</li>";
echo "<li><strong>Bind DN:</strong> " . LDAP_BIND_DN . "</li>";
echo "<li><strong>User Base:</strong> " . LDAP_USER_BASE . "</li>";
echo "</ul>";

echo "<h2>Test de Connexion LDAP:</h2>";

try {
    $ldap_conn = ldap_connect(LDAP_HOST, LDAP_PORT);
    
    if (!$ldap_conn) {
        echo "<p style='color: red;'>❌ Impossible de se connecter au serveur LDAP</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Connexion LDAP établie</p>";
    
    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
    
    // Test bind admin
    if (!ldap_bind($ldap_conn, LDAP_BIND_DN, LDAP_BIND_PASSWORD)) {
        echo "<p style='color: red;'>❌ Échec du bind admin: " . ldap_error($ldap_conn) . "</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Bind admin réussi</p>";
    
    echo "<h2>Test des Utilisateurs:</h2>";
    
    foreach ($test_users as $user) {
        echo "<h3>Test: {$user['username']}</h3>";
        
        // Search user
        $search_filter = "(uid={$user['username']})";
        $search_result = ldap_search($ldap_conn, LDAP_USER_BASE, $search_filter);
        
        if (!$search_result) {
            echo "<p style='color: red;'>❌ Erreur de recherche: " . ldap_error($ldap_conn) . "</p>";
            continue;
        }
        
        $entries = ldap_get_entries($ldap_conn, $search_result);
        
        if ($entries['count'] == 0) {
            echo "<p style='color: red;'>❌ Utilisateur non trouvé</p>";
            continue;
        }
        
        echo "<p style='color: green;'>✅ Utilisateur trouvé</p>";
        
        $user_dn = $entries[0]['dn'];
        echo "<p><strong>DN:</strong> $user_dn</p>";
        echo "<p><strong>CN:</strong> " . ($entries[0]['cn'][0] ?? 'N/A') . "</p>";
        echo "<p><strong>Email:</strong> " . ($entries[0]['mail'][0] ?? 'N/A') . "</p>";
        echo "<p><strong>Role:</strong> " . ($entries[0]['employeetype'][0] ?? 'N/A') . "</p>";
        
        // Test authentication
        if (ldap_bind($ldap_conn, $user_dn, $user['password'])) {
            echo "<p style='color: green;'>✅ Authentification réussie</p>";
        } else {
            echo "<p style='color: red;'>❌ Échec authentification: " . ldap_error($ldap_conn) . "</p>";
        }
        
        echo "<hr>";
    }
    
    ldap_close($ldap_conn);
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<h2>Test de Vulnérabilité LDAP Injection:</h2>";
echo "<p>Essayez ces payloads dans le formulaire de connexion:</p>";
echo "<ul>";
echo "<li><code>admin)(|(uid=*</code> - Bypass d'authentification</li>";
echo "<li><code>*)(uid=admin</code> - Test d'existence utilisateur</li>";
echo "<li><code>*)(|(cn=*)(mail=*</code> - Extraction d'informations</li>";
echo "</ul>";

?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f5f5f5;
}

h1, h2, h3 {
    color: #333;
}

p, li {
    margin: 5px 0;
}

code {
    background-color: #f0f0f0;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: monospace;
}

hr {
    margin: 20px 0;
    border: none;
    border-top: 1px solid #ccc;
}
</style>