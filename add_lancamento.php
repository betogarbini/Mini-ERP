<?php
session_start();
require_once 'check_auth.php'; // Esta linha já define a variável $id_perfil_ativo

if (!has_permission('lancamento_criar')) { 
    die('Acesso negado.'); 
}

require_once 'config/database.php';
require_once 'includes/functions.php';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação dos dados
    if (empty($_POST['descricao']) || empty($_POST['valor']) || empty($_POST['tipo']) || empty($_POST['data_vencimento']) || empty($_POST['id_categoria'])) {
        die("Erro: Todos os campos são obrigatórios.");
    }

    $descricao = $_POST['descricao'];
    $valor = $_POST['valor'];
    $tipo = $_POST['tipo'];
    $data_vencimento = $_POST['data_vencimento'];
    $id_categoria = $_POST['id_categoria'];
    $id_meio_pagamento = null; 

    try {
        $sql = "INSERT INTO lancamentos (descricao, valor, tipo, data_vencimento, id_categoria, id_meio_pagamento, id_perfil) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$descricao, $valor, $tipo, $data_vencimento, $id_categoria, $id_meio_pagamento, $id_perfil_ativo]);
        
        $last_id = $pdo->lastInsertId();

        // Registra a atividade no log
        $detalhes = "Criou o lançamento '{$descricao}' no valor de R$ " . number_format($valor, 2, ',', '.');
        $username = $_SESSION['username'] ?? 'usuário desconhecido'; 
        log_activity($pdo, $_SESSION['user_id'], $username, 'Criação', 'Lançamento', $last_id, $detalhes);

        // Salva a mensagem na sessão
        $_SESSION['notification'] = ['message' => 'Lançamento adicionado com sucesso!', 'type' => 'success'];
        
        // Redireciona para a URL limpa
        header("Location: lancamentos.php");
        exit();

    } catch (PDOException $e) {
        // Em caso de erro, exibe uma mensagem
        die("Erro ao inserir o lançamento: ". $e->getMessage());
    }
} else {
    // Se alguém tentar acessar este arquivo diretamente, apenas redireciona
    header("Location: lancamentos.php");
    exit();
}
?>