<?php
require_once 'check_auth.php';
require_once 'config/database.php';

if (isset($_GET['id'])) {
    $perfil_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Verificação de segurança: confirma que o perfil selecionado realmente pertence ao usuário logado
    $stmt = $pdo->prepare("SELECT id, nome FROM perfis WHERE id = ? AND id_user = ?");
    $stmt->execute([$perfil_id, $user_id]);
    $perfil = $stmt->fetch();

    if ($perfil) {
        // Sucesso: Salva o ID e o nome do perfil na sessão
        $_SESSION['id_perfil_ativo'] = $perfil['id'];
        $_SESSION['nome_perfil_ativo'] = $perfil['nome'];
        
        // Redireciona para o dashboard
        header('Location: index.php');
        exit();
    }
}

// Se o perfil for inválido ou não pertencer ao usuário, volta para a seleção
$_SESSION['notification'] = ['message' => 'Erro: Perfil inválido selecionado.', 'type' => 'error'];
header('Location: selecionar_perfil.php');
exit();
?>