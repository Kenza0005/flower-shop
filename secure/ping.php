<?php
require_once '../includes/config.php';

// SECURE: Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$output = '';
$host = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // SECURE: Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Requête invalide.";
    } else {
        $host = trim($_POST['host']);
        
        // SECURE: Whitelist validation - only allow valid IP addresses or specific domains
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            // Valid IP address
            $is_valid = true;
        } elseif (preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/', $host)) {
            // Valid domain name format
            $is_valid = true;
        } else {
            $is_valid = false;
            $error = "Format d'adresse IP ou nom d'hôte invalide.";
        }
        
        if ($is_valid) {
            // SECURE: Additional validation - block private IPs if needed
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                // Block localhost, private networks, and reserved ranges
                $ip = $host;
                $parts = explode('.', $ip);
                
                if ($parts[0] == 10 || 
                    ($parts[0] == 172 && $parts[1] >= 16 && $parts[1] <= 31) ||
                    ($parts[0] == 192 && $parts[1] == 168) ||
                    $parts[0] == 127 ||
                    $parts[0] >= 224) {
                    $error = "Les adresses IP privées et réservées ne sont pas autorisées.";
                    $is_valid = false;
                }
            }
            
            // SECURE: Additional domain validation - block suspicious domains
            if ($is_valid && !filter_var($host, FILTER_VALIDATE_IP)) {
                $blocked_domains = ['localhost', 'internal', 'local', 'intranet'];
                foreach ($blocked_domains as $blocked) {
                    if (strpos(strtolower($host), $blocked) !== false) {
                        $error = "Ce domaine n'est pas autorisé.";
                        $is_valid = false;
                        break;
                    }
                }
            }
            
            if ($is_valid) {
                // SECURE: Use escapeshellarg() to properly escape the argument
                $safe_host = escapeshellarg($host);
                
                // SECURE: Use full path to ping command (Windows compatible)
                if (PHP_OS_FAMILY === 'Windows') {
                    $command = "ping -n 4 " . $safe_host;
                } else {
                    $command = "/bin/ping -c 4 -W 2 " . $safe_host;
                }
                
                // SECURE: Limit output and add timeout
                exec($command . " 2>&1", $output_lines, $return_var);
                
                // SECURE: Limit output size to prevent DoS
                if (count($output_lines) > 100) {
                    $output_lines = array_slice($output_lines, 0, 100);
                    $output_lines[] = "... (sortie tronquée)";
                }
                
                $output = implode("\n", $output_lines);
                
                // Log the ping for security monitoring
                error_log("Ping exécuté par l'utilisateur " . ($_SESSION['username'] ?? 'anonyme') . " vers " . $host . " depuis " . $_SERVER['REMOTE_ADDR']);
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
    <title>Outil Ping Réseau - Sécurisé</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body class="bg-gray-50">
    
    <!-- Security Badge -->
    <div class="fixed top-4 right-4 z-50">
        <span class="bg-green-100 text-green-800 px-4 py-2 rounded-full text-sm font-semibold">
            <i class="fas fa-shield-alt mr-1"></i>
            Injection de Commande Protégée
        </span>
    </div>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-4xl font-bold text-center mb-8 text-gray-800">
                <i class="fas fa-network-wired mr-3"></i>Outil Ping Réseau
            </h1>
            
            <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
                <h2 class="text-2xl font-bold mb-4">Ping d'un Hôte</h2>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" onsubmit="return validatePing()">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">Saisir une Adresse IP ou Nom d'Hôte</label>
                        <div class="flex gap-4">
                            <input type="text" 
                                   id="hostInput"
                                   name="host" 
                                   value="<?php echo htmlspecialchars($host); ?>"
                                   class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                   placeholder="ex: 8.8.8.8 ou google.com"
                                   pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$|^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$"
                                   required>
                            <button type="submit" 
                                    class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 transition font-semibold">
                                <i class="fas fa-play mr-2"></i>Ping Sécurisé
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Seules les adresses IP valides et noms de domaine sont autorisés. Les caractères spéciaux sont bloqués.
                        </p>
                    </div>
                </form>
                
                <?php if ($output): ?>
                    <div class="bg-gray-900 text-green-400 p-6 rounded-lg font-mono text-sm overflow-x-auto mt-6">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-terminal mr-2"></i>
                            <span class="font-bold">Sortie de Commande :</span>
                        </div>
                        <pre class="whitespace-pre-wrap"><?php echo htmlspecialchars($output); ?></pre>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Quick Test Buttons -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h3 class="text-xl font-bold mb-4">Hôtes Publics Autorisés</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <form method="POST" class="inline">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="host" value="8.8.8.8">
                        <button type="submit" class="w-full bg-blue-500 text-white px-4 py-3 rounded-lg hover:bg-blue-600 transition text-center">
                            <i class="fas fa-globe mr-2"></i>Google DNS
                        </button>
                    </form>
                    
                    <form method="POST" class="inline">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="host" value="1.1.1.1">
                        <button type="submit" class="w-full bg-blue-500 text-white px-4 py-3 rounded-lg hover:bg-blue-600 transition text-center">
                            <i class="fas fa-cloud mr-2"></i>Cloudflare
                        </button>
                    </form>
                    
                    <form method="POST" class="inline">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="host" value="google.com">
                        <button type="submit" class="w-full bg-blue-500 text-white px-4 py-3 rounded-lg hover:bg-blue-600 transition text-center">
                            <i class="fas fa-search mr-2"></i>Google.com
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="text-center mb-8">
                <a href="../index.php" class="text-green-600 hover:text-green-700">
                    <i class="fas fa-arrow-left mr-1"></i>Retour à l'accueil
                </a>
            </div>
            
            <!-- Security Features Button -->
            <div class="mt-8">
                <button onclick="toggleCode('securityFeatures')" 
                        class="w-full bg-green-500 text-white py-3 px-4 rounded-lg hover:bg-green-600 transition font-semibold">
                    <i class="fas fa-shield-alt mr-2"></i>Voir l'Implémentation Sécurisée
                </button>
            </div>
            
            <!-- Security Features Display -->
            <div id="securityFeatures" class="mt-4 bg-white rounded-lg shadow-lg p-6" style="display: none;">
                <h3 class="text-xl font-bold mb-4 text-green-600">Implémentation Sécurisée</h3>
                
                <div class="code-block mb-4">
                    <pre><code>// CODE SÉCURISÉ
// 1. Protection CSRF
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Requête invalide");
}

// 2. Validation d'Entrée - IP ou Domaine uniquement
if (filter_var($host, FILTER_VALIDATE_IP)) {
    $is_valid = true;
} elseif (preg_match('/^[a-zA-Z0-9.-]+$/', $host)) {
    $is_valid = true;
} else {
    die("Format invalide");
}

// 3. Bloquer IPs Privées
if (strpos($host, '127.') === 0 || strpos($host, '192.168.') === 0) {
    die("IPs privées bloquées");
}

// 4. Échapper Arguments Shell
$safe_host = escapeshellarg($host);

// 5. Utiliser Chemin Complet
$command = "/bin/ping -c 4 " . $safe_host;

// 6. Limiter Sortie & Timeout
exec($command . " 2>&1", $output, $return);

// 7. Assainir Sortie
echo htmlspecialchars(implode("\n", $output));</code></pre>
                </div>
                
                <div class="space-y-4">
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">1. Protection CSRF</h4>
                        <p class="text-green-700 text-sm">Nécessite une méthode POST avec token CSRF valide pour prévenir l'exécution de commandes externes.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">2. Validation d'Entrée</h4>
                        <p class="text-green-700 text-sm">Autorise uniquement les adresses IP valides ou noms de domaine. Regex filtre les caractères spéciaux.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">3. Blocage IP Privées</h4>
                        <p class="text-green-700 text-sm">Prévient les attaques SSRF en bloquant localhost, 192.168.x.x, 10.x.x.x, et plages 172.16-31.x.x.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">4. escapeshellarg()</h4>
                        <p class="text-green-700 text-sm">Échappe correctement les arguments shell pour prévenir l'injection de commande via métacaractères.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">5. Chemin Complet vers Binaire</h4>
                        <p class="text-green-700 text-sm">Utilise le chemin complet vers ping pour prévenir les attaques de manipulation PATH.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">6. Exécution Limitée</h4>
                        <p class="text-green-700 text-sm">Limite ping à 4 paquets avec timeout de 2 secondes pour prévenir DoS.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">7. Assainissement de Sortie</h4>
                        <p class="text-green-700 text-sm">Utilise htmlspecialchars() sur la sortie pour prévenir tout XSS via les résultats de commande.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">8. Journalisation Sécuritaire</h4>
                        <p class="text-green-700 text-sm">Enregistre toutes les commandes ping pour surveillance sécuritaire et réponse aux incidents.</p>
                    </div>
                </div>
                
                <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4">
                    <h4 class="font-bold text-blue-800 mb-2">Bonne Pratique : Éviter les Commandes Shell</h4>
                    <p class="text-blue-700 text-sm">L'approche la plus sécurisée est d'éviter entièrement l'exécution de commandes shell. Considérez utiliser des bibliothèques PHP ou APIs au lieu de commandes système quand c'est possible.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/main.js"></script>
    <script>
        function validatePing() {
            const input = document.getElementById('hostInput').value;
            
            // Vérifier métacaractères shell
            const dangerous = [';', '|', '&', '$', '`', '\n', '(', ')', '{', '}', '[', ']', '<', '>', '\\', '"', "'"];
            for (let char of dangerous) {
                if (input.includes(char)) {
                    alert('Caractères invalides détectés ! Seules les adresses IP et noms de domaine sont autorisés.');
                    return false;
                }
            }
            
            // Valider format IP
            const ipPattern = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
            const domainPattern = /^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
            
            if (!ipPattern.test(input) && !domainPattern.test(input)) {
                alert('Format d\'adresse IP ou nom de domaine invalide !');
                return false;
            }
            
            // Bloquer domaines suspects
            const blocked = ['localhost', 'internal', 'local', 'intranet'];
            for (let domain of blocked) {
                if (input.toLowerCase().includes(domain)) {
                    alert('Ce domaine n\'est pas autorisé !');
                    return false;
                }
            }
            
            return true;
        }
    </script>
</body>
</html>