<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Échec de connexion à la base de données']);
    exit();
}

// Get parameters
$featured = isset($_GET['featured']) ? true : false;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 6;

// Build query
$sql = "SELECT id, name, description, price, stock, category, image_url FROM products WHERE stock > 0";

if ($featured) {
    $sql .= " ORDER BY RAND() LIMIT $limit";
} else {
    $sql .= " ORDER BY name ASC";
}

$result = $conn->query($sql);

$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'products' => $products
]);

$conn->close();
?>