<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// VULNERABLE: Direct file inclusion based on user input
$page = isset($_GET['page']) ? $_GET['page'] : 'help.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pages d'Information - Boutique des Jardins</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body class="bg-gray-50">
    
    <!-- Status Badge (Hidden but still present for consistency) -->
    <div class="fixed top-6 right-6 z-50">
        <span class="bg-rose-50 text-rose-700 px-5 py-2.5 rounded-full text-sm font-semibold border border-rose-200 shadow-sm opacity-20 hover:opacity-100 transition">
            <i class="fas fa-unlock-alt mr-1"></i>
            Session Vulnérable
        </span>
    </div>

    <!-- Navigation Bar -->
    <nav class="bg-white shadow-sm border-b border-gray-100 mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-flower text-emerald-600 text-2xl"></i>
                    </div>
                    <span class="ml-4 text-2xl font-bold text-gray-900"><?php echo SHOP_NAME; ?></span>
                </div>
                
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-900 font-semibold flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i>Retour Dashboard
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-5xl font-bold text-center mb-12 text-gray-900">
                Pages d'Information
            </h1>
            
            <!-- Navigation Tabs -->
            <div class="bg-white rounded-2xl shadow-sm p-4 mb-10 border border-gray-100 flex flex-wrap gap-4 justify-center">
                <a href="?page=help.php" class="<?php echo $page === 'help.php' ? 'bg-rose-500 text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'; ?> px-8 py-3 rounded-xl font-bold transition flex items-center">
                    <i class="fas fa-question-circle mr-2"></i>Aide
                </a>
                <a href="?page=privacy.php" class="<?php echo $page === 'privacy.php' ? 'bg-rose-500 text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'; ?> px-8 py-3 rounded-xl font-bold transition flex items-center">
                    <i class="fas fa-shield-alt mr-2"></i>Confidentialité
                </a>
                <a href="?page=../index.php" class="<?php echo $page === '../index.php' ? 'bg-rose-500 text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'; ?> px-8 py-3 rounded-xl font-bold transition flex items-center">
                    <i class="fas fa-home mr-2"></i>Accueil Système
                </a>
            </div>

            <!-- Page Content Area -->
            <div class="bg-white rounded-3xl shadow-lg p-12 mb-12 border border-gray-100 min-h-[400px]">
                <?php 
                // VULNERABLE: Direct include executes PHP
                // We keep the vulnerability but remove the "Pentesting UI" clutter
                if (file_exists($page)) {
                    if (strpos($page, '.php') !== false) {
                        include($page);
                    } else {
                        echo "<div class='prose max-w-none text-gray-800'>";
                        echo nl2br(htmlspecialchars(file_get_contents($page)));
                        echo "</div>";
                    }
                } else {
                    echo "<div class='p-10 bg-red-50 border-2 border-red-100 rounded-2xl text-red-600 text-center'>";
                    echo "<i class='fas fa-exclamation-triangle text-5xl mb-4 block'></i>";
                    echo "<h3 class='text-2xl font-bold'>Erreur de chargement</h3>";
                    echo "<p class='mt-2 text-lg'>Le fichier spécifié est introuvable ou inaccessible.</p>";
                    echo "</div>";
                }
                ?>
            </div>
            
            <!-- Footer Info -->
            <div class="text-center text-gray-400 text-sm">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SHOP_NAME; ?> - Système Interne d'Information</p>
            </div>
        </div>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>
