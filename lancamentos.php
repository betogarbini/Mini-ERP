<?php
require_once 'check_auth.php';
require_once 'config/database.php';

// **** LÓGICA DE FILTROS ****
$filtro_rapido = $_GET['filtro_rapido'] ?? null;
$titulo_secao = "Resultado"; // Título padrão

// **** LÓGICA DE ORDENAÇÃO ****
$sort_column = $_GET['sort'] ?? 'data_vencimento';
$sort_order = $_GET['order'] ?? 'DESC';

$allowed_columns = ['descricao', 'valor', 'tipo', 'nome_categoria', 'nome_meio_pagamento', 'data_vencimento'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'data_vencimento';
}

$next_order = ($sort_order === 'ASC') ? 'DESC' : 'ASC';
$sql_order = " ORDER BY $sort_column $sort_order";
$sql_limit = " LIMIT :limit OFFSET :offset";

// Função auxiliar para gerar o ícone da seta
function getSortIcon($column, $current_sort, $current_order) {
    // Se esta é a coluna ativa
    if ($column == $current_sort) {
        $icon = $current_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down';
        return '<i class="fas ' . $icon . ' sort-icon active"></i>';
    }
    // Para todas as outras colunas, mostra o ícone de "seta dupla"
    return '<i class="fas fa-sort sort-icon"></i>';
}
// ******************************************************

if ($filtro_rapido) {
    // Definir padrões comuns para todos os filtros rápidos
    $_GET['tipo'] = 'saida';
    $_GET['status'] = 'pendente';

    // Definir datas e título com base no filtro específico
    switch ($filtro_rapido) {
        case 'vencidas':
            $_GET['data_inicio'] = ''; // GARANTE QUE NÃO HÁ DATA DE INÍCIO
            $_GET['data_fim'] = date('Y-m-d', strtotime('-1 day')); // Tudo até ontem
            $titulo_secao = "Exibindo Saídas Vencidas e Não Pagas";
            break;
        
        case 'pagar_hoje':
            $_GET['data_inicio'] = date('Y-m-d');
            $_GET['data_fim'] = date('Y-m-d');
            $titulo_secao = "Saídas Pendentes com Vencimento Hoje";
            break;

        case 'pagar_7dias':
            $_GET['data_inicio'] = date('Y-m-d');
            $_GET['data_fim'] = date('Y-m-d', strtotime('+7 days'));
            $titulo_secao = "Saídas Pendentes com Vencimento nos Próximos 7 Dias";
            break;

        case 'pagar_15dias':
            $_GET['data_inicio'] = date('Y-m-d');
            $_GET['data_fim'] = date('Y-m-d', strtotime('+15 days'));
            $titulo_secao = "Saídas Pendentes com Vencimento nos Próximos 15 Dias";
            break;

        case 'pagar_30dias':
            $_GET['data_inicio'] = date('Y-m-d');
            $_GET['data_fim'] = date('Y-m-d', strtotime('+30 days'));
            $titulo_secao = "Saídas Pendentes com Vencimento nos Próximos 30 Dias";
            break;
    }
}
// ******************************************************

// **** LÓGICA DE PAGINAÇÃO ****
$itens_por_pagina = (int)($_GET['limit'] ?? 10);
$pagina_atual = (int)($_GET['page'] ?? 1);
$offset = ($pagina_atual - 1) * $itens_por_pagina;
// **********************************

// 1. Pegar os valores do formulário
$descricao_filtro = $_GET['descricao'] ?? '';
$data_inicio_filtro = $_GET['data_inicio'] ?? '';
$data_fim_filtro = $_GET['data_fim'] ?? '';
$tipo_filtro = $_GET['tipo'] ?? '';
$categoria_filtro = $_GET['id_categoria'] ?? '';
$status_filtro = $_GET['status'] ?? '';
$meio_pagamento_filtro = $_GET['id_meio_pagamento'] ?? '';

// 2. Construir a consulta SQL dinamicamente
$sql_base = "
    SELECT l.*, c.nome as nome_categoria, mp.nome as nome_meio_pagamento
    FROM lancamentos l
    LEFT JOIN categorias c ON l.id_categoria = c.id
    LEFT JOIN meios_pagamento mp ON l.id_meio_pagamento = mp.id
";

$where_parts = ['l.id_perfil = ?'];
$params = [$id_perfil_ativo];

if (!empty($descricao_filtro)) { $where_parts[] = "l.descricao LIKE ?"; $params[] = "%{$descricao_filtro}%"; }
if (!empty($data_inicio_filtro)) { $where_parts[] = "l.data_vencimento >= ?"; $params[] = $data_inicio_filtro; }
if (!empty($data_fim_filtro)) { $where_parts[] = "l.data_vencimento <= ?"; $params[] = $data_fim_filtro; }
if (!empty($tipo_filtro)) { $where_parts[] = "l.tipo = ?"; $params[] = $tipo_filtro; }
if (!empty($categoria_filtro)) { $where_parts[] = "l.id_categoria = ?"; $params[] = $categoria_filtro; }
if ($status_filtro === 'pendente') { $where_parts[] = "l.data_pagamento IS NULL"; } 
elseif ($status_filtro === 'pago') { $where_parts[] = "l.data_pagamento IS NOT NULL"; }
if (!empty($meio_pagamento_filtro)) { $where_parts[] = "l.id_meio_pagamento = ?"; $params[] = $meio_pagamento_filtro; }

$where_clause = '';
if (!empty($where_parts)) {
    $where_clause = ' WHERE ' . implode(' AND ', $where_parts);
}

// Conta o total de itens (com filtros) para a paginação
$count_sql = "SELECT COUNT(*) FROM lancamentos l $where_clause";
$stmt_count = $pdo->prepare($count_sql);
$stmt_count->execute($params);
$total_itens = $stmt_count->fetchColumn();
$total_paginas = ceil($total_itens / $itens_por_pagina);

// 3. Executar a consulta final com paginação
$sql_final = $sql_base . $where_clause . $sql_order . " LIMIT ? OFFSET ?";
$stmt_lanc = $pdo->prepare($sql_final);

// Adiciona os parâmetros de filtro normais
$i = 1;
foreach ($params as $param) {
    $stmt_lanc->bindValue($i++, $param);
}

// Adiciona os parâmetros de paginação no final
$stmt_lanc->bindValue($i++, $itens_por_pagina, PDO::PARAM_INT);
$stmt_lanc->bindValue($i++, $offset, PDO::PARAM_INT);

$stmt_lanc->execute();
$lancamentos = $stmt_lanc->fetchAll(PDO::FETCH_ASSOC);

// Buscar categorias para os selects
$stmt_cat = $pdo->prepare("SELECT * FROM categorias WHERE id_perfil = ? ORDER BY nome");
$stmt_cat->execute([$id_perfil_ativo]);
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

// Buscar meios de pagamento para os selects
$stmt_meios = $pdo->prepare("SELECT * FROM meios_pagamento WHERE id_perfil = ? ORDER BY nome");
$stmt_meios->execute([$id_perfil_ativo]);
$meios_pagamento = $stmt_meios->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';

// **** BLOCO DE NOTIFICAÇÕES ****
$notification_message = '';
$notification_type = '';

// Verifica se existe uma notificação na sessão
if (isset($_SESSION['notification'])) {
    $notification_message = $_SESSION['notification']['message'];
    $notification_type = $_SESSION['notification']['type'];
    
    // Remove a notificação da sessão para que não apareça novamente
    unset($_SESSION['notification']);
}

// Exibe a notificação se houver uma mensagem
if ($notification_message) {
    echo "<div class='notification is-{$notification_type}'>{$notification_message}</div>";
}
// ****************************************************
?>

<h2>Filtros de Lançamentos</h2>
<form class="filter-form" method="GET" action="lancamentos.php">
    <input type="hidden" name="limit" value="<?= $itens_por_pagina ?>">
    <div class="form-group">
        <label for="descricao">Descrição</label>
        <input type="text" id="descricao" name="descricao" placeholder="Parte da descrição..." value="<?= htmlspecialchars($descricao_filtro) ?>">
    </div>
    <div class="form-group">
        <label for="data_inicio">De:</label>
        <input type="date" id="data_inicio" name="data_inicio" value="<?= $data_inicio_filtro ?>">
    </div>
    <div class="form-group">
        <label for="data_fim">Até:</label>
        <input type="date" id="data_fim" name="data_fim" value="<?= $data_fim_filtro ?>">
    </div>
    <div class="form-group">
        <label for="tipo">Tipo</label>
        <select id="tipo" name="tipo">
            <option value="">Todos</option>
            <option value="entrada" <?= $tipo_filtro == 'entrada' ? 'selected' : '' ?>>Entrada</option>
            <option value="saida" <?= $tipo_filtro == 'saida' ? 'selected' : '' ?>>Saída</option>
        </select>
    </div>
    <div class="form-group">
        <label for="id_categoria">Categoria</label>
        <select id="id_categoria" name="id_categoria">
            <option value="">Todas</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $categoria_filtro == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">Todos</option>
            <option value="pendente" <?= $status_filtro == 'pendente' ? 'selected' : '' ?>>Pendente</option>
            <option value="pago" <?= $status_filtro == 'pago' ? 'selected' : '' ?>>Pago</option>
        </select>
    </div>
    <div class="form-group">
        <label for="id_meio_pagamento">Meio de Pagamento</label>
        <select id="id_meio_pagamento" name="id_meio_pagamento">
            <option value="">Todos</option>
            <?php foreach ($meios_pagamento as $meio): ?>
                <option value="<?= $meio['id'] ?>" <?= $meio_pagamento_filtro == $meio['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($meio['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-actions">
        <button type="submit">Filtrar</button>
        <a href="lancamentos.php" class="btn-clear">Limpar</a>
    </div>
</form>

<hr>

<?php if (has_permission('lancamento_criar')): ?>
<h2>Adicionar Novo Lançamento</h2>
<!-- Formulário de adicionar -->
<form action="add_lancamento.php" method="POST" class="add-form">
    <input type="text" name="descricao" placeholder="Descrição" required>
    <input type="number" step="0.01" name="valor" placeholder="Valor" required>
    <select name="tipo" required> <option value="entrada">Entrada</option> <option value="saida">Saída</option> </select>
    <select name="id_categoria" required>
        <option value="">Categoria</option>
        <?php foreach ($categorias as $cat): ?> <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option> <?php endforeach; ?>
    </select>
    <label for="data_vencimento">Venc:</label>
    <input type="date" id="data_vencimento" name="data_vencimento" required>
    <button type="submit">Salvar Lançamento</button>
</form>
<hr>
<?php endif; ?>

<h2><?= $titulo_secao ?> (<?= count($lancamentos) ?> lançamentos encontrados)</h2>

<div class="pagination-controls">
    <form method="GET" action="lancamentos.php" style="display: inline-block;">
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
    <?php
// Constrói a query string dos filtros e paginação atuais para manter ao reordenar
$filter_params = http_build_query(array_filter([
    'descricao' => $descricao_filtro,
    'data_inicio' => $data_inicio_filtro,
    'data_fim' => $data_fim_filtro,
    'tipo' => $tipo_filtro,
    'id_categoria' => $categoria_filtro,
    'status' => $status_filtro,
    'id_meio_pagamento' => $meio_pagamento_filtro,
    'limit' => $itens_por_pagina
]));
?>
<thead>
    <tr>
        <th><a href="?<?= $filter_params ?>&sort=descricao&order=<?= $next_order ?>">Descrição <?= getSortIcon('descricao', $sort_column, $sort_order) ?></a></th>
        <th><a href="?<?= $filter_params ?>&sort=valor&order=<?= $next_order ?>">Valor <?= getSortIcon('valor', $sort_column, $sort_order) ?></a></th>
        <th><a href="?<?= $filter_params ?>&sort=tipo&order=<?= $next_order ?>">Tipo <?= getSortIcon('tipo', $sort_column, $sort_order) ?></a></th>
        <th><a href="?<?= $filter_params ?>&sort=nome_categoria&order=<?= $next_order ?>">Categoria <?= getSortIcon('nome_categoria', $sort_column, $sort_order) ?></a></th>
        <th><a href="?<?= $filter_params ?>&sort=nome_meio_pagamento&order=<?= $next_order ?>">Meio Pagto. <?= getSortIcon('nome_meio_pagamento', $sort_column, $sort_order) ?></a></th>
        <th><a href="?<?= $filter_params ?>&sort=data_vencimento&order=<?= $next_order ?>">Vencimento <?= getSortIcon('data_vencimento', $sort_column, $sort_order) ?></a></th>
        <th>Status</th>
        <th>Ações</th>
    </tr>
</thead>
    <tbody>
        <?php if (count($lancamentos) > 0): ?>
            <?php foreach ($lancamentos as $lanc): ?>
                <tr>
                    <td><?= htmlspecialchars($lanc['descricao']) ?></td>
                    <td class="<?= $lanc['tipo'] ?>">R$ <?= number_format($lanc['valor'], 2, ',', '.') ?></td>
                    <td><?= ucfirst($lanc['tipo']) ?></td>
                    <td><?= htmlspecialchars($lanc['nome_categoria'] ?? 'Sem Categoria') ?></td>
                    <td><?= htmlspecialchars($lanc['nome_meio_pagamento'] ?? '-') ?></td>
                    <td><?= date('d/m/Y', strtotime($lanc['data_vencimento'])) ?></td>
                    <td>
                        <?php if ($lanc['data_pagamento']): ?>
                            <span class="entrada">Pago em <?= date('d/m/Y', strtotime($lanc['data_pagamento'])) ?></span>
                        <?php else: ?>
                            <span class="saida">Pendente</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions-cell">
    <button class="anexo-btn <?= !empty($lanc['comprovante_path']) ? 'has-comprovante' : '' ?>" 
            data-id="<?= $lanc['id'] ?>" 
            data-comprovante="<?= htmlspecialchars($lanc['comprovante_path'] ?? '') ?>" 
            title="Gerenciar Anexo">
        <i class="fa-solid fa-paperclip"></i>
    </button>
    <?php if (has_permission('lancamento_pagar')): ?>
        <?php if ($lanc['data_pagamento']): ?>
            <a href="gerenciar_pagamento.php?id=<?= $lanc['id'] ?>&action=desmarcar" title="Desmarcar Pagamento"><i class="fa-solid fa-circle-xmark"></i></a>
        <?php else: ?>
            <button class="open-modal-btn" data-id="<?= $lanc['id'] ?>" title="Marcar como Pago/Recebido"><i class="fa-solid fa-circle-check"></i></button>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (has_permission('lancamento_editar')): ?>
        <a href="editar_lancamento.php?id=<?= $lanc['id'] ?>" title="Editar"><i class="fa-solid fa-pen-to-square"></i></a>
    <?php endif; ?>

    <?php if (has_permission('lancamento_excluir')): ?>
        <a href="excluir_lancamento.php?id=<?= $lanc['id'] ?>" title="Excluir" onclick="return confirm('Tem certeza?')"><i class="fa-solid fa-trash-can"></i></a>
    <?php endif; ?>
</td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" style="text-align:center;">Nenhum lançamento encontrado para os critérios selecionados.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
</div>

<nav class="pagination-nav">
    <ul>
        <?php 
            // Constrói a URL base com todos os filtros E o limite de itens, menos a página
            $query_params = $_GET;
            unset($query_params['page']);
            $query_params['limit'] = $itens_por_pagina; // Garante que o 'limit' está sempre presente
            $base_url = http_build_query($query_params);

            for ($i = 1; $i <= $total_paginas; $i++): ?>
                <li class="<?= $i == $pagina_atual ? 'active' : '' ?>">
                    <a href="?page=<?= $i ?>&<?= $base_url ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
    </ul>
</nav>

<!-- **** HTML DA MODAL DE PAGAMENTO **** -->
<div id="paymentModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Confirmar Pagamento</h2>
        <p>Selecione o meio de pagamento utilizado:</p>
        <form id="paymentForm" action="gerenciar_pagamento.php" method="GET">
            <input type="hidden" name="id" id="lancamentoId">
            <input type="hidden" name="action" value="pagar">
            
            <select name="id_meio_pagamento" id="meioPagamentoSelect" required>
                <option value="">Selecione...</option>
                <?php foreach ($meios_pagamento as $meio): ?>
                    <option value="<?= $meio['id'] ?>"><?= htmlspecialchars($meio['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit">Confirmar Pagamento</button>
        </form>
    </div>
</div>

<!-- **** HTML DA MODAL DE ANEXOS **** -->
<div id="anexoModal" class="modal">
    <div class="modal-content">
        <span class="modal-close-btn">&times;</span>
        <h2>Gerenciar Comprovante</h2>
        
        <div id="anexo-display"></div>

        <hr>

        <form id="anexoForm" action="upload_comprovante.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_lancamento" id="anexoLancamentoId">
            <div class="form-group" style="flex-direction: column; align-items: flex-start;">
                <label for="comprovanteFile">Enviar/Substituir Comprovante (JPG, PNG, PDF):</label>
                <input type="file" name="comprovanteFile" id="comprovanteFile" required>
            </div>
            <button type="submit">Enviar Arquivo</button>
        </form>
    </div>
</div>
<!-- ************************************* -->

<?php include 'includes/footer.php'; ?>