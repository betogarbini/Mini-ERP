<?php
session_start();
require_once 'check_auth.php';

if (!has_permission('lancamento_pagar')) {
    die('Acesso negado.');
}

require_once 'config/database.php';
require_once 'includes/functions.php';

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];

    try {
        // Busca os dados do lançamento para o log, já verificando a posse do perfil
        $stmt_fetch = $pdo->prepare("SELECT descricao FROM lancamentos WHERE id = ? AND id_perfil = ?");
        $stmt_fetch->execute([$id, $id_perfil_ativo]);
        $lancamento_para_log = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

        // Se o lançamento não for encontrado ou não pertencer ao perfil, não faz nada e redireciona com erro
        if (!$lancamento_para_log) {
            $_SESSION['notification'] = ['message' => 'Erro: Lançamento não encontrado ou acesso não permitido.', 'type' => 'error'];
            header("Location: lancamentos.php");
            exit();
        }

        $descricao_log = $lancamento_para_log['descricao'];
        $notification_message = '';
        $sql = '';
        $params = [];
        $detalhes_log = '';

        if ($action === 'pagar') {
            $id_meio_pagamento = $_GET['id_meio_pagamento'] ?? null;
            if ($id_meio_pagamento) {
                // verificação de id_perfil
                $sql = "UPDATE lancamentos SET data_pagamento = CURDATE(), id_meio_pagamento = ? WHERE id = ? AND id_perfil = ?";
                $params = [$id_meio_pagamento, $id, $id_perfil_ativo];
                $notification_message = 'Lançamento marcado como pago!';
                $detalhes_log = "Marcou como pago o lançamento '{$descricao_log}'.";
            } else {
                // Caso o meio de pagamento não seja enviado (erro no formulário da modal)
                $_SESSION['notification'] = ['message' => 'Erro: Meio de pagamento não selecionado.', 'type' => 'error'];
                header("Location: lancamentos.php");
                exit();
            }
        } 
        elseif ($action === 'desmarcar') {
            // verificação de id_perfil
            $sql = "UPDATE lancamentos SET data_pagamento = NULL, id_meio_pagamento = NULL WHERE id = ? AND id_perfil = ?";
            $params = [$id, $id_perfil_ativo];
            $notification_message = 'Pagamento desmarcado com sucesso!';
            $detalhes_log = "Desmarcou o pagamento do lançamento '{$descricao_log}'.";
        }
        
        if (!empty($sql)) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Registra a atividade no log
            $username = $_SESSION['username'] ?? 'usuário desconhecido';
            log_activity($pdo, $_SESSION['user_id'], $username, 'Pagamento', 'Lançamento', $id, $detalhes_log);

            $_SESSION['notification'] = ['message' => $notification_message, 'type' => 'success'];
        }

        header("Location: lancamentos.php");
        exit();

    } catch (PDOException $e) {
        die("Erro ao gerenciar pagamento: " . $e->getMessage());
    }
} else {
    header("Location: lancamentos.php");
    exit();
}
?>