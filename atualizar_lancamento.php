<?php
session_start();
require_once 'check_auth.php';

if (!has_permission('lancamento_editar')) {
    die('Acesso negado.');
}

require_once 'config/database.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pegar os dados do formulário
    $id = $_POST['id'];
    $descricao = $_POST['descricao'];
    $valor = $_POST['valor'];
    $tipo = $_POST['tipo'];
    $id_categoria = $_POST['id_categoria'];
    $data_vencimento = $_POST['data_vencimento'];
    $data_pagamento = !empty($_POST['data_pagamento']) ? $_POST['data_pagamento'] : null;
    
    // Captura o meio de pagamento
    $id_meio_pagamento = !empty($_POST['id_meio_pagamento']) ? $_POST['id_meio_pagamento'] : null;

    try {
        // Adicionada a coluna id_meio_pagamento e a verificação de id_perfil
        $sql = "UPDATE lancamentos SET 
                    descricao = ?, 
                    valor = ?, 
                    tipo = ?, 
                    id_categoria = ?,
                    id_meio_pagamento = ?,
                    data_vencimento = ?, 
                    data_pagamento = ? 
                WHERE id = ? AND id_perfil = ?"; // <-- Verificação de segurança
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $descricao, 
            $valor, 
            $tipo, 
            $id_categoria, 
            $id_meio_pagamento, 
            $data_vencimento, 
            $data_pagamento, 
            $id, 
            $id_perfil_ativo // <-- Passa o ID do perfil ativo para a consulta
        ]);

        // Se nenhuma linha foi afetada, pode ser que o lançamento não pertença ao usuário
        if ($stmt->rowCount() === 0) {
        } else {
             // Registra a atividade no log apenas se a atualização foi bem-sucedida
            $detalhes = "Alterou o lançamento '{$descricao}' (ID: {$id}).";
            $username = $_SESSION['username'] ?? 'usuário desconhecido';
            log_activity($pdo, $_SESSION['user_id'], $username, 'Alteração', 'Lançamento', $id, $detalhes);
        }

        $_SESSION['notification'] = ['message' => 'Lançamento atualizado com sucesso!', 'type' => 'success'];
        header("Location: lancamentos.php");
        exit();

    } catch (PDOException $e) {
        die("Erro ao atualizar o lançamento: " . $e->getMessage());
    }
} else {
    header("Location: lancamentos.php");
    exit();
}
?>