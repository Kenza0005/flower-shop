<?php
require_once '../includes/config.php';

$output = '';
$host = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $host = $_POST['host'];
    
    // VULNERABLE: Direct command injection
    // User can append "; command" or "| command"
    if (PHP_OS_FAMILY === 'Windows') {
        $command = "ping -n 2 " . $host;
    } else {
        $command = "ping -c 2 " . $host;
    }
    
    // VULNERABLE: shell_exec is dangerous without escaping
    $output = shell_exec($command);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outils Réseau - Vulnérable</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/custom.css">
</head>
<body class="bg-gray-50 py-12 px-4">
    
    <!-- Status Badge -->
    <div class="fixed top-6 right-6 z-50">
        <span class="bg-rose-50 text-rose-700 px-5 py-2.5 rounded-full text-sm font-semibold border border-rose-200 shadow-sm">
            <i class="fas fa-terminal mr-1"></i>
            Command Injection Actif
        </span>
    </div>

    <div class="container mx-auto">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-4xl font-bold text-center mb-8 text-gray-900">
                <i class="fas fa-network-wired mr-3 text-rose-600"></i>Outils Réseau (Vulnérable)
            </h1>

            <div class="bg-white rounded-2xl shadow-sm p-8 mb-8 border-t-4 border-rose-500">
                <h2 class="text-2xl font-bold mb-4">Ping d'un hôte</h2>
                <form method="POST" action="" class="space-y-6">
                    <div class="flex gap-4">
                        <input type="text" name="host" value="<?php echo $host; ?>" class="flex-1 px-4 py-3 border rounded-lg focus:ring-2 focus:ring-rose-500 outline-none" placeholder="ex: 8.8.8.8 ; whoami">
                        <button type="submit" class="bg-rose-500 text-white px-8 py-3 rounded-lg font-bold">Exécuter</button>
                    </div>
                    <p class="text-sm text-gray-500 italic">Testez l'injection de commande: 8.8.8.8 ; whoami</p>
                </form>

                <?php if ($output): ?>
                    <div class="mt-8 bg-black text-green-400 p-6 rounded-xl font-mono text-sm overflow-x-auto">
                        <pre><?php echo $output; // VULNERABLE: XSS if output is echoed directly ?></pre>
                    </div>
                <?php endif; ?>
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
