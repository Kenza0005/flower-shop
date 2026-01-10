-- ENT Flower Shop Database Schema
-- Base de données pour les données applicatives (séparée de LDAP)

CREATE DATABASE ent_flowershop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ent_flowershop;

-- Table des profils utilisateurs (synchronisée avec LDAP)
CREATE TABLE user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100),
    email VARCHAR(100),
    role ENUM('admin', 'teacher', 'student', 'staff') DEFAULT 'student',
    department VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    preferences JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT 1,
    INDEX idx_username (username),
    INDEX idx_role (role)
);

-- Table des produits (fleurs)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    category VARCHAR(50),
    image_url VARCHAR(255),
    is_available BOOLEAN DEFAULT 1,
    created_by VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES user_profiles(username) ON DELETE SET NULL
);

-- Table des commandes
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    customer_username VARCHAR(50) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending',
    delivery_address TEXT,
    delivery_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_username) REFERENCES user_profiles(username) ON DELETE CASCADE,
    INDEX idx_customer (customer_username),
    INDEX idx_status (status),
    INDEX idx_order_number (order_number)
);

-- Table des articles de commande
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Table des commentaires/avis
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    reviewer_username VARCHAR(50) NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    is_approved BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_username) REFERENCES user_profiles(username) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_reviewer (reviewer_username)
);

-- Table des uploads de fichiers
CREATE TABLE file_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uploader_username VARCHAR(50) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100),
    file_size INT,
    upload_type ENUM('product_image', 'profile_picture', 'document') DEFAULT 'document',
    is_public BOOLEAN DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploader_username) REFERENCES user_profiles(username) ON DELETE CASCADE,
    INDEX idx_uploader (uploader_username),
    INDEX idx_type (upload_type)
);

-- Table des logs d'activité
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50),
    target_id INT,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (username) REFERENCES user_profiles(username) ON DELETE SET NULL,
    INDEX idx_username (username),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Insertion des données de test

-- Profils utilisateurs (seront synchronisés avec LDAP)
INSERT INTO user_profiles (username, full_name, email, role, department) VALUES
('admin', 'Administrateur Système', 'admin@ecole-jardins.fr', 'admin', 'Administration'),
('prof.martin', 'Jean Martin', 'j.martin@ecole-jardins.fr', 'teacher', 'Sciences Naturelles'),
('prof.dubois', 'Marie Dubois', 'm.dubois@ecole-jardins.fr', 'teacher', 'Arts Plastiques'),
('etudiant.alice', 'Alice Moreau', 'alice.moreau@eleve.ecole-jardins.fr', 'student', 'Terminale S'),
('etudiant.bob', 'Bob Leroy', 'bob.leroy@eleve.ecole-jardins.fr', 'student', 'Première ES'),
('secretaire', 'Sophie Secrétaire', 'secretaire@ecole-jardins.fr', 'staff', 'Secrétariat');

-- Produits (fleurs pour l'école)
INSERT INTO products (name, description, price, stock, category, image_url, created_by) VALUES
('Bouquet de Roses Rouges', 'Magnifique bouquet de 12 roses rouges, parfait pour la fête des mères', 45.99, 25, 'roses', 'roses-rouges.jpg', 'admin'),
('Composition Tournesols', 'Arrangement joyeux de tournesols pour égayer les salles de classe', 35.99, 15, 'tournesols', 'tournesols.jpg', 'admin'),
('Tulipes Multicolores', 'Mélange coloré de tulipes fraîches, idéal pour les événements scolaires', 29.99, 30, 'tulipes', 'tulipes.jpg', 'admin'),
('Orchidée Élégante', 'Orchidée en pot, parfaite pour décorer le bureau du directeur', 65.99, 8, 'orchidees', 'orchidee.jpg', 'admin'),
('Lys Blancs', 'Bouquet de lys blancs symbolisant la pureté, pour les cérémonies', 39.99, 20, 'lys', 'lys-blancs.jpg', 'admin'),
('Œillets Roses', 'Petits œillets roses, économiques pour les projets étudiants', 19.99, 40, 'oeillets', 'oeillets-roses.jpg', 'admin');

-- Commandes d'exemple
INSERT INTO orders (order_number, customer_username, total_price, status, delivery_address, notes) VALUES
('CMD001', 'prof.martin', 45.99, 'delivered', 'Salle 201 - Sciences Naturelles', 'Pour décorer la salle pendant la semaine de la science'),
('CMD002', 'etudiant.alice', 29.99, 'confirmed', 'Foyer des élèves', 'Commande groupée de la classe'),
('CMD003', 'secretaire', 65.99, 'pending', 'Bureau de direction', 'Pour l\'accueil des parents d\'élèves');

-- Articles des commandes
INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES
(1, 1, 1, 45.99, 45.99),
(2, 3, 1, 29.99, 29.99),
(3, 4, 1, 65.99, 65.99);

-- Avis/commentaires
INSERT INTO reviews (product_id, reviewer_username, rating, comment, is_approved) VALUES
(1, 'prof.martin', 5, 'Magnifiques roses, les élèves ont adoré !', 1),
(3, 'etudiant.alice', 4, 'Très jolies tulipes, parfaites pour notre projet', 1),
(1, 'secretaire', 5, 'Excellent service, livraison rapide', 1);

-- Configuration LDAP de test (pour documentation)
-- Ces utilisateurs devraient exister dans votre annuaire LDAP :
/*
LDAP Structure suggérée:
dc=school,dc=local
├── ou=users
│   ├── uid=admin (employeeType=admin)
│   ├── uid=prof.martin (employeeType=teacher, ou=Sciences Naturelles)
│   ├── uid=prof.dubois (employeeType=teacher, ou=Arts Plastiques)
│   ├── uid=etudiant.alice (employeeType=student, ou=Terminale S)
│   ├── uid=etudiant.bob (employeeType=student, ou=Première ES)
│   └── uid=secretaire (employeeType=staff, ou=Secrétariat)
└── ou=groups
    ├── cn=admins
    ├── cn=teachers
    ├── cn=students
    └── cn=staff
*/