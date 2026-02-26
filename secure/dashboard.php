<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Consistency check
if (!is_secure_mode()) {
    header("Location: ../vulnerable/dashboard.php");
    exit();
}

// Get user information
$user_info = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? 'Utilisateur',
    'full_name' => $_SESSION['full_name'] ?? 'Nom Complet',
    'email' => $_SESSION['email'] ?? 'email@example.com',
    'role' => $_SESSION['role'] ?? 'student',
    'department' => $_SESSION['department'] ?? 'Non spécifié'
];

// Role-based access and features
$role_config = [
    'admin' => [
        'title' => 'Administrateur',
        'color' => 'rose',
        'icon' => 'fas fa-user-shield',
        'features' => ['user_management', 'system_config', 'security_logs', 'all_access']
    ],
    'manager' => [
        'title' => 'Gérant',
        'color' => 'blue',
        'icon' => 'fas fa-user-tie',
        'features' => ['inventory_management', 'sales_reports', 'staff_management', 'customer_service']
    ],
    'seller' => [
        'title' => 'Vendeur',
        'color' => 'emerald',
        'icon' => 'fas fa-cash-register',
        'features' => ['sales', 'customer_service', 'inventory_view', 'order_processing']
    ],
    'customer' => [
        'title' => 'Client',
        'color' => 'purple',
        'icon' => 'fas fa-user',
        'features' => ['browse_products', 'place_orders', 'view_history', 'reviews']
    ]
];

$current_role = $role_config[$user_info['role']] ?? $role_config['customer'];

// Get recent activity (secure query)
$recent_activities = [];
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn->connect_error) {
    // Create activity log table if it doesn't exist
    $create_table = "CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        activity_type VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES user_profiles(id)
    )";
    $conn->query($create_table);
    
    // Verify user exists locally before logging activity to avoid foreign key failure
    $check_stmt = $conn->prepare("SELECT id FROM user_profiles WHERE id = ?");
    $check_stmt->bind_param("i", $user_info['id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Insert login activity
        $stmt = $conn->prepare("INSERT INTO activity_log (user_id, activity_type, description) VALUES (?, 'login', 'Connexion au tableau de bord')");
        $stmt->bind_param("i", $user_info['id']);
        $stmt->execute();
        $stmt->close();
    }
    $check_stmt->close();
    
    // Get recent activities
    $stmt = $conn->prepare("SELECT activity_type, description, created_at FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("i", $user_info['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_activities[] = $row;
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Boutique Sécurisée</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body class="bg-gray-50 min-h-screen">
    
    <!-- Security Badge -->
    <div class="fixed top-6 right-6 z-50">
        <span class="bg-emerald-50 text-emerald-700 px-5 py-2.5 rounded-full text-sm font-semibold border border-emerald-200 shadow-sm">
            <i class="fas fa-shield-alt mr-1"></i>
            Session Sécurisée
        </span>
    </div>
    
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-sm border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-flower text-emerald-600 text-2xl"></i>
                    </div>
                    <span class="ml-4 text-2xl font-bold text-gray-900"><?php echo SHOP_NAME; ?></span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-<?php echo $current_role['color']; ?>-50 rounded-xl flex items-center justify-center">
                            <i class="<?php echo $current_role['icon']; ?> text-<?php echo $current_role['color']; ?>-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($user_info['full_name']); ?></p>
                            <span class="text-xs bg-<?php echo $current_role['color']; ?>-50 text-<?php echo $current_role['color']; ?>-700 px-2 py-0.5 rounded-full font-semibold">
                                <?php echo htmlspecialchars($current_role['title']); ?>
                            </span>
                        </div>
                    </div>
                    <a href="../logout.php" class="bg-rose-500 text-white px-5 py-2.5 rounded-xl hover:bg-rose-600 transition font-semibold shadow-sm">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-12">
        <div class="max-w-7xl mx-auto">
            <!-- Welcome Section -->
            <div class="bg-white rounded-3xl shadow-sm p-10 mb-8 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-5xl font-bold mb-3 text-gray-900">
                            Bienvenue, <?php echo htmlspecialchars($user_info['full_name']); ?> !
                        </h1>
                        <p class="text-lg text-gray-600 mb-2">
                            <span class="font-semibold text-<?php echo $current_role['color']; ?>-600"><?php echo htmlspecialchars($current_role['title']); ?></span> 
                            <span class="text-gray-400">·</span> 
                            <?php echo htmlspecialchars($user_info['department']); ?>
                        </p>
                        <p class="text-sm text-gray-500">
                            <i class="fas fa-clock mr-1"></i>
                            Dernière connexion : <?php echo date('d/m/Y H:i'); ?>
                        </p>
                    </div>
                    <div class="w-24 h-24 bg-<?php echo $current_role['color']; ?>-50 rounded-2xl flex items-center justify-center">
                        <i class="<?php echo $current_role['icon']; ?> text-<?php echo $current_role['color']; ?>-600 text-5xl"></i>
                    </div>
                </div>
            </div>

            <div class=" gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Features Based on Role -->
                    <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-100">
                        <h2 class="text-2xl font-bold mb-6 text-gray-900 flex items-center">
                            <div class="w-10 h-10 bg-<?php echo $current_role['color']; ?>-50 rounded-xl flex items-center justify-center mr-3">
                                <i class="fas fa-star text-<?php echo $current_role['color']; ?>-600"></i>
                            </div>
                            Fonctionnalités <?php echo htmlspecialchars($current_role['title']); ?>
                        </h2>
                        
                        <?php if ($user_info['role'] === 'admin'): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-rose-50 border border-rose-200 rounded-xl p-5 hover:shadow-md transition">
                                    <h3 class="font-bold text-rose-900 mb-2 flex items-center">
                                        <i class="fas fa-users mr-2"></i>Gestion Utilisateurs
                                    </h3>
                                    <p class="text-sm text-rose-700">Gérer les comptes clients, vendeurs et gérants</p>
                                </div>
                                <div class="bg-rose-50 border border-rose-200 rounded-xl p-5 hover:shadow-md transition">
                                    <h3 class="font-bold text-rose-900 mb-2 flex items-center">
                                        <i class="fas fa-cog mr-2"></i>Configuration Système
                                    </h3>
                                    <p class="text-sm text-rose-700">Paramètres et configuration de la boutique</p>
                                </div>
                                <div class="bg-rose-50 border border-rose-200 rounded-xl p-5 hover:shadow-md transition">
                                    <h3 class="font-bold text-rose-900 mb-2 flex items-center">
                                        <i class="fas fa-shield-alt mr-2"></i>Logs de Sécurité
                                    </h3>
                                    <p class="text-sm text-rose-700">Surveillance et audit de sécurité</p>
                                </div>
                                <div class="bg-rose-50 border border-rose-200 rounded-xl p-5 hover:shadow-md transition">
                                    <h3 class="font-bold text-rose-900 mb-2 flex items-center">
                                        <i class="fas fa-chart-bar mr-2"></i>Statistiques
                                    </h3>
                                    <p class="text-sm text-rose-700">Rapports de ventes et statistiques</p>
                                </div>
                            </div>
                        <?php elseif ($user_info['role'] === 'manager'): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 hover:shadow-md transition">
                                    <h3 class="font-bold text-blue-900 mb-2 flex items-center">
                                        <i class="fas fa-boxes mr-2"></i>Gestion Stock
                                    </h3>
                                    <p class="text-sm text-blue-700">Gérer l'inventaire et les approvisionnements</p>
                                </div>
                                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 hover:shadow-md transition">
                                    <h3 class="font-bold text-blue-900 mb-2 flex items-center">
                                        <i class="fas fa-chart-line mr-2"></i>Rapports Ventes
                                    </h3>
                                    <p class="text-sm text-blue-700">Analyser les performances de vente</p>
                                </div>
                                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 hover:shadow-md transition">
                                    <h3 class="font-bold text-blue-900 mb-2 flex items-center">
                                        <i class="fas fa-user-friends mr-2"></i>Équipe
                                    </h3>
                                    <p class="text-sm text-blue-700">Gestion de l'équipe de vendeurs</p>
                                </div>
                                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 hover:shadow-md transition">
                                    <h3 class="font-bold text-blue-900 mb-2 flex items-center">
                                        <i class="fas fa-headset mr-2"></i>Service Client
                                    </h3>
                                    <p class="text-sm text-blue-700">Support et relation client</p>
                                </div>
                            </div>
                        <?php elseif ($user_info['role'] === 'seller'): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 hover:shadow-md transition">
                                    <h3 class="font-bold text-emerald-900 mb-2 flex items-center">
                                        <i class="fas fa-cash-register mr-2"></i>Ventes
                                    </h3>
                                    <p class="text-sm text-emerald-700">Traiter les commandes et paiements</p>
                                </div>
                                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 hover:shadow-md transition">
                                    <h3 class="font-bold text-emerald-900 mb-2 flex items-center">
                                        <i class="fas fa-users mr-2"></i>Clients
                                    </h3>
                                    <p class="text-sm text-emerald-700">Assistance et conseil clientèle</p>
                                </div>
                                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 hover:shadow-md transition">
                                    <h3 class="font-bold text-emerald-900 mb-2 flex items-center">
                                        <i class="fas fa-eye mr-2"></i>Stock
                                    </h3>
                                    <p class="text-sm text-emerald-700">Consulter les disponibilités</p>
                                </div>
                                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 hover:shadow-md transition">
                                    <h3 class="font-bold text-emerald-900 mb-2 flex items-center">
                                        <i class="fas fa-truck mr-2"></i>Commandes
                                    </h3>
                                    <p class="text-sm text-emerald-700">Traitement des commandes</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-purple-50 border border-purple-200 rounded-xl p-5 hover:shadow-md transition">
                                    <h3 class="font-bold text-purple-900 mb-2 flex items-center">
                                        <i class="fas fa-shopping-cart mr-2"></i>Mes Commandes
                                    </h3>
                                    <p class="text-sm text-purple-700">Historique et suivi de commandes</p>
                                </div>
                                <div class="bg-purple-50 border border-purple-200 rounded-xl p-5 hover:shadow-md transition">
                                    <h3 class="font-bold text-purple-900 mb-2 flex items-center">
                                        <i class="fas fa-heart mr-2"></i>Favoris
                                    </h3>
                                    <p class="text-sm text-purple-700">Produits favoris et liste de souhaits</p>
                                </div>
                                <div class="bg-purple-50 border border-purple-200 rounded-xl p-5 hover:shadow-md transition">
                                    <h3 class="font-bold text-purple-900 mb-2 flex items-center">
                                        <i class="fas fa-star mr-2"></i>Avis
                                    </h3>
                                    <p class="text-sm text-purple-700">Laisser des avis sur les produits</p>
                                </div>
                                <div class="bg-purple-50 border border-purple-200 rounded-xl p-5 hover:shadow-md transition">
                                    <h3 class="font-bold text-purple-900 mb-2 flex items-center">
                                        <i class="fas fa-user-circle mr-2"></i>Profil
                                    </h3>
                                    <p class="text-sm text-purple-700">Gérer vos informations personnelles</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Security Modules Grid -->
                    <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-100">
                        <h2 class="text-2xl font-bold mb-6 text-gray-900 flex items-center">
                            <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center mr-3">
                                <i class="fas fa-shield-alt text-emerald-600"></i>
                            </div>
                            Modules de Sécurité (Protection Active)
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Link to Search -->
                            <a href="../products.php" class="group p-6 rounded-xl border border-gray-100 bg-gray-50 hover:bg-blue-50 hover:border-blue-200 transition">
                                <div class="flex items-center mb-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-blue-200 transition">
                                        <i class="fas fa-search text-blue-600"></i>
                                    </div>
                                    <h3 class="font-bold text-gray-900 group-hover:text-blue-700">Recherche Sécurisée</h3>
                                </div>
                                <p class="text-sm text-gray-600">Requêtes préparées et validation stricte des entrées.</p>
                            </a>

                            <!-- Link to Comments -->
                            <a href="../product.php?id=1" class="group p-6 rounded-xl border border-gray-100 bg-gray-50 hover:bg-purple-50 hover:border-purple-200 transition">
                                <div class="flex items-center mb-3">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-purple-200 transition">
                                        <i class="fas fa-comments text-purple-600"></i>
                                    </div>
                                    <h3 class="font-bold text-gray-900 group-hover:text-purple-700">Avis & CSRF</h3>
                                </div>
                                <p class="text-sm text-gray-600">Protection contre XSS et validation par jeton CSRF.</p>
                            </a>



                        </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- User Profile -->
                    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                        <h3 class="text-xl font-bold mb-5 text-gray-900 flex items-center">
                            <div class="w-8 h-8 bg-<?php echo $current_role['color']; ?>-50 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-user text-<?php echo $current_role['color']; ?>-600 text-sm"></i>
                            </div>
                            Profil
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Nom complet</label>
                                <p class="text-gray-900 font-medium mt-1"><?php echo htmlspecialchars($user_info['full_name']); ?></p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Identifiant</label>
                                <p class="text-gray-900 font-medium mt-1"><?php echo htmlspecialchars($user_info['username']); ?></p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</label>
                                <p class="text-gray-900 font-medium mt-1"><?php echo htmlspecialchars($user_info['email']); ?></p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Département</label>
                                <p class="text-gray-900 font-medium mt-1"><?php echo htmlspecialchars($user_info['department']); ?></p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Rôle</label>
                                <span class="inline-block bg-<?php echo $current_role['color']; ?>-50 text-<?php echo $current_role['color']; ?>-700 px-3 py-1.5 rounded-lg text-sm font-semibold mt-1">
                                    <?php echo htmlspecialchars($current_role['title']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                        <h3 class="text-xl font-bold mb-5 text-gray-900 flex items-center">
                            <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-history text-blue-600 text-sm"></i>
                            </div>
                            Activité Récente
                        </h3>
                        <div class="space-y-4">
                            <?php if (empty($recent_activities)): ?>
                                <p class="text-gray-500 text-sm">Aucune activité récente</p>
                            <?php else: ?>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="flex items-start space-x-3">
                                        <div class="w-2 h-2 bg-emerald-500 rounded-full mt-2 flex-shrink-0"></div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($activity['description']); ?></p>
                                            <p class="text-xs text-gray-500 mt-0.5"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($activity['created_at']))); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Security Status -->
                    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                        <h3 class="text-xl font-bold mb-5 text-gray-900 flex items-center">
                            <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-shield-alt text-emerald-600 text-sm"></i>
                            </div>
                            Statut Sécurité
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700 font-medium">Session HTTPS</span>
                                <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700 font-medium">Auth LDAP</span>
                                <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700 font-medium">Session sécurisée</span>
                                <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700 font-medium">Protection CSRF</span>
                                <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>