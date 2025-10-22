<?php
require_once 'check_auth.php';
require_once 'config/database.php';

if (!has_permission('usuario_gerenciar')) {
    die('Acesso negado.');
}

$roles = $pdo->query("SELECT * FROM roles ORDER BY nome_funcao")->fetchAll(PDO::FETCH_ASSOC);
include 'includes/header.php';

// **** BLOCO DE NOTIFICAÇÕES (FLASH MESSAGES) ****
if (isset($_SESSION['notification'])) {
    echo "<div class='notification is-{$_SESSION['notification']['type']}'>{$_SESSION['notification']['message']}</div>";
    unset($_SESSION['notification']);
}
// ****************************************************
?>
<h2>Gerenciar Funções e Permissões</h2>
<p>Aqui você pode definir o que cada tipo de usuário pode fazer no sistema.</p>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Função</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $role): ?>
            <tr>
                <td><?= htmlspecialchars($role['nome_funcao']) ?></td>
                <td class="actions-cell">
                    <a href="editar_funcao.php?id=<?= $role['id'] ?>" title="Editar Permissões">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>