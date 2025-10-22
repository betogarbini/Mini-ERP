<?php
require_once 'check_auth.php';
require_once 'config/database.php';

$perfil_id = $_GET['id'] ?? null;
if (!$perfil_id) { die('ID do perfil não fornecido.'); }

// Busca informações do perfil e verifica se o usuário logado é o dono
$stmt = $pdo->prepare("SELECT * FROM perfis WHERE id = ? AND id_user = ?");
$stmt->execute([$perfil_id, $_SESSION['user_id']]);
$perfil = $stmt->fetch();
if (!$perfil) { die('Acesso negado. Você não é o dono deste perfil.'); }

// Lógica para salvar alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_ids = $_POST['users'] ?? [];
    
    // Apaga todos os acessos antigos (exceto o do dono)
    $stmt_del = $pdo->prepare("DELETE FROM perfil_users WHERE perfil_id = ? AND user_id != ?");
    $stmt_del->execute([$perfil_id, $_SESSION['user_id']]);

    // Insere os novos acessos
    if (!empty($user_ids)) {
        $stmt_add = $pdo->prepare("INSERT INTO perfil_users (perfil_id, user_id) VALUES (?, ?)");
        foreach ($user_ids as $user_id) {
            $stmt_add->execute([$perfil_id, $user_id]);
        }
    }
    $_SESSION['notification'] = ['message' => 'Acessos ao perfil atualizados com sucesso!', 'type' => 'success'];
    header("Location: gerenciar_perfis.php");
    exit();
}

// Busca todos os usuários (exceto o próprio dono) para exibir na lista
$all_users = $pdo->prepare("SELECT id, username FROM users WHERE id != ? ORDER BY username");
$all_users->execute([$_SESSION['user_id']]);

// Busca os IDs dos usuários que já têm acesso
$stmt_current = $pdo->prepare("SELECT user_id FROM perfil_users WHERE perfil_id = ?");
$stmt_current->execute([$perfil_id]);
$current_access_ids = $stmt_current->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
?>

<h2>Gerenciar Membros do Perfil: <?= htmlspecialchars($perfil['nome']) ?></h2>
<p>Selecione os usuários que terão acesso a este perfil.</p>

<form action="gerenciar_acesso_perfil.php?id=<?= $perfil_id ?>" method="POST" class="permissions-form">
    <div class="permission-group">
        <h3>Usuários</h3>
        <?php foreach ($all_users as $user): ?>
            <label>
                <input type="checkbox" name="users[]" value="<?= $user['id'] ?>" <?= in_array($user['id'], $current_access_ids) ? 'checked' : '' ?>>
                <?= htmlspecialchars($user['username']) ?>
            </label>
        <?php endforeach; ?>
    </div>
    <button type="submit">Salvar Acessos</button>
    <a href="gerenciar_perfis.php" class="btn-clear">Voltar</a>
</form>

<?php include 'includes/footer.php'; ?>