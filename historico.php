<?php
require_once 'check_auth.php';

if (!has_permission('historico_ver')) {
    die('Acesso negado.');
}

require_once 'config/database.php';

// **** LÓGICA DE PAGINAÇÃO ****
$itens_por_pagina = (int)($_GET['limit'] ?? 10);
$pagina_atual = (int)($_GET['page'] ?? 1);
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// --- LÓGICA DE FILTRO ---
$user_filter = $_GET['user'] ?? '';
$where_clause = '';
$params = [];

if (!empty($user_filter)) {
    $where_clause = 'WHERE id_user = :user_id';
    $params[':user_id'] = $user_filter;
}

// Busca a lista de usuários para o formulário de filtro
$users = $pdo->query("SELECT id, username FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Conta o total de itens (com filtros) para a paginação
$count_sql = "SELECT COUNT(*) FROM logs $where_clause";
$stmt_count = $pdo->prepare($count_sql);
$stmt_count->execute($params);
$total_itens = $stmt_count->fetchColumn();
$total_paginas = ceil($total_itens / $itens_por_pagina);

// Busca os registros do log com filtro e paginação usando parâmetros nomeados
$sql_final = "SELECT * FROM logs $where_clause ORDER BY data_hora DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql_final);

// Adiciona os parâmetros de filtro (se houver)
if (!empty($user_filter)) {
    $stmt->bindParam(':user_id', $user_filter, PDO::PARAM_INT);
}

// Adiciona os parâmetros de paginação
$stmt->bindParam(':limit', $itens_por_pagina, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<h2>Histórico de Atividades</h2>

<!-- Formulário de Filtro -->
<form method="GET" action="historico.php" class="filter-form">
    <div class="form-group">
        <label for="user">Filtrar por Usuário</label>
        <select name="user" id="user">
            <option value="">Todos os Usuários</option>
            <?php foreach ($users as $user): ?>
                <option value="<?= $user['id'] ?>" <?= $user_filter == $user['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($user['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-actions">
        <button type="submit">Filtrar</button>
        <a href="historico.php" class="btn-clear">Limpar</a>
    </div>
</form>
<div class="pagination-controls">
    <form method="GET" action="historico.php" style="display: inline-block;">
        <?php foreach ($_GET as $key => $value) if (!in_array($key, ['limit', 'page'])) echo "<input type='hidden' name='" . htmlspecialchars($key) . "' value='" . htmlspecialchars($value) . "'>"; ?>
        <label for="limit">Mostrar:</label>
        <select name="limit" id="limit" onchange="this.form.submit()">
            <?php foreach ([10, 25, 50, 100] as $val): ?>
                <option value="<?= $val ?>" <?= $itens_por_pagina == $val ? 'selected' : '' ?>><?= $val ?></option>
            <?php endforeach; ?>
        </select>
        <span> resultados</span>
    </form>
</div>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Data e Hora</th>
                <th>Usuário</th>
                <th>Ação</th>
                <th>Entidade</th>
                <th>Detalhes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($logs) > 0): ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i:s', strtotime($log['data_hora'])) ?></td>
                        <td><?= htmlspecialchars($log['username']) ?></td>
                        <td><?= htmlspecialchars($log['acao']) ?></td>
                        <td>
                            <?= htmlspecialchars($log['entidade']) ?>
                            <?php if ($log['entidade'] === 'Lançamento' && !empty($log['id_entidade'])): ?>
                                <!-- Cria o link para a página de edição do lançamento -->
                                <a href="editar_lancamento.php?id=<?= $log['id_entidade'] ?>" target="_blank" title="Ver/Editar Lançamento">(ID: <?= $log['id_entidade'] ?>)</a>
                            <?php elseif (!empty($log['id_entidade'])): ?>
                                (ID: <?= $log['id_entidade'] ?>)
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($log['detalhes']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align:center;">Nenhuma atividade registrada para os critérios selecionados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<nav class="pagination-nav">
    <ul>
        <?php 
        $query_params = $_GET;
        unset($query_params['page']);
        $base_url = http_build_query($query_params);

        for ($i = 1; $i <= $total_paginas; $i++): ?>
            <li class="<?= $i == $pagina_atual ? 'active' : '' ?>">
                <a href="?page=<?= $i ?>&<?= $base_url ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>

<?php include 'includes/footer.php'; ?>