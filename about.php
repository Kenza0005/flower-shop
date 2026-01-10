<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À propos - <?php echo SITE_NAME; ?></title>
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
                    <a href="products.php" class="text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 px-4 py-2 rounded-xl text-sm font-semibold transition">Catalogue</a>
                    <a href="about.php" class="text-emerald-600 px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-50">À propos</a>
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
            <h1 class="text-5xl font-bold mb-4 text-gray-900">À propos de Flower Shop</h1>
            <p class="text-lg text-gray-600">Découvrez notre histoire et nos valeurs</p>
        </div>
    </section>

    <!-- About Section -->
    <section class="py-20">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Hero Card -->
            <div class="bg-white rounded-3xl shadow-sm p-12 mb-12 border border-gray-100">
                <div class="text-center mb-10">
                    <div class="w-20 h-20 bg-rose-50 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-flower text-rose-500 text-4xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-3">Votre partenaire floral de confiance</h2>
                    <p class="text-gray-600">Depuis 2026</p>
                </div>
                
                <div class="space-y-8">
                    <div>
                        <p class="text-lg text-gray-700 leading-relaxed">
                            Bienvenue chez Flower Shop, votre destination de choix pour de magnifiques fleurs fraîches depuis 2026. 
                            Nous sommes passionnés par l'idée d'apporter joie et beauté dans votre vie grâce à notre sélection 
                            soigneusement choisie de fleurs et d'arrangements.
                        </p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-2xl p-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Notre Mission</h3>
                        <p class="text-gray-700 leading-relaxed">
                            Fournir les fleurs les plus fraîches et les plus belles tout en offrant un service client exceptionnel. 
                            Nous croyons que les fleurs ont le pouvoir d'exprimer les émotions, de célébrer les moments et d'égayer n'importe quelle journée.
                        </p>
                    </div>
                    
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-6">Pourquoi nous choisir ?</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex items-start space-x-4 bg-emerald-50 rounded-xl p-5">
                                <div class="w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-check text-white"></i>
                                </div>
                                <p class="text-gray-700 font-medium">Fleurs fraîches approvisionnées quotidiennement auprès de fermes locales</p>
                            </div>
                            <div class="flex items-start space-x-4 bg-blue-50 rounded-xl p-5">
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-check text-white"></i>
                                </div>
                                <p class="text-gray-700 font-medium">Fleuristes experts avec des années d'expérience</p>
                            </div>
                            <div class="flex items-start space-x-4 bg-purple-50 rounded-xl p-5">
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-check text-white"></i>
                                </div>
                                <p class="text-gray-700 font-medium">Livraison gratuite sur les commandes de plus de 50€</p>
                            </div>
                            <div class="flex items-start space-x-4 bg-rose-50 rounded-xl p-5">
                                <div class="w-10 h-10 bg-rose-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-check text-white"></i>
                                </div>
                                <p class="text-gray-700 font-medium">Garantie de satisfaction à 100%</p>
                            </div>
                            <div class="flex items-start space-x-4 bg-amber-50 rounded-xl p-5 md:col-span-2">
                                <div class="w-10 h-10 bg-amber-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-check text-white"></i>
                                </div>
                                <p class="text-gray-700 font-medium">Arrangements personnalisés pour les occasions spéciales</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl p-8 text-center shadow-sm border border-gray-100">
                    <div class="w-16 h-16 bg-emerald-50 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-emerald-600 text-2xl"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-2">10,000+</h3>
                    <p class="text-gray-600 font-medium">Clients satisfaits</p>
                </div>
                <div class="bg-white rounded-2xl p-8 text-center shadow-sm border border-gray-100">
                    <div class="w-16 h-16 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-flower text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-2">500+</h3>
                    <p class="text-gray-600 font-medium">Variétés de fleurs</p>
                </div>
                <div class="bg-white rounded-2xl p-8 text-center shadow-sm border border-gray-100">
                    <div class="w-16 h-16 bg-rose-50 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-award text-rose-600 text-2xl"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-2">4.9/5</h3>
                    <p class="text-gray-600 font-medium">Note moyenne</p>
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
</body>
</html>