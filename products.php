<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produits - <?php echo SITE_NAME; ?></title>
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

    <!-- Page Header -->
    <section class="bg-white py-16 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl font-bold mb-4 text-gray-900">Nos Magnifiques Fleurs</h1>
            <p class="text-lg text-gray-600">Découvrez notre collection complète de bouquets frais et élégants</p>
        </div>
    </section>

    <!-- Products Section -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8" id="allProducts">
                <!-- Products will be loaded here via JS -->
                <div class="text-center py-12 col-span-3">
                    <div class="w-16 h-16 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-spinner fa-spin text-3xl text-rose-500"></i>
                    </div>
                    <p class="text-gray-600 font-medium">Chargement des produits...</p>
                </div>
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
    <script>
        // Load all products
        document.addEventListener('DOMContentLoaded', function() {
            fetch('api/get_products.php')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('allProducts');
                    container.innerHTML = '';
                    
                    if (data.success && data.products.length > 0) {
                        data.products.forEach(product => {
                            container.innerHTML += createProductCard(product);
                        });
                    } else {
                        container.innerHTML = '<p class="col-span-3 text-center text-gray-600">Aucun produit disponible</p>';
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des produits:', error);
                    document.getElementById('allProducts').innerHTML = 
                        '<p class="col-span-3 text-center text-red-600">Erreur lors du chargement des produits</p>';
                });
        });
    </script>
</body>
</html>