<?php
require_once 'check_auth.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Proteção: Apenas usuários com a permissão correta podem acessar esta página.
if (!has_permission('usuario_gerenciar')) {
    die('Acesso negado. Você não tem permissão para gerenciar usuários.');
}

// ID DO SUPER ADMIN - PROTEGIDO
define('SUPER_ADMIN_ID', 1);

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$user_id = $_GET['id'] ?? $_POST['id'] ?? null;
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$role_id = $_POST['role_id'] ?? null;
$editing_user = null;
$current_admin_user = $_SESSION['username'] ?? 'usuário desconhecido';

// Bloqueia a edição do Super Admin por qualquer um que não seja ele mesmo
if (($action === 'edit' || $action === 'update') && $user_id == SUPER_ADMIN_ID && $_SESSION['user_id'] != SUPER_ADMIN_ID) {
    $_SESSION['notification'] = ['message' => 'Você não pode editar o Administrador principal.', 'type' => 'error'];
    header("Location: gerenciar_usuarios.php");
    exit();
}

// Lógica de Ações com Proteção
try {
    if ($action === 'add' && !empty($username) && !empty($password) && !empty($role_id)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashed_password, $role_id]);
        $last_id = $pdo->lastInsertId();
        
        log_activity($pdo, $_SESSION['user_id'], $current_admin_user, 'Criação', 'Usuário', $last_id, "Criou o usuário '{$username}'");
        $_SESSION['notification'] = ['message' => 'Usuário criado com sucesso!', 'type' => 'success'];
        header("Location: gerenciar_usuarios.php");
        exit();

    } elseif ($action === 'update' && $user_id && !empty($username) && !empty($role_id)) {
        // PROTEÇÃO HARD: Impede a alteração da função do Super Admin
        if ($user_id == SUPER_ADMIN_ID) {
            $role_id = 1; // Força a função a ser 'Administrador' (assumindo que role_id 1 é Admin)
        }

        $sql = "UPDATE users SET username = ?, role_id = ? WHERE id = ?";
        $params = [$username, $role_id, $user_id];
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = ?, role_id = ?, password = ? WHERE id = ?";
            $params = [$username, $role_id, $hashed_password, $user_id];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        log_activity($pdo, $_SESSION['user_id'], $current_admin_user, 'Alteração', 'Usuário', $user_id, "Atualizou os dados do usuário '{$username}'");
        $_SESSION['notification'] = ['message' => 'Usuário atualizado com sucesso!', 'type' => 'success'];
        header("Location: gerenciar_usuarios.php");
        exit();

    } elseif ($action === 'delete' && $user_id) {
        // PROTEÇÃO HARD: Impede a exclusão do Super Admin e do próprio usuário
        if ($user_id == $_SESSION['user_id'] || $user_id == SUPER_ADMIN_ID) {
            $message = ($user_id == SUPER_ADMIN_ID) ? 'O usuário Administrador principal não pode ser excluído.' : 'Você não pode excluir sua própria conta.';
            $_SESSION['notification'] = ['message' => $message, 'type' => 'error'];
            header("Location: gerenciar_usuarios.php");
            exit();
        }

        $stmt_old = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt_old->execute([$user_id]);
        $deleted_username = $stmt_old->fetchColumn();
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        log_activity($pdo, $_SESSION['user_id'], $current_admin_user, 'Exclusão', 'Usuário', $user_id, "Excluiu o usuário '{$deleted_username}'");
        $_SESSION['notification'] = ['message' => 'Usuário excluído com sucesso!', 'type' => 'success'];
        header("Location: gerenciar_usuarios.php");
        exit();
    }
} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) { // Erro de entrada duplicada (usuário já existe)
        $_SESSION['notification'] = ['message' => 'Erro: Este nome de usuário já existe.', 'type' => 'error'];
        header("Location: gerenciar_usuarios.php");
        exit();
    }
    die("Erro na operação de usuário: " . $e->getMessage());
}

// Busca dados para os formulários e listagem
if ($action === 'edit' && $user_id) {
    $stmt = $pdo->prepare("SELECT id, username, role_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $editing_user = $stmt->fetch(PDO::FETCH_ASSOC);
}
$users = $pdo->query("SELECT u.id, u.username, r.nome_funcao FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.username")->fetchAll(PDO::FETCH_ASSOC);
$roles = $pdo->query("SELECT * FROM roles ORDER BY nome_funcao")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';

// Bloco de Notificações
if (isset($_SESSION['notification'])) {
    echo "<div class='notification is-{$_SESSION['notification']['type']}'>{$_SESSION['notification']['message']}</div>";
    unset($_SESSION['notification']);
}
?>

<h2>Gerenciar Usuários</h2>

<div class="form-section">
    <form action="gerenciar_usuarios.php" method="POST">
        <?php if ($editing_user): ?>
            <h3>Editando Usuário: <?= htmlspecialchars($editing_user['username']) ?></h3>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $editing_user['id'] ?>">
        <?php else: ?>
            <h3>Adicionar Novo Usuário</h3>
            <input type="hidden" name="action" value="add">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="username">Nome de Usuário:</label>
            <input type="text" name="username" placeholder="Nome de usuário" value="<?= htmlspecialchars($editing_user['username'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Senha:</label>
            <input type="password" name="password" placeholder="<?= $editing_user ? 'Deixe em branco para não alterar' : 'Senha' ?>" <?= $editing_user ? '' : 'required' ?>>
        </div>

        <div class="form-group">
            <label for="role_id">Função:</label>
            <select name="role_id" required <?= ($editing_user && $editing_user['id'] == SUPER_ADMIN_ID) ? 'disabled' : '' ?>>
                <option value="">Selecione uma função</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= $role['id'] ?>" <?= ($editing_user['role_id'] ?? '') == $role['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($role['nome_funcao']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($editing_user && $editing_user['id'] == SUPER_ADMIN_ID): ?>
                <small>A função do Administrador principal não pode ser alterada.</small>
            <?php endif; ?>
        </div>

        <button type="submit"><?= $editing_user ? 'Atualizar Usuário' : 'Adicionar Usuário' ?></button>
        <?php if ($editing_user): ?>
            <a href="gerenciar_usuarios.php" class="btn-clear">Cancelar Edição</a>
        <?php endif; ?>
    </form>
</div>

<hr>

<h3>Usuários Cadastrados</h3>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Nome de Usuário</th>
                <th>Função</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($user['username']) ?>
                    <?php if ($user['id'] == SUPER_ADMIN_ID): ?>
                        <span class="admin-badge">(Super Admin)</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($user['nome_funcao'] ?? 'Sem função') ?></td>
                <td class="actions-cell">
                    <!-- Só mostra o link de edição se não for o Super Admin, ou se for o próprio Super Admin logado -->
                    <?php if ($user['id'] != SUPER_ADMIN_ID || $_SESSION['user_id'] == SUPER_ADMIN_ID): ?>
                        <a href="gerenciar_usuarios.php?action=edit&id=<?= $user['id'] ?>" title="Editar"><i class="fa-solid fa-pen-to-square"></i></a>
                    <?php endif; ?>
                    
                    <!-- Lógica de exclusão -->
                    <?php if ($user['id'] != $_SESSION['user_id'] && $user['id'] != SUPER_ADMIN_ID): ?>
                        <a href="gerenciar_usuarios.php?action=delete&id=<?= $user['id'] ?>" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir o usuário <?= htmlspecialchars($user['username']) ?>?')"><i class="fa-solid fa-trash-can"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>