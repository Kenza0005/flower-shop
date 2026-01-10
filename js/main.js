// Version toggle functionality removed for realistic pentesting experience
let isSecure = false;

document.addEventListener('DOMContentLoaded', function() {
    // Load featured products
    loadFeaturedProducts();
});

// Load featured products via AJAX
function loadFeaturedProducts() {
    fetch('api/get_products.php?featured=true')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('featuredProducts');
            container.innerHTML = '';
            
            if (data.success && data.products.length > 0) {
                data.products.forEach(product => {
                    container.innerHTML += createProductCard(product);
                });
            } else {
                container.innerHTML = '<p class="col-span-3 text-center text-gray-600">Aucun produit disponible</p>';
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des produits:', error);
            document.getElementById('featuredProducts').innerHTML = 
                '<p class="col-span-3 text-center text-red-600">Erreur lors du chargement des produits</p>';
        });
}

// Generate placeholder image URL based on product
function getPlaceholderImage(product) {
    // Using local flower images (1.jpg to 6.jpg)
    // Each product gets a consistent image based on its ID
    const seed = product.id || Math.floor(Math.random() * 1000);
    
    // Select image number from 1 to 6 based on product ID for consistency
    const imageNumber = (seed % 6) + 1;
    
    return `assets/images/flowers/${imageNumber}.jpg`;
}

// Create product card HTML
function createProductCard(product) {
    const imageUrl = product.image_url 
        ? `assets/images/${product.image_url}` 
        : getPlaceholderImage(product);
    
    return `
        <div class="product-card bg-white rounded-2xl shadow-sm hover:shadow-md transition overflow-hidden border border-gray-100">
            <div class="relative overflow-hidden bg-gray-100">
                <img src="${imageUrl}" 
                     alt="${product.name}" 
                     class="w-full h-64 object-cover hover:scale-105 transition duration-300"
                     onerror="this.src='${getPlaceholderImage(product)}'">
                <div class="absolute top-4 right-4">
                    <span class="bg-emerald-500 text-white px-3 py-1 rounded-full text-sm font-semibold shadow-lg">
                        Nouveau
                    </span>
                </div>
            </div>
            <div class="p-6">
                <h3 class="text-xl font-bold mb-2 text-gray-900">${product.name}</h3>
                <p class="text-gray-600 mb-4 line-clamp-2 leading-relaxed">${product.description}</p>
                <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                    <span class="text-3xl font-bold text-emerald-600">${parseFloat(product.price).toFixed(2)}€</span>
                    <button onclick="addToCart(${product.id})" 
                            class="bg-emerald-600 text-white px-6 py-3 rounded-xl hover:bg-emerald-700 transition font-semibold shadow-sm hover:shadow-md">
                        <i class="fas fa-shopping-cart mr-2"></i>Ajouter
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Fonction ajouter au panier
function addToCart(productId) {
    // Placeholder - implement cart functionality
    showNotification('Produit ajouté au panier avec succès!', 'success');
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-4 rounded-xl shadow-lg z-50 animate-fade-in ${
        type === 'success' ? 'bg-emerald-500' : 
        type === 'error' ? 'bg-rose-500' : 
        'bg-blue-500'
    } text-white font-semibold`;
    
    const icon = type === 'success' ? 'fa-check-circle' : 
                 type === 'error' ? 'fa-exclamation-circle' : 
                 'fa-info-circle';
    
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${icon} mr-3 text-xl"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-10px)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Toggle code visibility
function toggleCode(elementId) {
    const element = document.getElementById(elementId);
    if (element.style.display === 'none' || element.style.display === '') {
        element.style.display = 'block';
    } else {
        element.style.display = 'none';
    }
}