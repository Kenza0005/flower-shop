<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// SECURE: Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$product_id = isset($_GET['product']) ? intval($_GET['product']) : 1;
$comments = [];
$message = '';
$error = '';

// SECURE: POST request handling with validation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // SECURE: Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Requête invalide. Veuillez réessayer.";
    } else {
        // SECURE: Input sanitization and validation
        $comment_text = trim($_POST['comment']);
        $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
        
        // SECURE: Validate inputs
        if (empty($comment_text)) {
            $error = "Le commentaire ne peut pas être vide.";
        } elseif (strlen($comment_text) < 10) {
            $error = "Le commentaire doit contenir au moins 10 caractères.";
        } elseif (strlen($comment_text) > 500) {
            $error = "Le commentaire est trop long (max 500 caractères).";
        } elseif ($rating < 1 || $rating > 5) {
            $error = "Note invalide.";
        } elseif (!preg_match('/^[a-zA-Z0-9\s\-.,!?àáâäèéêëìíîïòóôöùúûüçñ]+$/u', $comment_text)) {
            $error = "Le commentaire contient des caractères non autorisés.";
        } else {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                $error = "Service temporairement indisponible.";
                error_log("Database connection failed: " . $conn->connect_error);
            } else {
                // SECURE: Check if product exists
                $check_stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
                $check_stmt->bind_param("i", $product_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows == 0) {
                    $error = "Produit non trouvé.";
                } else {
                    // SECURE: Check for duplicate comments (prevent spam)
                    $dup_stmt = $conn->prepare("SELECT id FROM comments WHERE user_id = ? AND product_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
                    $dup_stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
                    $dup_stmt->execute();
                    $dup_result = $dup_stmt->get_result();
                    
                    if ($dup_result->num_rows > 0) {
                        $error = "Vous avez déjà commenté ce produit récemment. Veuillez attendre 1 heure.";
                    } else {
                        // SECURE: Prepared statement for insertion
                        $stmt = $conn->prepare("INSERT INTO comments (user_id, product_id, comment, rating, created_at) 
                                               VALUES (?, ?, ?, ?, NOW())");
                        $stmt->bind_param("iisi", $_SESSION['user_id'], $product_id, $comment_text, $rating);
                        
                        if ($stmt->execute()) {
                            $message = "Commentaire publié avec succès !";
                            // Regenerate CSRF token after use
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        } else {
                            $error = "Échec de la publication du commentaire. Veuillez réessayer.";
                            error_log("Comment insertion failed: " . $stmt->error);
                        }
                        
                        $stmt->close();
                    }
                    $dup_stmt->close();
                }
                $check_stmt->close();
                $conn->close();
            }
        }
    }
}

// SECURE: Fetch comments with prepared statement
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn->connect_error) {
    // Ensure comments table exists
    $create_comments = "CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        comment TEXT NOT NULL,
        rating INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES user_profiles(id)
    )";
    $conn->query($create_comments);

    $stmt = $conn->prepare("SELECT c.comment, c.rating, c.created_at, u.full_name 
                           FROM comments c 
                           JOIN user_profiles u ON c.user_id = u.id 
                           WHERE c.product_id = ? 
                           ORDER BY c.created_at DESC
                           LIMIT 50");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    
    $stmt->close();
    $conn->close();
}

// SECURE: Get product info
$product_name = "Produit";
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn->connect_error) {
    $stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $product_name = $row['name'];
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
    <title>Commentaires Produit - Sécurisé</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body class="bg-gray-50 min-h-screen py-12 px-4">
    
    <!-- Security Badge -->
    <div class="fixed top-6 right-6 z-50">
        <span class="bg-emerald-50 text-emerald-700 px-5 py-2.5 rounded-full text-sm font-semibold border border-emerald-200 shadow-sm">
            <i class="fas fa-shield-alt mr-1"></i>
            XSS Protégé
        </span>
    </div>
    
    <div class="container mx-auto">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-5xl font-bold text-gray-900">
                    <i class="fas fa-comments mr-3 text-purple-600"></i>Avis sur <?php echo htmlspecialchars($product_name); ?>
                </h1>
                <a href="../index.php" class="text-emerald-600 hover:text-emerald-700 font-semibold inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Retour à la boutique
                </a>
            </div>
            
            <?php if ($message): ?>
                <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 px-5 py-4 rounded-xl mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-check-circle text-xl mr-3 flex-shrink-0 mt-0.5"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($message); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-5 py-4 rounded-xl mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-circle text-xl mr-3 flex-shrink-0 mt-0.5"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Comment Form -->
            <div class="bg-white rounded-2xl shadow-sm p-8 mb-8 border border-gray-100">
                <h2 class="text-2xl font-bold mb-6 text-gray-900">Laisser un Avis</h2>
                <form method="POST" action="" onsubmit="return validateComment()" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    
                    <div>
                        <label class="block text-gray-900 font-semibold mb-3">Note</label>
                        <div class="flex gap-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label class="cursor-pointer group">
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" class="hidden peer" required>
                                    <i class="fas fa-star text-4xl text-gray-300 peer-checked:text-amber-400 group-hover:text-amber-300 transition"></i>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-900 font-semibold mb-3">Votre Commentaire</label>
                        <textarea name="comment" 
                                  id="commentText"
                                  rows="5" 
                                  class="w-full px-5 py-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition bg-gray-50 resize-none"
                                  placeholder="Partagez votre expérience avec ce produit..."
                                  required
                                  minlength="10"
                                  maxlength="500"
                                  pattern="[a-zA-Z0-9\s\-.,!?àáâäèéêëìíîïòóôöùúûüçñ]+"
                                  title="Seuls les lettres, chiffres, espaces et ponctuation de base sont autorisés"></textarea>
                        <div class="flex justify-between items-center mt-2">
                            <p class="text-sm text-gray-500">
                                <span id="charCount" class="font-semibold">0</span>/500 caractères (minimum 10)
                            </p>
                            <span id="charStatus" class="text-xs font-semibold"></span>
                        </div>
                    </div>
                    
                    <button type="submit" 
                            class="bg-emerald-600 text-white px-8 py-4 rounded-xl hover:bg-emerald-700 transition font-semibold shadow-lg shadow-emerald-600/30">
                        <i class="fas fa-paper-plane mr-2"></i>Publier le Commentaire
                    </button>
                </form>
            </div>
            
            <!-- Comments List -->
            <div class="space-y-6">
                <h2 class="text-3xl font-bold text-gray-900">Avis Clients (<?php echo count($comments); ?>)</h2>
                
                <?php if (empty($comments)): ?>
                    <div class="bg-white rounded-2xl shadow-sm p-12 text-center text-gray-500 border border-gray-100">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-comment-slash text-4xl text-gray-400"></i>
                        </div>
                        <p class="text-lg font-medium">Aucun avis pour le moment. Soyez le premier à donner votre avis !</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-900"><?php echo htmlspecialchars($comment['full_name']); ?></h3>
                                    <div class="flex text-amber-400 mt-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star text-lg <?php echo $i <= $comment['rating'] ? '' : 'text-gray-300'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <span class="text-sm text-gray-500 font-medium bg-gray-50 px-3 py-1.5 rounded-lg">
                                    <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($comment['created_at']))); ?>
                                </span>
                            </div>
                            
                            <!-- SECURE: Output encoding prevents XSS -->
                            <div class="text-gray-700 leading-relaxed">
                                <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Security Features Button -->
            <div class="mt-8">
                <button onclick="toggleCode('securityFeatures')" 
                        class="w-full bg-emerald-500 text-white py-4 px-6 rounded-xl hover:bg-emerald-600 transition font-semibold shadow-lg shadow-emerald-500/30">
                    <i class="fas fa-shield-alt mr-2"></i>Voir l'Implémentation Sécurisée
                </button>
            </div>
            
            <!-- Security Features Display -->
            <div id="securityFeatures" class="mt-6 bg-white rounded-2xl shadow-xl p-8 border border-gray-100" style="display: none;">
                <h3 class="text-2xl font-bold mb-6 text-emerald-600 flex items-center">
                    <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center mr-3">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    Implémentation Sécurisée
                </h3>
                
                <div class="code-block mb-6">
                    <pre><code>// CODE SÉCURISÉ
// 1. Protection CSRF
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Requête invalide");
}

// 2. Validation d'entrée stricte
$comment_text = trim($_POST['comment']);
if (strlen($comment_text) < 10 || strlen($comment_text) > 500) {
    die("Longueur invalide");
}

// 3. Validation de caractères
if (!preg_match('/^[a-zA-Z0-9\s\-.,!?àáâäèéêëìíîïòóôöùúûüçñ]+$/u', $comment_text)) {
    die("Caractères non autorisés");
}

// 4. Requête préparée
$stmt = $conn->prepare("INSERT INTO comments (user_id, product_id, comment, rating) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iisi", $user_id, $product_id, $comment_text, $rating);

// 5. Encodage de sortie (CRITIQUE pour XSS)
echo nl2br(htmlspecialchars($comment['comment']));</code></pre>
                </div>
                
                <div class="space-y-4">
                    <div class="bg-emerald-50 border-l-4 border-emerald-500 p-5 rounded-xl">
                        <h4 class="font-bold text-emerald-900 mb-2 text-lg">1. Protection XSS (Stored)</h4>
                        <p class="text-emerald-700 leading-relaxed">Utilise htmlspecialchars() sur TOUTES les sorties pour convertir les caractères HTML dangereux en entités sûres.</p>
                    </div>
                    
                    <div class="bg-emerald-50 border-l-4 border-emerald-500 p-5 rounded-xl">
                        <h4 class="font-bold text-emerald-900 mb-2 text-lg">2. Validation d'Entrée</h4>
                        <p class="text-emerald-700 leading-relaxed">Validation stricte de longueur (10-500 chars) et de caractères autorisés avec regex Unicode.</p>
                    </div>
                    
                    <div class="bg-emerald-50 border-l-4 border-emerald-500 p-5 rounded-xl">
                        <h4 class="font-bold text-emerald-900 mb-2 text-lg">3. Protection CSRF</h4>
                        <p class="text-emerald-700 leading-relaxed">Token CSRF cryptographiquement sécurisé régénéré après chaque utilisation.</p>
                    </div>
                    
                    <div class="bg-emerald-50 border-l-4 border-emerald-500 p-5 rounded-xl">
                        <h4 class="font-bold text-emerald-900 mb-2 text-lg">4. Requêtes Préparées</h4>
                        <p class="text-emerald-700 leading-relaxed">Prévient l'injection SQL en séparant le code SQL des données utilisateur.</p>
                    </div>
                    
                    <div class="bg-emerald-50 border-l-4 border-emerald-500 p-5 rounded-xl">
                        <h4 class="font-bold text-emerald-900 mb-2 text-lg">5. Anti-Spam</h4>
                        <p class="text-emerald-700 leading-relaxed">Limite un commentaire par utilisateur par produit par heure pour prévenir le spam.</p>
                    </div>
                    
                    <div class="bg-emerald-50 border-l-4 border-emerald-500 p-5 rounded-xl">
                        <h4 class="font-bold text-emerald-900 mb-2 text-lg">6. Validation Côté Client</h4>
                        <p class="text-emerald-700 leading-relaxed">Validation JavaScript en temps réel avec compteur de caractères et vérification de format.</p>
                    </div>
                    
                    <div class="bg-emerald-50 border-l-4 border-emerald-500 p-5 rounded-xl">
                        <h4 class="font-bold text-emerald-900 mb-2 text-lg">7. Limitation de Résultats</h4>
                        <p class="text-emerald-700 leading-relaxed">Limite l'affichage à 50 commentaires pour prévenir les attaques DoS.</p>
                    </div>
                </div>
                
                <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-5 rounded-xl">
                    <h4 class="font-bold text-blue-900 mb-2 text-lg">Principe Clé: Encodage de Sortie</h4>
                    <p class="text-blue-700 leading-relaxed">La règle d'or contre XSS : TOUJOURS encoder les données utilisateur avant affichage avec htmlspecialchars() ou équivalent.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/main.js"></script>
    <script>
        const commentText = document.getElementById('commentText');
        const charCount = document.getElementById('charCount');
        const charStatus = document.getElementById('charStatus');
        
        if (commentText && charCount) {
            commentText.addEventListener('input', function() {
                const length = this.value.length;
                charCount.textContent = length;
                
                if (length < 10) {
                    charCount.className = 'font-semibold text-red-500';
                    charStatus.textContent = 'Trop court';
                    charStatus.className = 'text-xs font-semibold text-red-500';
                } else if (length > 450) {
                    charCount.className = 'font-semibold text-orange-500';
                    charStatus.textContent = 'Attention';
                    charStatus.className = 'text-xs font-semibold text-orange-500';
                } else {
                    charCount.className = 'font-semibold text-emerald-500';
                    charStatus.textContent = '✓ Parfait';
                    charStatus.className = 'text-xs font-semibold text-emerald-500';
                }
            });
        }
        
        function validateComment() {
            const comment = document.getElementById('commentText').value.trim();
            const rating = document.querySelector('input[name="rating"]:checked');
            
            if (!rating) {
                alert('Veuillez sélectionner une note');
                return false;
            }
            
            if (comment.length < 10) {
                alert('Le commentaire doit contenir au moins 10 caractères');
                return false;
            }
            
            if (comment.length > 500) {
                alert('Le commentaire est trop long (max 500 caractères)');
                return false;
            }
            
            // Vérifier les caractères dangereux
            if (!/^[a-zA-Z0-9\s\-.,!?àáâäèéêëìíîïòóôöùúûüçñ]+$/u.test(comment)) {
                alert('Le commentaire contient des caractères non autorisés');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>