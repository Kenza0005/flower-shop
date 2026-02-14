<?php
// =====================
// Helpers (ENV)
// =====================
function env($key, $default = null) {
    $val = getenv($key);
    if ($val === false || $val === null || $val === '') return $default;
    return $val;
}

// =====================
// Database configuration (MySQL - données applicatives)
// =====================
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int) env('DB_PORT', '3306'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'ent_flowershop'));

// =====================
// LDAP configuration (authentification)
// =====================
define('LDAP_HOST', env('LDAP_HOST', '127.0.0.1'));
define('LDAP_PORT', (int) env('LDAP_PORT', '389'));
define('LDAP_BASE_DN', env('LDAP_BASE_DN', 'dc=shop,dc=local'));
define('LDAP_BIND_DN', env('LDAP_BIND_DN', 'cn=admin,dc=shop,dc=local'));
define('LDAP_BIND_PASSWORD', env('LDAP_BIND_PASSWORD', 'admin123'));
define('LDAP_USER_BASE', env('LDAP_USER_BASE', 'ou=users,dc=shop,dc=local'));

// =====================
// Site configuration
// =====================
// IMPORTANT: chez toi l’URL est /flower-shop (pas /ent-flowershop)
define('SITE_URL', env('SITE_URL', 'http://localhost/flower-shop'));
define('SITE_NAME', 'Flower Shop - Boutique des Jardins');
define('SHOP_NAME', 'Boutique des Jardins');

// =====================
// Upload configuration
// =====================
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 2097152); // 2MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// =====================
// User roles for flower shop
// =====================
define('ROLE_ADMIN', 'admin');
define('ROLE_MANAGER', 'manager');
define('ROLE_SELLER', 'seller');
define('ROLE_CUSTOMER', 'customer');

// =====================
// Session configuration
// =====================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // 1 si HTTPS

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================
// Security mode handling
// =====================
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

// =====================
// LDAP Helper Functions
// =====================
function ldap_authenticate($username, $password) {

    // 1) Connexion
    $ldap_conn = @ldap_connect(LDAP_HOST, LDAP_PORT);
    if (!$ldap_conn) {
        error_log("LDAP: Impossible de se connecter à " . LDAP_HOST . ":" . LDAP_PORT);
        return false;
    }

    // 2) Options
    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

    // 3) Bind admin (pour rechercher l’utilisateur)
    if (!@ldap_bind($ldap_conn, LDAP_BIND_DN, LDAP_BIND_PASSWORD)) {
        error_log("LDAP: Échec du bind admin (DN=" . LDAP_BIND_DN . ")");
        ldap_close($ldap_conn);
        return false;
    }

    // 4) Filtre de recherche
    // Mode vulnérable: filtre non échappé (LDAP injection possible)
    // Mode secure: filtre échappé
    if (is_secure_mode()) {
        $safeUser = ldap_escape($username, "", LDAP_ESCAPE_FILTER);
        $search_filter = "(uid={$safeUser})";
    } else {
        $search_filter = "(uid=$username)";
    }

    $search_result = @ldap_search($ldap_conn, LDAP_USER_BASE, $search_filter);
    if (!$search_result) {
        error_log("LDAP: Échec recherche (base=" . LDAP_USER_BASE . ", filter=$search_filter) - " . ldap_error($ldap_conn));
        ldap_close($ldap_conn);
        return false;
    }

    $entries = ldap_get_entries($ldap_conn, $search_result);
    if (!$entries || $entries['count'] == 0) {
        error_log("LDAP: Utilisateur $username non trouvé (base=" . LDAP_USER_BASE . ")");
        ldap_close($ldap_conn);
        return false;
    }

    // 5) DN user + bind user
    $user_dn = $entries[0]['dn'];

    if (@ldap_bind($ldap_conn, $user_dn, $password)) {
        $user_info = array(
            'username'   => $username,
            'dn'         => $user_dn,
            'cn'         => isset($entries[0]['cn'][0]) ? $entries[0]['cn'][0] : $username,
            'mail'       => isset($entries[0]['mail'][0]) ? $entries[0]['mail'][0] : '',
            'role'       => isset($entries[0]['employeetype'][0]) ? $entries[0]['employeetype'][0] : 'customer',
            'department' => isset($entries[0]['ou'][0]) ? $entries[0]['ou'][0] : ''
        );

        ldap_close($ldap_conn);
        return $user_info;
    }

    error_log("LDAP: Mot de passe incorrect pour $username (dn=$user_dn)");
    ldap_close($ldap_conn);
    return false;
}

function get_or_create_user_profile($ldap_user) {

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        error_log("DB: Erreur connexion - " . $conn->connect_error);
        return false;
    }
    $conn->set_charset("utf8mb4");

    $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE username = ?");
    $stmt->bind_param("s", $ldap_user['username']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user_profile = $result->fetch_assoc();

        $update_stmt = $conn->prepare("UPDATE user_profiles SET
            full_name = ?,
            email = ?,
            role = ?,
            department = ?,
            last_login = NOW()
            WHERE username = ?");

        $update_stmt->bind_param(
            "sssss",
            $ldap_user['cn'],
            $ldap_user['mail'],
            $ldap_user['role'],
            $ldap_user['department'],
            $ldap_user['username']
        );
        $update_stmt->execute();
        $update_stmt->close();

    } else {
        $insert_stmt = $conn->prepare("INSERT INTO user_profiles
            (username, full_name, email, role, department, created_at, last_login)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())");

        $insert_stmt->bind_param(
            "sssss",
            $ldap_user['username'],
            $ldap_user['cn'],
            $ldap_user['mail'],
            $ldap_user['role'],
            $ldap_user['department']
        );
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
