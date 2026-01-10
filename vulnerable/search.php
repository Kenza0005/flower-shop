<?php
require_once '../includes/config.php';

$results = [];
$search_query = '';

if (isset($_GET['query'])) {
    // VULNERABLE: No sanitization
    $search_query = $_GET['query'];
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if (!$conn->connect_error) {
        // VULNERABLE: Direct SQL injection
        $sql = "SELECT id, name, description, price, stock, category, image_url 
                FROM products 
                WHERE name LIKE '%$search_query%' OR description LIKE '%$search_query%'";
        
        $result = $conn->query($sql);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche de Produits - Vulnérable</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body class="bg-gray-50">    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-4xl font-bold text-center mb-8 text-gray-800">
                <i class="fas fa-search mr-3 text-rose-500"></i>Recherche de Produits (Vulnérable)
            </h1>
            
            <!-- Search Form -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8 border-t-4 border-rose-500">
                <form method="GET" action="" class="flex gap-4">
                    <input type="text" 
                           name="query" 
                           value="<?php echo $search_query; // VULNERABLE: XSS ?>" 
                           class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-rose-500"
                           placeholder="Rechercher des fleurs...">
                    <button type="submit" 
                            class="bg-rose-500 text-white px-6 py-3 rounded-lg hover:bg-rose-600 transition font-semibold">
                        <i class="fas fa-search mr-2"></i>Recherche
                    </button>
                </form>
                <div class="mt-4 p-3 bg-rose-50 text-rose-700 rounded-lg text-sm">
                    <i class="fas fa-bug mr-2"></i>
                    <strong>Zone de Test:</strong> Cette page est intentionnellement vulnérable à l'injection SQL et au XSS.
                </div>
            </div>
            
            <!-- Results -->
            <?php if (!empty($results)): ?>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-2xl font-bold mb-4">Résultats pour: <?php echo $search_query; // VULNERABLE: XSS ?></h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($results as $product): ?>
                            <div class="border rounded-lg p-4 hover:shadow-lg transition">
                                <img src="../assets/images/<?php echo $product['image_url']; ?>" 
                                     alt="<?php echo $product['name']; ?>" 
                                     class="w-full h-48 object-cover rounded mb-4">
                                <h3 class="font-bold text-lg"><?php echo $product['name']; ?></h3>
                                <p class="text-gray-600 mt-2"><?php echo $product['description']; ?></p>
                                <p class="text-rose-500 font-bold text-xl mt-2"><?php echo $product['price']; ?>€</p>
                                <p class="text-sm text-gray-500 mt-1">Stock: <?php echo $product['stock']; ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif (isset($_GET['query'])): ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                    Aucun produit trouvé pour: <?php echo $search_query; // VULNERABLE: XSS ?>
                </div>
            <?php endif; ?>
            
            <div class="mt-8 text-center">
                <a href="dashboard.php" class="text-rose-600 hover:text-rose-700">
                    <i class="fas fa-arrow-left mr-1"></i>Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>
</body>
</html>
