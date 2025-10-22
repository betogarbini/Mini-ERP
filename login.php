<?php
session_start(); // Inicia a sessão para checar se o usuário já está logado

// Se já estiver logado, redireciona para o dashboard
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: index.php');
    exit;
}

// Verifica se há uma mensagem de erro vinda da autenticação
$error_message = '';
if (isset($_GET['error']) && $_GET['error'] == 1) {
    $error_message = 'Usuário ou senha inválidos!';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle Financeiro</title>
    <link rel="stylesheet" href="includes/style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f4f9; }
        .login-container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 350px; text-align: center; }
        .login-container h1 { margin-bottom: 1.5rem; }
        .login-container input { width: 100%; padding: 10px; margin-bottom: 1rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .login-container button { width: 100%; }
        .error { color: #c0392b; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Acessar Sistema</h1>
        <?php if ($error_message): ?>
            <p class="error"><?= $error_message ?></p>
        <?php endif; ?>
        <form action="auth.php" method="POST">
            <input type="text" name="username" placeholder="Usuário" required>
            <input type="password" name="password" placeholder="Senha" required>
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>