<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// SECURE: CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// SECURE: Rate limiting (simple implementation)
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = time();
}

// Reset attempts after 15 minutes
if (time() - $_SESSION['last_attempt'] > 900) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // SECURE: Check rate limiting
    if ($_SESSION['login_attempts'] >= 5) {
        $error = "Trop de tentatives de connexion. Veuillez réessayer dans 15 minutes.";
    } 
    // SECURE: Verify CSRF token
    elseif (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Requête invalide. Veuillez réessayer.";
    } 
    else {
        // SECURE: Input sanitization
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = $_POST['password'];
        
        // Increment login attempts
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt'] = time();
        
        // SECURE: LDAP authentication with proper validation
        if (empty($username) || empty($password)) {
            $error = "Identifiant et mot de passe requis.";
        } elseif (strlen($username) > 50 || strlen($password) > 100) {
            $error = "Identifiant ou mot de passe trop long.";
        } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
            $error = "Format d'identifiant invalide.";
        } else {
            // SECURE: Use the secure LDAP authentication function
            $ldap_user = ldap_authenticate($username, $password);
            
            if ($ldap_user) {
                // SECURE: Regenerate session ID to prevent fixation
                session_regenerate_id(true);
                
                // Get or create user profile in database
                $user_profile = get_or_create_user_profile($ldap_user);
                
                if ($user_profile) {
                    $_SESSION['user_id'] = $user_profile['id'];
                    $_SESSION['username'] = $ldap_user['username'];
                    $_SESSION['full_name'] = $ldap_user['cn'];
                    $_SESSION['email'] = $ldap_user['mail'];
                    $_SESSION['role'] = $ldap_user['role'];
                    $_SESSION['department'] = $ldap_user['department'];
                    
                    // Reset login attempts on success
                    $_SESSION['login_attempts'] = 0;
                    
                    // SECURE: Log successful login
                    error_log("Connexion réussie: " . $username . " depuis " . $_SERVER['REMOTE_ADDR']);
                    
                    $_SESSION['security_mode'] = 'secure';
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Erreur lors de la création du profil utilisateur.";
                    error_log("Erreur création profil pour: " . $username);
                }
            } else {
                // SECURE: Generic error message (doesn't reveal if username exists)
                $error = "Identifiants incorrects. Veuillez réessayer.";
                
                // SECURE: Log failed attempt
                error_log("Tentative de connexion échouée pour: " . $username . " depuis " . $_SERVER['REMOTE_ADDR']);
            }
        }
    }
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
            <div class="bg-emerald-600 p-8 text-white text-center">
                <div class="w-20 h-20 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-flower text-5xl"></i>
                </div>
                <h1 class="text-3xl font-bold mb-2"><?php echo SHOP_NAME; ?></h1>
            </div>
            
            <div class="p-8">
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-5 py-4 rounded-xl mb-6">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-circle text-xl mr-3 flex-shrink-0 mt-0.5"></i>
                            <span class="font-medium"><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-5 py-4 rounded-xl mb-6">
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-xl mr-3 flex-shrink-0 mt-0.5"></i>
                            <span class="font-medium"><?php echo htmlspecialchars($success); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($_SESSION['login_attempts'] > 0): ?>
                    <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-700 px-5 py-4 rounded-xl mb-6">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-xl mr-3 flex-shrink-0 mt-0.5"></i>
                            <span class="font-medium">Tentatives de connexion: <?php echo $_SESSION['login_attempts']; ?>/5</span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm" class="space-y-6">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    
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
                               required
                               maxlength="50"
                               pattern="[a-zA-Z0-9._-]{3,50}">
                        <p class="text-xs text-gray-500 mt-2">3-50 caractères, lettres, chiffres, points, tirets et underscores uniquement</p>
                    </div>
                    
                    <!-- Password Input -->
                    <div>
                        <label class="block text-gray-900 text-sm font-semibold mb-3" for="password">
                            <i class="fas fa-lock mr-2 text-emerald-600"></i>Mot de passe
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="w-full px-5 py-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition bg-gray-50 pr-12"
                                   placeholder="Votre mot de passe boutique"
                                   required
                                   maxlength="100">
                            <button type="button" 
                                    onclick="togglePasswordVisibility()" 
                                    class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 transition">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Login Button -->
                    <button type="submit" 
                            class="w-full bg-emerald-600 text-white font-bold py-4 px-6 rounded-xl hover:bg-emerald-700 transition shadow-lg shadow-emerald-600/30">
                        <i class="fas fa-sign-in-alt mr-2"></i>Connexion Sécurisée
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <a href="../index.php" class="text-emerald-600 hover:text-emerald-700 text-sm font-semibold inline-flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i>Retour à l'accueil
                    </a>
                </div>
                
                <!-- Test Accounts Info -->
                <div class="mt-8 bg-emerald-50 border border-emerald-200 rounded-2xl p-6">
                    <h4 class="font-bold text-emerald-900 mb-4 flex items-center">
                        <div class="w-8 h-8 bg-emerald-500 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-info-circle text-white text-sm"></i>
                        </div>
                        Comptes de Test
                    </h4>
                    <div class="text-sm text-emerald-800 space-y-2">
                        <div class="flex justify-between items-center py-2 border-b border-emerald-200">
                            <span class="font-semibold">Admin:</span>
                            <code class="bg-emerald-100 px-3 py-1 rounded-lg">admin.martin / admin123</code>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-emerald-200">
                            <span class="font-semibold">Gérant:</span>
                            <code class="bg-emerald-100 px-3 py-1 rounded-lg text-xs">manager.sophie / sophie123</code>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-emerald-200">
                            <span class="font-semibold">Vendeur:</span>
                            <code class="bg-emerald-100 px-3 py-1 rounded-lg text-xs">seller.marie / marie123</code>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="font-semibold">Client:</span>
                            <code class="bg-emerald-100 px-3 py-1 rounded-lg text-xs">client.alice / alice123</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Security Features Button -->
        <div class="mt-6">
            <button onclick="toggleCode('securityFeatures')" 
                    class="w-full bg-emerald-500 text-white py-4 px-6 rounded-xl hover:bg-emerald-600 transition font-semibold shadow-lg shadow-emerald-500/30">
                <i class="fas fa-shield-alt mr-2"></i>Fonctionnalités de Sécurité
            </button>
        </div>
        
        <!-- Security Features Display -->
        <div id="securityFeatures" class="mt-6 bg-white rounded-2xl shadow-xl p-8 border border-gray-100" style="display: none;">
            <h3 class="text-2xl font-bold mb-6 text-emerald-600 flex items-center">
                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center mr-3">
                    <i class="fas fa-shield-alt"></i>
                </div>
                Sécurité Implémentée
            </h3>
            
            <div class="space-y-4">
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-5 rounded-xl">
                    <h4 class="font-bold text-emerald-900 mb-2 text-lg flex items-center">
                        <i class="fas fa-filter mr-2"></i>Validation d'Entrée LDAP
                    </h4>
                    <p class="text-emerald-700 leading-relaxed">Validation stricte des identifiants avec regex et échappement des caractères spéciaux pour prévenir l'injection LDAP.</p>
                </div>
                
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-5 rounded-xl">
                    <h4 class="font-bold text-emerald-900 mb-2 text-lg flex items-center">
                        <i class="fas fa-database mr-2"></i>Requêtes Préparées
                    </h4>
                    <p class="text-emerald-700 leading-relaxed">Utilise des requêtes préparées pour la synchronisation BDD, séparant le code SQL des données utilisateur.</p>
                </div>
                
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-5 rounded-xl">
                    <h4 class="font-bold text-emerald-900 mb-2 text-lg flex items-center">
                        <i class="fas fa-shield-virus mr-2"></i>Protection CSRF
                    </h4>
                    <p class="text-emerald-700 leading-relaxed">Utilise des tokens CSRF cryptographiquement sécurisés pour prévenir les attaques cross-site.</p>
                </div>
                
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-5 rounded-xl">
                    <h4 class="font-bold text-emerald-900 mb-2 text-lg flex items-center">
                        <i class="fas fa-stopwatch mr-2"></i>Limitation de Taux
                    </h4>
                    <p class="text-emerald-700 leading-relaxed">Limite les tentatives de connexion à 5 par 15 minutes pour prévenir les attaques par force brute.</p>
                </div>
                
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-5 rounded-xl">
                    <h4 class="font-bold text-emerald-900 mb-2 text-lg flex items-center">
                        <i class="fas fa-user-lock mr-2"></i>Sécurité de Session
                    </h4>
                    <p class="text-emerald-700 leading-relaxed">Régénère l'ID de session après connexion pour prévenir la fixation de session.</p>
                </div>
                
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-5 rounded-xl">
                    <h4 class="font-bold text-emerald-900 mb-2 text-lg flex items-center">
                        <i class="fas fa-eye-slash mr-2"></i>Messages d'Erreur Génériques
                    </h4>
                    <p class="text-emerald-700 leading-relaxed">Messages d'erreur génériques qui ne révèlent pas d'informations sur l'existence des utilisateurs.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/main.js"></script>
    <script>
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>