<?php
require_once 'check_auth.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Pega os dados do usuário logado
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'usuário desconhecido';

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$perfil_id = $_GET['id'] ?? $_POST['id'] ?? null;
$nome_perfil = trim($_POST['nome'] ?? '');
$editing_perfil = null;

// Lógica de Ações (Adicionar, Atualizar, Deletar)
try {
    if ($action === 'add' && !empty($nome_perfil)) {
        $stmt = $pdo->prepare("INSERT INTO perfis (nome, id_user) VALUES (?, ?)");
        $stmt->execute([$nome_perfil, $user_id]);
        $last_id = $pdo->lastInsertId();

        // Adiciona o criador (dono) como o primeiro membro do perfil
        $stmt_access = $pdo->prepare("INSERT INTO perfil_users (perfil_id, user_id) VALUES (?, ?)");
        $stmt_access->execute([$last_id, $user_id]);
        
        log_activity($pdo, $user_id, $username, 'Criação', 'Perfil', $last_id, "Criou o perfil '{$nome_perfil}'");
        $_SESSION['notification'] = ['message' => 'Perfil criado com sucesso!', 'type' => 'success'];
        header("Location: gerenciar_perfis.php");
        exit();

    } elseif ($action === 'update' && !empty($nome_perfil) && $perfil_id) { 
        $stmt = $pdo->prepare("UPDATE perfis SET nome = ? WHERE id = ? AND id_user = ?");
        $stmt->execute([$nome_perfil, $perfil_id, $user_id]);
        
        log_activity($pdo, $user_id, $username, 'Alteração', 'Perfil', $perfil_id, "Renomeou um perfil para '{$nome_perfil}'");
        $_SESSION['notification'] = ['message' => 'Perfil atualizado com sucesso!', 'type' => 'success'];
        header("Location: gerenciar_perfis.php");
        exit();

    } elseif ($action === 'delete' && $perfil_id) {
        $stmt_count = $pdo->prepare("SELECT COUNT(id) FROM perfis WHERE id_user = ?");
        $stmt_count->execute([$user_id]);
        if ($stmt_count->fetchColumn() <= 1) {
            $_SESSION['notification'] = ['message' => 'Erro: Você não pode excluir seu último perfil.', 'type' => 'error'];
            header("Location: gerenciar_perfis.php");
            exit();
        }

        $stmt_old = $pdo->prepare("SELECT nome FROM perfis WHERE id = ? AND id_user = ?");
        $stmt_old->execute([$perfil_id, $user_id]);
        $old_name = $stmt_old->fetchColumn();
        
        if ($old_name) {
            $stmt = $pdo->prepare("DELETE FROM perfis WHERE id = ? AND id_user = ?");
            $stmt->execute([$perfil_id, $user_id]);
            log_activity($pdo, $user_id, $username, 'Exclusão', 'Perfil', $perfil_id, "Excluiu o perfil '{$old_name}'");
            $_SESSION['notification'] = ['message' => 'Perfil excluído com sucesso! Todos os dados associados foram removidos.', 'type' => 'success'];
        }
        header("Location: gerenciar_perfis.php");
        exit();
    }
} catch (PDOException $e) {
    die("Erro na operação de perfil: " . $e->getMessage());
}

// Busca dados para os formulários e listagem
if ($action === 'edit' && $perfil_id) {
    $stmt = $pdo->prepare("SELECT * FROM perfis WHERE id = ? AND id_user = ?");
    $stmt->execute([$perfil_id, $user_id]);
    $editing_perfil = $stmt->fetch(PDO::FETCH_ASSOC);
}
$stmt_perfis = $pdo->prepare("
    SELECT p.* 
    FROM perfis p
    JOIN perfil_users pu ON p.id = pu.perfil_id
    WHERE pu.user_id = ?
    ORDER BY p.nome
");
$stmt_perfis->execute([$user_id]);
$perfis = $stmt_perfis->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';

// Bloco de Notificações
if (isset($_SESSION['notification'])) {
    echo "<div class='notification is-{$_SESSION['notification']['type']}'>{$_SESSION['notification']['message']}</div>";
    unset($_SESSION['notification']);
}
?>

<h2>Gerenciar Meus Perfis</h2>
<p>Crie, renomeie ou exclua seus perfis de gerenciamento (ex: Pessoal, Empresarial).</p>

<div class="form-section">
    <form action="gerenciar_perfis.php" method="POST">
        <?php if ($editing_perfil): ?>
            <h3>Editando Perfil</h3>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $editing_perfil['id'] ?>">
        <?php else: ?>
            <h3>Criar Novo Perfil</h3>
            <input type="hidden" name="action" value="add">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="nome">Nome do Perfil:</label>
            <input type="text" name="nome" placeholder="Ex: Finanças Pessoais" value="<?= htmlspecialchars($editing_perfil['nome'] ?? '') ?>" required>
        </div>
        
        <button type="submit"><?= $editing_perfil ? 'Atualizar Perfil' : 'Criar Perfil' ?></button>
        <?php if ($editing_perfil): ?>
            <a href="gerenciar_perfis.php" class="btn-clear">Cancelar Edição</a>
        <?php endif; ?>
    </form>
</div>

<hr>

<h3>Meus Perfis Cadastrados</h3>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Nome do Perfil</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($perfis as $perfil): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($perfil['nome']) ?>
                    
                    <?php if ($perfil['id_user'] != $user_id): ?>
                        <span class="admin-badge shared-badge">Compartilhado</span>
                    <?php endif; ?>
                </td>
                <td class="actions-cell">
                    <!-- Mostra ações apenas se o usuário for o dono do perfil -->
                    <?php if ($perfil['id_user'] == $user_id): ?>
                        <a href="gerenciar_acesso_perfil.php?id=<?= $perfil['id'] ?>" title="Gerenciar Membros">
                            <i class="fa-solid fa-users"></i>
                        </a>
                        <a href="gerenciar_perfis.php?action=edit&id=<?= $perfil['id'] ?>" title="Renomear"><i class="fa-solid fa-pen-to-square"></i></a>
                        <a href="gerenciar_perfis.php?action=delete&id=<?= $perfil['id'] ?>" title="Excluir" onclick="return confirm('ATENÇÃO: Excluir este perfil irá apagar TODOS os lançamentos, categorias e dados associados a ele PERMANENTEMENTE. Deseja continuar?')"><i class="fa-solid fa-trash-can"></i></a>
                    <?php else: ?>
                        <small>Apenas o dono pode gerenciar.</small>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>