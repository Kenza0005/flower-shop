<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// VULNERABLE: No CSRF protection, LDAP injection possible
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username']; // NO SANITIZATION
    $password = $_POST['password']; // NO SANITIZATION
    
    // VULNERABLE: Direct LDAP query construction
    try {
        // Connexion LDAP vulnérable
        $ldap_conn = @ldap_connect(LDAP_HOST, LDAP_PORT);
        
        if (!$ldap_conn) {
            $error = "Impossible de se connecter au serveur d'authentification";
        } else {
            @ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
            @ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
            
            // Test bind admin first
            if (!@ldap_bind($ldap_conn, LDAP_BIND_DN, LDAP_BIND_PASSWORD)) {
                $error = "Erreur de configuration du serveur d'authentification";
            } else {
                // VULNERABLE: Direct string concatenation in LDAP filter
                $search_filter = "(uid=$username)"; // LDAP INJECTION POSSIBLE!
                
                // VULNERABLE: Pas de validation du filtre LDAP
                $search_result = @ldap_search($ldap_conn, LDAP_USER_BASE, $search_filter);
                
                if ($search_result) {
                    $entries = ldap_get_entries($ldap_conn, $search_result);
                    
                    if ($entries['count'] > 0) {
                        $user_dn = $entries[0]['dn'];
                        
                        // Tentative d'authentification
                        if (@ldap_bind($ldap_conn, $user_dn, $password)) {
                            // VULNERABLE: No session regeneration
                            $_SESSION['user_id'] = $entries[0]['uidnumber'][0] ?? 1;
                            $_SESSION['username'] = $username;
                            $_SESSION['full_name'] = $entries[0]['cn'][0] ?? $username;
                            $_SESSION['email'] = $entries[0]['mail'][0] ?? ($username . '@boutique-jardins.fr');
                            $_SESSION['role'] = $entries[0]['employeetype'][0] ?? 'customer';
                            $_SESSION['department'] = $entries[0]['ou'][0] ?? 'Boutique';
                            
                            // VULNERABLE: Sync to database without prepared statements
                            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                            if (!$conn->connect_error) {
                                // VULNERABLE: Direct SQL injection possible
                                $sql = "INSERT INTO user_profiles (username, full_name, email, role, department, last_login) 
                                       VALUES ('$username', '{$_SESSION['full_name']}', '{$_SESSION['email']}', '{$_SESSION['role']}', '{$_SESSION['department']}', NOW()) 
                                       ON DUPLICATE KEY UPDATE 
                                       full_name = '{$_SESSION['full_name']}', 
                                       email = '{$_SESSION['email']}', 
                                       role = '{$_SESSION['role']}', 
                                       department = '{$_SESSION['department']}', 
                                       last_login = NOW()";
                                
                                $conn->query($sql);
                                
                                // Fetch the actual local ID (Crucial to avoid foreign key errors in activity_log)
                                $result = $conn->query("SELECT id FROM user_profiles WHERE username = '$username'");
                                if ($result && $row = $result->fetch_assoc()) {
                                    $_SESSION['user_id'] = $row['id'];
                                }
                                
                                $conn->close();
                            }
                            
                            $success = "Connexion réussie !";
                            $_SESSION['security_mode'] = 'vulnerable';
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            $error = "Mot de passe incorrect pour l'utilisateur: " . $username; // VULNERABLE: Username enumeration
                        }
                    } else {
                        $error = "Utilisateur '$username' non trouvé dans l'annuaire LDAP"; // VULNERABLE: User enumeration
                    }
                } else {
                    $error = "Erreur de recherche dans l'annuaire: " . ldap_error($ldap_conn); // VULNERABLE: LDAP error disclosure
                }
            }
            
            @ldap_close($ldap_conn);
        }
    } catch (Exception $e) {
        $error = "Erreur système: " . $e->getMessage(); // VULNERABLE: System error disclosure
    }
    $error = "Identifiants incorrects";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Boutique des Jardins</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-12 px-4 flex-col">
    
    <!-- Mode Switcher -->
    <div class="mb-8">
        <a href="../toggle_mode.php" class="flex items-center space-x-3 px-6 py-3 rounded-2xl font-bold transition <?php echo is_secure_mode() ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20' : 'bg-rose-500 text-white shadow-lg shadow-rose-500/20'; ?>">
            <i class="fas <?php echo is_secure_mode() ? 'fa-shield-alt' : 'fa-unlock-alt'; ?> text-xl"></i>
            <span><?php echo is_secure_mode() ? 'Mode Sécurisé Activé' : 'Mode Vulnérable Activé'; ?></span>
            <div class="bg-white/20 px-3 py-1 rounded-lg text-xs">Changer</div>
        </a>
    </div>

    <div class="max-w-3xl w-full">
        <!-- Login Card -->
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
            <!-- Header -->
            <div class="bg-emerald-600 p-8 text-white text-center">
                <div class="w-20 h-20 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-flower text-5xl"></i>
                </div>
                <h1 class="text-3xl font-bold mb-2"><?php echo SHOP_NAME; ?></h1>
                <p class="text-emerald-100 font-medium">Connexion Boutique - Version Vulnérable</p>
            </div>
            
            <div class="p-8">
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-5 py-4 rounded-xl mb-6">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-circle text-xl mr-3 flex-shrink-0 mt-0.5"></i>
                            <span class="font-medium"><?php echo $error; ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-5 py-4 rounded-xl mb-6">
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-xl mr-3 flex-shrink-0 mt-0.5"></i>
                            <span class="font-medium"><?php echo $success; ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm" class="space-y-6">
                    <!-- Username Input -->
                    <div>
                        <label class="block text-gray-900 text-sm font-semibold mb-3" for="username">
                            <i class="fas fa-user mr-2 text-emerald-600"></i>Identifiant Boutique
                        </label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="w-full px-5 py-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition bg-gray-50"
                               placeholder="Votre identifiant (ex: client.martin)"
                               required>
                    </div>
                    
                    <!-- Password Input -->
                    <div>
                        <label class="block text-gray-900 text-sm font-semibold mb-3" for="password">
                            <i class="fas fa-lock mr-2 text-emerald-600"></i>Mot de passe
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="w-full px-5 py-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition bg-gray-50"
                               placeholder="Votre mot de passe boutique"
                               required>
                    </div>
                    
                    <!-- Login Button -->
                    <button type="submit" 
                            class="w-full bg-emerald-600 text-white font-bold py-4 px-6 rounded-xl hover:bg-emerald-700 transition shadow-lg shadow-emerald-600/30">
                        <i class="fas fa-sign-in-alt mr-2"></i>Se Connecter
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <a href="../index.php" class="text-emerald-600 hover:text-emerald-700 text-sm font-semibold inline-flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i>Retour à l'accueil
                    </a>
                </div>
                
                <!-- Test Accounts Info -->
                <div class="mt-8 bg-blue-50 border border-blue-200 rounded-2xl p-6">
                    <h4 class="font-bold text-blue-900 mb-4 flex items-center">
                        <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-info-circle text-white text-sm"></i>
                        </div>
                        Comptes de Test
                    </h4>
                    <div class="text-sm text-blue-800 space-y-2">
                        <div class="flex justify-between items-center py-2 border-b border-blue-200">
                            <span class="font-semibold">Admin:</span>
                            <code class="bg-blue-100 px-3 py-1 rounded-lg">admin.martin / admin123</code>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-blue-200">
                            <span class="font-semibold">Gérant:</span>
                            <code class="bg-blue-100 px-3 py-1 rounded-lg">manager.sophie / sophie123</code>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-blue-200">
                            <span class="font-semibold">Vendeur:</span>
                            <code class="bg-blue-100 px-3 py-1 rounded-lg">seller.marie / marie123</code>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="font-semibold">Client:</span>
                            <code class="bg-blue-100 px-3 py-1 rounded-lg">client.alice / alice123</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Security Analysis Button -->
        <div class="mt-6">
            <button onclick="toggleCode('vulnerabilityFeatures')" 
                    class="w-full bg-rose-500 text-white py-4 px-6 rounded-xl hover:bg-rose-600 transition font-semibold shadow-lg shadow-rose-500/30">
                <i class="fas fa-exclamation-triangle mr-2"></i>Analyse de Sécurité
            </button>
        </div>
        
        <!-- Vulnerability Features Display -->
        <div id="vulnerabilityFeatures" class="mt-6 bg-white rounded-2xl shadow-xl p-8 border border-gray-100" style="display: none;">
            <h3 class="text-2xl font-bold mb-6 text-rose-600 flex items-center">
                <div class="w-10 h-10 bg-rose-100 rounded-xl flex items-center justify-center mr-3">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                Vulnérabilités Présentes
            </h3>
            
            <div class="space-y-4">
                <div class="bg-rose-50 border-l-4 border-rose-500 p-5 rounded-xl">
                    <h4 class="font-bold text-rose-900 mb-2 text-lg flex items-center">
                        <i class="fas fa-code mr-2"></i>Injection LDAP
                    </h4>
                    <p class="text-rose-700 leading-relaxed">Aucune validation des entrées utilisateur. Le filtre LDAP est construit directement avec les données non filtrées.</p>
                </div>
                
                <div class="bg-rose-50 border-l-4 border-rose-500 p-5 rounded-xl">
                    <h4 class="font-bold text-rose-900 mb-2 text-lg flex items-center">
                        <i class="fas fa-database mr-2"></i>Injection SQL
                    </h4>
                    <p class="text-rose-700 leading-relaxed">Synchronisation BDD sans requêtes préparées. Concaténation directe des variables dans les requêtes SQL.</p>
                </div>
                
                <div class="bg-rose-50 border-l-4 border-rose-500 p-5 rounded-xl">
                    <h4 class="font-bold text-rose-900 mb-2 text-lg flex items-center">
                        <i class="fas fa-shield-alt mr-2"></i>Pas de Protection CSRF
                    </h4>
                    <p class="text-rose-700 leading-relaxed">Aucun token CSRF implémenté. Vulnérable aux attaques cross-site request forgery.</p>
                </div>
                
                <div class="bg-rose-50 border-l-4 border-rose-500 p-5 rounded-xl">
                    <h4 class="font-bold text-rose-900 mb-2 text-lg flex items-center">
                        <i class="fas fa-infinity mr-2"></i>Pas de Limitation de Taux
                    </h4>
                    <p class="text-rose-700 leading-relaxed">Aucune limite sur les tentatives de connexion. Vulnérable aux attaques par force brute.</p>
                </div>
                
                <div class="bg-rose-50 border-l-4 border-rose-500 p-5 rounded-xl">
                    <h4 class="font-bold text-rose-900 mb-2 text-lg flex items-center">
                        <i class="fas fa-user-times mr-2"></i>Fixation de Session
                    </h4>
                    <p class="text-rose-700 leading-relaxed">Pas de régénération d'ID de session après connexion. Vulnérable à la fixation de session.</p>
                </div>
                
                <div class="bg-rose-50 border-l-4 border-rose-500 p-5 rounded-xl">
                    <h4 class="font-bold text-rose-900 mb-2 text-lg flex items-center">
                        <i class="fas fa-eye mr-2"></i>Divulgation d'Informations
                    </h4>
                    <p class="text-rose-700 leading-relaxed">Messages d'erreur détaillés révèlent des informations système et permettent l'énumération d'utilisateurs.</p>
                </div>
            </div>
            
            <div class="mt-6 bg-orange-50 border-l-4 border-orange-500 p-6 rounded-xl">
                <h4 class="font-bold text-orange-900 mb-2 text-lg">
                    <i class="fas fa-lightbulb mr-2"></i>Impact Potentiel
                </h4>
                <p class="text-orange-800 leading-relaxed">
                    Ces vulnérabilités permettent un accès non autorisé, l'extraction de données sensibles, 
                    et peuvent servir de point d'entrée pour des attaques plus complexes sur l'infrastructure.
                </p>
            </div>
        </div>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>