<?php
require_once 'check_auth.php';

if (!has_permission('lancamento_editar')) { 
    die('Acesso negado.'); 
}

require_once 'config/database.php';

// Verifica se o ID foi passado
if (!isset($_GET['id'])) {
    header("Location: lancamentos.php");
    exit();
}

$id = $_GET['id'];

// Busca o lançamento verificando o ID E o ID do perfil ativo
$stmt = $pdo->prepare("SELECT * FROM lancamentos WHERE id = ? AND id_perfil = ?");
$stmt->execute([$id, $id_perfil_ativo]);
$lancamento = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não encontrar o lançamento (ou se ele não pertencer a este perfil), redireciona
if (!$lancamento) {
    $_SESSION['notification'] = ['message' => 'Lançamento não encontrado ou acesso não permitido.', 'type' => 'error'];
    header("Location: lancamentos.php");
    exit();
}

// Busca categorias e meios de pagamento filtrando pelo perfil ativo
$stmt_cat = $pdo->prepare("SELECT * FROM categorias WHERE id_perfil = ? ORDER BY nome");
$stmt_cat->execute([$id_perfil_ativo]);
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

$stmt_meios = $pdo->prepare("SELECT * FROM meios_pagamento WHERE id_perfil = ? ORDER BY nome");
$stmt_meios->execute([$id_perfil_ativo]);
$meios_pagamento = $stmt_meios->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<h2>Editar Lançamento</h2>
<form action="atualizar_lancamento.php" method="POST" class="account-form">
    <input type="hidden" name="id" value="<?= $lancamento['id'] ?>">

    <div class="form-group">
        <label for="descricao">Descrição</label>
        <input type="text" id="descricao" name="descricao" value="<?= htmlspecialchars($lancamento['descricao']) ?>" required>
    </div>
    
    <div class="form-group">
        <label for="valor">Valor</label>
        <input type="number" id="valor" step="0.01" name="valor" value="<?= $lancamento['valor'] ?>" required>
    </div>

    <div class="form-group">
        <label for="tipo">Tipo</label>
        <select id="tipo" name="tipo" required>
            <option value="entrada" <?= $lancamento['tipo'] == 'entrada' ? 'selected' : '' ?>>Entrada</option>
            <option value="saida" <?= $lancamento['tipo'] == 'saida' ? 'selected' : '' ?>>Saída</option>
        </select>
    </div>

    <div class="form-group">
        <label for="id_categoria">Categoria</label>
        <select id="id_categoria" name="id_categoria" required>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $lancamento['id_categoria'] == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Campo de Meio de Pagamento -->
    <div class="form-group">
        <label for="id_meio_pagamento">Meio de Pagamento</label>
        <select id="id_meio_pagamento" name="id_meio_pagamento">
            <option value="">Nenhum</option>
            <?php foreach ($meios_pagamento as $meio): ?>
                <option value="<?= $meio['id'] ?>" <?= ($lancamento['id_meio_pagamento'] ?? null) == $meio['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($meio['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="data_vencimento">Vencimento</label>
        <input type="date" id="data_vencimento" name="data_vencimento" value="<?= $lancamento['data_vencimento'] ?>" required>
    </div>
    
    <div class="form-group">
        <label for="data_pagamento">Data do Pagamento (deixe em branco se pendente)</label>
        <input type="date" id="data_pagamento" name="data_pagamento" value="<?= $lancamento['data_pagamento'] ?>">
    </div>

    <button type="submit">Atualizar Lançamento</button>
</form>

<?php include 'includes/footer.php'; ?>