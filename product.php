<?php
require_once 'includes/config.php';

$product_id = isset($_GET['id']) ? $_GET['id'] : 1;
$product = null;
$reviews = [];
$error = '';
$message = '';

// SECURE: Generate CSRF token
if (is_secure_mode() && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure reviews table exists (fallback if database.sql wasn't fully run)
$conn->query("CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    reviewer_username VARCHAR(50) NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    is_approved BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle POST request for reviews
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    if (is_secure_mode()) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Requête invalide.";
        } else {
            $comment_text = trim($_POST['comment']);
            $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
            if (empty($comment_text)) {
                $error = "Le commentaire ne peut pas être vide.";
            } elseif ($rating < 1 || $rating > 5) {
                $error = "Note invalide.";
            } else {
                $stmt = $conn->prepare("INSERT INTO reviews (product_id, reviewer_username, rating, comment, is_approved) VALUES (?, ?, ?, ?, 1)");
                $stmt->bind_param("isis", $product_id, $_SESSION['username'], $rating, $comment_text);
                if ($stmt->execute()) {
                    $message = "Avis publié avec succès !";
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $error = "Erreur de publication.";
                }
                $stmt->close();
            }
        }
    } else {
        // VULNERABLE: No CSRF check, direct SQL insertion, no sanitization.
        $comment_text = $_POST['comment'];
        $rating = $_POST['rating'];
        $username = $_SESSION['username'];
        
        $sql = "INSERT INTO reviews (product_id, reviewer_username, rating, comment, is_approved) 
                VALUES ($product_id, '$username', $rating, '$comment_text', 1)";
        if ($conn->query($sql)) {
            $message = "Avis publié !";
        } else {
            $error = "Échec : " . $conn->error; // VULNERABLE: Info disclosure
        }
    }
}

// Fetch product details
if (is_secure_mode()) {
    $stmt = $conn->prepare("SELECT id, name, description, price, stock, category, image_url FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $product = $row;
    }
    $stmt->close();
} else {
    // VULNERABLE: Direct SQL injection via GET param
    $sql = "SELECT id, name, description, price, stock, category, image_url FROM products WHERE id = " . $product_id;
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
    }
}

// Fetch reviews
if ($product) {
    if (is_secure_mode()) {
        $stmt = $conn->prepare("SELECT r.comment, r.rating, r.created_at, u.full_name 
                               FROM reviews r 
                               JOIN user_profiles u ON r.reviewer_username = u.username 
                               WHERE r.product_id = ? 
                               ORDER BY r.created_at DESC");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $reviews[] = $row;
        }
        $stmt->close();
    } else {
        $sql = "SELECT r.comment, r.rating, r.created_at, u.full_name 
                FROM reviews r 
                JOIN user_profiles u ON r.reviewer_username = u.username 
                WHERE r.product_id = $product_id 
                ORDER BY r.created_at DESC";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $reviews[] = $row;
            }
        }
    }
}

$conn->close();

if (!$product) {
    die("Produit non trouvé ou erreur SQL.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Vulnerable to XSS in title if tracking logic existed -->
    <title><?php echo is_secure_mode() ? htmlspecialchars($product['name']) : $product['name']; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/custom.css">
</head>
<body class="bg-gray-50 min-h-screen">
    
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
                        <a href="toggle_mode.php" class="flex items-center space-x-2 px-4 py-2 rounded-xl text-sm font-semibold transition <?php echo is_secure_mode() ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm' : 'bg-rose-500 text-white hover:bg-rose-600 shadow-sm'; ?>">
                            <i class="fas <?php echo is_secure_mode() ? 'fa-shield-alt' : 'fa-unlock-alt'; ?>"></i>
                            <span><?php echo is_secure_mode() ? 'Mode Sécurisé' : 'Mode Vulnérable'; ?></span>
                        </a>
                    <?php endif; ?>

                    <a href="index.php" class="text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 px-4 py-2 rounded-xl text-sm font-semibold transition">Accueil</a>
                    <a href="products.php" class="text-emerald-600 px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-50">Catalogue</a>
                    
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
    
    <!-- Status Badge -->
    <?php if (!is_secure_mode()): ?>
    <div class="fixed top-24 right-6 z-40">
        <span class="bg-rose-50 text-rose-700 px-5 py-2.5 rounded-full text-sm font-semibold border border-rose-200 shadow-sm">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            XSS & SQLi Actif
        </span>
    </div>
    <?php endif; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <a href="products.php" class="inline-flex items-center text-emerald-600 hover:text-emerald-700 font-semibold mb-8">
            <i class="fas fa-arrow-left mr-2"></i>Retour au catalogue
        </a>

        <!-- Product Details -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-12">
            <div class="grid grid-cols-1 md:grid-cols-2">
                <div class="bg-gray-100">
                    <?php 
                        $imageId = ($product['id'] % 6) + 1;
                        $imgSrc = empty($product['image_url']) ? "assets/images/flowers/{$imageId}.jpg" : "assets/images/" . (is_secure_mode() ? htmlspecialchars($product['image_url']) : $product['image_url']);
                    ?>
                    <img src="<?php echo $imgSrc; ?>" 
                         alt="<?php echo is_secure_mode() ? htmlspecialchars($product['name']) : $product['name']; ?>" 
                         class="w-full h-full object-cover min-h-[400px]"
                         onerror="this.src='assets/images/flowers/<?php echo $imageId; ?>.jpg'">
                </div>
                <div class="p-10 flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-4">
                            <h1 class="text-4xl font-bold text-gray-900"><?php echo is_secure_mode() ? htmlspecialchars($product['name']) : $product['name']; ?></h1>
                            <span class="text-3xl font-bold text-emerald-600"><?php echo is_secure_mode() ? number_format($product['price'], 2) : $product['price']; ?>€</span>
                        </div>
                        <p class="text-emerald-600 font-semibold mb-6 uppercase tracking-wider text-sm"><?php echo is_secure_mode() ? htmlspecialchars($product['category']) : $product['category']; ?></p>
                        
                        <div class="prose max-w-none text-gray-600 mb-8 leading-relaxed">
                            <?php echo is_secure_mode() ? nl2br(htmlspecialchars($product['description'])) : $product['description']; ?>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center justify-between py-6 border-t border-gray-100">
                            <span class="text-gray-700 font-medium">Disponibilité</span>
                            <span class="font-bold <?php echo $product['stock'] > 10 ? 'text-green-500' : 'text-orange-500'; ?>">
                                <?php echo $product['stock'] > 0 ? "En stock ({$product['stock']})" : 'Rupture'; ?>
                            </span>
                        </div>
                        <button class="w-full bg-emerald-600 text-white px-8 py-4 rounded-xl hover:bg-emerald-700 transition font-bold shadow-sm hover:shadow-md text-lg flex items-center justify-center">
                            <i class="fas fa-shopping-cart mr-2"></i> Ajouter au panier
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <!-- Review Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-100 sticky top-28 <?php echo !is_secure_mode() ? 'border-t-4 border-rose-500' : ''; ?>">
                    <h3 class="text-xl font-bold mb-6 text-gray-900">Donner votre avis</h3>
                    
                    <?php if ($message): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                            <?php echo is_secure_mode() ? htmlspecialchars($message) : $message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                            <?php echo is_secure_mode() ? htmlspecialchars($error) : $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form method="POST" action="">
                            <?php if (is_secure_mode()): ?>
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <?php endif; ?>
                            
                            <div class="mb-5">
                                <label class="block text-gray-700 font-semibold mb-2">Note sur 5</label>
                                <select name="rating" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    <option value="5">5 - Excellent</option>
                                    <option value="4">4 - Très bien</option>
                                    <option value="3">3 - Moyen</option>
                                    <option value="2">2 - Décevant</option>
                                    <option value="1">1 - Mauvais</option>
                                </select>
                            </div>
                            
                            <div class="mb-5">
                                <label class="block text-gray-700 font-semibold mb-2">Votre avis</label>
                                <textarea name="comment" rows="4" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="<?php echo !is_secure_mode() ? 'Testez le XSS avec <script>alert(1)</script>' : 'Ex: Magnifique bouquet...'; ?>" required></textarea>
                            </div>
                            
                            <button type="submit" class="w-full <?php echo is_secure_mode() ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-500 hover:bg-rose-600'; ?> text-white px-6 py-3 rounded-xl transition font-bold shadow-sm flex justify-center items-center">
                                <i class="fas fa-paper-plane mr-2"></i> Publier l'avis
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="bg-gray-50 rounded-xl p-6 text-center border border-gray-100">
                            <i class="fas fa-lock text-3xl text-gray-400 mb-3 block"></i>
                            <p class="text-gray-600 mb-4">Vous devez être connecté pour publier un avis.</p>
                            <a href="<?php echo get_login_url(); ?>" class="inline-block bg-emerald-600 text-white px-6 py-2.5 rounded-lg hover:bg-emerald-700 transition font-semibold">
                                Se connecter
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reviews List -->
            <div class="lg:col-span-2">
                <h3 class="text-2xl font-bold mb-6 text-gray-900 flex items-center">
                    Avis Clients <span class="ml-3 bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm"><?php echo count($reviews); ?></span>
                </h3>

                <?php if (empty($reviews)): ?>
                    <div class="bg-white rounded-2xl shadow-sm p-10 text-center text-gray-500 border border-gray-100">
                        <i class="fas fa-comment-slash text-4xl text-gray-300 mb-4 block"></i>
                        <p class="text-lg">Aucun avis pour le moment.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($reviews as $review): ?>
                            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-bold text-gray-900"><?php echo htmlspecialchars($review['full_name']); ?></h4>
                                        <div class="flex text-amber-400 text-sm mt-1">
                                            <?php for($i=1; $i<=5; $i++) echo '<i class="fas fa-star ' . ($i <= $review['rating'] ? '' : 'text-gray-200') . '"></i>'; ?>
                                        </div>
                                    </div>
                                    <span class="text-sm text-gray-500 bg-gray-50 px-3 py-1 rounded-lg">
                                        <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="text-gray-700 leading-relaxed mt-4">
                                    <?php 
                                        // VULNERABLE rendering in vulnerable mode (Stored XSS)
                                        echo is_secure_mode() ? nl2br(htmlspecialchars($review['comment'])) : $review['comment']; 
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>
