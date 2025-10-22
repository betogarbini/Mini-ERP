<?php
session_start();
require_once 'check_auth.php';
require_once 'config/database.php';

if (isset($_GET['id'])) {
    $id_lancamento = (int)$_GET['id'];
    
    // Busca o nome do arquivo para poder deletá-lo do servidor
    $stmt_old = $pdo->prepare("SELECT comprovante_path FROM lancamentos WHERE id = ? AND id_perfil = ?");
    $stmt_old->execute([$id_lancamento, $id_perfil_ativo]);
    $old_file = $stmt_old->fetchColumn();
    
    if ($old_file) {
        // Apaga a referência no banco de dados
        $stmt_update = $pdo->prepare("UPDATE lancamentos SET comprovante_path = NULL WHERE id = ? AND id_perfil = ?");
        $stmt_update->execute([$id_lancamento, $id_perfil_ativo]);
        
        // Apaga o arquivo físico do servidor
        $filePath = __DIR__ . '/uploads/comprovantes/' . $old_file;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        $_SESSION['notification'] = ['message' => 'Comprovante excluído com sucesso!', 'type' => 'success'];
    } else {
        $_SESSION['notification'] = ['message' => 'Nenhum comprovante encontrado para excluir.', 'type' => 'error'];
    }
}
header('Location: lancamentos.php');
exit();
?>