<?php
session_start();
require_once 'check_auth.php';

// Proteção de Acesso
if (!has_permission('lancamento_excluir')) {
    die('Acesso negado.');
}

require_once 'config/database.php';
require_once 'includes/functions.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // 1. Busca os dados do lançamento ANTES de deletar, verificando a posse do perfil
        $stmt_fetch = $pdo->prepare("SELECT descricao, valor FROM lancamentos WHERE id = ? AND id_perfil = ?");
        $stmt_fetch->execute([$id, $id_perfil_ativo]);
        $lancamento_para_log = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

        // Se encontrou o lançamento (significa que pertence ao usuário), então pode deletar
        if ($lancamento_para_log) {
            // 2. Deleta o registro do banco de dados, com a mesma verificação de segurança
            $sql = "DELETE FROM lancamentos WHERE id = ? AND id_perfil = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id, $id_perfil_ativo]);

            // 3. Registra a atividade no log
            $descricao_log = $lancamento_para_log['descricao'];
            $valor_log = number_format($lancamento_para_log['valor'], 2, ',', '.');
            $detalhes = "Excluiu o lançamento '{$descricao_log}' no valor de R$ {$valor_log}.";
            $username = $_SESSION['username'] ?? 'usuário desconhecido';
            log_activity($pdo, $_SESSION['user_id'], $username, 'Exclusão', 'Lançamento', $id, $detalhes);

            $_SESSION['notification'] = ['message' => 'Lançamento excluído com sucesso!', 'type' => 'success'];
        } else {
            // Se não encontrou, significa que o ID é inválido ou não pertence ao perfil do usuário
            $_SESSION['notification'] = ['message' => 'Erro: Lançamento não encontrado ou acesso não permitido.', 'type' => 'error'];
        }
        
        header("Location: lancamentos.php");
        exit();

    } catch (PDOException $e) {
        die("Erro ao excluir o lançamento: " . $e->getMessage());
    }
} else {
    header("Location: lancamentos.php");
    exit();
}
?>