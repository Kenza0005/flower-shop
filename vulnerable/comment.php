<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$product_id = isset($_GET['product']) ? $_GET['product'] : 1; // VULNERABLE: No intval
$comments = [];
$message = '';
$error = '';

// VULNERABLE: No CSRF protection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // VULNERABLE: No sanitization
    $comment_text = $_POST['comment'];
    $rating = $_POST['rating'];
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if (!$conn->connect_error) {
        // VULNERABLE: Direct SQL injection
        $sql = "INSERT INTO comments (user_id, product_id, comment, rating, created_at) 
                VALUES (" . $_SESSION['user_id'] . ", $product_id, '$comment_text', '$rating', NOW())";
        
        if ($conn->query($sql)) {
            $message = "Commentaire publié !";
        } else {
            $error = "Échec: " . $conn->error; // VULNERABLE: Info disclosure
        }
        $conn->close();
    }
}

// Fetch comments
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

    // VULNERABLE: Direct SQL injection via GET param
    $sql = "SELECT c.comment, c.rating, c.created_at, u.full_name 
            FROM comments c 
            JOIN user_profiles u ON c.user_id = u.id 
            WHERE c.product_id = $product_id 
            ORDER BY c.created_at DESC";
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avis Clients - Vulnérable</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body class="bg-gray-50 min-h-screen py-12 px-4">
    
    <!-- Status Badge -->
    <div class="fixed top-6 right-6 z-50">
        <span class="bg-rose-50 text-rose-700 px-5 py-2.5 rounded-full text-sm font-semibold border border-rose-200 shadow-sm">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            XSS & SQLi Actif
        </span>
    </div>

    <div class="container mx-auto">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <a href="dashboard.php" class="text-rose-600 hover:text-rose-700 font-semibold inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Retour au dashboard
                </a>
                <h1 class="text-5xl font-bold text-gray-900">
                    <i class="fas fa-comments mr-3 text-rose-600"></i>Avis Clients (Vulnérable)
                </h1>
            </div>

            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-2xl shadow-sm p-8 mb-8 border border-gray-100 border-t-4 border-rose-500">
                <h2 class="text-2xl font-bold mb-6 text-gray-900">Laisser un Avis</h2>
                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label class="block text-gray-900 font-semibold mb-3">Note (1-5)</label>
                        <input type="number" name="rating" min="1" max="5" value="5" class="w-20 px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-gray-900 font-semibold mb-3">Votre Commentaire</label>
                        <textarea name="comment" rows="5" class="w-full px-5 py-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500 outline-none" placeholder="Vous pouvez tester le XSS ici: <script>alert(1)</script>"></textarea>
                    </div>
                    <button type="submit" class="bg-rose-500 text-white px-8 py-4 rounded-xl hover:bg-rose-600 transition font-bold shadow-lg shadow-rose-500/30">
                        Publier (Sans Protection)
                    </button>
                </form>
            </div>

            <div class="space-y-6">
                <h2 class="text-3xl font-bold text-gray-900">Tous les avis</h2>
                <?php foreach ($comments as $comment): ?>
                    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-bold text-lg text-rose-900"><?php echo $comment['full_name']; ?></h3>
                            <span class="text-sm text-gray-500 bg-gray-50 px-3 py-1.5 rounded-lg">
                                <?php echo $comment['created_at']; ?>
                            </span>
                        </div>
                        <div class="flex text-amber-400 mb-4">
                            <?php for($i=0; $i<$comment['rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                        </div>
                        <!-- VULNERABLE: No htmlspecialchars allows Stored XSS -->
                        <div class="text-gray-700 leading-relaxed">
                            <?php echo $comment['comment']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
