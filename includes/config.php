<?php
// Database configuration (MySQL - données applicatives)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ent_flowershop');

// LDAP configuration (authentification)
define('LDAP_HOST', 'localhost');
define('LDAP_PORT', 389);
define('LDAP_BASE_DN', 'dc=shop,dc=local');
define('LDAP_BIND_DN', 'cn=admin,dc=shop,dc=local');
define('LDAP_BIND_PASSWORD', 'admin123');
define('LDAP_USER_BASE', 'ou=users,dc=shop,dc=local');

// Site configuration
define('SITE_URL', 'http://localhost/ent-flowershop');
define('SITE_NAME', 'Flower Shop - Boutique des Jardins');
define('SHOP_NAME', 'Boutique des Jardins');



// User roles for flower shop
define('ROLE_ADMIN', 'admin');
define('ROLE_MANAGER', 'manager');
define('ROLE_SELLER', 'seller');
define('ROLE_CUSTOMER', 'customer');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security mode handling
if (!isset($_SESSION['security_mode'])) {
    $_SESSION['security_mode'] = 'vulnerable';
}

function is_secure_mode() {
    return isset($_SESSION['security_mode']) && $_SESSION['security_mode'] === 'secure';
}

function get_login_url() {
    return is_secure_mode() ? 'secure/login.php' : 'vulnerable/login.php';
}

function get_dashboard_url() {
    return is_secure_mode() ? 'secure/dashboard.php' : 'vulnerable/dashboard.php';
}

// LDAP Helper Functions
function ldap_authenticate($username, $password) {
    // Connexion LDAP
    $ldap_conn = ldap_connect(LDAP_HOST, LDAP_PORT);
    
    if (!$ldap_conn) {
        error_log("LDAP: Impossible de se connecter au serveur");
        return false;
    }
    
    // Configuration LDAP
    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
    
    try {
        // Bind avec les credentials admin pour rechercher l'utilisateur
        if (!ldap_bind($ldap_conn, LDAP_BIND_DN, LDAP_BIND_PASSWORD)) {
            error_log("LDAP: Échec du bind admin");
            return false;
        }
        
        // Recherche de l'utilisateur
        $search_filter = "(uid=$username)";
        $search_result = ldap_search($ldap_conn, LDAP_USER_BASE, $search_filter);
        
        if (!$search_result) {
            error_log("LDAP: Échec de la recherche pour $username");
            return false;
        }
        
        $entries = ldap_get_entries($ldap_conn, $search_result);
        
        if ($entries['count'] == 0) {
            error_log("LDAP: Utilisateur $username non trouvé");
            return false;
        }
        
        // Récupération du DN de l'utilisateur
        $user_dn = $entries[0]['dn'];
        
        // Tentative d'authentification avec les credentials utilisateur
        if (ldap_bind($ldap_conn, $user_dn, $password)) {
            // Récupération des informations utilisateur
            $user_info = array(
                'username' => $username,
                'dn' => $user_dn,
                'cn' => isset($entries[0]['cn'][0]) ? $entries[0]['cn'][0] : $username,
                'mail' => isset($entries[0]['mail'][0]) ? $entries[0]['mail'][0] : '',
                'role' => isset($entries[0]['employeetype'][0]) ? $entries[0]['employeetype'][0] : 'customer',
                'department' => isset($entries[0]['ou'][0]) ? $entries[0]['ou'][0] : ''
            );
            
            ldap_close($ldap_conn);
            return $user_info;
        } else {
            error_log("LDAP: Mot de passe incorrect pour $username");
            ldap_close($ldap_conn);
            return false;
        }
        
    } catch (Exception $e) {
        error_log("LDAP: Erreur - " . $e->getMessage());
        ldap_close($ldap_conn);
        return false;
    }
}

function get_or_create_user_profile($ldap_user) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        error_log("DB: Erreur de connexion - " . $conn->connect_error);
        return false;
    }
    
    // Vérifier si l'utilisateur existe dans la BDD locale
    $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE username = ?");
    $stmt->bind_param("s", $ldap_user['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Utilisateur existe, mettre à jour les infos
        $user_profile = $result->fetch_assoc();
        
        $update_stmt = $conn->prepare("UPDATE user_profiles SET 
                                      full_name = ?, 
                                      email = ?, 
                                      role = ?, 
                                      department = ?, 
                                      last_login = NOW() 
                                      WHERE username = ?");
        $update_stmt->bind_param("sssss", 
                                $ldap_user['cn'], 
                                $ldap_user['mail'], 
                                $ldap_user['role'], 
                                $ldap_user['department'], 
                                $ldap_user['username']);
        $update_stmt->execute();
        $update_stmt->close();
        
    } else {
        // Nouvel utilisateur, créer le profil
        $insert_stmt = $conn->prepare("INSERT INTO user_profiles 
                                      (username, full_name, email, role, department, created_at, last_login) 
                                      VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $insert_stmt->bind_param("sssss", 
                                $ldap_user['username'], 
                                $ldap_user['cn'], 
                                $ldap_user['mail'], 
                                $ldap_user['role'], 
                                $ldap_user['department']);
        $insert_stmt->execute();
        $user_profile = array(
            'id' => $conn->insert_id,
            'username' => $ldap_user['username'],
            'full_name' => $ldap_user['cn'],
            'email' => $ldap_user['mail'],
            'role' => $ldap_user['role'],
            'department' => $ldap_user['department']
        );
        $insert_stmt->close();
    }
    
    $stmt->close();
    $conn->close();
    
    return $user_profile;
}
?>