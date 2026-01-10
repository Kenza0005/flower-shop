<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// SECURE: Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';
$uploaded_file = '';

// SECURE: Define allowed configurations
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_file_size = 2 * 1024 * 1024; // 2MB
$upload_dir = '../uploads/';

// SECURE: Ensure upload directory exists and is writable
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// SECURE: Create .htaccess to prevent PHP execution in uploads
$htaccess_content = "php_flag engine off\nOptions -Indexes\nAddType text/plain .php .php3 .php4 .php5 .phtml";
if (!file_exists($upload_dir . '.htaccess')) {
    file_put_contents($upload_dir . '.htaccess', $htaccess_content);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // SECURE: Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Requête invalide. Veuillez réessayer.";
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error = "Veuillez sélectionner un fichier valide.";
    } else {
        $file = $_FILES['image'];
        
        // SECURE: Check file size
        if ($file['size'] > $max_file_size) {
            $error = "Fichier trop volumineux. Taille maximale : 2MB.";
        } elseif ($file['size'] == 0) {
            $error = "Le fichier est vide.";
        } else {
            // SECURE: Validate MIME type using file content (finfo)
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($file['tmp_name']);
            
            if (!in_array($mime_type, $allowed_mime_types)) {
                $error = "Type de fichier invalide. Seules les images JPG, PNG et GIF sont autorisées.";
            } else {
                // SECURE: Validate extension
                $original_name = $file['name'];
                $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                
                if (!in_array($file_extension, $allowed_extensions)) {
                    $error = "Extension de fichier invalide. Seules les extensions .jpg, .jpeg, .png, .gif sont autorisées.";
                } else {
                    // SECURE: Additional image validation using getimagesize
                    $image_info = getimagesize($file['tmp_name']);
                    if ($image_info === false) {
                        $error = "Le fichier n'est pas une image valide.";
                    } else {
                        // SECURE: Validate image dimensions (optional)
                        list($width, $height) = $image_info;
                        if ($width > 4000 || $height > 4000) {
                            $error = "Dimensions d'image trop importantes. Maximum 4000x4000 pixels.";
                        } elseif ($width < 50 || $height < 50) {
                            $error = "Image trop petite. Minimum 50x50 pixels.";
                        } else {
                            // SECURE: Generate cryptographically secure random filename
                            $new_filename = bin2hex(random_bytes(16)) . '.' . $file_extension;
                            $target_path = $upload_dir . $new_filename;
                            
                            // SECURE: Re-encode image to strip metadata and potential exploits
                            $clean_image = null;
                            switch ($mime_type) {
                                case 'image/jpeg':
                                    $clean_image = imagecreatefromjpeg($file['tmp_name']);
                                    if ($clean_image) {
                                        imagejpeg($clean_image, $target_path, 90);
                                    }
                                    break;
                                case 'image/png':
                                    $clean_image = imagecreatefrompng($file['tmp_name']);
                                    if ($clean_image) {
                                        imagepng($clean_image, $target_path, 9);
                                    }
                                    break;
                                case 'image/gif':
                                    $clean_image = imagecreatefromgif($file['tmp_name']);
                                    if ($clean_image) {
                                        imagegif($clean_image, $target_path);
                                    }
                                    break;
                            }
                            
                            if ($clean_image) {
                                imagedestroy($clean_image);
                                
                                // SECURE: Store in database with prepared statement
                                $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                                if (!$conn->connect_error) {
                                    // Create uploads table if it doesn't exist
                                    $create_table = "CREATE TABLE IF NOT EXISTS uploads (
                                        id INT AUTO_INCREMENT PRIMARY KEY,
                                        user_id INT NOT NULL,
                                        original_filename VARCHAR(255) NOT NULL,
                                        stored_filename VARCHAR(255) NOT NULL,
                                        mime_type VARCHAR(100) NOT NULL,
                                        file_size INT NOT NULL,
                                        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (user_id) REFERENCES user_profiles(id)
                                    )";
                                    $conn->query($create_table);
                                    
                                    $stmt = $conn->prepare("INSERT INTO uploads (user_id, original_filename, stored_filename, mime_type, file_size, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
                                    $stmt->bind_param("isssi", $_SESSION['user_id'], $original_name, $new_filename, $mime_type, $file['size']);
                                    
                                    if ($stmt->execute()) {
                                        $message = "Fichier téléchargé avec succès !";
                                        $uploaded_file = $new_filename;
                                        
                                        // Regenerate CSRF token after successful upload
                                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                                    } else {
                                        $error = "Erreur de base de données. Veuillez réessayer.";
                                        error_log("Upload DB error: " . $stmt->error);
                                        unlink($target_path); // Remove uploaded file
                                    }
                                    
                                    $stmt->close();
                                    $conn->close();
                                } else {
                                    $error = "Service temporairement indisponible.";
                                    error_log("DB connection failed: " . $conn->connect_error);
                                    unlink($target_path);
                                }
                            } else {
                                $error = "Échec du traitement de l'image. Le fichier peut être corrompu.";
                            }
                        }
                    }
                }
            }
        }
    }
}

// SECURE: Fetch user's uploaded files
$user_uploads = [];
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn->connect_error) {
    // Ensure uploads table exists
    $create_uploads = "CREATE TABLE IF NOT EXISTS uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        stored_filename VARCHAR(255) NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        file_size INT NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES user_profiles(id)
    )";
    $conn->query($create_uploads);

    $stmt = $conn->prepare("SELECT original_filename, stored_filename, uploaded_at FROM uploads WHERE user_id = ? ORDER BY uploaded_at DESC LIMIT 10");
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $user_uploads[] = $row;
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload d'Image Produit - Sécurisé</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body class="bg-gray-50">
    
    <!-- Security Badge -->
    <div class="fixed top-4 right-4 z-50">
        <span class="bg-green-100 text-green-800 px-4 py-2 rounded-full text-sm font-semibold">
            <i class="fas fa-shield-alt mr-1"></i>
            Upload Sécurisé
        </span>
    </div>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-4xl font-bold text-center mb-8 text-gray-800">
                <i class="fas fa-upload mr-3"></i>Upload d'Image Produit
            </h1>
            
            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <?php if ($uploaded_file): ?>
                        <br>
                        <a href="../uploads/<?php echo htmlspecialchars($uploaded_file); ?>" target="_blank" class="underline">
                            Voir le fichier téléchargé
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Upload Form -->
            <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
                <form method="POST" action="" enctype="multipart/form-data" onsubmit="return validateUpload()">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 font-bold mb-2">Sélectionner une Image</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-green-500 transition">
                            <i class="fas fa-cloud-upload-alt text-6xl text-gray-400 mb-4"></i>
                            <input type="file" 
                                   name="image" 
                                   id="fileInput"
                                   accept=".jpg,.jpeg,.png,.gif"
                                   class="hidden"
                                   required>
                            <label for="fileInput" class="cursor-pointer">
                                <span class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 transition inline-block">
                                    Choisir un Fichier
                                </span>
                            </label>
                            <p class="text-gray-500 mt-4" id="fileName">Aucun fichier sélectionné</p>
                            <p class="text-xs text-gray-400 mt-2">Max 2MB • JPG, PNG, GIF uniquement • Min 50x50px</p>
                        </div>
                    </div>
                    
                    <div id="imagePreview" class="mb-6 hidden">
                        <label class="block text-gray-700 font-bold mb-2">Aperçu</label>
                        <img id="preview" src="" alt="Aperçu" class="max-w-full h-64 object-contain border rounded-lg mx-auto">
                        <div id="imageInfo" class="text-sm text-gray-600 mt-2 text-center"></div>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-green-500 text-white font-bold py-3 px-4 rounded-lg hover:bg-green-600 transition">
                        <i class="fas fa-upload mr-2"></i>Upload Sécurisé
                    </button>
                </form>
            </div>
            
            <!-- User's Uploads -->
            <?php if (!empty($user_uploads)): ?>
                <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                    <h2 class="text-2xl font-bold mb-4">Vos Fichiers Téléchargés</h2>
                    <div class="space-y-3">
                        <?php foreach ($user_uploads as $upload): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-image text-green-500 text-xl"></i>
                                    <div>
                                        <p class="font-semibold"><?php echo htmlspecialchars($upload['original_filename']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($upload['uploaded_at']))); ?></p>
                                    </div>
                                </div>
                                <a href="../uploads/<?php echo htmlspecialchars($upload['stored_filename']); ?>" 
                                   target="_blank"
                                   class="text-green-500 hover:text-green-600">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Navigation -->
            <div class="text-center">
                <a href="../index.php" class="text-green-600 hover:text-green-700">
                    <i class="fas fa-arrow-left mr-1"></i>Retour à l'accueil
                </a>
            </div>
            
            <!-- Security Features Button -->
            <div class="mt-8">
                <button onclick="toggleCode('securityFeatures')" 
                        class="w-full bg-green-500 text-white py-3 px-4 rounded-lg hover:bg-green-600 transition font-semibold">
                    <i class="fas fa-shield-alt mr-2"></i>Voir l'Implémentation Sécurisée
                </button>
            </div>
            
            <!-- Security Features Display -->
            <div id="securityFeatures" class="mt-4 bg-white rounded-lg shadow-lg p-6" style="display: none;">
                <h3 class="text-xl font-bold mb-4 text-green-600">Implémentation Sécurisée</h3>
                
                <div class="code-block mb-4">
                    <pre><code>// CODE UPLOAD SÉCURISÉ
// 1. Protection CSRF
$csrf_token = $_SESSION['csrf_token'];

// 2. Validation Taille Fichier
if ($file['size'] > 2 * 1024 * 1024) {
    die("Fichier trop volumineux");
}

// 3. Validation Type MIME (contenu fichier)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

// 4. Liste Blanche Extensions
$allowed = ['jpg', 'jpeg', 'png', 'gif'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// 5. Validation Image
$image_info = getimagesize($file['tmp_name']);

// 6. Nom de Fichier Aléatoire
$new_name = bin2hex(random_bytes(16)) . '.' . $ext;

// 7. Re-encodage Image (supprime métadonnées & exploits)
$clean = imagecreatefromjpeg($file['tmp_name']);
imagejpeg($clean, $target_path, 90);

// 8. Empêcher Exécution PHP dans uploads/
file_put_contents('uploads/.htaccess', 'php_flag engine off');</code></pre>
                </div>
                
                <div class="space-y-4">
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">1. Protection CSRF</h4>
                        <p class="text-green-700 text-sm">Token cryptographiquement sécurisé empêche les uploads non autorisés depuis des sites externes.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">2. Limites de Taille</h4>
                        <p class="text-green-700 text-sm">Restreint les uploads à 2MB pour prévenir les attaques DoS par épuisement d'espace disque.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">3. Validation Type MIME</h4>
                        <p class="text-green-700 text-sm">Utilise finfo pour vérifier le contenu réel du fichier, pas seulement l'extension. Prévient les attaques double extension.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">4. Liste Blanche Extensions</h4>
                        <p class="text-green-700 text-sm">Autorise uniquement .jpg, .jpeg, .png, .gif. Bloque .php, .exe, .sh et autres fichiers dangereux.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">5. Vérification Contenu Image</h4>
                        <p class="text-green-700 text-sm">getimagesize() vérifie que le fichier est réellement une image valide, pas un fichier PHP renommé.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">6. Noms de Fichiers Aléatoires</h4>
                        <p class="text-green-700 text-sm">Génère des noms cryptographiquement aléatoires pour prévenir les attaques basées sur les noms de fichiers.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">7. Re-encodage d'Image</h4>
                        <p class="text-green-700 text-sm">Recrée l'image à partir de zéro, supprimant le code PHP embarqué et les métadonnées malveillantes.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">8. Protection .htaccess</h4>
                        <p class="text-green-700 text-sm">Désactive l'exécution PHP dans le répertoire uploads. Même si un .php est uploadé, il ne s'exécutera pas.</p>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <h4 class="font-bold text-green-800 mb-2">9. Requêtes Préparées</h4>
                        <p class="text-green-700 text-sm">Les opérations de base de données utilisent des requêtes paramétrées pour prévenir l'injection SQL.</p>
                    </div>
                </div>
                
                <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4">
                    <h4 class="font-bold text-blue-800 mb-2">Défense en Profondeur</h4>
                    <p class="text-blue-700 text-sm">Plusieurs couches de sécurité garantissent que même si une vérification est contournée, d'autres protègent encore le système.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/main.js"></script>
    <script>
        const fileInput = document.getElementById('fileInput');
        const fileName = document.getElementById('fileName');
        const imagePreview = document.getElementById('imagePreview');
        const preview = document.getElementById('preview');
        const imageInfo = document.getElementById('imageInfo');
        
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                fileName.textContent = file.name;
                
                // Valider taille fichier (côté client)
                if (file.size > 2 * 1024 * 1024) {
                    alert('Fichier trop volumineux ! Taille maximale : 2MB.');
                    this.value = '';
                    fileName.textContent = 'Aucun fichier sélectionné';
                    imagePreview.classList.add('hidden');
                    return;
                }
                
                // Valider type fichier (côté client)
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Type de fichier invalide ! Seuls JPG, PNG et GIF sont autorisés.');
                    this.value = '';
                    fileName.textContent = 'Aucun fichier sélectionné';
                    imagePreview.classList.add('hidden');
                    return;
                }
                
                // Afficher aperçu
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    imagePreview.classList.remove('hidden');
                    
                    // Afficher infos fichier
                    const sizeKB = Math.round(file.size / 1024);
                    imageInfo.textContent = `Taille: ${sizeKB} KB • Type: ${file.type}`;
                    
                    // Vérifier dimensions (approximatif côté client)
                    preview.onload = function() {
                        if (this.naturalWidth < 50 || this.naturalHeight < 50) {
                            alert('Image trop petite ! Minimum 50x50 pixels.');
                            fileInput.value = '';
                            fileName.textContent = 'Aucun fichier sélectionné';
                            imagePreview.classList.add('hidden');
                        } else {
                            imageInfo.textContent += ` • Dimensions: ${this.naturalWidth}x${this.naturalHeight}px`;
                        }
                    };
                };
                reader.readAsDataURL(file);
            } else {
                fileName.textContent = 'Aucun fichier sélectionné';
                imagePreview.classList.add('hidden');
            }
        });
        
        function validateUpload() {
            const file = fileInput.files[0];
            
            if (!file) {
                alert('Veuillez sélectionner un fichier');
                return false;
            }
            
            // Double vérification taille fichier
            if (file.size > 2 * 1024 * 1024) {
                alert('Fichier trop volumineux ! Taille maximale : 2MB.');
                return false;
            }
            
            // Double vérification type fichier
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Type de fichier invalide ! Seuls JPG, PNG et GIF sont autorisés.');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>