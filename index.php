<?php
require_once 'check_auth.php';
require_once 'config/database.php';

// Array de meses para o filtro principal
$meses_pt = [ 1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro' ];

// Filtros da URL
$periodo_pagar = $_GET['periodo_pagar'] ?? 'hoje';
$mes_filtro = $_GET['mes'] ?? date('m');
$ano_filtro = $_GET['ano'] ?? date('Y');

// --- LÓGICA DE CÁLCULOS DO DASHBOARD (COM FILTRO DE PERFIL) ---

// Cláusula base para filtrar por perfil
$where_perfil = " AND id_perfil = ? ";

// Contas Vencidas
$stmt_vencidas_total = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) FROM lancamentos WHERE tipo = 'saida' AND data_pagamento IS NULL AND data_vencimento < CURDATE() $where_perfil");
$stmt_vencidas_total->execute([$id_perfil_ativo]);
$vencidas_total = $stmt_vencidas_total->fetchColumn();

$stmt_vencidas_count = $pdo->prepare("SELECT COUNT(id) FROM lancamentos WHERE tipo = 'saida' AND data_pagamento IS NULL AND data_vencimento < CURDATE() $where_perfil");
$stmt_vencidas_count->execute([$id_perfil_ativo]);
$vencidas_count = $stmt_vencidas_count->fetchColumn();

// Saldo Geral
$stmt_saldo = $pdo->prepare("SELECT 
    (COALESCE((SELECT SUM(valor) FROM lancamentos WHERE tipo = 'entrada' AND data_pagamento IS NOT NULL AND id_perfil = ?), 0)) - 
    (COALESCE((SELECT SUM(valor) FROM lancamentos WHERE tipo = 'saida' AND data_pagamento IS NOT NULL AND id_perfil = ?), 0)) 
    AS saldo_geral");
$stmt_saldo->execute([$id_perfil_ativo, $id_perfil_ativo]);
$saldo_geral = $stmt_saldo->fetchColumn();

// A Pagar no Período (7, 15, 30 dias)
$sql_where_pagar = ''; $link_filtro_rapido = '';
switch ($periodo_pagar) {
    case '7dias': $sql_where_pagar = "AND data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)"; $link_filtro_rapido = "pagar_7dias"; break;
    case '15dias': $sql_where_pagar = "AND data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)"; $link_filtro_rapido = "pagar_15dias"; break;
    case '30dias': $sql_where_pagar = "AND data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"; $link_filtro_rapido = "pagar_30dias"; break;
    default: $sql_where_pagar = "AND data_vencimento = CURDATE()"; $link_filtro_rapido = "pagar_hoje"; break;
}
$stmt_pagar_periodo = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) FROM lancamentos WHERE tipo = 'saida' AND data_pagamento IS NULL $sql_where_pagar $where_perfil");
$stmt_pagar_periodo->execute([$id_perfil_ativo]);
$a_pagar_periodo = $stmt_pagar_periodo->fetchColumn();

// --- Cálculos do Resumo Mensal ---
$where_pago_mes = "AND MONTH(data_pagamento) = ? AND YEAR(data_pagamento) = ?";
$where_vencimento_mes = "AND MONTH(data_vencimento) = ? AND YEAR(data_vencimento) = ?";

$stmt_receitas_mes = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) FROM lancamentos WHERE tipo = 'entrada' AND data_pagamento IS NOT NULL $where_perfil $where_pago_mes");
$stmt_receitas_mes->execute([$id_perfil_ativo, $mes_filtro, $ano_filtro]);
$receitas_mes = $stmt_receitas_mes->fetchColumn();

$stmt_despesas_mes = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) FROM lancamentos WHERE tipo = 'saida' AND data_pagamento IS NOT NULL $where_perfil $where_pago_mes");
$stmt_despesas_mes->execute([$id_perfil_ativo, $mes_filtro, $ano_filtro]);
$despesas_mes = $stmt_despesas_mes->fetchColumn();

$balanco_mes = $receitas_mes - $despesas_mes;

$stmt_receber = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) FROM lancamentos WHERE tipo = 'entrada' AND data_pagamento IS NULL $where_perfil $where_vencimento_mes");
$stmt_receber->execute([$id_perfil_ativo, $mes_filtro, $ano_filtro]);
$a_receber = $stmt_receber->fetchColumn();

$stmt_pagar = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) FROM lancamentos WHERE tipo = 'saida' AND data_pagamento IS NULL $where_perfil $where_vencimento_mes");
$stmt_pagar->execute([$id_perfil_ativo, $mes_filtro, $ano_filtro]);
$a_pagar = $stmt_pagar->fetchColumn();

// --- Cálculos para os Gráficos ---
$where_grafico = "AND data_pagamento IS NOT NULL AND MONTH(data_pagamento) = ? AND YEAR(data_pagamento) = ?";

$stmt_despesas_grafico = $pdo->prepare("SELECT c.nome, SUM(l.valor) as total FROM lancamentos l JOIN categorias c ON l.id_categoria = c.id WHERE l.tipo = 'saida' AND l.id_perfil = ? $where_grafico GROUP BY c.nome ORDER BY total DESC");
$stmt_despesas_grafico->execute([$id_perfil_ativo, $mes_filtro, $ano_filtro]);
$despesas_por_categoria = $stmt_despesas_grafico->fetchAll(PDO::FETCH_ASSOC);

$stmt_receitas_grafico = $pdo->prepare("SELECT c.nome, SUM(l.valor) as total FROM lancamentos l JOIN categorias c ON l.id_categoria = c.id WHERE l.tipo = 'entrada' AND l.id_perfil = ? $where_grafico GROUP BY c.nome ORDER BY total DESC");
$stmt_receitas_grafico->execute([$id_perfil_ativo, $mes_filtro, $ano_filtro]);
$receitas_por_categoria = $stmt_receitas_grafico->fetchAll(PDO::FETCH_ASSOC);

$despesas_labels = json_encode(array_column($despesas_por_categoria, 'nome')); $despesas_data = json_encode(array_column($despesas_por_categoria, 'total'));
$receitas_labels = json_encode(array_column($receitas_por_categoria, 'nome')); $receitas_data = json_encode(array_column($receitas_por_categoria, 'total'));

include 'includes/header.php';
?>
<!-- **** "SALDO TOTAL" **** -->
        <div class="saldo-total-display">
            <strong><span>Saldo:</span></strong>
            <strong class="<?= $saldo_geral >= 0 ? 'entrada' : 'saida' ?>">
                R$ <?= number_format($saldo_geral, 2, ',', '.') ?>
            </strong>
        </div>
<main>
    <div class="dashboard-header">
        <h2>Dashboard Financeiro</h2>
        
        <form method="GET" action="index.php">
            <input type="hidden" name="periodo_pagar" value="<?= htmlspecialchars($periodo_pagar) ?>">
            <select name="mes"><?php foreach ($meses_pt as $num => $nome): ?><option value="<?= str_pad($num, 2, '0', STR_PAD_LEFT) ?>" <?= $num == $mes_filtro ? 'selected' : '' ?>><?= $nome ?></option><?php endforeach; ?></select>
            <select name="ano"><?php for ($y = date('Y') - 5; $y <= date('Y') + 1; $y++): ?><option value="<?= $y ?>" <?= $y == $ano_filtro ? 'selected' : '' ?>><?= $y ?></option><?php endfor; ?></select>
            <button type="submit">Filtrar</button>
        </form>
    </div>

    <div class="dashboard-section">
        <h3>Resumo Geral</h3>
        <div class="dashboard-cards">
             <div class="card card-balanco">
                <h4>Balanço de <?= $meses_pt[(int)$mes_filtro] ?> de <?= $ano_filtro ?></h4>
                
                <!-- ÍCONE -->
                <i class="fas fa-chart-line card-icon"></i>

                <p class="<?= $balanco_mes >= 0 ? 'entrada' : 'saida' ?>">R$ <?= number_format($balanco_mes, 2, ',', '.') ?></p>
            </div>
            
            <div class="card">
                <form method="GET" action="index.php" class="card-filter-form">
                    <input type="hidden" name="mes" value="<?= htmlspecialchars($mes_filtro) ?>"><input type="hidden" name="ano" value="<?= htmlspecialchars($ano_filtro) ?>">
                    <select name="periodo_pagar" onchange="this.form.submit()">
                        <option value="hoje" <?= $periodo_pagar == 'hoje' ? 'selected' : '' ?>>A Pagar Hoje</option>
                        <option value="7dias" <?= $periodo_pagar == '7dias' ? 'selected' : '' ?>>Próximos 7 dias</option>
                        <option value="15dias" <?= $periodo_pagar == '15dias' ? 'selected' : '' ?>>Próximos 15 dias</option>
                        <option value="30dias" <?= $periodo_pagar == '30dias' ? 'selected' : '' ?>>Próximos 30 dias</option>
                    </select>
                </form>
                <p class="saida">R$ <?= number_format($a_pagar_periodo, 2, ',', '.') ?></p>
                <a href="lancamentos.php?filtro_rapido=<?= $link_filtro_rapido ?>" class="btn-detalhes">Ver Lançamentos</a>
            </div>
            <?php if ($vencidas_count > 0): ?>
            <div class="card card-vencido">
                <h4>Contas Vencidas (<?= $vencidas_count ?>)</h4>
                <p class="saida">R$ <?= number_format($vencidas_total, 2, ',', '.') ?></p>
                <a href="lancamentos.php?filtro_rapido=vencidas" class="btn-detalhes">Ver Vencidas</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="dashboard-section">
        <h3>Resumo Mensal Detalhado</h3>
        <div class="dashboard-cards dashboard-cards-4-cols">
            <div class="card"><h4>Receitas Recebidas no Mês</h4><p class="entrada">R$ <?= number_format($receitas_mes, 2, ',', '.') ?></p></div>
            <div class="card"><h4>Despesas Pagas no Mês</h4><p class="saida">R$ <?= number_format($despesas_mes, 2, ',', '.') ?></p></div>
            <div class="card"><h4>A Receber (Venc. no mês)</h4><p class="entrada">R$ <?= number_format($a_receber, 2, ',', '.') ?></p></div>
            <div class="card"><h4>A Pagar (Venc. no mês)</h4><p class="saida">R$ <?= number_format($a_pagar, 2, ',', '.') ?></p></div>
        </div>
    </div>

    <div class="dashboard-section">
        <h3>Análise Gráfica de <?= $meses_pt[(int)$mes_filtro] ?> de <?= $ano_filtro ?></h3>
        <div class="charts-container">
            <div class="chart-box"><h4>Receitas por Categoria</h4><canvas id="receitasChart"></canvas></div>
            <div class="chart-box"><h4>Despesas por Categoria</h4><canvas id="despesasChart"></canvas></div>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>