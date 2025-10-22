<?php
require_once 'check_auth.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Proteção: Apenas usuários com a permissão correta podem acessar esta página.
if (!has_permission('categoria_gerenciar')) {
    die('Acesso negado. Você não tem permissão para gerenciar categorias.');
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? $_POST['id'] ?? null;
$nome = trim($_POST['nome'] ?? '');
$editing_category = null;
$username = $_SESSION['username'] ?? 'usuário desconhecido';

// Lógica de Ações com filtro de perfil
try {
    if ($action === 'add' && !empty($nome)) {
        $stmt = $pdo->prepare("INSERT INTO categorias (nome, id_perfil) VALUES (?, ?)");
        $stmt->execute([$nome, $id_perfil_ativo]);
        $last_id = $pdo->lastInsertId();
        
        log_activity($pdo, $_SESSION['user_id'], $username, 'Criação', 'Categoria', $last_id, "Criou a categoria '{$nome}'");
        
        $_SESSION['notification'] = ['message' => 'Categoria adicionada com sucesso!', 'type' => 'success'];
        header("Location: categorias.php");
        exit();

    } elseif ($action === 'update' && !empty($nome) && $id) {
        // Busca nome antigo para o log
        $stmt_old = $pdo->prepare("SELECT nome FROM categorias WHERE id = ? AND id_perfil = ?");
        $stmt_old->execute([$id, $id_perfil_ativo]);
        $old_name = $stmt_old->fetchColumn();
        
        // verificação de id_perfil ao UPDATE para segurança
        $stmt = $pdo->prepare("UPDATE categorias SET nome = ? WHERE id = ? AND id_perfil = ?");
        $stmt->execute([$nome, $id, $id_perfil_ativo]);
        
        log_activity($pdo, $_SESSION['user_id'], $username, 'Alteração', 'Categoria', $id, "Alterou a categoria de '{$old_name}' para '{$nome}'");

        $_SESSION['notification'] = ['message' => 'Categoria atualizada com sucesso!', 'type' => 'success'];
        header("Location: categorias.php");
        exit();

    } elseif ($action === 'delete' && $id) {
        // Busca nome para o log
        $stmt_old = $pdo->prepare("SELECT nome FROM categorias WHERE id = ? AND id_perfil = ?");
        $stmt_old->execute([$id, $id_perfil_ativo]);
        $old_name = $stmt_old->fetchColumn();
        
        if ($old_name) {
            // verificação de id_perfil ao DELETE para segurança
            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ? AND id_perfil = ?");
            $stmt->execute([$id, $id_perfil_ativo]);
            log_activity($pdo, $_SESSION['user_id'], $username, 'Exclusão', 'Categoria', $id, "Excluiu a categoria '{$old_name}'");
            $_SESSION['notification'] = ['message' => 'Categoria excluída com sucesso!', 'type' => 'success'];
        } else {
            $_SESSION['notification'] = ['message' => 'Erro: Categoria não encontrada ou acesso não permitido.', 'type' => 'error'];
        }
        
        header("Location: categorias.php");
        exit();
    }
} catch (PDOException $e) {
    die("Erro na operação de categoria: " . $e->getMessage());
}

// Busca dados para os formulários e listagem, filtrando pelo perfil
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ? AND id_perfil = ?");
    $stmt->execute([$id, $id_perfil_ativo]);
    $editing_category = $stmt->fetch(PDO::FETCH_ASSOC);
}
// verificação de id_perfil à listagem
$stmt_categorias = $pdo->prepare("SELECT * FROM categorias WHERE id_perfil = ? ORDER BY nome");
$stmt_categorias->execute([$id_perfil_ativo]);
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';

// Bloco de Notificações
if (isset($_SESSION['notification'])) {
    echo "<div class='notification is-{$_SESSION['notification']['type']}'>{$_SESSION['notification']['message']}</div>";
    unset($_SESSION['notification']);
}
?>

<h2>Gerenciar Categorias</h2>
<div class="form-section">
<form action="categorias.php" method="POST">
    <?php if ($editing_category): ?>
        <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= $editing_category['id'] ?>">
        <h3>Editando Categoria</h3>
    <?php else: ?>
        <input type="hidden" name="action" value="add">
        <h3>Nova Categoria</h3>
    <?php endif; ?>
    <div class="form-group">
        <label for="nome">Nome da Categoria:</label>
        <input type="text" name="nome" id="nome" placeholder="Nome da Categoria" value="<?= htmlspecialchars($editing_category['nome'] ?? '') ?>" required>
    </div>
    <button type="submit"><?= $editing_category ? 'Atualizar Categoria' : 'Adicionar Categoria' ?></button>
    <?php if ($editing_category): ?><a href="categorias.php" class="btn-clear">Cancelar Edição</a><?php endif; ?>
</form>
</div>
<hr>
<h3>Categorias Cadastradas</h3>
<div class="table-container">
<table>
    <thead><tr><th>Nome</th><th>Ações</th></tr></thead>
    <tbody>
        <?php foreach ($categorias as $cat): ?>
        <tr>
            <td><?= htmlspecialchars($cat['nome']) ?></td>
            <td class="actions-cell">
                <a href="categorias.php?action=edit&id=<?= $cat['id'] ?>" title="Editar"><i class="fa-solid fa-pen-to-square"></i></a>
                <a href="categorias.php?action=delete&id=<?= $cat['id'] ?>" title="Excluir" onclick="return confirm('Atenção: excluir esta categoria fará com que os lançamentos associados fiquem sem categoria. Continuar?')"><i class="fa-solid fa-trash-can"></i></a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php include 'includes/footer.php'; ?>