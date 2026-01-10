<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';
$upload_dir = '../uploads/';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['image'])) {
        $file = $_FILES['image'];
        $filename = $file['name'];
        $target_path = $upload_dir . $filename;
        
        // VULNERABLE: No file type verification, no RCE protection
        // This allows uploading .php files directly
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $message = "Fichier téléchargé avec succès dans: " . $target_path;
            
            // VULNERABLE: Direct SQL insertion
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if (!$conn->connect_error) {
                $sql = "INSERT INTO uploads (user_id, original_filename, stored_filename, mime_type, file_size, uploaded_at) 
                        VALUES (" . $_SESSION['user_id'] . ", '$filename', '$filename', '{$file['type']}', {$file['size']}, NOW())";
                $conn->query($sql);
                $conn->close();
            }
        } else {
            $error = "Échec du téléchargement.";
        }
    }
}

// Fetch uploads
$user_uploads = [];
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn->connect_error) {
    // Ensure uploads table exists
    $create_uploads = "CREATE TABLE IF NOT EXISTS uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        stored_filename VARCHAR(255) NOT NULL,
        mime_type VARCHAR(100),
        file_size INT,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES user_profiles(id)
    )";
    $conn->query($create_uploads);

    $sql = "SELECT original_filename, stored_filename, uploaded_at FROM uploads WHERE user_id = " . $_SESSION['user_id'] . " ORDER BY uploaded_at DESC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $user_uploads[] = $row;
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
    <title>Upload - Vulnérable</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body class="bg-gray-50 py-12 px-4">
    
    <!-- Status Badge -->
    <div class="fixed top-6 right-6 z-50">
        <span class="bg-rose-50 text-rose-700 px-5 py-2.5 rounded-full text-sm font-semibold border border-rose-200 shadow-sm">
            <i class="fas fa-biohazard mr-1"></i>
            RCE Vulnérable
        </span>
    </div>

    <div class="container mx-auto">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-4xl font-bold text-center mb-8 text-gray-900">
                <i class="fas fa-upload mr-3 text-rose-600"></i>Upload de Fichiers (Vulnérable)
            </h1>

            <div class="bg-white rounded-2xl shadow-sm p-8 mb-8 border-t-4 border-rose-500">
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

                <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                    <div class="p-8 border-2 border-dashed border-gray-200 rounded-xl text-center">
                        <input type="file" name="image" id="fileInput" class="hidden">
                        <label for="fileInput" class="cursor-pointer">
                            <i class="fas fa-file-code text-5xl text-rose-300 mb-4 block"></i>
                            <span class="bg-rose-500 text-white px-6 py-3 rounded-lg font-bold">Choisir n'importe quel fichier</span>
                        </label>
                        <p class="mt-4 text-gray-500 text-sm">Attention: L'upload de .php est possible et dangereux!</p>
                    </div>
                    <button type="submit" class="w-full bg-rose-600 text-white py-4 rounded-xl font-bold shadow-lg shadow-rose-600/30">
                        Uploader (Sans Vérification)
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="text-2xl font-bold mb-4">Fichiers uploadés</h2>
                <div class="space-y-3">
                    <?php foreach ($user_uploads as $upload): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="font-medium"><?php echo $upload['original_filename']; ?></span>
                            <a href="../uploads/<?php echo $upload['stored_filename']; ?>" target="_blank" class="text-rose-500 underline">Voir/Exécuter</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mt-8 text-center">
                <a href="dashboard.php" class="text-rose-600 hover:text-rose-700">
                    <i class="fas fa-arrow-left mr-1"></i>Retour au dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
