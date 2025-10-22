<?php
require_once 'config/database.php';

// --- DEFINA O USUÁRIO A SER CRIADO AQUI ---
$username = 'admin';
$password = 'admin';
// ------------------------------------------

// Criptografa a senha usando o algoritmo mais seguro do PHP
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $hashed_password]);

    echo "Usuário '{$username}' criado com sucesso!";

} catch (PDOException $e) {
    die("Erro ao criar usuário: " . $e->getMessage());
}
?>