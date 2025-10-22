<?php
session_start();
require_once 'check_auth.php';
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Pegar os dados do formulário
    $current_password = $_POST['current_password'];
    $new_username = trim($_POST['username']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['user_id'];

    // 2. VERIFICAÇÃO DE SEGURANÇA: Validar a senha atual
    $stmt = $pdo->prepare("SELECT username, password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current_password, $user['password'])) {
        header('Location: minha_conta.php?error=senha_incorreta');
        exit();
    }

    // 3. Validações e preparação para o UPDATE
    $update_fields = [];
    $params = [];

    // Se uma nova senha foi fornecida, valida e prepara
    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            header('Location: minha_conta.php?error=senhas_nao_coincidem');
            exit();
        }
        $update_fields[] = "password = ?";
        $params[] = password_hash($new_password, PASSWORD_DEFAULT);
    }

    // Se um novo nome de usuário foi fornecido e é diferente do atual
    if (!empty($new_username) && $new_username !== $user['username']) {
        // Verifica se o novo nome de usuário já existe
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_check->execute([$new_username]);
        if ($stmt_check->fetch()) {
            header('Location: minha_conta.php?error=usuario_existe');
            exit();
        }
        $update_fields[] = "username = ?";
        $params[] = $new_username;
    }

    // 4. Executa o UPDATE apenas se houver algo para alterar
    if (!empty($update_fields)) {
        try {
            $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $params[] = $user_id;
            
            $stmt_update = $pdo->prepare($sql);
            $stmt_update->execute($params);

        } catch (PDOException $e) {
            die("Erro ao atualizar a conta: " . $e->getMessage());
        }
    }

    // 5. Redireciona com mensagem de sucesso
    header('Location: minha_conta.php?status=sucesso');
    exit();

} else {
    header('Location: minha_conta.php');
    exit();
}
?>