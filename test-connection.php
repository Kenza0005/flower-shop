<?php
/**
 * Test de connexion MySQL et LDAP
 * Fichier de test pour vérifier l'infrastructure
 */

echo "<h1>Test de Connexion - Infrastructure Flower Shop</h1>";

// Test MySQL
echo "<h2>Test MySQL</h2>";
try {
    require_once 'includes/config.php';
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        echo "<p style='color: red;'>❌ Erreur MySQL: " . $conn->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Connexion MySQL réussie</p>";
        
        // Test d'une requête simple
        $result = $conn->query("SELECT COUNT(*) as count FROM products");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p>📊 Nombre de produits: " . $row['count'] . "</p>";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception MySQL: " . $e->getMessage() . "</p>";
}

// Test LDAP
echo "<h2>Test LDAP</h2>";
try {
    $ldap_conn = ldap_connect(LDAP_HOST, LDAP_PORT);
    
    if (!$ldap_conn) {
        echo "<p style='color: red;'>❌ Impossible de se connecter au serveur LDAP</p>";
    } else {
        echo "<p style='color: green;'>✅ Connexion LDAP établie</p>";
        
        ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
        
        // Test bind admin
        if (ldap_bind($ldap_conn, LDAP_BIND_DN, LDAP_BIND_PASSWORD)) {
            echo "<p style='color: green;'>✅ Authentification admin LDAP réussie</p>";
            
            // Test recherche utilisateurs
            $search_result = ldap_search($ldap_conn, LDAP_USER_BASE, "(objectClass=inetOrgPerson)");
            if ($search_result) {
                $entries = ldap_get_entries($ldap_conn, $search_result);
                echo "<p>👥 Nombre d'utilisateurs LDAP: " . ($entries['count']) . "</p>";
                
                // Lister les utilisateurs
                echo "<h3>Utilisateurs LDAP:</h3><ul>";
                for ($i = 0; $i < $entries['count']; $i++) {
                    $uid = isset($entries[$i]['uid'][0]) ? $entries[$i]['uid'][0] : 'N/A';
                    $cn = isset($entries[$i]['cn'][0]) ? $entries[$i]['cn'][0] : 'N/A';
                    $role = isset($entries[$i]['employeetype'][0]) ? $entries[$i]['employeetype'][0] : 'N/A';
                    echo "<li><strong>$uid</strong> - $cn ($role)</li>";
                }
                echo "</ul>";
            }
        } else {
            echo "<p style='color: red;'>❌ Échec authentification admin LDAP</p>";
        }
        
        ldap_close($ldap_conn);
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception LDAP: " . $e->getMessage() . "</p>";
}

// Test des services
echo "<h2>Test des Services</h2>";

// Test Apache
if (function_exists('apache_get_version')) {
    echo "<p style='color: green;'>✅ Apache: " . apache_get_version() . "</p>";
} else {
    echo "<p style='color: green;'>✅ Serveur Web actif</p>";
}

// Test PHP
echo "<p style='color: green;'>✅ PHP: " . phpversion() . "</p>";

// Test extensions PHP
$extensions = ['mysqli', 'ldap', 'gd', 'json'];
echo "<h3>Extensions PHP:</h3><ul>";
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<li style='color: green;'>✅ $ext</li>";
    } else {
        echo "<li style='color: red;'>❌ $ext</li>";
    }
}
echo "</ul>";

// Informations système
echo "<h2>Informations Système</h2>";
echo "<p><strong>OS:</strong> " . php_uname() . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// Test des répertoires
echo "<h2>Test des Répertoires</h2>";
$directories = ['uploads', 'assets/images', 'vulnerable', 'secure'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $writable = is_writable($dir) ? "✏️ Écriture" : "👁️ Lecture seule";
        echo "<p style='color: green;'>✅ $dir ($writable)</p>";
    } else {
        echo "<p style='color: red;'>❌ $dir (manquant)</p>";
    }
}

echo "<hr>";
echo "<p><em>Test effectué le " . date('d/m/Y H:i:s') . "</em></p>";
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

ul {
    margin: 10px 0;
}
</style>