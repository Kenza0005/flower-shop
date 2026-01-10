<?php
require_once '../includes/config.php';

$results = [];
$search_query = '';
$error = '';

if (isset($_GET['query'])) {
    // SECURE: Input validation and sanitization
    $search_query = filter_input(INPUT_GET, 'query', FILTER_SANITIZE_STRING);
    $search_query = trim($search_query);
    
    // SECURE: Minimum length validation
    if (strlen($search_query) < 2) {
        $error = "Veuillez saisir au moins 2 caractères pour rechercher.";
    } 
    // SECURE: Maximum length validation
    elseif (strlen($search_query) > 100) {
        $error = "Requête de recherche trop longue.";
    }
    // SECURE: Character validation
    elseif (!preg_match('/^[a-zA-Z0-9\s\-àáâäèéêëìíîïòóôöùúûüçñ]+$/u', $search_query)) {
        $error = "Caractères non autorisés dans la recherche.";
    }
    else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            $error = "Service temporairement indisponible. Veuillez réessayer plus tard.";
            error_log("Database connection failed: " . $conn->connect_error);
        } else {
            // SECURE: Prepared statement with parameter binding
            $stmt = $conn->prepare("SELECT id, name, description, price, stock, category, image_url 
                                   FROM products 
                                   WHERE (name LIKE ? OR description LIKE ?) 
                                   AND stock > 0
                                   ORDER BY name ASC
                                   LIMIT 50");
            
            $search_param = "%$search_query%";
            $stmt->bind_param("ss", $search_param, $search_param);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
            
            $stmt->close();
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche de Produits - Sécurisé</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body class="bg-gray-50">    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-4xl font-bold text-center mb-8 text-gray-800">
                <i class="fas fa-search mr-3"></i>Recherche de Produits
            </h1>
            
            <!-- Search Form -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <form method="GET" action="" class="flex gap-4" onsubmit="return validateSearch()">
                    <input type="text" 
                           id="searchInput"
                           name="query" 
                           value="<?php echo htmlspecialchars($search_query); ?>" 
                           class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                           placeholder="Rechercher des fleurs..."
                           minlength="2"
                           maxlength="100"
                           pattern="[a-zA-Z0-9\s\-àáâäèéêëìíîïòóôöùúûüçñ]+"
                           title="Seuls les lettres, chiffres, espaces et tirets sont autorisés">
                    <button type="submit" 
                            class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 transition font-semibold">
                        <i class="fas fa-search mr-2"></i>Recherche Sécurisée
                    </button>
                </form>
                <p class="text-sm text-gray-500 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    2-100 caractères. Lettres, chiffres, espaces et tirets uniquement.
                </p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Results -->
            <?php if (!empty($results)): ?>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-2xl font-bold mb-4"><?php echo count($results); ?> résultat(s) trouvé(s)</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($results as $product): ?>
                            <div class="border rounded-lg p-4 hover:shadow-lg transition">
                                <img src="../assets/images/<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="w-full h-48 object-cover rounded mb-4"
                                     onerror="this.src='../assets/images/placeholder.jpg'">
                                <h3 class="font-bold text-lg"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($product['description']); ?></p>
                                <p class="text-green-500 font-bold text-xl mt-2"><?php echo number_format($product['price'], 2); ?>€</p>
                                <p class="text-sm text-gray-500 mt-1">Stock: <?php echo intval($product['stock']); ?> disponible(s)</p>
                                <p class="text-sm text-blue-500 mt-1">Catégorie: <?php echo htmlspecialchars($product['category']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif (isset($_GET['query']) && !$error): ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                    <i class="fas fa-search mr-2"></i>
                    Aucun produit trouvé correspondant à "<?php echo htmlspecialchars($search_query); ?>"
                </div>
            <?php endif; ?>
            
            <!-- Navigation -->
            <div class="mt-8 text-center">
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
$search_query = filter_input(INPUT_GET, 'query', FILTER_SANITIZE_STRING);
$search_query = trim($search_query);

// Validation de longueur
if (strlen($search_query) < 2 || strlen($search_query) > 100) {
    $error = "Longueur de requête invalide";
}

// Validation de caractères
if (!preg_match('/^[a-zA-Z0-9\s\-àáâäèéêëìíîïòóôöùúûüçñ]+$/u', $search_query)) {
    $error = "Caractères non autorisés";
}

// Requête préparée
$stmt = $conn->prepare("SELECT id, name, description, price, stock, category, image_url 
                       FROM products 
                       WHERE (name LIKE ? OR description LIKE ?) 
                       AND stock > 0
                       ORDER BY name ASC
                       LIMIT 50");

$search_param = "%$search_query%";
$stmt->bind_param("ss", $search_param, $search_param);
$stmt->execute();

// Encodage de sortie
echo htmlspecialchars($product['name']);</code></pre>
                </div>
                
                <div class="space-y-4">
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">1. Sanitisation d'Entrée</h4>
                        <p class="text-green-700 text-sm">Utilise filter_input() pour supprimer les caractères dangereux avant traitement.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">2. Validation d'Entrée</h4>
                        <p class="text-green-700 text-sm">Vérifie la longueur (2-100 chars) et valide les types de caractères avec regex.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">3. Requêtes Préparées</h4>
                        <p class="text-green-700 text-sm">Utilise des requêtes paramétrées pour prévenir l'injection SQL - les données utilisateur ne sont jamais mélangées au code SQL.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">4. Encodage de Sortie</h4>
                        <p class="text-green-700 text-sm">Utilise htmlspecialchars() sur toutes les sorties pour prévenir les attaques XSS.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">5. Gestion d'Erreurs</h4>
                        <p class="text-green-700 text-sm">Messages d'erreur génériques qui ne révèlent pas de détails système. Erreurs réelles loggées côté serveur.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">6. Limitation de Résultats</h4>
                        <p class="text-green-700 text-sm">Limite les résultats à 50 pour prévenir les attaques DoS par épuisement de ressources.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">7. Validation Côté Client</h4>
                        <p class="text-green-700 text-sm">Attributs de validation HTML5 fournissent un retour immédiat aux utilisateurs.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/main.js"></script>
    <script>
        function validateSearch() {
            const searchInput = document.getElementById('searchInput');
            const value = searchInput.value.trim();
            
            if (value.length < 2) {
                alert('Veuillez saisir au moins 2 caractères');
                return false;
            }
            
            if (value.length > 100) {
                alert('Requête de recherche trop longue (max 100 caractères)');
                return false;
            }
            
            // Vérifier les caractères invalides
            if (!/^[a-zA-Z0-9\s\-àáâäèéêëìíîïòóôöùúûüçñ]+$/u.test(value)) {
                alert('Seuls les lettres, chiffres, espaces et tirets sont autorisés');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>