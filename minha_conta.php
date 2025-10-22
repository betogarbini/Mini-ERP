<?php
require_once 'check_auth.php';
require_once 'config/database.php';

// Busca o nome de usuário atual para exibir no formulário
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Lógica para exibir mensagens de notificação
$notification_message = '';
$notification_type = '';

$status = $_GET['status'] ?? '';
$error = $_GET['error'] ?? '';

if ($status === 'sucesso') {
    $notification_message = 'Dados da conta atualizados com sucesso!';
    $notification_type = 'success';
} elseif ($error === 'senha_incorreta') {
    $notification_message = 'Erro: A senha atual informada está incorreta.';
    $notification_type = 'error';
} elseif ($error === 'senhas_nao_coincidem') {
    $notification_message = 'Erro: A nova senha e a confirmação não coincidem.';
    $notification_type = 'error';
} elseif ($error === 'usuario_existe') {
    $notification_message = 'Erro: Este nome de usuário já está em uso por outra conta.';
    $notification_type = 'error';
}

include 'includes/header.php';

if ($notification_message) {
    echo "<div class='notification is-{$notification_type}'>{$notification_message}</div>";
}
?>

<h2>Minha Conta</h2>
<form action="atualizar_conta.php" method="POST" class="account-form">
    <div class="form-section">
        <h3>Alterar Nome de Usuário</h3>
        <div class="form-group">
            <label for="username">Novo Nome de Usuário (deixe em branco para não alterar)</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>">
        </div>
    </div>
    
    <div class="form-section">
        <h3>Alterar Senha</h3>
        <div class="form-group">
            <label for="new_password">Nova Senha (deixe em branco para não alterar)</label>
            <input type="password" id="new_password" name="new_password">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirmar Nova Senha</label>
            <input type="password" id="confirm_password" name="confirm_password">
        </div>
    </div>

    <div class="form-section password-confirmation">
        <h3>Confirmação Obrigatória</h3>
        <div class="form-group">
            <label for="current_password">Digite sua Senha ATUAL para confirmar as alterações</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>
    </div>

    <button type="submit">Salvar Alterações</button>
</form>

<?php include 'includes/footer.php'; ?>