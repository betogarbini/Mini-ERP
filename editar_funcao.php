<?php
require_once 'check_auth.php';
require_once 'config/database.php';

if (!has_permission('usuario_gerenciar')) {
    die('Acesso negado.');
}

$role_id = $_GET['id'] ?? null;
if (!$role_id) {
    header('Location: gerenciar_funcoes.php');
    exit();
}

// Lógica de atualização quando o formulário é enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Pega as permissões marcadas no formulário
    $submitted_permissions = $_POST['permissions'] ?? [];

    // 2. Apaga todas as permissões antigas desta função
    $stmt_delete = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $stmt_delete->execute([$role_id]);

    // 3. Insere as novas permissões
    if (!empty($submitted_permissions)) {
        $stmt_insert = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($submitted_permissions as $permission_id) {
            $stmt_insert->execute([$role_id, $permission_id]);
        }
    }

    $_SESSION['notification'] = ['message' => 'Permissões atualizadas com sucesso!', 'type' => 'success'];
    header('Location: gerenciar_funcoes.php');
    exit();
}

// Busca os dados para exibir o formulário
$stmt_role = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
$stmt_role->execute([$role_id]);
$role = $stmt_role->fetch();

$all_permissions = $pdo->query("SELECT * FROM permissions ORDER BY nome_permissao")->fetchAll(PDO::FETCH_ASSOC);
$stmt_current_permissions = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
$stmt_current_permissions->execute([$role_id]);
$current_permission_ids = $stmt_current_permissions->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
?>
<h2>Editando Permissões para: <?= htmlspecialchars($role['nome_funcao']) ?></h2>
<form action="editar_funcao.php?id=<?= $role_id ?>" method="POST" class="permissions-form">
    <div class="permission-group">
        <h3>Lançamentos</h3>
        <?php foreach ($all_permissions as $p) if (str_starts_with($p['nome_permissao'], 'lancamento_')): ?>
            <label>
                <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" <?= in_array($p['id'], $current_permission_ids) ? 'checked' : '' ?>>
                <?= htmlspecialchars($p['descricao']) ?>
            </label>
        <?php endif; ?>
    </div>

    <div class="permission-group">
        <h3>Categorias</h3>
        <?php foreach ($all_permissions as $p) if (str_starts_with($p['nome_permissao'], 'categoria_')): ?>
            <label>
                <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" <?= in_array($p['id'], $current_permission_ids) ? 'checked' : '' ?>>
                <?= htmlspecialchars($p['descricao']) ?>
            </label>
        <?php endif; ?>
    </div>

    <div class="permission-group">
        <h3>Histórico</h3>
        <?php foreach ($all_permissions as $p) if (str_starts_with($p['nome_permissao'], 'historico_')): ?>
            <label>
                <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" <?= in_array($p['id'], $current_permission_ids) ? 'checked' : '' ?>>
                <?= htmlspecialchars($p['descricao']) ?>
            </label>
        <?php endif; ?>
    </div>
    
    <div class="permission-group">
        <h3>Administração</h3>
        <?php foreach ($all_permissions as $p) if (str_starts_with($p['nome_permissao'], 'usuario_')): ?>
            <label>
                <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" <?= in_array($p['id'], $current_permission_ids) ? 'checked' : '' ?>>
                <?= htmlspecialchars($p['descricao']) ?>
            </label>
        <?php endif; ?>
    </div>

    <button type="submit">Salvar Permissões</button>
</form>
<?php include 'includes/footer.php'; ?>