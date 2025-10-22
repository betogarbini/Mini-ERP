<?php
// Inicia a sessão
session_start();
require_once 'config/database.php';

// Verifica se o método é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Busca o usuário no banco de dados
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Verifica se o usuário existe E se a senha está correta
    if ($user && password_verify($password, $user['password'])) {
        // Sucesso no login!
        
        // Regenera o ID da sessão para segurança
        session_regenerate_id(true);

        // Armazena dados na sessão
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Limpa permissões antigas, força o check_auth a recarregar
        unset($_SESSION['permissions']); 
        
        // Redireciona para o dashboard
        header('Location: selecionar_perfil.php');
        exit();
    } else {
        // Falha no login, redireciona de volta com uma mensagem de erro
        header('Location: login.php?error=1');
        exit();
    }
} else {
    // Se não for POST, redireciona para a página de login
    header('Location: login.php');
    exit();
}
?>