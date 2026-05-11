DROP DATABASE IF EXISTS sistema_recenseadores;
CREATE DATABASE sistema_recenseadores;
USE sistema_recenseadores;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    -- Login Info
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    
    -- Personal Info
    name VARCHAR(255) NOT NULL,
    gender VARCHAR(20),
    phone VARCHAR(20),
    cpf VARCHAR(14) NOT NULL UNIQUE,
    rg VARCHAR(20),
    birth_date DATE,
    
    -- Address
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(2), -- 'SC'
    cep VARCHAR(10),
    
    -- Education
    education_level VARCHAR(100),
    course_detail VARCHAR(255),
    
    -- Other
    additional_info TEXT,
    
    -- System
    role ENUM('admin', 'recenseador') DEFAULT 'recenseador',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_location VARCHAR(255),
    end_location VARCHAR(255),
    waypoints JSON,
    status ENUM('assigned', 'in_progress', 'completed') DEFAULT 'assigned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create Admin User
INSERT INTO users (name, email, password, cpf, role, status) 
VALUES ('Administrador', 'admin@sistema.com', '$2y$10$YourHashedPasswordHere', '000.000.000-00', 'admin', 'approved');
-- Note: Password hash for 'admin123' should be updated in PHP setup or here. 
-- For simplicity, using a known hash or letting setup.php handle it. 
-- using hash for 'admin123': $2y$10$8WkT.aa/./.
