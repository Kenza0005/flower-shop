<?php 
require_once 'includes/config.php'; 

$results = [];
$search_query = '';
$error = '';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    $error = "Échec de connexion à la base de données";
} else {
    if (isset($_GET['query']) && $_GET['query'] !== '') {
        $search_query = $_GET['query'];
        
        if (is_secure_mode()) {
            // SECURE MODE: Input validation and prepared statements
            $sanitized_query = filter_input(INPUT_GET, 'query', FILTER_SANITIZE_STRING);
            $sanitized_query = trim($sanitized_query);
            
            if (strlen($sanitized_query) < 2) {
                $error = "Veuillez saisir au moins 2 caractères.";
            } elseif (strlen($sanitized_query) > 100) {
                $error = "Requête de recherche trop longue.";
            } elseif (!preg_match('/^[a-zA-Z0-9\s\-àáâäèéêëìíîïòóôöùúûüçñ]+$/u', $sanitized_query)) {
                $error = "Caractères non autorisés dans la recherche.";
            } else {
                $stmt = $conn->prepare("SELECT id, name, description, price, stock, category, image_url FROM products WHERE (name LIKE ? OR description LIKE ?) AND stock > 0 ORDER BY name ASC LIMIT 50");
                $search_param = "%$sanitized_query%";
                $stmt->bind_param("ss", $search_param, $search_param);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $results[] = $row;
                }
                $stmt->close();
            }
        } else {
            // VULNERABLE MODE: No validation, direct SQL interpolation
            $sql = "SELECT id, name, description, price, stock, category, image_url FROM products WHERE (name LIKE '%$search_query%' OR description LIKE '%$search_query%') AND stock > 0";
            $res = $conn->query($sql);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $results[] = $row;
                }
            } else {
                $error = "Erreur SQL : " . $conn->error;
            }
        }
    } else {
        // No search query, fetch all
        $sql = "SELECT id, name, description, price, stock, category, image_url FROM products WHERE stock > 0 ORDER BY name ASC LIMIT 50";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $results[] = $row;
            }
        }
    }
    $conn->close();
}

$display_query = is_secure_mode() ? htmlspecialchars($search_query) : $search_query;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/custom.css">
</head>
<body class="bg-gray-50">
    
    <!-- Navigation Bar - Modern Design -->
    <nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center group">
                        <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center group-hover:bg-emerald-100 transition">
                            <i class="fas fa-seedling text-emerald-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <div class="text-xl font-bold text-gray-900"><?php echo SHOP_NAME; ?></div>
                            <div class="text-sm text-emerald-600 font-medium">Flower Shop</div>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center space-x-2">
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <!-- Mode Toggle Button -->
                        <a href="toggle_mode.php" class="flex items-center space-x-2 px-4 py-2 rounded-xl text-sm font-semibold transition <?php echo is_secure_mode() ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm' : 'bg-rose-500 text-white hover:bg-rose-600 shadow-sm'; ?>">
                            <i class="fas <?php echo is_secure_mode() ? 'fa-shield-alt' : 'fa-unlock-alt'; ?>"></i>
                            <span><?php echo is_secure_mode() ? 'Mode Sécurisé' : 'Mode Vulnérable'; ?></span>
                        </a>
                    <?php endif; ?>

                    <a href="index.php" class="text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 px-4 py-2 rounded-xl text-sm font-semibold transition">Accueil</a>
                    <a href="products.php" class="text-emerald-600 px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-50">Catalogue</a>
                    <a href="about.php" class="text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 px-4 py-2 rounded-xl text-sm font-semibold transition">À propos</a>
                    <a href="contact.php" class="text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 px-4 py-2 rounded-xl text-sm font-semibold transition">Contact</a>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="flex items-center space-x-2 ml-2">
                            <a href="<?php echo get_dashboard_url(); ?>" class="bg-blue-500 text-white px-4 py-2.5 rounded-xl hover:bg-blue-600 transition text-sm font-semibold shadow-sm">
                                <i class="fas fa-tachometer-alt mr-1"></i>Tableau de bord
                            </a>
                            <a href="logout.php" class="bg-gray-100 text-gray-700 px-4 py-2.5 rounded-xl hover:bg-gray-200 transition text-sm font-semibold">
                                <i class="fas fa-sign-out-alt mr-1"></i>Déconnexion
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo get_login_url(); ?>" class="bg-emerald-600 text-white px-6 py-2.5 rounded-xl hover:bg-emerald-700 transition font-semibold shadow-sm ml-2">
                            <i class="fas fa-sign-in-alt mr-2"></i>Connexion
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header & Search -->
    <section class="bg-white py-16 border-b border-gray-100">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl font-bold mb-4 text-gray-900">Nos Magnifiques Fleurs</h1>
            <p class="text-lg text-gray-600 mb-10">Trouvez le bouquet parfait pour chaque occasion</p>
            
            <form method="GET" action="products.php" class="relative max-w-2xl mx-auto">
                <input type="text" 
                       name="query" 
                       value="<?php echo $display_query; ?>" 
                       class="w-full pl-6 pr-16 py-4 rounded-2xl border-2 border-emerald-100 bg-emerald-50/50 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-50 transition text-lg shadow-sm"
                       placeholder="Rechercher une fleur, une couleur..."
                       <?php if(is_secure_mode()) echo 'pattern="[a-zA-Z0-9\s\-àáâäèéêëìíîïòóôöùúûüçñ]+" title="Lettres et chiffres uniquement"'; ?>>
                <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-12 h-12 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 hover:shadow-md transition flex items-center justify-center">
                    <i class="fas fa-search text-lg"></i>
                </button>
            </form>
            
            <?php if (!is_secure_mode() && isset($_GET['query'])): ?>
                <div class="mt-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-lg text-sm inline-block shadow-sm">
                    <i class="fas fa-bug mr-2"></i>
                    <strong>Zone Vulnérable:</strong> Vos entrées sont interprétées sans filtre.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Products Section -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-5 py-4 rounded-xl mb-8 shadow-sm max-w-2xl mx-auto">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-xl mr-3"></i>
                        <span class="font-medium"><?php echo is_secure_mode() ? htmlspecialchars($error) : $error; // VULNERABLE rendering of error in vulnerable mode ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['query']) && $_GET['query'] !== '' && !$error): ?>
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800">Résultats pour "<?php echo $display_query; ?>" <span class="text-gray-500 text-lg font-normal ml-2">(<?php echo count($results); ?> trouves)</span></h2>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if (empty($results) && !$error): ?>
                    <div class="col-span-full text-center py-12">
                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                            <i class="fas fa-search-minus text-3xl text-gray-400"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-700 mb-2">Aucun produit trouvé</h3>
                        <p class="text-gray-500">Essayez d'autres mots-clés ou parcourez tout notre catalogue.</p>
                        <a href="products.php" class="inline-block mt-6 px-6 py-2.5 bg-emerald-50 text-emerald-700 font-semibold rounded-xl hover:bg-emerald-100 transition">Voir tout le catalogue</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($results as $product): ?>
                        <a href="product.php?id=<?php echo is_secure_mode() ? (int)$product['id'] : $product['id']; ?>" class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 flex flex-col h-full transform hover:-translate-y-1">
                            <div class="relative overflow-hidden bg-gray-100 aspect-w-4 aspect-h-3">
                                <?php 
                                    // Generate a stable placeholder based on ID
                                    $imageId = ($product['id'] % 6) + 1;
                                    $imgSrc = empty($product['image_url']) ? "assets/images/flowers/{$imageId}.jpg" : "assets/images/" . (is_secure_mode() ? htmlspecialchars($product['image_url']) : $product['image_url']);
                                ?>
                                <img src="<?php echo $imgSrc; ?>" 
                                     alt="<?php echo is_secure_mode() ? htmlspecialchars($product['name']) : $product['name']; ?>" 
                                     class="w-full h-64 object-cover group-hover:scale-105 transition duration-500"
                                     onerror="this.src='assets/images/flowers/<?php echo $imageId; ?>.jpg'">
                                <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <span class="bg-white/90 backdrop-blur-sm text-emerald-700 px-3 py-1.5 rounded-full text-sm font-bold shadow-lg">
                                        Voir détails <i class="fas fa-arrow-right ml-1"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="p-6 flex-1 flex flex-col">
                                <h3 class="text-xl font-bold mb-2 text-gray-900 group-hover:text-emerald-600 transition-colors"><?php echo is_secure_mode() ? htmlspecialchars($product['name']) : $product['name']; ?></h3>
                                <p class="text-gray-600 mb-4 line-clamp-2 leading-relaxed flex-1"><?php echo is_secure_mode() ? htmlspecialchars($product['description']) : $product['description']; ?></p>
                                <div class="flex justify-between items-end pt-4 border-t border-gray-100">
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1 uppercase tracking-wider font-semibold"><?php echo is_secure_mode() ? htmlspecialchars($product['category']) : $product['category']; ?></p>
                                        <span class="text-2xl font-bold text-emerald-600"><?php echo is_secure_mode() ? number_format($product['price'], 2) : $product['price']; ?>€</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm font-medium <?php echo $product['stock'] > 10 ? 'text-green-500' : 'text-orange-500'; ?>">
                                            <?php echo $product['stock'] > 0 ? 'En stock' : 'Rupture'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer - Modern Design -->
    <footer class="bg-gray-900 text-white py-16 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 mb-12">
                <div>
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-emerald-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-seedling text-white text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <div class="text-lg font-bold">Boutique des Jardins</div>
                            <div class="text-sm text-gray-400">Flower Shop</div>
                        </div>
                    </div>
                    <p class="text-gray-400 leading-relaxed"><?php echo SHOP_NAME; ?> - Votre fleuriste en ligne depuis 2026.</p>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-6">Liens Rapides</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-emerald-400 transition font-medium">Politique de Confidentialité</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-emerald-400 transition font-medium">Conditions d'Utilisation</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-emerald-400 transition font-medium">Support Technique</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-6">Contact</h3>
                    <div class="space-y-3">
                        <p class="text-gray-400 flex items-center">
                            <i class="fas fa-envelope mr-3 text-emerald-400"></i>
                            contact@boutique-jardins.fr
                        </p>
                        <p class="text-gray-400 flex items-center">
                            <i class="fas fa-phone mr-3 text-emerald-400"></i>
                            01 23 45 67 89
                        </p>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 text-center">
                <p class="text-gray-400">&copy; 2026 <?php echo SHOP_NAME; ?> - Flower Shop. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>